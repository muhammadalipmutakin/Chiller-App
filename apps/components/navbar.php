<?php
// IMPORTANT: session_start() (and session_name() if used)
// MUST be called at the very beginning of your main application entry point (e.g., index.php)
// before any HTML is sent to the browser and before this navbar file is included.
// If you are including this file without a prior session_start(), you MUST uncomment it here.

// session_name('chiller_app'); // Uncomment if a specific session name is used and not set globally
// session_start();             // Uncomment ONLY if session_start() isn't called before this include.

// Get user data from session, with a safe fallback
$displayNamaSetting = htmlspecialchars($_SESSION['user_nama'] ?? 'Pengguna');
?>

<nav class="bg-white shadow fixed top-0 w-full z-10">
    <div class="container mx-auto px-4 py-3 flex justify-between items-center">
        <div class="flex items-center ml-20">
            <div><img src="../apps/assets/img/logo.png" alt="Logo" class="md:w-full w-32"></div>
        </div>
        <div class="flex items-center space-x-2 sm:space-x-4">

            <div class="text-gray-700 text-sm sm:text-base">
                Selamat datang, <span class="font-semibold text-blue-600">
                    <?php echo $displayNamaSetting; ?>
                </span>
            </div>

            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button @click="open = !open"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-1.5 px-2 sm:py-2 sm:px-3 rounded-full inline-flex items-center shadow-sm transition duration-200">
                    <span class="text-sm sm:text-base">&#9660;</span>
                </button>
                <ul x-show="open" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                    class="absolute right-0 mt-2 w-40 sm:w-48 bg-white rounded-md shadow-lg overflow-hidden z-20 origin-top-right">
                    <li>
                        <a href="?page=setting"
                            class="block px-3 py-1.5 text-sm sm:px-4 sm:py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition duration-150 ease-in-out">
                            <i class="fas fa-cog mr-1"></i> Pengaturan Akun
                        </a>
                    </li>
                    <li>
                        <a href="./controller/auth/logout.php"
                            class="block px-3 py-1.5 text-sm sm:px-4 sm:py-2 text-red-500 hover:bg-red-50 hover:text-red-700 transition duration-150 ease-in-out">
                            <i class="fas fa-sign-out-alt mr-1"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>