<style>
    /* Styling Select2 (as per common application needs) - Add this if you use Select2 */
    .select2-container .select2-selection--single {
        height: 42px !important;
        display: flex;
        align-items: center;
        border-radius: 0.375rem;
        border-color: #d1d5db;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 42px !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #4b5563;
    }

    .select2-container--open .select2-dropdown {
        z-index: 9999;
    }

    /* Utilitas CSS untuk deteksi breakpoint */
    .mobile-only-block {
        display: none;
    }

    .desktop-only-block {
        display: block;
    }

    /* Show mobile-only-block on small screens (e.g., up to 639px, default Tailwind 'sm' breakpoint) */
    @media (max-width: 639px) {
        .mobile-only-block {
            display: block;
        }

        .desktop-only-block {
            display: none;
        }
    }
</style>

<main class="flex-1 min-w-0 transition-all duration-300" x-data="tracingManager()" x-init="init()">
    <h1 class="text-sm font-bold mb-4 text-gray-800">Pelacakan Item Transaksi</h1>

    <div class="flex flex-col text-xs" :class="{ 'lg:flex-row lg:space-x-3': showComparisonPanel }">
        <div class="bg-white shadow-lg rounded-lg p-4 mb-4 lg:mb-0"
            :class="showComparisonPanel ? 'w-full lg:w-1/2' : 'w-full'">
            <h2 class="text-sm font-semibold mb-3 text-gray-700">Detail Pelacakan</h2>
            <div class="mb-3">
                <label for="itemSelect" class="block text-xs font-medium text-gray-700 mb-1">Pilih Item:</label>
                <select id="itemSelect" class="w-full text-xs">
                    <option value="">-- Cari atau Pilih Item --</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="storageSelect" class="block text-xs font-medium text-gray-700 mb-1">Pilih Storage:</label>
                <select id="storageSelect" x-model="selectedStorage" @change="fetchTransactions()"
                    class="mt-0 block w-full pl-2 pr-8 py-1 text-xs border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm">
                    <option value="">-- Semua Storage --</option>
                    <template x-for="storage in availableStorages" :key="storage">
                        <option :value="storage" x-text="storage"></option>
                    </template>
                </select>
            </div>
            <div class="mb-3">
                <label for="barcodeInput" class="block text-xs font-medium text-gray-700 mb-1">Barcode:</label>
                <input type="text" id="barcodeInput" x-model="selectedBarcode" @input="handleBarcodeChange()"
                    class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs"
                    placeholder="Masukkan barcode...">
            </div>

            <div x-show="itemDetails && selectedItem" class="bg-gray-50 p-3 rounded-md mb-4 text-xs">
                <h3 class="text-sm font-semibold text-gray-800 mb-1">Informasi Item:</h3>
                <p><strong class="font-medium">SKU:</strong> <span x-text="itemDetails.sku"></span></p>
                <p><strong class="font-medium">Item:</strong> <span x-text="itemDetails.item"></span></p>
                <p><strong class="font-medium">Subitem:</strong> <span
                        x-text="itemDetails.subitem || 'N/A'"></span></p>
                <p><strong class="font-medium">Kategori:</strong> <span
                        x-text="itemDetails.kategory || 'N/A'"></span></p>
                <p><strong class="font-medium">Aktif:</strong> <span
                        x-text="itemDetails.isActive ? 'Ya' : 'Tidak'"></span></p>
            </div>

            <div class="mb-4 text-xs">
                <h3 class="text-sm font-semibold text-gray-800 mb-2">
                    Ringkasan Transaksi
                    <span x-show="transactionDateRange">(<span x-text="transactionDateRange"></span>)</span>
                </h3>
                <div x-show="transactionsLoading" class="flex items-center justify-center space-x-1 py-3">
                    <i class="fas fa-spinner fa-spin h-3 w-3 text-blue-500"></i>
                    <span>Memuat transaksi...</span>
                </div>
                <div x-show="!transactionsLoading && transactionsErrorMessage"
                    class="p-2 bg-red-100 text-red-700 rounded-md" x-text="transactionsErrorMessage"></div>
                <div x-show="!transactionsLoading && !transactionsErrorMessage && Object.keys(dailyTransactions).length === 0 && selectedItem"
                    class="p-2 bg-yellow-100 text-yellow-700 rounded-md">Tidak ada transaksi ditemukan untuk item ini.
                </div>
                <div x-show="!transactionsLoading && !transactionsErrorMessage && !selectedItem"
                    class="p-2 bg-yellow-100 text-yellow-700 rounded-md">Pilih item untuk melihat detail transaksi.
                </div>

                <div x-show="!transactionsLoading && !transactionsErrorMessage && selectedItem">
                    <div x-show="initialStock.quantity_kg !== null" class="bg-blue-50 p-2 rounded-md mb-3">
                        <p><strong class="font-medium">Stok Awal (<span x-text="formatDate(startDate)"></span>):</strong> <span x-text="initialStock.ekor_pack"></span> ekor/pack (<span x-text="initialStock.quantity_kg.toFixed(2)"></span> kg)</p>
                        <p><strong class="font-medium">Stok Saat Ini (<span x-text="formatDate(endDate)"></span>):</strong> <span x-text="currentStock.ekor_pack"></span> ekor/pack (<span x-text="currentStock.quantity_kg.toFixed(2)"></span> kg)</p>
                    </div>
                </div>

                <div x-show="!transactionsLoading && !transactionsErrorMessage && Object.keys(dailyTransactions).length > 0">
                    <button @click="toggleAllTransactions()"
                        class="mb-3 px-3 py-1 text-xs bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <span x-text="allExpanded ? 'Ciutkan Semua Detail' : 'Perluas Semua Detail'"></span>
                    </button>

                    <div class="desktop-only-block">
                        <template x-for="(data, date) in dailyTransactions" :key="date">
                            <div class="border rounded-lg mb-2 overflow-hidden">
                                <div class="bg-gray-100 p-2 flex justify-between items-center cursor-pointer text-xs"
                                    @click="toggleTransactionDetail(date)">
                                    <h4 class="font-medium text-gray-700" x-text="formatDate(date)"></h4>
                                    <span class="font-semibold"
                                        x-text="`Total: ${data.total_ekor_pack} ekor/pack (${data.total_quantity_kg.toFixed(2)} kg)`"></span>
                                    <span class="font-semibold"
                                        x-text="`Stok Akhir Hari: ${data.end_of_day_stock_ekor_pack} ekor/pack (${data.end_of_day_stock_kg.toFixed(2)} kg)`"></span>
                                    <i :class="{ 'rotate-90': expandedTransactions[date] }"
                                        class="fas fa-chevron-right w-3 h-3 text-gray-600 transform transition-transform duration-200">
                                    </i>
                                </div>
                                <div x-show="expandedTransactions[date]" x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 transform -translate-y-2"
                                    x-transition:enter-end="opacity-100 transform translate-y-0"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100 transform translate-y-0"
                                    x-transition:leave-end="opacity-0 transform -translate-y-2" class="p-2 border-t">
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full text-xs text-gray-600 table-fixed">
                                            <thead>
                                                <tr class="bg-gray-50">
                                                    <th class="px-1 py-1 text-left font-semibold w-[70px]">Waktu</th>
                                                    <th class="px-1 py-1 text-left font-semibold w-[100px]">Tipe Transaksi</th>
                                                    <th class="px-1 py-1 text-left font-semibold w-[70px]">Barcode</th>
                                                    <th class="px-1 py-1 text-left font-semibold w-[80px]">Dari Lokasi</th>
                                                    <th class="px-1 py-1 text-left font-semibold w-[80px]">Ke Lokasi</th>
                                                    <th class="px-1 py-1 text-left font-semibold w-[70px]">Ekor/Pack</th>
                                                    <th class="px-1 py-1 text-left font-semibold w-[70px]">Qty (kg)</th>
                                                    <th class="px-1 py-1 text-left font-semibold w-[70px]">Rataan (kg)</th>
                                                    <th class="px-1 py-1 text-left font-semibold w-[70px]">Stok (E/P)</th>
                                                    <th class="px-1 py-1 text-left font-semibold w-[70px]">Stok (kg)</th>
                                                    <th class="px-1 py-1 text-left font-semibold w-[70px]">Keterangan</th>
                                                    <th class="px-1 py-1 text-left font-semibold w-[70px]">User</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="trans in data.details" :key="trans.id">
                                                    <tr class="border-t border-gray-200"
                                                        :class="getTransactionRowClass(trans.transaction_type)">
                                                        <td class="px-1 py-1 w-[70px]" x-text="trans.created_at"></td>
                                                        <td class="px-1 py-1 w-[100px]" x-text="trans.transaction_type"></td>
                                                        <td class="px-1 py-1 w-[70px]" x-text="trans.barcode"
                                                            :class="{'bg-yellow-300': !trans.barcode}"></td>
                                                        <td class="px-1 py-1 w-[80px]" x-text="trans.from_location || '-'"></td>
                                                        <td class="px-1 py-1 w-[80px]" x-text="trans.to_location || '-'"></td>
                                                        <td class="px-1 py-1 w-[70px]" x-text="trans.ekor_pack || 0"></td>
                                                        <td class="px-1 py-1 w-[70px]" x-text="trans.quantity_kg.toFixed(2)"></td>
                                                        <td class="px-1 py-1 w-[70px]"
                                                            :class="getAverageClass(trans, selectedItem)">
                                                            <template x-if="isAyam(selectedItem)">
                                                                <span x-text="getAverage(trans)"></span>
                                                            </template>
                                                        </td>
                                                        <td class="px-1 py-1 w-[70px]" x-text="trans.running_stock_ekor || 0"></td>
                                                        <td class="px-1 py-1 w-[70px]" x-text="trans.running_stock_kg.toFixed(2)"></td>
                                                        <td class="px-1 py-1 w-[70px] cursor-pointer overflow-hidden whitespace-nowrap text-ellipsis"
                                                            @click="showKeteranganModal(trans.keterangan)"
                                                            x-text="truncateText(trans.keterangan, 5)"> </td>
                                                        <td class="px-1 py-1 w-[70px]" x-text="trans.user || '-'"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="mobile-only-block space-y-3">
                        <template x-for="(data, date) in dailyTransactions" :key="date">
                            <div class="border rounded-lg overflow-hidden shadow-sm">
                                <div class="bg-gray-100 p-3 flex flex-col cursor-pointer"
                                    @click="toggleTransactionDetail(date)">
                                    <div class="flex justify-between items-center">
                                        <h4 class="font-bold text-sm text-gray-700" x-text="formatDate(date)"></h4>
                                        <i :class="{ 'rotate-90': expandedTransactions[date] }"
                                            class="fas fa-chevron-right w-4 h-4 text-gray-600 transform transition-transform duration-200">
                                        </i>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-1">
                                        <span class="font-medium"
                                            x-text="`Total: ${data.total_ekor_pack} E/P (${data.total_quantity_kg.toFixed(2)} Kg)`"></span>
                                        | Stok Akhir: <span class="font-medium"
                                            x-text="`${data.end_of_day_stock_ekor_pack} E/P (${data.end_of_day_stock_kg.toFixed(2)} Kg)`"></span>
                                    </p>
                                </div>
                                <div x-show="expandedTransactions[date]" x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 transform -translate-y-2"
                                    x-transition:enter-end="opacity-100 transform translate-y-0"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100 transform translate-y-0"
                                    x-transition:leave-end="opacity-0 transform -translate-y-2" class="p-3 space-y-3 border-t">
                                    <template x-for="(trans, transIndex) in data.details" :key="trans.id">
                                        <div class="p-3 border rounded-md"
                                            :class="getTransactionRowClass(trans.transaction_type, true)">
                                            <div class="flex justify-between items-center text-xs pb-1 border-b mb-1">
                                                <span class="font-bold" x-text="trans.transaction_type"></span>
                                                <span class="text-gray-500" x-text="trans.created_at"></span>
                                            </div>
                                            <div class="grid grid-cols-2 gap-y-1 text-xs">
                                                <p><strong>Ekor/Pack:</strong> <span x-text="trans.ekor_pack || 0"></span></p>
                                                <p><strong>Qty (Kg):</strong> <span x-text="trans.quantity_kg.toFixed(2)"></span></p>
                                                <p><strong>Stok (E/P):</strong> <span x-text="trans.running_stock_ekor || 0"></span></p>
                                                <p><strong>Stok (Kg):</strong> <span x-text="trans.running_stock_kg.toFixed(2)"></span></p>
                                                <p class="col-span-2"><strong>Lokasi:</strong> <span
                                                        x-text="trans.from_location ? `${trans.from_location} --> ${trans.to_location}` : trans.to_location"></span></p>
                                                <p class="col-span-2"><strong>Barcode:</strong> <span
                                                        x-text="trans.barcode || 'N/A'"></span></p>
                                                <p class="col-span-2"><strong>Keterangan:</strong> <span
                                                        class="cursor-pointer text-blue-600 underline"
                                                        @click="showKeteranganModal(trans.keterangan)"
                                                        x-text="truncateText(trans.keterangan, 15)"></span></p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
        <div x-show="showComparisonPanel" class="w-full lg:w-1/2 bg-white shadow-lg rounded-lg p-4 text-xs">
            <h2 class="text-sm font-semibold mb-3 text-gray-700">Perbandingan Item</h2>
            <div class="mb-3">
                <label for="comparisonItemSelect" class="block text-xs font-medium text-gray-700 mb-1">Pilih Item untuk Perbandingan:</label>
                <select id="comparisonItemSelect" class="w-full text-xs">
                    <option value="">-- Cari atau Pilih Item --</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="comparisonStorageSelect" class="block text-xs font-medium text-gray-700 mb-1">Pilih Storage (Komparasi):</label>
                <select id="comparisonStorageSelect" x-model="selectedComparisonStorage" @change="fetchComparisonTransactions()"
                    class="mt-0 block w-full pl-2 pr-8 py-1 text-xs border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm">
                    <option value="">-- Semua Storage --</option>
                    <template x-for="storage in availableStorages" :key="storage">
                        <option :value="storage" x-text="storage"></option>
                    </template>
                </select>
            </div>

            <div class="mb-3">
                <label for="comparisonBarcodeInput" class="block text-xs font-medium text-gray-700 mb-1">Barcode (Komparasi):</label>
                <input type="text" id="comparisonBarcodeInput" x-model="selectedComparisonBarcode"
                    @input="handleComparisonBarcodeChange()"
                    class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs"
                    placeholder="Masukkan barcode...">
            </div>

            <div x-show="comparisonItemDetails && selectedComparisonItem" class="bg-gray-50 p-3 rounded-md mb-4 text-xs">
                <h3 class="text-sm font-semibold text-gray-800 mb-1">Informasi Item Perbandingan:</h3>
                <p><strong class="font-medium">SKU:</strong> <span x-text="comparisonItemDetails.sku"></span></p>
                <p><strong class="font-medium">Item:</strong> <span x-text="comparisonItemDetails.item"></span></p>
                <p><strong class="font-medium">Subitem:</strong> <span
                        x-text="comparisonItemDetails.subitem || 'N/A'"></span></p>
                <p><strong class="font-medium">Kategori:</strong> <span
                        x-text="comparisonItemDetails.kategory || 'N/A'"></span></p>
                <p><strong class="font-medium">Aktif:</strong> <span
                        x-text="comparisonItemDetails.isActive ? 'Ya' : 'Tidak'"></span></p>
            </div>

            <div class="mb-4 text-xs">
                <h3 class="text-sm font-semibold text-gray-800 mb-2">
                    Ringkasan Transaksi Perbandingan
                    <span x-show="comparisonTransactionDateRange">(<span x-text="comparisonTransactionDateRange"></span>)</span>
                </h3>
                <div x-show="comparisonTransactionsLoading" class="flex items-center justify-center space-x-1 py-3">
                    <i class="fas fa-spinner fa-spin h-3 w-3 text-blue-500"></i>
                    <span>Memuat transaksi perbandingan...</span>
                </div>
                <div x-show="!comparisonTransactionsLoading && comparisonTransactionsErrorMessage"
                    class="p-2 bg-red-100 text-red-700 rounded-md" x-text="comparisonTransactionsErrorMessage"></div>
                <div x-show="!comparisonTransactionsLoading && !comparisonTransactionsErrorMessage && Object.keys(dailyComparisonTransactions).length === 0 && selectedComparisonItem"
                    class="p-2 bg-yellow-100 text-yellow-700 rounded-md">Tidak ada transaksi ditemukan untuk item perbandingan ini.
                </div>
                <div x-show="!comparisonTransactionsLoading && !comparisonTransactionsErrorMessage && !selectedComparisonItem"
                    class="p-2 bg-yellow-100 text-yellow-700 rounded-md">Pilih item untuk perbandingan.
                </div>

                <div x-show="!comparisonTransactionsLoading && !comparisonTransactionsErrorMessage && selectedComparisonItem">
                    <div x-show="comparisonInitialStock.quantity_kg !== null" class="bg-blue-50 p-2 rounded-md mb-3">
                        <p><strong class="font-medium">Stok Awal (<span x-text="formatDate(comparisonStartDate)"></span>):</strong> <span x-text="comparisonInitialStock.ekor_pack"></span> ekor/pack (<span x-text="comparisonInitialStock.quantity_kg.toFixed(2)"></span> kg)</p>
                        <p><strong class="font-medium">Stok Saat Ini (<span x-text="formatDate(comparisonEndDate)"></span>):</strong> <span x-text="comparisonCurrentStock.ekor_pack"></span> ekor/pack (<span x-text="comparisonCurrentStock.quantity_kg.toFixed(2)"></span> kg)</p>
                    </div>
                </div>

                <div
                    x-show="!comparisonTransactionsLoading && !comparisonTransactionsErrorMessage && Object.keys(dailyComparisonTransactions).length > 0">
                    <button @click="toggleAllComparisonTransactions()"
                        class="mb-3 px-3 py-1 text-xs bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <span x-text="allComparisonExpanded ? 'Ciutkan Semua Detail' : 'Perluas Semua Detail'"></span>
                    </button>

                    <div class="desktop-only-block">
                        <template x-for="(data, date) in dailyComparisonTransactions" :key="date">
                            <div class="border rounded-lg mb-2 overflow-hidden">
                                <div class="bg-gray-100 p-2 flex justify-between items-center cursor-pointer text-xs"
                                    @click="toggleComparisonTransactionDetail(date)">
                                    <h4 class="font-medium text-gray-700" x-text="formatDate(date)"></h4>
                                    <span class="font-semibold"
                                        x-text="`Total: ${data.total_ekor_pack} ekor/pack (${data.total_quantity_kg.toFixed(2)} kg)`"></span>
                                    <span class="font-semibold"
                                        x-text="`Stok Akhir Hari: ${data.end_of_day_stock_ekor_pack} ekor/pack (${data.end_of_day_stock_kg.toFixed(2)} kg)`"></span>
                                    <i :class="{ 'rotate-90': expandedComparisonTransactions[date] }"
                                        class="fas fa-chevron-right w-3 h-3 text-gray-600 transform transition-transform duration-200">
                                    </i>
                                </div>
                                <div x-show="expandedComparisonTransactions[date]" x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 transform -translate-y-2"
                                    x-transition:enter-end="opacity-100 transform translate-y-0"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100 transform translate-y-0"
                                    x-transition:leave-end="opacity-0 transform -translate-y-2" class="p-2 border-t">
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full text-xs text-gray-600 table-fixed">
                                            <thead>
                                                <tr class="bg-gray-50">
                                                    <th class="px-1 py-1 text-left font-semibold w-[70px]">Waktu</th>
                                                    <th class="px-1 py-1 text-left font-semibold w-[100px]">Tipe Transaksi</th>
                                                    <th class="px-1 py-1 text-left font-semibold w-[70px]">Barcode</th>
                                                    <th class="px-1 py-1 text-left font-semibold w-[80px]">Dari Lokasi</th>
                                                    <th class="px-1 py-1 text-left font-semibold w-[80px]">Ke Lokasi</th>
                                                    <th class="px-1 py-1 text-left font-semibold w-[70px]">Ekor/Pack</th>
                                                    <th class="px-1 py-1 text-left font-semibold w-[70px]">Qty (kg)</th>
                                                    <th class="px-1 py-1 text-left font-semibold w-[70px]">Rataan (kg)</th>
                                                    <th class="px-1 py-1 text-left font-semibold w-[70px]">Stok (E/P)</th>
                                                    <th class="px-1 py-1 text-left font-semibold w-[70px]">Stok (kg)</th>
                                                    <th class="px-1 py-1 text-left font-semibold w-[70px]">Keterangan</th>
                                                    <th class="px-1 py-1 text-left font-semibold w-[70px]">User</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="trans in data.details" :key="trans.id">
                                                    <tr class="border-t border-gray-200"
                                                        :class="getTransactionRowClass(trans.transaction_type)">
                                                        <td class="px-1 py-1 w-[70px]" x-text="trans.created_at"></td>
                                                        <td class="px-1 py-1 w-[100px]" x-text="trans.transaction_type"></td>
                                                        <td class="px-1 py-1 w-[70px]" x-text="trans.barcode"
                                                            :class="{'bg-yellow-300': !trans.barcode}"></td>
                                                        <td class="px-1 py-1 w-[80px]" x-text="trans.from_location || '-'"></td>
                                                        <td class="px-1 py-1 w-[80px]" x-text="trans.to_location || '-'"></td>
                                                        <td class="px-1 py-1 w-[70px]" x-text="trans.ekor_pack || 0"></td>
                                                        <td class="px-1 py-1 w-[70px]" x-text="trans.quantity_kg.toFixed(2)"></td>
                                                        <td class="px-1 py-1 w-[70px]"
                                                            :class="getAverageClass(trans, selectedComparisonItem)">
                                                            <template x-if="isAyam(selectedComparisonItem)">
                                                                <span x-text="getAverage(trans)"></span>
                                                            </template>
                                                        </td>
                                                        <td class="px-1 py-1 w-[70px]" x-text="trans.running_stock_ekor || 0"></td>
                                                        <td class="px-1 py-1 w-[70px]" x-text="trans.running_stock_kg.toFixed(2)"></td>
                                                        <td class="px-1 py-1 w-[70px] cursor-pointer overflow-hidden whitespace-nowrap text-ellipsis"
                                                            @click="showKeteranganModal(trans.keterangan)"
                                                            x-text="truncateText(trans.keterangan, 5)">
                                                        </td>
                                                        <td class="px-1 py-1 w-[70px]" x-text="trans.user || '-'"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="mobile-only-block space-y-3">
                        <template x-for="(data, date) in dailyComparisonTransactions" :key="date">
                            <div class="border rounded-lg overflow-hidden shadow-sm">
                                <div class="bg-gray-100 p-3 flex flex-col cursor-pointer"
                                    @click="toggleComparisonTransactionDetail(date)">
                                    <div class="flex justify-between items-center">
                                        <h4 class="font-bold text-sm text-gray-700" x-text="formatDate(date)"></h4>
                                        <i :class="{ 'rotate-90': expandedComparisonTransactions[date] }"
                                            class="fas fa-chevron-right w-4 h-4 text-gray-600 transform transition-transform duration-200">
                                        </i>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-1">
                                        <span class="font-medium"
                                            x-text="`Total: ${data.total_ekor_pack} E/P (${data.total_quantity_kg.toFixed(2)} Kg)`"></span>
                                        | Stok Akhir: <span class="font-medium"
                                            x-text="`${data.end_of_day_stock_ekor_pack} E/P (${data.end_of_day_stock_kg.toFixed(2)} Kg)`"></span>
                                    </p>
                                </div>
                                <div x-show="expandedComparisonTransactions[date]" x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 transform -translate-y-2"
                                    x-transition:enter-end="opacity-100 transform translate-y-0"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100 transform translate-y-0"
                                    x-transition:leave-end="opacity-0 transform -translate-y-2" class="p-3 space-y-3 border-t">
                                    <template x-for="(trans, transIndex) in data.details" :key="trans.id">
                                        <div class="p-3 border rounded-md"
                                            :class="getTransactionRowClass(trans.transaction_type, true)">
                                            <div class="flex justify-between items-center text-xs pb-1 border-b mb-1">
                                                <span class="font-bold" x-text="trans.transaction_type"></span>
                                                <span class="text-gray-500" x-text="trans.created_at"></span>
                                            </div>
                                            <div class="grid grid-cols-2 gap-y-1 text-xs">
                                                <p><strong>Ekor/Pack:</strong> <span x-text="trans.ekor_pack || 0"></span></p>
                                                <p><strong>Qty (Kg):</strong> <span x-text="trans.quantity_kg.toFixed(2)"></span></p>
                                                <p><strong>Stok (E/P):</strong> <span x-text="trans.running_stock_ekor || 0"></span></p>
                                                <p><strong>Stok (Kg):</strong> <span x-text="trans.running_stock_kg.toFixed(2)"></span></p>
                                                <p class="col-span-2"><strong>Lokasi:</strong> <span
                                                        x-text="trans.from_location ? `${trans.from_location} $\rightarrow$ ${trans.to_location}` : trans.to_location"></span></p>
                                                <p class="col-span-2"><strong>Barcode:</strong> <span
                                                        x-text="trans.barcode || 'N/A'"></span></p>
                                                <p class="col-span-2"><strong>Keterangan:</strong> <span
                                                        class="cursor-pointer text-blue-600 underline"
                                                        @click="showKeteranganModal(trans.keterangan)"
                                                        x-text="truncateText(trans.keterangan, 15)"></span></p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button @click="toggleComparisonPanel()"
        class="mt-4 px-4 py-2 text-xs bg-green-600 text-white font-semibold rounded-lg shadow-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-75 transition duration-300">
        <span x-text="showComparisonPanel ? 'Sembunyikan Perbandingan' : 'Bandingkan Item'"></span>
    </button>
