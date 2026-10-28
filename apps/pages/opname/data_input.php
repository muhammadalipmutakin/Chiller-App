<style>
    /* Custom styles for Select2 to better integrate with Tailwind forms */
    .select2-container .select2-selection--multiple {
        border: 1px solid #d1d5db;
        /* A soft gray for borders */
        border-radius: 0.5rem;
        min-height: 44px;
        padding: 0.5rem 0.75rem;
        display: flex;
        align-items: center;
        font-size: 0.95rem;
        transition: all 0.2s ease-in-out;
    }

    .select2-container--default .select2-selection--multiple:focus-within {
        border-color: #60a5fa;
        /* Blue-400 on focus */
        box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.2);
        /* Blue-400 focus ring */
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #e0f2fe;
        /* Light blue-100 */
        border: 1px solid #93c5fd;
        /* Blue-300 border */
        border-radius: 0.375rem;
        padding: 0.25rem 0.75rem;
        margin-right: 0.5rem;
        margin-top: 0.2rem;
        font-size: 0.875rem;
        color: #1e40af;
        /* Blue-800 text */
        line-height: 1.25rem;
        display: flex;
        align-items: center;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #1e40af;
        /* Blue-800 */
        margin-right: 0.35rem;
        font-size: 1rem;
        transition: color 0.2s ease-in-out;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: #0c4a6e;
        /* Cyan-900 */
    }

    .select2-container--default .select2-selection--multiple .select2-selection__rendered {
        padding-right: 2.5rem;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__clear {
        margin-right: 0.5rem;
        font-size: 1.5rem;
        line-height: 1;
        color: #6b7280;
        /* Gray-500 */
    }

    .select2-dropdown {
        border: 1px solid #e5e7eb;
        /* Gray-200 */
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        overflow: hidden;
    }

    .select2-search input {
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem !important;
        padding: 0.6rem !important;
        margin: 0.5rem !important;
        width: calc(100% - 1rem) !important;
        box-sizing: border-box !important;
    }

    .select2-results__option {
        padding: 0.75rem 1rem;
        font-size: 0.95rem;
    }

    .select2-results__option--highlighted {
        background-color: #e0f2fe !important;
        /* Blue-100 */
        color: #1e40af !important;
        /* Blue-800 */
    }

    .select2-results__option[aria-selected=true] {
        background-color: #f9fafb !important;
        /* Gray-50 */
        color: #4b5563 !important;
        /* Gray-700 */
    }

    /* Custom height for table containers */
    .table-scroll-container {
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #e5e7eb;
        /* Gray-200 */
        border-radius: 0.5rem;
        box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.03);
        /* Subtle inner shadow */
        background-color: #ffffff;
    }

    .table-scroll-container table {
        width: 100%;
        border-collapse: collapse;
    }

    .table-scroll-container thead.sticky th {
        position: sticky;
        top: 0;
        background-color: #f9fafb;
        /* Gray-50 for sticky header */
        z-index: 10;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.04);
        /* Lighter shadow for sticky */
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .container {
            padding: 1rem;
        }

        .table-scroll-container {
            max-height: 300px;
        }

        h1 {
            font-size: 1.75rem;
        }

        h2 {
            font-size: 1.25rem;
        }
    }
</style>


