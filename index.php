<?php
// Auto Redirect HTTP ke HTTPS jika belum menggunakan HTTPS
$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
);

if (!$isHttps) {
    $redirectUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('Location: ' . $redirectUrl, true, 301);
    exit();
}

session_name('chiller_app');
session_start(); // Start the session at the very beginning

if (isset($_SESSION['user_id'])) {
    header('Location: ./apps/'); // Sesuaikan path ini jika halaman login Anda ada di tempat lain
    exit(); // Penting: Hentikan eksekusi script setelah redirect
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Aplikasi</title>
    <script src="./apps/assets/js/tailwind.js"></script>

    <link rel="icon" type="image/x-icon" href="./apps/assets/img/icon.jpg">

    <!--<script src="./apps/assets/js/VirtualKeyboardModule.js"></script>-->
    <!--<link rel="stylesheet" href="./apps/assets/css/keyboard.css">-->

    <style>
        /* Custom CSS for fade-in animation on page load */
        @keyframes fadeInSlideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fadeInSlideUp {
            animation: fadeInSlideUp 0.8s ease-out forwards;
        }
    </style>
</head>

<body class="bg-gradient-to-r from-blue-500 to-red-500 min-h-screen flex items-center justify-center p-4">
    <main class="w-full max-w-md animate-fadeInSlideUp">
        <div class="bg-white p-8 rounded-lg shadow-2xl border border-gray-200">
            <h1 class="text-3xl font-bold text-center text-gray-800 mb-8">Login</h1>

            <form action="./apps/controller/auth/login.php" method="POST" class="space-y-6">
                <div>
                    <label for="username" class="block text-gray-700 text-sm font-semibold mb-2">Username</label>
                    <input
                        type="email"
                        id="username"
                        name="username"
                        class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200"
                        placeholder="Masukkan username Anda"
                        autocomplete="off"
                        required>
                </div>

                <div>
                    <label for="password" class="block text-gray-700 text-sm font-semibold mb-2">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200"
                        placeholder="Masukkan password Anda"
                        autocomplete="off"
                        required>
                </div>

                <?php if (isset($_SESSION['login_error'])): ?>
                    <div id="messageArea" class="text-center py-2 px-4 rounded-md bg-red-100 text-red-700" role="alert">
                        <?php echo htmlspecialchars($_SESSION['login_error']); ?>
                    </div>
                    <?php unset($_SESSION['login_error']); // Clear the error message after displaying 
                    ?>
                <?php elseif (isset($_SESSION['login_success'])): ?>
                    <div id="messageArea" class="text-center py-2 px-4 rounded-md bg-green-100 text-green-700" role="alert">
                        <?php echo htmlspecialchars($_SESSION['login_success']); ?>
                    </div>
                    <?php unset($_SESSION['login_success']); // Clear the success message after displaying 
                    ?>
                <?php endif; ?>

                <div>
                    <button
                        type="submit"
                        class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-75 transition duration-300 transform hover:scale-105">
                        Login
                    </button>
                </div>

                <div class="text-center text-sm text-gray-600">
                    Lupa password? <a href="#" class="text-blue-600 hover:underline">Klik di sini</a>
                </div>
            </form>
        </div>
    </main>
</body>

</html>