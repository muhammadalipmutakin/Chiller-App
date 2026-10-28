<?php
session_name('chiller_app');
session_start();


header('Content-Type: application/json');
require '../../controller/db/config.php';

$logHandlerUrl = 'http://localhost/chiller/apps/controller/handler/log_activity_handler.php';
$currentUser = $_SESSION['user_nama'] ?? 'Unknown User';
$currentUserId = $_SESSION['user_id'] ?? 0;

$method = $_SERVER['REQUEST_METHOD'];
$id_from_get = $_GET['id'] ?? null;

try {
    switch ($method) {
        case 'GET':
            $sql = "SELECT id, lokasi FROM locations" . ($id_from_get ? " WHERE id = ?" : "");
            $stmt = $conn->prepare($sql);

            if ($stmt === false) {
                // Log error lebih detail untuk debugging
                error_log("GET - Failed to prepare statement: " . $conn->error);
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan server saat menyiapkan kueri.']);
                exit();
            }

            if ($id_from_get) {
                $stmt->bind_param("i", $id_from_get);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            if ($result === false) {
                error_log("GET - Failed to get result: " . $stmt->error);
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan server saat mengambil data.']);
                exit();
            }

            $locations = $result->fetch_all(MYSQLI_ASSOC);

            if ($id_from_get && empty($locations)) {
                http_response_code(404); // Not Found
                echo json_encode([
                    'success' => false,
                    'message' => 'Lokasi tidak ditemukan.'
                ]);
            } else {
                http_response_code(200); // OK
                echo json_encode([
                    'success' => true,
                    'data' => $locations
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
            $select_stmt = $conn->prepare("SELECT id, lokasi FROM locations WHERE id = ?");
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

            $stmt = $conn->prepare("DELETE FROM locations WHERE id = ?");
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
                    'error' => 'Gagal menghapus lokasi karena masalah database.'
                ]);
                exit();
            }

            if ($stmt->affected_rows > 0) {
                http_response_code(200); // OK
                echo json_encode([
                    'success' => true,
                    'message' => 'Lokasi berhasil dihapus.'
                ]);

                // --- KIRIM LOG AKTIVITAS (DELETE) ---
                $log_data = [
                    'action_type' => 'DELETE',
                    'table_name' => 'locations',
                    'record_id' => $id_from_get,
                    'data_old' => $old_data, // Data lama sebelum dihapus
                    'data_new' => null, // Operasi DELETE
                    'notes' => "Lokasi ID {$id_from_get} ('" . ($old_data['lokasi'] ?? 'N/A') . "') berhasil dihapus oleh '{$currentUser}'.",
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
                    error_log("Gagal mencatat log aktivitas untuk delete lokasi ID {$id_from_get}. HTTP Code: " . $log_http_code . " - Response: " . $log_response . " - cURL Error: " . $log_curl_error);
                }
                // --- END KIRIM LOG AKTIVITAS ---

            } else {
                http_response_code(200); // OK
                echo json_encode([
                    'success' => false,
                    'message' => 'Tidak ada lokasi ditemukan atau perubahan yang dibuat.'
                ]);
            }
            $stmt->close();
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);

            // Validasi input 'lokasi'
            if (!isset($input['lokasi']) || trim($input['lokasi']) === '') {
                http_response_code(400); // Bad Request
                echo json_encode([
                    'success' => false,
                    'error' => 'Nama lokasi diperlukan.'
                ]);
                exit();
            }
            $lokasi = trim($input['lokasi']);

            $stmt = $conn->prepare("INSERT INTO locations (lokasi) VALUES (?)");
            if ($stmt === false) {
                error_log("POST - Failed to prepare statement: " . $conn->error);
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan server saat menyiapkan penambahan.']);
                exit();
            }

            $stmt->bind_param("s", $lokasi);
            $stmt->execute();

            if ($stmt->errno) {
                error_log("POST - Execute failed: (" . $stmt->errno . ") " . $stmt->error);
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Gagal menambahkan lokasi karena masalah database.'
                ]);
                exit();
            }

            if ($stmt->affected_rows === 0) {
                http_response_code(500); // Internal Server Error
                echo json_encode([
                    'success' => false,
                    'error' => 'Gagal membuat lokasi atau tidak ada baris yang terpengaruh.'
                ]);
            } else {
                $new_location_id = $stmt->insert_id;
                http_response_code(201); // Created
                echo json_encode([
                    'success' => true,
                    'message' => 'Lokasi berhasil ditambahkan.',
                    'id' => $new_location_id
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
                    'error' => 'ID lokasi diperlukan dan harus berupa bilangan bulat untuk pembaruan.'
                ]);
                exit();
            }
            $id_to_update = $input['id'];

            // --- Ambil data lama sebelum diupdate untuk log ---
            $old_data = null;
            $select_stmt_old = $conn->prepare("SELECT id, lokasi FROM locations WHERE id = ?");
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

            // 2. Validasi field 'lokasi'
            if (!isset($input['lokasi']) || trim($input['lokasi']) === '') {
                http_response_code(400); // Bad Request
                echo json_encode([
                    'success' => false,
                    'error' => 'Nama lokasi diperlukan.'
                ]);
                exit();
            }
            $lokasi_to_update = trim($input['lokasi']);

            // Mulai operasi database
            $stmt = $conn->prepare("UPDATE locations SET lokasi = ? WHERE id = ?");
            if ($stmt === false) {
                error_log("PUT - Failed to prepare statement: " . $conn->error);
                http_response_code(500); // Internal Server Error
                echo json_encode([
                    'success' => false,
                    'error' => 'Terjadi kesalahan server saat menyiapkan pembaruan.'
                ]);
                exit();
            }

            $stmt->bind_param("si", $lokasi_to_update, $id_to_update);
            $stmt->execute();

            if ($stmt->errno) {
                error_log("PUT - Execute failed: (" . $stmt->errno . ") " . $stmt->error);
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Gagal memperbarui lokasi karena masalah database.'
                ]);
                exit();
            }

            if ($stmt->affected_rows === 0) {
                http_response_code(200); // OK
                echo json_encode([
                    'success' => true,
                    'message' => 'Lokasi ditemukan, tetapi tidak ada perubahan yang dibuat.'
                ]);
            } else {
                http_response_code(200); // OK
                echo json_encode([
                    'success' => true,
                    'message' => 'Lokasi berhasil diperbarui.'
                ]);

                // --- KIRIM LOG AKTIVITAS (UPDATE) ---
                $log_data = [
                    'action_type' => 'UPDATE',
                    'table_name' => 'locations',
                    'record_id' => $id_to_update,
                    'data_old' => $old_data, // Data lama sebelum update
                    'data_new' => $input, // Data baru yang diupdate
                    'notes' => "Lokasi ID {$id_to_update} ('" . ($old_data['lokasi'] ?? 'N/A') . "' menjadi '{$lokasi_to_update}') berhasil diperbarui oleh '{$currentUser}'.",
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
                    error_log("Gagal mencatat log aktivitas untuk update lokasi ID {$id_to_update}. HTTP Code: " . $log_http_code . " - Response: " . $log_response . " - cURL Error: " . $log_curl_error);
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
}

// Pastikan koneksi database ditutup
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
