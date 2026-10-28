<?php
session_name('chiller_app');
session_start();


ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require '../../controller/db/config.php';

$logHandlerUrl = 'http://localhost/chiller/apps/controller/handler/log_activity_handler.php';
$currentUser = $_SESSION['user_nama'] ?? 'Unknown User';
$currentUserId = $_SESSION['user_id'] ?? 0;

$method = $_SERVER['REQUEST_METHOD'];
$id_from_get = $_GET['id'] ?? null;

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    switch ($method) {
        case 'GET':
            $sql = "SELECT id, subitem FROM subitems" . ($id_from_get ? " WHERE id = ?" : "") . " ORDER BY subitem ASC";
            $stmt = $conn->prepare($sql);

            if ($stmt === false) {
                // Log error lebih detail untuk debugging
                error_log("GET - Failed to prepare statement: " . $conn->error);
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan server saat menyiapkan kueri.']);
                exit();
            }

            if ($id_from_get) {
                // Pastikan $id_from_get adalah integer yang valid
                if (!filter_var($id_from_get, FILTER_VALIDATE_INT)) {
                    http_response_code(400); // Bad Request
                    echo json_encode([
                        'success' => false,
                        'error' => 'ID untuk GET harus berupa bilangan bulat.'
                    ]);
                    exit();
                }
                $stmt->bind_param("i", $id_from_get);
            }

            $stmt->execute();
            // Cek error eksekusi
            if ($stmt->errno) {
                error_log("GET - Execute failed: (" . $stmt->errno . ") " . $stmt->error);
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Gagal mengambil data karena masalah database.']);
                exit();
            }

            $result = $stmt->get_result();

            if ($result === false) {
                error_log("GET - Failed to get result: " . $stmt->error);
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan server saat mengambil hasil data.']);
                exit();
            }

            $subitems = $result->fetch_all(MYSQLI_ASSOC);

            if ($id_from_get && empty($subitems)) {
                http_response_code(404); // Not Found
                echo json_encode([
                    'success' => false,
                    'message' => 'Subitem tidak ditemukan.'
                ]);
            } else {
                http_response_code(200); // OK
                echo json_encode([
                    'success' => true,
                    'data' => $subitems
                ]);
            }
            $stmt->close();
            break;

        case 'DELETE':
            // ID untuk DELETE diambil dari URL parameter
            if (!$id_from_get || !filter_var($id_from_get, FILTER_VALIDATE_INT)) {
                http_response_code(400); // Bad Request
                echo json_encode([
                    'success' => false,
                    'error' => 'ID diperlukan dan harus berupa bilangan bulat untuk penghapusan.'
                ]);
                exit();
            }

            // --- Ambil data lama sebelum dihapus untuk log ---
            $old_data = null;
            $select_stmt = $conn->prepare("SELECT id, subitem FROM subitems WHERE id = ?");
            if ($select_stmt) {
                $select_stmt->bind_param("i", $id_from_get);
                $select_stmt->execute();
                $result = $select_stmt->get_result();
                if ($result && $result->num_rows > 0) {
                    $old_data = $result->fetch_assoc();
                }
                $select_stmt->close();
            } else {
                error_log("DELETE - Failed to prepare select statement for old_data: " . $conn->error);
            }
            // --- END: Ambil data lama ---

            $stmt = $conn->prepare("DELETE FROM subitems WHERE id = ?");
            if ($stmt === false) {
                error_log("DELETE - Failed to prepare statement: " . $conn->error);
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan server saat menyiapkan penghapusan.']);
                exit();
            }

            $stmt->bind_param("i", $id_from_get);
            $stmt->execute();

            if ($stmt->errno) {
                error_log("DELETE - Execute failed: (" . $stmt->errno . ") " . $stmt->error);
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Gagal menghapus subitem karena masalah database.'
                ]);
                exit();
            }

            if ($stmt->affected_rows > 0) {
                http_response_code(200); // OK
                echo json_encode([
                    'success' => true,
                    'message' => 'Subitem berhasil dihapus.'
                ]);

                // --- KIRIM LOG AKTIVITAS (DELETE) ---
                $log_data = [
                    'action_type' => 'DELETE',
                    'table_name' => 'subitems',
                    'record_id' => $id_from_get,
                    'data_old' => $old_data, // Data lama sebelum dihapus
                    'data_new' => null, // Operasi DELETE
                    'notes' => "Subitem ID {$id_from_get} ('" . ($old_data['subitem'] ?? 'N/A') . "') berhasil dihapus oleh '{$currentUser}'.",
                    'id_user' => $currentUserId
                ];

                $ch = curl_init($logHandlerUrl);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($log_data),
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                    CURLOPT_TIMEOUT_MS => 500,
                    CURLOPT_NOSIGNAL => 1
                ]);
                $log_response = curl_exec($ch);
                $log_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $log_curl_error = curl_error($ch);
                curl_close($ch);

                if ($log_http_code !== 201) {
                    error_log("Gagal mencatat log aktivitas untuk delete subitem ID {$id_from_get}. HTTP Code: " . $log_http_code . " - Response: " . $log_response . " - cURL Error: " . $log_curl_error);
                }
                // --- END KIRIM LOG AKTIVITAS ---

            } else {
                http_response_code(200); // OK
                echo json_encode([
                    'success' => false,
                    'message' => 'Tidak ada subitem ditemukan dengan ID tersebut.'
                ]);
            }
            $stmt->close();
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);

            // Validasi input 'subitem'
            if (!isset($input['subitem']) || trim($input['subitem']) === '') {
                http_response_code(400); // Bad Request
                echo json_encode([
                    'success' => false,
                    'error' => 'Nama subitem diperlukan.'
                ]);
                exit();
            }
            $subitem = trim($input['subitem']);

            $stmt = $conn->prepare("INSERT INTO subitems (subitem) VALUES (?)");
            if ($stmt === false) {
                error_log("POST - Failed to prepare statement: " . $conn->error);
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan server saat menyiapkan penambahan.']);
                exit();
            }

            $stmt->bind_param("s", $subitem);
            $stmt->execute();

            if ($stmt->errno) {
                error_log("POST - Execute failed: (" . $stmt->errno . ") " . $stmt->error);
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Gagal menambahkan subitem karena masalah database.'
                ]);
                exit();
            }

            if ($stmt->affected_rows === 0) {
                http_response_code(500); // Internal Server Error
                echo json_encode([
                    'success' => false,
                    'error' => 'Gagal membuat subitem atau tidak ada baris yang terpengaruh (kesalahan tidak terduga).'
                ]);
            } else {
                $new_subitem_id = $stmt->insert_id;
                http_response_code(201); // Created
                echo json_encode([
                    'success' => true,
                    'message' => 'Subitem berhasil ditambahkan.',
                    'id' => $new_subitem_id
                ]);

                // --- BLOK LOG AKTIVITAS DIHAPUS ---
            }
            $stmt->close();
            break;

        case 'PUT':
            // Ambil data JSON dari body request
            $input = json_decode(file_get_contents('php://input'), true);

            // 1. Validasi ID dari body request JSON
            if (!isset($input['id']) || !filter_var($input['id'], FILTER_VALIDATE_INT)) {
                http_response_code(400); // Bad Request
                echo json_encode([
                    'success' => false,
                    'error' => 'ID subitem diperlukan dan harus berupa bilangan bulat untuk pembaruan.'
                ]);
                exit();
            }
            $id_to_update = $input['id'];

            // --- Ambil data lama sebelum diupdate untuk log ---
            $old_data = null;
            $select_stmt_old = $conn->prepare("SELECT id, subitem FROM subitems WHERE id = ?");
            if ($select_stmt_old) {
                $select_stmt_old->bind_param("i", $id_to_update);
                $select_stmt_old->execute();
                $result_old = $select_stmt_old->get_result();
                if ($result_old && $result_old->num_rows > 0) {
                    $old_data = $result_old->fetch_assoc();
                }
                $select_stmt_old->close();
            } else {
                error_log("PUT - Failed to prepare select statement for old_data: " . $conn->error);
            }
            // --- END: Ambil data lama ---

            // 2. Validasi field 'subitem'
            if (!isset($input['subitem']) || trim($input['subitem']) === '') {
                http_response_code(400); // Bad Request
                echo json_encode([
                    'success' => false,
                    'error' => 'Nama subitem diperlukan.'
                ]);
                exit();
            }
            $subitem_to_update = trim($input['subitem']);

            // Mulai operasi database
            $stmt = $conn->prepare("UPDATE subitems SET subitem = ? WHERE id = ?");
            if ($stmt === false) {
                error_log("PUT - Failed to prepare statement: " . $conn->error);
                http_response_code(500); // Internal Server Error
                echo json_encode([
                    'success' => false,
                    'error' => 'Terjadi kesalahan server saat menyiapkan pembaruan.'
                ]);
                exit();
            }

            $stmt->bind_param("si", $subitem_to_update, $id_to_update);
            $stmt->execute();

            if ($stmt->errno) {
                error_log("PUT - Execute failed: (" . $stmt->errno . ") " . $stmt->error);
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Gagal memperbarui subitem karena masalah database.'
                ]);
                exit();
            }

            if ($stmt->affected_rows === 0) {
                http_response_code(200); // OK
                echo json_encode([
                    'success' => true,
                    'message' => 'Subitem ditemukan, tetapi tidak ada perubahan yang dibuat.'
                ]);
            } else {
                http_response_code(200); // OK
                echo json_encode([
                    'success' => true,
                    'message' => 'Subitem berhasil diperbarui.'
                ]);

                // --- KIRIM LOG AKTIVITAS (UPDATE) ---
                $log_data = [
                    'action_type' => 'UPDATE',
                    'table_name' => 'subitems',
                    'record_id' => $id_to_update,
                    'data_old' => $old_data, // Data lama sebelum update
                    'data_new' => $input, // Data baru yang diupdate
                    'notes' => "Subitem ID {$id_to_update} ('" . ($old_data['subitem'] ?? 'N/A') . "' menjadi '{$subitem_to_update}') berhasil diperbarui oleh '{$currentUser}'.",
                    'id_user' => $currentUserId
                ];

                $ch = curl_init($logHandlerUrl);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($log_data),
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                    CURLOPT_TIMEOUT_MS => 500,
                    CURLOPT_NOSIGNAL => 1
                ]);
                $log_response = curl_exec($ch);
                $log_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $log_curl_error = curl_error($ch);
                curl_close($ch);

                if ($log_http_code !== 201) {
                    error_log("Gagal mencatat log aktivitas untuk update subitem ID {$id_to_update}. HTTP Code: " . $log_http_code . " - Response: " . $log_response . " - cURL Error: " . $log_curl_error);
                }
                // --- END KIRIM LOG AKTIVITAS ---
            }
            $stmt->close(); // Tutup prepared statement
            break;


        default:
            // Metode HTTP tidak diizinkan
            http_response_code(405); // Method Not Allowed
            echo json_encode(['error' => 'Metode tidak diizinkan.']);
            break;
    }
} catch (Exception $e) {
    // Tangkap setiap pengecualian yang tidak terduga
    error_log("General API error: " . $e->getMessage()); // Log error untuk debugging
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan internal server.']);
} finally {
    // Pastikan koneksi database ditutup di blok finally
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
