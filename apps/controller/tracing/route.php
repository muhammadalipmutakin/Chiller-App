<?php
// Pastikan semua error dilaporkan untuk pengembangan
ini_set('display_errors', 0); // Matikan tampilan error di produksi
ini_set('log_errors', 1);     // Pastikan error dicatat
error_reporting(E_ALL);

header('Content-Type: application/json');

// Izinkan semua origin untuk tujuan pengembangan (HATI-HATI DI PRODUKSI)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle CORS preflight (OPTIONS request)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require '../../controller/db/config.php'; // Pastikan file ini menghubungkan ke database ($conn)

$response = [
    'success' => false,
    'message' => 'Invalid request',
    'data' => null,
    'error' => null
];

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_items_filter':
            $items = [];
            $stmt = $conn->prepare("SELECT DISTINCT item FROM transactions WHERE deleted_at IS NULL ORDER BY item ASC");
            if ($stmt === false) {
                throw new Exception("Failed to prepare statement for fetching items from transactions: " . $conn->error);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                if (!empty($row['item'])) {
                    $items[] = $row['item'];
                }
            }
            $stmt->close();

            $response['success'] = true;
            $response['message'] = 'Item filters data fetched successfully.';
            $response['data'] = ['items' => $items];
            break;

        case 'get_item_details':
            $item_name = $_GET['item_name'] ?? '';

            if (empty($item_name)) {
                http_response_code(400);
                throw new Exception('Item name is required for getting details.');
            }

            $item_details = null;
            $itemParts = array_map('trim', explode(' : ', $item_name, 2));
            $mainItem = $itemParts[0];
            $subitem = isset($itemParts[1]) ? $itemParts[1] : null;

            $sql = "SELECT sku, item, kategory, isActive FROM items WHERE item = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Failed to prepare statement for fetching item details: " . $conn->error);
            }
            $stmt->bind_param("s", $mainItem);
            $stmt->execute();
            $result = $stmt->get_result();
            $item_details = $result->fetch_assoc();
            $stmt->close();

            if ($item_details) {
                $item_details['item'] = $item_name;
                $item_details['subitem'] = $subitem ?? 'N/A';
                $item_details['isActive'] = (bool)$item_details['isActive'];

                $response['success'] = true;
                $response['message'] = 'Item details fetched successfully.';
                $response['data'] = $item_details;
            } else {
                http_response_code(404);
                $response['message'] = 'Item details not found in master data.';
                $response['error'] = 'Item details not found in master data.';
            }
            break;

        case 'get_transactions_with_initial_stock':
            $item_name = $_GET['item_name'] ?? '';
            $storage = $_GET['storage'] ?? '';
            $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $barcode = $_GET['barcode'] ?? null;

            if (empty($item_name)) {
                http_response_code(400);
                throw new Exception('Item name is required for getting transactions.');
            }

            // --- Calculate Initial Stock (before the specified startDate) ---
            $initial_stock_kg = 0;
            $initial_stock_ekor = 0;

            $sql_initial_stock = "SELECT SUM(quantity_kg) AS total_kg, SUM(ekor_pack) AS total_ekor
                      FROM transactions
                      WHERE item = ? AND date < ? AND deleted_at IS NULL";

            $params_initial = [$item_name, $startDate];
            $types_initial = "ss";

            if (!empty($storage)) {
                $sql_initial_stock .= " AND storage = ?";
                $params_initial[] = $storage;
                $types_initial .= "s";
            }

            if ($barcode !== null && $barcode !== '') {
                $sql_initial_stock .= " AND barcode = ?";
                $params_initial[] = $barcode;
                $types_initial .= "s";
            }

            $stmt_initial = $conn->prepare($sql_initial_stock);
            if ($stmt_initial === false) {
                throw new Exception("Failed to prepare statement for fetching initial stock: " . $conn->error);
            }
            $stmt_initial->bind_param($types_initial, ...$params_initial);
            $stmt_initial->execute();
            $result_initial = $stmt_initial->get_result();
            $row_initial = $result_initial->fetch_assoc();

            $initial_stock_kg = (float)($row_initial['total_kg'] ?? 0);
            $initial_stock_ekor = (int)($row_initial['total_ekor'] ?? 0);
            $stmt_initial->close();

            // --- Fetch Transactions within Date Range (Ordered Chronologically for Calculation) ---
            $sql_transactions = "SELECT id, barcode, date, from_location, to_location, storage, item, ekor_pack, quantity_kg, transaction_type, keterangan, user, created_at
                     FROM transactions
                     WHERE item = ?
                     AND date BETWEEN ? AND ?
                     AND deleted_at IS NULL";

            $params_transactions = [$item_name, $startDate, $endDate];
            $types_transactions = "sss";

            if (!empty($storage)) {
                $sql_transactions .= " AND storage = ?";
                $params_transactions[] = $storage;
                $types_transactions .= "s";
            }

            if ($barcode !== null && $barcode !== '') {
                $sql_transactions .= " AND barcode = ?";
                $params_transactions[] = $barcode;
                $types_transactions .= "s";
            }

            // IMPORTANT: Order chronologically (oldest first) for accurate running stock calculation
            $sql_transactions .= " ORDER BY date ASC, created_at ASC";

            $stmt_transactions = $conn->prepare($sql_transactions);
            if ($stmt_transactions === false) {
                throw new Exception("Failed to prepare statement for fetching transactions: " . $conn->error);
            }
            $stmt_transactions->bind_param($types_transactions, ...$params_transactions);
            $stmt_transactions->execute();
            $result_transactions = $stmt_transactions->get_result();

            $transactions_chronological = [];
            while ($row = $result_transactions->fetch_assoc()) {
                $row['quantity_kg'] = (float)$row['quantity_kg'];
                $row['ekor_pack'] = (int)$row['ekor_pack'];
                $transactions_chronological[] = $row;
            }
            $stmt_transactions->close();

            // --- Calculate Running Stock in PHP ---
            $running_stock_ekor = $initial_stock_ekor;
            $running_stock_kg = $initial_stock_kg;

            // Iterate through chronologically ordered transactions to calculate running balance
            foreach ($transactions_chronological as $key => $transaction) {
                $running_stock_ekor += $transaction['ekor_pack'];
                $running_stock_kg += $transaction['quantity_kg'];

                $transactions_chronological[$key]['running_stock_ekor'] = $running_stock_ekor;
                // Round to 2 decimal places for kg, consistent with display
                $transactions_chronological[$key]['running_stock_kg'] = round($running_stock_kg, 2);
            }

            // --- Reverse the array for desired display order (newest on top) ---
            // This is done AFTER calculating the running stock on the chronological data.
            $transactions_for_display = array_reverse($transactions_chronological);

            $response['success'] = true;
            $response['message'] = 'Transactions and initial stock fetched successfully.';
            $response['data'] = [
                'initial_stock' => ['ekor_pack' => $initial_stock_ekor, 'quantity_kg' => $initial_stock_kg],
                'transactions' => $transactions_for_display // This array is now ordered newest first with correct running stock values
            ];
            break;

        default:
            http_response_code(400); // Bad Request
            $response['message'] = 'Unknown action.';
            $response['error'] = 'Unknown action.';
            break;
    }
} catch (Exception $e) {
    error_log("Tracing API error: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    $response['success'] = false;
    $response['error'] = $e->getMessage();
    $response['message'] = 'An internal server error occurred.';
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
    echo json_encode($response);
}
