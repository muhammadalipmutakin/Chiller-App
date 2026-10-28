<?php
require '../../controller/db/config.php'; // Ensure this path is correct

header('Content-Type: application/json');

$response = [
    'status' => 'error',
    'message' => 'Invalid request',
    'data' => []
];

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_dropdown_data':
            // Get distinct storages from locations table, filtering for 'chiller'
            $storages = [];
            $query = "SELECT DISTINCT lokasi FROM locations WHERE lokasi LIKE '%chiller%' AND lokasi IS NOT NULL AND lokasi != ''";
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $storages[] = $row['lokasi'];
            }
            sort($storages); // Sort for better display

            // Get distinct main items (before ':') from transactions
            $items = [];
            $query = "SELECT DISTINCT IF(LOCATE(':', item) > 0, SUBSTRING_INDEX(item, ':', 1), item) AS main_item
                      FROM transactions WHERE item IS NOT NULL AND item != ''";
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $items[] = trim($row['main_item']);
            }
            $items = array_unique($items);
            sort($items);

            // Get distinct subitems from the subitems table
            $subitems = [];
            $query = "SELECT DISTINCT subitem FROM subitems WHERE subitem IS NOT NULL AND subitem != ''";
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $subitems[] = $row['subitem'];
            }
            sort($subitems);

            $response['status'] = 'success';
            $response['message'] = 'Dropdown data fetched successfully';
            $response['data'] = [
                'storages' => $storages,
                'items' => $items,
                'subitems' => $subitems
            ];
            break;

        case 'check_stock':
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $selectedStorages = $_GET['storage'] ?? [];
            $selectedItems = $_GET['item'] ?? [];
            $selectedSubitems = $_GET['subitem'] ?? [];

            if (!is_array($selectedStorages)) $selectedStorages = [$selectedStorages];
            if (!is_array($selectedItems)) $selectedItems = [$selectedItems];
            if (!is_array($selectedSubitems)) $selectedSubitems = [$selectedSubitems];

            $query = "SELECT
                                IF(LOCATE(':', t.item) > 0, SUBSTRING_INDEX(t.item, ':', 1), t.item) AS main_item,
                                COALESCE(NULLIF(TRIM(SUBSTRING_INDEX(t.item, ':', -1)), IF(LOCATE(':', t.item) > 0, SUBSTRING_INDEX(t.item, ':', 1), t.item)), '') AS extracted_subitem,
                                t.storage,
                                SUM(CASE WHEN t.transaction_type IN ('in', 'transfer-in') THEN t.ekor_pack ELSE 0 END) -
                                SUM(CASE WHEN t.transaction_type IN ('out', 'transfer-out') THEN ABS(t.ekor_pack) ELSE 0 END) AS ekor_pack,
                                SUM(CASE WHEN t.transaction_type IN ('in', 'transfer-in') THEN t.quantity_kg ELSE 0 END) -
                                SUM(CASE WHEN t.transaction_type IN ('out', 'transfer-out') THEN ABS(t.quantity_kg) ELSE 0 END) AS quantity_kg
                              FROM transactions t
                              WHERE t.date BETWEEN ? AND ?";

            $params = [$startDate, $endDate];
            $types = 'ss';

            $appendInClause = function (&$query, &$params, &$types, $columnName, $selectedValues, $isItemOrSubitem = false) use ($conn) {
                if (!empty($selectedValues)) {
                    $placeholders = implode(',', array_fill(0, count($selectedValues), '?'));
                    if ($isItemOrSubitem) {
                        if ($columnName === 'item') {
                            $query .= " AND IF(LOCATE(':', t.item) > 0, SUBSTRING_INDEX(t.item, ':', 1), t.item) IN (" . $placeholders . ")";
                        } elseif ($columnName === 'subitem') {
                            $query .= " AND COALESCE(NULLIF(TRIM(SUBSTRING_INDEX(t.item, ':', -1)), IF(LOCATE(':', t.item) > 0, SUBSTRING_INDEX(t.item, ':', 1), t.item)), '') IN (" . $placeholders . ")";
                        }
                    } else {
                        $query .= " AND t." . $columnName . " IN (" . $placeholders . ")";
                    }
                    foreach ($selectedValues as $val) {
                        $params[] = $val;
                        $types .= 's';
                    }
                }
            };

            $appendInClause($query, $params, $types, 'storage', $selectedStorages);
            $appendInClause($query, $params, $types, 'item', $selectedItems, true);
            $appendInClause($query, $params, $types, 'subitem', $selectedSubitems, true);

            $query .= " GROUP BY main_item, extracted_subitem, t.storage
                        HAVING (ekor_pack != 0 OR quantity_kg != 0)";

            $stmt = $conn->prepare($query);
            if ($stmt === false) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();

            $stockData = [];
            while ($row = $result->fetch_assoc()) {
                $stockData[] = [
                    'item' => $row['main_item'],
                    'storage' => $row['storage'],
                    'subitem' => $row['extracted_subitem'], // Still extracted for display/logic
                    'ekor_pack' => (int)$row['ekor_pack'],
                    'quantity_kg' => number_format((float)$row['quantity_kg'], 2, '.', '')
                ];
            }

            $stmt->close();

            $response['status'] = 'success';
            $response['message'] = 'Stock data fetched successfully';
            $response['data'] = $stockData;
            break;

        case 'check_existing_opname':
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;

            if (!$startDate || !$endDate) {
                throw new Exception("Start date and end date are required for checking existing opname.");
            }

            $query = "SELECT COUNT(*) AS count FROM so_stock_awal WHERE opname_date BETWEEN ? AND ?";
            $stmt = $conn->prepare($query);
            if ($stmt === false) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }
            $stmt->bind_param('ss', $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            $response['status'] = 'success';
            $response['message'] = 'Existing opname check completed';
            $response['data'] = [
                'exists' => $row['count'] > 0
            ];
            break;

        case 'check_existing_opname_any':
            $query = "SELECT COUNT(*) AS count FROM so_stock_awal";
            $stmt = $conn->prepare($query);
            if ($stmt === false) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            $response['status'] = 'success';
            $response['message'] = 'Any existing opname check completed';
            $response['data'] = [
                'exists' => $row['count'] > 0
            ];
            break;

        case 'create_opname':
            $input = json_decode(file_get_contents('php://input'), true);
            $stockData = $input['stock_data'] ?? [];
            $filter = $input['filter'] ?? [];

            $opnameDate = $filter['endDate'] ?? date('Y-m-d'); // Uses current date if not provided

            $conn->begin_transaction();

            try {
                // Check if opname for this date already exists
                $checkQuery = "SELECT COUNT(*) AS count FROM so_stock_awal WHERE opname_date = ?";
                $checkStmt = $conn->prepare($checkQuery);
                if ($checkStmt === false) {
                    throw new Exception("Failed to prepare check statement: " . $conn->error);
                }
                $checkStmt->bind_param('s', $opnameDate);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                $checkRow = $checkResult->fetch_assoc();
                $checkStmt->close();

                if ($checkRow['count'] > 0) {
                    throw new Exception("Stock opname for date " . $opnameDate . " already exists. Cannot create multiple opnames for the same day.");
                }

                // Prepare the INSERT statement for so_stock_awal
                // Now, 'item' column will store the combined main_item and subitem
                $stmt = $conn->prepare("INSERT INTO so_stock_awal (item, storage, ekor_pack, quantity_kg, opname_date) VALUES (?, ?, ?, ?, ?)");
                if ($stmt === false) {
                    throw new Exception("Failed to prepare insert statement: " . $conn->error);
                }

                foreach ($stockData as $itemRow) {
                    $main_item = trim($itemRow['item']);
                    $subitem_val = trim($itemRow['subitem']);
                    $storage_val = $itemRow['storage'] ?? null;

                    // Construct the full item name as per your database's item column format
                    $full_item_name = $main_item;
                    if (!empty($subitem_val)) {
                        $full_item_name .= ' : ' . $subitem_val;
                    }

                    // Bind parameters: (item, storage, ekor_pack, quantity_kg, opname_date)
                    $stmt->bind_param('ssids', $full_item_name, $storage_val, $itemRow['ekor_pack'], $itemRow['quantity_kg'], $opnameDate);
                    $stmt->execute();
                }

                $stmt->close();
                $conn->commit();

                $response['status'] = 'success';
                $response['message'] = 'Stock opname created successfully';
            } catch (Exception $e) {
                $conn->rollback();
                $response['message'] = $e->getMessage();
            }
            break;

        // NEW CASE: get_opname_data_input
        case 'get_opname_data_input':
            $selectedStorages = $_GET['storage'] ?? [];
            if (!is_array($selectedStorages)) {
                $selectedStorages = [$selectedStorages];
            }

            $whereClauses = [];
            $params = [];
            $types = '';

            if (!empty($selectedStorages)) {
                $placeholders = implode(',', array_fill(0, count($selectedStorages), '?'));
                $whereClauses[] = "storage IN (" . $placeholders . ")";
                foreach ($selectedStorages as $val) {
                    $params[] = $val;
                    $types .= 's';
                }
            }

            $whereSql = '';
            if (!empty($whereClauses)) {
                $whereSql = " WHERE " . implode(' AND ', $whereClauses);
            }

            // Query for aggregated data
            $aggregatedQuery = "SELECT
                                    item,
                                    storage,
                                    opname_date,
                                    SUM(ekor_pack) AS total_ekor_pack,
                                    SUM(quantity_kg) AS total_quantity_kg
                                FROM so_hitung" . $whereSql . "
                                GROUP BY item, storage, opname_date
                                ORDER BY opname_date DESC, item ASC, storage ASC";

            $aggregatedStmt = $conn->prepare($aggregatedQuery);
            if ($aggregatedStmt === false) {
                throw new Exception("Failed to prepare aggregated statement: " . $conn->error);
            }
            if (!empty($params)) {
                $aggregatedStmt->bind_param($types, ...$params);
            }
            $aggregatedStmt->execute();
            $aggregatedResult = $aggregatedStmt->get_result();

            $aggregatedData = [];
            while ($row = $aggregatedResult->fetch_assoc()) {
                $aggregatedData[] = [
                    'item' => $row['item'],
                    'storage' => $row['storage'],
                    'total_ekor_pack' => (int)$row['total_ekor_pack'],
                    'total_quantity_kg' => number_format((float)$row['total_quantity_kg'], 2, '.', ''),
                    'opname_date' => $row['opname_date']
                ];
            }
            $aggregatedStmt->close();

            // Query for detailed data
            $detailedQuery = "SELECT
                                id,
                                item,
                                storage,
                                ekor_pack,
                                quantity_kg,
                                opname_date,
                                user_hitung
                              FROM so_hitung" . $whereSql . "
                              ORDER BY opname_date DESC, item ASC, storage ASC, id ASC";

            $detailedStmt = $conn->prepare($detailedQuery);
            if ($detailedStmt === false) {
                throw new Exception("Failed to prepare detailed statement: " . $conn->error);
            }
            if (!empty($params)) {
                // Reuse $types and $params as they are the same for the storage filter
                $detailedStmt->bind_param($types, ...$params);
            }
            $detailedStmt->execute();
            $detailedResult = $detailedStmt->get_result();

            $detailedData = [];
            while ($row = $detailedResult->fetch_assoc()) {
                $detailedData[] = [
                    'id' => $row['id'],
                    'item' => $row['item'],
                    'storage' => $row['storage'],
                    'ekor_pack' => (int)$row['ekor_pack'],
                    'quantity_kg' => number_format((float)$row['quantity_kg'], 2, '.', ''),
                    'opname_date' => $row['opname_date'],
                    'user_hitung' => $row['user_hitung']
                ];
            }
            $detailedStmt->close();

            $response['status'] = 'success';
            $response['message'] = 'Stock opname data input fetched successfully';
            $response['data'] = [
                'aggregated' => $aggregatedData,
                'detailed' => $detailedData
            ];
            break;


        default:
            throw new Exception("Unknown action: $action");
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Stock Opname Error: " . $e->getMessage());
} finally {
    echo json_encode($response);
    if (isset($conn)) {
        $conn->close();
    }
}
