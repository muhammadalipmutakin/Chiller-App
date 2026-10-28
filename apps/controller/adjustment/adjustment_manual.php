<?php
// adjustment_handler.php
session_name('chiller_app');
session_start();
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => null,
    'stats' => []
];

// Database connection
require_once __DIR__ . '/../../controller/db/config.php';

// Check if database connection is valid
if ($conn->connect_error) {
    $response['message'] = 'Database connection failed: ' . $conn->connect_error;
    http_response_code(500);
    echo json_encode($response);
    exit();
}

// Get action from GET or POST request
$action = null;
$requestData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $requestData = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $response['message'] = 'Invalid JSON format: ' . json_last_error_msg();
        http_response_code(400);
        echo json_encode($response);
        exit();
    }
    $action = $requestData['action'] ?? null;
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? null;
    $requestData = $_GET;
}

if (!$action) {
    $response['message'] = 'Action parameter is required.';
    http_response_code(400);
    echo json_encode($response);
    exit();
}

// Ambil nilai user_nama dari session. Jika tidak ada, gunakan 'Manual Adjustment User' sebagai default.
$userName = $_SESSION['user_nama'] ?? 'Manual Adjustment User';

$transactionStarted = false;
try {
    switch ($action) {
        case 'get_item_list':
            $query = "SELECT item FROM items WHERE isActive = 1 ORDER BY item ASC";
            $result = $conn->query($query);
            if ($result === false) {
                throw new Exception("Failed to execute item list query: " . $conn->error);
            }
            $items = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();

            $response['success'] = true;
            $response['message'] = 'Items fetched successfully';
            $response['data'] = $items;
            break;

        case 'get_subitem_list':
            $query = "SELECT subitem FROM subitems ORDER BY subitem ASC";
            $result = $conn->query($query);
            if ($result === false) {
                throw new Exception("Failed to execute subitem list query: " . $conn->error);
            }
            $subitems = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();

            $response['success'] = true;
            $response['message'] = 'Subitems fetched successfully';
            $response['data'] = $subitems;
            break;

        case 'save_manual_adjustments':
            $adjustmentDate = $conn->real_escape_string($requestData['adjustment_date'] ?? '');
            $adjustmentType = $conn->real_escape_string($requestData['adjustment_type'] ?? '');
            $storage = $conn->real_escape_string($requestData['storage'] ?? '');
            $details = $requestData['details'] ?? [];

            // Validation
            if (empty($adjustmentDate) || empty($adjustmentType) || empty($storage)) {
                throw new InvalidArgumentException("Tanggal, Tipe Adjustment, dan Storage wajib diisi.");
            }
            if (!is_array($details) || empty($details)) {
                throw new InvalidArgumentException("Tidak ada data adjustment yang valid untuk disimpan.");
            }
            if (!in_array($storage, ['Chiller 1', 'Chiller 2', 'Chiller 3'])) {
                throw new InvalidArgumentException("Storage tidak valid. Hanya boleh Chiller 1, Chiller 2, atau Chiller 3.");
            }

            // Start transaction
            $conn->begin_transaction();
            $transactionStarted = true;

            $insertedRows = 0;
            $skippedRows = 0;
            $totalProcessed = count($details);
            $transaction_type_for_row = 'adjustment-new';
            $fromLocation = 'adjustment';
            $toLocation = $storage;

            foreach ($details as $idx => $row) {
                $barcode = !empty($row['barcode']) ? "'" . $conn->real_escape_string(trim($row['barcode'])) . "'" : "NULL";
                $item = $conn->real_escape_string(trim($row['item'] ?? ''));
                $subitem = $conn->real_escape_string(trim($row['subitem'] ?? ''));
                $ekorPack = (int)($row['ekor_pack'] ?? 0);
                $quantityKg = (float)($row['quantity_kg'] ?? 0);
                $keterangan = $conn->real_escape_string(trim($row['keterangan'] ?? 'Manual Stock Adjustment'));

                // Combine item and subitem
                $fullItemName = !empty($subitem) ? $item . ' : ' . $subitem : $item;

                // Validate row data
                if (empty($fullItemName) || ($ekorPack == 0 && $quantityKg == 0)) {
                    $skippedRows++;
                    continue;
                }

                $query = "
            INSERT INTO transactions (
                date, from_location, to_location, storage, item,
                ekor_pack, quantity_kg, transaction_type, keterangan,
                user, adjustment_type, barcode
            ) VALUES (
                '$adjustmentDate', '$fromLocation', '$toLocation', '$storage', '$fullItemName',
                $ekorPack, $quantityKg, '$transaction_type_for_row', '$keterangan',
                '$userName', '$adjustmentType', $barcode
            )
        ";

                if (!$conn->query($query)) {
                    $skippedRows++;
                    error_log("Failed to insert row " . ($idx + 1) . ": " . $conn->error);
                    continue;
                }

                $insertedRows++;
            }

            if ($insertedRows > 0) {
                $conn->commit();
                $response['success'] = true;
                $response['message'] = "Berhasil menyimpan $insertedRows Adjustment. Dilewati: $skippedRows.";
                $response['stats'] = [
                    'total_processed' => $totalProcessed,
                    'inserted' => $insertedRows,
                    'skipped' => $skippedRows
                ];
            } else {
                $conn->rollback();
                $response['message'] = "Tidak ada Adjustment yang valid untuk disimpan. Pastikan Anda telah mengisi item dan salah satu nilai kuantitas tidak nol.";
            }
            break;

        default:
            $response['message'] = 'Invalid action specified.';
            http_response_code(400);
            break;
    }
} catch (Exception $e) {
    if ($transactionStarted) {
        $conn->rollback();
    }
    $response['message'] = 'Error: ' . $e->getMessage();
    http_response_code(500);
    error_log('Error in adjustment_handler.php: ' . $e->getMessage());
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}

echo json_encode($response);
exit();
