<?php
session_name('chiller_app');
session_start();
header('Content-Type: application/json');

require '../../controller/db/config.php';

$logHandlerUrl = 'http://localhost/chiller/apps/controller/handler/log_activity_handler.php';
$currentUser = $_SESSION['user_nama'] ?? 'Unknown User';
$currentUserId = $_SESSION['user_id'] ?? 0;


$response = [
    'status' => 'error',
    'message' => 'Invalid request',
    'data' => []
];

$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_role'] ?? 'guest';

$action = $_GET['action'] ?? null;
$input = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $action;
}

if (!isset($conn) || !$conn) {
    $response['message'] = 'Database connection error. Please check db/config.php';
    echo json_encode($response);
    exit();
}

try {
    switch ($action) {
        case 'get_filter_options':
            $locations = [];
            $stmt = $conn->prepare("SELECT DISTINCT lokasi FROM locations WHERE lokasi LIKE 'chiller%' ORDER BY lokasi ASC");
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $locations[] = $row['lokasi'];
            }
            $stmt->close();

            $response['status'] = 'success';
            $response['message'] = 'Filter options fetched successfully';
            $response['data'] = [
                'locations' => $locations
            ];
            break;

        case 'get_adjustments':
            // Handle startDate: if null/empty, default to '2000-01-01'
            $startDate = $_GET['startDate'] ?? '';
            if (empty($startDate)) {
                $startDate = '2000-01-01';
            }

            // Handle endDate: if null/empty, default to '2050-12-31'
            $endDate = $_GET['endDate'] ?? '';
            if (empty($endDate)) {
                $endDate = '2050-12-31';
            }

            // Validasi tanggal hanya jika bukan nilai default, atau jika nilai default itu sendiri tidak valid formatnya
            if ($startDate !== '2000-01-01' && !strtotime($startDate)) {
                throw new Exception("Format tanggal awal tidak valid.");
            }
            if ($endDate !== '2050-12-31' && !strtotime($endDate)) {
                throw new Exception("Format tanggal akhir tidak valid.");
            }
            if (new DateTime($startDate) > new DateTime($endDate)) {
                throw new Exception("Tanggal akhir tidak boleh lebih kecil dari tanggal awal.");
            }

            $adjustments = [];

            // Query utama untuk mendapatkan transaksi adjustment
            $sql = "SELECT id, date, storage, item, ekor_pack, quantity_kg, adjustment_type, keterangan, user
                    FROM transactions
                    WHERE transaction_type = 'adjustment'
                    AND date BETWEEN ? AND ?
                    AND deleted_at IS NULL
                    ORDER BY date DESC, created_at DESC";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed for transactions: " . $conn->error);
            }
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();

            $transactionIds = [];
            while ($row = $result->fetch_assoc()) {
                // Konversi nilai numerik untuk konsistensi frontend
                $row['ekor_pack'] = (int)$row['ekor_pack'];
                $row['quantity_kg'] = (float)$row['quantity_kg']; // Biarkan sebagai float untuk presisi

                $adjustments[$row['id']] = $row; // Simpan berdasarkan ID transaksi
                $adjustments[$row['id']]['reasons'] = []; // Inisialisasi array reasons
                $transactionIds[] = $row['id'];
            }
            $stmt->close();

            // Jika ada transaksi adjustment, ambil alasannya
            if (!empty($transactionIds)) {
                $placeholders = implode(',', array_fill(0, count($transactionIds), '?'));
                $sqlReasons = "SELECT ra.id, ra.id_transaction, ra.ekor_pack, ra.quantity_kg, ra.reason, u.nama AS user_name
                               FROM reason_adjustment ra
                               JOIN users u ON ra.id_user = u.id
                               WHERE ra.id_transaction IN ($placeholders) AND ra.deleted_at IS NULL
                               ORDER BY ra.created_at ASC";

                $stmtReasons = $conn->prepare($sqlReasons);
                if (!$stmtReasons) {
                    throw new Exception("Prepare failed for reasons: " . $conn->error);
                }

                // Bind parameter secara dinamis untuk klausa IN
                $types = str_repeat('i', count($transactionIds));
                $stmtReasons->bind_param($types, ...$transactionIds);
                $stmtReasons->execute();
                $resultReasons = $stmtReasons->get_result();

                while ($reasonRow = $resultReasons->fetch_assoc()) {
                    // Konversi nilai numerik
                    $reasonRow['ekor_pack'] = (int)$reasonRow['ekor_pack'];
                    $reasonRow['quantity_kg'] = (float)round($reasonRow['quantity_kg'], 2); // Bulatkan 2 desimal
                    $adjustments[$reasonRow['id_transaction']]['reasons'][] = $reasonRow;
                }
                $stmtReasons->close();
            }

            // Konversi array asosiatif kembali ke array berindeks untuk frontend
            $response['status'] = 'success';
            $response['message'] = 'Adjustment data fetched successfully';
            $response['data'] = array_values($adjustments);
            break;

            $adjustmentId = $_GET['adjustment_id'] ?? null;
            if (!$adjustmentId) {
                throw new Exception('ID Adjustment diperlukan.');
            }
            $reasons = [];
            // Lakukan JOIN ke tabel users untuk mendapatkan nama pengguna
            $reasonStmt = $conn->prepare("SELECT ra.id, ra.id_transaction, ra.ekor_pack, ra.quantity_kg, ra.reason, u.nama AS user_name
                                          FROM reason_adjustment ra
                                          JOIN users u ON ra.id_user = u.id
                                          WHERE ra.id_transaction = ? AND ra.deleted_at IS NULL
                                          ORDER BY ra.created_at ASC");
            if (!$reasonStmt) {
                throw new Exception("Prepare failed for get_reasons_for_adjustment: " . $conn->error);
            }
            $reasonStmt->bind_param("i", $adjustmentId);
            $reasonStmt->execute();
            $resultReasons = $reasonStmt->get_result();
            while ($reasonRow = $resultReasons->fetch_assoc()) {
                $reasonRow['ekor_pack'] = (int)$reasonRow['ekor_pack'];
                $reasonRow['quantity_kg'] = (float)round($reasonRow['quantity_kg'], 2);
                $reasons[] = $reasonRow;
            }
            $reasonStmt->close();

            $response['status'] = 'success';
            $response['message'] = 'Reasons fetched successfully';
            $response['data'] = ['reasons' => $reasons];
            break;

        case 'add_reason':
            // Memeriksa izin pengguna
            if ($userRole !== 'admin' && $userRole !== 'superadmin') {
                throw new Exception('Akses tidak diizinkan. Hanya Admin/Superadmin yang bisa menambah alasan.');
            }
            // Pastikan user id ada
            if (!$currentUserId) { // Menggunakan $currentUserId yang sudah diambil dari sesi
                throw new Exception('Sesi pengguna tidak ditemukan.');
            }

            $adjustmentId = $input['adjustment_id'] ?? null;
            $ekorPack = $input['ekor_pack'] ?? null;
            $quantityKg = $input['quantity_kg'] ?? null;
            $reason = $input['reason'] ?? null;

            if (!$adjustmentId || $ekorPack === null || $quantityKg === null || !$reason) {
                throw new Exception('Data yang diperlukan tidak lengkap untuk menambah alasan.');
            }

            // Tambahkan id_user ke dalam query INSERT
            $stmt = $conn->prepare("INSERT INTO reason_adjustment (id_transaction, id_user, ekor_pack, quantity_kg, reason) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Prepare failed for add_reason: " . $conn->error);
            }
            // Tambahkan 'i' untuk id_user dan bind parameter
            $stmt->bind_param("iiids", $adjustmentId, $currentUserId, $ekorPack, $quantityKg, $reason);

            if ($stmt->execute()) {
                $newReasonId = $conn->insert_id; // Dapatkan ID yang baru di-insert
                $response['status'] = 'success';
                $response['message'] = 'Alasan berhasil ditambahkan.';

                // --- BLOK LOG AKTIVITAS DIHAPUS ---

            } else {
                throw new Exception('Gagal menambahkan alasan: ' . $stmt->error);
            }
            $stmt->close();
            break;

        case 'edit_reason':
            // Memeriksa izin pengguna
            if ($userRole !== 'admin' && $userRole !== 'superadmin') {
                throw new Exception('Akses tidak diizinkan. Hanya Admin/Superadmin yang bisa mengedit alasan.');
            }
            // Pastikan user id ada
            if (!$currentUserId) { // Menggunakan $currentUserId
                throw new Exception('Sesi pengguna tidak ditemukan.');
            }

            $reasonId = $input['reason_id'] ?? null;
            $ekorPack = $input['ekor_pack'] ?? null;
            $quantityKg = $input['quantity_kg'] ?? null;
            $reason = $input['reason'] ?? null;

            if (!$reasonId || $ekorPack === null || $quantityKg === null || !$reason) {
                throw new Exception('Data yang diperlukan tidak lengkap untuk mengedit alasan.');
            }

            // --- Ambil data lama sebelum diupdate untuk log ---
            $old_data = null;
            $select_stmt_old = $conn->prepare("SELECT id, id_transaction, id_user, ekor_pack, quantity_kg, reason FROM reason_adjustment WHERE id = ? AND deleted_at IS NULL");
            if ($select_stmt_old) {
                $select_stmt_old->bind_param("i", $reasonId);
                $select_stmt_old->execute();
                $result_old = $select_stmt_old->get_result();
                if ($result_old && $result_old->num_rows > 0) {
                    $old_data = $result_old->fetch_assoc();
                }
                $select_stmt_old->close();
            } else {
                error_log("EDIT REASON - Failed to prepare select statement for old_data: " . $conn->error);
            }
            // --- END: Ambil data lama ---

            // Tambahkan id_user ke dalam query UPDATE untuk melacak siapa yang terakhir mengedit
            $stmt = $conn->prepare("UPDATE reason_adjustment SET id_user = ?, ekor_pack = ?, quantity_kg = ?, reason = ? WHERE id = ? AND deleted_at IS NULL");
            if (!$stmt) {
                throw new Exception("Prepare failed for edit_reason: " . $conn->error);
            }
            // Tambahkan 'i' untuk id_user dan bind parameter
            $stmt->bind_param("iidsi", $currentUserId, $ekorPack, $quantityKg, $reason, $reasonId);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $response['status'] = 'success';
                    $response['message'] = 'Alasan berhasil diperbarui.';

                    // --- KIRIM LOG AKTIVITAS (UPDATE) ---
                    $log_data = [
                        'action_type' => 'UPDATE',
                        'table_name' => 'reason_adjustment',
                        'record_id' => $reasonId,
                        'data_old' => $old_data,
                        'data_new' => $input,
                        'notes' => "Alasan penyesuaian ID {$reasonId} diperbarui oleh user '{$currentUser}'.",
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
                        error_log("Gagal mencatat log aktivitas untuk edit reason. HTTP Code: " . $log_http_code . " - Response: " . $log_response . " - cURL Error: " . $log_curl_error);
                    }
                    // --- END KIRIM LOG AKTIVITAS ---

                } else {
                    // Ini bisa berarti ID tidak ditemukan atau tidak ada perubahan
                    $response['status'] = 'success'; // Treat as success if no rows affected (no actual change made)
                    $response['message'] = 'Alasan tidak ditemukan atau tidak ada perubahan yang dilakukan.';
                }
            } else {
                throw new Exception('Gagal memperbarui alasan: ' . $stmt->error);
            }
            $stmt->close();
            break;

        case 'delete_reason':
            // Memeriksa izin pengguna
            if ($userRole !== 'admin' && $userRole !== 'superadmin') {
                throw new Exception('Akses tidak diizinkan. Hanya Admin/Superadmin yang bisa menghapus alasan.');
            }

            $reasonId = $input['reason_id'] ?? null;

            if (!$reasonId) {
                throw new Exception('ID Alasan diperlukan untuk penghapusan.');
            }

            // --- Ambil data lama sebelum dihapus (soft delete) untuk log ---
            $old_data = null;
            $select_stmt = $conn->prepare("SELECT id, id_transaction, id_user, ekor_pack, quantity_kg, reason FROM reason_adjustment WHERE id = ? AND deleted_at IS NULL");
            if ($select_stmt) {
                $select_stmt->bind_param("i", $reasonId);
                $select_stmt->execute();
                $result = $select_stmt->get_result();
                if ($result && $result->num_rows > 0) {
                    $old_data = $result->fetch_assoc();
                }
                $select_stmt->close();
            } else {
                error_log("DELETE REASON - Failed to prepare select statement for old_data: " . $conn->error);
            }
            // --- END: Ambil data lama ---

            // Soft delete alasan dan catat siapa yang menghapus
            $stmt = $conn->prepare("UPDATE reason_adjustment SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL");
            if (!$stmt) {
                throw new Exception("Prepare failed for delete_reason: " . $conn->error);
            }
            $stmt->bind_param("i", $reasonId);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $response['status'] = 'success';
                    $response['message'] = 'Alasan berhasil dihapus.';

                    // --- KIRIM LOG AKTIVITAS (DELETE) ---
                    $log_data = [
                        'action_type' => 'DELETE',
                        'table_name' => 'reason_adjustment',
                        'record_id' => $reasonId,
                        'data_old' => $old_data, // Data lama sebelum dihapus
                        'data_new' => null, // Ini adalah operasi delete, jadi data_new null
                        'notes' => "Alasan penyesuaian ID {$reasonId} berhasil dihapus (soft delete) oleh user '{$currentUser}'.",
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
                        error_log("Gagal mencatat log aktivitas untuk delete reason. HTTP Code: " . $log_http_code . " - Response: " . $log_response . " - cURL Error: " . $log_curl_error);
                    }
                    // --- END KIRIM LOG AKTIVITAS ---

                } else {
                    throw new Exception('Alasan tidak ditemukan atau sudah dihapus.');
                }
            } else {
                throw new Exception('Gagal menghapus alasan: ' . $stmt->error);
            }
            $stmt->close();
            break;

        default:
            $response['message'] = 'Aksi tidak valid.';
            break;
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    // Untuk debugging, bisa tambahkan error_log($e->getMessage());
} finally {
    // Pastikan koneksi ditutup hanya jika sudah dibuka dan merupakan objek mysqli
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
    echo json_encode($response);
}
