<?php
// Pastikan session_start() sudah dipanggil di awal file utama (misalnya index.php)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'superadmin') {
    header('Location: ?page=dashboard'); // Alihkan ke dashboard jika bukan superadmin
    exit();
}
?>

<style>
    body {
        font-family: 'Inter', sans-serif;
    }

    /* Gaya dasar untuk baris detail yang tersembunyi pada TABLE */
    .detail-row-table {
        display: none;
    }

    /* Gaya untuk baris detail yang terbuka pada TABLE */
    .detail-row-table.open {
        display: table-row;
    }

    /* Gaya untuk detail card yang tersembunyi pada CARD VIEW */
    .card-detail {
        display: none;
        /* transition: all 0.3s ease-in-out; (Dihapus untuk kompatibilitas 'display: none') */
    }

    .card-detail.open {
        display: block;
    }

    /* Gaya untuk baris utama yang bisa diklik di tabel */
    .main-row-table {
        cursor: pointer;
    }

    /* Gaya untuk Card yang bisa diklik */
    .activity-card {
        cursor: pointer;
    }
</style>

<div class="flex-1 min-w-0 transition-all duration-300">
    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4">Activity Log</h1>

    <div id="app">
        <div class="bg-white p-4 rounded-lg shadow-md mb-4">
            <h2 class="text-sm sm:text-base font-semibold text-gray-700 mb-3">Filter Aktivitas</h2>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-4 mb-3">
                <div>
                    <label for="startDate" class="block text-xs font-medium text-gray-700">Tanggal Awal:</label>
                    <input type="date" id="startDate"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs p-1 sm:p-2 border">
                </div>
                <div>
                    <label for="endDate" class="block text-xs font-medium text-gray-700">Tanggal Akhir:</label>
                    <input type="date" id="endDate"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs p-1 sm:p-2 border">
                </div>
                <div>
                    <label for="userFilter" class="block text-xs font-medium text-gray-700">Pengguna:</label>
                    <select id="userFilter"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs p-1 sm:p-2 border">
                    </select>
                </div>
                <div>
                    <label for="tableFilter" class="block text-xs font-medium text-gray-700">Tabel:</label>
                    <select id="tableFilter"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs p-1 sm:p-2 border">
                    </select>
                </div>
                <div>
                    <label for="actionTypeFilter" class="block text-xs font-medium text-gray-700">Tipe Aksi:</label>
                    <select id="actionTypeFilter"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs p-1 sm:p-2 border">
                    </select>
                </div>
                <div class="col-span-2">
                    <label for="generalSearch" class="block text-xs font-medium text-gray-700">Cari Umum:</label>
                    <input type="text" id="generalSearch" placeholder="Cari di semua kolom, termasuk data lama dan baru..."
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs p-1 sm:p-2 border">
                </div>
            </div>
            <div class="flex justify-end pt-2">
                <button id="resetFiltersBtn"
                    class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-1 px-3 sm:py-2 sm:px-4 text-xs rounded-md shadow-md transition duration-300 ease-in-out">
                    <i class="fas fa-redo mr-1 sm:mr-2"></i>Reset Filter
                </button>
            </div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-md">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-3">
                <h2 class="text-sm sm:text-base font-semibold text-gray-700 mb-2 sm:mb-0">Daftar Aktivitas</h2>
                <div class="flex space-x-2">
                    <button id="expandAllBtn"
                        class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-1 px-3 text-xs rounded-md shadow-md transition duration-300 ease-in-out">
                        <i class="fas fa-plus mr-1"></i>Expand All
                    </button>
                    <button id="collapseAllBtn"
                        class="bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-3 text-xs rounded-md shadow-md transition duration-300 ease-in-out">
                        <i class="fas fa-minus mr-1"></i>Collapse All
                    </button>
                </div>
            </div>

            <div id="loadingIndicator" class="text-center text-gray-600 py-4 hidden">
                <i class="fas fa-spinner fa-spin text-2xl sm:text-4xl mb-2 text-blue-500"></i>
                <p class="text-xs">Memuat data aktivitas...</p>
            </div>
            <div id="noDataMessage" class="text-center text-gray-600 py-4 hidden">
                <i class="fas fa-box-open text-2xl sm:text-4xl mb-2 text-gray-400"></i>
                <p class="text-xs">Tidak ada aktivitas yang ditemukan dengan kriteria filter saat ini.</p>
            </div>

            <div id="activityTableContainer" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[5%]">
                                No
                            </th>
                            <th scope="col"
                                class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[15%]">
                                Tanggal & Waktu
                            </th>
                            <th scope="col"
                                class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[15%]">
                                Pengguna
                            </th>
                            <th scope="col"
                                class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[10%]">
                                Tipe Aksi
                            </th>
                            <th scope="col"
                                class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[15%]">
                                Tabel
                            </th>
                            <th scope="col"
                                class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[35%]">
                                Catatan
                            </th>
                            <th scope="col"
                                class="px-3 sm:px-6 py-2 sm:py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-[5%]">
                                Detail
                            </th>
                        </tr>
                    </thead>
                    <tbody id="activityTableBody" class="bg-white divide-y divide-gray-200 text-xs">
                    </tbody>
                </table>
            </div>

            <div id="activityCardContainer" class="space-y-3">
            </div>
        </div>
    </div>