</main>


<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('tracingManager', () => ({
            API_ENDPOINT: './controller/tracing/route.php',

            availableItems: [],
            availableStorages: ['Chiller 1', 'Chiller 2', 'Chiller 3'],

            selectedItem: '',
            selectedStorage: '',
            selectedBarcode: '',
            selectedComparisonBarcode: '',
            itemDetails: null,
            dailyTransactions: {},
            transactionsLoading: false,
            transactionsErrorMessage: '',
            expandedTransactions: {},
            allExpanded: false,
            initialStock: {
                quantity_kg: null,
                ekor_pack: null
            },
            currentStock: { // Ini adalah stok akhir dari seluruh rentang tanggal yang dipilih
                quantity_kg: null,
                ekor_pack: null
            },
            transactionDateRange: '',
            startDate: '',
            endDate: '',

            showComparisonPanel: false,
            selectedComparisonItem: '',
            selectedComparisonStorage: '',
            comparisonItemDetails: null,
            dailyComparisonTransactions: {},
            comparisonTransactionsLoading: false,
            comparisonTransactionsErrorMessage: '',
            expandedComparisonTransactions: {},
            allComparisonExpanded: false,
            comparisonInitialStock: {
                quantity_kg: null,
                ekor_pack: null
            },
            comparisonCurrentStock: {
                quantity_kg: null,
                ekor_pack: null
            },
            comparisonTransactionDateRange: '',
            comparisonStartDate: '', // Make sure these are initialized
            comparisonEndDate: '',
            debouncedFetchTransactions: null,
            debouncedFetchComparisonTransactions: null,

            debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            },

            // Handler dengan debounce
            handleBarcodeChange() {
                this.debouncedFetchTransactions();
            },
            handleComparisonBarcodeChange() {
                this.debouncedFetchComparisonTransactions();
            },

            async init() {
                // Inisialisasi tanggal default (30 hari terakhir) untuk main panel
                const today = new Date();
                const thirtyDaysAgo = new Date();
                thirtyDaysAgo.setDate(today.getDate() - 30);
                this.startDate = thirtyDaysAgo.toISOString().split('T')[0];
                this.endDate = today.toISOString().split('T')[0];

                // Inisialisasi tanggal default (30 hari terakhir) untuk comparison panel
                // Ini penting agar fetchComparisonTransactions memiliki tanggal untuk dikirim
                const comparisonToday = new Date();
                const comparisonThirtyDaysAgo = new Date();
                comparisonThirtyDaysAgo.setDate(comparisonToday.getDate() - 30);
                this.comparisonStartDate = comparisonThirtyDaysAgo.toISOString().split('T')[0];
                this.comparisonEndDate = comparisonToday.toISOString().split('T')[0];

                // Setup debounced functions
                this.debouncedFetchTransactions = this.debounce(() => {
                    this.fetchTransactions();
                }, 500);

                this.debouncedFetchComparisonTransactions = this.debounce(() => {
                    this.fetchComparisonTransactions();
                }, 500);


                // Fetch items pertama kali
                await this.fetchItems();

                // Inisialisasi Select2 untuk item (hanya item, storage tidak pakai Select2 lagi)
                this.initSelect2ForItems();

                // Isi dropdown storage biasa
                this.populateStorageSelects();

                // Capture item and storage from URL if available for the main item and storage
                const urlParams = new URLSearchParams(window.location.search);
                const itemFromUrl = urlParams.get('item');
                const storageFromUrl = urlParams.get('storage');
                const barcodeFromUrl = urlParams.get('barcode');

                this.selectedBarcode = barcodeFromUrl ? decodeURIComponent(barcodeFromUrl) : '';

                if (itemFromUrl) {
                    this.selectedItem = decodeURIComponent(itemFromUrl);
                    // Set Select2 value for the item
                    $('#itemSelect').val(this.selectedItem).trigger('change.select2');
                    this.fetchItemDetails(); // Fetch details for the pre-selected item
                }

                if (storageFromUrl) {
                    this.selectedStorage = decodeURIComponent(storageFromUrl);
                    // Set value for regular select dropdown
                    document.getElementById('storageSelect').value = this.selectedStorage;
                }

                // If an item was pre-selected, fetch its transactions
                if (itemFromUrl) {
                    this.fetchTransactions();
                }

                // You might also want to handle URL parameters for comparison items if needed,
                // but for a typical comparison flow, they are selected after initial load.
            },


            // Fungsi ini sekarang HANYA mengisi opsi ke elemen <select> biasa
            populateStorageSelects() {
                const storageSelect = document.getElementById('storageSelect');
                const comparisonStorageSelect = document.getElementById('comparisonStorageSelect');

                // Clear existing options
                storageSelect.innerHTML = '<option value="">-- Semua Storage --</option>';
                comparisonStorageSelect.innerHTML = '<option value="">-- Semua Storage --</option>';

                // Add options from availableStorages
                this.availableStorages.forEach(storage => {
                    const option1 = document.createElement('option');
                    option1.value = storage;
                    option1.textContent = storage;
                    storageSelect.appendChild(option1);

                    const option2 = document.createElement('option');
                    option2.value = storage;
                    option2.textContent = storage;
                    comparisonStorageSelect.appendChild(option2);
                });
            },

            async fetchItems() {
                try {
                    const response = await fetch(`${this.API_ENDPOINT}?action=get_items_filter`);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const result = await response.json();
                    if (result.success && result.data && Array.isArray(result.data.items)) {
                        this.availableItems = result.data.items;

                        // Pastikan Select2 dihancurkan dan dibuat ulang sebelum mengisi opsi
                        if ($('#itemSelect').data('select2')) {
                            $('#itemSelect').select2('destroy');
                        }
                        if ($('#comparisonItemSelect').data('select2')) {
                            $('#comparisonItemSelect').select2('destroy');
                        }

                        // Clear existing options, then append new ones
                        $('#itemSelect').empty().append(new Option('-- Cari atau Pilih Item --', ''));
                        $('#comparisonItemSelect').empty().append(new Option('-- Cari atau Pilih Item --', ''));

                        this.availableItems.forEach(item => {
                            $('#itemSelect').append(new Option(item, item));
                            $('#comparisonItemSelect').append(new Option(item, item));
                        });

                    } else {
                        console.error('Failed to fetch items:', result.message || 'Unknown error');
                        alert('Gagal memuat daftar item: ' + (result.message || 'Error tidak diketahui'));
                    }
                } catch (error) {
                    console.error('Error fetching items:', error);
                    alert('Terjadi kesalahan saat mengambil item: ' + error.message);
                }
            },

            // Fungsi ini hanya untuk inisialisasi Select2 untuk item
            initSelect2ForItems() {
                // Hancurkan Select2 yang sudah ada sebelum inisialisasi ulang
                if ($('#itemSelect').data('select2')) {
                    $('#itemSelect').select2('destroy');
                }
                if ($('#comparisonItemSelect').data('select2')) {
                    $('#comparisonItemSelect').select2('destroy');
                }

                // Inisialisasi Select2 untuk pemilihan item utama
                $('#itemSelect').select2({
                    placeholder: '-- Cari atau Pilih Item --',
                    allowClear: true,
                    width: '100%',
                });

                // Event listeners for main item
                $('#itemSelect').off('select2:select').on('select2:select', (e) => {
                    this.selectedItem = e.params.data.id;
                    this.fetchItemDetails();
                    this.fetchTransactions();
                });
                $('#itemSelect').off('select2:unselect').on('select2:unselect', () => {
                    this.selectedItem = '';
                    this.itemDetails = null;
                    this.dailyTransactions = {};
                    this.transactionsErrorMessage = '';
                    this.expandedTransactions = {};
                    this.allExpanded = false;
                    this.initialStock = {
                        quantity_kg: null,
                        ekor_pack: null
                    };
                    this.currentStock = {
                        quantity_kg: null,
                        ekor_pack: null
                    };
                    this.transactionDateRange = '';
                });

                // Inisialisasi Select2 untuk pemilihan item perbandingan
                $('#comparisonItemSelect').select2({
                    placeholder: '-- Cari atau Pilih Item --',
                    allowClear: true,
                    width: '100%',
                });

                // Event listeners for comparison item
                $('#comparisonItemSelect').off('select2:select').on('select2:select', (e) => {
                    this.selectedComparisonItem = e.params.data.id;
                    this.fetchComparisonItemDetails();
                    this.fetchComparisonTransactions();
                });
                $('#comparisonItemSelect').off('select2:unselect').on('select2:unselect', () => {
                    this.selectedComparisonItem = '';
                    this.comparisonItemDetails = null;
                    this.dailyComparisonTransactions = {};
                    this.comparisonTransactionsErrorMessage = '';
                    this.expandedComparisonTransactions = {};
                    this.allComparisonExpanded = false;
                    this.comparisonInitialStock = {
                        quantity_kg: null,
                        ekor_pack: null
                    };
                    this.comparisonCurrentStock = {
                        quantity_kg: null,
                        ekor_pack: null
                    };
                    this.comparisonTransactionDateRange = '';
                });

                // Event listeners untuk storage sekarang akan ditangani oleh Alpine.js
                // atau listener langsung ke elemen HTML <select>
            },

            // --- Bagian ini untuk penanganan event listener untuk dropdown <select> biasa ---
            // Pastikan elemen <select> Anda memiliki x-model="selectedStorage"
            // dan x-on:change="fetchTransactions()"
            // dan x-model="selectedComparisonStorage"
            // dan x-on:change="fetchComparisonTransactions()"
            // Contoh di HTML:
            // <select id="storageSelect" x-model="selectedStorage" @change="fetchTransactions()" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            //     <option value="">-- Semua Storage --</option>
            //     <template x-for="storage in availableStorages" :key="storage">
            //         <option :value="storage" x-text="storage"></option>
            //     </template>
            // </select>

            async fetchItemDetails() {
                if (!this.selectedItem) {
                    this.itemDetails = null;
                    return;
                }
                try {
                    const response = await fetch(`${this.API_ENDPOINT}?action=get_item_details&item_name=${encodeURIComponent(this.selectedItem)}`);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const result = await response.json();
                    if (result.success) {
                        this.itemDetails = result.data;
                    } else {
                        console.error('Failed to fetch item details:', result.message);
                        this.itemDetails = null;
                    }
                } catch (error) {
                    console.error('Error fetching item details:', error);
                    this.itemDetails = null;
                }
            },

            async fetchComparisonItemDetails() {
                if (!this.selectedComparisonItem) {
                    this.comparisonItemDetails = null;
                    return;
                }
                try {
                    const response = await fetch(`${this.API_ENDPOINT}?action=get_item_details&item_name=${encodeURIComponent(this.selectedComparisonItem)}`);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const result = await response.json();
                    if (result.success) {
                        this.comparisonItemDetails = result.data;
                    } else {
                        console.error('Failed to fetch comparison item details:', result.message);
                        this.comparisonItemDetails = null;
                    }
                } catch (error) {
                    console.error('Error fetching comparison item details:', error);
                    this.comparisonItemDetails = null;
                }
            },

            isAyam(itemName) {
                if (!itemName) return false;
                return itemName.toLowerCase().includes('ayam');
            },

            // Fungsi untuk memotong teks keterangan
            truncateText(text, length) {
                if (!text) return '-';
                if (text.length <= length) return text;
                return text.substring(0, length) + '...';
            },

            // Fungsi untuk menampilkan modal keterangan
            showKeteranganModal(text) {
                Swal.fire({
                    title: 'Detail Keterangan',
                    html: `<div class="text-left whitespace-pre-wrap">${text}</div>`,
                    width: '600px',
                    confirmButtonText: 'Tutup',
                });
            },

            // Fungsi untuk mendapatkan rata-rata berat
            getAverage(trans) {
                if (trans.quantity_kg && trans.ekor_pack) {
                    // Mencegah pembagian dengan nol
                    if (trans.ekor_pack === 0) {
                        return '-';
                    }
                    return (trans.quantity_kg / trans.ekor_pack).toFixed(2);
                }
                return '-';
            },

            // Fungsi untuk mengekstrak range angka dari nama item
            parseItemWeightRange(itemName) {
                if (!itemName) return null;
                const match = itemName.match(/(\d+)-(\d+)/);
                if (match) {
                    // Ubah dari format 10-11 menjadi 1.0-1.1
                    const min = parseFloat(match[1]) / 10;
                    const max = parseFloat(match[2]) / 10;
                    return [min, max];
                }
                return null;
            },

            // Fungsi untuk menentukan kelas warna background berdasarkan validasi
            getAverageClass(trans, itemName) {
                const avg = this.getAverage(trans);
                if (avg === '-') {
                    return ''; // Tidak ada warna jika data tidak valid
                }

                const range = this.parseItemWeightRange(itemName);

                if (range) {
                    const avgValue = parseFloat(avg);
                    const [min, max] = range;

                    if (avgValue >= min && avgValue <= max) {
                        return 'bg-green-500 text-white'; // Hijau jika di dalam range
                    } else {
                        return 'bg-red-500 text-white'; // Merah jika di luar range
                    }
                }
                return ''; // Tidak ada range, tidak ada warna
            },

            async fetchTransactions() {
                if (!this.selectedItem) {
                    this.dailyTransactions = {};
                    this.transactionsErrorMessage = '';
                    this.initialStock = {
                        quantity_kg: null,
                        ekor_pack: null
                    };
                    this.currentStock = {
                        quantity_kg: null,
                        ekor_pack: null
                    };
                    this.transactionDateRange = '';
                    return;
                }

                this.transactionsLoading = true;
                this.transactionsErrorMessage = '';
                this.dailyTransactions = {}; // Reset dailyTransactions on new fetch
                this.initialStock = {
                    quantity_kg: null,
                    ekor_pack: null
                };
                this.currentStock = {
                    quantity_kg: null,
                    ekor_pack: null
                };
                this.transactionDateRange = '';

                let url = `${this.API_ENDPOINT}?action=get_transactions_with_initial_stock&item_name=${encodeURIComponent(this.selectedItem)}&start_date=${this.startDate}&end_date=${this.endDate}`;
                if (this.selectedStorage) {
                    url += `&storage=${encodeURIComponent(this.selectedStorage)}`;
                }
                // Tambahkan barcode jika ada
                if (this.selectedBarcode) {
                    url += `&barcode=${encodeURIComponent(this.selectedBarcode)}`;
                }

                try {
                    const response = await fetch(url);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const result = await response.json();

                    if (result.success) {
                        this.initialStock = result.data.initial_stock;
                        this.dailyTransactions = this.processTransactions(result.data.transactions, this.initialStock);

                        if (result.data.transactions.length > 0) {
                            this.currentStock = {
                                quantity_kg: result.data.transactions[0].running_stock_kg,
                                ekor_pack: result.data.transactions[0].running_stock_ekor
                            };
                        } else {
                            // If no transactions in the range, current stock is the initial stock
                            this.currentStock = {
                                quantity_kg: this.initialStock.quantity_kg,
                                ekor_pack: this.initialStock.ekor_pack
                            };
                        }

                        this.transactionDateRange = `${this.formatDate(this.startDate)} - ${this.formatDate(this.endDate)}`;
                    } else {
                        this.transactionsErrorMessage = result.message || 'Gagal memuat transaksi.';
                        console.error('API Error (Transactions):', result.error);
                    }
                } catch (error) {
                    this.transactionsErrorMessage = 'Terjadi kesalahan saat memuat transaksi: ' + error.message;
                    console.error('Fetch Error (Transactions):', error);
                } finally {
                    this.transactionsLoading = false;
                }
            },

            async fetchComparisonTransactions() {
                if (!this.selectedComparisonItem) {
                    this.dailyComparisonTransactions = {};
                    this.comparisonTransactionsErrorMessage = '';
                    this.comparisonInitialStock = {
                        quantity_kg: null,
                        ekor_pack: null
                    };
                    this.comparisonCurrentStock = {
                        quantity_kg: null,
                        ekor_pack: null
                    };
                    this.comparisonTransactionDateRange = '';
                    return;
                }

                this.comparisonTransactionsLoading = true;
                this.comparisonTransactionsErrorMessage = '';
                this.dailyComparisonTransactions = {};
                this.comparisonInitialStock = {
                    quantity_kg: null,
                    ekor_pack: null
                };
                this.comparisonCurrentStock = {
                    quantity_kg: null,
                    ekor_pack: null
                };
                this.comparisonTransactionDateRange = '';

                let url = `${this.API_ENDPOINT}?action=get_transactions_with_initial_stock&item_name=${encodeURIComponent(this.selectedComparisonItem)}&start_date=${this.comparisonStartDate}&end_date=${this.comparisonEndDate}`;
                if (this.selectedComparisonStorage) {
                    url += `&storage=${encodeURIComponent(this.selectedComparisonStorage)}`;
                }
                if (this.selectedComparisonBarcode) {
                    url += `&barcode=${encodeURIComponent(this.selectedComparisonBarcode)}`;
                }

                try {
                    const response = await fetch(url);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const result = await response.json();

                    if (result.success) {
                        this.comparisonInitialStock = result.data.initial_stock;
                        this.dailyComparisonTransactions = this.processTransactions(result.data.transactions, this.comparisonInitialStock);

                        if (result.data.transactions.length > 0) {
                            this.comparisonCurrentStock = {
                                quantity_kg: result.data.transactions[0].running_stock_kg,
                                ekor_pack: result.data.transactions[0].running_stock_ekor
                            };
                        } else {
                            this.comparisonCurrentStock = {
                                quantity_kg: this.comparisonInitialStock.quantity_kg,
                                ekor_pack: this.comparisonInitialStock.ekor_pack
                            };
                        }
                        this.comparisonTransactionDateRange = `${this.formatDate(this.comparisonStartDate)} - ${this.formatDate(this.comparisonEndDate)}`;
                    } else {
                        this.comparisonTransactionsErrorMessage = result.message || 'Gagal memuat transaksi perbandingan.';
                        console.error('API Error (Comparison Transactions):', result.error);
                    }
                } catch (error) {
                    this.comparisonTransactionsErrorMessage = 'Terjadi kesalahan saat memuat transaksi perbandingan: ' + error.message;
                    console.error('Fetch Error (Comparison Transactions):', error);
                } finally {
                    this.comparisonTransactionsLoading = false;
                }
            },

            processTransactions(transactions) {
                const dailyGrouped = {};
                transactions.forEach(trans => {
                    const date = trans.date;
                    const created_at_time = trans.created_at.split(' ')[1];

                    if (!dailyGrouped[date]) {
                        dailyGrouped[date] = {
                            details: [],
                            total_quantity_kg: 0,
                            total_ekor_pack: 0,
                            end_of_day_stock_kg: null,
                            end_of_day_stock_ekor_pack: null
                        };
                    }

                    let dailyAdjustedQuantityKg = parseFloat(trans.quantity_kg);
                    let dailyAdjustedEkorPack = parseInt(trans.ekor_pack);

                    dailyGrouped[date].details.push({
                        ...trans,
                        transaction_time: created_at_time,
                        display_running_stock_kg: trans.running_stock_kg,
                        display_running_stock_ekor: trans.running_stock_ekor
                    });

                    dailyGrouped[date].total_quantity_kg += dailyAdjustedQuantityKg;
                    dailyGrouped[date].total_ekor_pack += dailyAdjustedEkorPack;

                    if (dailyGrouped[date].end_of_day_stock_kg === null) {
                        dailyGrouped[date].end_of_day_stock_kg = trans.running_stock_kg;
                        dailyGrouped[date].end_of_day_stock_ekor_pack = trans.running_stock_ekor;
                    }
                });

                const sortedDailyGroupedKeys = Object.keys(dailyGrouped).sort((a, b) => new Date(b) - new Date(a));
                const finalDailyGrouped = {};
                sortedDailyGroupedKeys.forEach(date => {
                    dailyGrouped[date].details.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                    finalDailyGrouped[date] = dailyGrouped[date];
                });

                return finalDailyGrouped;
            },

            calculateCurrentStock(dailyTransactions, initialStock) {
                const sortedDates = Object.keys(dailyTransactions).sort((a, b) => new Date(b) - new Date(a));

                if (sortedDates.length > 0) {
                    const latestDate = sortedDates[0];
                    return {
                        quantity_kg: dailyTransactions[latestDate].end_of_day_stock_kg,
                        ekor_pack: dailyTransactions[latestDate].end_of_day_stock_ekor_pack
                    };
                }
                return {
                    quantity_kg: initialStock.quantity_kg,
                    ekor_pack: initialStock.ekor_pack
                };
            },

            formatDate(dateString) {
                if (!dateString) return '';
                const options = {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                };
                return new Date(dateString).toLocaleDateString('id-ID', options);
            },

            formatTime(dateTimeString) {
                if (!dateTimeString) return '-';
                const timePart = dateTimeString.split(' ')[1];
                return timePart;
            },

            getTransactionRowClass(type) {
                if (type === 'in' || type === 'transfer-in' || type === 'ada') {
                    return 'bg-green-100';
                } else if (type === 'out' || type === 'transfer-out') {
                    return 'bg-red-100';
                } else if (type === 'adjustment') {
                    return 'bg-blue-100';
                }
                return '';
            },

            toggleTransactionDetail(date) {
                this.expandedTransactions[date] = !this.expandedTransactions[date];
            },

            toggleAllTransactions() {
                this.allExpanded = !this.allExpanded;
                for (const date in this.dailyTransactions) {
                    this.expandedTransactions[date] = this.allExpanded;
                }
            },

            toggleComparisonTransactionDetail(date) {
                this.expandedComparisonTransactions[date] = !this.expandedComparisonTransactions[date];
            },

            toggleAllComparisonTransactions() {
                this.allComparisonExpanded = !this.allComparisonExpanded;
                for (const date in this.dailyComparisonTransactions) {
                    this.expandedComparisonTransactions[date] = this.allComparisonExpanded;
                }
            },

            toggleComparisonPanel() {
                this.showComparisonPanel = !this.showComparisonPanel;
                if (!this.showComparisonPanel) {
                    this.selectedComparisonItem = '';
                    this.selectedComparisonStorage = '';
                    this.comparisonItemDetails = null;
                    this.dailyComparisonTransactions = {};
                    this.comparisonTransactionsLoading = false;
                    this.comparisonTransactionsErrorMessage = '';
                    this.expandedComparisonTransactions = {};
                    this.allComparisonExpanded = false;
                    this.comparisonInitialStock = {
                        quantity_kg: null,
                        ekor_pack: null
                    };
                    this.comparisonCurrentStock = {
                        quantity_kg: null,
                        ekor_pack: null
                    };
                    this.comparisonTransactionDateRange = '';

                    // Clear regular select for comparison item and storage fields
                    // Ini seharusnya akan direset melalui x-model, tapi bisa di-force juga
                    $('#comparisonItemSelect').val(null).trigger('change'); // Tetap Select2
                    document.getElementById('comparisonStorageSelect').value = ''; // Regular select
                }
            }
        }));
    });
</script>