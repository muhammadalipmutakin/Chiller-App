<?php
// controller/adjustment/adjust.php
session_name('chiller_app');
session_start();
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'details' => [],
    'stats' => [
        'total_processed' => 0,
        'inserted' => 0,
        'skipped' => 0
    ]
];

// Database connection
require_once __DIR__ . '/../../controller/db/config.php';

// $logHandlerUrl = 'http://localhost/chiller/apps/controller/handler/log_activity_handler.php'; // Dihapus
// $currentUserId = $_SESSION['user_id'] ?? 0; // Dihapus karena tidak lagi digunakan

if ($conn->connect_error) {
    $response['message'] = 'Database connection failed: ' . $conn->connect_error;
    echo json_encode($response);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['message'] = 'Method not allowed';
    echo json_encode($response);
    exit();
}

$input = file_get_contents('php://input');
$requestData = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    $response['message'] = 'Invalid JSON input: ' . json_last_error_msg();
    echo json_encode($response);
    exit();
}

$action = 'confirm'; // Action is implicitly 'confirm' from frontend's saveAdjustment call

// Validate and process the data
$adjustmentDate = $requestData['adjustment_date'] ?? '';
$adjustmentType = $requestData['adjustment_type'] ?? '';
$userName = $requestData['user_nama'] ?? $_SESSION['user_nama'] ?? 'Unknown User';
$dataToSave = $requestData['data'] ?? [];

// Validate date
if (empty($adjustmentDate) || !strtotime($adjustmentDate)) {
    $response['message'] = 'Invalid adjustment date';
    echo json_encode($response);
    exit();
}

// Validate adjustment type
if (empty($adjustmentType)) {
    $response['message'] = 'Adjustment type is required.';
    echo json_encode($response);
    exit();
}

if (empty($dataToSave)) {
    $response['message'] = 'No valid data to save.';
    echo json_encode($response);
    exit();
}

// Start transaction
$conn->begin_transaction();
$insertedRows = 0;
$skippedRows = 0;
$skippedDetails = [];

try {
    // Prepare SQL statement for transactions table
    $stmt = $conn->prepare("INSERT INTO transactions
        (date, from_location, to_location, storage, item, ekor_pack, quantity_kg, transaction_type, keterangan, user, adjustment_type, barcode, id_in)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $response['stats']['total_processed'] = count($dataToSave);

    foreach ($dataToSave as $rowNum => $rowData) {
        $storage = trim($rowData['storage'] ?? '');
        $item = trim($rowData['item'] ?? '');
        $subitem = trim($rowData['subitem'] ?? '');

        // Use the adjustment values sent by the frontend
        $ekorPack = $rowData['adjustment_ekor_pack'] ?? 0;
        $quantityKg = $rowData['adjustment_quantity_kg'] ?? 0.00;
        $keterangan = trim($rowData['keterangan'] ?? '');
        $barcode = trim($rowData['barcode'] ?? ''); // Barcode dari rowData
        $id_in = trim($rowData['id_in'] ?? ''); // id_in dari rowData

        // Construct full item name
        $fullItemName = $item . (!empty($subitem) ? " : $subitem" : '');

        // Validate essential fields before saving
        $skipReason = '';
        if (empty($storage)) {
            $skipReason = 'Empty Storage';
        } elseif (empty($item)) {
            $skipReason = 'Empty Item';
        } elseif ($ekorPack == 0 && $quantityKg == 0) {
            $skipReason = 'Zero quantity (Ekor/Pack and Quantity (KG) are both zero) after adjustment';
        }

        if (!empty($skipReason)) {
            $skippedRows++;
            $skippedDetails[] = ['row_index' => $rowNum, 'reason' => $skipReason, 'data' => $rowData];
            continue;
        }

        $fromLocation = 'adjustment';
        $toLocation = $storage;
        $transactionType = 'adjustment'; // Stays 'adjustment'

        // Determine transaction_type_for_row and id_in_sql based on id_in presence
        $transaction_type_for_row = 'adjustment';
        $id_in_sql = null; // Default to null for prepared statement

        if (empty($id_in) || $id_in === 'null') {
            $transaction_type_for_row = 'adjustment';
            $id_in_sql = null; // Tetap NULL
        } else {
            $id_in_sql = $id_in; // Jika ada nilai, gunakan nilai tersebut
        }

        // Clean barcode for database
        $cleanedBarcode = ltrim($barcode, '0');
        $barcode_sql_value = empty($cleanedBarcode) ? null : $cleanedBarcode;

        // Bind parameters and execute
        $stmt->bind_param(
            "sssssidssssss", // s = string, d = double (float), i = integer. Sesuaikan jika ada perubahan tipe data.
            $adjustmentDate,
            $fromLocation,
            $toLocation,
            $storage,
            $fullItemName,
            $ekorPack,
            $quantityKg,
            $transaction_type_for_row, // Menggunakan tipe transaksi yang ditentukan
            $keterangan,
            $userName,
            $adjustmentType,
            $barcode_sql_value, // Barcode
            $id_in_sql // id_in
        );

        if (!$stmt->execute()) {
            $skippedRows++;
            $skippedDetails[] = [
                'row_index' => $rowNum,
                'reason' => 'DB Error: ' . $stmt->error,
                'data' => $rowData
            ];
            continue;
        }

        $newlyInsertedId = $conn->insert_id;
        $insertedRows++;

        // --- Blok LOG AKTIVITAS DIHAPUS ---
        // Kode terkait cURL untuk mengirim log telah dihapus.
        // --- END BLOK LOG AKTIVITAS ---
    }

    if ($insertedRows === 0) {
        $conn->rollback();
        $response['message'] = 'Tidak ada Adjustment yang valid untuk disimpan. Pastikan nilai Adjustment tidak nol.';
        error_log("No valid adjustments processed. Rolling back transaction.");
    } else {
        // Commit transaction
        $conn->commit();

        // Build success response
        $response['success'] = true;
        $response['message'] = sprintf(
            "Data adjustment berhasil disimpan. Tersimpan: %d, Dilewati: %d",
            $insertedRows,
            $skippedRows
        );
        $response['details'] = $skippedDetails;
        $response['stats']['inserted'] = $insertedRows;
        $response['stats']['skipped'] = $skippedRows;
        error_log("Successfully committed adjustments. Inserted rows: " . $insertedRows);
    }
} catch (Exception $e) {
    $conn->rollback();

    $response['message'] = 'Error: ' . $e->getMessage();

    error_log('Adjustment Save Error: ' . $e->getMessage());
} finally {
    if (isset($stmt) && $stmt !== false) {
        $stmt->close();
    }
    $conn->close();
}

echo json_encode($response);
exit();
