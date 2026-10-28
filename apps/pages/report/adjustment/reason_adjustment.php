<?php
$userRole = $_SESSION['user_role'] ?? 'guest'; // Default ke 'guest' jika tidak diset
?>
<main class="flex-1 min-w-0 transition-all duration-300">
    <h1 class="text-lg sm:text-xl font-extrabold mb-4 text-gray-900 text-center">Reason Adjustment Chiller</h1>

    <div class="bg-white p-3 rounded-lg shadow-md mb-4">
        <h2 class="text-sm font-semibold mb-3 text-gray-800">Filter Laporan</h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <label for="startDate" class="block text-xs font-medium text-gray-700 mb-1">Tanggal Awal:</label>
                <input type="date" id="startDate"
                    class="block w-full text-xs rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-1.5">
            </div>
            <div>
                <label for="endDate" class="block text-xs font-medium text-gray-700 mb-1">Tanggal Akhir:</label>
                <input type="date" id="endDate"
                    class="block w-full text-xs rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-1.5">
            </div>
            <div>
                <label for="storageFilter" class="block text-xs font-medium text-gray-700 mb-1">Storage:</label>
                <select id="storageFilter"
                    class="block w-full text-xs rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-1.5">
                    <option value="">Semua</option>
                </select>
            </div>
            <div>
                <label for="itemSearch" class="block text-xs font-medium text-gray-700 mb-1">Cari Item:</label>
                <input type="text" id="itemSearch" placeholder="Masukkan nama item..."
                    class="block w-full text-xs rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-1.5">
            </div>
        </div>
        <div class="mt-4 flex justify-between gap-3 items-end">
            <div class="w-1/4 flex flex-col gap-y-1">
                <label for="typeFilter" class="block text-xs font-medium text-gray-700 mb-1">Type Adjustment:</label>
                <select id="typeFilter"
                    class="block w-full text-xs shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-1.5 border-gray-600 rounded-lg border">
                    <option value="">Semua</option>
                    <option value="stok_chiller_harian">Stok Chiller Harian</option>
                    <option value="stok_opname_mingguan">Stok Opname Mingguan</option>
                </select>
            </div>
            <button id="resetFiltersBtn"
                class="h-full px-3 py-1.5 text-xs bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-75 transition duration-150 ease-in-out">
                <i class="fas fa-sync-alt mr-1"></i>Reset Filter
            </button>
        </div>
    </div>

    <div class="bg-white p-3 rounded-xl shadow-lg">
        <h2 class="text-sm font-semibold mb-3 text-gray-800">Data Adjustment</h2>

        <div class="mb-3 flex justify-end space-x-2">
            <button id="expandAllBtn"
                class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-75 transition duration-150 ease-in-out">
                <i class="fas fa-eye mr-1"></i>Buka Semua Detail
            </button>
            <button id="collapseAllBtn"
                class="px-3 py-1.5 text-xs bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-75 transition duration-150 ease-in-out">
                <i class="fas fa-eye-slash mr-1"></i>Sembunyikan Semua Detail
            </button>
        </div>

        <div id="loadingIndicator" class="flex flex-col justify-center items-center py-6 hidden">
            <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-blue-500"></div>
            <p class="text-xs text-gray-600 mt-2">Memuat data...</p>
        </div>

        <div id="noDataMessage" class="text-center py-6 text-gray-500 text-sm hidden">
            <p><i class="fas fa-info-circle mr-1"></i>Belum ada data adjustment yang ditemukan untuk kriteria yang dipilih.</p>
        </div>

        <!-- Tabel untuk layar besar (lg ke atas) -->
        <div id="adjustmentTableContainer" class="hidden lg:block overflow-x-auto relative shadow-md sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col"
                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tanggal
                        </th>
                        <th scope="col"
                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Storage
                        </th>
                        <th scope="col"
                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Item
                        </th>
                        <th scope="col"
                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Ekor/Pack
                        </th>
                        <th scope="col"
                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Quantity (Kg)
                        </th>
                        <th scope="col"
                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Type Adjustment
                        </th>
                        <th scope="col"
                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Keterangan
                        </th>
                        <th scope="col"
                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            User
                        </th>
                        <th scope="col"
                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody id="adjustmentTableBody" class="bg-white divide-y divide-gray-200 text-xs">
                </tbody>
            </table>
        </div>

        <!-- Card untuk layar kecil (di bawah lg) -->
        <div id="adjustmentCardContainer" class="lg:hidden space-y-3">
            <!-- Cards will be rendered here -->
        </div>

        <div id="paginationContainer" class="mt-4 flex justify-between items-center hidden">
            <button id="prevPageBtn"
                class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition duration-150 ease-in-out">
                <i class="fas fa-chevron-left mr-1"></i>Previous
            </button>
            <span class="text-xs text-gray-700">
                Page <span id="currentPageSpan" class="font-semibold">1</span> of <span id="totalPagesSpan" class="font-semibold">1</span>
            </span>
            <button id="nextPageBtn"
                class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition duration-150 ease-in-out">
                Next<i class="fas fa-chevron-right ml-1"></i>
            </button>
        </div>
    </div>
</main>

