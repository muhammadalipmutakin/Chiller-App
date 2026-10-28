<?php
header('Content-Type: application/json');
session_name('chiller_app');
session_start();

require '../../controller/db/config.php';
$logHandlerUrl = 'http://localhost/chiller/apps/controller/handler/log_activity_handler.php';
$currentUser = $_SESSION['user_nama'] ?? 'Unknown User';
$currentUserId = $_SESSION['user_id'] ?? 0;

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}


// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $action = $input['action'] ?? '';

    $conn->begin_transaction();

    try {
        switch ($action) {
            case 'savePendingTransaction':
                // Validate essential fields before proceeding
                if (!isset($input['barcode_out']) && !isset($input['barcode_in'])) {
                    throw new InvalidArgumentException('Setidaknya Barcode Out atau Barcode In harus diisi untuk pending.');
                }

                $pending_id = $input['id'] ?? null;

                // --- START: Refined Data Preparation ---
                // Pastikan nilai di-trim dan '0' di awal dihapus untuk barcode
                $barcode_out_val = (isset($input['barcode_out']) && trim($input['barcode_out']) !== '') ? ltrim(trim($input['barcode_out']), '0') : null;
                $barcode_in_val  = (isset($input['barcode_in']) && trim($input['barcode_in']) !== '') ? ltrim(trim($input['barcode_in']), '0') : null;

                // Pastikan setidaknya satu barcode memiliki nilai non-null setelah pembersihan
                if (empty($barcode_out_val) && empty($barcode_in_val)) {
                    throw new InvalidArgumentException('Setidaknya Barcode Out atau Barcode In harus diisi dengan nilai yang valid untuk pending.');
                }

                $id_in_val       = (isset($input['id_in']) && trim($input['id_in']) !== '') ? trim($input['id_in']) : null;
                $date_val        = (isset($input['date']) && trim($input['date']) !== '') ? trim($input['date']) : null;
                $from_location_val = (isset($input['from_location']) && trim($input['from_location']) !== '') ? trim($input['from_location']) : null;
                $to_location_val = (isset($input['to_location']) && trim($input['to_location']) !== '') ? trim($input['to_location']) : null;
                $item_val        = (isset($input['item']) && trim($input['item']) !== '') ? trim($input['item']) : null;
                $subitem_val     = (isset($input['subitem']) && trim($input['subitem']) !== '') ? trim($input['subitem']) : null;
                $keterangan_val  = (isset($input['keterangan']) && trim($input['keterangan']) !== '') ? trim($input['keterangan']) : null;

                // Untuk angka, pastikan nilai 0 tetap disimpan jika memang 0, atau null jika kosong
                $ekor_pack_val = (isset($input['ekor_pack']) && ($input['ekor_pack'] !== '' && $input['ekor_pack'] !== null)) ? (int)$input['ekor_pack'] : 0; // Default ke 0 jika kosong
                $quantity_kg_val = (isset($input['quantity_kg']) && ($input['quantity_kg'] !== '' && $input['quantity_kg'] !== null)) ? (float)$input['quantity_kg'] : 0.0; // Default ke 0.0 jika kosong


                $user_val = $_SESSION['user_nama'] ?? 'Unknown User'; // Always use session user for consistency
                // --- END: Refined Data Preparation ---

                $new_pending_count = 1; // Default for new records
                $log_action_type = 'CREATE'; // Default action type for log
                $log_record_id = null; // To store the ID for the log

                $stmt = null; // Inisialisasi $stmt di luar if-else

                if ($pending_id) {
                    // Logika untuk UPDATE
                    $log_action_type = 'UPDATE'; // Set action type for update
                    $log_record_id = $pending_id; // Set record ID for update

                    $check_stmt = $conn->prepare("SELECT pending_count FROM pending_transactions WHERE id = ?");
                    if (!$check_stmt) {
                        error_log("Failed to prepare check statement: " . $conn->error);
                        throw new Exception("Failed to prepare check statement: " . $conn->error);
                    }
                    $check_stmt->bind_param("i", $pending_id);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();

                    if ($check_result && $check_result->num_rows > 0) {
                        $row = $check_result->fetch_assoc();
                        $new_pending_count = $row['pending_count'] + 1;

                        // Pastikan pending_count tidak melebihi batas (misal 2 atau 3)
                        if ($new_pending_count > 3) { // Sesuaikan batas maksimal Anda di sini (misal 2 atau 3)
                            throw new InvalidArgumentException("Transaksi pending ini sudah mencapai batas maksimal (" . ($new_pending_count - 1) . " kali) dan harus diselesaikan atau dihapus.");
                        }

                        $sql = "UPDATE pending_transactions SET
                barcode_out = ?,
                barcode_in = ?,
                id_in = ?,
                date = ?,
                from_location = ?,
                to_location = ?,
                item = ?,
                subitem = ?,
                ekor_pack = ?,
                quantity_kg = ?,
                keterangan = ?,
                user = ?,
                pending_count = ?,
                deleted_at = NULL,
                created_at = CURRENT_TIMESTAMP
            WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        if (!$stmt) {
                            error_log("Failed to prepare update statement: " . $conn->error);
                            throw new Exception("Failed to prepare update statement: " . $conn->error);
                        }
                        // PERBAIKAN: String format bind_param diubah menjadi "ssssssssidssii"
                        // (s untuk subitem, i untuk ekor_pack)
                        $stmt->bind_param(
                            "ssssssssidssii",
                            $barcode_out_val,
                            $barcode_in_val,
                            $id_in_val,
                            $date_val,
                            $from_location_val,
                            $to_location_val,
                            $item_val,
                            $subitem_val,     // Ini adalah parameter ke-8, sekarang terikat sebagai 's'
                            $ekor_pack_val,   // Ini adalah parameter ke-9, sekarang terikat sebagai 'i'
                            $quantity_kg_val,
                            $keterangan_val,
                            $user_val,
                            $new_pending_count,
                            $pending_id
                        );
                    } else {
                        // Jika ID diberikan tapi tidak ditemukan (mungkin sudah dihapus permanen), masukkan sebagai record baru
                        error_log("Pending ID $pending_id not found. Inserting as new record.");
                        $log_action_type = 'CREATE'; // Fallback to CREATE if ID not found for update
                        $sql = "INSERT INTO pending_transactions (barcode_out, barcode_in, id_in, date, from_location, to_location, item, subitem, ekor_pack, quantity_kg, keterangan, user, pending_count) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )";
                        $stmt = $conn->prepare($sql);
                        if (!$stmt) {
                            error_log("Failed to prepare insert statement (fallback from update): " . $conn->error);
                            throw new Exception("Failed to prepare insert statement (fallback from update): " . $conn->error);
                        }
                        // PERBAIKAN: String format bind_param diubah menjadi "ssssssssidssi"
                        // (s untuk subitem, i untuk ekor_pack)
                        $stmt->bind_param(
                            "ssssssssidssi",
                            $barcode_out_val,
                            $barcode_in_val,
                            $id_in_val,
                            $date_val,
                            $from_location_val,
                            $to_location_val,
                            $item_val,
                            $subitem_val,     // Ini adalah parameter ke-8, sekarang terikat sebagai 's'
                            $ekor_pack_val,   // Ini adalah parameter ke-9, sekarang terikat sebagai 'i'
                            $quantity_kg_val,
                            $keterangan_val,
                            $user_val,
                            $new_pending_count
                        );
                    }
                } else {
                    // Logika untuk INSERT BARU
                    $sql = "INSERT INTO pending_transactions (barcode_out, barcode_in, id_in, date, from_location, to_location, item, subitem, ekor_pack, quantity_kg, keterangan, user, pending_count) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )";
                    $stmt = $conn->prepare($sql);
                    if (!$stmt) {
                        error_log("Failed to prepare insert statement: " . $conn->error);
                        throw new Exception("Failed to prepare insert statement: " . $conn->error);
                    }
                    // PERBAIKAN: String format bind_param diubah menjadi "ssssssssidssi"
                    // (s untuk subitem, i untuk ekor_pack)
                    $stmt->bind_param(
                        "ssssssssidssi",
                        $barcode_out_val,
                        $barcode_in_val,
                        $id_in_val,
                        $date_val,
                        $from_location_val,
                        $to_location_val,
                        $item_val,
                        $subitem_val,     // Ini adalah parameter ke-8, sekarang terikat sebagai 's'
                        $ekor_pack_val,   // Ini adalah parameter ke-9, sekarang terikat sebagai 'i'
                        $quantity_kg_val,
                        $keterangan_val,
                        $user_val,
                        $new_pending_count
                    );
                }

                // Eksekusi statement setelah if-else block
                if (!$stmt->execute()) {
                    error_log("Error executing pending transaction statement: " . $stmt->error);
                    throw new mysqli_sql_exception("Error saving pending transaction: " . $stmt->error);
                }

                // Jika ini adalah INSERT baru, dapatkan ID yang baru dibuat
                if (!$pending_id || ($pending_id && !$check_result->num_rows)) {
                    $log_record_id = $conn->insert_id;
                }

                $stmt->close();

                echo json_encode(['success' => true, 'message' => 'Transaksi berhasil disimpan sebagai pending.']);

                // --- LOG ACTIVITY ---
                $log_data = [
                    'action_type' => $log_action_type,
                    'table_name' => 'pending_transactions',
                    'record_id' => $log_record_id,
                    'data_old' => null, // Untuk kesederhanaan, data_old di set null
                    'data_new' => $input, // Data yang baru disimpan/diupdate
                    'notes' => "Transaksi pending " . ($log_action_type === 'CREATE' ? "baru ditambahkan" : "diperbarui") . " oleh user '{$user_val}'.",
                    'id_user' => $currentUserId
                ];

                // Panggil log handler
                $ch = curl_init($logHandlerUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($log_data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);
                curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
                $log_response = curl_exec($ch);
                $log_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $log_curl_error = curl_error($ch);
                curl_close($ch);

                if ($log_http_code !== 201) {
                    error_log("Gagal mencatat log aktivitas untuk pending transaction. HTTP Code: " . $log_http_code . " - Response: " . $log_response . " - cURL Error: " . $log_curl_error);
                }
                // --- END LOG ACTIVITY ---
                break;

            case 'deletePendingTransaction':
                $pending_id_to_delete = $input['id'] ?? null;
                $isSoftDelete = true;
                $user_val = $_SESSION['user_nama'] ?? 'Unknown User'; // Get user for log

                if (!$pending_id_to_delete) {
                    throw new Exception("ID transaksi pending tidak ditemukan untuk dihapus.");
                }

                // --- START: Ambil data lama sebelum dihapus/diupdate ---
                $old_data = null;
                $select_stmt = $conn->prepare("SELECT * FROM pending_transactions WHERE id = ?");
                if ($select_stmt) {
                    $select_stmt->bind_param("i", $pending_id_to_delete);
                    $select_stmt->execute();
                    $result = $select_stmt->get_result();
                    if ($result && $result->num_rows > 0) {
                        $old_data = $result->fetch_assoc();
                    }
                    $select_stmt->close();
                } else {
                    error_log("Failed to prepare select statement for old_data in deletePendingTransaction: " . $conn->error);
                }
                // --- END: Ambil data lama sebelum dihapus/diupdate ---


                $sql = "";
                if ($isSoftDelete) {
                    $sql = "UPDATE pending_transactions SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?";
                } else {
                    $sql = "DELETE FROM pending_transactions WHERE id = ?";
                }

                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Failed to prepare delete statement: " . $conn->error);
                }
                $stmt->bind_param("i", $pending_id_to_delete);

                if (!$stmt->execute()) {
                    throw new mysqli_sql_exception("Error deleting pending transaction: " . $stmt->error);
                }

                $response_message = '';
                if ($stmt->affected_rows > 0) {
                    $response_message = 'Transaksi pending berhasil dihapus.';
                    echo json_encode(['success' => true, 'message' => $response_message]);

                    // --- LOG ACTIVITY ---
                    $log_data = [
                        'action_type' => 'DELETE',
                        'table_name' => 'pending_transactions',
                        'record_id' => $pending_id_to_delete,
                        'data_old' => $old_data, // Mengirim data lama di sini
                        'data_new' => null, // Ini adalah operasi delete, jadi data_new null
                        'notes' => "Transaksi pending ID {$pending_id_to_delete} berhasil dihapus " . ($isSoftDelete ? "(soft delete)" : "(permanen)") . " oleh user '{$user_val}'.",
                        'id_user' => $currentUserId
                    ];

                    // Panggil log handler
                    $ch = curl_init($logHandlerUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($log_data));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);
                    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
                    $log_response = curl_exec($ch);
                    $log_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $log_curl_error = curl_error($ch);
                    curl_close($ch);

                    if ($log_http_code !== 201) {
                        error_log("Gagal mencatat log aktivitas untuk delete pending transaction. HTTP Code: " . $log_http_code . " - Response: " . $log_response . " - cURL Error: " . $log_curl_error);
                    }
                    // --- END LOG ACTIVITY ---

                } else {
                    $response_message = 'Transaksi pending tidak ditemukan atau sudah dihapus.';
                    echo json_encode(['success' => false, 'message' => $response_message]);
                }
                $stmt->close();
                break;

            case 'completePendingTransaction':
                $pending_id_to_complete = $input['id'] ?? null;
                $user_val = $_SESSION['user_nama'] ?? 'Unknown User'; // Get user for log

                if (!$pending_id_to_complete) {
                    throw new Exception("ID transaksi pending tidak ditemukan untuk diselesaikan.");
                }

                // --- START: Ambil data lama sebelum diupdate ---
                $old_data = null;
                $select_stmt = $conn->prepare("SELECT * FROM pending_transactions WHERE id = ?");
                if ($select_stmt) {
                    $select_stmt->bind_param("i", $pending_id_to_complete);
                    $select_stmt->execute();
                    $result = $select_stmt->get_result();
                    if ($result && $result->num_rows > 0) {
                        $old_data = $result->fetch_assoc();
                    }
                    $select_stmt->close();
                } else {
                    error_log("Failed to prepare select statement for old_data in completePendingTransaction: " . $conn->error);
                }
                // --- END: Ambil data lama sebelum diupdate ---

                $sql = "UPDATE pending_transactions SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Failed to prepare complete statement: " . $conn->error);
                }
                $stmt->bind_param("i", $pending_id_to_complete);

                if (!$stmt->execute()) {
                    throw new mysqli_sql_exception("Error completing pending transaction (soft delete): " . $stmt->error);
                }

                $response_message = '';
                if ($stmt->affected_rows > 0) {
                    $response_message = 'Transaksi pending berhasil diselesaikan dan diarsipkan.';
                    echo json_encode(['success' => true, 'message' => $response_message]);

                    // --- LOG ACTIVITY ---
                    $log_data = [
                        'action_type' => 'UPDATE', // Ini adalah soft delete, jadi lebih tepat UPDATE
                        'table_name' => 'pending_transactions',
                        'record_id' => $pending_id_to_complete,
                        'data_old' => $old_data, // Mengirim data lama di sini
                        'data_new' => ['deleted_at' => date('Y-m-d H:i:s')], // Menunjukkan field yang diupdate
                        'notes' => "Transaksi pending ID {$pending_id_to_complete} berhasil diselesaikan (diarsipkan) oleh user '{$user_val}'.",
                        'id_user' => $currentUserId
                    ];

                    // Panggil log handler
                    $ch = curl_init($logHandlerUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($log_data));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);
                    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
                    $log_response = curl_exec($ch);
                    $log_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $log_curl_error = curl_error($ch);
                    curl_close($ch);

                    if ($log_http_code !== 201) {
                        error_log("Gagal mencatat log aktivitas untuk complete pending transaction. HTTP Code: " . $log_http_code . " - Response: " . $log_response . " - cURL Error: " . $log_curl_error);
                    }
                    // --- END LOG ACTIVITY ---

                } else {
                    $response_message = 'Transaksi pending tidak ditemukan atau sudah diarsipkan.';
                    echo json_encode(['success' => false, 'message' => $response_message]);
                }
                $stmt->close();
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Aksi POST tidak valid untuk endpoint ini.']);
                break;
        }
        $conn->commit();
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        error_log("Database error in pending_transaksi_api.php for action '$action': " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Gagal dalam operasi database: ' . $e->getMessage()]);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("General error in pending_transaksi_api.php for action '$action': " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan umum: ' . $e->getMessage()]);
    } finally {
        if ($conn && !$conn->connect_error) {
            $conn->close();
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    try {
        switch ($action) {
            case 'getPendingTransactions':
                // Hapus semua logika untuk mengambil $user_param dari GET atau SESSION.
                // Sekarang kita akan mengambil semua transaksi pending.

                $sql = "SELECT id, barcode_out, barcode_in, id_in, date, from_location, to_location, item, subitem, ekor_pack, quantity_kg, keterangan, user, pending_count, created_at
            FROM pending_transactions
            WHERE deleted_at IS NULL ORDER BY created_at DESC"; // Hapus 'user = ?'

                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    error_log("Failed to prepare get pending transactions statement: " . $conn->error);
                    echo json_encode(['success' => false, 'message' => 'Gagal menyiapkan query untuk mengambil transaksi pending.']);
                    exit();
                }

                // Hapus baris bind_param karena tidak ada lagi parameter 'user' di query.
                // $stmt->bind_param("s", $user_param); // BARIS INI DIHAPUS

                $stmt->execute();
                $result = $stmt->get_result();

                $pending_data = [];
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        // Konversi NULL dari database menjadi string kosong untuk display di frontend.
                        foreach ($row as $key => $value) {
                            if ($value === null) {
                                $row[$key] = '';
                            }
                        }
                        $pending_data[] = $row;
                    }
                }
                echo json_encode(['success' => true, 'data' => $pending_data]);
                $stmt->close();
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Aksi GET tidak valid untuk endpoint ini.']);
                break;
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Database error in pending_transaksi_api.php for action '$action': " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Gagal dalam operasi database: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("General error in pending_transaksi_api.php for action '$action': " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan umum: ' . $e->getMessage()]);
    } finally {
        if ($conn && !$conn->connect_error) {
            $conn->close();
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak diizinkan.']);
    if ($conn && !$conn->connect_error) {
        $conn->close();
    }
}
