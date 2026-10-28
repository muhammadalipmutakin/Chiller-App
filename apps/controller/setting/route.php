<?php
header("Content-Type: application/json");
session_name('chiller_app');
session_start();
require_once '../../controller/db/config.php';

$logHandlerUrl = 'http://localhost/chiller/apps/controller/handler/log_activity_handler.php';
$currentUser = $_SESSION['user_nama'] ?? 'Unknown User';
$currentUserId = $_SESSION['user_id'] ?? 0;

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. User not logged in.']);
    exit();
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// --- Dynamically determine the base URL (kept as is from previous response) ---
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$scriptPath = $_SERVER['SCRIPT_NAME'];
$baseAppPath = dirname(dirname(dirname($scriptPath)));
if ($baseAppPath === DIRECTORY_SEPARATOR || $baseAppPath === '.') {
    $baseAppPath = '';
} else {
    $baseAppPath = rtrim($baseAppPath, '/') . '/';
}
$fullBaseUrl = $protocol . $host . $baseAppPath;

$uploadDir = __DIR__ . '/../../uploads/profile_photos/';
$publicPhotoPathSegment = 'uploads/profile_photos/';

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create upload directory. Check server permissions.']);
        exit();
    }
}

try {
    switch ($method) {
        case 'GET':
            $stmt = $conn->prepare("SELECT id, nama, role, username, photo FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $userData = $result->fetch_assoc();

            if ($userData) {
                if ($userData['photo']) {
                    if (file_exists($uploadDir . $userData['photo'])) {
                        $userData['photo_url'] = $fullBaseUrl . $publicPhotoPathSegment . $userData['photo'];
                    } else {
                        $userData['photo_url'] = $fullBaseUrl . './assets/img/avatar.png';
                        error_log("Missing photo file for user ID " . $userId . ": " . $uploadDir . $userData['photo'] . " (Database entry exists but file does not).");
                    }
                } else {
                    $userData['photo_url'] = $fullBaseUrl . './assets/img/avatar.png';
                }
                echo json_encode(['success' => true, 'data' => $userData]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'User not found.']);
            }
            break;

        case 'POST': // Used for updating user data and photo upload
            if (isset($_FILES['photo'])) {
                // --- Ambil data lama sebelum diupdate untuk log ---
                $oldPhotoData = null;
                $select_stmt_old = $conn->prepare("SELECT photo FROM users WHERE id = ?");
                if ($select_stmt_old) {
                    $select_stmt_old->bind_param("i", $userId);
                    $select_stmt_old->execute();
                    $result_old = $select_stmt_old->get_result();
                    if ($result_old && $result_old->num_rows > 0) {
                        $oldPhotoData = $result_old->fetch_assoc();
                    }
                    $select_stmt_old->close();
                } else {
                    error_log("POST (photo) - Failed to prepare select statement for old_data: " . $conn->error);
                }
                $oldPhotoFilename = $oldPhotoData['photo'] ?? null;
                // --- END: Ambil data lama ---

                $file = $_FILES['photo'];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception("File upload error: " . $file['error']);
                }

                $newFilename = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9_\-.]/", "", basename($file['name']));
                $newTargetFilePath = $uploadDir . $newFilename;
                $fileType = strtolower(pathinfo($newTargetFilePath, PATHINFO_EXTENSION));

                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array($fileType, $allowedTypes)) {
                    throw new Exception("Invalid file type. Only JPG, JPEG, PNG, GIF are allowed.");
                }
                if ($file['size'] > 5 * 1024 * 1024) {
                    throw new Exception("File is too large. Maximum 5MB allowed.");
                }

                if (move_uploaded_file($file['tmp_name'], $newTargetFilePath)) {
                    $stmt = $conn->prepare("UPDATE users SET photo = ? WHERE id = ?");
                    $stmt->bind_param("si", $newFilename, $userId);
                    $stmt->execute();

                    if ($stmt->affected_rows > 0) {
                        if ($oldPhotoFilename) {
                            $fullOldPhotoPath = $uploadDir . $oldPhotoFilename;
                            if (file_exists($fullOldPhotoPath) && $oldPhotoFilename !== $newFilename) {
                                if (unlink($fullOldPhotoPath)) {
                                    error_log("Successfully deleted old profile photo: " . $fullOldPhotoPath);
                                } else {
                                    error_log("Failed to delete old profile photo: " . $fullOldPhotoPath . " (Permissions issue or file locked?)");
                                }
                            } else if ($oldPhotoFilename === $newFilename) {
                                error_log("Old and new filenames are identical, skipping deletion for user " . $userId . ".");
                            } else {
                                error_log("Old photo file does not exist at " . $fullOldPhotoPath . ", skipping deletion for user " . $userId . ".");
                            }
                        } else {
                            error_log("No previous photo found in DB for user " . $userId . ", skipping deletion.");
                        }

                        echo json_encode(['success' => true, 'message' => 'Foto profil berhasil diperbarui.', 'photo_url' => $fullBaseUrl . $publicPhotoPathSegment . $newFilename]);

                        // --- KIRIM LOG AKTIVITAS (UPDATE PHOTO) ---
                        $log_data = [
                            'action_type' => 'UPDATE',
                            'table_name' => 'users',
                            'record_id' => $userId,
                            'data_old' => ['photo' => $oldPhotoFilename],
                            'data_new' => ['photo' => $newFilename],
                            'notes' => "Foto profil diperbarui untuk user '{$currentUser}'.",
                            'id_user' => $currentUserId // Mengirim ID user
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
                            error_log("Gagal mencatat log aktivitas untuk update foto user {$userId}. HTTP Code: " . $log_http_code . " - Response: " . $log_response . " - cURL Error: " . $log_curl_error);
                        }
                        // --- END KIRIM LOG AKTIVITAS ---

                    } else {
                        unlink($newTargetFilePath);
                        throw new Exception("Gagal memperbarui jalur foto di database.");
                    }
                } else {
                    throw new Exception("Gagal mengunggah foto. Periksa izin direktori dan konfigurasi PHP (upload_max_filesize, post_max_size).");
                }
            } else {
                // Handle general user data update (nama, username)
                $nama = $input['nama'] ?? null;
                $username = $input['username'] ?? null;

                if (!$nama && !$username) {
                    echo json_encode(['success' => false, 'message' => 'Tidak ada data yang diberikan untuk pembaruan.']);
                    exit();
                }

                // --- Ambil data lama sebelum diupdate untuk log ---
                $oldUserData = null;
                $select_stmt_old = $conn->prepare("SELECT nama, username FROM users WHERE id = ?");
                if ($select_stmt_old) {
                    $select_stmt_old->bind_param("i", $userId);
                    $select_stmt_old->execute();
                    $result_old = $select_stmt_old->get_result();
                    if ($result_old && $result_old->num_rows > 0) {
                        $oldUserData = $result_old->fetch_assoc();
                    }
                    $select_stmt_old->close();
                } else {
                    error_log("POST (data) - Failed to prepare select statement for old_data: " . $conn->error);
                }
                // --- END: Ambil data lama ---

                $setClauses = [];
                $params = [];
                $types = "";

                if ($nama !== null) {
                    $setClauses[] = "nama = ?";
                    $params[] = $nama;
                    $types .= "s";
                }
                if ($username !== null) {
                    $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                    $checkStmt->bind_param("si", $username, $userId);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    if ($checkResult->num_rows > 0) {
                        throw new Exception("Username sudah digunakan oleh pengguna lain.");
                    }

                    $setClauses[] = "username = ?";
                    $params[] = $username;
                    $types .= "s";
                }

                if (empty($setClauses)) {
                    echo json_encode(['success' => false, 'message' => 'Tidak ada perubahan yang dilakukan.']);
                    exit();
                }

                $sql = "UPDATE users SET " . implode(', ', $setClauses) . " WHERE id = ?";
                $params[] = $userId;
                $types .= "i";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    echo json_encode(['success' => true, 'message' => 'Data pengguna berhasil diperbarui.']);

                    // --- KIRIM LOG AKTIVITAS (UPDATE DATA) ---
                    $log_data = [
                        'action_type' => 'UPDATE',
                        'table_name' => 'users',
                        'record_id' => $userId,
                        'data_old' => $oldUserData,
                        'data_new' => ['nama' => $nama, 'username' => $username], // Data yang diupdate
                        'notes' => "Data pengguna (nama/username) diperbarui untuk user '{$currentUser}'.",
                        'id_user' => $currentUserId // Mengirim ID user
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
                        error_log("Gagal mencatat log aktivitas untuk update data user {$userId}. HTTP Code: " . $log_http_code . " - Response: " . $log_response . " - cURL Error: " . $log_curl_error);
                    }
                    // --- END KIRIM LOG AKTIVITAS ---

                } else {
                    echo json_encode(['success' => false, 'message' => 'Tidak ada perubahan yang dilakukan atau pengguna tidak ditemukan.']);
                }
            }
            break;

        case 'PUT': // Used for password change
            $input = json_decode(file_get_contents('php://input'), true);

            $currentPassword = $input['current_password'] ?? null;
            $newPassword = $input['new_password'] ?? null;
            $confirmNewPassword = $input['confirm_new_password'] ?? null;

            if (!$currentPassword || !$newPassword || !$confirmNewPassword) {
                throw new Exception("Semua bidang kata sandi diperlukan.");
            }

            if ($newPassword !== $confirmNewPassword) {
                throw new Exception("Kata sandi baru dan konfirmasi tidak cocok.");
            }

            // Fetch current password from DB for old_data log
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if (!$user) {
                throw new Exception("Pengguna tidak ditemukan.");
            }

            // --- VERIFIKASI PASSWORD SAAT INI (PLAIN TEXT) ---
            if ($currentPassword !== $user['password']) {
                throw new Exception("Kata sandi saat ini salah.");
            }
            // --- AKHIR VERIFIKASI PASSWORD SAAT INI ---

            // --- PERHATIAN SANGAT PENTING: PASSWORD BARU AKAN DISIMPAN SEBAGAI PLAIN TEXT ---
            $passwordToStore = $newPassword; // Menyimpan plain text password
            // --- AKHIR PERHATIAN ---

            // Update password di DB
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $passwordToStore, $userId);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Kata sandi berhasil diperbarui.']);

                // --- KIRIM LOG AKTIVITAS (UPDATE PASSWORD) ---
                $log_data = [
                    'action_type' => 'UPDATE',
                    'table_name' => 'users',
                    'record_id' => $userId,
                    'data_old' => ['password' => $user['password']], // Password lama (plain text)
                    'data_new' => ['password' => $newPassword], // Password baru (plain text)
                    'notes' => "Kata sandi user '{$currentUser}' berhasil diperbarui.",
                    'id_user' => $currentUserId // Mengirim ID user
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
                    error_log("Gagal mencatat log aktivitas untuk update password user {$userId}. HTTP Code: " . $log_http_code . " - Response: " . $log_response . " - cURL Error: " . $log_curl_error);
                }
                // --- END KIRIM LOG AKTIVITAS ---

            } else {
                throw new Exception("Gagal memperbarui kata sandi. Tidak ada perubahan yang dilakukan.");
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Metode tidak diizinkan.']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
