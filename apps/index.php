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
session_start(); // Baris ini HARUS ada di paling atas setiap file yang menggunakan session

// Cek apakah user_id ada di session. Ini adalah indikator bahwa pengguna sudah login.
// Jika tidak ada, berarti pengguna belum login atau session sudah habis.
if (!isset($_SESSION['user_id'])) {
    header('Location: ../'); // Sesuaikan path ini jika halaman login Anda ada di tempat lain
    exit(); // Penting: Hentikan eksekusi script setelah redirect
}

// Ambil data pengguna dari session untuk kemudahan akses di dalam file ini dan komponen yang di-include
$loggedInUsername = $_SESSION['username'] ?? 'Tamu';
$loggedInNama = $_SESSION['user_nama'] ?? 'Pengguna';
$loggedInRole = $_SESSION['user_role'] ?? 'Tidak Diketahui';

// Anda bisa menambahkan logic pengecekan role di sini jika ada halaman yang hanya boleh diakses role tertentu.
// Contoh:
// if ($loggedInRole !== 'admin' && ($page === 'pages/admin/dashboard.php' || strpos($page, 'admin/') !== false)) {
//     header('Location: pages/unauthorized.php'); // Redirect ke halaman tidak berizin
//     exit();
// }

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pencatatan Chiller || Citraguna Lestari</title>

    <link rel="icon" type="image/x-icon" href="./assets/img/icon.jpg">

    <script src="./assets/js/tailwind.js"></script>
    <script defer src="./assets/js/alpine.js"></script>

    <link rel="stylesheet" href="./assets/css/select2.min.css" />
    <link rel="stylesheet" href="./assets/css/all.min.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">


    <script src="./assets/js/sweetalert2@11.js"></script>

    <script src="./assets/js/jquery-3.7.1.min.js"></script>
    <script src="./assets/js/select2.min.js"></script>
    <script src="./assets/js/chart.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6',
                        secondary: '#6b7280',
                        accent: '#10b981',
                    },
                },
            },
        }
    </script>
    <style>
        /* Base styles for sidebar and content */
        #sidebarToggle {
            position: fixed;
            top: 20px;
            left: 20px;
            background-color: #f3f4f6;
            color: #6b7280;
            border: none;
            padding: 8px;
            border-radius: 5px;
            cursor: pointer;
            z-index: 40;
            /* Higher z-index to ensure it's above the overlay */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        #sidebar {
            width: 256px;
            transform: translateX(-256px);
            /* Start hidden by default */
            transition: transform 0.3s ease-in-out, width 0.3s ease-in-out;
            position: fixed;
            height: 100%;
            z-index: 30;
            /* Higher z-index than content */
            background-color: #ffffff;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            /* Add a subtle shadow when open */
        }

        #content {
            margin-left: 0;
            /* Start with no margin */
            transition: margin-left 0.3s ease-in-out, width 0.3s ease-in-out;
            width: 100%;
            /* Default to full width */
        }

        /* Overlay for small screens when sidebar is open */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 25;
            /* Between sidebar and content */
            display: none;
            /* Hidden by default */
        }

        body.sidebar-open .sidebar-overlay {
            display: block;
            /* Show when sidebar is open */
        }

        /* --- Small Screens (e.g., Mobile/Tablet) --- */
        @media (max-width: 767px) {
            body.sidebar-open #sidebar {
                transform: translateX(0);
                /* Sidebar slides in */
            }

            body.sidebar-closed #sidebar {
                transform: translateX(-256px);
                /* Sidebar remains hidden */
            }

            #content {
                margin-left: 0;
                /* Content never gets a margin from sidebar on small screens */
                width: 100%;
                /* Content always takes full width */
            }

            /* Adjust toggle button position when sidebar is open on small screens */
            body.sidebar-open #sidebarToggle {
                left: calc(256px + 20px);
                /* Move button when sidebar is open */
                transition: left 0.3s ease-in-out;
            }

            body.sidebar-closed #sidebarToggle {
                left: 20px;
                /* Default position when closed */
                transition: left 0.3s ease-in-out;
            }
        }

        /* --- Large Screens (e.g., Desktop) --- */
        @media (min-width: 768px) {
            body.sidebar-open #sidebar {
                transform: translateX(0);
                /* Sidebar is visible */
                width: 256px;
                /* Pastikan lebar tetap 256px saat terbuka */
            }

            body.sidebar-closed #sidebar {
                transform: translateX(-256px);
                /* Hidden but can be seen as "closed" */
            }

            body.sidebar-open #content {
                margin-left: 256px;
                /* Content pushes when sidebar is open */
                width: calc(100% - 256px);
                /* Konten menyempit sesuai sisa lebar */
            }

            body.sidebar-closed #content {
                margin-left: 0;
                /* Content goes full width when sidebar is closed */
                width: 100%;
                /* Konten meluas ke lebar penuh */
            }

            /* Toggle button stays fixed on large screens */
            #sidebarToggle {
                left: 20px;
                transition: none;
                /* No transition on left for large screens */
            }

            /* Hide overlay on large screens */
            .sidebar-overlay {
                display: none !important;
            }
        }
    </style>
    <?php if (file_exists('styles.php')) include 'styles.php'; ?>
