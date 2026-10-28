<?php
session_name('chiller_app');
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../controller/db/config.php';
$logHandlerUrl = 'http://localhost/chiller/apps/controller/handler/log_activity_handler.php';
$currentUser = $_SESSION['user_nama'] ?? 'Unknown User';
$currentUserId = $_SESSION['user_id'] ?? 0;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$requestData = json_decode(file_get_contents('php://input'), true);
$action = $requestData['action'] ?? '';

try {
    switch ($action) {
        case 'search_barcode':
            $barcode_input = $requestData['barcode'] ?? '';
            $barcode_cleaned = $conn->real_escape_string(ltrim($barcode_input, '0')); // Use a different variable to avoid confusion
            $storage = $conn->real_escape_string($requestData['storage'] ?? '');

            if (empty($barcode_cleaned) || empty($storage)) {
                throw new InvalidArgumentException('Barcode dan lokasi penyimpanan (storage) wajib diisi.');
            }

            // Corrected query for ONLY_FULL_GROUP_BY mode and column referencing
            $query_local =
                "SELECT
                    MAX(id_final) AS id,
                    id_in_transaction_final,
                    MAX(item_final) AS item,
                    MAX(storage_final) AS `storage`,
                    SUM(ekor_final) AS ekor_stock_current,
                    SUM(berat_final) AS berat_stock_current,
                    MAX(created_at_final) AS created_at,
                    MAX(user_final) AS `user`,
                    barcode_final
                FROM (
                    -- First part of UNION ALL (from subquery C)
                    SELECT
                        id AS id_final,
                        id AS id_in_transaction_final, -- original id is id_in for this part
                        item AS item_final,
                        barcode AS barcode_final,
                        `storage` AS storage_final,
                        ekor AS ekor_final,
                        berat AS berat_final,
                        created_at AS created_at_final,
                        `user` AS user_final
                    FROM (
                        SELECT
                            id, item, barcode, `storage`,
                            SUM(ekor_pack) AS ekor,
                            SUM(quantity_kg) AS berat,
                            created_at, `user`
                        FROM transactions
                        WHERE quantity_kg > 0
                            AND barcode = '$barcode_cleaned'
                            AND deleted_at IS NULL
                            AND transaction_type IN ('in', 'transfer-in', 'adjustment-new', 'adjustment')
                            AND storage LIKE '$storage'
                        GROUP BY id, item, barcode, `storage`, created_at, `user`
                        ORDER BY id DESC
                        LIMIT 1
                    ) AS C_inner

                    UNION ALL

                    -- Second part of UNION ALL (from subquery A_inner LEFT JOIN subquery B_inner)
                    SELECT
                        A_inner.id AS id_final,      -- Corrected: Use A_inner.id
                        A_inner.id AS id_in_transaction_final, -- Corrected: Use A_inner.id
                        A_inner.item AS item_final,   -- Corrected: Use A_inner.item
                        A_inner.barcode AS barcode_final, -- Corrected: Use A_inner.barcode
                        A_inner.`storage` AS storage_final, -- Corrected: Use A_inner.`storage`
                        COALESCE(B_inner.ekor, 0) AS ekor_final, -- Corrected: Use B_inner.ekor
                        COALESCE(B_inner.berat, 0) AS berat_final, -- Corrected: Use B_inner.berat
                        '' AS created_at_final,
                        '' AS user_final
                    FROM (
                        SELECT
                            id, item, barcode, `storage`, SUM(ekor_pack) AS ekor, SUM(quantity_kg) AS berat
                        FROM transactions
                        WHERE quantity_kg > 0
                            AND barcode = '$barcode_cleaned'
                            AND transaction_type IN ('in', 'transfer-in', 'adjustment-new', 'adjustment')
                            AND deleted_at IS NULL
                            AND storage LIKE '$storage'
                        GROUP BY id, item, barcode, `storage`
                        ORDER BY id DESC
                        LIMIT 1
                    ) AS A_inner
                    LEFT JOIN (
                        SELECT
                            id_in, SUM(ekor_pack) AS ekor, SUM(quantity_kg) AS berat
                        FROM transactions
                        WHERE 1=1
                            AND barcode = '$barcode_cleaned'
                            AND id_in IS NOT NULL
                            AND deleted_at IS NULL
                        GROUP BY id_in
                    ) AS B_inner
                    ON A_inner.id = B_inner.id_in
                ) AS UnionResult
                GROUP BY id_in_transaction_final, barcode_final
            ";

            // $query_local = 
            //     "SELECT 
            //         SUM(ekor) AS current_ekor_barcode, 
            //         SUM(berat) AS current_kg_barcode
            //     FROM ( 
            //         SELECT * FROM (
            //             SELECT id, SUM(ekor_pack) AS ekor, SUM(quantity_kg) AS berat 
            //             FROM transactions
            //             WHERE quantity_kg>0
            //                 AND barcode = '$escaped_barcode'
            //                 AND transaction_type IN ('in', 'gabung-in', 'transfer-in', 'adjustment-new', 'adjustment')
            //                 AND deleted_at IS NULL
            //                 AND quantity_kg>0
            //             GROUP BY id
            //             ORDER BY id DESC
            //             LIMIT 1
            //         ) AS C
            //         UNION ALL
            //         SELECT B.id_in, B.ekor, B.berat
            //         FROM (
            //             SELECT id, SUM(ekor_pack) AS ekor, SUM(quantity_kg) AS berat 
            //             FROM transactions
            //             WHERE 1=1
            //                 AND barcode = '$escaped_barcode'
            //                 AND transaction_type IN ('in', 'gabung-in', 'transfer-in', 'adjustment-new', 'adjustment')
            //                 AND deleted_at IS NULL
            //                 AND quantity_kg>0
            //             GROUP BY id
            //             ORDER BY id DESC
            //             LIMIT 1
            //         ) AS A
            //         LEFT JOIN (
            //             SELECT id_in, SUM(ekor_pack) AS ekor, SUM(quantity_kg) AS berat 
            //             FROM transactions
            //             WHERE 1=1
            //                 AND barcode = '$escaped_barcode'
            //                 AND id_in IS NOT NULL
            //                 AND deleted_at IS NULL
            //             GROUP BY id_in
            //         ) AS B
            //         ON A.id = B.id_in
            //     ) AS B             ";

            $result_local_barcode = $conn->query($query_local);

            if (!$result_local_barcode) {
                error_log("Database Error: Query failed for search_barcode (local stock) - " . $conn->error);
                throw new Exception("Gagal menjalankan query pencarian barcode lokal: " . $conn->error);
            }

            $data_local_barcode = $result_local_barcode->fetch_assoc();

            $stok_ekor_global = 0.0;
            $stok_berat_global = 0.0;
            $item_name_for_global_stock = '';

            if ($data_local_barcode) {
                $fullItem = $data_local_barcode['item'];
                $item_name_for_global_stock = $conn->real_escape_string($fullItem);

                $query_global = "
                    SELECT
                        SUM(ekor_pack) AS total_ekor_item_global,
                        SUM(quantity_kg) AS total_berat_item_global
                    FROM transactions
                    WHERE item = '$item_name_for_global_stock' AND storage = '$storage' AND deleted_at IS NULL
                ";

                $result_global_item_stock = $conn->query($query_global);

                if (!$result_global_item_stock) {
                    error_log("Database Error: Query failed for search_barcode (global stock) - " . $conn->error);
                    throw new Exception("Gagal menjalankan query stok item global: " . $conn->error);
                }

                $data_global_item_stock = $result_global_item_stock->fetch_assoc();
                $stok_ekor_global = (float)($data_global_item_stock['total_ekor_item_global'] ?? 0);
                $stok_berat_global = (float)($data_global_item_stock['total_berat_item_global'] ?? 0);
            }

            if (!$data_local_barcode) {
                $response = [
                    'success' => false,
                    'message' => 'Barcode tidak ditemukan di lokasi penyimpanan ini.',
                    'data' => [
                        'id' => null,
                        'id_in' => null,
                        'barcode' => $barcode_cleaned, // Use the cleaned barcode input if no data found
                        'item' => '',
                        'subitem' => null,
                        'storage' => $storage,
                        'created_at' => null,
                        'user_in' => null,
                        'ekor_stock_current' => 0.0,
                        'berat_stock_current' => 0.0,
                        'stok_ekor_global' => $stok_ekor_global,
                        'stok_berat_global' => $stok_berat_global,
                    ]
                ];
            } else {
                $fullItem = $data_local_barcode['item'];
                $item = $fullItem;
                $subitem = null;

                if (strpos($fullItem, ' : ') !== false) {
                    list($item, $subitem) = explode(' : ', $fullItem, 2);
                    $item = trim($item);
                    $subitem = trim($subitem);
                }

                $response = [
                    'success' => true,
                    'message' => 'Data barcode berhasil dimuat.',
                    'data' => [
                        'id' => $data_local_barcode['id'],
                        'id_in' => $data_local_barcode['id_in_transaction_final'],
                        'barcode' => $data_local_barcode['barcode_final'] ?? null, // Changed from 'barcode' to 'barcode_final' as per your SQL alias
                        'item' => $item,
                        'subitem' => $subitem,
                        'storage' => $data_local_barcode['storage'],
                        'created_at' => $data_local_barcode['created_at'],
                        'user_in' => $data_local_barcode['user'],
                        'ekor_stock_current' => (float)$data_local_barcode['ekor_stock_current'],
                        'berat_stock_current' => (float)$data_local_barcode['berat_stock_current'],
                        'stok_ekor_global' => $stok_ekor_global,
                        'stok_berat_global' => $stok_berat_global,
                    ]
                ];
            }
            break;

        case 'save_merge_transaction':
            // Gunakan array_map untuk membersihkan dan memvalidasi data dengan lebih efisien
            $requestData = json_decode(file_get_contents('php://input'), true);

            $date = $conn->real_escape_string($requestData['date'] ?? date('Y-m-d'));
            $storage = $conn->real_escape_string($requestData['storage'] ?? '');
            $is_new_barcode_to = (bool)($requestData['is_new_barcode_to'] ?? false);
            $barcode_to_data = $requestData['barcode_to_data'] ?? [];
            $from_barcodes_data = $requestData['from_barcodes'] ?? [];
            $user = $conn->real_escape_string($requestData['user'] ?? $_SESSION['user_nama'] ?? 'Unknown User');
            $currentUserId = $_SESSION['user_id'] ?? null; // Sesuaikan dengan cara Anda mendapatkan ID user

            // --- Validasi Awal ---
            if (empty($from_barcodes_data)) {
                throw new InvalidArgumentException('Minimal satu barcode asal harus ditambahkan.');
            }
            if (empty($barcode_to_data['barcode'])) {
                throw new InvalidArgumentException('Barcode tujuan wajib diisi.');
            }
            if (empty($barcode_to_data['item'])) {
                throw new InvalidArgumentException('Informasi item untuk barcode tujuan tidak lengkap.');
            }
            if (empty($storage)) {
                throw new InvalidArgumentException('Lokasi penyimpanan wajib diisi.');
            }

            // Mulai transaksi database
            $conn->begin_transaction();
            $inserted_ids = [];

            try {
                // 1. Proses Transaksi OUT (Pengurangan dari setiap barcode_from)
                foreach ($from_barcodes_data as $from_barcode) {
                    $barcode_from = $conn->real_escape_string(ltrim($from_barcode['barcode'] ?? '', '0'));
                    $item_from = $conn->real_escape_string($from_barcode['item'] ?? '');
                    $subitem_from = $conn->real_escape_string($from_barcode['subitem'] ?? '');
                    $id_in_from = $conn->real_escape_string($from_barcode['id_in'] ?? '');
                    $ekor_pack_transferred = (int)($from_barcode['ekor_pack_transferred'] ?? 0);
                    $quantity_kg_transferred = (float)($from_barcode['quantity_kg_transferred'] ?? 0.0);

                    // Validasi per barcode
                    if ($ekor_pack_transferred <= 0 || $quantity_kg_transferred <= 0) {
                        throw new InvalidArgumentException("Jumlah ekor atau berat untuk barcode '$barcode_from' tidak valid.");
                    }
                    if ($barcode_from === ltrim($barcode_to_data['barcode'], '0') && !$is_new_barcode_to) {
                        throw new InvalidArgumentException("Barcode asal '$barcode_from' tidak boleh sama dengan barcode tujuan.");
                    }

                    $transaction_type_out = 'gabung-out';
                    $keterangan_out = "Gabungan Keluar (dari $barcode_from ke {$barcode_to_data['barcode']})";
                    $from_location_out = $storage;
                    $to_location_out = $storage;
                    $full_item_from = $item_from . (empty($subitem_from) ? '' : ' : ' . $subitem_from);
                    $ekor_out_value = -$ekor_pack_transferred;
                    $quantity_kg_out_value = -$quantity_kg_transferred;
                    $id_in_for_out_transaction = empty($id_in_from) ? "NULL" : "'" . $id_in_from . "'";

                    $query_out = "
                INSERT INTO transactions (
                    date, from_location, to_location, storage, item, ekor_pack, quantity_kg,
                    transaction_type, keterangan, user, barcode, id_in
                ) VALUES (
                    '$date',
                    '$from_location_out',
                    '$to_location_out',
                    '$storage',
                    '$full_item_from',
                    $ekor_out_value,
                    $quantity_kg_out_value,
                    '$transaction_type_out',
                    '$keterangan_out',
                    '$user',
                    '$barcode_from',
                    $id_in_for_out_transaction
                )
            ";
                    if (!$conn->query($query_out)) {
                        throw new Exception("Execute statement failed (out for $barcode_from): " . $conn->error . " Query: " . $query_out);
                    }
                    $inserted_ids[] = $conn->insert_id;
                }

                // 2. Proses Transaksi IN (Penambahan ke barcode_to)
                // Hitung total ekor dan berat dari semua barcode sumber
                $total_ekor_in = array_sum(array_column($from_barcodes_data, 'ekor_pack_transferred'));
                $total_berat_in = array_sum(array_column($from_barcodes_data, 'quantity_kg_transferred'));

                $barcode_to = $conn->real_escape_string(ltrim($barcode_to_data['barcode'], '0'));
                $item_to = $conn->real_escape_string($barcode_to_data['item']);
                $subitem_to = $conn->real_escape_string($barcode_to_data['subitem'] ?? '');
                $id_in_to = $conn->real_escape_string($barcode_to_data['id_in'] ?? '');

                $transaction_type_in = $is_new_barcode_to ? 'gabung-new' : 'gabung-in';
                $keterangan_in = "Gabungan Masuk (ke $barcode_to dari " . count($from_barcodes_data) . " barcode asal)";
                $full_item_to = $item_to . (empty($subitem_to) ? '' : ' : ' . $subitem_to);
                $id_in_for_in_transaction = empty($id_in_to) ? "NULL" : "'" . $id_in_to . "'";

                $query_in = "
            INSERT INTO transactions (
                date, from_location, to_location, storage, item, ekor_pack, quantity_kg,
                transaction_type, keterangan, user, barcode, id_in
            ) VALUES (
                '$date',
                '$storage',
                '$storage',
                '$storage',
                '$full_item_to',
                $total_ekor_in,
                $total_berat_in,
                '$transaction_type_in',
                '$keterangan_in',
                '$user',
                '$barcode_to',
                $id_in_for_in_transaction
            )
        ";
                if (!$conn->query($query_in)) {
                    throw new Exception("Execute statement failed (in): " . $conn->error . " Query: " . $query_in);
                }
                $inserted_ids[] = $conn->insert_id;

                // Commit transaksi utama
                $conn->commit();
                $response = ['success' => true, 'message' => 'Transaksi gabungan berhasil disimpan.'];

                // --- Panggil Log Activity Handler setelah transaksi berhasil di-commit ---
                // Siapkan data log
                $log_data = [
                    'action_type' => 'CREATE',
                    'table_name' => 'transactions',
                    'record_id' => implode(',', $inserted_ids),
                    'data_old' => null,
                    'data_new' => $requestData,
                    'notes' => "Transaksi penggabungan berhasil dari " . count($from_barcodes_data) . " barcode asal ke '$barcode_to' oleh user '$user'.",
                    'id_user' => $currentUserId
                ];

                // Asumsi URL log handler sudah didefinisikan sebelumnya, contoh: $logHandlerUrl = 'http://api.log-service.com/log_activity';
                if (isset($logHandlerUrl)) {
                    $ch = curl_init($logHandlerUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($log_data));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);
                    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
                    curl_exec($ch);
                    curl_close($ch);
                }
            } catch (Exception $e) {
                $conn->rollback();
                error_log("Transaction failed: " . $e->getMessage());
                $response = ['success' => false, 'message' => 'Gagal menyimpan transaksi gabungan: ' . $e->getMessage()];
            }
            break;
        default:
            $response = ['success' => false, 'message' => 'Aksi tidak dikenal.'];
            break;
    }
} catch (InvalidArgumentException $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Terjadi kesalahan server: ' . $e->getMessage()];
}

echo json_encode($response);
$conn->close();
