<?php
session_name('chiller_app');
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../controller/db/config.php';

$logHandlerUrl = 'http://localhost/chiller/apps/controller/handler/log_activity_handler.php';
$currentUserId = $_SESSION['user_id'] ?? 0;

// Enable verbose error reporting for debugging. REMOVE THESE LINES IN PRODUCTION!
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$response = [
    'success' => false,
    'message' => '',
    'data' => null,
    'csv_data' => null
];

// Validate database connection
if ($conn->connect_error) {
    $response['message'] = 'Database connection failed: ' . $conn->connect_error;
    http_response_code(500);
    echo json_encode($response);
    exit();
}

// Get request data based on method
$requestData = [];
$action = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        $requestData = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $response['message'] = 'Invalid JSON format: ' . json_last_error_msg();
            http_response_code(400);
            echo json_encode($response);
            exit();
        }
    }
    $action = $requestData['action'] ?? null;
} else {
    // For GET requests, primarily for 'get_item_list', 'get_subitem_list', 'get_current_stock'
    $action = $_GET['action'] ?? null;
    $requestData = $_GET; // Assign $_GET to requestData for consistency
}

if (!$action) {
    $response['message'] = 'Action parameter is required';
    http_response_code(400);
    echo json_encode($response);
    exit();
}

// Transaction flag
$inTransaction = false;
$stmt = null; // $stmt will not be used for direct queries, but keep it for finally block consistency

