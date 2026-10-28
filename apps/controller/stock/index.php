<?php
header('Content-Type: application/json');

// Include database configuration
require '../../controller/db/config.php'; // Pastikan path ini benar

$response = [
    'status' => 'error',
    'message' => 'Invalid request',
    'data' => []
];

// Tentukan jenis request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        switch ($action) {
            case 'get_full_stock_data':
                $startDate = $_GET['startDate'] ?? '';
                $endDate = $_GET['endDate'] ?? date('Y-m-d');

                if (empty($startDate)) {
                    $startDate = '1900-01-01'; // Tanggal yang sangat lama untuk mencakup semua transaksi sebelumnya
                }

                if (!strtotime($startDate) || !strtotime($endDate)) {
                    throw new Exception("Format tanggal tidak valid.");
                }
                if (new DateTime($startDate) > new DateTime($endDate)) {
                    throw new Exception("Tanggal akhir tidak boleh lebih kecil dari tanggal awal.");
                }

                $stockData = [];
                $uniqueItems = [];
                $uniqueSubitems = [];

                // --- 1. SQL Query untuk Data Stok per Periode dengan Pemisahan Tipe Transaksi yang Lebih Akurat ---
                $selectStockClauses = [
                    't.storage',
                    't.item AS full_item_name',
                    'IF(LOCATE(" : ", t.item) > 0, SUBSTRING_INDEX(t.item, " : ", 1), t.item) AS item_name_parsed',
                    'IF(LOCATE(" : ", t.item) > 0, SUBSTRING_INDEX(t.item, " : ", -1), "") AS subitem_name_parsed',

                    // Stok Awal (sebelum startDate)
                    'SUM(CASE WHEN t.date < ? THEN t.ekor_pack ELSE 0 END) AS initial_ep',
                    'SUM(CASE WHEN t.date < ? THEN t.quantity_kg ELSE 0 END) AS initial_kg',

                    // Masuk (transaction_type = 'IN' atau 'TRANSFER-IN' dalam rentang tanggal)
                    'SUM(CASE WHEN t.date BETWEEN ? AND ? AND (t.transaction_type = "IN" OR t.transaction_type = "TRANSFER-IN") THEN t.ekor_pack ELSE 0 END) AS in_ep',
                    'SUM(CASE WHEN t.date BETWEEN ? AND ? AND (t.transaction_type = "IN" OR t.transaction_type = "TRANSFER-IN") THEN t.quantity_kg ELSE 0 END) AS in_kg',

                    // Keluar (transaction_type = 'OUT' atau 'TRANSFER-OUT' dalam rentang tanggal)
                    // Menggunakan ABS karena nilai keluar dicatat sebagai negatif
                    'SUM(CASE WHEN t.date BETWEEN ? AND ? AND (t.transaction_type = "OUT" OR t.transaction_type = "TRANSFER-OUT") THEN ABS(t.ekor_pack) ELSE 0 END) AS out_ep',
                    'SUM(CASE WHEN t.date BETWEEN ? AND ? AND (t.transaction_type = "OUT" OR t.transaction_type = "TRANSFER-OUT") THEN ABS(t.quantity_kg) ELSE 0 END) AS out_kg',

                    // Adjustment (transaction_type = 'ADJUSTMENT' dalam rentang tanggal)
                    'SUM(CASE WHEN t.date BETWEEN ? AND ? AND (t.transaction_type = "ADJUSTMENT" OR t.transaction_type = "ADJUSTMENT-NEW") THEN t.ekor_pack ELSE 0 END) AS adjustment_ep',
                    'SUM(CASE WHEN t.date BETWEEN ? AND ? AND (t.transaction_type = "ADJUSTMENT" OR t.transaction_type = "ADJUSTMENT-NEW") THEN t.quantity_kg ELSE 0 END) AS adjustment_kg',

                    // Stok Akhir (total kumulatif dari semua transaksi hingga endDate, termasuk adjustment)
                    'SUM(CASE WHEN t.date <= ? THEN t.ekor_pack ELSE 0 END) AS final_ep',
                    'SUM(CASE WHEN t.date <= ? THEN t.quantity_kg ELSE 0 END) AS final_kg'
                ];

                $whereStockClauses = ["t.deleted_at IS NULL"];
                $groupByStockClauses = ['t.storage', 'full_item_name'];

                $paramsStock = [
                    $startDate,
                    $startDate, // initial
                    $startDate,
                    $endDate,
                    $startDate,
                    $endDate, // in
                    $startDate,
                    $endDate,
                    $startDate,
                    $endDate, // out
                    $startDate,
                    $endDate,
                    $startDate,
                    $endDate, // adjustment
                    $endDate,
                    $endDate // final
                ];
                $typesStock = str_repeat("s", count($paramsStock));

                $queryStock = "SELECT " . implode(', ', $selectStockClauses) . " FROM transactions t WHERE " . implode(' AND ', $whereStockClauses) . " GROUP BY " . implode(', ', $groupByStockClauses);
                // Hanya sertakan baris yang memiliki perubahan stok atau stok saat ini
                $queryStock .= " HAVING (initial_ep != 0 OR initial_kg != 0 OR in_ep != 0 OR in_kg != 0 OR out_ep != 0 OR out_kg != 0 OR adjustment_ep != 0 OR adjustment_kg != 0 OR final_ep != 0 OR final_kg != 0)";
                $queryStock .= " ORDER BY t.storage ASC, full_item_name ASC";

                $stmtStock = $conn->prepare($queryStock);
                if (!$stmtStock) {
                    throw new Exception("Prepare failed for stock data: " . $conn->error);
                }
                $stmtStock->bind_param($typesStock, ...$paramsStock);
                $stmtStock->execute();
                $resultStock = $stmtStock->get_result();

                while ($row = $resultStock->fetch_assoc()) {
                    // Hitung 'Stok Akhir Sebelum Adjustment' di sini
                    $stockEndBeforeAdjEp = $row['initial_ep'] + $row['in_ep'] - $row['out_ep'];
                    $stockEndBeforeAdjKg = $row['initial_kg'] + $row['in_kg'] - $row['out_kg'];

                    $stockData[] = [
                        'storage' => $row['storage'],
                        'item' => $row['full_item_name'],
                        'item_name_parsed' => $row['item_name_parsed'],
                        'subitem_name_parsed' => $row['subitem_name_parsed'],

                        'initial_ep' => (int)$row['initial_ep'],
                        'initial_kg' => (float)round($row['initial_kg'], 2),

                        'in_ep' => (int)$row['in_ep'],
                        'in_kg' => (float)round($row['in_kg'], 2),

                        'out_ep' => (int)$row['out_ep'],
                        'out_kg' => (float)round($row['out_kg'], 2),

                        'stock_end_before_adj_ep' => (int)$stockEndBeforeAdjEp,
                        'stock_end_before_adj_kg' => (float)round($stockEndBeforeAdjKg, 2),

                        'adjustment_ep' => (int)$row['adjustment_ep'],
                        'adjustment_kg' => (float)round($row['adjustment_kg'], 2),

                        'stock_after_adj_ep' => (int)$row['final_ep'],
                        'stock_after_adj_kg' => (float)round($row['final_kg'], 2)
                    ];
                }
                $stmtStock->close();

                // --- 2. SQL Query untuk mendapatkan Item Utama Unik (dari SEMUA data, tidak terbatas tanggal) ---
                $queryUniqueItems = "SELECT DISTINCT IF(LOCATE(' : ', item) > 0, SUBSTRING_INDEX(item, ' : ', 1), item) AS main_item
                                    FROM transactions
                                    WHERE deleted_at IS NULL
                                    ORDER BY main_item ASC";
                $stmtItems = $conn->prepare($queryUniqueItems);
                if (!$stmtItems) {
                    throw new Exception("Prepare failed for unique items: " . $conn->error);
                }
                $stmtItems->execute();
                $resultItems = $stmtItems->get_result();
                while ($row = $resultItems->fetch_assoc()) {
                    if (!empty($row['main_item'])) {
                        $uniqueItems[] = $row['main_item'];
                    }
                }
                $stmtItems->close();

                // --- 3. SQL Query untuk mendapatkan Subitem Unik (dari SEMUA data, tidak terbatas tanggal) ---
                $queryUniqueSubitems = "SELECT DISTINCT TRIM(SUBSTRING_INDEX(item, ' : ', -1)) AS extracted_subitem
                                        FROM transactions
                                        WHERE item LIKE '% : %' AND deleted_at IS NULL
                                        ORDER BY extracted_subitem ASC";
                $stmtSubitems = $conn->prepare($queryUniqueSubitems);
                if (!$stmtSubitems) {
                    throw new Exception("Prepare failed for unique subitems: " . $conn->error);
                }
                $stmtSubitems->execute();
                $resultSubitems = $stmtSubitems->get_result();
                while ($row = $resultSubitems->fetch_assoc()) {
                    if (!empty($row['extracted_subitem']) && strpos($row['extracted_subitem'], ' : ') === false) {
                        $uniqueSubitems[] = $row['extracted_subitem'];
                    }
                }
                $stmtSubitems->close();

                $response['status'] = 'success';
                $response['message'] = 'Full stock data and filter options fetched successfully';
                $response['data'] = [
                    'stock_data' => $stockData,
                    'items_for_filter' => array_values(array_unique($uniqueItems)),
                    'subitems_for_filter' => array_values(array_unique($uniqueSubitems))
                ];
                break;

            default:
                $response['message'] = 'Unknown action.';
                break;
        }
    } else {
        $response['message'] = 'Invalid request method.';
    }
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
    error_log("API Error: " . $e->getMessage());
} finally {
    if ($conn) {
        $conn->close();
    }
}

echo json_encode($response);
