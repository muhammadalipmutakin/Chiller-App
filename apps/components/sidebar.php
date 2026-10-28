<aside id="sidebar" class="bg-gray-200 fixed top-19 left-0 h-screen p-2 sm:p-4 shadow-md transition-all duration-300 sidebar-expanded">
    <div class="overflow-y-auto overflow-x-hidden h-full">
        <div class="p-2 border-b border-gray-300 mb-2 text-center">
            <?php
            // Pastikan session_start() sudah dipanggil di awal file utama yang menyertakan sidebar ini.
            // Jika tidak, Anda mungkin perlu mengaktifkannya di sini (tetapi sebaiknya di file utama).
            // session_name('chiller_app'); // Contoh jika nama sesi spesifik digunakan
            // session_start();            // Hanya aktifkan jika session_start() belum ada di file utama!

            $displayNamaSidebar = $_SESSION['user_nama'] ?? 'Pengguna Aplikasi';
            $displayRoleSidebar = $_SESSION['user_role'] ?? 'Guest'; // Pastikan 'user_role' disetel saat login
            ?>
            <p class="font-bold text-sm text-gray-800" x-text="userName ?? '<?= htmlspecialchars($displayNamaSidebar) ?>'"></p>
            <p class="text-xs text-gray-600" x-text="userRole ?? '<?= htmlspecialchars($displayRoleSidebar) ?>'"></p>
        </div>

        <ul class="space-y-1 sm:space-y-2">
            <li>
                <a href="?page=dashboard" class="flex items-center p-1.5 rounded-md text-sm <?= (!isset($_GET['page']) || $_GET['page'] === 'dashboard') ? 'bg-gray-300 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-300' ?>">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9.75L12 3l9 6.75v9.75a1.5 1.5 0 01-1.5 1.5H4.5A1.5 1.5 0 013 19.5V9.75z" />
                    </svg>
                    <span class="sidebar-expanded-item text-sm">Dashboard</span>
                </a>
            </li>

            <li>
                <a href="?page=stock" class="flex items-center p-1.5 rounded-md text-sm <?= (isset($_GET['page']) && $_GET['page'] === 'stock') ? 'bg-gray-300 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-300' ?>">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4a2 2 0 001-1.73z" />
                    </svg>
                    <span class="sidebar-expanded-item text-sm">Stock</span>
                </a>
            </li>

            <li>
                <a href="?page=stock_barcode" class="flex items-center p-1.5 rounded-md text-sm <?= (isset($_GET['page']) && $_GET['page'] === 'stock_barcode') ? 'bg-gray-300 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-300' ?>">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2l4 -4m1 -4h-2a2 2 0 00-4 0h-2a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002 -2V6a2 2 0 00-2 -2z" />
                    </svg>
                    <span class="sidebar-expanded-item text-sm">Stok Barcode</span>
                </a>
            </li>
            <li>
                <a href="?page=transaksi" class="flex items-center p-1.5 rounded-md text-sm <?= (isset($_GET['page']) && $_GET['page'] === 'transaksi') ? 'bg-gray-300 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-300' ?>">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 6H7a2 2 0 01-2-2V6a2 2 0 012-2h5l2 2h6a2 2 0 012 2v12a2 2 0 01-2 2z" />
                    </svg>
                    <span class="sidebar-expanded-item text-sm">Transaksi</span>
                </a>
            </li>

            <li class="relative">
                <?php
                $currentPage = $_GET['page'] ?? '';
                $isMasterActive = strpos($currentPage, 'master/') === 0;
                ?>
                <button
                    type="button"
                    class="flex items-center w-full p-1.5 rounded-md text-sm text-gray-700 hover:bg-gray-300 focus:outline-none"
                    aria-expanded="<?= $isMasterActive ? 'true' : 'false' ?>"
                    aria-controls="masterDropdown"
                    onclick="toggleSidebarDropdown('masterDropdown', 'arrowIconMaster')">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a8 3 0 0116 0v12a8 3 0 01-16 0V6z" />
                    </svg>
                    <span class="flex-1 text-left text-sm font-semibold <?= $isMasterActive ? 'text-primary' : '' ?>">Master Data</span>
                    <svg
                        id="arrowIconMaster"
                        class="w-3 h-3 sm:w-4 sm:h-4 transition-transform transform <?= $isMasterActive ? 'rotate-180' : '' ?>"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <ul id="masterDropdown" class="ml-4 mt-1 space-y-0.5 overflow-hidden transition-all duration-300 <?= $isMasterActive ? 'max-h-40' : 'max-h-0' ?>">
                    <li>
                        <a href="?page=master/item" class="block px-2 py-1 rounded-md text-xs sm:text-sm <?= $currentPage === 'master/item' ? 'bg-gray-300 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-300' ?>">Item</a>
                    </li>
                    <?php if ($displayRoleSidebar === 'superadmin' || $displayRoleSidebar === 'admin'): ?>
                        <li>
                            <a href="?page=master/subitem" class="block px-2 py-1 rounded-md text-xs sm:text-sm <?= $currentPage === 'master/subitem' ? 'bg-gray-300 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-300' ?>">Subitem</a>
                        </li>
                    <?php endif; ?>
                    <?php if ($displayRoleSidebar === 'superadmin'): ?>
                        <li>
                            <a href="?page=master/lokasi" class="block px-2 py-1 rounded-md text-xs sm:text-sm <?= $currentPage === 'master/lokasi' ? 'bg-gray-300 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-300' ?>">Lokasi</a>
                        </li>
                        <li>
                            <a href="?page=master/user" class="block px-2 py-1 rounded-md text-xs sm:text-sm <?= $currentPage === 'master/user' ? 'bg-gray-300 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-300' ?>">User</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </li>

            <li class="relative">
                <?php
                // Cek apakah halaman saat ini adalah bagian dari laporan atau halaman riwayat download
                $isReportOrHistoryActive = (strpos($currentPage, 'report/') === 0 || $currentPage === 'history/download_history' || $currentPage === 'report/adjustment' || $currentPage === 'report/reason_adjustment' || $currentPage === 'laporan-grading');
                ?>
                <button
                    type="button"
                    class="flex items-center w-full p-1.5 rounded-md text-sm text-gray-700 hover:bg-gray-300 focus:outline-none"
                    aria-expanded="<?= $isReportOrHistoryActive ? 'true' : 'false' ?>"
                    aria-controls="reportDropdown"
                    onclick="toggleSidebarDropdown('reportDropdown', 'arrowIconReport')">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-4m0-4V7m0 10H7m2 0h2m0-4H7m2 0h2m0-4H7m2 0h2m0 10h4m-4 0h-2m-4 0h-2a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v12a2 2 0 01-2 2h-2m-4 0h-2m-4 0H7a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v12a2 2 0 01-2 2z" />
                    </svg>
                    <span class="flex-1 text-left text-sm font-semibold <?= $isReportOrHistoryActive ? 'text-primary' : '' ?>">Laporan</span>
                    <svg
                        id="arrowIconReport"
                        class="w-3 h-3 sm:w-4 sm:h-4 transition-transform transform <?= $isReportOrHistoryActive ? 'rotate-180' : '' ?>"
                        fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <ul id="reportDropdown" class="ml-4 mt-1 space-y-0.5 overflow-hidden transition-all duration-300 <?= $isReportOrHistoryActive ? 'max-h-80' : 'max-h-0' ?>">
                    <li>
                        <a href="?page=report/daily" class="block px-2 py-1 rounded-md text-xs sm:text-sm <?= $currentPage === 'report/daily' ? 'bg-gray-300 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-300' ?>">Laporan Harian</a>
                    </li>
                    <li>
                        <a href="?page=laporan-grading" class="block px-2 py-1 rounded-md text-xs sm:text-sm <?= $currentPage === 'laporan-grading' ? 'bg-gray-300 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-300' ?>">
                            Laporan Grading
                        </a>
                    </li>
                    <li>
                        <a href="?page=report/daily/stok_chiller" class="block px-2 py-1 rounded-md text-xs sm:text-sm <?= $currentPage === 'report/daily/stok_chiller' ? 'bg-gray-300 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-300' ?>">
                            Stok Chiller
                        </a>
                    </li>
                    <li>
                        <a href="?page=report/adjustment" class="block px-2 py-1 rounded-md text-xs sm:text-sm <?= $currentPage === 'report/adjustment' ? 'bg-gray-300 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-300' ?>">
                            Laporan Adjustment
                        </a>
                    </li>
                    <li>
                        <a href="?page=report/adjustment/reason_adjustment" class="block px-2 py-1 rounded-md text-xs sm:text-sm <?= $currentPage === 'report/adjustment/reason_adjustment' ? 'bg-gray-300 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-300' ?>">
                            Reason Adjustment
                        </a>
                    </li>

                    <?php if ($displayRoleSidebar === 'superadmin'): ?>
                        <li>
                            <a href="?page=report/daily/download_history" class="block px-2 py-1 rounded-md text-xs sm:text-sm <?= $currentPage === 'report/daily/download_history' ? 'bg-gray-300 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-300' ?>">
                                Riwayat Download
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </li>

            <?php if ($displayRoleSidebar === 'superadmin'): ?>
                <li>
                    <a href="?page=opname" class="flex items-center p-1.5 rounded-md text-sm <?= (isset($_GET['page']) && $_GET['page'] === 'opname') ? 'bg-gray-300 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-300' ?>">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2l4 -4m1 -4h-2a2 2 0 00-4 0h-2a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002 -2V6a2 2 0 00-2 -2z" />
                        </svg>
                        <span class="sidebar-expanded-item text-sm">Stock Opname</span>
                    </a>
                </li>
                <li>
                    <a href="?page=adjustment" class="flex items-center p-1.5 rounded-md text-sm <?= (isset($_GET['page']) && $_GET['page'] === 'adjustment') ? 'bg-gray-300 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-300' ?>">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.25 14.25L7.5 16.5L10.5 13.5M15 14.25H12M15 17.25H12M21 12.75V12a3 3 0 00-3-3h-3.375a3 3 0 00-2.83 2.062L6.75 21l1.681-1.681A8.647 8.647 0 0112 18.75c2.305 0 4.498.814 6.321 2.222L21 21V12.75z" />
                        </svg>
                        <span class="sidebar-expanded-item text-sm">Adjustment</span>
                    </a>
                </li>
                <li>
                    <a href="?page=adjustment2" class="flex items-center p-1.5 rounded-md text-sm <?= (isset($_GET['page']) && $_GET['page'] === 'adjustment2') ? 'bg-gray-300 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-300' ?>">
                        <i class="fas fa-th w-4 h-4 sm:w-5 sm:h-5 mr-1.5"></i>
                        <span class="sidebar-expanded-item text-sm">Adjustment Barcode</span>
                    </a>
                </li>
            <?php endif; ?>

            <li>
                <a href="?page=tracing" class="flex items-center p-1.5 rounded-md text-sm <?= (isset($_GET['page']) && $_GET['page'] === 'tracing') ? 'bg-gray-300 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-300' ?>">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.132l-3.375-2.671a1 1 0 00-1.375.375l-4.25 6.375a1 1 0 00.375 1.375l3.375 2.671a1 1 0 001.375-.375l4.25-6.375a1 1 0 00-.375-1.375zM12 2a10 10 0 100 20 10 10 0 000-20zM12 18a6 6 0 100-12 6 6 0 000 12z" />
                    </svg>
                    <span class="sidebar-expanded-item text-sm">Tracing</span>
                </a>
            </li>

            <li>
                <a href="?page=setting" class="flex items-center p-1.5 rounded-md text-sm <?= (isset($_GET['page']) && $_GET['page'] === 'setting') ? 'bg-gray-300 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-300' ?>">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="sidebar-expanded-item text-sm">Settings</span>
                </a>
            </li>

            <?php if ($displayRoleSidebar === 'superadmin'): ?>
                <li>
                    <a href="?page=activity_log" class="flex items-center p-1.5 rounded-md text-sm <?= (isset($_GET['page']) && $_GET['page'] === 'activity_log') ? 'bg-gray-300 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-300' ?>">
                        <i class="fas fa-file-lines w-4 h-4 sm:w-5 sm:h-5 mr-1.5"></i>
                        <span class="sidebar-expanded-item text-sm">Activity Log</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <script>
        function toggleSidebarDropdown(dropdownId, arrowId) {
            const dropdown = document.getElementById(dropdownId);
            const arrow = document.getElementById(arrowId);
            const isOpen = dropdown.classList.contains('max-h-40') || dropdown.classList.contains('max-h-60') || dropdown.classList.contains('max-h-80');

            if (isOpen) {
                dropdown.classList.remove('max-h-40', 'max-h-60', 'max-h-80');
                dropdown.classList.add('max-h-0');
                arrow.classList.remove('rotate-180');
            } else {
                // Close other open dropdowns
                document.querySelectorAll('.sidebar-expanded ul[id$="Dropdown"]').forEach(otherDropdown => {
                    const currentMaxHeight = otherDropdown.id === 'masterDropdown' ? 'max-h-40' : 'max-h-80';
                    if (otherDropdown.id !== dropdownId && (otherDropdown.classList.contains('max-h-40') || otherDropdown.classList.contains('max-h-60') || otherDropdown.classList.contains('max-h-80'))) {
                        otherDropdown.classList.remove('max-h-40', 'max-h-60', 'max-h-80');
                        otherDropdown.classList.add('max-h-0');
                        const otherArrowId = 'arrowIcon' + otherDropdown.id.replace('Dropdown', '');
                        const otherArrow = document.getElementById(otherArrowId);
                        if (otherArrow) {
                            otherArrow.classList.remove('rotate-180');
                        }
                    }
                });

                dropdown.classList.remove('max-h-0');
                // Set the correct max-h based on the dropdownId
                dropdown.classList.add(dropdownId === 'masterDropdown' ? 'max-h-40' : 'max-h-80');
                arrow.classList.add('rotate-180');
            }
        }
    </script>
</aside>