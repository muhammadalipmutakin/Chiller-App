<?php
header('Content-Type: application/json');

session_name('chiller_app');
// Start session to get user_nama
session_start();

// Include database configuration
require '../../controller/db/config.php';

$logHandlerUrl = 'http://localhost/chiller/apps/controller/handler/log_activity_handler.php';
$currentUser = $_SESSION['user_nama'] ?? 'Unknown User';
$currentUserId = $_SESSION['user_id'] ?? 0;

// Get the action from the request
$action = $_GET['action'] ?? '';

// Handle GET requests for data fetching
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    switch ($action) {
        case 'getLocations':
            $sql = "SELECT id, lokasi FROM locations";
            $result = $conn->query($sql);
            $locations = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $locations[] = $row;
                }
            }
            echo json_encode($locations);
            break;

        case 'getAllItems':
            $sql = "SELECT id, item FROM items WHERE isActive = 1";
            $result = $conn->query($sql);
            $items = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $items[] = $row;
                }
            }
            echo json_encode($items);
            break;

        case 'getItemsWithStock':
            $location = $_GET['location'] ?? '';

            // 1. Get distinct items with their current stock for the specific location
            $sql_stock = "SELECT
                        t.item,
                        COALESCE(SUM(t.ekor_pack), 0) AS ekor_pack,
                        COALESCE(SUM(t.quantity_kg), 0) AS quantity_kg
                    FROM transactions t
                    WHERE t.storage = ? AND t.deleted_at IS NULL
                    GROUP BY t.item
                    HAVING COALESCE(SUM(t.ekor_pack), 0) != 0 OR COALESCE(SUM(t.quantity_kg), 0) != 0
                    ORDER BY t.item";

            $stmt_stock = $conn->prepare($sql_stock);
            $stmt_stock->bind_param("s", $location);
            $stmt_stock->execute();
            $result_stock = $stmt_stock->get_result();

            $itemsWithStock = [];
            $itemNamesWithStock = []; // To keep track of items already added
            if ($result_stock && $result_stock->num_rows > 0) {
                while ($row = $result_stock->fetch_assoc()) {
                    $itemsWithStock[] = [
                        'item' => $row['item'],
                        'ekor_pack' => (int)$row['ekor_pack'],
                        'quantity_kg' => (float)$row['quantity_kg']
                    ];
                    $itemNamesWithStock[] = $row['item']; // Add item name to tracker
                }
            }
            $stmt_stock->close();

            // 2. Get all active items from the 'items' table
            $sql_all_items = "SELECT item FROM items WHERE isActive = 1 ORDER BY item";
            $result_all_items = $conn->query($sql_all_items);

            $allItems = [];
            if ($result_all_items && $result_all_items->num_rows > 0) {
                while ($row = $result_all_items->fetch_assoc()) {
                    // Only add items that are not already in itemsWithStock
                    if (!in_array($row['item'], $itemNamesWithStock)) {
                        $allItems[] = [
                            'item' => $row['item'],
                            'ekor_pack' => 0, // Default to 0 stock for items without specific stock
                            'quantity_kg' => 0.0
                        ];
                    }
                }
            }

            // 3. Combine and prioritize: itemsWithStock followed by other active items
            $finalItemsList = array_merge($itemsWithStock, $allItems);

            echo json_encode($finalItemsList);
            break;

        case 'getAllSubitems':
            $sql = "SELECT id, subitem FROM subitems ORDER BY subitem ASC";
            $result = $conn->query($sql);
            $subitems = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $subitems[] = $row;
                }
            }
            echo json_encode($subitems);
            break;

        case 'getTransactionByBarcodeIn':
            $raw_barcode = trim($_GET['barcode_in'] ?? '');

            if (empty($raw_barcode)) {
                echo json_encode(['success' => false, 'message' => 'Barcode tidak boleh kosong.']);
                if ($conn) $conn->close();
                exit();
            }

            // Validasi maksimal 4 digit
            if (strlen($raw_barcode) > 4) {
                echo json_encode(['success' => false, 'message' => 'Barcode maksimal 4 digit.']);
                if ($conn) $conn->close();
                exit();
            }

            $barcode = ltrim($raw_barcode, '0');
            $date = $_GET['date'] ?? '';

            $escaped_barcode = $conn->real_escape_string($barcode);

            $sql_transaction =
                "SELECT id, item, storage, ekor_pack, quantity_kg, barcode, created_at
                FROM transactions
                WHERE barcode = '$escaped_barcode'
                    AND transaction_type IN ('in', 'gabung-new', 'transfer-in', 'adjustment-new', 'adjustment')
                    AND deleted_at IS NULL
                    AND `date` <= '$date'
                ORDER BY created_at DESC
                LIMIT 1";

            $result_transaction = $conn->query($sql_transaction);

            if ($result_transaction && $result_transaction->num_rows > 0) {
                $transaction_data = $result_transaction->fetch_assoc();

                $escaped_item = $conn->real_escape_string($transaction_data['item']);
                $escaped_storage = $conn->real_escape_string($transaction_data['storage']);

                $sql_stock_item_storage =
                    "SELECT
                        SUM(ekor_pack) AS current_ekor_stock,
                        SUM(quantity_kg) AS current_kg_stock
                    FROM
                        transactions
                    WHERE
                        item = '$escaped_item'
                        AND storage = '$escaped_storage'
                        AND deleted_at IS NULL
                        AND date<='$date' ;";

                $result_stock_item_storage = $conn->query($sql_stock_item_storage);

                if ($result_stock_item_storage && $result_stock_item_storage->num_rows > 0) {
                    $stock_data_item_storage = $result_stock_item_storage->fetch_assoc();
                    $transaction_data['current_ekor_stock'] = (float) $stock_data_item_storage['current_ekor_stock'];
                    $transaction_data['current_kg_stock'] = (float) $stock_data_item_storage['current_kg_stock'];
                } else {
                    $transaction_data['current_ekor_stock'] = 0;
                    $transaction_data['current_kg_stock'] = 0;
                }

                $sql_stock_barcode =
                    "SELECT 
                        SUM(ekor) AS current_ekor_barcode, 
                        SUM(berat) AS current_kg_barcode, 
                        MAX(tanggal_stock) AS tanggal_stock,
                        IF(MAX(tanggal_stock) >= '$date', 'baru', 'lama') AS status_bahan
                    FROM ( 
                    SELECT * FROM (
                        SELECT id, ekor_pack AS ekor, quantity_kg AS berat, `date` AS tanggal_stock
                        FROM transactions
                        WHERE quantity_kg>0
                            AND barcode = '$escaped_barcode'
                            AND transaction_type IN ('in', 'transfer-in','gabung-new', 'adjustment-new', 'adjustment')
                            AND deleted_at IS NULL
                            AND quantity_kg>0
                            AND `date` <= '$date'
                        ORDER BY id DESC
                        LIMIT 1
                    ) AS C
                    UNION ALL
                    SELECT B.id_in, B.ekor, B.berat, '' AS tanggal_stock
                    FROM (
                        SELECT id, SUM(ekor_pack) AS ekor, SUM(quantity_kg) AS berat 
                        FROM transactions
                        WHERE 1=1
                            AND barcode = '$escaped_barcode'
                            AND transaction_type IN ('in', 'transfer-in','gabung-new', 'adjustment-new', 'adjustment')
                            AND deleted_at IS NULL
                            AND `date` <= '$date'
                            AND quantity_kg>0
                        GROUP BY id
                        ORDER BY id DESC
                        LIMIT 1
                    ) AS A
                    LEFT JOIN (
                        SELECT id_in, SUM(ekor_pack) AS ekor, SUM(quantity_kg) AS berat 
                        FROM transactions
                        WHERE 1=1
                            AND barcode = '$escaped_barcode'
                            AND id_in IS NOT NULL
                            AND `date` <= '$date'
                            AND deleted_at IS NULL
                        GROUP BY id_in
                    ) AS B
                    ON A.id = B.id_in
                    ) AS B
                ;";

                $result_stock_barcode = $conn->query($sql_stock_barcode);

                if ($result_stock_barcode && $result_stock_barcode->num_rows > 0) {
                    $stock_data_barcode = $result_stock_barcode->fetch_assoc();
                    $transaction_data['current_ekor_barcode'] = (float) $stock_data_barcode['current_ekor_barcode'];
                    $transaction_data['current_kg_barcode'] = (float) $stock_data_barcode['current_kg_barcode'];
                    $transaction_data['tanggal_stock'] = $stock_data_barcode['tanggal_stock'];
                    $transaction_data['status_bahan'] = $stock_data_barcode['status_bahan'];
                } else {
                    $transaction_data['current_ekor_barcode'] = 0;
                    $transaction_data['current_kg_barcode'] = 0;
                    $transaction_data['tanggal_stock'] = null;
                    $transaction_data['status_bahan'] = null;
                }

                echo json_encode(['success' => true, 'data' => $transaction_data]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Barcode tidak ditemukan atau bukan transaksi masuk.']);
            }

            if ($conn) $conn->close();
            exit();
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
    $conn->close();
    exit();
}