<main class="flex-1 min-w-0 transition-all duration-300" x-data="dataInputApp()">
    <a href="?page=opname/hitung" class="inline-flex items-center px-3 py-1.5 text-xs bg-gray-200 text-gray-700 rounded-md shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-75 transition duration-300 mb-4">
        <i class="fas fa-chevron-left w-3 h-3 mr-1"></i>
        Kembali
    </a>

    <h1 class="text-sm sm:text-lg font-bold text-gray-800 mb-4">
        Stock Opname Insight
    </h1>

    <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md mb-4 border border-blue-200">
        <h2 class="text-sm font-bold mb-3 text-gray-800 flex items-center">
            Filter Data
        </h2>
        <div class="grid grid-cols-1 gap-3">
            <div>
                <label for="filter-storage" class="block text-xs font-medium text-gray-700 mb-1">Pilih Storage</label>
                <select id="filter-storage" multiple="multiple" class="w-full text-xs"></select>
            </div>
        </div>
        <div x-show="loading" class="mt-2 text-center text-xs text-blue-600 font-semibold flex items-center justify-center">
            <i class="fas fa-spinner fa-spin mr-1"></i> Memuat data...
        </div>
    </div>

    <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md mb-4 border border-green-200" x-show="aggregatedData.length > 0">
        <h2 class="text-sm font-bold mb-3 text-gray-800 flex items-center">
            <i class="fas fa-chart-pie mr-1.5 text-indigo-600"></i> Data Akumulasi Stock Opname
        </h2>
        <div class="overflow-x-auto rounded-md border border-gray-200">
            <table class="min-w-full bg-white">
                <thead class="sticky bg-gray-50">
                    <tr>
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">No</th>
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">Item</th>
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">Storage</th>
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">Total Ekor/Pack</th>
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">Total Berat (Kg)</th>
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">Tanggal Opname</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="(item, index) in aggregatedData" :key="index">
                        <tr class="hover:bg-blue-50 transition-colors duration-150">
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-700" x-text="index + 1"></td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-700" x-text="item.item"></td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-700" x-text="item.storage"></td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-700" x-text="item.total_ekor_pack"></td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-700" x-text="item.total_quantity_kg"></td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-700" x-text="item.opname_date"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md border border-purple-200" x-show="detailedData.length > 0">
        <h2 class="text-sm font-bold mb-3 text-gray-800 flex items-center">
            <i class="fas fa-file-invoice mr-1.5 text-orange-500"></i> Data Detail Stock Opname
        </h2>
        <div class="overflow-x-auto rounded-md border border-gray-200">
            <table class="min-w-full bg-white">
                <thead class="sticky bg-gray-50">
                    <tr>
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">No</th>
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">Item</th>
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">Storage</th>
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">Ekor/Pack</th>
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">Berat (Kg)</th>
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">Tanggal Opname</th>
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">User Hitung</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="(item, index) in detailedData" :key="item.id">
                        <tr class="hover:bg-purple-50 transition-colors duration-150">
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-700" x-text="index + 1"></td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-700" x-text="item.item"></td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-700" x-text="item.storage"></td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-700" x-text="item.ekor_pack"></td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-700" x-text="item.quantity_kg"></td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-700" x-text="item.opname_date"></td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-700" x-text="item.user_hitung"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
    function dataInputApp() {
        return {
            filter: {
                storage: []
            },
            storageList: [],
            aggregatedData: [],
            detailedData: [],
            loading: false,

            init() {
                this.$nextTick(() => {
                    // Initialize Select2 for the single storage filter
                    $('#filter-storage').select2({
                        placeholder: 'Pilih Storage',
                        allowClear: true,
                        width: '100%' // Ensure it takes full width
                    });

                    // Listen for Select2 changes and update Alpine.js model, then fetch data
                    $('#filter-storage').on('change', (e) => {
                        this.filter.storage = $(e.target).val();
                        this.fetchData(); // Fetch data immediately on change
                    });

                    this.fetchDropdownData(); // Fetch storage options
                    this.fetchData(); // Fetch initial data for tables
                });
            },

            async fetchDropdownData() {
                try {
                    const response = await fetch('./controller/opname/data_input.php?action=get_dropdown_data');
                    const result = await response.json();

                    if (result.status === 'success') {
                        this.storageList = result.data.storages || [];
                        this.populateSelect2('#filter-storage', this.storageList, this.filter.storage);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: result.message || 'Gagal mengambil data dropdown storage.'
                        });
                    }
                } catch (error) {
                    console.error('Error fetching dropdown data:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Terjadi kesalahan saat mengambil data dropdown storage.'
                    });
                }
            },

            populateSelect2(selector, dataArray, selectedValues) {
                const $select = $(selector);
                $select.empty(); // Clear existing options

                // Add options
                $.each(dataArray, function(index, value) {
                    $select.append(new Option(value, value));
                });

                // Set selected values
                if (selectedValues && selectedValues.length > 0) {
                    $select.val(selectedValues);
                }
                $select.trigger('change'); // Notify Select2 of changes
            },

            async fetchData() {
                this.loading = true;
                this.aggregatedData = [];
                this.detailedData = [];

                try {
                    const params = new URLSearchParams();
                    this.filter.storage.forEach(val => params.append('storage[]', val));

                    // Fetch data for both tables using the same filter
                    const response = await fetch(`./controller/opname/data_input.php?action=get_opname_data_input&${params.toString()}`);
                    const result = await response.json();

                    if (result.status === 'success') {
                        this.aggregatedData = result.data.aggregated || [];
                        this.detailedData = result.data.detailed || [];

                        if (this.aggregatedData.length === 0 && this.detailedData.length === 0) {
                            Swal.fire({
                                icon: 'info',
                                title: 'Tidak Ada Data',
                                text: 'Tidak ada data stock opname yang ditemukan untuk filter yang dipilih.'
                            });
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: result.message || 'Gagal mengambil data stock opname.'
                        });
                    }

                } catch (error) {
                    console.error('Error fetching data:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Terjadi kesalahan saat mengambil data stock opname.'
                    });
                } finally {
                    this.loading = false;
                }
            }
        }
    }
</script>