try {
    // Ensure user_nama is available for adjustment actions
    if (in_array($action, ['save_barcode_adjustments', 'save_global_adjustments'])) {
        if (!isset($_SESSION['user_nama'])) {
            error_log("Authentication Error: User session not valid for action '$action'.");
            throw new RuntimeException('Sesi tidak valid, silakan login kembali', 401);
        }
        $userName = $conn->real_escape_string($_SESSION['user_nama']); // Escape for security
    }

    switch ($action) {
        case 'get_item_list':
            $sql = "SELECT DISTINCT item FROM items WHERE isActive = 1 ORDER BY item ASC";
            $result = $conn->query($sql);
            if ($result === false) {
                error_log("Database Error: Failed to get items - " . $conn->error);
                throw new Exception('Failed to get items: ' . $conn->error);
            }
            $response = [
                'success' => true,
                'message' => 'Items fetched successfully',
                'data' => $result->fetch_all(MYSQLI_ASSOC)
            ];
            break;

        case 'get_subitem_list':
            $sql = "SELECT DISTINCT subitem FROM subitems ORDER BY subitem ASC";
            $result = $conn->query($sql);
            if ($result === false) {
                error_log("Database Error: Failed to get subitems - " . $conn->error);
                throw new Exception('Failed to get subitems: ' . $conn->error);
            }
            $response = [
                'success' => true,
                'message' => 'Subitems fetched successfully',
                'data' => $result->fetch_all(MYSQLI_ASSOC)
            ];
            break;

        case 'search_barcode':
            $barcode_input = $requestData['barcode'] ?? '';
            $barcode = $conn->real_escape_string(ltrim($barcode_input, '0'));
            $storage = $conn->real_escape_string($requestData['storage'] ?? '');
            $adjustment_date = $conn->real_escape_string($requestData['adjustment_date'] ?? '');
            $adjustment_type = $conn->real_escape_string($requestData['adjustment_type'] ?? '');
            $user_input = $conn->real_escape_string($requestData['user_input'] ?? '');

            if (empty($barcode) || empty($storage)) {
                throw new InvalidArgumentException('Barcode dan lokasi penyimpanan (storage) wajib diisi.');
            }

            // --- CEK APAKAH BARCODE SUDAH DISCAN DI adjust_tmp ---
            $isAlreadyScanned = false;
            $scannedByUser = '';
            $scannedAt = '';

            $checkDuplicateSql = "SELECT user_input, created_at FROM adjust_tmp 
                         WHERE barcode = '$barcode' 
                         AND storage = '$storage' 
                         AND adjustment_date = '$adjustment_date' 
                         AND adjustment_type = '$adjustment_type'
                         AND deleted_at IS NULL 
                         LIMIT 1";

            $checkResult = $conn->query($checkDuplicateSql);
            if ($checkResult && $checkResult->num_rows > 0) {
                $duplicateData = $checkResult->fetch_assoc();
                $isAlreadyScanned = true;
                $scannedByUser = $duplicateData['user_input'];
                $scannedAt = date('Y-m-d H:i', strtotime($duplicateData['created_at']));
            }
            // --- END CEK DUPLIKAT ---

            // 1. Ambil data transaksi masuk terakhir untuk barcode ini (sama dengan getTransactionByBarcodeIn)
            $query_last_in = "
        SELECT 
            id,
            item,
            storage,
            ekor_pack,
            quantity_kg,
            barcode,
            created_at,
            user
        FROM transactions
        WHERE barcode = '$barcode'
            AND storage = '$storage'
            AND transaction_type IN ('in', 'gabung-new', 'transfer-in', 'adjustment-new', 'adjustment')
            AND deleted_at IS NULL
            AND quantity_kg > 0
        ORDER BY created_at DESC
        LIMIT 1
    ";

            $result_last_in = $conn->query($query_last_in);
            $last_in_data = $result_last_in ? $result_last_in->fetch_assoc() : null;

            $ekor_stock_current = 0;
            $berat_stock_current = 0;
            $id_in = null;
            $item = '';
            $created_at = '';
            $user_in = '';
            $full_item = '';

            if ($last_in_data) {
                $id = $last_in_data['id'];
                $full_item = $last_in_data['item'];
                $created_at = $last_in_data['created_at'];
                $user_in = $last_in_data['user'];
                $id_in = $last_in_data['id'];
                $storage = $last_in_data['storage'];

                // 2. Hitung stok barcode dengan query yang sama seperti getTransactionByBarcodeIn
                $query_stock_barcode = "
            SELECT 
                SUM(ekor) AS current_ekor_barcode, 
                SUM(berat) AS current_kg_barcode,
                tanggal_stock,
                IF(tanggal_stock >= '$adjustment_date', 'baru', 'lama') AS status_bahan
            FROM ( 
                SELECT * FROM (
                    SELECT id, ekor_pack AS ekor, quantity_kg AS berat, `date` AS tanggal_stock
                    FROM transactions
                    WHERE quantity_kg > 0
                        AND barcode = '$barcode'
                        AND transaction_type IN ('in', 'transfer-in','gabung-new', 'adjustment-new', 'adjustment')
                        AND deleted_at IS NULL
                        AND `date` <= '$adjustment_date'
                    ORDER BY id DESC
                    LIMIT 1
                ) AS C
                UNION ALL
                SELECT B.id_in, B.ekor, B.berat, '' AS tanggal_stock
                FROM (
                    SELECT id, SUM(ekor_pack) AS ekor, SUM(quantity_kg) AS berat 
                    FROM transactions
                    WHERE 1=1
                        AND barcode = '$barcode'
                        AND transaction_type IN ('in', 'transfer-in','gabung-new', 'adjustment-new', 'adjustment')
                        AND deleted_at IS NULL
                        AND `date` <= '$adjustment_date'
                        AND quantity_kg > 0
                    GROUP BY id
                    ORDER BY id DESC
                    LIMIT 1
                ) AS A
                LEFT JOIN (
                    SELECT id_in, SUM(ekor_pack) AS ekor, SUM(quantity_kg) AS berat 
                    FROM transactions
                    WHERE 1=1
                        AND barcode = '$barcode'
                        AND id_in IS NOT NULL
                        AND `date` <= '$adjustment_date'
                        AND deleted_at IS NULL
                    GROUP BY id_in
                ) AS B
                ON A.id = B.id_in
            ) AS B
        ";

                $result_stock_barcode = $conn->query($query_stock_barcode);
                if ($result_stock_barcode && $result_stock_barcode->num_rows > 0) {
                    $stock_data_barcode = $result_stock_barcode->fetch_assoc();
                    $ekor_stock_current = (float) ($stock_data_barcode['current_ekor_barcode'] ?? 0);
                    $berat_stock_current = (float) ($stock_data_barcode['current_kg_barcode'] ?? 0);
                }

                // 3. Hitung stok global untuk item di storage ini (sama seperti getTransactionByBarcodeIn)
                $stok_ekor_global = 0;
                $stok_berat_global = 0;

                if ($full_item) {
                    $escaped_item = $conn->real_escape_string($full_item);
                    $escaped_storage = $conn->real_escape_string($storage);

                    $query_global = "
                SELECT
                    SUM(ekor_pack) AS current_ekor_stock,
                    SUM(quantity_kg) AS current_kg_stock
                FROM
                    transactions
                WHERE
                    item = '$escaped_item'
                    AND storage = '$escaped_storage'
                    AND deleted_at IS NULL
                    AND date <= '$adjustment_date'
            ";

                    $result_global = $conn->query($query_global);
                    if ($result_global && $result_global->num_rows > 0) {
                        $stock_data_global = $result_global->fetch_assoc();
                        $stok_ekor_global = (float) ($stock_data_global['current_ekor_stock'] ?? 0);
                        $stok_berat_global = (float) ($stock_data_global['current_kg_stock'] ?? 0);
                    }
                }
            }

            // 4. Siapkan response
            if (!$last_in_data) {
                $response = [
                    'success' => false,
                    'message' => 'Barcode tidak ditemukan di lokasi penyimpanan ini.',
                    'data' => [
                        'id' => null,
                        'id_in' => null,
                        'barcode' => $barcode,
                        'item' => '',
                        'subitem' => null,
                        'storage' => $storage,
                        'created_at' => null,
                        'user_in' => null,
                        'ekor_stock_current' => 0.0,
                        'berat_stock_current' => 0.0,
                        'stok_ekor_global' => 0.0,
                        'stok_berat_global' => 0.0,
                        'is_already_scanned' => $isAlreadyScanned,
                        'scanned_by_user' => $scannedByUser,
                        'scanned_at' => $scannedAt
                    ]
                ];
            } else {
                // Handle item dengan subitem format "item : subitem"
                $fullItem = $full_item;
                $item_name = $fullItem;
                $subitem_name = null;

                if (strpos($fullItem, ' : ') !== false) {
                    list($item_name, $subitem_name) = explode(' : ', $fullItem, 2);
                    $item_name = trim($item_name);
                    $subitem_name = trim($subitem_name);
                }

                $response = [
                    'success' => true,
                    'message' => 'Data barcode berhasil dimuat.',
                    'data' => [
                        'id' => $id,
                        'id_in' => $id_in,
                        'barcode' => $barcode,
                        'item' => $item_name,
                        'subitem' => $subitem_name,
                        'storage' => $storage,
                        'created_at' => $created_at,
                        'user_in' => $user_in,
                        'ekor_stock_current' => $ekor_stock_current,
                        'berat_stock_current' => $berat_stock_current,
                        'stok_ekor_global' => $stok_ekor_global,
                        'stok_berat_global' => $stok_berat_global,
                        'is_already_scanned' => $isAlreadyScanned,
                        'scanned_by_user' => $scannedByUser,
                        'scanned_at' => $scannedAt
                    ]
                ];
            }
            break;

        case 'get_current_stock':
            $storage = $conn->real_escape_string($requestData['storage'] ?? ''); // Escape input
            $adjustment_date = $conn->real_escape_string($requestData['adjustment_date'] ?? date('Y-m-d')); // Gunakan tanggal adjustment, default hari ini

            if (empty($storage)) {
                throw new InvalidArgumentException('Lokasi penyimpanan (storage) wajib diisi.');
            }

            // PERBAIKAN: Gunakan adjustment_date untuk filter tanggal, bukan date('Y-m-d')
            $selectStockClauses = [
                't.storage',
                't.item AS full_item_name',
                'IF(LOCATE(" : ", t.item) > 0, SUBSTRING_INDEX(t.item, " : ", 1), t.item) AS item_name_parsed',
                'IF(LOCATE(" : ", t.item) > 0, SUBSTRING_INDEX(t.item, " : ", -1), "") AS subitem_name_parsed',

                // PERBAIKAN: Gunakan adjustment_date sebagai batas tanggal
                'SUM(CASE WHEN t.date <= ? THEN t.ekor_pack ELSE 0 END) AS final_ep',
                'SUM(CASE WHEN t.date <= ? THEN t.quantity_kg ELSE 0 END) AS final_kg'
            ];

            $whereStockClauses = [
                "t.deleted_at IS NULL",
                "t.storage = ?"
            ];

            $groupByStockClauses = ['t.storage', 'full_item_name'];

            $paramsStock = [
                $adjustment_date, // PERBAIKAN: Gunakan adjustment_date, bukan currentDate
                $adjustment_date, // PERBAIKAN: Gunakan adjustment_date, bukan currentDate
                $storage      // storage filter
            ];

            $typesStock = "sss"; // 3 parameter string

            $queryStock = "SELECT " . implode(', ', $selectStockClauses) . " 
                   FROM transactions t 
                   WHERE " . implode(' AND ', $whereStockClauses) . " 
                   GROUP BY " . implode(', ', $groupByStockClauses);

            // Hanya sertakan baris yang memiliki stok saat ini tidak nol
            $queryStock .= " HAVING final_ep != 0 OR final_kg != 0";
            $queryStock .= " ORDER BY full_item_name ASC";

            $stmt = $conn->prepare($queryStock);
            if (!$stmt) {
                error_log("Database Error: Prepare failed for get_current_stock - " . $conn->error);
                throw new Exception("Gagal mempersiapkan query untuk mendapatkan stok saat ini: " . $conn->error);
            }

            $stmt->bind_param($typesStock, ...$paramsStock);
            $stmt->execute();
            $result = $stmt->get_result();
            $stockData = $result->fetch_all(MYSQLI_ASSOC);

            $processedData = [];
            foreach ($stockData as $row) {
                $processedData[] = [
                    'item' => $row['item_name_parsed'],
                    'subitem' => $row['subitem_name_parsed'],
                    'total_ekor' => (float)$row['final_ep'],
                    'total_berat' => (float)$row['final_kg']
                ];
            }

            $response = [
                'success' => true,
                'message' => 'Data stok saat ini berhasil dimuat.',
                'data' => $processedData
            ];
            break;

        // --- BARU: VALIDASI BARCODE SUDAH ADA DI ADJUST_TMP ---
        case 'check_barcode_in_adjust_tmp':
            $barcode = $conn->real_escape_string($requestData['barcode'] ?? '');
            $adjustment_date = $conn->real_escape_string($requestData['adjustment_date'] ?? '');
            $storage = $conn->real_escape_string($requestData['storage'] ?? '');
            $adjustment_type = $conn->real_escape_string($requestData['adjustment_type'] ?? '');
            $current_user = $conn->real_escape_string($requestData['current_user'] ?? '');

            if (empty($barcode) || empty($adjustment_date) || empty($storage) || empty($adjustment_type)) {
                throw new InvalidArgumentException('Parameter tidak lengkap untuk validasi barcode.');
            }

            // Bersihkan barcode (hapus leading zeros)
            $cleaned_barcode = ltrim($barcode, '0');

            // ========== PERBAIKAN: AMBIL DATA LENGKAP DARI ADJUST_TMP ==========
            $checkSql = "SELECT id, row_unique_id, user_input, created_at, 
                            item, subitem, ekor_stock_current, berat_stock_current,
                            ekor_new, berat_new, keterangan 
                    FROM adjust_tmp 
                    WHERE barcode = '$cleaned_barcode' 
                    AND adjustment_date = '$adjustment_date' 
                    AND storage = '$storage' 
                    AND adjustment_type = '$adjustment_type'
                    AND deleted_at IS NULL 
                    LIMIT 1";

            $result = $conn->query($checkSql);

            $is_exists = false;
            $user_input = '';
            $created_at = '';
            $is_current_user = false;
            $existing_data = null;

            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $is_exists = true;
                $user_input = $row['user_input'];
                $created_at = date('Y-m-d H:i:s', strtotime($row['created_at']));
                $is_current_user = ($user_input === $current_user);

                // Siapkan data existing untuk frontend
                $existing_data = [
                    'id' => $row['id'],
                    'row_unique_id' => $row['row_unique_id'],
                    'item' => $row['item'],
                    'subitem' => $row['subitem'],
                    'ekor_stock_current' => $row['ekor_stock_current'],
                    'berat_stock_current' => $row['berat_stock_current'],
                    'ekor_new' => $row['ekor_new'],
                    'berat_new' => $row['berat_new'],
                    'keterangan' => $row['keterangan']
                ];
            }

            $response = [
                'success' => true,
                'data' => [
                    'is_exists' => $is_exists,
                    'user_input' => $user_input,
                    'created_at' => $created_at,
                    'is_current_user' => $is_current_user,
                    'existing_data' => $existing_data
                ]
            ];
            break;

        // --- START OF ADJUSTMENT SAVE LOGIC (MODIFIED: NO BIND_PARAM) ---
        case 'save_barcode_adjustments':
            // Validate required data
            $requiredFields = ['adjustment_date', 'adjustment_type', 'storage', 'details'];
            foreach ($requiredFields as $field) {
                if (!isset($requestData[$field]) || (empty($requestData[$field]) && $requestData[$field] !== 0 && $requestData[$field] !== '0')) {
                    error_log("Validation Error (barcode): Missing or empty required field '$field'. Request Data: " . json_encode($requestData));
                    throw new InvalidArgumentException("Field '$field' harus diisi untuk Adjustment barcode.");
                }
            }

            $adjustmentDate = $conn->real_escape_string($requestData['adjustment_date']);
            $adjustmentType = $conn->real_escape_string($requestData['adjustment_type']);
            $storage = $conn->real_escape_string($requestData['storage']);
            $details = $requestData['details'];

            if (!is_array($details) || empty($details)) {
                error_log("Validation Error (barcode): Details array is empty or not an array. Details: " . json_encode($details));
                throw new InvalidArgumentException('Data Adjustment barcode tidak boleh kosong.');
            }

            // Start transaction
            $conn->begin_transaction();
            $inTransaction = true;

            $insertedRows = 0;
            $insertedRecordIds = [];

            foreach ($details as $idx => $row) {
                // Ensure required fields exist in each detail row
                $rowRequiredFields = ['barcode', 'item', 'ekor_adjustment', 'berat_adjustment'];
                foreach ($rowRequiredFields as $field) {
                    if (!isset($row[$field])) {
                        error_log("Skipping invalid barcode detail row at index $idx: Missing required field '$field'. Row: " . json_encode($row));
                        throw new InvalidArgumentException("Detail Adjustment barcode pada baris ke-" . ($idx + 1) . " tidak lengkap. Field '$field' hilang.");
                    }
                }

                // Determine id_in and the specific transaction type - SEMUA MENJADI 'adjustment-new'
                $id_in_value = $row['id_in'] ?? null;
                $transaction_type_for_row = 'adjustment-new';

                if ($id_in_value === null || $id_in_value === '' || $id_in_value === 'null') {
                    $id_in_sql = "NULL";
                } else {
                    $id_in_sql = "'" . $conn->real_escape_string($id_in_value) . "'";
                }

                // Clean barcode: Trim spaces, then remove leading zeros
                $rawBarcode = $row['barcode'] ?? '';
                $cleanedBarcode = ltrim(trim($rawBarcode), '0');
                $barcode_sql_value = ($cleanedBarcode !== '') ? "'" . $conn->real_escape_string($cleanedBarcode) . "'" : "NULL";

                // Cast numeric values
                $ekorAdjustment = (float)$row['ekor_adjustment'];
                $beratAdjustment = (float)$row['berat_adjustment'];

                // Skip if no adjustment
                if ($ekorAdjustment == 0 && $beratAdjustment == 0) {
                    error_log("Skipping barcode detail row at index $idx: No adjustment (ekor and berat are both 0). Row: " . json_encode($row));
                    continue;
                }

                // Keterangan handling
                $currentKeterangan = !empty($row['keterangan']) ? $conn->real_escape_string($row['keterangan']) : 'Adjustment stok via barcode';

                // Combine item and subitem if available
                $itemName = $conn->real_escape_string($row['item']);
                if (!empty($row['subitem'])) {
                    $subitem_escaped = $conn->real_escape_string($row['subitem']);
                    $itemName .= ' : ' . $subitem_escaped;
                }

                // UBAH: Gunakan user_input dari request, bukan dari session
                $userForThisRow = !empty($row['user_input']) ? $conn->real_escape_string($row['user_input']) : $userName;

                // Construct the SQL query
                $sql = "
        INSERT INTO transactions (
            date, from_location, to_location, storage, item,
            ekor_pack, quantity_kg, transaction_type, keterangan,
            user, adjustment_type, barcode, id_in
        ) VALUES (
            '$adjustmentDate',
            'adjustment',
            '$storage',
            '$storage',
            '$itemName',
            $ekorAdjustment,
            $beratAdjustment,
            '$transaction_type_for_row', 
            '$currentKeterangan',
            '$userForThisRow',
            '$adjustmentType',
            $barcode_sql_value,
            $id_in_sql 
        )";

                error_log("Executing SQL (barcode adjustment) for row " . ($idx + 1) . " - User: $userForThisRow: " . $sql);

                if (!$conn->query($sql)) {
                    error_log("Database Error (barcode): Failed to execute query for row " . ($idx + 1) . " - " . $conn->error . " for row data: " . json_encode($row));
                    throw new Exception('Gagal menyimpan Adjustment barcode pada baris ke-' . ($idx + 1) . ': ' . $conn->error);
                }

                $newlyInsertedId = $conn->insert_id;
                $insertedRecordIds[] = $newlyInsertedId;
                $insertedRows++;
            }

            if ($insertedRows === 0) {
                $conn->rollback();
                $inTransaction = false;
                error_log("No valid barcode adjustments processed. Rolling back transaction.");
                throw new InvalidArgumentException('Tidak ada Adjustment barcode yang valid untuk disimpan. Pastikan nilai Adjustment tidak nol.');
            }

            $conn->commit();
            $inTransaction = false;
            error_log("Successfully committed barcode adjustments. Inserted rows: " . $insertedRows);

            $response = [
                'success' => true,
                'message' => "Berhasil menyimpan $insertedRows Adjustment barcode.",
                'inserted_count' => $insertedRows,
                'inserted_ids' => $insertedRecordIds
            ];
            break;

        case 'save_global_adjustments':
            // Validate required data
            $requiredFields = ['adjustment_date', 'adjustment_type', 'storage', 'details'];
            foreach ($requiredFields as $field) {
                if (!isset($requestData[$field])) {
                    error_log("Validation Error (global): Missing required field '$field'. Request Data: " . json_encode($requestData));
                    throw new InvalidArgumentException("Field '$field' harus diisi untuk Adjustment global.");
                }
            }

            $adjustmentDate = $conn->real_escape_string($requestData['adjustment_date']);
            $adjustmentType = $conn->real_escape_string($requestData['adjustment_type']);
            $storage = $conn->real_escape_string($requestData['storage']);
            $details = $requestData['details'];

            if (!is_array($details) || empty($details)) {
                error_log("Validation Error (global): Details array is empty or not an array. Details: " . json_encode($details));
                throw new InvalidArgumentException('Data Adjustment global tidak boleh kosong.');
            }

            // Start transaction
            $conn->begin_transaction();
            $inTransaction = true;

            $insertedRows = 0;
            $insertedRecordIds = [];

            foreach ($details as $idx => $row) {
                // Ensure required fields exist in each detail row
                $rowRequiredFields = ['item', 'ekor_adjustment', 'berat_adjustment'];
                foreach ($rowRequiredFields as $field) {
                    if (!isset($row[$field])) {
                        error_log("Skipping invalid global detail row at index $idx: Missing required field '$field'. Row: " . json_encode($row));
                        throw new InvalidArgumentException("Detail Adjustment global pada baris ke-" . ($idx + 1) . " tidak lengkap. Field '$field' hilang.");
                    }
                }

                $ekorAdjustment = (float)$row['ekor_adjustment'];
                $beratAdjustment = (float)$row['berat_adjustment'];

                // Skip if no adjustment
                if ($ekorAdjustment == 0 && $beratAdjustment == 0) {
                    error_log("Skipping global detail row at index $idx: No adjustment (ekor and berat are both 0). Row: " . json_encode($row));
                    continue;
                }

                $currentKeterangan = !empty($row['keterangan']) ? $conn->real_escape_string($row['keterangan']) : 'Adjustment stok global';

                // Combine item and subitem if available
                $itemName = $conn->real_escape_string($row['item']);
                if (!empty($row['subitem'])) {
                    $itemName .= ' : ' . $conn->real_escape_string($row['subitem']);
                }

                // Untuk global adjustments, gunakan userName dari session (bukan dari request)
                $userForThisRow = $userName;

                // For global adjustments, barcode and id_in must be NULL
                $sql = "
        INSERT INTO transactions (
            date, from_location, to_location, storage, item,
            ekor_pack, quantity_kg, transaction_type, keterangan,
            user, adjustment_type, barcode, id_in
        ) VALUES (
            '$adjustmentDate',
            'adjustment',
            '$storage',
            '$storage',
            '$itemName',
            $ekorAdjustment,
            $beratAdjustment,
            'adjustment',
            '$currentKeterangan',
            '$userForThisRow',
            '$adjustmentType',
            NULL,
            NULL
        )";

                error_log("Executing SQL (global adjustment) for row " . ($idx + 1) . " - User: $userForThisRow: " . $sql);

                if (!$conn->query($sql)) {
                    error_log("Database Error (global): Failed to execute query for row " . ($idx + 1) . " - " . $conn->error . " for row data: " . json_encode($row));
                    throw new Exception('Gagal menyimpan Adjustment global pada baris ke-' . ($idx + 1) . ': ' . $conn->error);
                }

                $newlyInsertedId = $conn->insert_id;
                $insertedRecordIds[] = $newlyInsertedId;
                $insertedRows++;
            }

            if ($insertedRows === 0) {
                $conn->rollback();
                $inTransaction = false;
                error_log("No valid global adjustments processed. Rolling back transaction.");
                throw new InvalidArgumentException('Tidak ada Adjustment global yang valid untuk disimpan. Pastikan nilai Adjustment tidak nol.');
            }

            $conn->commit();
            $inTransaction = false;
            error_log("Successfully committed global adjustments. Inserted rows: " . $insertedRows);

            $response = [
                'success' => true,
                'message' => "Berhasil menyimpan $insertedRows Adjustment global.",
                'inserted_count' => $insertedRows,
                'inserted_ids' => $insertedRecordIds
            ];
            break;

        default:
            throw new InvalidArgumentException('Action tidak valid.');
    }
} catch (InvalidArgumentException $e) {
    if ($inTransaction) {
        $conn->rollback();
        error_log("Transaction rolled back due to InvalidArgumentException: " . $e->getMessage());
    }
    $response['message'] = $e->getMessage();
    http_response_code(400); // Bad Request
    error_log("InvalidArgumentException: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
} catch (RuntimeException $e) {
    if ($inTransaction) {
        $conn->rollback();
        error_log("Transaction rolled back due to RuntimeException: " . $e->getMessage());
    }
    $response['message'] = $e->getMessage();
    http_response_code($e->getCode() ?: 500); // Use specific code if available, else 500
    error_log("RuntimeException: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
} catch (Exception $e) {
    if ($inTransaction) {
        $conn->rollback();
        error_log("Transaction rolled back due to general Exception: " . $e->getMessage());
    }
    $response['message'] = 'Terjadi kesalahan server: ' . $e->getMessage();
    http_response_code(500); // Internal Server Error
    error_log('Exception: ' . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()); // Log detailed error
} finally {
    // Ensure statement is closed (relevant for search_barcode/get_current_stock)
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }

    // Ensure connection is closed
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }

    // Send response
    echo json_encode($response);
    exit();
}