// Handle POST requests for form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $raw_barcode_in = trim($input['barcode_in'] ?? '');
    $raw_barcode_out = trim($input['barcode_out'] ?? '');

    // Validasi maksimal 4 digit barcode di BE
    if (!empty($raw_barcode_in) && strlen($raw_barcode_in) > 4) {
        echo json_encode(['success' => false, 'message' => 'Barcode In maksimal 4 digit.']);
        $conn->close();
        exit();
    }

    if (!empty($raw_barcode_out) && strlen($raw_barcode_out) > 4) {
        echo json_encode(['success' => false, 'message' => 'Barcode Out maksimal 4 digit.']);
        $conn->close();
        exit();
    }

    $date = $conn->real_escape_string($input['date'] ?? '');
    $from_location = $conn->real_escape_string($input['from_location'] ?? '');
    $to_location = $conn->real_escape_string($input['to_location'] ?? '');
    $item = $conn->real_escape_string($input['item'] ?? '');

    $subitem = !empty($input['subitem']) ? "'" . $conn->real_escape_string($input['subitem']) . "'" : "NULL";

    $ekor_pack = (int)($input['ekor_pack'] ?? 0);
    $quantity_kg = (float)($input['quantity_kg'] ?? 0.0);

    $keterangan = !empty($input['keterangan']) ? "'" . $conn->real_escape_string($input['keterangan']) . "'" : "NULL";

    $user = $conn->real_escape_string($input['user'] ?? 'Unknown User');
    $transaction_type_frontend = $input['transaction_type'] ?? '';

    $storage = !empty($input['storage']) ? "'" . $conn->real_escape_string($input['storage']) . "'" : "NULL";

    $barcode_in = !empty($input['barcode_in']) ? "'" . $conn->real_escape_string(ltrim($input['barcode_in'], '0')) . "'" : "NULL";
    $barcode_out = !empty($input['barcode_out']) ? "'" . $conn->real_escape_string(ltrim($input['barcode_out'], '0')) . "'" : "NULL";

    $id_in = !empty($input['id_in']) ? "'" . $conn->real_escape_string($input['id_in']) . "'" : "NULL";

    if (empty($date) || empty($from_location) || empty($to_location) || empty($item)) {
        echo json_encode(['success' => false, 'message' => 'Data wajib (Tanggal, Dari, Tujuan, Item) tidak lengkap.']);
        $conn->close();
        exit();
    }

    $item_to_store_value = $item;
    $item_to_store_sql = "'$item_to_store_value'";
    if ($subitem !== "NULL") {
        $raw_subitem = $conn->real_escape_string($input['subitem'] ?? '');
        $item_to_store_sql = "'" . $item_to_store_value . ' : ' . $raw_subitem . "'";
    }

    $conn->begin_transaction();

    try {
        $transaction_type_db = '';
        $ekor_pack_signed = 0;
        $quantity_kg_signed = 0.0;
        $storage_location_db = '';
        $barcode_to_use = '';
        $insert_id = null;

        $barcode_to_use_sql = "NULL";
        $storage_location_db_sql = "NULL";

        if ($transaction_type_frontend === 'in') {
            $transaction_type_db = 'in';
            $ekor_pack_signed = abs($ekor_pack);
            $quantity_kg_signed = abs($quantity_kg);
            $barcode_to_use_sql = $barcode_in;
            $storage_location_db_sql = !empty($to_location) ? "'$to_location'" : "NULL";

            // ## Langkah 1: Cek duplikasi barcode jika barcode diisi ##
            if ($barcode_to_use_sql !== "NULL") {
                $check_sql = "SELECT COUNT(*) as count FROM transactions WHERE barcode = $barcode_to_use_sql AND deleted_at is null and date = '$date'";

                $result = $conn->query($check_sql);
                if (!$result) {
                    throw new mysqli_sql_exception("Error saat memeriksa barcode: " . $conn->error);
                }

                $row = $result->fetch_assoc();
                $barcode_count = (int)$row['count'];

                // ## Langkah 2: Jika barcode ditemukan, hentikan proses dan berikan pesan error ##
                if ($barcode_count > 0) {
                    $clean_barcode = trim($barcode_to_use_sql, "'");
                    throw new Exception("Barcode '$clean_barcode' sudah pernah digunakan pada tanggal ini ($date).");
                }
            }

            // ## Langkah 3: Jika barcode aman (tidak duplikat), lanjutkan proses INSERT ##
            $sql = "INSERT INTO transactions (date, from_location, to_location, storage, item, ekor_pack, quantity_kg, barcode, id_in, transaction_type, keterangan, user) VALUES (
                '$date',
                '$from_location',
                '$to_location',
                $storage_location_db_sql,
                $item_to_store_sql,
                $ekor_pack_signed,
                $quantity_kg_signed,
                $barcode_to_use_sql,
                $id_in,
                '$transaction_type_db',
                $keterangan,
                '$user'
            )";

            if (!$conn->query($sql)) {
                throw new mysqli_sql_exception("Error inserting 'in' transaction: " . $conn->error);
            }
            $insert_id = $conn->insert_id;
        } elseif ($transaction_type_frontend === 'out') {
            $transaction_type_db = 'out';
            $ekor_pack_signed = -abs($ekor_pack);
            $quantity_kg_signed = -abs($quantity_kg);
            $barcode_to_use_sql = $barcode_out;
            $storage_location_db_sql = !empty($from_location) ? "'$from_location'" : "NULL";

            $sql = "INSERT INTO transactions (date, from_location, to_location, storage, item, ekor_pack, quantity_kg, barcode, id_in, transaction_type, keterangan, user) VALUES (
                '$date',
                '$from_location',
                '$to_location',
                $storage_location_db_sql,
                $item_to_store_sql,
                $ekor_pack_signed,
                $quantity_kg_signed,
                $barcode_to_use_sql,
                $id_in,
                '$transaction_type_db',
                $keterangan,
                '$user'
            )";

            if (!$conn->query($sql)) {
                throw new mysqli_sql_exception("Error inserting 'out' transaction: " . $conn->error);
            }
            $insert_id = $conn->insert_id;
        } elseif ($transaction_type_frontend === 'transfer') {
            // Transfer-out transaction
            $transaction_type_out = 'transfer-out';
            $ekor_pack_out = -abs($ekor_pack);
            $quantity_kg_out = -abs($quantity_kg);
            $storage_location_out_sql = !empty($from_location) ? "'" . $from_location . "'" : "NULL";
            $barcode_to_use_out_sql = $barcode_out;

            $sql_out = "INSERT INTO transactions (date, from_location, to_location, storage, item, ekor_pack, quantity_kg, barcode, id_in, transaction_type, keterangan, user) VALUES (
                '$date',
                '$from_location',
                '$to_location',
                $storage_location_out_sql,
                $item_to_store_sql,
                $ekor_pack_out,
                $quantity_kg_out,
                $barcode_to_use_out_sql,
                $id_in,
                '$transaction_type_out',
                $keterangan,
                '$user'
            )";

            if (!$conn->query($sql_out)) {
                throw new mysqli_sql_exception("Error in transfer-out: " . $conn->error);
            }
            $insert_id_out = $conn->insert_id;

            // Transfer-in transaction
            $transaction_type_in = 'transfer-in';
            $ekor_pack_in = abs($ekor_pack);
            $quantity_kg_in = abs($quantity_kg);
            $storage_location_in_sql = !empty($to_location) ? "'" . $to_location . "'" : "NULL";
            $barcode_to_use_in_sql = $barcode_out;

            $sql_in = "INSERT INTO transactions (date, from_location, to_location, storage, item, ekor_pack, quantity_kg, barcode, id_in, transaction_type, keterangan, user) VALUES (
                '$date',
                '$from_location',
                '$to_location',
                $storage_location_in_sql,
                $item_to_store_sql,
                $ekor_pack_in,
                $quantity_kg_in,
                $barcode_to_use_in_sql,
                $id_in,
                '$transaction_type_in',
                $keterangan,
                '$user'
            )";

            if (!$conn->query($sql_in)) {
                throw new mysqli_sql_exception("Error in transfer-in: " . $conn->error);
            }
            $insert_id_in = $conn->insert_id;
            $insert_id = ['out' => $insert_id_out, 'in' => $insert_id_in];
        } elseif ($transaction_type_frontend === 'other') {
            $transaction_type_db = 'other';
            $ekor_pack_signed = abs($ekor_pack);
            $quantity_kg_signed = abs($quantity_kg);
            $storage_location_db_sql = "NULL";
            $barcode_to_use_sql = "NULL";

            if (!empty($input['barcode_in'])) {
                $barcode_to_use_sql = "'" . $conn->real_escape_string($input['barcode_in']) . "'";
            } elseif (!empty($input['barcode_out'])) {
                $barcode_to_use_sql = "'" . $conn->real_escape_string($input['barcode_out']) . "'";
            }

            $sql = "INSERT INTO transactions (date, from_location, to_location, storage, item, ekor_pack, quantity_kg, barcode, id_in, transaction_type, keterangan, user) VALUES (
                '$date',
                '$from_location',
                '$to_location',
                $storage_location_db_sql,
                $item_to_store_sql,
                $ekor_pack_signed,
                $quantity_kg_signed,
                $barcode_to_use_sql,
                $id_in,
                '$transaction_type_db',
                $keterangan,
                '$user'
            )";

            if (!$conn->query($sql)) {
                throw new mysqli_sql_exception("Error inserting 'other' transaction: " . $conn->error);
            }
            $insert_id = $conn->insert_id;
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Tipe transaksi tidak valid.']);
            $conn->close();
            exit();
        }

        // Commit transaksi utama
        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Transaksi berhasil disimpan.']);
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        error_log("Database error during transaction: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan transaksi: ' . $e->getMessage()]);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("General error during transaction: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
    } finally {
        if ($conn && !$conn->connect_error) {
            $conn->close();
        }
    }
}
