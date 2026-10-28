<?php
header('Content-Type: application/json');
require '../../controller/db/config.php';

// Handle CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$action = $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {
        case 'get_storage_locations':
            $stmt = $conn->prepare("SELECT DISTINCT lokasi FROM locations WHERE lokasi LIKE 'chiller%'");
            $stmt->execute();
            $result = $stmt->get_result();
            $locations = [];
            while ($row = $result->fetch_assoc()) {
                $locations[] = $row['lokasi'];
            }
            $response = ['success' => true, 'locations' => $locations];
            break;

        case 'get_initial_data':
            $storage = $_GET['storage'] ?? '';
            $opnameDate = $_GET['opname_date'] ?? date('Y-m-d');

            // Get stock awal
            $queryAwal = "SELECT DISTINCT item, storage, SUM(ekor_pack) as ekor_pack, SUM(quantity_kg) as quantity_kg 
                          FROM so_stock_awal 
                          WHERE opname_date = ?";
            $params = [$opnameDate];

            if (!empty($storage)) {
                $queryAwal .= " AND storage = ?";
                $params[] = $storage;
            }

            $queryAwal .= " GROUP BY item, storage";

            $stmt = $conn->prepare($queryAwal);
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
            $stmt->execute();
            $stockAwal = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Get stock hitung
            $queryHitung = "SELECT DISTINCT item, storage, SUM(ekor_pack) as ekor_pack, SUM(quantity_kg) as quantity_kg 
                            FROM so_hitung 
                            WHERE opname_date = ?";
            $paramsHitung = [$opnameDate];

            if (!empty($storage)) {
                $queryHitung .= " AND storage = ?";
                $paramsHitung[] = $storage;
            }

            $queryHitung .= " GROUP BY item, storage";

            $stmt = $conn->prepare($queryHitung);
            $stmt->bind_param(str_repeat('s', count($paramsHitung)), ...$paramsHitung);
            $stmt->execute();
            $stockHitung = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $response = [
                'success' => true,
                'stock_awal' => $stockAwal,
                'stock_hitung' => $stockHitung
            ];
            break;

        case 'process_calculation':
            $storage = $_GET['storage'] ?? '';
            $opnameDate = $_GET['opname_date'] ?? date('Y-m-d');
            $userProses = 'admin'; // In a real app, get from session

            // Begin transaction
            $conn->begin_transaction();

            try {
                // Get stock awal data
                $queryAwal = "SELECT item, storage, SUM(ekor_pack) as ekor_pack, SUM(quantity_kg) as quantity_kg 
                               FROM so_stock_awal 
                               WHERE opname_date = ?";
                $params = [$opnameDate];

                if (!empty($storage)) {
                    $queryAwal .= " AND storage = ?";
                    $params[] = $storage;
                }

                $queryAwal .= " GROUP BY item, storage";

                $stmt = $conn->prepare($queryAwal);
                $stmt->bind_param(str_repeat('s', count($params)), ...$params);
                $stmt->execute();
                $stockAwal = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                // Get stock hitung data
                $queryHitung = "SELECT item, storage, SUM(ekor_pack) as ekor_pack, SUM(quantity_kg) as quantity_kg, MAX(user_hitung) as user_hitung 
                                FROM so_hitung 
                                WHERE opname_date = ?";
                $paramsHitung = [$opnameDate];

                if (!empty($storage)) {
                    $queryHitung .= " AND storage = ?";
                    $paramsHitung[] = $storage;
                }

                $queryHitung .= " GROUP BY item, storage";

                $stmt = $conn->prepare($queryHitung);
                $stmt->bind_param(str_repeat('s', count($paramsHitung)), ...$paramsHitung);
                $stmt->execute();
                $stockHitung = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                // Create a combined array for calculation
                $combined = [];

                // Process stock awal
                foreach ($stockAwal as $item) {
                    $key = $item['item'] . '|' . $item['storage'];
                    $combined[$key] = [
                        'item' => $item['item'],
                        'storage' => $item['storage'],
                        'awal_ekor' => $item['ekor_pack'],
                        'awal_berat' => $item['quantity_kg'],
                        'hitung_ekor' => 0,
                        'hitung_berat' => 0,
                        'user_hitung' => ''
                    ];
                }

                // Process stock hitung
                foreach ($stockHitung as $item) {
                    $key = $item['item'] . '|' . $item['storage'];
                    if (isset($combined[$key])) {
                        $combined[$key]['hitung_ekor'] = $item['ekor_pack'];
                        $combined[$key]['hitung_berat'] = $item['quantity_kg'];
                        $combined[$key]['user_hitung'] = $item['user_hitung'];
                    } else {
                        $combined[$key] = [
                            'item' => $item['item'],
                            'storage' => $item['storage'],
                            'awal_ekor' => 0,
                            'awal_berat' => 0,
                            'hitung_ekor' => $item['ekor_pack'],
                            'hitung_berat' => $item['quantity_kg'],
                            'user_hitung' => $item['user_hitung']
                        ];
                    }
                }

                // Clear previous results for this date and storage
                $deleteQuery = "DELETE FROM so_hasil WHERE opname_date = ?";
                $deleteParams = [$opnameDate];

                if (!empty($storage)) {
                    $deleteQuery .= " AND storage = ?";
                    $deleteParams[] = $storage;
                }

                $stmt = $conn->prepare($deleteQuery);
                $stmt->bind_param(str_repeat('s', count($deleteParams)), ...$deleteParams);
                $stmt->execute();

                // Insert calculated results
                $insertQuery = "INSERT INTO so_hasil 
                                (item, storage, ekor_pack, quantity_kg, selisih_ekor_pack, selisih_quantity_kg, 
                                 opname_date, user_hitung, user_proses) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($insertQuery);
                $resultData = [];

                foreach ($combined as $data) {
                    $selisihEkor = $data['hitung_ekor'] - $data['awal_ekor'];
                    $selisihBerat = $data['hitung_berat'] - $data['awal_berat'];

                    $stmt->bind_param(
                        'ssddddsss',
                        $data['item'],
                        $data['storage'],
                        $data['hitung_ekor'],
                        $data['hitung_berat'],
                        $selisihEkor,
                        $selisihBerat,
                        $opnameDate,
                        $data['user_hitung'],
                        $userProses
                    );

                    $stmt->execute();

                    $resultData[] = [
                        'id' => $stmt->insert_id,
                        'item' => $data['item'],
                        'storage' => $data['storage'],
                        'ekor_pack' => $data['hitung_ekor'],
                        'quantity_kg' => $data['hitung_berat'],
                        'selisih_ekor_pack' => $selisihEkor,
                        'selisih_quantity_kg' => $selisihBerat,
                        'keterangan' => null
                    ];
                }

                $conn->commit();

                $response = [
                    'success' => true,
                    'result' => $resultData
                ];
            } catch (Exception $e) {
                $conn->rollback();
                $response = [
                    'success' => false,
                    'message' => 'Error processing calculation: ' . $e->getMessage()
                ];
            }
            break;

        case 'update_keterangan':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? 0;
            $keterangan = $input['keterangan'] ?? '';

            $stmt = $conn->prepare("UPDATE so_hasil SET keterangan = ? WHERE id = ?");
            $stmt->bind_param('si', $keterangan, $id);
            $success = $stmt->execute();

            $response = [
                'success' => $success,
                'message' => $success ? 'Keterangan updated' : 'Failed to update keterangan'
            ];
            break;

        case 'save_adjustment':
            $input = json_decode(file_get_contents('php://input'), true);
            $items = $input['items'] ?? [];
            $opnameDate = $input['opname_date'] ?? date('Y-m-d');
            $storage = $input['storage'] ?? '';
            $user = 'admin'; // In a real app, get from session

            // Begin transaction
            $conn->begin_transaction();

            try {
                foreach ($items as $item) {
                    if ($item['selisih_ekor_pack'] != 0 || $item['selisih_quantity_kg'] != 0) {
                        $transactionType = ($item['selisih_ekor_pack'] > 0 || $item['selisih_quantity_kg'] > 0) ? 'in' : 'out';
                        $quantity = abs($item['selisih_quantity_kg']);
                        $ekor = abs($item['selisih_ekor_pack']);
                        $keterangan = $item['keterangan'] ?? 'Stock opname adjustment';

                        $stmt = $conn->prepare("INSERT INTO transactions 
                                              (date, storage, item, ekor_pack, quantity_kg, transaction_type, keterangan, user) 
                                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param(
                            'sssdssss',
                            $opnameDate,
                            $item['storage'],
                            $item['item'],
                            $ekor,
                            $quantity,
                            $transactionType,
                            $keterangan,
                            $user
                        );
                        $stmt->execute();
                    }
                }

                $conn->commit();
                $response = ['success' => true, 'message' => 'Adjustment saved successfully'];
            } catch (Exception $e) {
                $conn->rollback();
                $response = ['success' => false, 'message' => 'Error saving adjustment: ' . $e->getMessage()];
            }
            break;

        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
}

echo json_encode($response);
