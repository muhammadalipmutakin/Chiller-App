 <script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
 <main class="flex-1 min-w-0 transition-all duration-300">
     <h1 class="text-lg sm:text-xl font-extrabold mb-4 text-gray-900 text-center">Laporan Adjustment Chiller</h1>

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
             <div class="w-1/4 flex flex-col gap-y-1 hidden">
                 <label for="typeFilter" class="block text-xs font-medium text-gray-700 mb-1">Type
                     Adjustment:</label>
                 <select id="typeFilter"
                     class="block w-full text-xs shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-1.5 border-gray-600 rounded-lg border">
                     <option value="" selected>Semua</option>
                     <option value="harian">Harian</option>
                     <option value="mingguan">Mingguan</option>
                 </select>
             </div>
             <div class="w-1/4 flex flex-col gap-y-1">
                 <label for="typeFilter" class="block text-xs font-medium text-gray-700 mb-1">Detail Adjustment:</label>
                 <select id="typeFilter"
                     class="block w-full text-xs shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-1.5 border-gray-600 rounded-lg border">
                     <option value="" selected>Semua</option>
                     <option value=" harian">Harian</option>
                     <option value="mingguan">Mingguan</option>
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

         <div class="mb-3 flex justify-end space-x-2 relative">
             <button id="expandAllBtn"
                 class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-75 transition duration-150 ease-in-out">
                 <i class="fas fa-eye mr-1"></i>Buka Semua Detail
             </button>
             <button id="collapseAllBtn"
                 class="px-3 py-1.5 text-xs bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-75 transition duration-150 ease-in-out">
                 <i class="fas fa-eye-slash mr-1"></i>Sembunyikan Semua Detail
             </button>

             <div class="relative inline-block text-left">
                 <button id="exportDropdownBtn" type="button"
                     class="px-3 py-1.5 text-xs bg-green-600 text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-75 transition duration-150 ease-in-out"
                     aria-haspopup="true" aria-expanded="true">
                     <i class="fas fa-file-excel mr-1"></i>Export Excel <i class="fas fa-caret-down ml-1"></i>
                 </button>
                 <div id="exportDropdownMenu"
                     class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 hidden z-10"
                     role="menu" aria-orientation="vertical" aria-labelledby="exportDropdownBtn">
                     <div class="py-1" role="none">
                         <a href="#" id="exportSummaryBtn"
                             class="text-gray-700 block px-3 py-1.5 text-xs hover:bg-gray-100" role="menuitem">
                             Export Data Ringkasan
                         </a>
                         <a href="#" id="exportAllDetailsBtn"
                             class="text-gray-700 block px-3 py-1.5 text-xs hover:bg-gray-100" role="menuitem">
                             Export Ringkasan + Detail Harian
                         </a>
                     </div>
                 </div>
             </div>
         </div>

         <div id="loadingIndicator" class="flex flex-col justify-center items-center py-6 hidden">
             <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-blue-500"></div>
             <p class="text-xs text-gray-600 mt-2">Memuat data...</p>
         </div>

         <div id="noDataMessage" class="text-center py-6 text-gray-500 text-sm hidden">
             <p><i class="fas fa-info-circle mr-1"></i>Belum ada data adjustment yang ditemukan untuk kriteria yang
                 dipilih.</p>
         </div>

         <div id="adjustmentTableContainer" class="overflow-x-auto relative shadow-md sm:rounded-lg hidden">
             <table class="min-w-full divide-y divide-gray-200" id="adjustmentTable">
                 <thead class="bg-gray-50">
                     <tr>
                         <th scope="col"
                             class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                             No.</th>
                         <th scope="col"
                             class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                             Storage</th>
                         <th scope="col"
                             class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                             Item</th>
                         <th scope="col"
                             class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                             Total Ekor/Pack</th>
                         <th scope="col"
                             class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                             Total Quantity (Kg)</th>
                         <th scope="col"
                             class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                             Type Adjustment</th>
                         <th scope="col"
                             class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                             Aksi</th>
                     </tr>
                 </thead>
                 <tbody id="adjustmentTableBody" class="bg-white divide-y divide-gray-200 text-xs">
                 </tbody>
             </table>
         </div>

         <div id="paginationContainer" class="mt-4 flex justify-between items-center hidden">
             <button id="prevPageBtn"
                 class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition duration-150 ease-in-out">
                 <i class="fas fa-chevron-left mr-1"></i>Previous
             </button>
             <span class="text-xs text-gray-700">
                 Page <span id="currentPageSpan" class="font-semibold">1</span> of <span id="totalPagesSpan"
                     class="font-semibold">1</span>
             </span>
             <button id="nextPageBtn"
                 class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition duration-150 ease-in-out">
                 Next<i class="fas fa-chevron-right ml-1"></i>
             </button>
         </div>
     </div>
 </main>

 <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>

 <script>
     const API_URL = './controller/report/report_adjustment_api.php'; // Adjust this path if your API is elsewhere

     const startDateInput = document.getElementById('startDate');
     const endDateInput = document.getElementById('endDate');
     const storageFilter = document.getElementById('storageFilter');
     const itemSearchInput = document.getElementById('itemSearch');
     const typeFilter = document.getElementById('typeFilter');
     const resetFiltersBtn = document.getElementById('resetFiltersBtn');
     const adjustmentTableBody = document.getElementById('adjustmentTableBody');
     const loadingIndicator = document.getElementById('loadingIndicator');
     const noDataMessage = document.getElementById('noDataMessage');
     const adjustmentTableContainer = document.getElementById('adjustmentTableContainer');
     const expandAllBtn = document.getElementById('expandAllBtn');
     const collapseAllBtn = document.getElementById('collapseAllBtn');

     const prevPageBtn = document.getElementById('prevPageBtn');
     const nextPageBtn = document.getElementById('nextPageBtn');
     const currentPageSpan = document.getElementById('currentPageSpan');
     const totalPagesSpan = document.getElementById('totalPagesSpan');
     const paginationContainer = document.getElementById('paginationContainer');

     const exportDropdownBtn = document.getElementById('exportDropdownBtn');
     const exportDropdownMenu = document.getElementById('exportDropdownMenu');
     const exportSummaryBtn = document.getElementById('exportSummaryBtn');
     const exportAllDetailsBtn = document.getElementById('exportAllDetailsBtn');

     let allSummarizedData = []; // Stores all data fetched from backend (based on date filters)
     let filteredData = []; // Stores data after client-side filtering/searching
     let dailyDetailsCache = {}; // Key: "storage_item", Value: Array of daily details

     const rowsPerPage = 20; // Number of rows per page
     let currentPage = 1;
     let totalPages = 1;

     // Set default dates (first day of current month to today)
     const today = new Date();
     const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
     startDateInput.value = firstDayOfMonth.toISOString().split('T')[0];
     endDateInput.value = today.toISOString().split('T')[0];

     // Hardcode storage options (as per previous request)
     const chillerLocations = ["Chiller 1", "Chiller 2", "Chiller 3"];

     function populateStorageFilter() {
         storageFilter.innerHTML = '<option value="">Semua</option>'; // Reset
         chillerLocations.forEach(location => {
             const option = document.createElement('option');
             option.value = location;
             option.textContent = location;
             storageFilter.appendChild(option);
         });
     }

     async function fetchData() {
         loadingIndicator.classList.remove('hidden');
         adjustmentTableContainer.classList.add('hidden');
         noDataMessage.classList.add('hidden');
         paginationContainer.classList.add('hidden');

         const startDate = startDateInput.value;
         const endDate = endDateInput.value;

         const params = new URLSearchParams({
             action: 'get_summarized_adjustments',
             startDate: startDate,
             endDate: endDate,
         });

         try {
             const response = await fetch(`${API_URL}?${params.toString()}`);
             if (!response.ok) {
                 throw new Error(`HTTP error! status: ${response.status}`);
             }
             const result = await response.json();

             if (result.status === 'success') {
                 allSummarizedData = result.data;
                 dailyDetailsCache = {}; // Clear cache when new summarized data is loaded
                 currentPage = 1;
                 applyFiltersAndRender();
             } else {
                 console.error('Error fetching data:', result.message);
                 allSummarizedData = [];
                 dailyDetailsCache = {};
                 applyFiltersAndRender();
             }
         } catch (error) {
             console.error('Fetch error:', error);
             allSummarizedData = [];
             dailyDetailsCache = {};
             applyFiltersAndRender();
         } finally {
             loadingIndicator.classList.add('hidden');
         }
     }

     function applyFiltersAndRender() {
         const storage = storageFilter.value;
         const itemSearch = itemSearchInput.value.toLowerCase();
         const type = typeFilter.value;

         filteredData = allSummarizedData.filter(item => {
             const matchesStorage = storage === '' || item.storage === storage;
             const matchesItem = itemSearch === '' || item.item.toLowerCase().includes(itemSearch);
             const matchesType = type === '' || item.adjustment_type === type;
             return matchesStorage && matchesItem && matchesType;
         });

         const chillerTotals = filteredData.reduce((acc, item) => {
             const storage = item.storage;
             if (!acc[storage]) {
                 acc[storage] = {
                     total_ekor_pack: 0,
                     total_quantity_kg: 0
                 };
             }
             acc[storage].total_ekor_pack += parseFloat(item.total_ekor_pack || 0);
             acc[storage].total_quantity_kg += parseFloat(item.total_quantity_kg || 0);
             return acc;
         }, {});

         totalPages = Math.ceil(filteredData.length / rowsPerPage);
         if (currentPage > totalPages && totalPages > 0) {
             currentPage = totalPages;
         } else if (totalPages === 0) {
             currentPage = 1;
         }

         const startIndex = (currentPage - 1) * rowsPerPage;
         const endIndex = startIndex + rowsPerPage;
         const dataForPage = filteredData.slice(startIndex, endIndex);

         renderTable(dataForPage, chillerTotals);
         updatePaginationControls();

         if (filteredData.length === 0) {
             noDataMessage.classList.remove('hidden');
             adjustmentTableContainer.classList.add('hidden');
             paginationContainer.classList.add('hidden');
         } else {
             noDataMessage.classList.add('hidden');
             adjustmentTableContainer.classList.remove('hidden');
             paginationContainer.classList.remove('hidden');
         }
     }

     // Perbaikan: Fungsi renderTable sekarang menerima parameter chillerTotals
     function renderTable(data, chillerTotals) {
         const currentTableBody = typeof adjustmentTableBody !== 'undefined' ? adjustmentTableBody : document.querySelector('#adjustmentTable tbody');
         if (!currentTableBody) {
             console.error('Error: Elemen tabel body (tbody) tidak ditemukan.');
             return;
         }
         currentTableBody.innerHTML = '';

         let grandTotalEkorPack = 0;
         let grandTotalKg = 0;
         for (const storage in chillerTotals) {
             grandTotalEkorPack += parseFloat(chillerTotals[storage].total_ekor_pack || 0);
             grandTotalKg += parseFloat(chillerTotals[storage].total_quantity_kg || 0);
         }

         const tableHeader = document.querySelector('#adjustmentTable thead');
         if (tableHeader) {
             const ekorHeader = Array.from(tableHeader.querySelectorAll('th')).find(th => th.textContent.includes('Total Ekor/Pack'));
             const kgHeader = Array.from(tableHeader.querySelectorAll('th')).find(th => th.textContent.includes('Total Quantity (Kg)'));

             if (ekorHeader) {
                 ekorHeader.innerHTML = `Total Ekor/Pack<br><span class="font-bold text-lg">${grandTotalEkorPack}</span>`;
             }
             if (kgHeader) {
                 kgHeader.innerHTML = `Total Quantity (Kg)<br><span class="font-bold text-lg">${grandTotalKg.toFixed(2)} Kg</span>`;
             }
         }

         const groupedData = data.reduce((acc, item) => {
             const storage = item.storage;
             if (!acc[storage]) {
                 acc[storage] = [];
             }
             acc[storage].push(item);
             return acc;
         }, {});

         let rowIndex = 1 + (currentPage - 1) * rowsPerPage;

         for (const storage in groupedData) {
             const totalPerChiller = chillerTotals[storage] || {
                 total_ekor_pack: 0,
                 total_quantity_kg: 0
             };
             const groupHeaderRow = document.createElement('tr');
             groupHeaderRow.classList.add('bg-blue-300', 'font-bold', 'text-gray-800');
             groupHeaderRow.innerHTML = `
            <td colspan="3" class="px-6 py-2 text-left text-sm">Storage: ${storage}</td>
            <td class="px-6 py-2 text-sm text-gray-800">${totalPerChiller.total_ekor_pack}</td>
            <td class="px-6 py-2 text-sm text-gray-800">${totalPerChiller.total_quantity_kg.toFixed(2)} Kg</td>
            <td colspan="2"></td>
        `;
             currentTableBody.appendChild(groupHeaderRow);

             groupedData[storage].forEach((item) => {
                 const rowId = `detail-${item.storage.replace(/\s/g, '-')}-${item.item.replace(/\s/g, '-')}`;

                 const mainRow = document.createElement('tr');
                 mainRow.id = rowId;
                 mainRow.classList.add('bg-blue-200', 'border-b', 'border-gray-200');
                 mainRow.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${rowIndex++}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${item.storage}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.item}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.total_ekor_pack}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.total_quantity_kg.toFixed(2)} Kg</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.adjustment_type}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button class="toggle-detail-btn text-blue-600 hover:text-blue-900"
                            data-storage="${item.storage}"
                            data-item="${item.item}"
                            data-expanded="false">
                        <i class="fas fa-chevron-down mr-2"></i>Detail
                    </button>
                </td>
            `;
                 currentTableBody.appendChild(mainRow);

                 const detailRow = document.createElement('tr');
                 detailRow.classList.add('detail-row', 'hidden');
                 detailRow.dataset.parentRowId = rowId;
                 detailRow.innerHTML = `
                <td colspan="7" class="p-4 bg-gray-100">
                    <div class="p-2 space-y-4">
                        <div class="loading-detail-indicator text-center text-gray-500 hidden">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Memuat detail...
                        </div>
                        <div class="no-detail-message text-center text-gray-500 hidden">
                            Tidak ada data detail yang ditemukan.
                        </div>
                        <div class="overflow-x-auto">
                            <table class="detail-table min-w-full divide-y divide-gray-200 hidden">
                                <thead class="bg-gray-200">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">No</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Tanggal</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Item</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Ekor/Pack</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Quantity (Kg)</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody class="detail-table-body bg-white divide-y divide-gray-200">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </td>
            `;
                 currentTableBody.appendChild(detailRow);
             });
         }
     }
     async function toggleDetail(button) {
         const mainRow = button.closest('tr');
         const parentRowId = mainRow.id;
         const expanded = button.dataset.expanded === 'true';

         const detailRow = document.querySelector(`tr[data-parent-row-id="${parentRowId}"]`);

         if (!detailRow || !detailRow.classList.contains('detail-row')) {
             console.error(`ERROR: Detail row with ID ${parentRowId} not found.`);
             return;
         }

         const detailTableBody = detailRow.querySelector('.detail-table-body');
         const loadingDetailIndicator = detailRow.querySelector('.loading-detail-indicator');
         const noDetailMessage = detailRow.querySelector('.no-detail-message');
         const detailTable = detailRow.querySelector('.detail-table');

         if (expanded) {
             detailRow.classList.add('hidden');
             button.dataset.expanded = 'false';
             button.innerHTML = '<i class="fas fa-chevron-down mr-2"></i>Detail';
         } else {
             detailRow.classList.remove('hidden');

             loadingDetailIndicator.classList.remove('hidden');
             detailTableBody.innerHTML = '';
             detailTable.classList.add('hidden');
             noDetailMessage.classList.add('hidden');

             const storage = button.dataset.storage;
             const item = button.dataset.item;

             const cacheKey = `${storage}_${item}`;
             let dailyDetails = dailyDetailsCache[cacheKey];

             if (!dailyDetails) {
                 const startDate = startDateInput.value;
                 const endDate = endDateInput.value;

                 const params = new URLSearchParams({
                     action: 'get_daily_adjustment_details',
                     storage: storage,
                     item: item,
                     startDate: startDate,
                     endDate: endDate
                 });

                 try {
                     const response = await fetch(`${API_URL}?${params.toString()}`);
                     if (!response.ok) {
                         throw new Error(`HTTP error! status: ${response.status}`);
                     }
                     const result = await response.json();

                     if (result.status === 'success') {
                         dailyDetails = result.data;
                         dailyDetailsCache[cacheKey] = dailyDetails;
                     } else {
                         dailyDetails = [];
                         console.error('Error fetching daily details:', result.message);
                     }
                 } catch (error) {
                     dailyDetails = [];
                     console.error('Fetch error for daily details:', error);
                 } finally {
                     loadingDetailIndicator.classList.add('hidden');
                 }
             } else {
                 loadingDetailIndicator.classList.add('hidden');
             }

             if (dailyDetails.length > 0) {
                 let detailRowNo = 1;
                 dailyDetails.forEach(dailyDetail => {
                     const tr = document.createElement('tr');
                     tr.innerHTML = `
                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-800">${detailRowNo++}.</td>
                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-800">${dailyDetail.date}</td>
                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-800">${dailyDetail.item}</td>
                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-800">${dailyDetail.daily_ekor_pack}</td>
                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-800">${dailyDetail.daily_quantity_kg.toFixed(2)} Kg</td>
                    <td class="px-4 py-2 text-sm text-gray-800 max-w-xs truncate" title="${dailyDetail.keterangan || ''}">${dailyDetail.keterangan || ''}</td>
                `;
                     detailTableBody.appendChild(tr);
                 });
                 detailTable.classList.remove('hidden');
                 noDetailMessage.classList.add('hidden');
             } else {
                 detailTable.classList.add('hidden');
                 noDetailMessage.classList.remove('hidden');
             }

             button.dataset.expanded = 'true';
             button.innerHTML = '<i class="fas fa-chevron-up mr-2"></i>Sembunyikan';
         }
     }

     function updatePaginationControls() {
         currentPageSpan.textContent = currentPage;
         totalPagesSpan.textContent = totalPages;

         prevPageBtn.disabled = (currentPage === 1);
         nextPageBtn.disabled = (currentPage === totalPages || totalPages === 0);
     }

     // Event Listeners for filters
     startDateInput.addEventListener('change', () => {
         dailyDetailsCache = {};
         fetchData();
     });
     endDateInput.addEventListener('change', () => {
         dailyDetailsCache = {};
         fetchData();
     });
     storageFilter.addEventListener('change', () => {
         currentPage = 1;
         applyFiltersAndRender();
     });
     itemSearchInput.addEventListener('input', () => {
         currentPage = 1;
         applyFiltersAndRender();
     });
     typeFilter.addEventListener('change', () => {
         currentPage = 1;
         applyFiltersAndRender();
     });

     resetFiltersBtn.addEventListener('click', () => {
         const today = new Date();
         const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
         startDateInput.value = firstDayOfMonth.toISOString().split('T')[0];
         endDateInput.value = today.toISOString().split('T')[0];
         storageFilter.value = '';
         itemSearchInput.value = '';
         typeFilter.value = '';
         dailyDetailsCache = {};
         fetchData();
     });

     // Event Listeners for Pagination
     prevPageBtn.addEventListener('click', () => {
         if (currentPage > 1) {
             currentPage--;
             applyFiltersAndRender();
         }
     });

     nextPageBtn.addEventListener('click', () => {
         if (currentPage < totalPages) {
             currentPage++;
             applyFiltersAndRender();
         }
     });

     // Event Listener for Toggle Detail Buttons
     adjustmentTableBody.addEventListener('click', (event) => {
         const button = event.target.closest('.toggle-detail-btn');
         if (button) {
             toggleDetail(button);
         }
     });

     expandAllBtn.addEventListener('click', async () => {
         // Memperbaiki selektor agar hanya mencari tombol yang terlihat di tabel
         const visibleToggleButtons = document.querySelectorAll('#adjustmentTable tbody .toggle-detail-btn');

         // Tampilkan indikator loading saat proses dimulai
         loadingIndicator.classList.remove('hidden');

         // Iterasi melalui setiap tombol dan memanggil fungsi toggleDetail() untuk membukanya
         for (const button of visibleToggleButtons) {
             // Hanya panggil toggleDetail jika baris belum diperluas
             if (button.dataset.expanded === 'false') {
                 await toggleDetail(button);
             }
         }

         // Sembunyikan indikator loading setelah semua selesai
         loadingIndicator.classList.add('hidden');
     });

     collapseAllBtn.addEventListener('click', () => {
         // Memperbaiki selektor untuk tombol
         const toggleButtons = document.querySelectorAll('.toggle-detail-btn');

         // Memperbaiki selektor untuk baris detail agar sesuai dengan kelas .detail-row
         const detailRows = document.querySelectorAll('.detail-row');

         // Mengubah tampilan tombol kembali ke "Detail"
         toggleButtons.forEach(button => {
             if (button.dataset.expanded === 'true') {
                 button.dataset.expanded = 'false';
                 button.innerHTML = '<i class="fas fa-chevron-down mr-2"></i>Detail';
             }
         });

         // Menyembunyikan semua baris detail yang terbuka
         detailRows.forEach(row => row.classList.add('hidden'));
     });

     // --- Export Functions ---
     exportDropdownBtn.addEventListener('click', (event) => {
         event.stopPropagation();
         exportDropdownMenu.classList.toggle('hidden');
     });

     window.addEventListener('click', (event) => {
         if (!exportDropdownBtn.contains(event.target) && !exportDropdownMenu.contains(event.target)) {
             exportDropdownMenu.classList.add('hidden');
         }
     });

     // Helper function to apply styles (borders, bold, etc.) to a range of cells
     function setCellStyle(ws, range, style) {
         const borderStyle = {
             top: {
                 style: "thin"
             },
             bottom: {
                 style: "thin"
             },
             left: {
                 style: "thin"
             },
             right: {
                 style: "thin"
             }
         };

         for (let R = range.s.r; R <= range.e.r; ++R) {
             for (let C = range.s.c; C <= range.e.c; ++C) {
                 const cellRef = XLSX.utils.encode_cell({
                     r: R,
                     c: C
                 });
                 if (!ws[cellRef]) ws[cellRef] = {};
                 if (style.bold) {
                     if (!ws[cellRef].s) ws[cellRef].s = {};
                     if (!ws[cellRef].s.font) ws[cellRef].s.font = {};
                     ws[cellRef].s.font.bold = true;
                 }
                 if (style.border) {
                     if (!ws[cellRef].s) ws[cellRef].s = {};
                     ws[cellRef].s.border = borderStyle;
                 }
                 if (style.fill) {
                     if (!ws[cellRef].s) ws[cellRef].s = {};
                     ws[cellRef].s.fill = {
                         fgColor: {
                             rgb: style.fill
                         }
                     };
                 }
                 if (style.alignment) {
                     if (!ws[cellRef].s) ws[cellRef].s = {};
                     ws[cellRef].s.alignment = {
                         ...ws[cellRef].s.alignment,
                         ...style.alignment
                     };
                 }
                 if (style.font_size) {
                     if (!ws[cellRef].s) ws[cellRef].s = {};
                     if (!ws[cellRef].s.font) ws[cellRef].s.font = {};
                     ws[cellRef].s.font.sz = style.font_size;
                 }
             }
         }
     }

     // Helper function to calculate column widths
     function calculateColumnWidths(data, headers, includeEmptyCol = false) {
         const widths = headers.map(header => ({
             wch: String(header).length + 2
         })); // Start with header length

         data.forEach(row => {
             row.forEach((cell, i) => {
                 const cellValue = String(cell);
                 const currentWidth = widths[i] ? widths[i].wch : 0;
                 if (cellValue.length + 2 > currentWidth) { // +2 for padding
                     widths[i] = {
                         wch: cellValue.length + 2
                     };
                 }
             });
         });

         // If including an empty column for indentation, set a fixed small width
         if (includeEmptyCol) {
             widths.unshift({
                 wch: 3
             }); // For the indentation column (A)
         }

         return widths;
     }

     //  dengan type transaksi
     //  async function exportSummarizedDataToXLSX() {
     //      loadingIndicator.classList.remove('hidden');
     //      const originalNoDataDisplay = noDataMessage.style.display;
     //      noDataMessage.style.display = 'none';

     //      const startDate = startDateInput.value;
     //      const endDate = endDateInput.value;
     //      const storage = storageFilter.value;
     //      const itemSearch = itemSearchInput.value;
     //      const type = typeFilter.value;

     //      const params = new URLSearchParams({
     //          action: 'get_export_summary_data',
     //          startDate: startDate,
     //          endDate: endDate,
     //          storage: storage,
     //          itemSearch: itemSearch,
     //          type: type
     //      });

     //      try {
     //          const response = await fetch(`${API_URL}?${params.toString()}`);
     //          if (!response.ok) {
     //              throw new Error(`HTTP error! status: ${response.status}`);
     //          }
     //          const result = await response.json();

     //          if (result.status === 'success') {
     //              const dataToExport = result.data;
     //              if (dataToExport.length === 0) {
     //                  alert("Tidak ada data ringkasan yang sesuai filter untuk diekspor.");
     //                  return;
     //              }

     //              const wb = XLSX.utils.book_new();
     //              const ws = XLSX.utils.aoa_to_sheet([]);

     //              let rowIndex = 0;
     //              const headers = ["No.", "Storage", "Item", "Total Ekor/Pack", "Total Quantity (Kg)", "Type Adjustment"];
     //              const numCols = headers.length;

     //              // Title with enhanced styling
     //              const titleText = "LAPORAN ADJUSTMENT BARANG (RINGKASAN)";
     //              XLSX.utils.sheet_add_aoa(ws, [
     //                  [titleText]
     //              ], {
     //                  origin: `A${rowIndex + 1}`
     //              });
     //              ws['!merges'] = [{
     //                  s: {
     //                      r: rowIndex,
     //                      c: 0
     //                  },
     //                  e: {
     //                      r: rowIndex,
     //                      c: numCols - 1
     //                  }
     //              }];
     //              setCellStyle(ws, {
     //                  s: {
     //                      r: rowIndex,
     //                      c: 0
     //                  },
     //                  e: {
     //                      r: rowIndex,
     //                      c: numCols - 1
     //                  }
     //              }, {
     //                  font_size: 16,
     //                  bold: true,
     //                  alignment: {
     //                      horizontal: "center",
     //                      vertical: "center"
     //                  },
     //                  fill: "FF4F81BD", // Blue background
     //                  font_color: "FFFFFFFF", // White text
     //                  border: {
     //                      top: {
     //                          style: "medium",
     //                          color: {
     //                              rgb: "FF000000"
     //                          }
     //                      },
     //                      bottom: {
     //                          style: "medium",
     //                          color: {
     //                              rgb: "FF000000"
     //                          }
     //                      },
     //                      left: {
     //                          style: "medium",
     //                          color: {
     //                              rgb: "FF000000"
     //                          }
     //                      },
     //                      right: {
     //                          style: "medium",
     //                          color: {
     //                              rgb: "FF000000"
     //                          }
     //                      }
     //                  }
     //              });
     //              rowIndex += 2;

     //              // Date Range with improved styling
     //              const dateRangeText = `PERIODE: ${startDate} S/D ${endDate}`;
     //              XLSX.utils.sheet_add_aoa(ws, [
     //                  [dateRangeText]
     //              ], {
     //                  origin: `A${rowIndex + 1}`
     //              });
     //              ws['!merges'].push({
     //                  s: {
     //                      r: rowIndex,
     //                      c: 0
     //                  },
     //                  e: {
     //                      r: rowIndex,
     //                      c: numCols - 1
     //                  }
     //              });
     //              setCellStyle(ws, {
     //                  s: {
     //                      r: rowIndex,
     //                      c: 0
     //                  },
     //                  e: {
     //                      r: rowIndex,
     //                      c: numCols - 1
     //                  }
     //              }, {
     //                  font_size: 12,
     //                  bold: true,
     //                  italic: true,
     //                  alignment: {
     //                      horizontal: "center"
     //                  },
     //                  border: {
     //                      bottom: {
     //                          style: "thin",
     //                          color: {
     //                              rgb: "FF000000"
     //                          }
     //                      }
     //                  }
     //              });
     //              rowIndex += 2;

     //              // Headers with improved styling
     //              XLSX.utils.sheet_add_aoa(ws, [headers], {
     //                  origin: `A${rowIndex + 1}`
     //              });
     //              const headerRange = {
     //                  s: {
     //                      r: rowIndex,
     //                      c: 0
     //                  },
     //                  e: {
     //                      r: rowIndex,
     //                      c: headers.length - 1
     //                  }
     //              };
     //              setCellStyle(ws, headerRange, {
     //                  bold: true,
     //                  alignment: {
     //                      horizontal: "center",
     //                      vertical: "center"
     //                  },
     //                  fill: "FFDCE6F1", // Light blue background
     //                  font_color: "FF000000",
     //                  border: {
     //                      top: {
     //                          style: "thin",
     //                          color: {
     //                              rgb: "FF000000"
     //                          }
     //                      },
     //                      bottom: {
     //                          style: "thin",
     //                          color: {
     //                              rgb: "FF000000"
     //                          }
     //                      },
     //                      left: {
     //                          style: "thin",
     //                          color: {
     //                              rgb: "FF000000"
     //                          }
     //                      },
     //                      right: {
     //                          style: "thin",
     //                          color: {
     //                              rgb: "FF000000"
     //                          }
     //                      }
     //                  }
     //              });
     //              rowIndex++;

     //              const excelData = dataToExport.map((item, index) => [
     //                  index + 1,
     //                  item.storage,
     //                  item.item,
     //                  item.total_ekor_pack,
     //                  parseFloat(item.total_quantity_kg),
     //                  item.adjustment_type
     //              ]);

     //              XLSX.utils.sheet_add_aoa(ws, excelData, {
     //                  origin: `A${rowIndex + 1}`
     //              });

     //              // Apply border to all data cells
     //              const dataRange = {
     //                  s: {
     //                      r: rowIndex,
     //                      c: 0
     //                  },
     //                  e: {
     //                      r: rowIndex + excelData.length - 1,
     //                      c: headers.length - 1
     //                  }
     //              };
     //              setCellStyle(ws, dataRange, {
     //                  border: {
     //                      top: {
     //                          style: "thin",
     //                          color: {
     //                              rgb: "FF000000"
     //                          }
     //                      },
     //                      bottom: {
     //                          style: "thin",
     //                          color: {
     //                              rgb: "FF000000"
     //                          }
     //                      },
     //                      left: {
     //                          style: "thin",
     //                          color: {
     //                              rgb: "FF000000"
     //                          }
     //                      },
     //                      right: {
     //                          style: "thin",
     //                          color: {
     //                              rgb: "FF000000"
     //                          }
     //                      }
     //                  },
     //                  alignment: {
     //                      vertical: "top"
     //                  }
     //              });

     //              // Add totals row
     //              rowIndex += excelData.length;
     //              const totalEkorPack = dataToExport.reduce((sum, item) => sum + (parseInt(item.total_ekor_pack) || 0), 0);
     //              const totalQuantityKg = dataToExport.reduce((sum, item) => sum + (parseFloat(item.total_quantity_kg) || 0), 0);

     //              const totalsRow = ["TOTAL", "", "", totalEkorPack, totalQuantityKg, ""];
     //              XLSX.utils.sheet_add_aoa(ws, [totalsRow], {
     //                  origin: `A${rowIndex + 1}`
     //              });
     //              setCellStyle(ws, {
     //                  s: {
     //                      r: rowIndex,
     //                      c: 0
     //                  },
     //                  e: {
     //                      r: rowIndex,
     //                      c: headers.length - 1
     //                  }
     //              }, {
     //                  bold: true,
     //                  fill: "FFC6E0B4", // Light green background
     //                  border: {
     //                      top: {
     //                          style: "medium",
     //                          color: {
     //                              rgb: "FF000000"
     //                          }
     //                      },
     //                      bottom: {
     //                          style: "double",
     //                          color: {
     //                              rgb: "FF000000"
     //                          }
     //                      },
     //                      left: {
     //                          style: "thin",
     //                          color: {
     //                              rgb: "FF000000"
     //                          }
     //                      },
     //                      right: {
     //                          style: "thin",
     //                          color: {
     //                              rgb: "FF000000"
     //                          }
     //                      }
     //                  }
     //              });

     //              // Calculate and set column widths
     //              const allCellsData = [headers, ...excelData, totalsRow];
     //              ws['!cols'] = calculateColumnWidths(allCellsData, headers);

     //              XLSX.utils.book_append_sheet(wb, ws, "Ringkasan Adjustment");
     //              XLSX.writeFile(wb, "Laporan_Adjustment_Ringkasan.xlsx");

     //          } else {
     //              alert('Error fetching summary data for export: ' + result.message);
     //              console.error('Error fetching summary data for export:', result.message);
     //          }
     //      } catch (error) {
     //          alert('Fetch error during summary export: ' + error.message);
     //          console.error('Fetch error during summary export:', error);
     //      } finally {
     //          loadingIndicator.classList.add('hidden');
     //          noDataMessage.style.display = originalNoDataDisplay;
     //      }
     //  }

     function calculateColumnWidths(rows, headers) {
         const widths = headers.map(h => ({
             wch: h.length
         }));

         rows.forEach(row => {
             if (Array.isArray(row)) {
                 row.forEach((cell, i) => {
                     const cellValue = String(cell);
                     if (cellValue.length > widths[i].wch) {
                         widths[i].wch = cellValue.length;
                     }
                 });
             }
         });

         return widths;
     }

     function setCellStyle(ws, range, style) {
         const {
             s,
             e
         } = range;
         for (let R = s.r; R <= e.r; ++R) {
             for (let C = s.c; C <= e.c; ++C) {
                 const cellRef = XLSX.utils.encode_cell({
                     r: R,
                     c: C
                 });
                 if (!ws[cellRef]) ws[cellRef] = {};
                 ws[cellRef].s = {
                     ...ws[cellRef].s,
                     ...style
                 };
             }
         }
     }

     async function exportSummarizedDataToXLSX() {
         loadingIndicator.classList.remove('hidden');
         const originalNoDataDisplay = noDataMessage.style.display;
         noDataMessage.style.display = 'none';

         const startDate = startDateInput.value;
         const endDate = endDateInput.value;
         const storage = storageFilter.value;
         const itemSearch = itemSearchInput.value;
         const type = typeFilter.value;

         const params = new URLSearchParams({
             action: 'get_export_summary_data',
             startDate: startDate,
             endDate: endDate,
             storage: storage,
             itemSearch: itemSearch,
             type: type
         });

         try {
             const response = await fetch(`${API_URL}?${params.toString()}`);
             if (!response.ok) {
                 throw new Error(`HTTP error! status: ${response.status}`);
             }
             const result = await response.json();

             if (result.status === 'success') {
                 const dataToExport = result.data;
                 if (dataToExport.length === 0) {
                     alert("Tidak ada data ringkasan yang sesuai filter untuk diekspor.");
                     return;
                 }

                 // Kelompokkan dan hitung sub-total per chiller
                 const groupedData = dataToExport.reduce((acc, item) => {
                     const storageName = item.storage;
                     if (!acc[storageName]) {
                         acc[storageName] = {
                             items: [],
                             subTotalEkorPack: 0,
                             subTotalKg: 0
                         };
                     }
                     acc[storageName].items.push(item);
                     acc[storageName].subTotalEkorPack += parseFloat(item.total_ekor_pack || 0);
                     acc[storageName].subTotalKg += parseFloat(item.total_quantity_kg || 0);
                     return acc;
                 }, {});

                 // Hitung Grand Total
                 const grandTotalEkorPack = dataToExport.reduce((sum, item) => sum + (parseFloat(item.total_ekor_pack) || 0), 0);
                 const grandTotalKg = dataToExport.reduce((sum, item) => sum + (parseFloat(item.total_quantity_kg) || 0), 0);

                 const wb = XLSX.utils.book_new();
                 const ws = XLSX.utils.aoa_to_sheet([]);
                 let rowIndex = 0;
                 const headers = ["No.", "Storage", "Item", "Total Ekor/Pack", "Total Quantity (Kg)"];
                 const numCols = headers.length;
                 const storageNames = Object.keys(groupedData);
                 const allRowsDataForWidths = [headers];

                 const borderStyle = {
                     top: {
                         style: "thin"
                     },
                     bottom: {
                         style: "thin"
                     },
                     left: {
                         style: "thin"
                     },
                     right: {
                         style: "thin"
                     }
                 };

                 // 1. Tambahkan judul utama
                 const titleText = "LAPORAN ADJUSTMENT BARANG (RINGKASAN)";
                 XLSX.utils.sheet_add_aoa(ws, [
                     [titleText]
                 ], {
                     origin: `A${rowIndex + 1}`
                 });
                 ws['!merges'] = [{
                     s: {
                         r: rowIndex,
                         c: 0
                     },
                     e: {
                         r: rowIndex,
                         c: numCols - 1
                     }
                 }];
                 setCellStyle(ws, {
                     s: {
                         r: rowIndex,
                         c: 0
                     },
                     e: {
                         r: rowIndex,
                         c: numCols - 1
                     }
                 }, {
                     font_size: 16,
                     bold: true,
                     alignment: {
                         horizontal: "center"
                     },
                     fill: "FF4F81BD",
                     font_color: "FFFFFFFF",
                     border: {
                         top: {
                             style: "medium"
                         },
                         bottom: {
                             style: "medium"
                         },
                         left: {
                             style: "medium"
                         },
                         right: {
                             style: "medium"
                         }
                     }
                 });
                 rowIndex += 2;

                 // 2. Tambahkan rentang tanggal
                 const dateRangeText = `PERIODE: ${startDate} S/D ${endDate}`;
                 XLSX.utils.sheet_add_aoa(ws, [
                     [dateRangeText]
                 ], {
                     origin: `A${rowIndex + 1}`
                 });
                 ws['!merges'].push({
                     s: {
                         r: rowIndex,
                         c: 0
                     },
                     e: {
                         r: rowIndex,
                         c: numCols - 1
                     }
                 });
                 setCellStyle(ws, {
                     s: {
                         r: rowIndex,
                         c: 0
                     },
                     e: {
                         r: rowIndex,
                         c: numCols - 1
                     }
                 }, {
                     font_size: 12,
                     bold: true,
                     italic: true,
                     alignment: {
                         horizontal: "center"
                     },
                     border: {
                         bottom: {
                             style: "thin"
                         }
                     }
                 });
                 rowIndex += 2;

                 // 3. Tambahkan baris header tabel
                 XLSX.utils.sheet_add_aoa(ws, [headers], {
                     origin: `A${rowIndex + 1}`
                 });
                 setCellStyle(ws, {
                     s: {
                         r: rowIndex,
                         c: 0
                     },
                     e: {
                         r: rowIndex,
                         c: numCols - 1
                     }
                 }, {
                     bold: true,
                     alignment: {
                         horizontal: "center"
                     },
                     fill: "FFDCE6F1",
                     font_color: "FF000000",
                     border: borderStyle
                 });
                 rowIndex++;

                 let currentNo = 1;
                 // 4. Iterasi setiap kelompok chiller untuk menambahkan data dan sub-total
                 storageNames.forEach((storageName, index) => {
                     const group = groupedData[storageName];

                     // Tambahkan baris data
                     const excelData = group.items.map(item => [
                         currentNo++,
                         item.storage,
                         item.item,
                         item.total_ekor_pack,
                         parseFloat(item.total_quantity_kg)
                     ]);
                     XLSX.utils.sheet_add_aoa(ws, excelData, {
                         origin: `A${rowIndex + 1}`
                     });

                     // Terapkan border ke setiap sel data
                     setCellStyle(ws, {
                         s: {
                             r: rowIndex,
                             c: 0
                         },
                         e: {
                             r: rowIndex + excelData.length - 1,
                             c: numCols - 1
                         }
                     }, {
                         border: borderStyle
                     });

                     // Tambahkan baris sub-total di bawah data chiller
                     rowIndex += excelData.length;
                     const subTotalsRow = ["SUB-TOTAL", "", "", group.subTotalEkorPack, group.subTotalKg];
                     XLSX.utils.sheet_add_aoa(ws, [subTotalsRow], {
                         origin: `A${rowIndex + 1}`
                     });
                     setCellStyle(ws, {
                         s: {
                             r: rowIndex,
                             c: 0
                         },
                         e: {
                             r: rowIndex,
                             c: numCols - 1
                         }
                     }, {
                         bold: true,
                         fill: "FFDCE6F1",
                         font_color: "FF000000",
                         border: borderStyle
                     });
                     ws['!merges'].push({
                         s: {
                             r: rowIndex,
                             c: 0
                         },
                         e: {
                             r: rowIndex,
                             c: 2
                         }
                     });

                     allRowsDataForWidths.push(...excelData, subTotalsRow);

                     // Tambahkan 2 baris kosong jika bukan chiller terakhir
                     if (index < storageNames.length - 1) {
                         rowIndex += 2;
                         XLSX.utils.sheet_add_aoa(ws, [
                             [''],
                             ['']
                         ], {
                             origin: `A${rowIndex}`
                         });
                     }
                 });

                 // 5. Tambahkan baris Grand Total di bagian paling bawah
                 rowIndex += 2;
                 const grandTotalsRow = ["GRAND TOTAL", "", "", grandTotalEkorPack, grandTotalKg];
                 XLSX.utils.sheet_add_aoa(ws, [grandTotalsRow], {
                     origin: `A${rowIndex + 1}`
                 });
                 setCellStyle(ws, {
                     s: {
                         r: rowIndex,
                         c: 0
                     },
                     e: {
                         r: rowIndex,
                         c: numCols - 1
                     }
                 }, {
                     bold: true,
                     fill: "FFC6E0B4",
                     border: {
                         top: {
                             style: "medium"
                         },
                         bottom: {
                             style: "double"
                         },
                         left: {
                             style: "thin"
                         },
                         right: {
                             style: "thin"
                         }
                     }
                 });
                 ws['!merges'].push({
                     s: {
                         r: rowIndex,
                         c: 0
                     },
                     e: {
                         r: rowIndex,
                         c: 2
                     }
                 });
                 allRowsDataForWidths.push(grandTotalsRow);

                 // 6. Atur lebar kolom otomatis
                 ws['!cols'] = calculateColumnWidths(allRowsDataForWidths, headers);

                 XLSX.utils.book_append_sheet(wb, ws, "Ringkasan Adjustment");
                 XLSX.writeFile(wb, "Laporan_Adjustment_Ringkasan.xlsx");

             } else {
                 alert('Error fetching summary data for export: ' + result.message);
                 console.error('Error fetching summary data for export:', result.message);
             }
         } catch (error) {
             alert('Fetch error during summary export: ' + error.message);
             console.error('Fetch error during summary export:', error);
         } finally {
             loadingIndicator.classList.add('hidden');
             noDataMessage.style.display = originalNoDataDisplay;
         }
     }
     //  //  dengan detail full
     //  async function exportAllDataWithDailyDetailsToXLSX() {
     //      loadingIndicator.classList.remove('hidden');
     //      const originalNoDataDisplay = noDataMessage.style.display;
     //      noDataMessage.style.display = 'none';

     //      const startDate = startDateInput.value;
     //      const endDate = endDateInput.value;
     //      const storage = storageFilter.value;
     //      const itemSearch = itemSearchInput.value;
     //      const type = typeFilter.value;

     //      try {
     //          // Fetch summarized data
     //          const summaryParams = new URLSearchParams({
     //              action: 'get_export_summary_data',
     //              startDate: startDate,
     //              endDate: endDate,
     //              storage: storage,
     //              itemSearch: itemSearch,
     //              type: type
     //          });
     //          const summaryResponse = await fetch(`${API_URL}?${summaryParams.toString()}`);
     //          if (!summaryResponse.ok) throw new Error(`HTTP error! status: ${summaryResponse.status} for summary data`);
     //          const summaryResult = await summaryResponse.json();
     //          const summaryData = summaryResult.status === 'success' ? summaryResult.data : [];

     //          if (summaryData.length === 0) {
     //              alert("Tidak ada data yang sesuai filter untuk diekspor (Ringkasan + Detail Harian).");
     //              return;
     //          }

     //          // Fetch all daily details data
     //          const allDetailsParams = new URLSearchParams({
     //              action: 'get_export_all_details_data',
     //              startDate: startDate,
     //              endDate: endDate,
     //              storage: storage,
     //              itemSearch: itemSearch,
     //              type: type
     //          });
     //          const allDetailsResponse = await fetch(`${API_URL}?${allDetailsParams.toString()}`);
     //          if (!allDetailsResponse.ok) throw new Error(`HTTP error! status: ${allDetailsResponse.status} for all details data`);
     //          const allDetailsResult = await allDetailsResponse.json();
     //          const allDailyDetails = allDetailsResult.status === 'success' ? allDetailsResult.data : [];

     //          const wb = XLSX.utils.book_new();
     //          const ws = XLSX.utils.aoa_to_sheet([]);

     //          let rowIndex = 0;
     //          const mainSummaryHeaders = ["NO.", "STORAGE", "ITEM", "TOTAL EKOR/PACK", "TOTAL QUANTITY (KG)", "TYPE ADJUSTMENT"];
     //          const mainNumCols = mainSummaryHeaders.length;

     //          // --- Report Title ---
     //          const mainTitle = "LAPORAN ADJUSTMENT BARANG LENGKAP";
     //          XLSX.utils.sheet_add_aoa(ws, [
     //              [mainTitle]
     //          ], {
     //              origin: `A${rowIndex + 1}`
     //          });
     //          ws['!merges'] = [{
     //              s: {
     //                  r: rowIndex,
     //                  c: 0
     //              },
     //              e: {
     //                  r: rowIndex,
     //                  c: mainNumCols - 1
     //              }
     //          }];
     //          setCellStyle(ws, {
     //              s: {
     //                  r: rowIndex,
     //                  c: 0
     //              },
     //              e: {
     //                  r: rowIndex,
     //                  c: mainNumCols - 1
     //              }
     //          }, {
     //              font_size: 18,
     //              bold: true,
     //              alignment: {
     //                  horizontal: "center",
     //                  vertical: "center"
     //              },
     //              fill: "FF4F81BD", // Blue background
     //              font_color: "FFFFFFFF", // White text
     //              border: {
     //                  top: {
     //                      style: "medium",
     //                      color: {
     //                          rgb: "FF000000"
     //                      }
     //                  },
     //                  bottom: {
     //                      style: "medium",
     //                      color: {
     //                          rgb: "FF000000"
     //                      }
     //                  },
     //                  left: {
     //                      style: "medium",
     //                      color: {
     //                          rgb: "FF000000"
     //                      }
     //                  },
     //                  right: {
     //                      style: "medium",
     //                      color: {
     //                          rgb: "FF000000"
     //                      }
     //                  }
     //              }
     //          });
     //          rowIndex += 2;

     //          // --- Date Range ---
     //          const dateRange = `PERIODE: ${startDate} S/D ${endDate}`;
     //          XLSX.utils.sheet_add_aoa(ws, [
     //              [dateRange]
     //          ], {
     //              origin: `A${rowIndex + 1}`
     //          });
     //          ws['!merges'].push({
     //              s: {
     //                  r: rowIndex,
     //                  c: 0
     //              },
     //              e: {
     //                  r: rowIndex,
     //                  c: mainNumCols - 1
     //              }
     //          });
     //          setCellStyle(ws, {
     //              s: {
     //                  r: rowIndex,
     //                  c: 0
     //              },
     //              e: {
     //                  r: rowIndex,
     //                  c: mainNumCols - 1
     //              }
     //          }, {
     //              font_size: 12,
     //              bold: true,
     //              italic: true,
     //              alignment: {
     //                  horizontal: "center"
     //              },
     //              border: {
     //                  bottom: {
     //                      style: "thin",
     //                      color: {
     //                          rgb: "FF000000"
     //                      }
     //                  }
     //              }
     //          });
     //          rowIndex += 3;

     //          // Add the single top-level header for summary
     //          XLSX.utils.sheet_add_aoa(ws, [mainSummaryHeaders], {
     //              origin: `A${rowIndex + 1}`
     //          });
     //          const topHeaderRange = {
     //              s: {
     //                  r: rowIndex,
     //                  c: 0
     //              },
     //              e: {
     //                  r: rowIndex,
     //                  c: mainSummaryHeaders.length - 1
     //              }
     //          };
     //          setCellStyle(ws, topHeaderRange, {
     //              bold: true,
     //              alignment: {
     //                  horizontal: "center",
     //                  vertical: "center"
     //              },
     //              fill: "FFDCE6F1", // Light blue background
     //              font_color: "FF000000",
     //              border: {
     //                  top: {
     //                      style: "thin",
     //                      color: {
     //                          rgb: "FF000000"
     //                      }
     //                  },
     //                  bottom: {
     //                      style: "thin",
     //                      color: {
     //                          rgb: "FF000000"
     //                      }
     //                  },
     //                  left: {
     //                      style: "thin",
     //                      color: {
     //                          rgb: "FF000000"
     //                      }
     //                  },
     //                  right: {
     //                      style: "thin",
     //                      color: {
     //                          rgb: "FF000000"
     //                      }
     //                  }
     //              }
     //          });
     //          rowIndex++;

     //          const allExportedDataRows = [mainSummaryHeaders];
     //          let globalItemNo = 1;
     //          let grandTotalEkorPack = 0;
     //          let grandTotalQuantityKg = 0;

     //          for (const summaryItem of summaryData) {
     //              // Calculate totals for this item
     //              const itemEkorPack = parseInt(summaryItem.total_ekor_pack) || 0;
     //              const itemQuantityKg = parseFloat(summaryItem.total_quantity_kg) || 0;
     //              grandTotalEkorPack += itemEkorPack;
     //              grandTotalQuantityKg += itemQuantityKg;

     //              // Summary Row for each item
     //              const summaryRowData = [
     //                  globalItemNo++,
     //                  summaryItem.storage,
     //                  summaryItem.item,
     //                  itemEkorPack,
     //                  itemQuantityKg,
     //                  summaryItem.adjustment_type
     //              ];
     //              XLSX.utils.sheet_add_aoa(ws, [summaryRowData], {
     //                  origin: `A${rowIndex + 1}`
     //              });
     //              const currentSummaryDataRange = {
     //                  s: {
     //                      r: rowIndex,
     //                      c: 0
     //                  },
     //                  e: {
     //                      r: rowIndex,
     //                      c: mainSummaryHeaders.length - 1
     //                  }
     //              };
     //              setCellStyle(ws, currentSummaryDataRange, {
     //                  border: {
     //                      top: {
     //                          style: "thin",
     //                          color: {
     //                              rgb: "FF000000"
     //                          }
     //                      },
     //                      bottom: {
     //                          style: "thin",
     //                          color: {
     //                              rgb: "FF000000"
     //                          }
     //                      },
     //                      left: {
     //                          style: "thin",
     //                          color: {
     //                              rgb: "FF000000"
     //                          }
     //                      },
     //                      right: {
     //                          style: "thin",
     //                          color: {
     //                              rgb: "FF000000"
     //                          }
     //                      }
     //                  },
     //                  fill: "FFE7E6E6" // Light gray background for summary rows
     //              });
     //              allExportedDataRows.push(summaryRowData);
     //              rowIndex++;

     //              // Daily Details Section
     //              const itemDailyDetails = allDailyDetails.filter(
     //                  detail => detail.summary_storage === summaryItem.storage && detail.summary_item === summaryItem.item
     //              );

     //              if (itemDailyDetails.length > 0) {
     //                  rowIndex++; // Add a blank line after summary row for separation

     //                  // Daily Detail Sub-header
     //                  const detailHeaders = ["", "NO.", "TANGGAL", "ITEM (DETAIL)", "EKOR/PACK", "QUANTITY (KG)", "KETERANGAN HARIAN"];
     //                  XLSX.utils.sheet_add_aoa(ws, [detailHeaders], {
     //                      origin: `A${rowIndex + 1}`
     //                  });
     //                  const currentDetailHeaderRange = {
     //                      s: {
     //                          r: rowIndex,
     //                          c: 0
     //                      },
     //                      e: {
     //                          r: rowIndex,
     //                          c: detailHeaders.length - 1
     //                      }
     //                  };
     //                  setCellStyle(ws, currentDetailHeaderRange, {
     //                      bold: true,
     //                      alignment: {
     //                          horizontal: "center",
     //                          vertical: "center"
     //                      },
     //                      fill: "FFF2F2F2", // Very light gray background
     //                      font_color: "FF000000",
     //                      border: {
     //                          top: {
     //                              style: "thin",
     //                              color: {
     //                                  rgb: "FF000000"
     //                              }
     //                          },
     //                          bottom: {
     //                              style: "thin",
     //                              color: {
     //                                  rgb: "FF000000"
     //                              }
     //                          },
     //                          left: {
     //                              style: "thin",
     //                              color: {
     //                                  rgb: "FF000000"
     //                              }
     //                          },
     //                          right: {
     //                              style: "thin",
     //                              color: {
     //                                  rgb: "FF000000"
     //                              }
     //                          }
     //                      }
     //                  });
     //                  allExportedDataRows.push(detailHeaders);
     //                  rowIndex++;

     //                  let dailyDetailNo = 1;
     //                  let detailEkorPackTotal = 0;
     //                  let detailQuantityKgTotal = 0;

     //                  itemDailyDetails.forEach(dailyDetail => {
     //                      const ekorPack = parseInt(dailyDetail.daily_ekor_pack) || 0;
     //                      const quantityKg = parseFloat(dailyDetail.daily_quantity_kg) || 0;
     //                      detailEkorPackTotal += ekorPack;
     //                      detailQuantityKgTotal += quantityKg;

     //                      const detailRow = [
     //                          "", // Empty for indentation
     //                          dailyDetailNo++,
     //                          dailyDetail.date,
     //                          dailyDetail.item,
     //                          ekorPack,
     //                          quantityKg,
     //                          dailyDetail.keterangan || ''
     //                      ];
     //                      XLSX.utils.sheet_add_aoa(ws, [detailRow], {
     //                          origin: `A${rowIndex + 1}`
     //                      });
     //                      const currentDetailDataRange = {
     //                          s: {
     //                              r: rowIndex,
     //                              c: 0
     //                          },
     //                          e: {
     //                              r: rowIndex,
     //                              c: detailHeaders.length - 1
     //                          }
     //                      };
     //                      setCellStyle(ws, currentDetailDataRange, {
     //                          border: {
     //                              top: {
     //                                  style: "thin",
     //                                  color: {
     //                                      rgb: "FF000000"
     //                                  }
     //                              },
     //                              bottom: {
     //                                  style: "thin",
     //                                  color: {
     //                                      rgb: "FF000000"
     //                                  }
     //                              },
     //                              left: {
     //                                  style: "thin",
     //                                  color: {
     //                                      rgb: "FF000000"
     //                                  }
     //                              },
     //                              right: {
     //                                  style: "thin",
     //                                  color: {
     //                                      rgb: "FF000000"
     //                                  }
     //                              }
     //                          }
     //                      });
     //                      allExportedDataRows.push(detailRow);
     //                      rowIndex++;
     //                  });

     //                  // Add subtotal for details
     //                  const detailSubtotalRow = ["", "SUB TOTAL", "", "", detailEkorPackTotal, detailQuantityKgTotal, ""];
     //                  XLSX.utils.sheet_add_aoa(ws, [detailSubtotalRow], {
     //                      origin: `A${rowIndex + 1}`
     //                  });
     //                  setCellStyle(ws, {
     //                      s: {
     //                          r: rowIndex,
     //                          c: 1
     //                      },
     //                      e: {
     //                          r: rowIndex,
     //                          c: 5
     //                      }
     //                  }, {
     //                      bold: true,
     //                      fill: "FFDDEBF7", // Light blue background
     //                      border: {
     //                          top: {
     //                              style: "thin",
     //                              color: {
     //                                  rgb: "FF000000"
     //                              }
     //                          },
     //                          bottom: {
     //                              style: "double",
     //                              color: {
     //                                  rgb: "FF000000"
     //                              }
     //                          },
     //                          left: {
     //                              style: "thin",
     //                              color: {
     //                                  rgb: "FF000000"
     //                              }
     //                          },
     //                          right: {
     //                              style: "thin",
     //                              color: {
     //                                  rgb: "FF000000"
     //                              }
     //                          }
     //                      }
     //                  });
     //                  allExportedDataRows.push(detailSubtotalRow);
     //                  rowIndex += 2; // Extra space after details
     //              } else {
     //                  // If no daily details
     //                  XLSX.utils.sheet_add_aoa(ws, [
     //                      ["", "TIDAK ADA DETAIL HARIAN UNTUK ITEM INI."]
     //                  ], {
     //                      origin: `A${rowIndex + 1}`
     //                  });
     //                  ws['!merges'].push({
     //                      s: {
     //                          r: rowIndex,
     //                          c: 1
     //                      },
     //                      e: {
     //                          r: rowIndex,
     //                          c: mainSummaryHeaders.length + 1
     //                      }
     //                  });
     //                  setCellStyle(ws, {
     //                      s: {
     //                          r: rowIndex,
     //                          c: 1
     //                      },
     //                      e: {
     //                          r: rowIndex,
     //                          c: mainSummaryHeaders.length + 1
     //                      }
     //                  }, {
     //                      font: {
     //                          italic: true,
     //                          color: {
     //                              rgb: "FF808080"
     //                          }
     //                      },
     //                      border: true,
     //                      alignment: {
     //                          horizontal: "center"
     //                      },
     //                      fill: "FFF2F2F2"
     //                  });
     //                  allExportedDataRows.push(["", "TIDAK ADA DETAIL HARIAN UNTUK ITEM INI."]);
     //                  rowIndex += 2;
     //              }
     //          }

     //          // Add grand totals row
     //          const grandTotalRow = ["GRAND TOTAL", "", "", grandTotalEkorPack, grandTotalQuantityKg, ""];
     //          XLSX.utils.sheet_add_aoa(ws, [grandTotalRow], {
     //              origin: `A${rowIndex + 1}`
     //          });
     //          setCellStyle(ws, {
     //              s: {
     //                  r: rowIndex,
     //                  c: 0
     //              },
     //              e: {
     //                  r: rowIndex,
     //                  c: mainSummaryHeaders.length - 1
     //              }
     //          }, {
     //              bold: true,
     //              fill: "FFC6E0B4", // Light green background
     //              border: {
     //                  top: {
     //                      style: "medium",
     //                      color: {
     //                          rgb: "FF000000"
     //                      }
     //                  },
     //                  bottom: {
     //                      style: "double",
     //                      color: {
     //                          rgb: "FF000000"
     //                      }
     //                  },
     //                  left: {
     //                      style: "thin",
     //                      color: {
     //                          rgb: "FF000000"
     //                      }
     //                  },
     //                  right: {
     //                      style: "thin",
     //                      color: {
     //                          rgb: "FF000000"
     //                      }
     //                  }
     //              }
     //          });
     //          allExportedDataRows.push(grandTotalRow);

     //          // Calculate and set column widths
     //          ws['!cols'] = calculateColumnWidths(allExportedDataRows, mainSummaryHeaders, true);

     //          XLSX.utils.book_append_sheet(wb, ws, "Laporan Lengkap Adjustment");
     //          XLSX.writeFile(wb, "Laporan_Adjustment_Lengkap.xlsx");

     //      } catch (error) {
     //          alert('Fetch error during all details export: ' + error.message);
     //          console.error('Fetch error during all details export:', error);
     //      } finally {
     //          loadingIndicator.classList.add('hidden');
     //          noDataMessage.style.display = originalNoDataDisplay;
     //      }
     //  }

     async function exportAllDataWithDailyDetailsToXLSX() {
         loadingIndicator.classList.remove('hidden');
         const originalNoDataDisplay = noDataMessage.style.display;
         noDataMessage.style.display = 'none';

         const startDate = startDateInput.value;
         const endDate = endDateInput.value;
         const storage = storageFilter.value;
         const itemSearch = itemSearchInput.value;
         const type = typeFilter.value;

         try {
             // Fetch summarized data to get the list of unique items
             const summaryParams = new URLSearchParams({
                 action: 'get_export_summary_data',
                 startDate: startDate,
                 endDate: endDate,
                 storage: storage,
                 itemSearch: itemSearch,
                 type: type
             });
             const summaryResponse = await fetch(`${API_URL}?${summaryParams.toString()}`);
             if (!summaryResponse.ok) throw new Error(`HTTP error! status: ${summaryResponse.status} for summary data`);
             const summaryResult = await summaryResponse.json();
             const summaryData = summaryResult.status === 'success' ? summaryResult.data : [];

             if (summaryData.length === 0) {
                 alert("Tidak ada data yang sesuai filter untuk diekspor (Ringkasan + Detail Harian).");
                 return;
             }

             // Fetch all daily details data
             const allDetailsParams = new URLSearchParams({
                 action: 'get_export_all_details_data',
                 startDate: startDate,
                 endDate: endDate,
                 storage: storage,
                 itemSearch: itemSearch,
                 type: type
             });
             const allDetailsResponse = await fetch(`${API_URL}?${allDetailsParams.toString()}`);
             if (!allDetailsResponse.ok) throw new Error(`HTTP error! status: ${allDetailsResponse.status} for all details data`);
             const allDetailsResult = await allDetailsResponse.json();
             const allDailyDetails = allDetailsResult.status === 'success' ? allDetailsResult.data : [];

             const wb = XLSX.utils.book_new();
             const ws = XLSX.utils.aoa_to_sheet([]);

             let rowIndex = 0;
             let globalItemNo = 1;
             let grandTotalEkorPack = 0;
             let grandTotalQuantityKg = 0;
             const mainHeaders = ["NO.", "ITEM", "TANGGAL", "EKOR/PACK", "BERAT (KG)", "KETERANGAN"];
             const numCols = mainHeaders.length;

             // --- Report Title ---
             const mainTitle = "LAPORAN ADJUSTMENT BARANG LENGKAP";
             XLSX.utils.sheet_add_aoa(ws, [
                 [mainTitle]
             ], {
                 origin: `A${rowIndex + 1}`
             });
             ws['!merges'] = [{
                 s: {
                     r: rowIndex,
                     c: 0
                 },
                 e: {
                     r: rowIndex,
                     c: numCols - 1
                 }
             }];
             setCellStyle(ws, {
                 s: {
                     r: rowIndex,
                     c: 0
                 },
                 e: {
                     r: rowIndex,
                     c: numCols - 1
                 }
             }, {
                 font_size: 18,
                 bold: true,
                 alignment: {
                     horizontal: "center",
                     vertical: "center"
                 },
                 fill: "FF4F81BD",
                 font_color: "FFFFFFFF",
                 border: {
                     top: {
                         style: "medium",
                         color: {
                             rgb: "FF000000"
                         }
                     },
                     bottom: {
                         style: "medium",
                         color: {
                             rgb: "FF000000"
                         }
                     },
                     left: {
                         style: "medium",
                         color: {
                             rgb: "FF000000"
                         }
                     },
                     right: {
                         style: "medium",
                         color: {
                             rgb: "FF000000"
                         }
                     }
                 }
             });
             rowIndex += 2;

             // --- Date Range ---
             const dateRange = `PERIODE: ${startDate} S/D ${endDate}`;
             XLSX.utils.sheet_add_aoa(ws, [
                 [dateRange]
             ], {
                 origin: `A${rowIndex + 1}`
             });
             ws['!merges'].push({
                 s: {
                     r: rowIndex,
                     c: 0
                 },
                 e: {
                     r: rowIndex,
                     c: numCols - 1
                 }
             });
             setCellStyle(ws, {
                 s: {
                     r: rowIndex,
                     c: 0
                 },
                 e: {
                     r: rowIndex,
                     c: numCols - 1
                 }
             }, {
                 font_size: 12,
                 bold: true,
                 italic: true,
                 alignment: {
                     horizontal: "center"
                 },
                 border: {
                     bottom: {
                         style: "thin",
                         color: {
                             rgb: "FF000000"
                         }
                     }
                 }
             });
             rowIndex += 3;

             // --- Single Main Table Header ---
             XLSX.utils.sheet_add_aoa(ws, [mainHeaders], {
                 origin: `A${rowIndex + 1}`
             });
             const mainHeaderRange = {
                 s: {
                     r: rowIndex,
                     c: 0
                 },
                 e: {
                     r: rowIndex,
                     c: mainHeaders.length - 1
                 }
             };
             setCellStyle(ws, mainHeaderRange, {
                 bold: true,
                 alignment: {
                     horizontal: "center",
                     vertical: "center"
                 },
                 fill: "FFDCE6F1", // Light blue background
                 font_color: "FF000000",
                 border: {
                     top: {
                         style: "thin",
                         color: {
                             rgb: "FF000000"
                         }
                     },
                     bottom: {
                         style: "thin",
                         color: {
                             rgb: "FF000000"
                         }
                     },
                     left: {
                         style: "thin",
                         color: {
                             rgb: "FF000000"
                         }
                     },
                     right: {
                         style: "thin",
                         color: {
                             rgb: "FF000000"
                         }
                     }
                 }
             });
             rowIndex++;
             // Array to collect all rows for calculating column widths later
             const allExportedDataRows = [mainHeaders];

             // Loop through each summarized item to create a sub-table for its details
             for (const summaryItem of summaryData) {
                 const itemDailyDetails = allDailyDetails.filter(
                     detail => detail.summary_storage === summaryItem.storage && detail.summary_item === summaryItem.item
                 );

                 if (itemDailyDetails.length === 0) {
                     continue;
                 }

                 let isFirstRowForThisItem = true;
                 let detailEkorPackTotal = 0;
                 let detailQuantityKgTotal = 0;

                 // Add the daily details
                 itemDailyDetails.forEach(dailyDetail => {
                     const ekorPack = parseInt(dailyDetail.daily_ekor_pack) || 0;
                     const quantityKg = parseFloat(dailyDetail.daily_quantity_kg) || 0;
                     detailEkorPackTotal += ekorPack;
                     detailQuantityKgTotal += quantityKg;

                     const detailRow = [
                         isFirstRowForThisItem ? globalItemNo : '',
                         dailyDetail.item,
                         dailyDetail.date,
                         ekorPack,
                         quantityKg,
                         dailyDetail.keterangan || ''
                     ];
                     XLSX.utils.sheet_add_aoa(ws, [detailRow], {
                         origin: `A${rowIndex + 1}`
                     });
                     const currentDetailDataRange = {
                         s: {
                             r: rowIndex,
                             c: 0
                         },
                         e: {
                             r: rowIndex,
                             c: mainHeaders.length - 1
                         }
                     };
                     setCellStyle(ws, currentDetailDataRange, {
                         border: {
                             top: {
                                 style: "thin",
                                 color: {
                                     rgb: "FF000000"
                                 }
                             },
                             bottom: {
                                 style: "thin",
                                 color: {
                                     rgb: "FF000000"
                                 }
                             },
                             left: {
                                 style: "thin",
                                 color: {
                                     rgb: "FF000000"
                                 }
                             },
                             right: {
                                 style: "thin",
                                 color: {
                                     rgb: "FF000000"
                                 }
                             }
                         },
                         alignment: {
                             vertical: "top"
                         }
                     });
                     // Center the number only in the first cell of the block
                     if (isFirstRowForThisItem) {
                         setCellStyle(ws, {
                             s: {
                                 r: rowIndex,
                                 c: 0
                             },
                             e: {
                                 r: rowIndex,
                                 c: 0
                             }
                         }, {
                             alignment: {
                                 horizontal: "center"
                             }
                         });
                         isFirstRowForThisItem = false;
                     }
                     allExportedDataRows.push(detailRow);
                     rowIndex++;
                 });

                 // Add subtotal for details with merged cells
                 const detailSubtotalRow = ['', "TOTAL", "", detailEkorPackTotal, detailQuantityKgTotal, ""];
                 XLSX.utils.sheet_add_aoa(ws, [detailSubtotalRow], {
                     origin: `A${rowIndex + 1}`
                 });
                 ws['!merges'].push({
                     s: {
                         r: rowIndex,
                         c: 1
                     },
                     e: {
                         r: rowIndex,
                         c: 2
                     }
                 });
                 setCellStyle(ws, {
                     s: {
                         r: rowIndex,
                         c: 0
                     },
                     e: {
                         r: rowIndex,
                         c: mainHeaders.length - 1
                     }
                 }, {
                     bold: true,
                     fill: "FFC6E0B4", // Light green background
                     border: {
                         top: {
                             style: "medium",
                             color: {
                                 rgb: "FF000000"
                             }
                         },
                         bottom: {
                             style: "double",
                             color: {
                                 rgb: "FF000000"
                             }
                         },
                         left: {
                             style: "thin",
                             color: {
                                 rgb: "FF000000"
                             }
                         },
                         right: {
                             style: "thin",
                             color: {
                                 rgb: "FF000000"
                             }
                         }
                     },
                     alignment: {
                         horizontal: "right"
                     } // Right-align all cells in this row
                 });
                 allExportedDataRows.push(detailSubtotalRow);
                 rowIndex += 2; // Extra space after each item's table

                 // Add item's totals to the grand totals
                 grandTotalEkorPack += detailEkorPackTotal;
                 grandTotalQuantityKg += detailQuantityKgTotal;

                 // Increment the global item number for the next item
                 globalItemNo++;
             }

             // Add grand totals row with merged cells
             const grandTotalRow = ['', "GRAND TOTAL", "", grandTotalEkorPack, grandTotalQuantityKg, ""];
             XLSX.utils.sheet_add_aoa(ws, [grandTotalRow], {
                 origin: `A${rowIndex + 1}`
             });
             ws['!merges'].push({
                 s: {
                     r: rowIndex,
                     c: 1
                 },
                 e: {
                     r: rowIndex,
                     c: 2
                 }
             });
             setCellStyle(ws, {
                 s: {
                     r: rowIndex,
                     c: 0
                 },
                 e: {
                     r: rowIndex,
                     c: numCols - 1
                 }
             }, {
                 bold: true,
                 fill: "FFC6E0B4",
                 border: {
                     top: {
                         style: "medium",
                         color: {
                             rgb: "FF000000"
                         }
                     },
                     bottom: {
                         style: "double",
                         color: {
                             rgb: "FF000000"
                         }
                     },
                     left: {
                         style: "thin",
                         color: {
                             rgb: "FF000000"
                         }
                     },
                     right: {
                         style: "thin",
                         color: {
                             rgb: "FF000000"
                         }
                     }
                 },
                 alignment: {
                     horizontal: "right"
                 }
             });
             allExportedDataRows.push(grandTotalRow);

             // Calculate and set column widths
             ws['!cols'] = calculateColumnWidths(allExportedDataRows, mainHeaders, true);

             XLSX.utils.book_append_sheet(wb, ws, "Laporan Lengkap Adjustment");
             XLSX.writeFile(wb, "Laporan_Adjustment_Lengkap.xlsx");

         } catch (error) {
             alert('Fetch error during all details export: ' + error.message);
             console.error('Fetch error during all details export:', error);
         } finally {
             loadingIndicator.classList.add('hidden');
             noDataMessage.style.display = originalNoDataDisplay;
         }
     }


     exportSummaryBtn.addEventListener('click', async (e) => {
         e.preventDefault();
         await exportSummarizedDataToXLSX();
         exportDropdownMenu.classList.add('hidden');
     });

     exportAllDetailsBtn.addEventListener('click', async (e) => {
         e.preventDefault();
         await exportAllDataWithDailyDetailsToXLSX();
         exportDropdownMenu.classList.add('hidden');
     });
     // --- End Export Functions ---

     // Initial data load
     document.addEventListener('DOMContentLoaded', () => {
         populateStorageFilter();
         fetchData();
     });
 </script>