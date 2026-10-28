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

// Handle CORS preflight (OPTIONS request)
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    switch ($method) {
        case 'GET':
            // Mengambil semua user atau user berdasarkan ID
            $sql = "SELECT id, nama, role, username FROM users" . ($id_from_get ? " WHERE id = ?" : "");
            $stmt = $conn->prepare($sql);

            if ($stmt === false) {
                error_log("GET - Failed to prepare statement: " . $conn->error);
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan server saat menyiapkan kueri.']);
                exit();
            }

            if ($id_from_get) {
                if (!filter_var($id_from_get, FILTER_VALIDATE_INT)) {
                    http_response_code(400); // Bad Request
                    echo json_encode([
                        'success' => false,
                        'error' => 'ID untuk GET harus berupa bilangan bulat yang valid.'
                    ]);
                    exit();
                }
                $stmt->bind_param("i", $id_from_get);
            }

            $stmt->execute();
            if ($stmt->errno) {
                error_log("GET - Execute failed: (" . $stmt->errno . ") " . $stmt->error);
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Gagal mengambil data pengguna karena masalah database.']);
                exit();
            }

            $result = $stmt->get_result();

            if ($result === false) {
                error_log("GET - Failed to get result: " . $stmt->error);
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan server saat mengambil hasil data.']);
                exit();
            }

            $user = $result->fetch_all(MYSQLI_ASSOC);

            if ($id_from_get && empty($user)) {
                http_response_code(404); // Not Found
                echo json_encode([
                    'success' => false,
                    'message' => 'Pengguna tidak ditemukan.'
                ]);
            } else {
                http_response_code(200); // OK
                echo json_encode([
                    'success' => true,
                    'data' => $user
                ]);
            }
            $stmt->close();
            break;


        case 'DELETE':
            // Menghapus user berdasarkan ID dari URL parameter
            if (!$id_from_get || !filter_var($id_from_get, FILTER_VALIDATE_INT)) {
                http_response_code(400); // Bad Request
                echo json_encode([
                    'success' => false,
                    'error' => 'ID diperlukan dan harus berupa bilangan bulat untuk penghapusan pengguna.'
                ]);
                exit();
            }

            // --- Ambil data lama sebelum dihapus untuk log ---
            $old_data = null;
            $select_stmt = $conn->prepare("SELECT id, nama, role, username FROM users WHERE id = ?");
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

            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt === false) {
                error_log("DELETE - Failed to prepare statement: " . $conn->error);
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan server saat menyiapkan penghapusan pengguna.']);
                exit();
            }

            $stmt->bind_param("i", $id_from_get);
            $stmt->execute();

            if ($stmt->errno) {
                error_log("DELETE - Execute failed: (" . $stmt->errno . ") " . $stmt->error);
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Gagal menghapus pengguna karena masalah database.'
                ]);
                exit();
            }

            if ($stmt->affected_rows > 0) {
                http_response_code(200); // OK
                echo json_encode([
                    'success' => true,
                    'message' => 'Pengguna berhasil dihapus.'
                ]);

                // --- KIRIM LOG AKTIVITAS (DELETE) ---
                $log_data = [
                    'action_type' => 'DELETE',
                    'table_name' => 'users',
                    'record_id' => $id_from_get,
                    'data_old' => $old_data, // Data lama sebelum dihapus
                    'data_new' => null, // Operasi DELETE
                    'notes' => "Pengguna ID {$id_from_get} ('" . ($old_data['username'] ?? 'N/A') . "') berhasil dihapus oleh '{$currentUser}'.",
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
                    error_log("Gagal mencatat log aktivitas untuk delete user ID {$id_from_get}. HTTP Code: " . $log_http_code . " - Response: " . $log_response . " - cURL Error: " . $log_curl_error);
                }
                // --- END KIRIM LOG AKTIVITAS ---

            } else {
                http_response_code(200); // OK
                echo json_encode([
                    'success' => false,
                    'message' => 'Tidak ada pengguna ditemukan dengan ID tersebut.'
                ]);
            }
            $stmt->close();
            break;

        case 'POST':
            // Menambah user baru
            $input = json_decode(file_get_contents('php://input'), true);

            // Validasi input 'nama', 'role', 'username', dan 'password'
            if (!isset($input['nama']) || trim($input['nama']) === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Nama pengguna diperlukan.']);
                exit();
            }
            if (!isset($input['role']) || trim($input['role']) === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Role pengguna diperlukan.']);
                exit();
            }
            if (!isset($input['username']) || trim($input['username']) === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Username diperlukan.']);
                exit();
            }
            // Password diperlukan untuk POST (pembuatan baru)
            if (!isset($input['password']) || trim($input['password']) === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Password diperlukan untuk pengguna baru.']);
                exit();
            }

            $nama = trim($input['nama']);
            $role = trim($input['role']);
            $username = trim($input['username']);
            $password = trim($input['password']); // Menyimpan password dalam plain text (SANGAT TIDAK AMAN)

            $stmt = $conn->prepare("INSERT INTO users (nama, role, username, password) VALUES (?, ?, ?, ?)");
            if ($stmt === false) {
                error_log("POST - Failed to prepare statement: " . $conn->error);
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan server saat menyiapkan penambahan pengguna.']);
                exit();
            }

            $stmt->bind_param("ssss", $nama, $role, $username, $password);
            $stmt->execute();

            if ($stmt->errno) {
                error_log("POST - Execute failed: (" . $stmt->errno . ") " . $stmt->error);
                // Handle duplicate username error (MySQL error code 1062)
                if ($stmt->errno == 1062) {
                    http_response_code(409); // Conflict
                    echo json_encode(['success' => false, 'error' => 'Username sudah ada. Harap pilih username lain.']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'Gagal menambahkan pengguna karena masalah database.']);
                }
                exit();
            }

            if ($stmt->affected_rows === 0) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Gagal membuat pengguna atau tidak ada baris yang terpengaruh (kesalahan tidak terduga).'
                ]);
            } else {
                $new_user_id = $stmt->insert_id;
                http_response_code(201); // Created
                echo json_encode([
                    'success' => true,
                    'message' => 'Pengguna berhasil ditambahkan.',
                    'id' => $new_user_id
                ]);

                // --- BLOK LOG AKTIVITAS DIHAPUS ---
            }
            $stmt->close();
            break;

        case 'PUT':
            // Memperbarui user yang sudah ada
            $input = json_decode(file_get_contents('php://input'), true);

            // Validasi ID
            if (!isset($input['id']) || !filter_var($input['id'], FILTER_VALIDATE_INT)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'ID pengguna diperlukan dan harus berupa bilangan bulat untuk pembaruan.'
                ]);
                exit();
            }
            $id_to_update = $input['id'];

            // --- Ambil data lama sebelum diupdate untuk log ---
            $old_data = null;
            $select_stmt_old = $conn->prepare("SELECT id, nama, role, username FROM users WHERE id = ?");
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

            // Validasi field 'nama', 'role', 'username' (ini wajib)
            if (!isset($input['nama']) || trim($input['nama']) === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Nama pengguna diperlukan.']);
                exit();
            }
            if (!isset($input['role']) || trim($input['role']) === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Role pengguna diperlukan.']);
                exit();
            }
            if (!isset($input['username']) || trim($input['username']) === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Username diperlukan.']);
                exit();
            }

            $nama_to_update = trim($input['nama']);
            $role_to_update = trim($input['role']);
            $username_to_update = trim($input['username']);
            $password_to_update = $input['password'] ?? null;

            $query_parts = [];
            $bind_params = [];
            $bind_types = '';

            // Hanya tambahkan password ke query jika dikirim dan tidak kosong
            if ($password_to_update !== null && trim($password_to_update) !== '') {
                $query_parts[] = "nama = ?, role = ?, username = ?, password = ?";
                $bind_params[] = $nama_to_update;
                $bind_params[] = $role_to_update;
                $bind_params[] = $username_to_update;
                $bind_params[] = trim($password_to_update); // Menyimpan password dalam plain text (SANGAT TIDAK AMAN)
                $bind_types = "ssss"; // 4 string
            } else {
                $query_parts[] = "nama = ?, role = ?, username = ?";
                $bind_params[] = $nama_to_update;
                $bind_params[] = $role_to_update;
                $bind_params[] = $username_to_update;
                $bind_types = "sss"; // 3 strings
            }

            $sql = "UPDATE users SET " . implode(", ", $query_parts) . " WHERE id = ?";
            $stmt = $conn->prepare($sql);

            if ($stmt === false) {
                error_log("PUT - Failed to prepare statement: " . $conn->error);
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Terjadi kesalahan server saat menyiapkan pembaruan pengguna.'
                ]);
                exit();
            }

            // Tambahkan ID ke parameter binding setelah parameter lainnya
            $bind_params[] = $id_to_update;
            $bind_types .= "i"; // Tambahkan 'i' untuk ID (integer)

            // Dynamic binding parameter
            $stmt->bind_param($bind_types, ...$bind_params);

            $stmt->execute();

            if ($stmt->errno) {
                error_log("PUT - Execute failed: (" . $stmt->errno . ") " . $stmt->error);
                // Handle duplicate username error on update
                if ($stmt->errno == 1062) {
                    http_response_code(409); // Conflict
                    echo json_encode(['success' => false, 'error' => 'Username sudah ada untuk pengguna lain. Harap pilih username lain.']);
                } else {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Gagal memperbarui pengguna karena masalah database.'
                    ]);
                }
                exit();
            }

            if ($stmt->affected_rows === 0) {
                http_response_code(200); // OK
                echo json_encode([
                    'success' => true,
                    'message' => 'Pengguna ditemukan, tetapi tidak ada perubahan yang dibuat atau ID tidak valid.'
                ]);
            } else {
                http_response_code(200); // OK
                echo json_encode([
                    'success' => true,
                    'message' => 'Pengguna berhasil diperbarui.'
                ]);

                // --- KIRIM LOG AKTIVITAS (UPDATE) ---
                // Buat salinan input tanpa password untuk log data_new
                $log_data_new = $input;

                $log_data = [
                    'action_type' => 'UPDATE',
                    'table_name' => 'users',
                    'record_id' => $id_to_update,
                    'data_old' => $old_data, // Data lama sebelum update
                    'data_new' => $log_data_new,
                    'notes' => "Pengguna ID {$id_to_update} ('" . ($old_data['username'] ?? 'N/A') . "') berhasil diperbarui oleh '{$currentUser}'.",
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
                    error_log("Gagal mencatat log aktivitas untuk update user ID {$id_to_update}. HTTP Code: " . $log_http_code . " - Response: " . $log_response . " - cURL Error: " . $log_curl_error);
                }
                // --- END KIRIM LOG AKTIVITAS ---
            }
            $stmt->close();
            break;

        default:
            // Metode HTTP tidak diizinkan
            http_response_code(405); // Method Not Allowed
            echo json_encode(['error' => 'Metode tidak diizinkan.']);
            break;
    }
} catch (Exception $e) {
    // Tangkap setiap pengecualian yang tidak terduga
    error_log("General API error: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan internal server.']);
} finally {
    // Pastikan koneksi database ditutup di blok finally
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
