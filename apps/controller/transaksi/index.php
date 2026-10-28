<?php
header('Content-Type: application/json');
session_name('chiller_app');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require '../../controller/db/config.php';

$logHandlerUrl = 'http://localhost/chiller/apps/controller/handler/log_activity_handler.php';
$currentUser   = $_SESSION['user_nama'] ?? 'Unknown User';
$currentUserId = $_SESSION['user_id'] ?? 0;

$action = $_GET['action'] ?? '';

// =========================================================================
// GET REQUESTS
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    switch ($action) {
        case 'getLocations':
            $sql = "SELECT id, lokasi FROM locations ORDER BY lokasi ASC";
            $result = $conn->query($sql);
            $locations = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $locations[] = ['id' => $row['id'], 'lokasi' => $row['lokasi'], 'nama_lokasi' => $row['lokasi']];
                }
            }
            echo json_encode($locations);
            break;

        case 'getAllItems':
            $sql = "SELECT id, item FROM items WHERE isActive = 1 ORDER BY item ASC";
            $result = $conn->query($sql);
            $items = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $items[] = ['id' => $row['id'], 'item' => $row['item'], 'nama_item' => $row['item']];
                }
            }
            echo json_encode($items);
            break;

        case 'getAllSubitems':
            $sql = "SELECT id, subitem FROM subitems ORDER BY subitem ASC";
            $result = $conn->query($sql);
            $subitems = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $subitems[] = ['id' => $row['id'], 'subitem' => $row['subitem'], 'nama_subitem' => $row['subitem']];
                }
            }
            echo json_encode($subitems);
            break;

        case 'getUsers':
            $sql = "SELECT id, nama FROM users ORDER BY nama ASC";
            $result = $conn->query($sql);
            $users = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $users[] = $row;
                }
            }
            echo json_encode($users);
            break;

        case 'getItemStock':
            $location = $_GET['location'] ?? '';
            $item     = $_GET['item'] ?? '';

            if (empty($location) || empty($item)) {
                echo json_encode(['ekor_pack' => 0, 'quantity_kg' => 0]);
                exit();
            }

            $sql = "SELECT SUM(ekor_pack) AS total_ekor_pack,
                           SUM(quantity_kg) AS total_quantity_kg
                    FROM transactions
                    WHERE storage = ? AND item = ? AND deleted_at IS NULL";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $location, $item);
            $stmt->execute();
            $result = $stmt->get_result();
            $stock  = $result->fetch_assoc();

            echo json_encode([
                'ekor_pack'   => (float)($stock['total_ekor_pack'] ?? 0),
                'quantity_kg' => (float)($stock['total_quantity_kg'] ?? 0)
            ]);
            $stmt->close();
            break;

        case 'getTransactions':
            $tanggal_awal   = $_GET['tanggal_awal'] ?? '';
            $tanggal_akhir  = $_GET['tanggal_akhir'] ?? '';
            $type_transaksi = $_GET['type_transaksi'] ?? '';
            $user_name      = $_GET['user_name'] ?? '';
            $from_location  = $_GET['from_location'] ?? '';
            $to_location    = $_GET['to_location'] ?? '';
            $storage        = trim($_GET['storage'] ?? '');
            $barcode_filter = trim($_GET['barcode'] ?? '');
            $limit          = isset($_GET['limit']) ? intval($_GET['limit']) : 500;

            // Normalisasi storage agar cocok dengan ENUM DB ('Chiller 1', 'Chiller 2', 'Chiller 3')
            if (!empty($storage)) {
                $storage = ucwords(strtolower($storage));
            }

            $item_input = $_GET['item'] ?? '';
            $itemTerms  = [];
            if (is_array($item_input)) {
                $itemTerms = array_filter(array_map('trim', $item_input));
            } elseif (is_string($item_input) && !empty($item_input)) {
                $itemTerms = array_filter(array_map('trim', explode(',', $item_input)));
            }

            $subitem_input = $_GET['subitem'] ?? '';
            $subitemTerms  = [];
            if (is_array($subitem_input)) {
                $subitemTerms = array_filter(array_map('trim', $subitem_input));
            } elseif (is_string($subitem_input) && !empty($subitem_input)) {
                $subitemTerms = array_filter(array_map('trim', explode(',', $subitem_input)));
            }

            // =========================================================================
            // 1. FILTER TARGET ITEMS (UNTUK CTE INDEX)
            // =========================================================================
            $targetWhere = ["t.deleted_at IS NULL"];
            $targetParams = [];
            $targetTypes  = "";

            if (!empty($tanggal_awal)) {
                $targetWhere[] = "t.date >= ?";
                $targetParams[] = $tanggal_awal;
                $targetTypes   .= "s";
            }
            if (!empty($tanggal_akhir)) {
                $targetWhere[] = "t.date <= ?";
                $targetParams[] = $tanggal_akhir;
                $targetTypes   .= "s";
            }
            if (!empty($storage) && strtolower($storage) !== 'semua') {
                $targetWhere[] = "t.storage = ?";
                $targetParams[] = $storage;
                $targetTypes   .= "s";
            }
            if (!empty($itemTerms)) {
                $itemCond = [];
                foreach ($itemTerms as $term) {
                    $itemCond[] = "t.item LIKE ?";
                    $targetParams[] = '%' . $term . '%';
                    $targetTypes   .= "s";
                }
                $targetWhere[] = "(" . implode(" OR ", $itemCond) . ")";
            }
            // Filter Subitem Eksak: Menghindari 'M.1' cocok dengan 'M.10'
            if (!empty($subitemTerms)) {
                $subCond = [];
                foreach ($subitemTerms as $term) {
                    if ($term === '__NO_SUBITEM__') {
                        $subCond[] = "t.item NOT LIKE '%:%'";
                    } else {
                        $subCond[] = "(t.item LIKE ? OR TRIM(SUBSTRING_INDEX(t.item, ' : ', -1)) = ?)";
                        $targetParams[] = '% : ' . $term;
                        $targetParams[] = $term;
                        $targetTypes   .= "ss";
                    }
                }
                if (!empty($subCond)) {
                    $targetWhere[] = "(" . implode(" OR ", $subCond) . ")";
                }
            }
            $targetWhereClause = implode(" AND ", $targetWhere);

            // =========================================================================
            // 2. FILTER HISTORIS HINGGA TANGGAL AKHIR
            // =========================================================================
            $historyDateCond = "";
            $historyParams   = [];
            $historyTypes    = "";
            if (!empty($tanggal_akhir)) {
                $historyDateCond = "AND t.date <= ?";
                $historyParams[] = $tanggal_akhir;
                $historyTypes   .= "s";
            }

            // =========================================================================
            // 3. FILTER TAMPILAN LUAR
            // =========================================================================
            $outerWhere = ["t.deleted_at IS NULL"];
            $outerParams = [];
            $outerTypes  = "";

            if (!empty($tanggal_awal)) {
                $outerWhere[] = "t.date >= ?";
                $outerParams[] = $tanggal_awal;
                $outerTypes   .= "s";
            }
            if (!empty($tanggal_akhir)) {
                $outerWhere[] = "t.date <= ?";
                $outerParams[] = $tanggal_akhir;
                $outerTypes   .= "s";
            }
            if (!empty($storage) && strtolower($storage) !== 'semua') {
                $outerWhere[] = "t.storage = ?";
                $outerParams[] = $storage;
                $outerTypes   .= "s";
            }
            if (!empty($type_transaksi)) {
                $outerWhere[] = "t.transaction_type LIKE ?";
                $outerParams[] = $type_transaksi . '%';
                $outerTypes   .= "s";
            }
            if (!empty($user_name)) {
                $outerWhere[] = "t.user = ?";
                $outerParams[] = $user_name;
                $outerTypes   .= "s";
            }
            if (!empty($from_location)) {
                $outerWhere[] = "t.from_location = ?";
                $outerParams[] = $from_location;
                $outerTypes   .= "s";
            }
            if (!empty($to_location)) {
                $outerWhere[] = "t.to_location = ?";
                $outerParams[] = $to_location;
                $outerTypes   .= "s";
            }
            if (!empty($barcode_filter)) {
                $outerWhere[] = "t.barcode = ?";
                $outerParams[] = $barcode_filter;
                $outerTypes   .= "s";
            }
            if (!empty($itemTerms)) {
                $itemCond = [];
                foreach ($itemTerms as $term) {
                    $itemCond[] = "t.item LIKE ?";
                    $outerParams[] = '%' . $term . '%';
                    $outerTypes   .= "s";
                }
                $outerWhere[] = "(" . implode(" OR ", $itemCond) . ")";
            }
            if (!empty($subitemTerms)) {
                $subCond = [];
                foreach ($subitemTerms as $term) {
                    if ($term === '__NO_SUBITEM__') {
                        $subCond[] = "t.item NOT LIKE '%:%'";
                    } else {
                        $subCond[] = "(t.item LIKE ? OR TRIM(SUBSTRING_INDEX(t.item, ' : ', -1)) = ?)";
                        $outerParams[] = '% : ' . $term;
                        $outerParams[] = $term;
                        $outerTypes   .= "ss";
                    }
                }
                if (!empty($subCond)) {
                    $outerWhere[] = "(" . implode(" OR ", $subCond) . ")";
                }
            }

            $outerWhereClause = implode(" AND ", $outerWhere);

            // =========================================================================
            // CTE QUERY RINGAN (IN-MEMORY FAST RUNNING TOTAL)
            // =========================================================================
            $sql = "
                WITH TargetItems AS (
                    SELECT DISTINCT t.storage, t.item 
                    FROM transactions t
                    WHERE {$targetWhereClause}
                ),
                ItemRunningStock AS (
                    SELECT 
                        t.id,
                        SUM(t.ekor_pack) OVER (PARTITION BY t.storage, t.item ORDER BY t.date ASC, t.id ASC) AS stock_ekor,
                        SUM(t.quantity_kg) OVER (PARTITION BY t.storage, t.item ORDER BY t.date ASC, t.id ASC) AS stock_berat
                    FROM transactions t
                    INNER JOIN TargetItems ti_filter 
                            ON t.storage = ti_filter.storage 
                           AND t.item = ti_filter.item
                    WHERE t.deleted_at IS NULL {$historyDateCond}
                )
                SELECT 
                    t.id, 
                    t.barcode, 
                    t.date, 
                    t.from_location, 
                    t.to_location, 
                    t.storage, 
                    t.item,
                    t.ekor_pack, 
                    t.quantity_kg, 
                    t.transaction_type, 
                    t.keterangan,
                    t.user, 
                    t.created_at, 
                    t.updated_at,
                    t.id_in,
                    ti.date AS tanggal_produksi,
                    CASE 
                        WHEN t.transaction_type IN ('out', 'transfer-out') AND t.id_in IS NOT NULL AND ti.date IS NOT NULL 
                        THEN DATEDIFF(t.date, ti.date)
                        ELSE NULL 
                    END AS lama_penyimpanan_hari,
                    CASE 
                        WHEN t.transaction_type IN ('out', 'transfer-out') AND t.barcode IS NOT NULL AND t.barcode != '' 
                        THEN 
                            CASE 
                                WHEN ti.date IS NOT NULL THEN 
                                    CONCAT(
                                        DATE_FORMAT(ti.date, '%d-%m-%Y'), 
                                        ' (', 
                                        CASE 
                                            WHEN DATEDIFF(t.date, ti.date) = 0 THEN 'Hari yang sama'
                                            WHEN DATEDIFF(t.date, ti.date) = 1 THEN '1 hari'
                                            ELSE CONCAT(DATEDIFF(t.date, ti.date), ' hari')
                                        END, 
                                        ')'
                                    )
                                ELSE 'Tidak ada data produksi'
                            END
                        ELSE '-'
                    END AS info_produksi,
                    irs.stock_ekor,
                    irs.stock_berat
                FROM transactions t
                INNER JOIN ItemRunningStock irs ON t.id = irs.id
                LEFT JOIN transactions ti ON t.id_in = ti.id AND ti.deleted_at IS NULL
                WHERE {$outerWhereClause}
                ORDER BY t.date DESC, t.id DESC
                LIMIT ?
            ";

            $finalParams = array_merge($targetParams, $historyParams, $outerParams, [$limit]);
            $finalTypes  = $targetTypes . $historyTypes . $outerTypes . "i";

            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                echo json_encode(['success' => false, 'message' => 'Gagal menyiapkan query: ' . $conn->error]);
                exit();
            }

            if (!empty($finalParams)) {
                $stmt->bind_param($finalTypes, ...$finalParams);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            $transactions      = [];
            $noBarcodeCountIn  = 0;
            $noBarcodeCountOut = 0;

            $distinctMap = [
                'barcode'          => [],
                'date'             => [],
                'from_location'    => [],
                'to_location'      => [],
                'storage'          => [],
                'item'             => [],
                'ekor_pack'        => [],
                'quantity_kg'      => [],
                'stock_ekor'       => [],
                'stock_berat'      => [],
                'transaction_type' => [],
                'keterangan'       => [],
                'user'             => [],
                'created_at_date'  => [],
                'info_produksi'    => []
            ];

            while ($row = $result->fetch_assoc()) {
                if (empty($row['barcode'])) {
                    if ($row['quantity_kg'] > 0) {
                        $noBarcodeCountIn++;
                    } elseif ($row['quantity_kg'] < 0) {
                        $noBarcodeCountOut++;
                    }
                }

                $createdDate = !empty($row['created_at']) ? substr($row['created_at'], 0, 10) : '';
                $row['created_at_date'] = $createdDate;

                $transactions[] = $row;

                $distinctMap['barcode'][(string)$row['barcode']] = true;
                $distinctMap['date'][$row['date']] = true;
                if ($row['from_location'] !== null) $distinctMap['from_location'][$row['from_location']] = true;
                if ($row['to_location'] !== null) $distinctMap['to_location'][$row['to_location']] = true;
                if ($row['storage'] !== null) $distinctMap['storage'][$row['storage']] = true;
                if ($row['item'] !== null) $distinctMap['item'][$row['item']] = true;
                $distinctMap['ekor_pack'][(string)$row['ekor_pack']] = true;
                $distinctMap['quantity_kg'][(string)$row['quantity_kg']] = true;
                $distinctMap['stock_ekor'][(string)$row['stock_ekor']] = true;
                $distinctMap['stock_berat'][(string)$row['stock_berat']] = true;
                $distinctMap['transaction_type'][$row['transaction_type']] = true;
                if ($row['keterangan'] !== null) $distinctMap['keterangan'][$row['keterangan']] = true;
                if ($row['user'] !== null) $distinctMap['user'][$row['user']] = true;
                if ($createdDate !== '') $distinctMap['created_at_date'][$createdDate] = true;
                if ($row['info_produksi'] !== null) $distinctMap['info_produksi'][$row['info_produksi']] = true;
            }
            $stmt->close();

            $distinctData = [];
            foreach ($distinctMap as $key => $values) {
                $keys = array_keys($values);
                if ($key !== 'info_produksi') {
                    sort($keys);
                }
                $distinctData[$key] = $keys;
            }

            echo json_encode([
                'success'                => true,
                'data'                   => $transactions,
                'no_barcode_in_count'    => $noBarcodeCountIn,
                'no_barcode_out_count'   => $noBarcodeCountOut,
                'no_barcode_total_count' => $noBarcodeCountIn + $noBarcodeCountOut,
                'distinct_data'          => $distinctData
            ]);
            break;

        case 'downloadExcel':
            $tanggal_awal   = $_GET['tanggal_awal'] ?? '';
            $tanggal_akhir  = $_GET['tanggal_akhir'] ?? '';
            $storage        = trim($_GET['storage'] ?? '');
            $item_input     = $_GET['item'] ?? '';
            $subitem_input  = $_GET['subitem'] ?? '';
            $search         = $_GET['search'] ?? '';
            $barcode_filter = trim($_GET['barcode'] ?? '');
            $type_transaksi = $_GET['type_transaksi'] ?? '';
            $user_name      = $_GET['user_name'] ?? '';
            $from_location  = $_GET['from_location'] ?? '';
            $to_location    = $_GET['to_location'] ?? '';

            if (!empty($storage)) {
                $storage = ucwords(strtolower($storage));
            }

            if (empty($tanggal_awal) || empty($tanggal_akhir)) {
                echo json_encode(['success' => false, 'message' => 'Tanggal awal dan akhir harus disertakan.']);
                exit();
            }

            $targetWhere = ["t.deleted_at IS NULL", "t.date >= ?", "t.date <= ?"];
            $targetParams = [$tanggal_awal, $tanggal_akhir];
            $targetTypes  = "ss";

            if (!empty($storage) && strtolower($storage) !== 'semua') {
                $targetWhere[] = "t.storage = ?";
                $targetParams[] = $storage;
                $targetTypes   .= "s";
            }

            $targetWhereClause = implode(" AND ", $targetWhere);

            $outerWhere = ["t.deleted_at IS NULL", "t.date >= ?", "t.date <= ?"];
            $outerParams = [$tanggal_awal, $tanggal_akhir];
            $outerTypes  = "ss";

            if (!empty($storage) && strtolower($storage) !== 'semua') {
                $outerWhere[] = "t.storage = ?";
                $outerParams[] = $storage;
                $outerTypes   .= "s";
            }
            if (!empty($type_transaksi)) {
                $outerWhere[] = "t.transaction_type LIKE ?";
                $outerParams[] = $type_transaksi . '%';
                $outerTypes   .= "s";
            }
            if (!empty($user_name)) {
                $outerWhere[] = "t.user = ?";
                $outerParams[] = $user_name;
                $outerTypes   .= "s";
            }
            if (!empty($from_location)) {
                $outerWhere[] = "t.from_location = ?";
                $outerParams[] = $from_location;
                $outerTypes   .= "s";
            }
            if (!empty($to_location)) {
                $outerWhere[] = "t.to_location = ?";
                $outerParams[] = $to_location;
                $outerTypes   .= "s";
            }
            if (!empty($barcode_filter)) {
                $outerWhere[] = "t.barcode = ?";
                $outerParams[] = $barcode_filter;
                $outerTypes   .= "s";
            }
            if (!empty($search) && strlen($search) >= 3) {
                $outerWhere[] = "(t.item LIKE ? OR t.keterangan LIKE ? OR t.user LIKE ?)";
                $outerParams[] = '%' . $search . '%';
                $outerParams[] = '%' . $search . '%';
                $outerParams[] = '%' . $search . '%';
                $outerTypes   .= "sss";
            }

            $outerWhereClause = implode(" AND ", $outerWhere);

            $sql_final = "
                WITH TargetItems AS (
                    SELECT DISTINCT t.storage, t.item 
                    FROM transactions t
                    WHERE {$targetWhereClause}
                ),
                ItemRunningStock AS (
                    SELECT 
                        t.id,
                        SUM(t.ekor_pack) OVER (PARTITION BY t.storage, t.item ORDER BY t.date ASC, t.id ASC) AS stock_ekor,
                        SUM(t.quantity_kg) OVER (PARTITION BY t.storage, t.item ORDER BY t.date ASC, t.id ASC) AS stock_berat
                    FROM transactions t
                    INNER JOIN TargetItems ti_filter 
                            ON t.storage = ti_filter.storage 
                           AND t.item = ti_filter.item
                    WHERE t.deleted_at IS NULL AND t.date <= ?
                )
                SELECT 
                    t.id, 
                    t.barcode, 
                    t.date, 
                    t.from_location, 
                    t.to_location, 
                    t.storage, 
                    t.item,
                    t.ekor_pack, 
                    t.quantity_kg, 
                    t.transaction_type, 
                    t.keterangan,
                    t.user, 
                    t.created_at, 
                    t.updated_at,
                    ti.date AS tanggal_produksi,
                    CASE 
                        WHEN t.transaction_type IN ('out', 'transfer-out') AND t.id_in IS NOT NULL AND ti.date IS NOT NULL 
                        THEN DATEDIFF(t.date, ti.date)
                        ELSE NULL 
                    END AS lama_penyimpanan_hari,
                    CASE 
                        WHEN t.transaction_type IN ('out', 'transfer-out') AND t.barcode IS NOT NULL AND t.barcode != '' 
                        THEN 
                            CASE 
                                WHEN ti.date IS NOT NULL THEN 
                                    CONCAT(
                                        DATE_FORMAT(ti.date, '%d-%m-%Y'), 
                                        ' (', 
                                        CASE 
                                            WHEN DATEDIFF(t.date, ti.date) = 0 THEN 'Hari yang sama'
                                            WHEN DATEDIFF(t.date, ti.date) = 1 THEN '1 hari'
                                            ELSE CONCAT(DATEDIFF(t.date, ti.date), ' hari')
                                        END, 
                                        ')'
                                    )
                                ELSE 'Tidak ada data produksi'
                            END
                        ELSE '-'
                    END AS info_produksi,
                    irs.stock_ekor,
                    irs.stock_berat
                FROM transactions t
                INNER JOIN ItemRunningStock irs ON t.id = irs.id
                LEFT JOIN transactions ti ON t.id_in = ti.id AND ti.deleted_at IS NULL
                WHERE {$outerWhereClause}
                ORDER BY t.date DESC, t.id DESC
            ";

            $finalParams = array_merge($targetParams, [$tanggal_akhir], $outerParams);
            $finalTypes  = $targetTypes . "s" . $outerTypes;

            $stmt = $conn->prepare($sql_final);
            if ($stmt === false) {
                echo json_encode(['success' => false, 'message' => 'Gagal mempersiapkan statement SQL: ' . $conn->error]);
                exit();
            }

            if (!empty($finalParams)) {
                $stmt->bind_param($finalTypes, ...$finalParams);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            $transactions = [];
            while ($row = $result->fetch_assoc()) {
                $transactions[] = $row;
            }

            echo json_encode(['success' => true, 'data' => $transactions]);
            $stmt->close();
            break;

        case 'getTransactionDetail':
            $id = $_GET['id'] ?? null;
            if (empty($id)) {
                echo json_encode(['success' => false, 'message' => 'ID transaksi tidak ditemukan.']);
                exit();
            }

            $sql = "SELECT id, barcode, date, from_location, to_location, storage, item, 
                           ekor_pack, quantity_kg, transaction_type, keterangan, 
                           user, created_at, updated_at
                    FROM transactions
                    WHERE id = ? AND deleted_at IS NULL";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $transaction = $result->fetch_assoc();

            if ($transaction) {
                echo json_encode(['success' => true, 'data' => $transaction]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan.']);
            }
            $stmt->close();
            break;

        case 'searchItems':
            $searchTerm = trim($_GET['q'] ?? '');
            $page  = max(1, intval($_GET['page'] ?? 1));
            $limit = 100;
            $offset = ($page - 1) * $limit;

            $whereSql = "WHERE isActive = 1";
            $params = [];
            $types  = "";

            if (!empty($searchTerm)) {
                $whereSql .= " AND item LIKE ?";
                $params[] = '%' . $searchTerm . '%';
                $types   .= "s";
            }

            $countSql = "SELECT COUNT(*) as total FROM items {$whereSql}";
            $stmtCount = $conn->prepare($countSql);
            if (!empty($params)) {
                $stmtCount->bind_param($types, ...$params);
            }
            $stmtCount->execute();
            $total = $stmtCount->get_result()->fetch_assoc()['total'] ?? 0;
            $stmtCount->close();

            $sql = "SELECT id, item FROM items {$whereSql} ORDER BY item ASC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types   .= "ii";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            $items = [];
            while ($row = $result->fetch_assoc()) {
                $items[] = [
                    'id'   => $row['item'],
                    'text' => $row['item']
                ];
            }
            $stmt->close();

            echo json_encode([
                'results'    => $items,
                'pagination' => [
                    'more' => ($offset + $limit) < $total
                ]
            ]);
            break;

        case 'searchSubitems':
            $searchTerm = trim($_GET['q'] ?? '');
            $page  = max(1, intval($_GET['page'] ?? 1));
            $limit = 100;
            $offset = ($page - 1) * $limit;

            if ($searchTerm === '__NO_SUBITEM__') {
                echo json_encode([
                    'results' => [['id' => '__NO_SUBITEM__', 'text' => 'Tanpa Subitem']],
                    'pagination' => ['more' => false]
                ]);
                exit();
            }

            $whereSql = "WHERE 1=1";
            $params = [];
            $types  = "";

            if (!empty($searchTerm)) {
                $whereSql .= " AND subitem LIKE ?";
                $params[] = '%' . $searchTerm . '%';
                $types   .= "s";
            }

            $countSql = "SELECT COUNT(*) AS total FROM subitems {$whereSql}";
            $stmtCount = $conn->prepare($countSql);
            if (!empty($params)) {
                $stmtCount->bind_param($types, ...$params);
            }
            $stmtCount->execute();
            $total = $stmtCount->get_result()->fetch_assoc()['total'] ?? 0;
            $stmtCount->close();

            $sql = "SELECT subitem FROM subitems {$whereSql} ORDER BY subitem ASC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types   .= "ii";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            $subitems = [];
            while ($row = $result->fetch_assoc()) {
                $subitems[] = [
                    'id'   => $row['subitem'],
                    'text' => $row['subitem']
                ];
            }
            $stmt->close();

            echo json_encode([
                'results'    => $subitems,
                'pagination' => [
                    'more' => ($offset + $limit) < $total
                ]
            ]);
            break;

        default:
            echo json_encode(['error' => 'Aksi GET tidak valid']);
            break;
    }
    $conn->close();
    exit();
}

// =========================================================================
// POST REQUESTS (UPDATE & DELETE TRANSAKSI)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'updateTransaction':
            $id                         = $input['id'] ?? null;
            $date                       = $input['date'] ?? null;
            $from_location              = $input['from_location'] ?? null;
            $to_location                = $input['to_location'] ?? null;
            $item_raw                   = $input['item'] ?? null;
            $subitem_raw                = $input['subitem'] ?? null;
            $ekor_pack_input            = (int)($input['ekor_pack'] ?? 0);
            $quantity_kg_input          = (float)($input['quantity_kg'] ?? 0.0);
            $keterangan                 = $input['keterangan'] ?? null;
            $user                       = $input['user'] ?? $currentUser;
            $transaction_type_frontend  = $input['transaction_type_frontend'] ?? null;
            $barcode                    = isset($input['barcode']) && trim($input['barcode']) !== '' ? (int)trim($input['barcode']) : null;

            if (empty($id) || empty($date) || empty($from_location) || empty($to_location) || empty($item_raw)) {
                echo json_encode(['success' => false, 'message' => 'Data tidak lengkap untuk update.']);
                $conn->close();
                exit();
            }

            $chillerLocations = ['Chiller 1', 'Chiller 2', 'Chiller 3'];
            $fromChiller      = in_array($from_location, $chillerLocations);
            $toChiller        = in_array($to_location, $chillerLocations);

            $item_final = $item_raw;
            if (!empty($subitem_raw)) {
                $item_final = $item_raw . ' : ' . $subitem_raw;
            }

            $conn->begin_transaction();

            try {
                $original_sql = "SELECT id, date, from_location, to_location, storage, item, ekor_pack, quantity_kg, transaction_type, keterangan, user, barcode, created_at, updated_at, deleted_at
                                 FROM transactions
                                 WHERE id = ? AND deleted_at IS NULL";
                $stmt_original = $conn->prepare($original_sql);
                $stmt_original->bind_param("i", $id);
                $stmt_original->execute();
                $original_transaction = $stmt_original->get_result()->fetch_assoc();
                $stmt_original->close();

                if (!$original_transaction) {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan atau sudah dihapus.']);
                    $conn->close();
                    exit();
                }

                $new_transaction_type = '';
                $new_storage_location = '';

                if ($toChiller && !$fromChiller) {
                    $new_transaction_type = 'in';
                    $new_storage_location = $to_location;
                } elseif ($fromChiller && !$toChiller) {
                    $new_transaction_type = 'out';
                    $new_storage_location = $from_location;
                } elseif ($fromChiller && $toChiller) {
                    $new_transaction_type = 'transfer-out';
                    $new_storage_location = $from_location;
                } else {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'message' => 'Tipe transaksi tidak valid berdasarkan lokasi.']);
                    $conn->close();
                    exit();
                }

                $ekor_pack_signed   = ($new_transaction_type === 'out' || $new_transaction_type === 'transfer-out') ? -abs($ekor_pack_input) : abs($ekor_pack_input);
                $quantity_kg_signed = ($new_transaction_type === 'out' || $new_transaction_type === 'transfer-out') ? -abs($quantity_kg_input) : abs($quantity_kg_input);

                if ($new_transaction_type === 'transfer-out') {
                    $delete_sql = "UPDATE transactions SET deleted_at = NOW() WHERE id = ?";
                    $stmt_delete = $conn->prepare($delete_sql);
                    $stmt_delete->bind_param("i", $id);
                    $stmt_delete->execute();
                    $stmt_delete->close();

                    $stmt_out = $conn->prepare("INSERT INTO transactions (date, from_location, to_location, storage, item, ekor_pack, quantity_kg, transaction_type, keterangan, user, barcode) VALUES (?, ?, ?, ?, ?, ?, ?, 'transfer-out', ?, ?, ?)");
                    $stmt_out->bind_param("sssssddssi", $date, $from_location, $to_location, $from_location, $item_final, $ekor_pack_signed, $quantity_kg_signed, $keterangan, $user, $barcode);
                    $stmt_out->execute();
                    $stmt_out->close();

                    $ekor_in = abs($ekor_pack_input);
                    $kg_in   = abs($quantity_kg_input);
                    $stmt_in = $conn->prepare("INSERT INTO transactions (date, from_location, to_location, storage, item, ekor_pack, quantity_kg, transaction_type, keterangan, user, barcode) VALUES (?, ?, ?, ?, ?, ?, ?, 'transfer-in', ?, ?, ?)");
                    $stmt_in->bind_param("sssssddssi", $date, $from_location, $to_location, $to_location, $item_final, $ekor_in, $kg_in, $keterangan, $user, $barcode);
                    $stmt_in->execute();
                    $stmt_in->close();
                } else {
                    $update_sql = "UPDATE transactions SET date = ?, from_location = ?, to_location = ?, storage = ?, item = ?, ekor_pack = ?, quantity_kg = ?, transaction_type = ?, keterangan = ?, user = ?, barcode = ?, updated_at = NOW() WHERE id = ?";
                    $stmt_update = $conn->prepare($update_sql);
                    $stmt_update->bind_param("sssssddsssii", $date, $from_location, $to_location, $new_storage_location, $item_final, $ekor_pack_signed, $quantity_kg_signed, $new_transaction_type, $keterangan, $user, $barcode, $id);
                    $stmt_update->execute();
                    $stmt_update->close();
                }

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Transaksi berhasil diperbarui.']);
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Gagal memperbarui transaksi: ' . $e->getMessage()]);
            }
            break;

        case 'deleteTransaction':
            $id = $input['id'] ?? null;
            if (empty($id)) {
                echo json_encode(['success' => false, 'message' => 'ID transaksi tidak ditemukan.']);
                exit();
            }

            $conn->begin_transaction();
            try {
                $sql = "UPDATE transactions SET deleted_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $conn->commit();
                    echo json_encode(['success' => true, 'message' => 'Transaksi berhasil dihapus.']);
                } else {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan atau sudah dihapus.']);
                }
                $stmt->close();
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus transaksi: ' . $e->getMessage()]);
            }
            break;

        default:
            echo json_encode(['error' => 'Aksi POST tidak dikenal']);
            break;
    }
    $conn->close();
    exit();
}