/**
 * Generate CSV report for adjustments
 * @param string $date Adjustment Date
 * @param string $storage Storage Location
 * @param string $user User who made the adjustment
 * @param array $data Array of adjustment details
 * @return string CSV content
 */
function generateAdjustmentReport($date, $storage, $user, $data)
{
    $csvFile = fopen('php://temp', 'r+');

    // Header
    fputcsv($csvFile, ["LAPORAN Adjustment STOK"]);
    fputcsv($csvFile, ["Tanggal: " . $date]);
    fputcsv($csvFile, ["Gudang: " . $storage]);
    fputcsv($csvFile, []); // Empty row for spacing

    // Columns
    fputcsv($csvFile, [
        "Barcode",
        "Item",
        "Subitem",
        "Stok Awal (Ekor)",
        "Stok Baru (Ekor)",
        "Adjustment (Ekor)",
        "Stok Awal (Kg)",
        "Stok Baru (Kg)",
        "Adjustment (Kg)",
        "Keterangan"
    ]);

    // Data
    foreach ($data as $row) {
        // Ensure all necessary keys exist, provide defaults if not
        $barcode = $row['barcode'] ?? '';
        $item = $row['item'] ?? '';
        $subitem = $row['subitem'] ?? '';
        $ekor_stock_current = $row['ekor_stock_current'] ?? 0;
        $ekor_new = $row['ekor_new'] ?? 0;
        $ekor_adjustment = $row['ekor_adjustment'] ?? 0;
        $berat_stock_current = $row['berat_stock_current'] ?? 0;
        $berat_new = $row['berat_new'] ?? 0;
        $berat_adjustment = $row['berat_adjustment'] ?? 0;
        $keterangan = $row['keterangan'] ?? '';

        fputcsv($csvFile, [
            $barcode,
            $item,
            $subitem,
            number_format($ekor_stock_current, 2),
            number_format($ekor_new, 2),
            ($ekor_adjustment > 0 ? '+' : '') . number_format($ekor_adjustment, 2),
            number_format($berat_stock_current, 2),
            number_format($berat_new, 2),
            ($berat_adjustment > 0 ? '+' : '') . number_format($berat_adjustment, 2),
            $keterangan
        ]);
    }

    // Footer
    fputcsv($csvFile, []); // Empty row for spacing
    fputcsv($csvFile, ["Disesuaikan oleh:", $user]);
    fputcsv($csvFile, ["Tanggal cetak:", date('Y-m-d H:i:s')]);

    rewind($csvFile); // Rewind the file pointer to the beginning
    $content = stream_get_contents($csvFile); // Get all contents
    fclose($csvFile); // Close the file handle

    return $content;
}
