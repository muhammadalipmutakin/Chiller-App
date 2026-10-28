<?php
// Pastikan sesi dimulai jika diperlukan
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Include file koneksi database
require_once '../../controller/db/config.php'; // Sesuaikan path jika berbeda

try {
    // Pastikan koneksi database ($conn) tersedia dari config.php
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Koneksi database tidak tersedia.");
    }

    $action = $_GET['action'] ?? ''; // Menggunakan GET untuk mengambil data

    if ($action === 'get_download_history') {
        $data = [];
        $query = "SELECT id, created_at, created_by, jenis_dokumen, file FROM log_download ORDER BY created_at DESC";
        $result = $conn->query($query);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Tambahkan URL lengkap untuk file, asumsi tersimpan di uploads/file/so_chiller/
                $row['file_url'] = './uploads/file/so_chiller/' . urlencode($row['file']);
                $data[] = $row;
            }
            echo json_encode([
                'status' => 'success',
                'data' => $data
            ]);
        } else {
            throw new Exception("Gagal mengambil data: " . $conn->error);
        }
    } else {
        throw new Exception('Aksi tidak valid.');
    }
} catch (Exception $e) {
    error_log("Error in download_history_api.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    // Tutup koneksi database jika belum dikelola secara global di config.php
    if (isset($conn) && $conn instanceof mysqli) { // Pastikan $conn adalah objek mysqli sebelum ditutup
        $conn->close();
    }
}
