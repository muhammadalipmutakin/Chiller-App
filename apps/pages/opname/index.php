<div class="flex-1 min-w-0 transition-all duration-300" x-data="stockOpnameApp()">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-sm sm:text-lg font-bold text-gray-800">Stock Opname</h1>
        <button x-show="hasExistingOpname" @click="viewExistingOpname()" class="bg-purple-600 hover:bg-purple-700 text-white text-xs px-3 py-1.5 rounded-md flex items-center">
            <i class="fas fa-calculator mr-1"></i> Hitung Stock
        </button>
    </div>

    <div class="bg-white p-3 rounded-lg shadow-md mb-4">
        <h2 class="text-sm font-semibold mb-3 text-gray-700">Filter Data</h2>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-3">
            <div class="filter-item">
                <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Awal</label>
                <input type="date" x-model="filter.startDate" class="w-full p-1.5 text-xs border border-gray-300 rounded-md">
            </div>

            <div class="filter-item">
                <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Akhir</label>
                <input type="date" x-model="filter.endDate" class="w-full p-1.5 text-xs border border-gray-300 rounded-md">
            </div>

            <div class="filter-item">
                <label class="block text-xs font-medium text-gray-700 mb-1">Storage</label>
                <select id="storage-select" multiple="multiple" class="w-full p-1.5 text-xs border border-gray-300 rounded-md"></select>
            </div>

            <div class="filter-item">
                <label class="block text-xs font-medium text-gray-700 mb-1">Item</label>
                <select id="item-select" multiple="multiple" class="w-full p-1.5 text-xs border border-gray-300 rounded-md"></select>
            </div>

            <div class="filter-item">
                <label class="block text-xs font-medium text-gray-700 mb-1">Subitem</label>
                <select id="subitem-select" multiple="multiple" class="w-full p-1.5 text-xs border border-gray-300 rounded-md"></select>
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <button @click="checkStock()" class="bg-blue-500 hover:bg-blue-600 text-white text-xs px-3 py-1.5 rounded-md flex items-center">
                <i class="fas fa-search mr-1"></i> Cek Stock
            </button>
        </div>
    </div>

    <div class="bg-white p-3 rounded-lg shadow-md mb-4" x-show="stockData.length > 0">
        <div class="flex justify-between items-center mb-3">
            <h2 class="text-sm font-semibold text-gray-700">Hasil Stock</h2>
            <div class="flex gap-2">
                <button @click="cancelOpname()" class="bg-gray-500 hover:bg-gray-600 text-white text-xs px-3 py-1.5 rounded-md flex items-center">
                    <i class="fas fa-times mr-1"></i> Cancel
                </button>
                <button @click="createOpname()" class="bg-green-500 hover:bg-green-600 text-white text-xs px-3 py-1.5 rounded-md flex items-center">
                    <i class="fas fa-save mr-1"></i> Buat Stock Opname
                </button>
            </div>
        </div>

        <div class="overflow-x-auto rounded-md border border-gray-200">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200">Item</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200">Subitem</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200">Storage</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200">Stock (Ekor)</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200">Stock (Kg)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <template x-for="(item, index) in stockData" :key="index">
                        <tr>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-500" x-text="item.item"></td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-500" x-text="item.subitem || '-'"></td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-500" x-text="item.storage"></td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-500" x-text="item.ekor_pack"></td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-500" x-text="item.quantity_kg"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function stockOpnameApp() {
        return {
            filter: {
                startDate: new Date().toISOString().split('T')[0],
                endDate: new Date().toISOString().split('T')[0],
                storage: [], // Changed to array for multiple selection
                item: [], // Changed to array for multiple selection
                subitem: [] // Changed to array for multiple selection
            },
            storageList: [],
            itemList: [],
            subitemList: [],
            stockData: [],
            loading: false,
            hasExistingOpname: false, // New property to control button visibility

            init() {
                const today = new Date();
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                this.filter.startDate = firstDay.toISOString().split('T')[0];

                // Defer Select2 initialization until after Alpine.js has rendered
                // Use $nextTick to ensure DOM is ready for Select2
                this.$nextTick(() => {
                    $('#storage-select').select2({
                        placeholder: 'Semua Storage',
                        allowClear: true
                    });
                    $('#item-select').select2({
                        placeholder: 'Semua Item',
                        allowClear: true
                    });
                    $('#subitem-select').select2({
                        placeholder: 'Semua Subitem',
                        allowClear: true
                    });

                    // Listen for Select2 changes and update Alpine.js model
                    $('#storage-select').on('change', (e) => {
                        this.filter.storage = $(e.target).val();
                    });
                    $('#item-select').on('change', (e) => {
                        this.filter.item = $(e.target).val();
                    });
                    $('#subitem-select').on('change', (e) => {
                        this.filter.subitem = $(e.target).val();
                    });

                    this.fetchDropdownData();
                    this.checkInitialExistingOpname(); // Check for existing opname on init
                });
            },

            async fetchDropdownData() {
                try {
                    const response = await fetch('./controller/opname/index.php?action=get_dropdown_data');
                    const result = await response.json();

                    if (result.status === 'success') {
                        this.storageList = result.data.storages || [];
                        this.itemList = result.data.items || [];
                        this.subitemList = result.data.subitems || [];

                        // Populate Select2 dropdowns
                        this.populateSelect2('#storage-select', this.storageList, this.filter.storage);
                        this.populateSelect2('#item-select', this.itemList, this.filter.item);
                        this.populateSelect2('#subitem-select', this.subitemList, this.filter.subitem);
                    }
                } catch (error) {
                    console.error('Error fetching dropdown data:', error);
                }
            },

            // Helper function to populate Select2 dropdowns
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

            async checkInitialExistingOpname() {
                try {
                    const response = await fetch('./controller/opname/index.php?action=check_existing_opname_any');
                    const result = await response.json();
                    if (result.status === 'success' && result.data.exists) {
                        this.hasExistingOpname = true;
                    } else {
                        this.hasExistingOpname = false;
                    }
                } catch (error) {
                    console.error('Error checking initial existing opname:', error);
                    this.hasExistingOpname = false; // Assume no existing opname on error
                }
            },

            async checkStock() {
                // Date Validation
                if (this.filter.startDate > this.filter.endDate) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Input Tanggal Tidak Valid',
                        text: 'Tanggal akhir tidak boleh lebih awal dari tanggal awal.'
                    });
                    return; // Stop execution
                }

                this.loading = true;
                this.stockData = []; // Clear previous results

                try {
                    const params = new URLSearchParams();
                    params.append('start_date', this.filter.startDate);
                    params.append('end_date', this.filter.endDate);

                    // Append arrays for multiple selections
                    this.filter.storage.forEach(val => params.append('storage[]', val));
                    this.filter.item.forEach(val => params.append('item[]', val));
                    this.filter.subitem.forEach(val => params.append('subitem[]', val));

                    const response = await fetch(`./controller/opname/index.php?action=check_stock&${params.toString()}`);
                    const result = await response.json();

                    if (result.status === 'success') {
                        this.stockData = result.data || [];
                        if (this.stockData.length === 0) {
                            Swal.fire({
                                icon: 'info',
                                title: 'Tidak Ada Data',
                                text: 'Tidak ada data stok yang ditemukan untuk filter yang dipilih.'
                            });
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: result.message || 'Gagal memeriksa stock'
                        });
                    }
                } catch (error) {
                    console.error('Error checking stock:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Terjadi kesalahan saat memeriksa stock'
                    });
                } finally {
                    this.loading = false;
                }
            },

            async createOpname() {
                if (this.stockData.length === 0) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Tidak Ada Data',
                        text: 'Tidak ada data stock untuk dibuat opname. Silakan cek stock terlebih dahulu.'
                    });
                    return;
                }

                try {
                    const params = new URLSearchParams();
                    params.append('start_date', this.filter.startDate);
                    params.append('end_date', this.filter.endDate);

                    const checkResponse = await fetch(`./controller/opname/index.php?action=check_existing_opname&${params.toString()}`);
                    const checkResult = await checkResponse.json();

                    if (checkResult.status === 'success' && checkResult.data.exists) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Data Opname Sudah Ada',
                            text: `Data stock opname untuk periode ${this.filter.startDate} hingga ${this.filter.endDate} sudah ada. Hubungi administrator jika ingin membatalkan data sebelumnya.`,
                            confirmButtonText: 'OK'
                        });
                        return;
                    }
                } catch (error) {
                    console.error('Error checking existing opname:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Gagal memeriksa data opname yang sudah ada'
                    });
                    return;
                }

                const result = await Swal.fire({
                    icon: 'warning',
                    title: 'Konfirmasi',
                    text: 'Pastikan data sudah benar. Setelah dibuat, stock opname tidak bisa dicancel kecuali oleh administrator.',
                    showCancelButton: true,
                    confirmButtonText: 'Buat Stock Opname',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                });

                if (result.isConfirmed) {
                    try {
                        const response = await fetch('./controller/opname/index.php?action=create_opname', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                stock_data: this.stockData,
                                filter: this.filter
                            })
                        });

                        const result = await response.json();

                        if (result.status === 'success') {
                            await Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: 'Stock opname berhasil dibuat',
                                confirmButtonText: 'OK'
                            });
                            window.location.href = '?page=opname/hitung';
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: result.message || 'Gagal membuat stock opname'
                            });
                        }
                    } catch (error) {
                        console.error('Error creating opname:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Terjadi kesalahan saat membuat stock opname'
                        });
                    }
                }
            },

            cancelOpname() {
                Swal.fire({
                    icon: 'info',
                    title: 'Dibatalkan',
                    text: 'Proses stock opname dibatalkan',
                    confirmButtonText: 'OK'
                });
                this.stockData = [];
            },

            viewExistingOpname() {
                window.location.href = '?page=opname/hitung'; // Redirect to the page that displays existing stock opname
            }
        }
    }
</script>