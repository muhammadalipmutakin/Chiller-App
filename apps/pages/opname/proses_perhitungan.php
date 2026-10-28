<style>
    [x-cloak] {
        display: none !important;
    }
</style>


<div class="flex-1 min-w-0 transition-all duration-300" x-data="stockOpname" x-cloak>
    <h1 class="text-sm sm:text-lg font-bold mb-4 text-gray-800">Stock Opname Calculation</h1>

    <div x-show="showInitialFilter" class="bg-white p-3 rounded-lg shadow-md mb-4">
        <div class="flex items-center space-x-3">
            <div class="w-full sm:w-1/3">
                <label class="block text-xs font-medium text-gray-700 mb-1">Opname Date</label>
                <input type="date" x-model="opnameDate" class="block w-full text-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-1.5">
            </div>
            <div class="w-full sm:w-1/3 flex items-end">
                <button @click="loadData" class="bg-blue-600 text-white text-xs px-3 py-1.5 rounded-md hover:bg-blue-700">
                    Load Data
                </button>
            </div>
        </div>
    </div>

    <div x-show="showSecondaryFilter" class="bg-white p-3 rounded-lg shadow-md mb-4">
        <div class="flex items-center space-x-3">
            <div class="w-full sm:w-1/3">
                <label class="block text-xs font-medium text-gray-700 mb-1">Storage Location</label>
                <select x-model="selectedStorage" @change="filterByStorage" class="block w-full text-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-1.5">
                    <option value="">All Chiller Locations</option>
                    <template x-for="storage in storageLocations" :key="storage">
                        <option x-text="storage" :value="storage"></option>
                    </template>
                </select>
            </div>
        </div>
    </div>

    <div x-show="showInitialTables" class="mb-4">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white p-3 rounded-lg shadow-md">
                <h2 class="text-sm font-semibold mb-3 text-gray-700">Stock Awal</h2>
                <div class="overflow-x-auto border rounded-md border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Storage</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ekor/Pack</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Berat (kg)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-xs">
                            <template x-for="item in filteredStockAwal" :key="item.id">
                                <tr>
                                    <td class="px-2 py-2 whitespace-nowrap" x-text="item.item"></td>
                                    <td class="px-2 py-2 whitespace-nowrap" x-text="item.storage"></td>
                                    <td class="px-2 py-2 whitespace-nowrap" x-text="item.ekor_pack"></td>
                                    <td class="px-2 py-2 whitespace-nowrap" x-text="item.quantity_kg"></td>
                                </tr>
                            </template>
                            <template x-if="filteredStockAwal.length === 0">
                                <tr>
                                    <td colspan="4" class="px-2 py-2 text-center text-gray-500">No data available</td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white p-3 rounded-lg shadow-md">
                <h2 class="text-sm font-semibold mb-3 text-gray-700">Stock Hitung</h2>
                <div class="overflow-x-auto border rounded-md border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Storage</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ekor/Pack</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Berat (kg)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-xs">
                            <template x-for="item in filteredStockHitung" :key="item.id">
                                <tr>
                                    <td class="px-2 py-2 whitespace-nowrap" x-text="item.item"></td>
                                    <td class="px-2 py-2 whitespace-nowrap" x-text="item.storage"></td>
                                    <td class="px-2 py-2 whitespace-nowrap" x-text="item.ekor_pack"></td>
                                    <td class="px-2 py-2 whitespace-nowrap" x-text="item.quantity_kg"></td>
                                </tr>
                            </template>
                            <template x-if="filteredStockHitung.length === 0">
                                <tr>
                                    <td colspan="4" class="px-2 py-2 text-center text-gray-500">No data available</td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-4 flex justify-center">
            <button
                @click="processCalculation"
                x-text="processButtonText"
                :class="{'bg-blue-600 hover:bg-blue-700': !showResultTable, 'bg-green-600 hover:bg-green-700': showResultTable}"
                class="text-white text-xs px-4 py-2 rounded-md transition-colors"></button>
        </div>
    </div>

    <div x-show="showResultTable" class="bg-white p-3 rounded-lg shadow-md">
        <h2 class="text-sm font-semibold mb-3 text-gray-700">Hasil Perhitungan Stock Opname</h2>
        <div class="overflow-x-auto border rounded-md border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Storage</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ekor/Pack</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Berat (kg)</th>
                        <template x-if="showDifferenceColumns">
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selisih Ekor/Pack</th>
                        </template>
                        <template x-if="showDifferenceColumns">
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selisih Berat (kg)</th>
                        </template>
                        <template x-if="showDifferenceColumns">
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                        </template>
                        <template x-if="showDifferenceColumns">
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </template>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 text-xs">
                    <template x-for="(item, index) in filteredHasilPerhitungan" :key="item.id">
                        <tr>
                            <td class="px-2 py-2 whitespace-nowrap" x-text="item.item"></td>
                            <td class="px-2 py-2 whitespace-nowrap" x-text="item.storage"></td>
                            <td class="px-2 py-2 whitespace-nowrap" x-text="item.ekor_pack"></td>
                            <td class="px-2 py-2 whitespace-nowrap" x-text="item.quantity_kg"></td>
                            <template x-if="showDifferenceColumns">
                                <td class="px-2 py-2 whitespace-nowrap"
                                    :class="{'text-red-500': item.selisih_ekor_pack < 0, 'text-green-500': item.selisih_ekor_pack > 0}">
                                    <span x-text="item.selisih_ekor_pack > 0 ? '+' + item.selisih_ekor_pack : item.selisih_ekor_pack"></span>
                                </td>
                            </template>
                            <template x-if="showDifferenceColumns">
                                <td class="px-2 py-2 whitespace-nowrap"
                                    :class="{'text-red-500': item.selisih_quantity_kg < 0, 'text-green-500': item.selisih_quantity_kg > 0}">
                                    <span x-text="item.selisih_quantity_kg > 0 ? '+' + item.selisih_quantity_kg.toFixed(2) : item.selisih_quantity_kg.toFixed(2)"></span>
                                </td>
                            </template>
                            <template x-if="showDifferenceColumns">
                                <td class="px-2 py-2 whitespace-nowrap">
                                    <input
                                        x-model="item.keterangan"
                                        :disabled="!item.editing"
                                        class="border rounded px-1 py-0.5 w-full text-xs"
                                        @keydown.enter="saveRow(index)">
                                </td>
                            </template>
                            <template x-if="showDifferenceColumns">
                                <td class="px-2 py-2 whitespace-nowrap">
                                    <template x-if="!item.editing">
                                        <button @click="editRow(index)" class="text-blue-600 hover:text-blue-800 mr-1 text-xs">Edit</button>
                                    </template>
                                    <template x-if="item.editing">
                                        <button @click="saveRow(index)" class="text-green-600 hover:text-green-800 mr-1 text-xs">Save</button>
                                    </template>
                                </td>
                            </template>
                        </tr>
                    </template>
                    <template x-if="filteredHasilPerhitungan.length === 0">
                        <tr>
                            <td :colspan="showDifferenceColumns ? 8 : 4" class="px-2 py-2 text-center text-gray-500">No data available</td>
                        </tr>
                    </template>
                </tbody>
                <tfoot x-show="showDifferenceColumns && filteredHasilPerhitungan.length > 0" class="bg-gray-50">
                    <tr>
                        <td colspan="3" class="px-2 py-2 text-right font-medium text-xs">Total Selisih:</td>
                        <td class="px-2 py-2 whitespace-nowrap"></td>
                        <td class="px-2 py-2 whitespace-nowrap font-medium text-xs"
                            :class="{'text-red-500': totalSelisihEkor < 0, 'text-green-500': totalSelisihEkor > 0}">
                            <span x-text="totalSelisihEkor > 0 ? '+' + totalSelisihEkor : totalSelisihEkor"></span>
                        </td>
                        <td class="px-2 py-2 whitespace-nowrap font-medium text-xs"
                            :class="{'text-red-500': totalSelisihBerat < 0, 'text-green-500': totalSelisihBerat > 0}">
                            <span x-text="totalSelisihBerat > 0 ? '+' + totalSelisihBerat.toFixed(2) : totalSelisihBerat.toFixed(2)"></span>
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div x-show="showResultTable && !showDifferenceColumns" class="mt-4 flex justify-center">
            <button
                @click="checkDifference"
                class="bg-purple-600 text-white text-xs px-4 py-2 rounded-md hover:bg-purple-700 transition-colors">
                Check Data Selisih
            </button>
        </div>

        <div x-show="showDifferenceColumns" class="mt-4 flex justify-end">
            <button
                @click="saveAdjustment"
                class="bg-indigo-600 text-white text-xs px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">
                Save Adjustment
            </button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('stockOpname', () => ({
            selectedStorage: '',
            opnameDate: new Date().toISOString().split('T')[0],
            storageLocations: [],
            stockAwal: [],
            stockHitung: [],
            hasilPerhitungan: [],
            showInitialFilter: true,
            showSecondaryFilter: false,
            showInitialTables: false,
            showResultTable: false,
            showDifferenceColumns: false,
            processButtonText: 'Proses Data Perhitungan',

            init() {
                this.loadStorageLocations();
            },

            get filteredStockAwal() {
                if (!this.selectedStorage) return this.stockAwal;
                return this.stockAwal.filter(item => item.storage === this.selectedStorage);
            },

            get filteredStockHitung() {
                if (!this.selectedStorage) return this.stockHitung;
                return this.stockHitung.filter(item => item.storage === this.selectedStorage);
            },

            get filteredHasilPerhitungan() {
                if (!this.selectedStorage) return this.hasilPerhitungan;
                return this.hasilPerhitungan.filter(item => item.storage === this.selectedStorage);
            },

            async loadStorageLocations() {
                try {
                    const response = await fetch('./controller/opname/proses.php?action=get_storage_locations');
                    const data = await response.json();
                    this.storageLocations = data.locations;
                } catch (error) {
                    console.error('Error loading storage locations:', error);
                }
            },

            async loadData() {
                if (!this.opnameDate) {
                    Swal.fire('Error', 'Please select an opname date', 'error');
                    return;
                }

                try {
                    const params = new URLSearchParams({
                        action: 'get_initial_data',
                        opname_date: this.opnameDate
                    });

                    const response = await fetch(`./controller/opname/proses.php?${params}`);
                    const data = await response.json();

                    if (data.success) {
                        this.stockAwal = data.stock_awal;
                        this.stockHitung = data.stock_hitung;
                        this.showInitialFilter = false;
                        this.showSecondaryFilter = true;
                        this.showInitialTables = true;
                        this.showResultTable = false;
                        this.showDifferenceColumns = false;
                        this.processButtonText = 'Proses Data Perhitungan';
                    } else {
                        Swal.fire('Error', data.message || 'Failed to load data', 'error');
                    }
                } catch (error) {
                    console.error('Error loading data:', error);
                    Swal.fire('Error', 'Failed to load data', 'error');
                }
            },

            filterByStorage() {
                // Just triggers the computed properties to update
            },

            async processCalculation() {
                if (this.showResultTable) {
                    this.checkDifference();
                    return;
                }

                const result = await Swal.fire({
                    title: 'Konfirmasi Proses Perhitungan',
                    text: 'Perhitungan akan dilakukan untuk semua storage. Setelah diproses, data tidak dapat dibatalkan.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Proses Sekarang',
                    cancelButtonText: 'Batal'
                });

                if (result.isConfirmed) {
                    try {
                        const params = new URLSearchParams({
                            action: 'process_calculation',
                            opname_date: this.opnameDate
                        });

                        const response = await fetch(`./controller/opname/proses.php?${params}`, {
                            method: 'POST'
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.hasilPerhitungan = data.result.map(item => ({
                                ...item,
                                editing: false
                            }));
                            this.showInitialTables = false;
                            this.showResultTable = true;
                            this.processButtonText = 'Check Data Selisih';
                            Swal.fire('Success', 'Perhitungan berhasil diproses', 'success');
                        } else {
                            Swal.fire('Error', data.message || 'Failed to process calculation', 'error');
                        }
                    } catch (error) {
                        console.error('Error processing calculation:', error);
                        Swal.fire('Error', 'Failed to process calculation', 'error');
                    }
                }
            },

            checkDifference() {
                this.showDifferenceColumns = true;
                this.processButtonText = 'Proses Data Perhitungan';
            },

            editRow(index) {
                this.hasilPerhitungan[index].editing = true;
            },

            async saveRow(index) {
                try {
                    const response = await fetch('./controller/opname/proses.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'update_keterangan',
                            id: this.hasilPerhitungan[index].id,
                            keterangan: this.hasilPerhitungan[index].keterangan
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.hasilPerhitungan[index].editing = false;
                    } else {
                        Swal.fire('Error', data.message || 'Failed to update keterangan', 'error');
                    }
                } catch (error) {
                    console.error('Error updating keterangan:', error);
                    Swal.fire('Error', 'Failed to update keterangan', 'error');
                }
            },

            async saveAdjustment() {
                const result = await Swal.fire({
                    title: 'Konfirmasi Adjustment',
                    text: 'Apakah Anda yakin ingin menyimpan adjustment?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Simpan',
                    cancelButtonText: 'Batal'
                });

                if (result.isConfirmed) {
                    try {
                        const itemsToSave = this.selectedStorage ?
                            this.hasilPerhitungan.filter(item => item.storage === this.selectedStorage) :
                            this.hasilPerhitungan;

                        const response = await fetch('./controller/opname/proses.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'save_adjustment',
                                items: itemsToSave,
                                opname_date: this.opnameDate,
                                storage: this.selectedStorage
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            Swal.fire('Success', 'Adjustment berhasil disimpan', 'success');
                        } else {
                            Swal.fire('Error', data.message || 'Failed to save adjustment', 'error');
                        }
                    } catch (error) {
                        console.error('Error saving adjustment:', error);
                        Swal.fire('Error', 'Failed to save adjustment', 'error');
                    }
                }
            },

            get totalSelisihEkor() {
                const items = this.selectedStorage ?
                    this.hasilPerhitungan.filter(item => item.storage === this.selectedStorage) :
                    this.hasilPerhitungan;

                return items.reduce((sum, item) => sum + (item.selisih_ekor_pack || 0), 0);
            },

            get totalSelisihBerat() {
                const items = this.selectedStorage ?
                    this.hasilPerhitungan.filter(item => item.storage === this.selectedStorage) :
                    this.hasilPerhitungan;

                return items.reduce((sum, item) => sum + (item.selisih_quantity_kg || 0), 0);
            }
        }));
    });
</script>