<div id="reasonModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex justify-center items-center z-50 hidden">
    <div class="bg-white p-4 rounded-lg shadow-xl w-11/12 max-w-xl relative border border-gray-300"> <span class="absolute top-2 right-3 text-gray-500 hover:text-gray-800 text-xl font-bold cursor-pointer" id="closeReasonModal">&times;</span>
        <h2 class="text-sm font-bold mb-4 text-gray-800" id="reasonModalTitle">Tambah Alasan Adjustment</h2>
        <form id="reasonForm" class="space-y-3">
            <input type="hidden" id="modalAdjustmentId">
            <input type="hidden" id="modalReasonId">
            <div>
                <label for="modalItemName" class="block text-xs font-medium text-gray-700 mb-1">Nama Item:</label>
                <input type="text" id="modalItemName" readonly
                    class="block w-full text-xs rounded-md border border-gray-300 bg-gray-100 shadow-sm p-1.5">
            </div>
            <div>
                <label for="modalEkorPack" class="block text-xs font-medium text-gray-700 mb-1">Ekor/Pack:</label>
                <input type="number" id="modalEkorPack" required
                    class="block w-full text-xs rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-1.5">
                <span id="ekorPackFeedback" class="text-xs mt-1 block"></span>
            </div>
            <div>
                <label for="modalQuantityKg" class="block text-xs font-medium text-gray-700 mb-1">Quantity (Kg):</label>
                <input type="number" step="0.01" id="modalQuantityKg" required
                    class="block w-full text-xs rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-1.5">
                <span id="quantityKgFeedback" class="text-xs mt-1 block"></span>
            </div>
            <div>
                <label for="modalReason" class="block text-xs font-medium text-gray-700 mb-1">Alasan:</label>
                <textarea id="modalReason" rows="3" required
                    class="block w-full text-xs rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-1.5 resize-y"></textarea>
            </div>
            <div class="flex justify-end space-x-2 pt-3">
                <button type="button" id="cancelReasonBtn"
                    class="px-3 py-1.5 text-xs bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 transition duration-150 ease-in-out">Batal</button>
                <button type="submit" id="saveReasonBtn"
                    class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150 ease-in-out">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Global variables for JavaScript to check user role.
    const currentUserRole = "<?php echo $userRole; ?>";
    const hasAccess = (currentUserRole === 'admin' || currentUserRole === 'superadmin');
