<?php
session_name('chiller_app');
session_start();

date_default_timezone_set('Asia/Jakarta');

header('Content-Type: application/json');

require_once '../../controller/db/config.php';

$logHandlerUrl = 'http://localhost/chiller/apps/controller/handler/log_activity_handler.php';
$currentUser = $_SESSION['user_nama'] ?? 'Unknown User';
$currentUserId = $_SESSION['user_id'] ?? 0;

// Lokasi penyimpanan file PDF
$uploadDir = '../../uploads/file/so_chiller/';

// Buat direktori jika belum ada
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

try {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_and_log_pdf') {
        if (!isset($_FILES['pdfFile']) || $_FILES['pdfFile']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File PDF tidak diterima atau terjadi kesalahan upload.');
        }

        $tempFilePathUploaded = $_FILES['pdfFile']['tmp_name'];
        $createdBy = $_POST['createdBy'] ?? 'Unknown User';
        $jenisDokumen = $_POST['jenis_dokumen'] ?? 'SO Chiller';

        // 1. Simpan file PDF ke lokasi sementara terlebih dahulu dengan nama unik sementara
        $uniqueTempFileName = uniqid('temp_upload_', true) . '.pdf';
        $tempTargetFilePath = $uploadDir . $uniqueTempFileName;

        if (!move_uploaded_file($tempFilePathUploaded, $tempTargetFilePath)) {
            throw new Exception('Gagal menyimpan file PDF sementara ke server.');
        }

        // 2. Simpan log ke database untuk mendapatkan ID
        $stmt = $conn->prepare("INSERT INTO log_download (created_at, created_by, jenis_dokumen, file) VALUES (NOW(), ?, ?, ?)");
        if ($stmt === false) {
            unlink($tempTargetFilePath);
            throw new Exception("Gagal menyiapkan statement: " . $conn->error);
        }

        $initialFileNameForLog = "placeholder_for_id";
        $stmt->bind_param("sss", $createdBy, $jenisDokumen, $initialFileNameForLog);

        if (!$stmt->execute()) {
            unlink($tempTargetFilePath);
            throw new Exception("Gagal mengeksekusi statement: " . $stmt->error);
        }

        $newLogId = $conn->insert_id;
        $stmt->close();

        // 3. Dapatkan created_at dalam format YYYYMMDD_HHMMSS
        $stmtGetTime = $conn->prepare("SELECT DATE_FORMAT(created_at, '%Y%m%d_%H%i%s') as formatted_time FROM log_download WHERE id = ?");
        $stmtGetTime->bind_param("i", $newLogId);
        $stmtGetTime->execute();
        $result = $stmtGetTime->get_result();
        $row = $result->fetch_assoc();
        $formattedTime = $row['formatted_time'];
        $stmtGetTime->close();

        // Bersihkan nama created_by untuk nama file (hapus spasi dan karakter khusus)
        $cleanCreatedBy = preg_replace('/[^a-zA-Z0-9]/', '_', $createdBy);
        $cleanJenisDokumen = preg_replace('/[^a-zA-Z0-9]/', '_', $jenisDokumen);

        // 4. Bentuk nama file final: id_created_at_created_by_jenis_dokumen.pdf
        $finalFileName = "{$newLogId}_{$formattedTime}_{$cleanCreatedBy}_{$cleanJenisDokumen}.pdf";
        $finalTargetFilePath = $uploadDir . $finalFileName;

        // 5. Ubah nama file sementara menjadi nama file final
        if (!rename($tempTargetFilePath, $finalTargetFilePath)) {
            error_log("Gagal rename file dari {$uniqueTempFileName} ke {$finalFileName}");
            if (file_exists($tempTargetFilePath)) {
                unlink($tempTargetFilePath);
            }
            $conn->query("DELETE FROM log_download WHERE id = {$newLogId}");
            throw new Exception('Gagal mengubah nama file PDF.');
        }

        // 6. Update nama file di log_download dengan nama file final
        $stmtUpdate = $conn->prepare("UPDATE log_download SET file = ? WHERE id = ?");
        if ($stmtUpdate === false) {
            error_log("Gagal menyiapkan statement UPDATE untuk log_download: " . $conn->error);
        } else {
            $stmtUpdate->bind_param("si", $finalFileName, $newLogId);
            if (!$stmtUpdate->execute()) {
                error_log("Gagal mengupdate nama file di log_download untuk ID {$newLogId}: " . $stmtUpdate->error);
            }
            $stmtUpdate->close();
        }

        // Kembalikan nama file yang akan digunakan untuk download
        echo json_encode([
            'status' => 'success',
            'message' => 'File PDF berhasil disimpan dan log dicatat.',
            'fileName' => $finalFileName // Nama file dalam format id_created_at_created_by_jenis_dokumen
        ]);

        // --- BLOK LOG AKTIVITAS DIHAPUS ---

    } else {
        throw new Exception('Aksi tidak valid.');
    }
} catch (Exception $e) {
    error_log("Application Error in download_report.php: " . $e->getMessage() . " on line " . $e->getLine() . " in " . $e->getFile());
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan server: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
