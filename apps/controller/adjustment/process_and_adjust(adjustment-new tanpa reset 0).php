<?php
// controller/adjustment/process_and_adjust.php
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
            $barcode_input = $requestData['barcode'] ?? ''; // Ambil input mentah
            $barcode = $conn->real_escape_string(ltrim($barcode_input, '0')); // Hapus semua nol di awal
            $storage = $conn->real_escape_string($requestData['storage'] ?? '');

            if (empty($barcode) || empty($storage)) {
                throw new InvalidArgumentException('Barcode dan lokasi penyimpanan (storage) wajib diisi.');
            }


            $query_local =
                "SELECT 
                    id, id AS id_in_transaction, item, `storage`,
                    SUM(ekor) AS ekor_stock_current, 
                    SUM(berat) AS berat_stock_current, created_at, `user`, barcode
                FROM ( 
                    SELECT * FROM (
                        SELECT id, item, barcode, `storage`,  
                            SUM(ekor_pack) AS ekor, 
                            SUM(quantity_kg) AS berat ,
                            created_at, `user`
                        FROM transactions
                        WHERE quantity_kg>0
                            AND barcode = '$barcode'
                            AND deleted_at IS NULL
                            AND transaction_type IN ('in', 'transfer-in', 'gabung-new', 'adjustment-new')
                            AND storage LIKE '$storage'
                        GROUP BY id, item, barcode, `storage`
                        ORDER BY id DESC
                        LIMIT 1
                    ) AS C
                    UNION ALL
                        SELECT A.id, A.item, A.barcode, A.`storage`, B.ekor, B.berat, '' AS created_at, '' AS `user`
                        FROM (
                        SELECT id, item, barcode, `storage`, SUM(ekor_pack) AS ekor, SUM(quantity_kg) AS berat 
                        FROM transactions
                        WHERE quantity_kg>0
                            AND barcode = '$barcode'
                            AND transaction_type IN ('in', 'transfer-in', 'gabung-new', 'adjustment-new')
                            AND deleted_at IS NULL
                            AND storage LIKE '$storage'
                        GROUP BY id, item, barcode, `storage`
                        ORDER BY id DESC
                        LIMIT 1
                    ) AS A
                    LEFT JOIN (
                        SELECT id_in, SUM(ekor_pack) AS ekor, SUM(quantity_kg) AS berat 
                        FROM transactions
                        WHERE 1=1
                            AND barcode = '$barcode'
                            AND id_in IS NOT NULL
                            AND deleted_at IS NULL
                        GROUP BY id_in
                    ) AS B
                    ON A.id = B.id_in
                ) AS B
                GROUP BY id_in_transaction, barcode
            ";

            // $query_local = 
            // "SELECT 
            //     SUM(ekor) AS current_ekor_barcode, 
            //     SUM(berat) AS current_kg_barcode
            // FROM ( 
            //     SELECT * FROM (
            //         SELECT id, SUM(ekor_pack) AS ekor, SUM(quantity_kg) AS berat 
            //         FROM transactions
            //         WHERE quantity_kg>0
            //             AND barcode = '$barcode'
            //             AND transaction_type IN ('in', 'transfer-in', 'gabung-new', 'adjustment-new')
            //             AND deleted_at IS NULL
            //             AND storage LIKE '$storage'
            //         GROUP BY id
            //         ORDER BY id DESC
            //         LIMIT 1
            //     ) AS C
            //     UNION ALL
            //     SELECT B.id_in, B.ekor, B.berat
            //     FROM (
            //         SELECT id, SUM(ekor_pack) AS ekor, SUM(quantity_kg) AS berat 
            //         FROM transactions
            //         WHERE 1=1
            //             AND barcode = '$barcode'
            //             AND transaction_type IN ('in', 'transfer-in', 'gabung-new', 'adjustment-new')
            //             AND deleted_at IS NULL
            //             AND storage LIKE '$storage'
            //         GROUP BY id
            //         ORDER BY id DESC
            //         LIMIT 1
            //     ) AS A
            //     LEFT JOIN (
            //         SELECT id_in, SUM(ekor_pack) AS ekor, SUM(quantity_kg) AS berat 
            //         FROM transactions
            //         WHERE 1=1
            //             AND barcode = '$barcode'
            //             AND id_in IS NOT NULL
            //             AND deleted_at IS NULL
            //         GROUP BY id_in
            //     ) AS B
            //     ON A.id = B.id_in
            // ) AS B ;";


            $result_local_barcode = $conn->query($query_local);

            if (!$result_local_barcode) {
                error_log("Database Error: Query failed for search_barcode (local stock) - " . $conn->error);
                throw new Exception("Gagal menjalankan query pencarian barcode lokal: " . $conn->error);
            }

            $data_local_barcode = $result_local_barcode->fetch_assoc();

            $stok_ekor_global = 0.0;
            $stok_berat_global = 0.0;
            $item_name_for_global_stock = '';

            if ($data_local_barcode) {
                $fullItem = $data_local_barcode['item'];
                $item_name_for_global_stock = $conn->real_escape_string($fullItem);

                // --- Query 2: Hitung STOK GLOBAL ITEM ---
                $query_global = "
            SELECT
                SUM(ekor_pack) AS total_ekor_item_global,
                SUM(quantity_kg) AS total_berat_item_global
            FROM transactions
            WHERE item = '$item_name_for_global_stock' AND storage = '$storage' AND deleted_at IS NULL
            ";

                $result_global_item_stock = $conn->query($query_global);

                if (!$result_global_item_stock) {
                    error_log("Database Error: Query failed for search_barcode (global stock) - " . $conn->error);
                    throw new Exception("Gagal menjalankan query stok item global: " . $conn->error);
                }

                $data_global_item_stock = $result_global_item_stock->fetch_assoc();
                $stok_ekor_global = (float)($data_global_item_stock['total_ekor_item_global'] ?? 0);
                $stok_berat_global = (float)($data_global_item_stock['total_berat_item_global'] ?? 0);
            }

            if (!$data_local_barcode) {
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
                        'stok_ekor_global' => $stok_ekor_global,
                        'stok_berat_global' => $stok_berat_global,
                    ]
                ];
            } else {
                $fullItem = $data_local_barcode['item'];
                $item = $fullItem;
                $subitem = null;

                if (strpos($fullItem, ' : ') !== false) {
                    list($item, $subitem) = explode(' : ', $fullItem, 2);
                    $item = trim($item);
                    $subitem = trim($subitem);
                }

                $response = [
                    'success' => true,
                    'message' => 'Data barcode berhasil dimuat.',
                    'data' => [
                        'id' => $data_local_barcode['id'],
                        'id_in' => $data_local_barcode['id_in_transaction'],
                        'barcode' => $data_local_barcode['barcode'],
                        'item' => $item,
                        'subitem' => $subitem,
                        'storage' => $data_local_barcode['storage'],
                        'created_at' => $data_local_barcode['created_at'],
                        'user_in' => $data_local_barcode['user'],
                        'ekor_stock_current' => (float)$data_local_barcode['ekor_stock_current'],
                        'berat_stock_current' => (float)$data_local_barcode['berat_stock_current'],
                        'stok_ekor_global' => $stok_ekor_global,
                        'stok_berat_global' => $stok_berat_global,
                    ]
                ];
            }
            break;



        case 'get_current_stock':
            $storage = $conn->real_escape_string($requestData['storage'] ?? ''); // Escape input
            if (empty($storage)) {
                throw new InvalidArgumentException('Lokasi penyimpanan (storage) wajib diisi.');
            }

            // Using prepared statement here is still recommended for search
            $stmt = $conn->prepare("
                SELECT item,
                        SUM(ekor_pack) AS total_ekor,
                        SUM(quantity_kg) AS total_berat
                FROM transactions
                WHERE storage = ? AND deleted_at IS NULL
                GROUP BY item
                HAVING total_ekor != 0 OR total_berat != 0
                ORDER BY item ASC
            ");

            if (!$stmt) {
                error_log("Database Error: Prepare failed for get_current_stock - " . $conn->error);
                throw new Exception("Gagal mempersiapkan query untuk mendapatkan stok saat ini: " . $conn->error);
            }

            $stmt->bind_param("s", $storage);
            $stmt->execute();
            $result = $stmt->get_result();
            $stockData = $result->fetch_all(MYSQLI_ASSOC);

            $processedData = [];
            foreach ($stockData as $row) {
                $fullItem = $row['item'];
                $item = $fullItem;
                $subitem = null;

                if (strpos($fullItem, ' : ') !== false) {
                    list($item, $subitem) = explode(' : ', $fullItem, 2);
                    $item = trim($item);
                    $subitem = trim($subitem);
                }

                $processedData[] = [
                    'item' => $item,
                    'subitem' => $subitem,
                    'total_ekor' => (float)$row['total_ekor'],
                    'total_berat' => (float)$row['total_berat']
                ];
            }

            $response = [
                'success' => true,
                'message' => 'Data stok saat ini berhasil dimuat.',
                'data' => $processedData
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
                $rowRequiredFields = ['barcode', 'item', 'ekor_new', 'berat_new'];
                foreach ($rowRequiredFields as $field) {
                    if (!isset($row[$field])) {
                        error_log("Skipping invalid barcode detail row at index $idx: Missing required field '$field'. Row: " . json_encode($row));
                        throw new InvalidArgumentException("Detail Adjustment barcode pada baris ke-" . ($idx + 1) . " tidak lengkap. Field '$field' hilang.");
                    }
                }

                // Transaction type will always be 'adjustment-new'
                $transaction_type_for_row = 'adjustment-new';
                $id_in_sql = "NULL"; // id_in is always NULL as per request

                // Clean barcode: Trim spaces, then remove leading zeros
                $rawBarcode = $row['barcode'] ?? '';
                $cleanedBarcode = ltrim(trim($rawBarcode), '0');
                $barcode_sql_value = ($cleanedBarcode !== '') ? "'" . $conn->real_escape_string($cleanedBarcode) . "'" : "NULL";

                // Cast numeric values to new stock
                $ekorNew = (float)$row['ekor_new'];
                $beratNew = (float)$row['berat_new'];

                // Skip if no new stock (ekor_new and berat_new are both 0)
                if ($ekorNew == 0 && $beratNew == 0) {
                    error_log("Skipping barcode detail row at index $idx: No new stock (ekor and berat are both 0). Row: " . json_encode($row));
                    continue; // Skip processing this row
                }

                // Keterangan handling
                $currentKeterangan = !empty($row['keterangan']) ? $conn->real_escape_string($row['keterangan']) : 'Penambahan stok baru via barcode';

                // Combine item and subitem if available for the 'item' column in transactions table
                $itemName = $conn->real_escape_string($row['item']);
                if (!empty($row['subitem'])) {
                    $subitem_escaped = $conn->real_escape_string($row['subitem']);
                    $itemName .= ' : ' . $subitem_escaped;
                }

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
                $ekorNew,
                $beratNew,
                '$transaction_type_for_row',
                '$currentKeterangan',
                '$userName',
                '$adjustmentType',
                $barcode_sql_value,
                $id_in_sql
            )";

                error_log("Executing SQL (new stock adjustment): " . $sql);

                if (!$conn->query($sql)) {
                    error_log("Database Error (new stock): Failed to execute query for row " . ($idx + 1) . " - " . $conn->error . " for row data: " . json_encode($row));
                    throw new Exception('Gagal menyimpan Adjustment stok baru pada baris ke-' . ($idx + 1) . ': ' . $conn->error);
                }

                $newlyInsertedId = $conn->insert_id;
                $insertedRecordIds[] = $newlyInsertedId;
                $insertedRows++;

                // --- BLOK LOG AKTIVITAS DIHAPUS ---
            }

            if ($insertedRows === 0) {
                $conn->rollback();
                $inTransaction = false;
                error_log("No valid new stock adjustments processed. Rolling back transaction.");
                throw new InvalidArgumentException('Tidak ada stok baru yang valid untuk disimpan. Pastikan nilai Ekor atau Berat tidak nol.');
            }

            $conn->commit();
            $inTransaction = false;
            error_log("Successfully committed new stock adjustments. Inserted rows: " . $insertedRows);

            $response = [
                'success' => true,
                'message' => "Berhasil menyimpan $insertedRows Adjustment stok baru."
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

            $details = $requestData['details'];

            if (!is_array($details) || empty($details)) {
                error_log("Validation Error (global): Details array is empty or not an array. Details: " . json_encode($details));
                throw new InvalidArgumentException('Data Adjustment global tidak boleh kosong.');
            }

            $insertedRows = 0;
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

                // Skip if no adjustment (this is crucial, as 0 adjustments shouldn't be recorded)
                if ($ekorAdjustment == 0 && $beratAdjustment == 0) {
                    error_log("Skipping global detail row at index $idx: No adjustment (ekor and berat are both 0). Row: " . json_encode($row));
                    continue;
                }

                // Simulasikan seolah-olah data sudah disimpan
                $insertedRows++;

                // --- BLOK LOG AKTIVITAS DIHAPUS ---
            }

            if ($insertedRows === 0) {
                // No actual adjustments were processed (all were 0 or invalid)
                error_log("No valid global adjustments processed. Simulation complete.");
                throw new InvalidArgumentException('Tidak ada Adjustment global yang valid untuk disimpan. Pastikan nilai Adjustment tidak nol.');
            }

            error_log("Successfully simulated global adjustments. Processed rows: " . $insertedRows);

            $response = [
                'success' => true,
                'message' => "Berhasil mensimulasikan penyimpanan $insertedRows Adjustment global."
            ];
            break;
        // --- END OF ADJUSTMENT SAVE LOGIC (MODIFIED: NO BIND_PARAM) ---

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