</script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Mengambil referensi ke elemen-elemen DOM
        const startDateInput = document.getElementById('startDate');
        const endDateInput = document.getElementById('endDate');
        const storageFilterSelect = document.getElementById('storageFilter');
        const typeFilterSelect = document.getElementById('typeFilter');
        if (typeFilterSelect) {
            typeFilterSelect.value = 'stok_chiller_harian';
        }
        const itemSearchInput = document.getElementById('itemSearch');
        const resetFiltersBtn = document.getElementById('resetFiltersBtn');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const noDataMessage = document.getElementById('noDataMessage');
        const adjustmentTableContainer = document.getElementById('adjustmentTableContainer');
        const adjustmentTableBody = document.getElementById('adjustmentTableBody');
        const adjustmentCardContainer = document.getElementById('adjustmentCardContainer');
        const paginationContainer = document.getElementById('paginationContainer');
        const prevPageBtn = document.getElementById('prevPageBtn');
        const nextPageBtn = document.getElementById('nextPageBtn');
        const currentPageSpan = document.getElementById('currentPageSpan');
        const totalPagesSpan = document.getElementById('totalPagesSpan');
        const expandAllBtn = document.getElementById('expandAllBtn');
        const collapseAllBtn = document.getElementById('collapseAllBtn');

        // Elemen Modal
        const reasonModal = document.getElementById('reasonModal');
        const closeReasonModalBtn = document.getElementById('closeReasonModal');
        const cancelReasonBtn = document.getElementById('cancelReasonBtn');
        const reasonModalTitle = document.getElementById('reasonModalTitle');
        const reasonForm = document.getElementById('reasonForm');
        const modalAdjustmentId = document.getElementById('modalAdjustmentId');
        const modalReasonId = document.getElementById('modalReasonId');
        const modalItemName = document.getElementById('modalItemName');
        const modalEkorPack = document.getElementById('modalEkorPack');
        const modalQuantityKg = document.getElementById('modalQuantityKg');
        const modalReason = document.getElementById('modalReason');
        const ekorPackFeedback = document.getElementById('ekorPackFeedback');
        const quantityKgFeedback = document.getElementById('quantityKgFeedback');

        // Variabel Data
        let allAdjustmentData = [];
        let filteredData = [];
        let perPage = 20;
        let currentPage = 1;
        let visibleReasons = {};

        // Store original adjustment values for comparison in modal
        let currentParentEkorPack = 0;
        let currentParentQuantityKg = 0;
        let currentAdjustmentId = null;

        // --- Fungsi Pembantu (Helper Functions) ---

        function showElement(element) {
            element.classList.remove('hidden');
            if (element.classList.contains('modal')) {
                element.style.display = 'flex';
            }
        }

        function hideElement(element) {
            element.classList.add('hidden');
            if (element.classList.contains('modal')) {
                element.style.display = 'none';
            }
        }

        // Mengatur tanggal awal dan akhir default (bulan ini)
        function setDefaultDates() {
            const today = new Date();
            endDateInput.value = today.toISOString().slice(0, 10);
            const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
            startDateInput.value = firstDayOfMonth.toISOString().slice(0, 10);
        }

        // Mengambil opsi filter (misalnya, daftar storage) dari backend
        async function fetchFilterOptions() {
            try {
                const response = await fetch('./controller/report/adjustment_api.php?action=get_filter_options');
                const result = await response.json();
                if (result.status === 'success') {
                    storageFilterSelect.innerHTML = '<option value="">Semua</option>';
                    result.data.locations.forEach(location => {
                        const option = document.createElement('option');
                        option.value = location;
                        option.textContent = location;
                        storageFilterSelect.appendChild(option);
                    });
                } else {
                    Swal.fire('Error', 'Gagal memuat opsi filter: ' + result.message, 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Terjadi kesalahan saat mengambil opsi filter: ' + error.message, 'error');
            }
        }

        // Mengambil data adjustment dari backend berdasarkan tanggal
        async function fetchAdjustmentData() {
            if (startDateInput.value && endDateInput.value && new Date(startDateInput.value) > new Date(endDateInput.value)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Tanggal akhir tidak boleh lebih kecil dari tanggal awal!',
                });
                allAdjustmentData = [];
                filteredData = [];
                renderData();
                hideElement(loadingIndicator);
                showElement(noDataMessage);
                return;
            }

            showElement(loadingIndicator);
            hideElement(noDataMessage);
            hideElement(adjustmentTableContainer);
            hideElement(adjustmentCardContainer);
            hideElement(paginationContainer);

            try {
                const params = new URLSearchParams({
                    action: 'get_adjustments',
                    startDate: startDateInput.value,
                    endDate: endDateInput.value,
                });

                const response = await fetch(`./controller/report/adjustment_api.php?${params.toString()}`);
                const result = await response.json();

                if (result.status === 'success') {
                    allAdjustmentData = result.data;
                    applyFilters();
                } else {
                    Swal.fire('Error', 'Gagal memuat data adjustment: ' + result.message, 'error');
                    allAdjustmentData = [];
                    filteredData = [];
                    renderData();
                    showElement(noDataMessage);
                }
            } catch (error) {
                Swal.fire('Error', 'Terjadi kesalahan saat mengambil data adjustment: ' + error.message, 'error');
                allAdjustmentData = [];
                filteredData = [];
                renderData();
                showElement(noDataMessage);
            } finally {
                hideElement(loadingIndicator);
            }
        }

        // Menerapkan filter (storage dan item search) pada data yang sudah ada
        function applyFilters() {
            const selectedStorage = storageFilterSelect.value;
            const selectedType = typeFilterSelect.value;
            const itemSearchTerm = itemSearchInput.value.toLowerCase();

            filteredData = allAdjustmentData.filter(adjustment => {
                if (selectedStorage && adjustment.storage !== selectedStorage) {
                    return false;
                }
                if (selectedType && adjustment.adjustment_type !== selectedType) {
                    return false;
                }
                if (itemSearchTerm && !adjustment.item.toLowerCase().includes(itemSearchTerm)) {
                    return false;
                }
                return true;
            });
            currentPage = 1;
            renderData();
        }

        // Function to determine row background color based on adjustment and reasons quantity
        function getRowBackgroundColor(adjustment) {
            const totalReasonQuantity = (adjustment.reasons || []).reduce((sum, reason) => sum + parseFloat(reason.quantity_kg), 0);
            const adjustmentQuantity = parseFloat(adjustment.quantity_kg);
            const epsilon = 0.001;
            const isSesuai = Math.abs(totalReasonQuantity - adjustmentQuantity) < epsilon;

            let bgClass;
            let hoverClass;

            if ((adjustment.reasons || []).length === 0) {
                bgClass = 'bg-gray-100';
                hoverClass = 'hover:bg-gray-200';
            } else if (isSesuai) {
                bgClass = 'bg-green-300';
                hoverClass = 'hover:bg-green-400';
            } else if (adjustmentQuantity >= 0) {
                if (totalReasonQuantity > adjustmentQuantity) {
                    bgClass = 'bg-blue-200';
                    hoverClass = 'hover:bg-blue-300';
                } else {
                    bgClass = 'bg-yellow-200';
                    hoverClass = 'hover:bg-yellow-300';
                }
            } else {
                if (totalReasonQuantity < adjustmentQuantity) {
                    bgClass = 'bg-blue-100';
                    hoverClass = 'hover:bg-blue-200';
                } else {
                    bgClass = 'bg-yellow-100';
                    hoverClass = 'hover:bg-yellow-200';
                }
            }

            return {
                bgClass,
                hoverClass,
                isSesuai
            };
        }

        // Merender data berdasarkan ukuran layar
        function renderData() {
            adjustmentTableBody.innerHTML = '';
            adjustmentCardContainer.innerHTML = '';

            const totalPages = Math.ceil(filteredData.length / perPage);
            const startIndex = (currentPage - 1) * perPage;
            const endIndex = Math.min(startIndex + perPage, filteredData.length);
            const paginatedData = filteredData.slice(startIndex, endIndex);

            if (paginatedData.length === 0) {
                hideElement(adjustmentTableContainer);
                hideElement(adjustmentCardContainer);
                hideElement(paginationContainer);
                showElement(noDataMessage);
                return;
            }

            // Render untuk layar besar (tabel)
            if (window.innerWidth >= 1024) {
                renderTable(paginatedData);
                showElement(adjustmentTableContainer);
                hideElement(adjustmentCardContainer);
            } else {
                // Render untuk layar kecil (card)
                renderCards(paginatedData);
                hideElement(adjustmentTableContainer);
                showElement(adjustmentCardContainer);
            }

            updatePagination(totalPages);
        }

        // Render tabel untuk layar besar
        function renderTable(paginatedData) {
            paginatedData.forEach(adjustment => {
                const row = document.createElement('tr');
                const {
                    bgClass,
                    hoverClass,
                    isSesuai
                } = getRowBackgroundColor(adjustment);
                row.classList.add(bgClass, hoverClass);

                const hideAddReasonButton = isSesuai;
                const originalKeterangan = adjustment.keterangan || '-';
                const maxKeteranganLength = 50;
                const isKeteranganLong = originalKeterangan.length > maxKeteranganLength;
                const displayKeterangan = isKeteranganLong ?
                    originalKeterangan.substring(0, maxKeteranganLength) + '...' :
                    originalKeterangan;

                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${adjustment.date}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${adjustment.storage}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${adjustment.item}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${adjustment.ekor_pack}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${adjustment.quantity_kg}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${adjustment.adjustment_type}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        ${displayKeterangan}
                        ${isKeteranganLong ? `<span class="text-blue-500 hover:text-blue-700 cursor-pointer text-xs font-semibold" data-full-keterangan="${originalKeterangan}">(Lihat Detail)</span>` : ''}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${adjustment.user}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button class="toggle-reason-btn text-blue-600 hover:text-blue-900 mr-3" data-id="${adjustment.id}">
                            <span><i class="fas fa-chevron-down mr-1"></i>Detail</span>
                        </button>
                        ${hasAccess && !hideAddReasonButton ? `<button class="add-reason-btn text-green-600 hover:text-green-900" data-adjustment-id="${adjustment.id}"><i class="fas fa-plus mr-1"></i>Tambah Alasan</button>` : ''}
                    </td>
                `;
                adjustmentTableBody.appendChild(row);

                const detailSpan = row.querySelector('[data-full-keterangan]');
                if (detailSpan) {
                    detailSpan.addEventListener('click', (event) => {
                        event.stopPropagation();
                        const fullText = event.target.dataset.fullKeterangan;
                        showKeteranganDetailModal(fullText);
                    });
                }

                const reasonRow = document.createElement('tr');
                reasonRow.id = `reason-row-${adjustment.id}`;
                reasonRow.classList.add('hidden', 'bg-gray-50');
                reasonRow.innerHTML = generateReasonContent(adjustment);
                adjustmentTableBody.appendChild(reasonRow);

                if (visibleReasons[adjustment.id]) {
                    showElement(reasonRow);
                    const toggleButton = row.querySelector(`.toggle-reason-btn[data-id="${adjustment.id}"]`);
                    if (toggleButton) {
                        toggleButton.innerHTML = '<span><i class="fas fa-chevron-up mr-1"></i>Close</span>';
                    }
                }
            });

            addEventListenersToTableButtons();
        }

        // Render cards untuk layar kecil
        function renderCards(paginatedData) {
            paginatedData.forEach(adjustment => {
                const {
                    bgClass,
                    hoverClass,
                    isSesuai
                } = getRowBackgroundColor(adjustment);
                const hideAddReasonButton = isSesuai;
                const originalKeterangan = adjustment.keterangan || '-';
                const maxKeteranganLength = 30;
                const isKeteranganLong = originalKeterangan.length > maxKeteranganLength;
                const displayKeterangan = isKeteranganLong ?
                    originalKeterangan.substring(0, maxKeteranganLength) + '...' :
                    originalKeterangan;

                const card = document.createElement('div');
                card.className = `bg-white rounded-lg shadow-md border-l-4 ${bgClass.replace('bg-', 'border-')} ${hoverClass}`;
                card.innerHTML = `
                    <div class="p-4">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-1">
                                <h3 class="text-sm font-semibold text-gray-900 mb-1">${adjustment.item}</h3>
                                <div class="grid grid-cols-2 gap-2 text-xs text-gray-600">
                                    <div><span class="font-medium">Tanggal:</span> ${adjustment.date}</div>
                                    <div><span class="font-medium">Storage:</span> ${adjustment.storage}</div>
                                    <div><span class="font-medium">Ekor/Pack:</span> ${adjustment.ekor_pack}</div>
                                    <div><span class="font-medium">Quantity:</span> ${adjustment.quantity_kg} Kg</div>
                                    <div><span class="font-medium">Type:</span> ${adjustment.adjustment_type}</div>
                                    <div><span class="font-medium">User:</span> ${adjustment.user}</div>
                                </div>
                                <div class="mt-2 text-xs">
                                    <span class="font-medium">Keterangan:</span> ${displayKeterangan}
                                    ${isKeteranganLong ? `<span class="text-blue-500 hover:text-blue-700 cursor-pointer font-semibold ml-1" data-full-keterangan="${originalKeterangan}">(Lihat Detail)</span>` : ''}
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-between items-center mt-3">
                            <button class="toggle-reason-btn-card text-blue-600 hover:text-blue-900 text-xs font-medium" data-id="${adjustment.id}">
                                <i class="fas fa-chevron-down mr-1"></i>Detail Alasan
                            </button>
                            ${hasAccess && !hideAddReasonButton ? `
                                <button class="add-reason-btn-card text-green-600 hover:text-green-900 text-xs font-medium" data-adjustment-id="${adjustment.id}">
                                    <i class="fas fa-plus mr-1"></i>Tambah Alasan
                                </button>
                            ` : ''}
                        </div>
                    </div>
                    <div id="reason-card-${adjustment.id}" class="hidden bg-gray-50 border-t border-gray-200">
                        <div class="p-4">
                            ${generateReasonContent(adjustment)}
                        </div>
                    </div>
                `;
                adjustmentCardContainer.appendChild(card);

                const detailSpan = card.querySelector('[data-full-keterangan]');
                if (detailSpan) {
                    detailSpan.addEventListener('click', (event) => {
                        event.stopPropagation();
                        const fullText = event.target.dataset.fullKeterangan;
                        showKeteranganDetailModal(fullText);
                    });
                }
            });

            addEventListenersToCardButtons();
        }

        // Generate content untuk reason (digunakan oleh tabel dan card)
        function generateReasonContent(adjustment) {
            if (adjustment.reasons && adjustment.reasons.length > 0) {
                const totalReasonsEkor = adjustment.reasons.reduce((sum, reason) => sum + parseFloat(reason.ekor_pack), 0);
                const totalReasonsQuantity = adjustment.reasons.reduce((sum, reason) => sum + parseFloat(reason.quantity_kg), 0);

                return `
                    <div class="bg-gray-100 p-3 rounded-lg">
                        <h4 class="text-sm font-semibold mb-2 text-gray-800">Detail Alasan Adjustment:</h4>
                        <div class="mb-3 text-xs">
                            <span class="font-bold">Total Ekor/Pack: ${totalReasonsEkor.toFixed(2)}</span>
                            <span class="ml-4 font-bold">Total Quantity: ${totalReasonsQuantity.toFixed(2)} Kg</span>
                        </div>
                        <div class="space-y-2">
                            ${adjustment.reasons.map(reason => `
                                <div class="bg-white p-3 rounded border border-gray-200">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs mb-2">
                                        <div><span class="font-medium">Ekor/Pack:</span> ${reason.ekor_pack}</div>
                                        <div><span class="font-medium">Quantity:</span> ${reason.quantity_kg} Kg</div>
                                        <div class="sm:col-span-2"><span class="font-medium">Alasan:</span> ${reason.reason}</div>
                                        <div><span class="font-medium">Oleh:</span> ${reason.user_name}</div>
                                    </div>
                                    ${hasAccess ? `
                                        <div class="flex space-x-2 mt-2">
                                            <button class="edit-reason-btn text-indigo-600 hover:text-indigo-900 text-xs"
                                                data-adjustment-id="${adjustment.id}"
                                                data-reason-id="${reason.id}"
                                                data-ekor-pack="${reason.ekor_pack}"
                                                data-quantity-kg="${reason.quantity_kg}"
                                                data-reason-text="${reason.reason}">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </button>
                                            <button class="delete-reason-btn text-red-600 hover:text-red-900 text-xs"
                                                data-adjustment-id="${adjustment.id}"
                                                data-reason-id="${reason.id}">
                                                <i class="fas fa-trash-alt mr-1"></i>Hapus
                                            </button>
                                        </div>
                                    ` : ''}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            } else {
                return `
                    <div class="bg-gray-100 p-4 rounded-lg text-center text-gray-600 text-sm">
                        Tidak ada alasan yang dicatat.
                    </div>
                `;
            }
        }

        function showKeteranganDetailModal(fullText) {
            Swal.fire({
                title: 'Detail Keterangan',
                html: `<div style="text-align: left; white-space: pre-wrap;">${fullText}</div>`,
                width: '600px',
                confirmButtonText: 'Tutup',
            });
        }

        // Memperbarui kontrol paginasi
        function updatePagination(totalPages) {
            currentPageSpan.textContent = currentPage;
            totalPagesSpan.textContent = totalPages;

            prevPageBtn.disabled = currentPage === 1;
            nextPageBtn.disabled = currentPage === totalPages;

            if (totalPages > 1) {
                showElement(paginationContainer);
            } else {
                hideElement(paginationContainer);
            }
        }

        // Menambah event listener untuk tombol-tombol di dalam tabel
        function addEventListenersToTableButtons() {
            document.querySelectorAll('.toggle-reason-btn').forEach(button => {
                button.onclick = (event) => {
                    const id = event.currentTarget.dataset.id;
                    toggleReason(id);
                };
            });

            if (hasAccess) {
                document.querySelectorAll('.add-reason-btn').forEach(button => {
                    button.onclick = (event) => {
                        const adjustmentId = event.currentTarget.dataset.adjustmentId;
                        const adjustmentData = allAdjustmentData.find(adj => adj.id == adjustmentId);
                        openReasonModal('add', adjustmentId, adjustmentData);
                    };
                });

                document.querySelectorAll('.edit-reason-btn').forEach(button => {
                    button.onclick = (event) => {
                        const adjustmentId = event.currentTarget.dataset.adjustmentId;
                        const reasonId = event.currentTarget.dataset.reasonId;
                        const ekorPack = parseFloat(event.currentTarget.dataset.ekorPack);
                        const quantityKg = parseFloat(event.currentTarget.dataset.quantityKg);
                        const reasonText = event.currentTarget.dataset.reasonText;
                        const adjustmentData = allAdjustmentData.find(adj => adj.id == adjustmentId);
                        openReasonModal('edit', adjustmentId, {
                            id: reasonId,
                            ekor_pack: ekorPack,
                            quantity_kg: quantityKg,
                            reason: reasonText,
                            item: adjustmentData.item
                        });
                    };
                });

                document.querySelectorAll('.delete-reason-btn').forEach(button => {
                    button.onclick = (event) => {
                        const adjustmentId = event.currentTarget.dataset.adjustmentId;
                        const reasonId = event.currentTarget.dataset.reasonId;
                        Swal.fire({
                            title: 'Apakah Anda yakin?',
                            text: "Anda tidak akan dapat mengembalikan ini!",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Ya, hapus!',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                deleteReason(adjustmentId, reasonId);
                            }
                        });
                    };
                });
            }
        }

        // Menambah event listener untuk tombol-tombol di dalam card
        function addEventListenersToCardButtons() {
            document.querySelectorAll('.toggle-reason-btn-card').forEach(button => {
                button.onclick = (event) => {
                    const id = event.currentTarget.dataset.id;
                    toggleReasonCard(id);
                };
            });

            if (hasAccess) {
                document.querySelectorAll('.add-reason-btn-card').forEach(button => {
                    button.onclick = (event) => {
                        const adjustmentId = event.currentTarget.dataset.adjustmentId;
                        const adjustmentData = allAdjustmentData.find(adj => adj.id == adjustmentId);
                        openReasonModal('add', adjustmentId, adjustmentData);
                    };
                });

                document.querySelectorAll('.edit-reason-btn').forEach(button => {
                    button.onclick = (event) => {
                        const adjustmentId = event.currentTarget.dataset.adjustmentId;
                        const reasonId = event.currentTarget.dataset.reasonId;
                        const ekorPack = parseFloat(event.currentTarget.dataset.ekorPack);
                        const quantityKg = parseFloat(event.currentTarget.dataset.quantityKg);
                        const reasonText = event.currentTarget.dataset.reasonText;
                        const adjustmentData = allAdjustmentData.find(adj => adj.id == adjustmentId);
                        openReasonModal('edit', adjustmentId, {
                            id: reasonId,
                            ekor_pack: ekorPack,
                            quantity_kg: quantityKg,
                            reason: reasonText,
                            item: adjustmentData.item
                        });
                    };
                });

                document.querySelectorAll('.delete-reason-btn').forEach(button => {
                    button.onclick = (event) => {
                        const adjustmentId = event.currentTarget.dataset.adjustmentId;
                        const reasonId = event.currentTarget.dataset.reasonId;
                        Swal.fire({
                            title: 'Apakah Anda yakin?',
                            text: "Anda tidak akan dapat mengembalikan ini!",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Ya, hapus!',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                deleteReason(adjustmentId, reasonId);
                            }
                        });
                    };
                });
            }
        }

        // Mengganti status collapse baris detail alasan (tabel)
        function toggleReason(id) {
            const reasonRow = document.getElementById(`reason-row-${id}`);
            const toggleButton = document.querySelector(`.toggle-reason-btn[data-id="${id}"]`);

            if (reasonRow && toggleButton) {
                if (reasonRow.classList.contains('hidden')) {
                    showElement(reasonRow);
                    visibleReasons[id] = true;
                    toggleButton.innerHTML = '<span><i class="fas fa-chevron-up mr-1"></i>Close</span>';
                } else {
                    hideElement(reasonRow);
                    visibleReasons[id] = false;
                    toggleButton.innerHTML = '<span><i class="fas fa-chevron-down mr-1"></i>Detail</span>';
                }
            }
        }

        // Mengganti status collapse card detail alasan
        function toggleReasonCard(id) {
            const reasonCard = document.getElementById(`reason-card-${id}`);
            const toggleButton = document.querySelector(`.toggle-reason-btn-card[data-id="${id}"]`);

            if (reasonCard && toggleButton) {
                if (reasonCard.classList.contains('hidden')) {
                    showElement(reasonCard);
                    visibleReasons[id] = true;
                    toggleButton.innerHTML = '<i class="fas fa-chevron-up mr-1"></i>Sembunyikan Alasan';
                } else {
                    hideElement(reasonCard);
                    visibleReasons[id] = false;
                    toggleButton.innerHTML = '<i class="fas fa-chevron-down mr-1"></i>Detail Alasan';
                }
            }
        }

        // Mengganti status collapse semua baris detail alasan
        function toggleAllReasons(show) {
            const paginatedIds = filteredData.slice((currentPage - 1) * perPage, currentPage * perPage)
                .map(adj => adj.id);

            paginatedIds.forEach(id => {
                if (window.innerWidth >= 1024) {
                    // Untuk layar besar (tabel)
                    const reasonRow = document.getElementById(`reason-row-${id}`);
                    const toggleButton = document.querySelector(`.toggle-reason-btn[data-id="${id}"]`);
                    if (reasonRow && toggleButton) {
                        if (show) {
                            showElement(reasonRow);
                            visibleReasons[id] = true;
                            toggleButton.innerHTML = '<span><i class="fas fa-chevron-up mr-1"></i>Close</span>';
                        } else {
                            hideElement(reasonRow);
                            visibleReasons[id] = false;
                            toggleButton.innerHTML = '<span><i class="fas fa-chevron-down mr-1"></i>Detail</span>';
                        }
                    }
                } else {
                    // Untuk layar kecil (card)
                    const reasonCard = document.getElementById(`reason-card-${id}`);
                    const toggleButton = document.querySelector(`.toggle-reason-btn-card[data-id="${id}"]`);
                    if (reasonCard && toggleButton) {
                        if (show) {
                            showElement(reasonCard);
                            visibleReasons[id] = true;
                            toggleButton.innerHTML = '<i class="fas fa-chevron-up mr-1"></i>Sembunyikan Alasan';
                        } else {
                            hideElement(reasonCard);
                            visibleReasons[id] = false;
                            toggleButton.innerHTML = '<i class="fas fa-chevron-down mr-1"></i>Detail Alasan';
                        }
                    }
                }
            });
        }

        // Fungsi untuk memeriksa dan mewarnai input ekor/pack dan quantity/kg di modal
        function checkAndColorInputs() {
            const mode = reasonModal.dataset.mode;
            const enteredEkor = parseFloat(modalEkorPack.value);
            const enteredQuantity = parseFloat(modalQuantityKg.value);

            let totalEkorForReasons = 0;
            let totalQuantityForReasons = 0;

            const currentAdjustment = allAdjustmentData.find(adj => adj.id == currentAdjustmentId);
            const originalParentEkorAdjustment = parseFloat(currentAdjustment.ekor_pack);
            const originalParentQuantityAdjustment = parseFloat(currentAdjustment.quantity_kg);

            if (currentAdjustment && currentAdjustment.reasons) {
                currentAdjustment.reasons.forEach(reason => {
                    if (mode === 'edit' && reason.id == modalReasonId.value) {
                        return;
                    }
                    totalEkorForReasons += parseFloat(reason.ekor_pack);
                    totalQuantityForReasons += parseFloat(reason.quantity_kg);
                });
            }

            const remainingEkor = originalParentEkorAdjustment - totalEkorForReasons;
            const remainingQuantity = originalParentQuantityAdjustment - totalQuantityForReasons;

            const epsilon = 0.001;

            // Ekor/Pack feedback
            ekorPackFeedback.classList.remove('text-green-600', 'text-red-600', 'text-yellow-600');
            let ekorFeedbackText = '';
            let ekorClass = '';

            const ekorAdjText = `<span class="font-bold text-lg">Adj: ${originalParentEkorAdjustment.toFixed(2)}</span>`;

            if (Math.abs(enteredEkor - remainingEkor) < epsilon) {
                ekorFeedbackText = `<span class="text-lg">(Sesuai)</span> ${ekorAdjText}`;
                ekorClass = 'text-green-600';
            } else if (originalParentEkorAdjustment >= 0) {
                if (enteredEkor > remainingEkor) {
                    ekorFeedbackText = `<span class="text-lg">(Lebih: +${(enteredEkor - remainingEkor).toFixed(2)})</span> ${ekorAdjText}`;
                    ekorClass = 'text-red-600';
                } else {
                    ekorFeedbackText = `<span class="text-lg">(Kurang: ${(remainingEkor - enteredEkor).toFixed(2)})</span> ${ekorAdjText}`;
                    ekorClass = 'text-yellow-600';
                }
            } else {
                if (enteredEkor < remainingEkor) {
                    ekorFeedbackText = `<span class="text-lg">(Lebih: +${Math.abs(enteredEkor - remainingEkor).toFixed(2)})</span> ${ekorAdjText}`;
                    ekorClass = 'text-red-600';
                } else {
                    ekorFeedbackText = `<span class="text-lg">(Kurang: ${Math.abs(enteredEkor - remainingEkor).toFixed(2)})</span> ${ekorAdjText}`;
                    ekorClass = 'text-yellow-600';
                }
            }
            ekorPackFeedback.innerHTML = ekorFeedbackText;
            ekorPackFeedback.classList.add(ekorClass);

            // Quantity (Kg) feedback
            quantityKgFeedback.classList.remove('text-green-600', 'text-red-600', 'text-yellow-600');
            let quantityFeedbackText = '';
            let quantityClass = '';

            const quantityAdjText = `<span class="font-bold text-lg">Adj: ${originalParentQuantityAdjustment.toFixed(2)} Kg</span>`;

            if (Math.abs(enteredQuantity - remainingQuantity) < epsilon) {
                quantityFeedbackText = `<span class="text-lg">(Sesuai)</span> ${quantityAdjText}`;
                quantityClass = 'text-green-600';
            } else if (originalParentQuantityAdjustment >= 0) {
                if (enteredQuantity > remainingQuantity) {
                    quantityFeedbackText = `<span class="text-lg">(Lebih: +${(enteredQuantity - remainingQuantity).toFixed(2)} Kg)</span> ${quantityAdjText}`;
                    quantityClass = 'text-red-600';
                } else {
                    quantityFeedbackText = `<span class="text-lg">(Kurang: ${(remainingQuantity - enteredQuantity).toFixed(2)} Kg)</span> ${quantityAdjText}`;
                    quantityClass = 'text-yellow-600';
                }
            } else {
                if (enteredQuantity < remainingQuantity) {
                    quantityFeedbackText = `<span class="text-lg">(Lebih: +${Math.abs(enteredQuantity - remainingQuantity).toFixed(2)} Kg)</span> ${quantityAdjText}`;
                    quantityClass = 'text-red-600';
                } else {
                    quantityFeedbackText = `<span class="text-lg">(Kurang: ${Math.abs(enteredQuantity - remainingQuantity).toFixed(2)} Kg)</span> ${quantityAdjText}`;
                    quantityClass = 'text-yellow-600';
                }
            }
            quantityKgFeedback.innerHTML = quantityFeedbackText;
            quantityKgFeedback.classList.add(quantityClass);
        }

        // --- Fungsi Modal ---
        function openReasonModal(mode, adjustmentId, adjustmentData) {
            reasonModal.dataset.mode = mode;
            modalAdjustmentId.value = adjustmentId;
            currentAdjustmentId = adjustmentId;
            reasonForm.reset();
            ekorPackFeedback.textContent = '';
            quantityKgFeedback.textContent = '';

            modalItemName.value = adjustmentData.item || '';
            currentParentEkorPack = parseFloat(adjustmentData.ekor_pack);
            currentParentQuantityKg = parseFloat(adjustmentData.quantity_kg);

            const currentAdjustment = allAdjustmentData.find(adj => adj.id == adjustmentId);
            let sumEkorReasons = 0;
            let sumQuantityReasons = 0;

            if (currentAdjustment && currentAdjustment.reasons) {
                currentAdjustment.reasons.forEach(reason => {
                    sumEkorReasons += parseFloat(reason.ekor_pack);
                    sumQuantityReasons += parseFloat(reason.quantity_kg);
                });
            }

            if (mode === 'add') {
                reasonModalTitle.textContent = 'Tambah Alasan Adjustment';
                modalReasonId.value = '';
                const remainingEkor = currentParentEkorPack - sumEkorReasons;
                const remainingQuantity = currentParentQuantityKg - sumQuantityReasons;
                modalEkorPack.value = remainingEkor.toFixed();
                modalQuantityKg.value = remainingQuantity.toFixed(2);
            } else if (mode === 'edit' && adjustmentData) {
                reasonModalTitle.textContent = 'Edit Alasan Adjustment';
                modalReasonId.value = adjustmentData.id;
                modalEkorPack.value = adjustmentData.ekor_pack;
                modalQuantityKg.value = adjustmentData.quantity_kg;
                modalReason.value = adjustmentData.reason;
            }

            showElement(reasonModal);
            checkAndColorInputs();
        }

        function closeReasonModal() {
            hideElement(reasonModal);
            reasonForm.reset();
            ekorPackFeedback.textContent = '';
            quantityKgFeedback.textContent = '';
        }

        async function submitReasonForm() {
            const mode = reasonModal.dataset.mode;
            const adjustmentId = modalAdjustmentId.value;
            const reasonId = modalReasonId.value;
            const ekorPack = parseFloat(modalEkorPack.value);
            const quantityKg = parseFloat(modalQuantityKg.value);
            const reasonText = modalReason.value;

            if (isNaN(ekorPack) || isNaN(quantityKg) || !reasonText) {
                Swal.fire('Peringatan', 'Harap lengkapi semua bidang dengan nilai numerik yang valid.', 'warning');
                return;
            }

            const payload = {
                adjustment_id: adjustmentId,
                ekor_pack: ekorPack,
                quantity_kg: quantityKg,
                reason: reasonText,
                user: currentUserRole
            };

            let url = './controller/report/adjustment_api.php';
            let method = '';

            if (mode === 'add') {
                url += '?action=add_reason';
                method = 'POST';
            } else if (mode === 'edit') {
                url += '?action=edit_reason';
                method = 'POST';
                payload.reason_id = reasonId;
            }

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();

                if (result.status === 'success') {
                    Swal.fire('Berhasil!', result.message, 'success');
                    closeReasonModal();
                    await fetchAdjustmentData();
                } else {
                    Swal.fire('Error', 'Gagal menyimpan alasan: ' + result.message, 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Terjadi kesalahan saat menyimpan alasan: ' + error.message, 'error');
            }
        }

        async function deleteReason(adjustmentId, reasonId) {
            try {
                const response = await fetch('./controller/report/adjustment_api.php?action=delete_reason', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        adjustment_id: adjustmentId,
                        reason_id: reasonId,
                        user: currentUserRole
                    })
                });
                const result = await response.json();

                if (result.status === 'success') {
                    Swal.fire('Dihapus!', result.message, 'success');
                    await fetchAdjustmentData();
                } else {
                    Swal.fire('Error', 'Gagal menghapus alasan: ' + result.message, 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Terjadi kesalahan saat menghapus alasan: ' + error.message, 'error');
            }
        }

        // --- Event Listeners ---

        startDateInput.addEventListener('change', fetchAdjustmentData);
        endDateInput.addEventListener('change', fetchAdjustmentData);
        storageFilterSelect.addEventListener('change', applyFilters);
        typeFilterSelect.addEventListener('change', applyFilters);
        itemSearchInput.addEventListener('input', applyFilters);

        resetFiltersBtn.addEventListener('click', () => {
            setDefaultDates();
            storageFilterSelect.value = '';
            typeFilterSelect.value = 'stok_chiller_harian';
            itemSearchInput.value = '';
            visibleReasons = {};
            fetchAdjustmentData();
        });

        prevPageBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderData();
            }
        });

        nextPageBtn.addEventListener('click', () => {
            const totalPages = Math.ceil(filteredData.length / perPage);
            if (currentPage < totalPages) {
                currentPage++;
                renderData();
            }
        });

        expandAllBtn.addEventListener('click', () => toggleAllReasons(true));
        collapseAllBtn.addEventListener('click', () => toggleAllReasons(false));

        // Event listeners for modal
        closeReasonModalBtn.addEventListener('click', closeReasonModal);
        cancelReasonBtn.addEventListener('click', closeReasonModal);
        reasonForm.addEventListener('submit', (event) => {
            event.preventDefault();
            submitReasonForm();
        });

        // Add input event listeners for real-time feedback
        modalEkorPack.addEventListener('input', checkAndColorInputs);
        modalQuantityKg.addEventListener('input', checkAndColorInputs);

        // Handle window resize untuk responsive rendering
        window.addEventListener('resize', () => {
            if (filteredData.length > 0) {
                renderData();
            }
        });

        // Inisialisasi
        setDefaultDates();
        fetchFilterOptions();
        fetchAdjustmentData();
    });
</script>