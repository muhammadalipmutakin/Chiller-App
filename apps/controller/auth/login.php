<?php
ini_set('session.cookie_lifetime', 86400);
ini_set('session.gc_maxlifetime', 86400);

// Set a unique session name for your application
session_name('chiller_app');
session_start();

// Sertakan file konfigurasi database
require '../db/config.php';

// Pastikan koneksi database tersedia
if (!isset($conn) || $conn === null) {
    $_SESSION['login_error'] = 'Kesalahan koneksi database. Mohon coba lagi nanti.';
    header('Location: ../../index.php'); // Redirect ke halaman login utama
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validasi input
    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = 'Username dan password harus diisi.';
        header('Location: ../../index.php'); // Kembali ke halaman login
        exit();
    }

    // Query untuk mencari user
    $stmt = $conn->prepare("SELECT id, username, password, nama, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // **PENTING: Ganti ini dengan password_verify() untuk keamanan!**
        // Ini adalah contoh untuk plaintext (TIDAK AMAN UNTUK PRODUKSI)
        if ($password === $user['password']) {
            // Login berhasil
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['login_success'] = 'Login Berhasil! Selamat datang, ' . htmlspecialchars($user['nama']) . '.';

            // Regenerate session ID to prevent Session Fixation attacks
            session_regenerate_id(true);

            header('Location: ../../'); // Redirect ke dashboard setelah login sukses
            exit();
        } else {
            // Password salah
            $_SESSION['login_error'] = 'Username atau password salah.'; // Pesan yang lebih umum untuk keamanan
            header('Location: ../../index.php'); // Kembali ke halaman login
            exit();
        }
    } else {
        // Username tidak ditemukan
        $_SESSION['login_error'] = 'Username atau password salah.'; // Pesan yang lebih umum untuk keamanan
        header('Location: ../../index.php'); // Kembali ke halaman login
        exit();
    }

    $stmt->close();
} else {
    // Jika diakses langsung tanpa POST request
    header('Location: ../../index.php');
    exit();
}

// Tutup koneksi database
$conn->close();
