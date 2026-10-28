<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
session_start();

require '../../controller/db/config.php';

if ($conn->connect_error) {
    error_log("Database Connection Failed: " . $conn->connect_error);
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal: ' . $conn->connect_error]);
    exit();
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    switch ($action) {
        case 'getLocations':
            $result = $conn->query("SELECT id, lokasi FROM locations");
            $locations = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $locations[] = $row;
                }
            }
            echo json_encode($locations);
            break;

        case 'getAllItems':
            $result = $conn->query("SELECT id, item FROM items WHERE isActive = 1 ORDER BY item");
            $items = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $row['has_subitem'] = true;
                    $items[] = $row;
                }
            }
            echo json_encode($items);
            break;

        case 'getAllSubitems':
            $result = $conn->query("SELECT id, subitem FROM subitems ORDER BY subitem ASC");
            $subitems = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $trimmedSubitem = trim($row['subitem']);
                    if (!empty($trimmedSubitem)) {
                        $isDuplicate = false;
                        foreach ($subitems as $existingSubitem) {
                            if ($existingSubitem['subitem'] === $trimmedSubitem) {
                                $isDuplicate = true;
                                break;
                            }
                        }
                        if (!$isDuplicate) {
                            $subitems[] = ['id' => $row['id'], 'subitem' => $trimmedSubitem];
                        }
                    }
                }
            }
            echo json_encode($subitems);
            break;

        case 'getItemsWithStock':
            $location = $conn->real_escape_string($_GET['location'] ?? '');
            $sql_stock = "SELECT
                                t.item,
                                COALESCE(SUM(CASE WHEN t.transaction_type IN ('in', 'transfer-in') THEN t.ekor_pack ELSE -t.ekor_pack END), 0) AS ekor_pack,
                                COALESCE(SUM(CASE WHEN t.transaction_type IN ('in', 'transfer-in') THEN t.quantity_kg ELSE -t.quantity_kg END), 0) AS quantity_kg
                            FROM transactions t
                            WHERE t.storage = '$location' AND t.deleted_at IS NULL
                            GROUP BY t.item
                            HAVING COALESCE(SUM(CASE WHEN t.transaction_type IN ('in', 'transfer-in') THEN t.ekor_pack ELSE -t.ekor_pack END), 0) != 0 OR COALESCE(SUM(CASE WHEN t.transaction_type IN ('in', 'transfer-in') THEN t.quantity_kg ELSE -t.quantity_kg END), 0) != 0
                            ORDER BY t.item";

            $result_stock = $conn->query($sql_stock);
            $itemsWithStock = [];
            if ($result_stock) {
                while ($row = $result_stock->fetch_assoc()) {
                    $itemsWithStock[] = [
                        'item' => $row['item'],
                        'ekor_pack' => (int)$row['ekor_pack'],
                        'quantity_kg' => (float)$row['quantity_kg']
                    ];
                }
            }
            echo json_encode($itemsWithStock);
            break;



        case 'getTransactionByBarcodeIn':
            $barcode = $_GET['barcode_in'] ?? '';

            if (empty($barcode)) {
                echo json_encode(['success' => false, 'message' => 'Barcode tidak boleh kosong.']);
                if ($conn) $conn->close();
                exit();
            }

            $escaped_barcode = $conn->real_escape_string($barcode);

            $sql_transaction =
                "SELECT id, item, storage, ekor_pack, quantity_kg, barcode, created_at
                        FROM transactions
                        WHERE barcode = '$escaped_barcode'
                        AND (transaction_type = 'in' OR transaction_type = 'transfer-in')
                        AND deleted_at IS NULL
                        ORDER BY created_at DESC
                        LIMIT 1";

            $result_transaction = $conn->query($sql_transaction);

            if ($result_transaction && $result_transaction->num_rows > 0) {
                $transaction_data = $result_transaction->fetch_assoc();

                $escaped_item = $conn->real_escape_string($transaction_data['item']);
                $escaped_storage = $conn->real_escape_string($transaction_data['storage']);

                $sql_stock_item_storage = "SELECT
                                        SUM(ekor_pack) AS current_ekor_stock,
                                        SUM(quantity_kg) AS current_kg_stock
                                    FROM
                                        transactions
                                    WHERE
                                        item = '$escaped_item'
                                        AND storage = '$escaped_storage'
                                        AND deleted_at IS NULL;";

                $result_stock_item_storage = $conn->query($sql_stock_item_storage);

                if ($result_stock_item_storage && $result_stock_item_storage->num_rows > 0) {
                    $stock_data_item_storage = $result_stock_item_storage->fetch_assoc();
                    $transaction_data['current_ekor_stock'] = (float) $stock_data_item_storage['current_ekor_stock'];
                    $transaction_data['current_kg_stock'] = (float) $stock_data_item_storage['current_kg_stock'];
                } else {
                    $transaction_data['current_ekor_stock'] = 0;
                    $transaction_data['current_kg_stock'] = 0;
                }

                // $sql_stock_barcode = "SELECT
                //                 SUM(ekor_pack) AS current_ekor_barcode,
                //                 SUM(quantity_kg) AS current_kg_barcode
                //               FROM
                //                 transactions
                //               WHERE
                //                 barcode = '$escaped_barcode'
                //                 AND deleted_at IS NULL;";

                $sql_stock_barcode =
                    "SELECT 
                    SUM(ekor) AS current_ekor_barcode, 
                    SUM(berat) AS current_kg_barcode
                FROM ( 
                    SELECT * FROM (
                        SELECT id, SUM(ekor_pack) AS ekor, SUM(quantity_kg) AS berat 
                        FROM transactions
                        WHERE quantity_kg>0
                            AND barcode = '$escaped_barcode'
                            AND deleted_at IS NULL
                        GROUP BY id
                        ORDER BY id DESC
                        LIMIT 1
                    ) AS C
                    UNION ALL
                    SELECT B.id_in, B.ekor, B.berat
                    FROM (
                        SELECT id, SUM(ekor_pack) AS ekor, SUM(quantity_kg) AS berat 
                        FROM transactions
                        WHERE quantity_kg>0
                            AND barcode = '$escaped_barcode'
                            AND deleted_at IS NULL
                        GROUP BY id
                        ORDER BY id DESC
                        LIMIT 1
                    ) AS A
                    LEFT JOIN (
                        SELECT id_in, SUM(ekor_pack) AS ekor, SUM(quantity_kg) AS berat 
                        FROM transactions
                        WHERE quantity_kg<0
                            AND barcode = '$escaped_barcode'
                            AND deleted_at IS NULL
                        GROUP BY id_in
                    ) AS B
                    ON A.id = B.id_in
                ) AS B ;";

                $result_stock_barcode = $conn->query($sql_stock_barcode);

                if ($result_stock_barcode && $result_stock_barcode->num_rows > 0) {
                    $stock_data_barcode = $result_stock_barcode->fetch_assoc();
                    $transaction_data['current_ekor_barcode'] = (float) $stock_data_barcode['current_ekor_barcode'];
                    $transaction_data['current_kg_barcode'] = (float) $stock_data_barcode['current_kg_barcode'];
                } else {
                    $transaction_data['current_ekor_barcode'] = 0;
                    $transaction_data['current_kg_barcode'] = 0;
                }

                echo json_encode(['success' => true, 'data' => $transaction_data]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Barcode tidak ditemukan atau bukan transaksi masuk.']);
            }

            if ($conn) $conn->close();
            exit();
            break;


        default:
            echo json_encode(['error' => 'Aksi tidak valid']);
            break;
    }
    $conn->close();
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Escape all input to prevent basic SQL injection (though prepared statements are much safer)
    $date = $conn->real_escape_string($input['date'] ?? '');
    $from_location = $conn->real_escape_string($input['from_location'] ?? '');
    $to_location = $conn->real_escape_string($input['to_location'] ?? '');
    $item = $conn->real_escape_string($input['item'] ?? '');
    $subitem = $conn->real_escape_string($input['subitem'] ?? '');
    $ekor_pack = (int)($input['ekor_pack'] ?? 0); // Cast to int for safety
    $quantity_kg = (float)($input['quantity_kg'] ?? 0.0); // Cast to float for safety
    $keterangan = $conn->real_escape_string($input['keterangan'] ?? '');
    $user = $conn->real_escape_string($input['user'] ?? 'Unknown User');
    $transaction_type_frontend = $input['transaction_type'] ?? '';
    $storage = $conn->real_escape_string($input['storage'] ?? '');
    $barcode_in = $conn->real_escape_string($input['barcode_in'] ?? '');
    $barcode_out = $conn->real_escape_string($input['barcode_out'] ?? '');
    $id_in = $conn->real_escape_string($input['id_in'] ?? '');

    if (empty($date) || empty($from_location) || empty($to_location) || empty($item)) {
        echo json_encode(['success' => false, 'message' => 'Data wajib (Tanggal, Dari, Tujuan, Item) tidak lengkap.']);
        $conn->close();
        exit();
    }

    $item_to_store = $item;
    if (!empty($subitem)) {
        $item_to_store = $item . ' : ' . $subitem;
    }

    $conn->begin_transaction();

    try {
        $transaction_type_db = '';
        $ekor_pack_signed = 0;
        $quantity_kg_signed = 0.0;
        $storage_location_db = '';
        $barcode_to_use = '';
        $id_in_to_use = '';

        if ($transaction_type_frontend === 'in') {
            $transaction_type_db = 'in';
            $ekor_pack_signed = abs($ekor_pack);
            $quantity_kg_signed = abs($quantity_kg);
            $barcode_to_use = $barcode_in;
            $storage_location_db = $to_location;

            $sql = "INSERT INTO transactions (date, from_location, to_location, storage, item, ekor_pack, quantity_kg, barcode, id_in, transaction_type, keterangan, user) VALUES (
                '$date', 
                '$from_location', 
                '$to_location', 
                '$storage_location_db', 
                '$item_to_store', 
                $ekor_pack_signed, 
                $quantity_kg_signed, 
                '$barcode_to_use', 
                '$id_in_to_use', 
                '$transaction_type_db', 
                '$keterangan', 
                '$user'
            )";

            if (!$conn->query($sql)) {
                throw new mysqli_sql_exception("Error inserting 'in' transaction: " . $conn->error);
            }
        } elseif ($transaction_type_frontend === 'out') {
            $transaction_type_db = 'out';
            $ekor_pack_signed = -abs($ekor_pack);
            $quantity_kg_signed = -abs($quantity_kg);
            $barcode_to_use = $barcode_out;
            $id_in_to_use = $id_in;
            $storage_location_db = $from_location;

            $sql =
                "INSERT INTO transactions (date, from_location, to_location, storage, item, ekor_pack, quantity_kg, barcode, id_in, transaction_type, keterangan, user) VALUES (
                '$date', 
                '$from_location', 
                '$to_location', 
                '$storage_location_db', 
                '$item_to_store', 
                $ekor_pack_signed, 
                $quantity_kg_signed, 
                '$barcode_to_use', 
                '$id_in_to_use', 
                '$transaction_type_db', 
                '$keterangan', 
                '$user'
            )";

            // $sql = 
            //     "INSERT INTO transactions 
            //     (date, from_location, to_location, storage, item, ekor_pack, quantity_kg, ";

            // $sql .= "  barcode,";
            // $sql .= " id_in, transaction_type, keterangan, user) 
            //     VALUES (
            //     '$date', 
            //     '$from_location', 
            //     '$to_location', 
            //     '$storage_location_db', 
            //     '$item_to_store', 
            //     $ekor_pack_signed, 
            //     $quantity_kg_signed, 
            //     '$barcode_to_use', 
            //     '$id_in_to_use', 
            //     '$transaction_type_db', 
            //     '$keterangan', 
            //     '$user'
            // )";

            if (!$conn->query($sql)) {
                throw new mysqli_sql_exception("Error inserting 'out' transaction: " . $conn->error);
            }
        } elseif ($transaction_type_frontend === 'transfer') {
            // Transfer-out transaction
            $transaction_type_out = 'transfer-out';
            $ekor_pack_out = -abs($ekor_pack);
            $quantity_kg_out = -abs($quantity_kg);
            $storage_location_out = $from_location; // The stock is leaving this location
            $barcode_to_use_out = $barcode_out; // Use barcode_out for the outgoing leg
            $id_in_to_use_out = $id_in; // Pass id_in if it's relevant to the item being moved out

            $sql_out = "INSERT INTO transactions (date, from_location, to_location, storage, item, ekor_pack, quantity_kg, id_in, transaction_type, keterangan, user) VALUES (
                '$date', 
                '$from_location', 
                '$to_location', 
                '$storage_location_out', 
                '$item_to_store', 
                $ekor_pack_out, 
                $quantity_kg_out, 
                '$id_in_to_use_out', 
                '$transaction_type_out', 
                '$keterangan', 
                '$user'
            )";

            if (!$conn->query($sql_out)) {
                throw new mysqli_sql_exception("Error in transfer-out: " . $conn->error);
            }

            // Transfer-in transaction
            $transaction_type_in = 'transfer-in';
            $ekor_pack_in = abs($ekor_pack);
            $quantity_kg_in = abs($quantity_kg);
            $storage_location_in = $to_location; // The stock is arriving at this location
            $barcode_to_use_in = $barcode_out; // Assuming the same barcode is used for the incoming leg of the transfer
            $id_in_to_use_in = $id_in; // Pass id_in if it's relevant to the item being moved in

            $sql_in = "INSERT INTO transactions (date, from_location, to_location, storage, item, ekor_pack, quantity_kg, barcode, id_in, transaction_type, keterangan, user) VALUES (
                '$date', 
                '$from_location', 
                '$to_location', 
                '$storage_location_in', 
                '$item_to_store', 
                $ekor_pack_in, 
                $quantity_kg_in, 
                '$barcode_to_use_in', 
                '$id_in_to_use_in', 
                '$transaction_type_in', 
                '$keterangan', 
                '$user'
            )";

            if (!$conn->query($sql_in)) {
                throw new mysqli_sql_exception("Error in transfer-in: " . $conn->error);
            }
        } elseif ($transaction_type_frontend === 'other') {
            $transaction_type_db = 'other';
            $ekor_pack_signed = abs($ekor_pack);
            $quantity_kg_signed = abs($quantity_kg);
            $storage_location_db = ''; // Storage column empty for 'other'
            $barcode_to_use = !empty($barcode_in) ? $barcode_in : $barcode_out; // Use either barcode if available
            $id_in_to_use = $id_in; // Pass id_in if relevant

            $sql = "INSERT INTO transactions (date, from_location, to_location, storage, item, ekor_pack, quantity_kg, barcode, id_in, transaction_type, keterangan, user) VALUES (
                '$date', 
                '$from_location', 
                '$to_location', 
                '$storage_location_db', 
                '$item_to_store', 
                $ekor_pack_signed, 
                $quantity_kg_signed, 
                '$barcode_to_use', 
                '$id_in_to_use', 
                '$transaction_type_db', 
                '$keterangan', 
                '$user'
            )";

            if (!$conn->query($sql)) {
                throw new mysqli_sql_exception("Error inserting 'other' transaction: " . $conn->error);
            }
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Tipe transaksi tidak valid.']);
            $conn->close();
            exit();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Transaksi berhasil disimpan.']);
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        error_log("Database error during transaction: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan transaksi: ' . $e->getMessage()]);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("General error during transaction: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan umum: ' . $e->getMessage()]);
    } finally {
        if ($conn && !$conn->connect_error) {
            $conn->close();
        }
    }
}