</head>

<body class="bg-gray-100 font-sans antialiased sidebar-closed">
    <?php include 'components/navbar.php'; ?>

    <button id="sidebarToggle" onclick="toggleSidebar()">
        <i class="fas fa-bars w-6 h-6"></i>
    </button>

    <div class="flex pt-16 w-full">
        <?php include 'components/sidebar.php'; ?>

        <div id="sidebar-overlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

        <main id="content" class="flex-1 p-4 transition-all duration-300 max-w-full">
            <?php
            $page = $_GET['page'] ?? 'dashboard';

            $parts = explode('/', $page);

            $pagePath = 'pages/' . implode('/', $parts) . '/index.php';

            if (!file_exists($pagePath)) {
                $pagePath = 'pages/' . implode('/', $parts) . '.php';
            }

            if (file_exists($pagePath)) {
                include $pagePath;
            } else {
                echo "<p class='text-red-500'>Halaman tidak ditemukan: <code>$pagePath</code></p>";
            }
            ?>
        </main>
    </div>

    <script>
        const body = document.body;
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');
        const toggleBtn = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const breakpoint = 768; // Define your breakpoint for small vs large screens

        // Initialize isSidebarOpen based on screen size or localStorage
        let isSidebarOpen;

        function checkScreenWidth() {
            if (window.innerWidth < breakpoint) {
                // On small screens, sidebar is always closed by default
                isSidebarOpen = false;
                localStorage.removeItem('sidebarState'); // Clear previous state for small screens
            } else {
                // On large screens, load state from localStorage, default to open if not found
                const storedState = localStorage.getItem('sidebarState');
                isSidebarOpen = storedState === null ? true : storedState === 'open';
            }
            updateSidebarDisplay();
        }

        function toggleSidebar() {
            isSidebarOpen = !isSidebarOpen;
            updateSidebarDisplay();
            // Only save state to localStorage on large screens
            if (window.innerWidth >= breakpoint) {
                localStorage.setItem('sidebarState', isSidebarOpen ? 'open' : 'closed');
            } else {
                localStorage.removeItem('sidebarState'); // Ensure state is not saved for small screens
            }
        }

        function updateSidebarDisplay() {
            if (isSidebarOpen) {
                body.classList.add('sidebar-open');
                body.classList.remove('sidebar-closed');
                toggleBtn.innerHTML = '<i class="fas fa-chevron-left w-6 h-6"></i>'; // Close icon
            } else {
                body.classList.add('sidebar-closed');
                body.classList.remove('sidebar-open');
                toggleBtn.innerHTML = '<i class="fas fa-bars w-6 h-6"></i>'; // Open icon
            }

            // Adjust toggle button position for small screens only
            if (window.innerWidth < breakpoint) {
                toggleBtn.style.left = isSidebarOpen ? 'calc(256px + 20px)' : '20px';
            } else {
                toggleBtn.style.left = '20px'; // Keep fixed on large screens
            }
        }

        // --- Event Listeners ---
        document.addEventListener('DOMContentLoaded', () => {
            checkScreenWidth(); // Initial check on page load

            // Close sidebar when navigating on small screens
            const navLinks = sidebar.querySelectorAll('a'); // Get all links in the sidebar
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < breakpoint) {
                        isSidebarOpen = false; // Force close on small screens
                        updateSidebarDisplay();
                    }
                });
            });
        });

        window.addEventListener('resize', checkScreenWidth); // Re-check on window resize
    </script>
</body>

</html>