<?php
// Set zona waktu ke Asia/Jakarta (WIB)
date_default_timezone_set('Asia/Jakarta');

// Konfigurasi Database
$dbHost = 'localhost';
$dbUser = 'admin';
$dbPass = '0d4WudzS@Mg54lzy';
$dbName = 'chiller';

// Konfigurasi Backup
// Menggunakan $_SERVER['USERPROFILE'] untuk path Desktop yang dinamis
$desktopPath = getenv('USERPROFILE') . DIRECTORY_SEPARATOR . 'Desktop';
$backupDir = $desktopPath . DIRECTORY_SEPARATOR . 'backup_chiller' . DIRECTORY_SEPARATOR;
$maxBackups = 10; // Maksimal 10 file backup

$backupFileName = $dbName . '_' . date('Y-m-d_H-i-s') . '.sql'; // Format waktu akan menggunakan WIB
$backupFilePath = $backupDir . $backupFileName;
$logFilePath = $backupDir . 'backup_log.txt';

// --- Proses Backup ---

// 1. Pastikan folder backup ada, jika tidak, buat
if (!is_dir($backupDir)) {
    // Memberikan izin penuh untuk folder yang baru dibuat (penting di Windows)
    if (!mkdir($backupDir, 0777, true)) {
        logMessage("Error: Gagal membuat direktori backup: {$backupDir}", $logFilePath);
        exit(); // Hentikan script jika folder tidak bisa dibuat
    }
}

// 2. Perintah untuk menjalankan mysqldump
// Pastikan path ke mysqldump.exe benar.
$mysqldumpPath = 'C:\\xampp\\mysql\\bin\\mysqldump.exe'; // Sesuaikan jika berbeda
$command = "\"{$mysqldumpPath}\" --host={$dbHost} --user={$dbUser} --password={$dbPass} {$dbName} > \"{$backupFilePath}\"";

// 3. Eksekusi perintah backup
$output = [];
$returnVar = 0; // 0 menandakan sukses
exec($command, $output, $returnVar);

// 4. Cek hasil eksekusi backup
if ($returnVar === 0) {
    logMessage("Backup database '{$dbName}' berhasil dibuat: {$backupFileName}", $logFilePath);

    // 5. Jika backup sukses, baru hapus file lama
    cleanOldBackups($backupDir, $maxBackups, $logFilePath);
} else {
    logMessage("Error: Backup database '{$dbName}' GAGAL.", $logFilePath);
    logMessage("Output Error: " . implode("\n", $output), $logFilePath);
    // Jika gagal backup, file lama tidak akan dihapus sesuai permintaan
}

// --- Fungsi Pendukung ---

/**
 * Fungsi untuk menulis pesan ke file log.
 * @param string $message Pesan yang akan ditulis.
 * @param string $logFile Path ke file log.
 */
function logMessage($message, $logFile)
{
    // Timestamp akan otomatis menggunakan zona waktu yang sudah diset di awal script
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "{$timestamp} - {$message}\n", FILE_APPEND | LOCK_EX);
    // Echo ke console juga untuk debugging saat dijalankan manual
    echo "{$timestamp} - {$message}\n";
}

/**
 * Fungsi untuk menghapus file backup lama.
 * @param string $directory Path folder backup.
 * @param int $maxFiles Jumlah file backup maksimal yang dipertahankan.
 * @param string $logFile Path ke file log.
 */
function cleanOldBackups($directory, $maxFiles, $logFile)
{
    // Dapatkan semua file .sql dalam direktori
    $files = glob($directory . '*.sql');

    if (count($files) > $maxFiles) {
        // Urutkan file berdasarkan waktu modifikasi (terlama ke terbaru)
        // File yang lebih lama akan berada di awal array
        usort($files, function ($a, $b) {
            return filemtime($a) <=> filemtime($b);
        });

        // Hitung berapa banyak file yang perlu dihapus
        $filesToDeleteCount = count($files) - $maxFiles;

        for ($i = 0; $i < $filesToDeleteCount; $i++) {
            $fileToDelete = $files[$i];
            if (unlink($fileToDelete)) {
                logMessage("Deleted old backup: " . basename($fileToDelete), $logFile);
            } else {
                logMessage("Error: Gagal menghapus file lama: " . basename($fileToDelete), $logFile);
            }
        }
    } else {
        logMessage("Jumlah backup saat ini (" . count($files) . ") kurang dari atau sama dengan batas maksimal ({$maxFiles}). Tidak ada file yang dihapus.", $logFile);
    }
}
