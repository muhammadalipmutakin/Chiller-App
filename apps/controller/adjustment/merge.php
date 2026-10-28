<?php
session_name('chiller_app');
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../controller/db/config.php';

$currentUser = $_SESSION['user_nama'] ?? 'Unknown User';
$currentUserId = $_SESSION['user_id'] ?? 0;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$requestData = json_decode(file_get_contents('php://input'), true);
$action = $requestData['action'] ?? '';

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    switch ($action) {
        case 'save_merge_transaction':
            $date = $conn->real_escape_string($requestData['date'] ?? date('Y-m-d'));
            $storage = $conn->real_escape_string($requestData['storage'] ?? '');
            $adjustment_type = $conn->real_escape_string($requestData['adjustment_type'] ?? '');
            $is_new_barcode_to = (bool)($requestData['is_new_barcode_to'] ?? false);
            $barcode_to_data = $requestData['barcode_to_data'] ?? [];
            $from_barcodes_data = $requestData['from_barcodes'] ?? [];
            $user = $conn->real_escape_string($requestData['user'] ?? $_SESSION['user_nama'] ?? 'Unknown User');

            // Validasi awal
            if (empty($from_barcodes_data)) {
                throw new InvalidArgumentException('Minimal satu barcode asal harus ditambahkan.');
            }
            if (empty($barcode_to_data['barcode'])) {
                throw new InvalidArgumentException('Barcode tujuan wajib diisi.');
            }
            if (empty($storage)) {
                throw new InvalidArgumentException('Lokasi penyimpanan wajib diisi.');
            }
            if (empty($date)) {
                throw new InvalidArgumentException('Tanggal wajib diisi.');
            }
            if (empty($adjustment_type)) {
                throw new InvalidArgumentException('Tipe adjustment wajib diisi.');
            }

            // ========== VALIDASI BARCODE SUDAH ADA DI ADJUST_TMP ==========
            // Fungsi untuk cek barcode di adjust_tmp
            function checkBarcodeInAdjustTmp($conn, $barcode, $date, $storage, $adjustment_type)
            {
                $barcode_escaped = $conn->real_escape_string(ltrim($barcode, '0'));
                $checkSql = "SELECT user_input, created_at FROM adjust_tmp 
                            WHERE barcode = '$barcode_escaped' 
                            AND adjustment_date = '$date' 
                            AND storage = '$storage' 
                            AND adjustment_type = '$adjustment_type'
                            AND deleted_at IS NULL 
                            LIMIT 1";

                $result = $conn->query($checkSql);
                if ($result && $result->num_rows > 0) {
                    return $result->fetch_assoc();
                }
                return null;
            }

            // 1. Validasi semua barcode asal
            foreach ($from_barcodes_data as $from_barcode) {
                $barcode_from = ltrim($from_barcode['barcode'] ?? '', '0');
                $checkResult = checkBarcodeInAdjustTmp($conn, $barcode_from, $date, $storage, $adjustment_type);
                if ($checkResult) {
                    throw new InvalidArgumentException("Barcode asal '$barcode_from' sudah digunakan oleh user: " . $checkResult['user_input'] . " pada " . date('Y-m-d H:i:s', strtotime($checkResult['created_at'])) . ". Tidak bisa melanjutkan gabungan.");
                }
            }

            // 2. Validasi barcode tujuan
            $barcode_to = ltrim($barcode_to_data['barcode'], '0');
            $checkResult = checkBarcodeInAdjustTmp($conn, $barcode_to, $date, $storage, $adjustment_type);
            if ($checkResult) {
                throw new InvalidArgumentException("Barcode tujuan '$barcode_to' sudah digunakan oleh user: " . $checkResult['user_input'] . " pada " . date('Y-m-d H:i:s', strtotime($checkResult['created_at'])) . ". Tidak bisa melanjutkan gabungan.");
            }
            // ========== END VALIDASI ==========

            // Mulai transaksi database
            $conn->begin_transaction();
            $inserted_ids = [];
            $barcode_from_list = [];

            try {
                // Hitung total ekor dan berat dengan perhitungan berat yang benar
                $total_ekor_transferred = 0;
                $total_berat_transferred = 0.0;

                $from_barcodes_with_actual_stock = [];

                foreach ($from_barcodes_data as $from_barcode) {
                    $barcode_from = $conn->real_escape_string(ltrim($from_barcode['barcode'] ?? '', '0'));
                    $ekor_pack_transferred = (int)($from_barcode['ekor_pack_from'] ?? 0);
                    $quantity_kg_transferred = (float)($from_barcode['quantity_kg_from'] ?? 0.0);

                    if ($ekor_pack_transferred <= 0 || $quantity_kg_transferred <= 0) {
                        throw new InvalidArgumentException("Jumlah ekor atau berat untuk barcode " . ($from_barcode['barcode'] ?? '') . " tidak valid.");
                    }

                    // Ambil stok AKTUAL dari database untuk barcode asal
                    $query_actual_stock_from = "
            SELECT 
                SUM(ekor) AS current_ekor_barcode, 
                SUM(berat) AS current_kg_barcode
            FROM ( 
                SELECT * FROM (
                    SELECT id, ekor_pack AS ekor, quantity_kg AS berat, `date` AS tanggal_stock
                    FROM transactions
                    WHERE quantity_kg > 0
                        AND barcode = '$barcode_from'
                        AND transaction_type IN ('in', 'transfer-in','gabung-new', 'adjustment-new', 'adjustment')
                        AND deleted_at IS NULL
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
                        AND barcode = '$barcode_from'
                        AND transaction_type IN ('in', 'transfer-in','gabung-new', 'adjustment-new', 'adjustment')
                        AND deleted_at IS NULL
                        AND `date` <= '$date'
                        AND quantity_kg > 0
                    GROUP BY id
                    ORDER BY id DESC
                    LIMIT 1
                ) AS A
                LEFT JOIN (
                    SELECT id_in, SUM(ekor_pack) AS ekor, SUM(quantity_kg) AS berat 
                    FROM transactions
                    WHERE 1=1
                        AND barcode = '$barcode_from'
                        AND id_in IS NOT NULL
                        AND `date` <= '$date'
                        AND deleted_at IS NULL
                    GROUP BY id_in
                ) AS B
                ON A.id = B.id_in
            ) AS B
        ";

                    $result_actual_stock_from = $conn->query($query_actual_stock_from);
                    $actual_stock_from = ['current_ekor_barcode' => 0, 'current_kg_barcode' => 0];
                    if ($result_actual_stock_from && $result_actual_stock_from->num_rows > 0) {
                        $actual_stock_from = $result_actual_stock_from->fetch_assoc();
                    }

                    $actual_ekor_stock = (float) ($actual_stock_from['current_ekor_barcode'] ?? 0);
                    $actual_berat_stock = (float) ($actual_stock_from['current_kg_barcode'] ?? 0);

                    // Validasi tidak melebihi stok AKTUAL
                    if ($ekor_pack_transferred > $actual_ekor_stock) {
                        throw new InvalidArgumentException("Jumlah ekor untuk barcode $barcode_from melebihi stok (Stok Aktual: $actual_ekor_stock, Transfer: $ekor_pack_transferred).");
                    }

                    // ========== PERBAIKAN: HITUNG BERAT BERDASARKAN RASIO STOK ==========
                    // Hitung berat berdasarkan rasio stok jika input berat lebih besar dari rasio
                    $average_weight_per_item = ($actual_ekor_stock > 0) ? ($actual_berat_stock / $actual_ekor_stock) : 0;
                    $calculated_weight = $ekor_pack_transferred * $average_weight_per_item;

                    // Gunakan berat yang lebih kecil antara input dan perhitungan
                    if ($quantity_kg_transferred > $calculated_weight && $calculated_weight > 0) {
                        $quantity_kg_transferred = round($calculated_weight, 2);
                    }

                    if ($quantity_kg_transferred > $actual_berat_stock) {
                        throw new InvalidArgumentException("Berat untuk barcode $barcode_from melebihi stok (Stok Aktual: $actual_berat_stock, Transfer: $quantity_kg_transferred).");
                    }

                    // Ambil data item lengkap dari database untuk barcode asal
                    $query_item_data = "
            SELECT item 
            FROM transactions 
            WHERE barcode = '$barcode_from' 
            AND storage = '$storage' 
            AND transaction_type IN ('in', 'gabung-new', 'transfer-in', 'adjustment-new', 'adjustment')
            AND deleted_at IS NULL 
            ORDER BY id DESC 
            LIMIT 1";

                    $result_item = $conn->query($query_item_data);
                    $full_item_db = '';
                    $item_db = '';
                    $subitem_db = '';

                    if ($result_item && $result_item->num_rows > 0) {
                        $item_row = $result_item->fetch_assoc();
                        $full_item_db = $item_row['item'];

                        // Pisahkan item dan subitem dari database
                        if (!empty($full_item_db)) {
                            $parts = explode(' : ', $full_item_db, 2);
                            $item_db = trim($parts[0]);
                            $subitem_db = isset($parts[1]) ? trim($parts[1]) : '';
                        }
                    }

                    // Gunakan data dari database jika ada, jika tidak gunakan dari input
                    $item_final = !empty($item_db) ? $item_db : $conn->real_escape_string($from_barcode['item'] ?? '');
                    $subitem_final = !empty($subitem_db) ? $subitem_db : $conn->real_escape_string($from_barcode['subitem'] ?? '');

                    $from_barcodes_with_actual_stock[] = [
                        'barcode' => $barcode_from,
                        'item' => $item_final,
                        'subitem' => $subitem_final,
                        'full_item' => $item_final . (empty($subitem_final) ? '' : ' : ' . $subitem_final),
                        'id_in' => $conn->real_escape_string($from_barcode['id_in'] ?? ''),
                        'ekor_pack_transferred' => $ekor_pack_transferred,
                        'quantity_kg_transferred' => $quantity_kg_transferred,
                        'actual_ekor_stock' => $actual_ekor_stock,
                        'actual_berat_stock' => $actual_berat_stock
                    ];

                    $total_ekor_transferred += $ekor_pack_transferred;
                    $total_berat_transferred += $quantity_kg_transferred;

                    $barcode_from_list[] = $barcode_from;
                }

                $total_berat_transferred = round($total_berat_transferred, 2);

                $barcode_to = $conn->real_escape_string(ltrim($barcode_to_data['barcode'], '0'));
                $item_to = $conn->real_escape_string($barcode_to_data['item'] ?? '');
                $subitem_to = $conn->real_escape_string($barcode_to_data['subitem'] ?? '');

                // PERBAIKAN: Jika subitem mengandung karakter khusus seperti "//", escape dengan benar
                $subitem_to_clean = $subitem_to;

                // Jika item_to kosong, ambil dari barcode pertama
                if (empty($item_to) && count($from_barcodes_with_actual_stock) > 0) {
                    $item_to = $from_barcodes_with_actual_stock[0]['item'];
                    $subitem_to_clean = $from_barcodes_with_actual_stock[0]['subitem'];
                }

                // Buat full_item_to dengan benar untuk tabel transactions
                $full_item_to = $item_to;
                if (!empty($subitem_to_clean)) {
                    $full_item_to .= ' : ' . $subitem_to_clean;
                }

                // 3. Hitung stok saat ini untuk barcode tujuan (jika bukan barcode baru)
                $current_ekor_barcode_to = 0;
                $current_berat_barcode_to = 0.0;
                $id_in_for_in_transaction = "NULL";

                if (!$is_new_barcode_to) {
                    $query_current_stock_to = "
            SELECT 
                SUM(ekor) AS current_ekor_barcode, 
                SUM(berat) AS current_kg_barcode
            FROM ( 
                SELECT * FROM (
                    SELECT id, ekor_pack AS ekor, quantity_kg AS berat, `date` AS tanggal_stock
                    FROM transactions
                    WHERE quantity_kg > 0
                        AND barcode = '$barcode_to'
                        AND transaction_type IN ('in', 'transfer-in','gabung-new', 'adjustment-new', 'adjustment')
                        AND deleted_at IS NULL
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
                        AND barcode = '$barcode_to'
                        AND transaction_type IN ('in', 'transfer-in','gabung-new', 'adjustment-new', 'adjustment')
                        AND deleted_at IS NULL
                        AND `date` <= '$date'
                        AND quantity_kg > 0
                    GROUP BY id
                    ORDER BY id DESC
                    LIMIT 1
                ) AS A
                LEFT JOIN (
                    SELECT id_in, SUM(ekor_pack) AS ekor, SUM(quantity_kg) AS berat 
                    FROM transactions
                    WHERE 1=1
                        AND barcode = '$barcode_to'
                        AND id_in IS NOT NULL
                        AND `date` <= '$date'
                        AND deleted_at IS NULL
                    GROUP BY id_in
                ) AS B
                ON A.id = B.id_in
            ) AS B
        ";

                    $result_current_stock_to = $conn->query($query_current_stock_to);
                    if ($result_current_stock_to && $result_current_stock_to->num_rows > 0) {
                        $stock_data_to = $result_current_stock_to->fetch_assoc();
                        $current_ekor_barcode_to = (float) ($stock_data_to['current_ekor_barcode'] ?? 0);
                        $current_berat_barcode_to = (float) ($stock_data_to['current_kg_barcode'] ?? 0);
                    }

                    // Ambil id_in dari transaksi terakhir untuk barcode tujuan
                    $query_id_in_to = "SELECT id FROM transactions 
                     WHERE barcode = '$barcode_to' 
                     AND storage = '$storage' 
                     AND transaction_type IN ('in', 'gabung-new', 'transfer-in', 'adjustment-new', 'adjustment')
                     AND deleted_at IS NULL
                     ORDER BY id DESC LIMIT 1";
                    $result_id_in_to = $conn->query($query_id_in_to);
                    if ($result_id_in_to && $result_id_in_to->num_rows > 0) {
                        $id_in_data = $result_id_in_to->fetch_assoc();
                        $id_in_for_in_transaction = "'" . $id_in_data['id'] . "'";
                    }
                }

                // 4. Proses Transaksi OUT (Pengurangan dari setiap barcode_from)
                foreach ($from_barcodes_with_actual_stock as $from_barcode) {
                    $barcode_from = $from_barcode['barcode'];
                    $item_from = $from_barcode['item'];
                    $subitem_from = $from_barcode['subitem'];
                    $full_item_from = $from_barcode['full_item'];
                    $id_in_from = $from_barcode['id_in'];
                    $ekor_pack_transferred = $from_barcode['ekor_pack_transferred'];
                    $quantity_kg_transferred = $from_barcode['quantity_kg_transferred'];

                    if ($barcode_from === $barcode_to) {
                        throw new InvalidArgumentException("Barcode asal '$barcode_from' tidak boleh sama dengan barcode tujuan.");
                    }

                    $keterangan_out = "Gabungan Keluar (ke $barcode_to) - $adjustment_type";
                    $ekor_out_value = -$ekor_pack_transferred;
                    $quantity_kg_out_value = -$quantity_kg_transferred;
                    $id_in_for_out_transaction = empty($id_in_from) ? "NULL" : "'" . $id_in_from . "'";

                    $query_out = "
    INSERT INTO transactions (
        date, from_location, to_location, storage, item, 
        ekor_pack, quantity_kg, transaction_type, keterangan, 
        user, barcode, id_in, adjustment_type
    ) VALUES (
        '$date',
        '$storage',
        '$storage',
        '$storage',
        '$full_item_from',
        $ekor_out_value,
        $quantity_kg_out_value,
        'gabung-out',
        '$keterangan_out',
        '$user',
        '$barcode_from',
        $id_in_for_out_transaction,
        '$adjustment_type'
    )
";

                    if (!$conn->query($query_out)) {
                        throw new Exception("Gagal menyimpan transaksi keluar untuk $barcode_from: " . $conn->error . " Query: " . $query_out);
                    }
                    $inserted_ids[] = $conn->insert_id;

                    // Simpan barcode asal ke adjust_tmp dengan format yang benar
                    $row_unique_id_from = time() . rand(100, 999) . "_from_" . $barcode_from;

                    $new_ekor_from = $from_barcode['actual_ekor_stock'] - $ekor_pack_transferred;
                    $new_berat_from = round($from_barcode['actual_berat_stock'] - $quantity_kg_transferred, 2);

                    if ($new_ekor_from < 0) $new_ekor_from = 0;
                    if ($new_berat_from < 0) $new_berat_from = 0;

                    // Untuk adjust_tmp: Simpan item dan subitem terpisah
                    $item_for_adjust_tmp = $item_from; // Hanya item saja
                    $subitem_for_adjust_tmp = !empty($subitem_from) ? $subitem_from : null;

                    // PERBAIKAN: Escape subitem dengan benar
                    $subitem_sql = empty($subitem_for_adjust_tmp) ? "NULL" : "'" . $conn->real_escape_string($subitem_for_adjust_tmp) . "'";

                    $adjust_tmp_query_from = "
    INSERT INTO adjust_tmp (
        user_input, adjustment_date, storage, adjustment_type, row_unique_id,
        id_in, barcode, item, subitem,
        ekor_stock_current, berat_stock_current, ekor_new, berat_new,
        keterangan, is_edited, is_details_collapsed
    ) VALUES (
        '$user',
        '$date',
        '$storage',
        '$adjustment_type',
        '$row_unique_id_from',
        $id_in_for_out_transaction,
        '$barcode_from',
        '$item_for_adjust_tmp',
        $subitem_sql,
        " . $from_barcode['actual_ekor_stock'] . ",
        " . $from_barcode['actual_berat_stock'] . ",
        $new_ekor_from,
        $new_berat_from,
        'Barcode asal gabungan (dikirim ke $barcode_to)',
        1,
        1
    )
";

                    if (!$conn->query($adjust_tmp_query_from)) {
                        error_log("Failed to save from barcode to adjust_tmp: " . $conn->error . " Query: " . $adjust_tmp_query_from);
                    }
                }

                // 5. Proses Transaksi IN (Penambahan ke barcode_to)
                $transaction_type_in = $is_new_barcode_to ? 'gabung-new' : 'gabung-in';

                // Buat keterangan yang lebih detail dengan daftar barcode
                $barcode_from_string = implode(', ', $barcode_from_list);
                $keterangan_in = "Gabungan Masuk (dari barcode: $barcode_from_string) - $adjustment_type";

                // Hitung stok baru untuk barcode tujuan
                $new_ekor_barcode_to = $current_ekor_barcode_to + $total_ekor_transferred;
                $new_berat_barcode_to = round($current_berat_barcode_to + $total_berat_transferred, 2);

                $query_in = "
        INSERT INTO transactions (
            date, from_location, to_location, storage, item, 
            ekor_pack, quantity_kg, transaction_type, keterangan, 
            user, barcode, id_in, adjustment_type
        ) VALUES (
            '$date',
            '$storage',
            '$storage',
            '$storage',
            '$full_item_to',
            $total_ekor_transferred,
            $total_berat_transferred,
            '$transaction_type_in',
            '$keterangan_in',
            '$user',
            '$barcode_to',
            $id_in_for_in_transaction,
            '$adjustment_type'
        )
    ";

                if (!$conn->query($query_in)) {
                    throw new Exception("Gagal menyimpan transaksi masuk: " . $conn->error . " Query: " . $query_in);
                }
                $inserted_in_id = $conn->insert_id;
                $inserted_ids[] = $inserted_in_id;

                // ========== PERBAIKAN: SIMPAN BARCODE TUJUAN DI PALING BAWAH adjust_tmp ==========
                // Tunggu sebentar untuk memastikan data asal sudah disimpan
                usleep(100000); // 100ms delay

                // 6. Simpan barcode tujuan ke adjust_tmp dengan created_at yang lebih baru
                $row_unique_id_to = time() . rand(1000, 9999) . "_to_" . $barcode_to; // Pastikan lebih unik

                // Hitung stok global untuk item tujuan
                $sql_global_to = "
        SELECT
            SUM(ekor_pack) AS ekor_item_global_stock,
            SUM(quantity_kg) AS berat_item_global_stock
        FROM transactions
        WHERE item = '$full_item_to'
            AND storage = '$storage'
            AND deleted_at IS NULL
            AND date <= '$date'
    ";

                $result_global_to = $conn->query($sql_global_to);
                $global_data_to = ['ekor_item_global_stock' => 0, 'berat_item_global_stock' => 0];
                if ($result_global_to && $result_global_to->num_rows > 0) {
                    $global_data_to = $result_global_to->fetch_assoc();
                }

                // Buat keterangan yang detail untuk adjust_tmp
                $keterangan_adjust_tmp = "Hasil gabungan dari barcode: " . implode(', ', $barcode_from_list);

                // Untuk adjust_tmp: Simpan item dan subitem terpisah
                $item_to_for_adjust_tmp = $item_to; // Hanya item saja
                $subitem_to_for_adjust_tmp = !empty($subitem_to_clean) ? $subitem_to_clean : null;

                // PERBAIKAN: Escape subitem dengan benar
                $subitem_to_sql = empty($subitem_to_for_adjust_tmp) ? "NULL" : "'" . $conn->real_escape_string($subitem_to_for_adjust_tmp) . "'";

                // HAPUS created_at EXPLICIT untuk dibuat secara otomatis dengan timestamp terkini
                $adjust_tmp_query_to = "
        INSERT INTO adjust_tmp (
            user_input, adjustment_date, storage, adjustment_type, row_unique_id,
            id_in, barcode, item, subitem,
            ekor_stock_current, berat_stock_current, ekor_new, berat_new,
            keterangan, is_edited, is_details_collapsed
        ) VALUES (
            '$user',
            '$date',
            '$storage',
            '$adjustment_type',
            '$row_unique_id_to',
            $id_in_for_in_transaction,
            '$barcode_to',
            '$item_to_for_adjust_tmp',
            $subitem_to_sql,
            $current_ekor_barcode_to,
            $current_berat_barcode_to,
            $new_ekor_barcode_to,
            $new_berat_barcode_to,
            '$keterangan_adjust_tmp',
            1,
            1
        )
    ";

                if (!$conn->query($adjust_tmp_query_to)) {
                    error_log("Failed to save to barcode to adjust_tmp: " . $conn->error . " Query: " . $adjust_tmp_query_to);
                    // Jangan throw error, biarkan transaksi tetap berhasil
                }

                // Commit transaksi
                $conn->commit();

                // Siapkan response data untuk frontend
                $response_data = [
                    'id' => $inserted_in_id,
                    'id_in' => !$is_new_barcode_to ? ($id_in_for_in_transaction !== "NULL" ? str_replace("'", "", $id_in_for_in_transaction) : null) : null,
                    'barcode' => $barcode_to,
                    'item' => $item_to,
                    'subitem' => $subitem_to_clean ?: null,
                    'storage' => $storage,
                    'ekor_stock_current' => $current_ekor_barcode_to,
                    'berat_stock_current' => $current_berat_barcode_to,
                    'ekor_item_global_stock' => (float)($global_data_to['ekor_item_global_stock'] ?? 0),
                    'berat_item_global_stock' => (float)($global_data_to['berat_item_global_stock'] ?? 0),
                    'ekor_new' => $new_ekor_barcode_to,
                    'berat_new' => $new_berat_barcode_to,
                    'keterangan' => $keterangan_adjust_tmp,
                    'is_already_scanned' => false
                ];

                $response = [
                    'success' => true,
                    'message' => 'Transaksi gabungan berhasil disimpan.',
                    'inserted_ids' => $inserted_ids,
                    'row_unique_id' => $row_unique_id_to,
                    'data' => $response_data
                ];
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
