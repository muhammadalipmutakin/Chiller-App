<?php
// controller/handler/log_activity_handler.php

require '../../controller/db/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak diizinkan. Hanya POST yang diterima.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Input JSON tidak valid atau kosong.']);
    exit();
}

$id_user = $input['id_user'] ?? null;


// Validasi dan konversi id_user ke integer. Default ke 0 jika tidak valid.
if ($id_user !== null && filter_var($id_user, FILTER_VALIDATE_INT) !== false) {
    $id_user = (int)$id_user;
} else {
    // Catat error jika id_user tidak valid atau tidak ada
    error_log("Log Activity Handler: User ID is not set or not a valid integer from POST or SESSION. Defaulting id_user to 0. Value: " . var_export($id_user, true));
    $id_user = 0; // Default ke 0 jika tidak set atau bukan integer valid
}
// --- AKHIR PENGAMBILAN ID USER YANG DIPERBAIKI ---


$action_type = $input['action_type'] ?? null;
$table_name = $input['table_name'] ?? null;
$record_id = $input['record_id'] ?? null;
$data_old = $input['data_old'] ?? null;
$data_new = $input['data_new'] ?? null;
$notes = $input['notes'] ?? null;

// Pastikan data_old dan data_new di-encode ke JSON jika berupa array/objek
if (is_array($data_old) || is_object($data_old)) {
    $data_old = json_encode($data_old);
}
if (is_array($data_new) || is_object($data_new)) {
    $data_new = json_encode($data_new);
}

// Validasi dasar
if (empty($action_type) || empty($table_name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tipe aksi dan nama tabel wajib diisi.']);
    exit();
}

$conn->begin_transaction();

try {
    $sql = "INSERT INTO log_activity (id_user, action_type, table_name, record_id, data_old, data_new, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        throw new Exception("Gagal menyiapkan statement: " . $conn->error);
    }

    // Tipe parameter: 'i' untuk id_user (integer), 's' untuk sisanya (string)
    $param_types = 'issssss';

    $record_id_for_bind = $record_id;
    if (is_array($record_id_for_bind)) {
        // Jika record_id adalah array (misal untuk multi-insert), gabungkan menjadi string
        $record_id_for_bind = implode(',', $record_id_for_bind);
    }
    // Jika record_id kosong atau null (setelah konversi dari array), set ke null untuk database
    if (empty($record_id_for_bind) && $record_id_for_bind !== 0 && $record_id_for_bind !== '0') {
        $record_id_for_bind = null;
    }

    $stmt->bind_param(
        $param_types,
        $id_user, // id_user sekarang akan diisi dari POST atau sesi
        $action_type,
        $table_name,
        $record_id_for_bind,
        $data_old,
        $data_new,
        $notes
    );

    if ($stmt->execute()) {
        $conn->commit();
        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Log aktivitas berhasil disimpan.']);
    } else {
        throw new Exception("Gagal mengeksekusi statement: " . $stmt->error);
    }
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error menyimpan log aktivitas: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan log aktivitas: ' . $e->getMessage()]);
} finally {
    if (isset($stmt) && $stmt !== false) {
        $stmt->close();
    }
    if ($conn && !$conn->connect_error) {
        $conn->close();
    }
}
