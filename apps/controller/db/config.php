<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'chiller-app';

// Buat koneksi MySQLi
$conn = new mysqli($host, $user, $pass, $db);

// Periksa koneksi
if ($conn->connect_error) {
    // Log error, tetapi jangan tampilkan detail sensitif ke pengguna
    error_log("Koneksi database gagal: " . $conn->connect_error);
    // Anda bisa menghentikan eksekusi atau mengarahkan ke halaman error
    die("Koneksi database gagal. Silakan coba lagi nanti.");
}

// Opsional: Atur charset
$conn->set_charset("utf8mb4");

// Variabel $conn sekarang tersedia untuk file yang meng-include config.php