</div>

<script>
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const userFilter = document.getElementById('userFilter');
    const tableFilter = document.getElementById('tableFilter');
    const actionTypeFilter = document.getElementById('actionTypeFilter');
    const generalSearchInput = document.getElementById('generalSearch');
    const resetFiltersBtn = document.getElementById('resetFiltersBtn');
    const expandAllBtn = document.getElementById('expandAllBtn');
    const collapseAllBtn = document.getElementById('collapseAllBtn');

    const loadingIndicator = document.getElementById('loadingIndicator');
    const noDataMessage = document.getElementById('noDataMessage');
    const activityTableContainer = document.getElementById('activityTableContainer');
    const activityTableBody = document.getElementById('activityTableBody');
    const activityCardContainer = document.getElementById('activityCardContainer'); // ID baru untuk container Card

    let allLogsData = [];
    let debounceTimer;
    const SM_BREAKPOINT = 640; // Tailwind CSS 'sm' breakpoint

    // --- UTILITY FUNCTIONS ---

    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function setDefaultDates() {
        const today = new Date();
        startDateInput.value = formatDate(today);
        endDateInput.value = formatDate(today);
    }

    function formatDateTime(dateTimeString) {
        if (!dateTimeString) return 'N/A';
        const options = {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        };
        return new Date(dateTimeString).toLocaleDateString('id-ID', options);
    }

    function getActionTypeClass(actionType) {
        switch (actionType) {
            case 'CREATE':
                return 'bg-green-100 text-green-800'; // Untuk Tabel
            case 'UPDATE':
                return 'bg-yellow-100 text-yellow-800'; // Untuk Tabel
            case 'DELETE':
                return 'bg-red-100 text-red-800'; // Untuk Tabel
            default:
                return 'bg-gray-100 text-gray-800'; // Untuk Tabel
        }
    }

    function getActionTypeCardClass(actionType) {
        switch (actionType) {
            case 'CREATE':
                return 'bg-green-500 text-white'; // Untuk Card
            case 'UPDATE':
                return 'bg-blue-500 text-white'; // Untuk Card
            case 'DELETE':
                return 'bg-red-500 text-white'; // Untuk Card
            default:
                return 'bg-gray-500 text-white'; // Untuk Card
        }
    }

    function formatDataDetail(data) {
        if (!data || Object.keys(data).length === 0) {
            return '<pre class="bg-gray-200 p-3 rounded-md overflow-auto text-xs italic">Tidak ada</pre>';
        }
        return `<pre class="bg-gray-200 p-3 rounded-md overflow-auto text-xs">${JSON.stringify(data, null, 2)}</pre>`;
    }

    // --- FETCHING & FILTERING ---

    async function fetchActivityLogs() {
        loadingIndicator.classList.remove('hidden');
        noDataMessage.classList.add('hidden');
        activityTableContainer.classList.add('hidden');
        activityCardContainer.classList.add('hidden');

        const filters = {
            startDate: startDateInput.value,
            endDate: endDateInput.value,
        };

        const params = new URLSearchParams(filters);
        try {
            // Mengubah URL fetch sesuai dengan yang ada di script lama
            const response = await fetch(`./controller/activity_log/route.php?${params.toString()}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();

            if (result.success) {
                allLogsData = result.data;
                renderFilterOptions(result.filters);
                applyLocalFilters();
            } else {
                console.error('Failed to fetch activity logs:', result.message);
                allLogsData = [];
                applyLocalFilters();
            }
        } catch (error) {
            console.error('Error fetching activity logs:', error);
            allLogsData = [];
            applyLocalFilters();
        } finally {
            loadingIndicator.classList.add('hidden');
        }
    }

    function applyLocalFilters() {
        let filteredLogs = [...allLogsData];

        if (userFilter.value) {
            filteredLogs = filteredLogs.filter(log => String(log.id_user) === String(userFilter.value));
        }

        if (tableFilter.value) {
            filteredLogs = filteredLogs.filter(log => log.table_name === tableFilter.value);
        }

        if (actionTypeFilter.value) {
            filteredLogs = filteredLogs.filter(log => log.action_type === actionTypeFilter.value);
        }

        if (generalSearchInput.value) {
            const query = generalSearchInput.value.toLowerCase();
            filteredLogs = filteredLogs.filter(log => {
                const logString = [
                    log.user_nama,
                    log.action_type,
                    log.table_name,
                    log.notes,
                    log.data_old ? JSON.stringify(log.data_old) : '',
                    log.data_new ? JSON.stringify(log.data_new) : ''
                ].filter(Boolean).join(' ').toLowerCase();
                return logString.includes(query);
            });
        }

        // Conditional Rendering: Tentukan tampilan yang akan di-render
        renderActivityLog(filteredLogs);
    }

    function renderFilterOptions(filters) {
        const uniqueUsers = [...new Set(filters.users.map(user => user.id))].map(id => filters.users.find(user => user.id === id));
        const uniqueTables = [...new Set(filters.tables)];
        const uniqueActionTypes = [...new Set(filters.action_types)];

        userFilter.innerHTML = '<option value="">Semua Pengguna</option>';
        uniqueUsers.forEach(user => {
            const option = document.createElement('option');
            option.value = user.id;
            option.textContent = user.nama;
            userFilter.appendChild(option);
        });

        tableFilter.innerHTML = '<option value="">Semua Tabel</option>';
        uniqueTables.forEach(table => {
            const option = document.createElement('option');
            option.value = table;
            option.textContent = table;
            tableFilter.appendChild(option);
        });

        actionTypeFilter.innerHTML = '<option value="">Semua Tipe Aksi</option>';
        uniqueActionTypes.forEach(type => {
            const option = document.createElement('option');
            option.value = type;
            option.textContent = type;
            actionTypeFilter.appendChild(option);
        });
    }

    // --- CONDITIONAL RENDERING ---

    function renderActivityLog(logs) {
        const isSmallScreen = window.innerWidth < SM_BREAKPOINT;

        // Reset containers
        activityTableContainer.classList.add('hidden');
        activityCardContainer.classList.add('hidden');
        noDataMessage.classList.add('hidden');

        if (logs.length === 0) {
            noDataMessage.classList.remove('hidden');
            return;
        }

        if (isSmallScreen) {
            activityCardContainer.classList.remove('hidden');
            renderCardView(logs);
        } else {
            activityTableContainer.classList.remove('hidden');
            renderTableView(logs);
        }

        // Reset Expand/Collapse state
        document.querySelectorAll('.toggle-details-btn i').forEach(icon => {
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
        });
    }

    // --- TABLE VIEW (Large Screen) ---
    function renderTableView(logs) {
        activityTableBody.innerHTML = '';

        logs.forEach((log, index) => {
            const mainRow = document.createElement('tr');
            mainRow.className = 'main-row-table hover:bg-gray-50 transition-colors duration-200';
            mainRow.dataset.logId = log.id; // Tambahkan data-log-id ke baris utama
            mainRow.dataset.view = 'table'; // Tambahkan data-view ke baris utama

            mainRow.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">${index + 1}</td>
                <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">${formatDateTime(log.created_at)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-xs font-medium text-gray-900">${log.user_nama || 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getActionTypeClass(log.action_type)}">
                        ${log.action_type}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">${log.table_name}</td>
                <td class="px-6 py-4 text-xs text-gray-500 align-top max-w-lg whitespace-normal">${log.notes || 'Tidak ada catatan.'}</td>
                <td class="px-6 py-4 text-right text-xs font-medium">
                    <button class="toggle-details-btn text-blue-600 hover:text-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-full p-2 transition-colors duration-200" data-log-id="${log.id}" data-view="table" onclick="event.stopPropagation();">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </td>
            `;
            activityTableBody.appendChild(mainRow);

            const detailRow = document.createElement('tr');
            detailRow.id = `detail-log-${log.id}`;
            detailRow.className = 'detail-row-table'; // Ganti ke class khusus tabel

            detailRow.innerHTML = `
                <td colspan="7" class="p-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="font-semibold mb-2">Data Lama:</p>
                                ${formatDataDetail(log.data_old)}
                            </div>
                            <div>
                                <p class="font-semibold mb-2">Data Baru:</p>
                                ${formatDataDetail(log.data_new)}
                            </div>
                        </div>
                    </div>
                </td>
            `;
            activityTableBody.appendChild(detailRow);
        });

        attachToggleListeners();
    }

    // --- CARD VIEW (Small Screen) ---
    function renderCardView(logs) {
        activityCardContainer.innerHTML = '';

        logs.forEach((log, index) => {
            const card = document.createElement('div');
            card.className = 'activity-card bg-white p-4 rounded-lg shadow border border-gray-200';
            card.dataset.logId = log.id; // Tambahkan data-log-id ke Card
            card.dataset.view = 'card'; // Tambahkan data-view ke Card

            card.innerHTML = `
                <div class="flex justify-between items-start mb-2">
                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded ${getActionTypeCardClass(log.action_type)}">
                        ${log.action_type}
                    </span>
                    <button class="toggle-details-btn text-gray-500 hover:text-blue-600 transition duration-150 ease-in-out" data-log-id="${log.id}" data-view="card">
                        <i class="fas fa-chevron-down text-sm"></i>
                    </button>
                </div>

                <p class="text-xs text-gray-500">${formatDateTime(log.created_at)}</p>
                <p class="text-sm font-bold text-gray-800 mt-1">${log.notes || 'Tidak ada catatan.'}</p>
                
                <div class="mt-2 text-xs text-gray-600 space-y-0.5 border-t border-gray-100 pt-2">
                    <p><strong>Pengguna:</strong> ${log.user_nama || 'N/A'}</p>
                    <p><strong>Tabel:</strong> ${log.table_name}</p>
                </div>

                <div id="detail-log-${log.id}" class="card-detail mt-3 pt-3 border-t border-gray-200">
                    <div class="space-y-3">
                        <div>
                            <p class="font-semibold text-gray-700 mb-1">Data Lama:</p>
                            ${formatDataDetail(log.data_old)}
                        </div>
                        <div>
                            <p class="font-semibold text-gray-700 mb-1">Data Baru:</p>
                            ${formatDataDetail(log.data_new)}
                        </div>
                    </div>
                </div>
            `;
            activityCardContainer.appendChild(card);
        });

        attachToggleListeners();
    }

    // --- EVENT HANDLERS ---

    // Handler untuk toggle detail
    function toggleDetails(logId, viewType, targetElement) {
        const detailElement = document.getElementById(`detail-log-${logId}`);
        // Jika detail dipicu dari baris/card utama, kita perlu mencari ikon toggle di dalamnya
        const iconElement = targetElement.querySelector('.toggle-details-btn i') ||
            document.querySelector(`.toggle-details-btn[data-log-id="${logId}"][data-view="${viewType}"] i`);

        if (!detailElement || !iconElement) return;

        if (viewType === 'table') {
            detailElement.classList.toggle('open');
            if (detailElement.classList.contains('open')) {
                iconElement.classList.replace('fa-chevron-down', 'fa-chevron-up');
            } else {
                iconElement.classList.replace('fa-chevron-up', 'fa-chevron-down');
            }
        } else if (viewType === 'card') {
            detailElement.classList.toggle('open');
            if (detailElement.classList.contains('open')) {
                iconElement.classList.replace('fa-chevron-down', 'fa-chevron-up');
            } else {
                iconElement.classList.replace('fa-chevron-up', 'fa-chevron-down');
            }
        }
    }

    function toggleDetailsButtonHandler(event) {
        // Button handler hanya untuk icon toggle (menggunakan event.currentTarget)
        const logId = event.currentTarget.dataset.logId;
        const viewType = event.currentTarget.dataset.view;
        // Gunakan parentElement dari tombol toggle sebagai targetElement
        const targetElement = event.currentTarget.closest(viewType === 'table' ? '.main-row-table' : '.activity-card');

        toggleDetails(logId, viewType, targetElement);
    }

    function clickableRowCardHandler(event) {
        // Handler untuk baris atau card yang bisa diklik (menggunakan event.currentTarget)
        const target = event.currentTarget;
        if (target.classList.contains('main-row-table') || target.classList.contains('activity-card')) {
            const logId = target.dataset.logId;
            const viewType = target.dataset.view;

            // Panggil fungsi toggleDetails
            toggleDetails(logId, viewType, target);
        }
    }


    function attachToggleListeners() {
        // Attach listener untuk tombol detail (ikon panah)
        document.querySelectorAll('.toggle-details-btn').forEach(button => {
            button.removeEventListener('click', toggleDetailsButtonHandler);
            button.addEventListener('click', toggleDetailsButtonHandler);
        });

        // Attach listener untuk baris utama tabel
        document.querySelectorAll('.main-row-table').forEach(row => {
            row.removeEventListener('click', clickableRowCardHandler);
            row.addEventListener('click', clickableRowCardHandler);
        });

        // Attach listener untuk card
        document.querySelectorAll('.activity-card').forEach(card => {
            card.removeEventListener('click', clickableRowCardHandler);
            card.addEventListener('click', clickableRowCardHandler);
        });
    }

    expandAllBtn.addEventListener('click', () => {
        const isSmallScreen = window.innerWidth < SM_BREAKPOINT;

        document.querySelectorAll(isSmallScreen ? '.card-detail' : '.detail-row-table').forEach(element => {
            element.classList.add('open');
        });

        document.querySelectorAll('.toggle-details-btn i').forEach(icon => {
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
        });
    });

    collapseAllBtn.addEventListener('click', () => {
        const isSmallScreen = window.innerWidth < SM_BREAKPOINT;

        document.querySelectorAll(isSmallScreen ? '.card-detail' : '.detail-row-table').forEach(element => {
            element.classList.remove('open');
        });

        document.querySelectorAll('.toggle-details-btn i').forEach(icon => {
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
        });
    });

    // Event Listeners Filter
    startDateInput.addEventListener('change', fetchActivityLogs);
    endDateInput.addEventListener('change', fetchActivityLogs);
    userFilter.addEventListener('change', applyLocalFilters);
    tableFilter.addEventListener('change', applyLocalFilters);
    actionTypeFilter.addEventListener('change', applyLocalFilters);

    generalSearchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(applyLocalFilters, 300);
    });

    resetFiltersBtn.addEventListener('click', () => {
        setDefaultDates();
        userFilter.value = '';
        tableFilter.value = '';
        actionTypeFilter.value = '';
        generalSearchInput.value = '';
        fetchActivityLogs();
    });

    // Listener untuk perubahan ukuran layar
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(applyLocalFilters, 150); // Debounce untuk resize
    });

    // Inisialisasi
    document.addEventListener('DOMContentLoaded', () => {
        setDefaultDates();
        fetchActivityLogs();
    });
</script>