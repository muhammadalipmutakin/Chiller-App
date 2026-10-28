<?php
header('Content-Type: application/json');

// Start session to get user_nama
session_name('chiller_app');
session_start();
$currentUser = isset($_SESSION['user_nama']) ? $_SESSION['user_nama'] : 'guest';

require '../../controller/db/config.php';

$response = [
    'status' => 'error',
    'message' => 'Invalid request',
    'data' => []
];

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        switch ($action) {
            case 'get_full_stock_barcode_data':
                $startDate = $_GET['startDate'] ?? '';
                $endDate = $_GET['endDate'] ?? date('Y-m-d');

                $stockBarcodeData = [];
                $uniqueItems = [];
                $uniqueSubitems = [];

                $queryBarcodeStock =
                    "SELECT A.id, A.storage,A.date, A.barcode, A.item, A.ekor_pack, A.quantity_kg, B.ekor AS current_ekor, B.berat AS current_berat, (B.berat/A.quantity_kg)*100 AS percent_susut, A.created_at
                    FROM (
                        SELECT id, `storage`, `date`,barcode,item, ekor_pack, quantity_kg,created_at
                        FROM transactions
                        WHERE transaction_type IN ('in', 'transfer-in','gabung-new', 'adjustment-new', 'adjustment')
                            AND `date` >= '$startDate'
                            AND `date` <= '$endDate'
                            AND barcode IS NOT NULL
                            AND deleted_at IS NULL
                            AND id_in IS NULL
                    ) AS A
                    LEFT JOIN (
                        SELECT id AS id_B, SUM(ekor_pack) AS ekor, SUM(quantity_kg) AS berat 
                        FROM (
                            SELECT id, ekor_pack, quantity_kg
                            FROM transactions
                            WHERE barcode IS NOT NULL
                                AND transaction_type IN ('in', 'transfer-in','gabung-new', 'adjustment-new', 'adjustment')
                                AND deleted_at IS NULL
                                AND id_in IS NULL
                            UNION ALL
                            SELECT 
                                id_in , ekor_pack AS ekor, quantity_kg AS berat
                            FROM transactions
                            WHERE barcode IS NOT NULL
                                AND deleted_at IS NULL
                                AND id_in IS NOT NULL
                        ) AS A
                        GROUP BY id
                    ) AS B
                    ON A.id = B.id_B
                    ORDER BY A.date DESC, A.created_at DESC
                    LIMIT 5000
                ";


                $stmtBarcode = $conn->prepare($queryBarcodeStock);
                if (!$stmtBarcode) {
                    throw new Exception("Prepare failed for barcode stock data: " . $conn->error);
                }
                $stmtBarcode->execute();
                $resultBarcode = $stmtBarcode->get_result();

                while ($row = $resultBarcode->fetch_assoc()) {
                    $stockBarcodeData[] = [
                        'id' => $row['id'],
                        'date' => $row['date'],
                        'storage' => $row['storage'],
                        'barcode' => $row['barcode'],
                        'item' => $row['item'],
                        'ekor_pack' => (int)$row['ekor_pack'],
                        'quantity_kg' => (float)round($row['quantity_kg'], 2),
                        'current_ekor' => (int)$row['current_ekor'],
                        'current_berat' => (float)round($row['current_berat'], 2),
                        'percent_susut' => (float)round($row['percent_susut'], 2)
                    ];
                }
                $stmtBarcode->close();

                $queryUniqueItems = "SELECT DISTINCT item AS full_item
                                             FROM transactions
                                             WHERE barcode IS NOT NULL AND deleted_at IS NULL
                                             ORDER BY full_item ASC";
                $stmtItems = $conn->prepare($queryUniqueItems);
                if (!$stmtItems) {
                    throw new Exception("Prepare failed for unique items: " . $conn->error);
                }
                $stmtItems->execute();
                $resultItems = $stmtItems->get_result();
                while ($row = $resultItems->fetch_assoc()) {
                    if (!empty($row['full_item'])) {
                        $uniqueItems[] = $row['full_item'];
                    }
                }
                $stmtItems->close();

                $queryUniqueSubitems = "SELECT DISTINCT TRIM(SUBSTRING_INDEX(item, ' : ', -1)) AS extracted_subitem
                                                 FROM transactions
                                                 WHERE item LIKE '% : %' AND barcode IS NOT NULL AND deleted_at IS NULL
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
                $response['message'] = 'Full barcode stock data and filter options fetched successfully';
                $response['data'] = [
                    'stock_data' => $stockBarcodeData,
                    'items_for_filter' => array_values(array_unique($uniqueItems)),
                    'subitems_for_filter' => array_values(array_unique($uniqueSubitems))
                ];
                break;

            default:
                $response['message'] = 'Unknown action.';
                break;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Read JSON data from POST body
        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data, true);

        $action = $data['action'] ?? $action;

        switch ($action) {
            case 'save_stock_adjustments':
                $adjustments = $data['adjustments'] ?? [];
                $successful_adjustments = 0;

                if (empty($adjustments)) {
                    $response['message'] = 'No adjustments data provided.';
                    break;
                }

                $insert_query = "INSERT INTO transactions 
        (date, from_location, to_location, storage, item, ekor_pack, quantity_kg, transaction_type, keterangan, user, barcode, id_in)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt_insert = $conn->prepare($insert_query);

                if (!$stmt_insert) {
                    throw new Exception("Prepare failed for adjustment insert: " . $conn->error);
                }

                $conn->begin_transaction();

                try {
                    $current_date = date('Y-m-d');
                    $from_location = 'adjustment';
                    $transaction_type = 'adjustment';

                    foreach ($adjustments as $adjustment) {
                        $id_in = $adjustment['id_in'] ?? null;
                        $storage = $adjustment['storage'] ?? null;
                        $item = $adjustment['item'] ?? null;
                        $barcode = $adjustment['barcode'] ?? null;
                        $keterangan = $adjustment['keterangan'] ?? null;

                        // Konversi dan negasi nilai
                        $adj_ekor = isset($adjustment['adj_ekor']) ? $adjustment['adj_ekor'] : 0;
                        $adj_berat = isset($adjustment['adj_berat']) ? $adjustment['adj_berat'] : 0.0;

                        $ekor_pack = -(int)$adj_ekor;
                        $quantity_kg = -(float)$adj_berat; // Ini akan jadi -1.5 jika input 1.5

                        if ($id_in !== null && $storage && $item && $barcode !== null) {
                            $stmt_insert->bind_param(
                                "sssssidsssss",
                                $current_date,
                                $from_location,
                                $storage, // to_location = storage
                                $storage,
                                $item,
                                $ekor_pack,
                                $quantity_kg,
                                $transaction_type,
                                $keterangan,
                                $currentUser,
                                $barcode,
                                $id_in
                            );

                            if (!$stmt_insert->execute()) {
                                throw new Exception("Execute failed: " . $stmt_insert->error);
                            }
                            $successful_adjustments++;
                        }
                    }

                    $conn->commit();
                    $response['status'] = 'success';
                    $response['message'] = "Successfully saved {$successful_adjustments} stock adjustment(s).";
                } catch (Exception $e) {
                    $conn->rollback();
                    throw $e;
                } finally {
                    $stmt_insert->close();
                }
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
