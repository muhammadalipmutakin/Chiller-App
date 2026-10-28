<div class="grid grid-cols-3 gap-2 sm:gap-4 mb-4">
    <div class="bg-emerald-100 border-l-4 border-emerald-500 text-emerald-800 p-2 sm:p-3 rounded-lg shadow-md flex items-center justify-between">
        <div>
            <div class="text-xs font-medium uppercase text-emerald-700">IN NO Barcode</div>
            <p class="text-sm sm:text-lg font-bold text-black" x-text="no_barcode_in_count || '0'"></p>
        </div>
    </div>

    <div class="bg-rose-100 border-l-4 border-rose-500 text-rose-800 p-2 sm:p-3 rounded-lg shadow-md flex items-center justify-between">
        <div>
            <div class="text-xs font-medium uppercase text-rose-700">OUT NO Barcode</div>
            <p class="text-sm sm:text-lg font-bold" x-text="no_barcode_out_count || '0'"></p>
        </div>
    </div>

    <div class="bg-sky-100 border-l-4 border-sky-500 text-sky-800 p-2 sm:p-3 rounded-lg shadow-md flex items-center justify-between">
        <div>
            <div class="text-xs font-medium uppercase text-sky-700">Total</div>
            <p class="text-sm sm:text-lg font-bold" x-text="no_barcode_total_count || '0'"></p>
        </div>
    </div>
</div>

<!-- Desktop Filter Section -->
<div class="hidden md:flex flex-col sm:flex-row items-center justify-between p-3 sm:p-4 bg-gray-100 rounded-lg shadow-sm space-y-2 sm:space-y-0 sm:space-x-4 mb-4">
    <div class="flex items-center">
        <input type="checkbox" id="showHiddenColumns" x-model="showHiddenColumns" @change="toggleHiddenColumns" class="mr-2 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer">
        <label for="showHiddenColumns" class="text-xs sm:text-sm font-medium text-gray-700 select-none">Tampilkan kolom lainnya</label>
    </div>

    <div class="flex flex-col w-full sm:w-auto">
        <label for="filterDateStart" class="text-xs font-medium text-gray-700 mb-0.5">Tanggal Awal</label>
        <input type="date" x-model="filters.tanggal_awal" @change="fetchTransactions" class="p-1.5 border border-gray-300 rounded-md text-gray-700 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150 ease-in-out">
    </div>

    <div class="flex flex-col w-full sm:w-auto">
        <label for="filterDateEnd" class="text-xs font-medium text-gray-700 mb-0.5">Tanggal Akhir</label>
        <input type="date" x-model="filters.tanggal_akhir" @change="fetchTransactions" class="p-1.5 border border-gray-300 rounded-md text-gray-700 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150 ease-in-out">
    </div>

    <div class="w-full sm:w-auto">
        <label for="filterLimit" class="block text-xs font-medium text-gray-700 mb-0.5">Limit:</label>
        <select id="filterLimit" x-model="filters.limit" @change="fetchTransactions"
            class="w-full p-1.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150 ease-in-out text-xs">
            <option value="5">5</option>
            <option value="500">500</option>
            <option value="1000">1000</option>
            <option value="2000">2000</option>
            <option value="5000">5000</option>
            <option value="10000">10000</option>
        </select>
    </div>

    <div class="w-full sm:w-auto">
        <label for="filterType" class="block text-xs font-medium text-gray-700 mb-0.5">Type Transaksi:</label>
        <select x-model="filters.type_transaksi" @change="fetchTransactions"
            class="w-full p-1.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150 ease-in-out text-xs">
            <option value="">-Semua-</option>
            <option value="in">Masuk (In)</option>
            <option value="out">Keluar (Out)</option>
            <option value="transfer">Transfer</option>
            <option value="adjustment">Adjustment</option>
            <option value="adjustment-new">Adjustment-New</option>
        </select>
    </div>

    <div class="w-full sm:w-auto">
        <label for="filterUser" class="block text-xs font-medium text-gray-700 mb-0.5">User:</label>
        <select id="filterUser" x-model="filters.user_name" @change="fetchTransactions"
            class="w-full p-1.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150 ease-in-out text-xs">
            <option value="">-Semua-</option>
            <template x-for="user in filterOptions.users" :key="user.nama">
                <option :value="user.nama" x-text="user.nama"></option>
            </template>
        </select>
    </div>

    <div class="w-full sm:w-auto">
        <label for="filterBarcode" class="block text-xs font-medium text-gray-700 mb-0.5">Barcode:</label>
        <input
            type="text"
            inputmode="numeric"
            pattern="[0-9]*"
            id="filterBarcode"
            x-model="filters.barcode"
            x-on:keydown.enter="fetchTransactions()"
            placeholder="Cari Barcode..."
            class="w-full p-1.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs">
    </div>

    <div class="flex items-end w-full sm:w-auto pt-2 sm:pt-0">
        <button @click="resetFilters()" class="px-3 py-1.5 text-xs sm:text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-300 ease-in-out w-full">
            Reset Filter
        </button>
    </div>
</div>

<!-- Mobile Filter Section -->
<div class="hidden md:hidden mb-4 space-y-3">
    <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-800">Filter Data</h3>
            <button @click="showMobileFilters = !showMobileFilters" class="text-gray-500 hover:text-gray-700">
                <i class="fas" :class="showMobileFilters ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </button>
        </div>

        <div x-show="showMobileFilters" x-collapse class="space-y-3">
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-xs font-medium text-gray-700 mb-1">Tanggal Awal</label>
                    <input type="date" x-model="filters.tanggal_awal" @change="fetchTransactions"
                        class="w-full p-1.5 border border-gray-300 rounded text-xs">
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-700 mb-1">Tanggal Akhir</label>
                    <input type="date" x-model="filters.tanggal_akhir" @change="fetchTransactions"
                        class="w-full p-1.5 border border-gray-300 rounded text-xs">
                </div>
            </div>

            <div>
                <label class="text-xs font-medium text-gray-700 mb-1">Tipe Transaksi</label>
                <select x-model="filters.type_transaksi" @change="fetchTransactions"
                    class="w-full p-1.5 border border-gray-300 rounded text-xs">
                    <option value="">Semua Tipe</option>
                    <option value="in">Masuk (In)</option>
                    <option value="out">Keluar (Out)</option>
                    <option value="transfer">Transfer</option>
                    <option value="adjustment">Adjustment</option>
                </select>
            </div>

            <div>
                <label class="text-xs font-medium text-gray-700 mb-1">Cari Barcode</label>
                <input type="text" x-model="filters.barcode" @keydown.enter="fetchTransactions()"
                    placeholder="Masukkan barcode..."
                    class="w-full p-1.5 border border-gray-300 rounded text-xs">
            </div>

            <button @click="resetFilters()" class="w-full py-2 bg-gray-100 text-gray-700 text-xs font-medium rounded border border-gray-300 hover:bg-gray-200">
                Reset Filter
            </button>
        </div>
    </div>
</div>

<div class="flex items-end mt-2 mb-2">
    <button @click="isDownloadModalOpen = true" class="px-3 py-1.5 text-xs sm:text-sm font-semibold text-white bg-green-600 border border-transparent rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-300 ease-in-out">
        Unduh Excel
    </button>
</div>

<!-- Table for All Screen Sizes -->
<div class="overflow-x-auto rounded-lg border border-gray-200" style="max-height: calc(100vh - 235px);">
    <table class="min-w-full border-collapse">
        <thead>
            <?php include 'header-tabel.php' ?>
        </thead>
        <tbody>
            <template x-if="loading">
                <tr>
                    <td colspan="16" class="text-center py-4 text-sm font-semibold text-gray-500">
                        Memuat data...
                    </td>
                </tr>
            </template>
            <template x-if="!loading && visibleTransactions.length === 0 && !errorMessage">
                <tr>
                    <td colspan="16" class="text-center py-4 text-sm text-gray-500">
                        Data tidak ditemukan.
                    </td>
                </tr>
            </template>
            <template x-if="!loading && errorMessage">
                <tr>
                    <td colspan="16" class="text-center py-4 text-sm text-red-500">
                        Terjadi kesalahan: <span x-text="errorMessage"></span>
                    </td>
                </tr>
            </template>
            <template x-for="(transaction, index) in visibleTransactions" :key="transaction.id">
                <tr :class="{
                    'bg-amber-200 hover:bg-amber-300': transaction.transaction_type === 'transfer-in' || transaction.transaction_type === 'transfer-out',
                    'bg-emerald-200 hover:bg-emerald-300': transaction.transaction_type === 'in',
                    'bg-rose-200 hover:bg-rose-300': transaction.transaction_type === 'out',
                    'bg-purple-200 hover:bg-purple-300': transaction.transaction_type === 'adjustment'
                }">
                    <td class="px-2 py-1 text-center whitespace-nowrap text-gray-800 border-b border-gray-200 cursor-pointer text-xs bg-blue-200 rounded-full" @click="openModal(transaction.id)" x-text="index + 1"></td>

                    <td class="px-2 py-1 text-center whitespace-nowrap text-gray-800 border-b border-gray-200 text-xs" :class="{'bg-yellow-400': !transaction.barcode || transaction.barcode === null || transaction.barcode === ''}" x-text="transaction.barcode || '-'"></td>

                    <!-- Kolom Baru: Info Produksi -->
                    <td class="px-2 py-1 text-center whitespace-nowrap text-gray-800 border-b border-gray-200 text-xs">
                        <template x-if="transaction.info_produksi && transaction.info_produksi !== '-'">
                            <div class="flex flex-col">
                                <span class="font-semibold" x-text="transaction.info_produksi.split(' (')[0]"></span>
                                <span class="text-xs" :class="{
                                    'text-emerald-600': transaction.lama_penyimpanan_hari === 0,
                                    'text-blue-600': transaction.lama_penyimpanan_hari === 1,
                                    'text-amber-600': transaction.lama_penyimpanan_hari > 1 && transaction.lama_penyimpanan_hari <= 3,
                                    'text-orange-600': transaction.lama_penyimpanan_hari > 3 && transaction.lama_penyimpanan_hari <= 7,
                                    'text-rose-600': transaction.lama_penyimpanan_hari > 7
                                }" x-text="transaction.info_produksi.split('(')[1]?.replace(')', '') || ''"></span>
                            </div>
                        </template>
                        <template x-if="!transaction.info_produksi || transaction.info_produksi === '-'">
                            <span class="text-gray-400">-</span>
                        </template>
                    </td>

                    <td class="px-2 py-1 text-center whitespace-nowrap text-gray-800 border-b border-gray-200 text-xs" x-text="transaction.date"></td>
                    <td class="px-2 py-1 whitespace-nowrap text-gray-800 border-b border-gray-200 text-xs" x-text="transaction.item"></td>
                    <td class="px-2 py-1 whitespace-nowrap border-b border-gray-200 text-sm text-right font-bold" :class="{
                        'text-emerald-700': transaction.transaction_type === 'in',
                        'text-rose-700': transaction.transaction_type === 'out',
                        'text-amber-700': transaction.transaction_type === 'transfer-in' || transaction.transaction_type === 'transfer-out',
                        'text-purple-700': transaction.transaction_type === 'adjustment'
                    }" x-text="formatNumber(transaction.ekor_pack)"></td>
                    <td class="px-2 py-1 whitespace-nowrap border-b border-gray-200 text-sm text-right font-bold" :class="{
                        'text-emerald-700': transaction.transaction_type === 'in',
                        'text-rose-700': transaction.transaction_type === 'out',
                        'text-amber-700': transaction.transaction_type === 'transfer-in' || transaction.transaction_type === 'transfer-out',
                        'text-purple-700': transaction.transaction_type === 'adjustment'
                    }" x-text="formatNumber(transaction.quantity_kg, 2)"></td>

                    <td class="px-2 py-1 whitespace-nowrap border-b border-gray-200 text-xs sm:text-sm text-right font-bold" :class="{
                        'bg-yellow-300 text-yellow-900 border-yellow-500': transaction.stock_ekor < 0,
                        'text-black': transaction.stock_ekor >= 0
                    }" x-text="formatNumber(transaction.stock_ekor)"></td>

                    <td class="px-2 py-1 whitespace-nowrap border-b border-gray-200 text-xs sm:text-sm text-right font-bold" :class="{
                        'bg-yellow-300 text-yellow-900 border-yellow-500': transaction.stock_berat < 0,
                        'text-black': transaction.stock_berat >= 0
                    }" x-text="formatNumber(transaction.stock_berat, 2)"></td>

                    <td class="px-2 py-1 text-center whitespace-nowrap text-gray-800 border-b border-gray-200 text-xs" x-text="transaction.from_location"></td>
                    <td class="px-2 py-1 text-center whitespace-nowrap text-gray-800 border-b border-gray-200 text-xs" x-text="transaction.to_location"></td>
                    <td class="px-2 py-1 text-center whitespace-nowrap text-gray-800 border-b border-gray-200 text-xs" x-text="transaction.storage"></td>
                    <td class="px-2 py-1 text-center whitespace-nowrap text-gray-800 border-b border-gray-200 hidden-column text-xs" style="display: none;">
                        <span :class="{
                            'bg-emerald-200 text-emerald-800': transaction.transaction_type === 'in',
                            'bg-rose-200 text-rose-800': transaction.transaction_type === 'out',
                            'bg-amber-200 text-amber-800': transaction.transaction_type === 'transfer-in' || transaction.transaction_type === 'transfer-out',
                            'bg-purple-200 text-purple-800': transaction.transaction_type === 'adjustment'
                        }" class="px-2 py-0.5 rounded-full text-xs font-medium" x-text="formatTransactionType(transaction.transaction_type)"></span>
                    </td>
                    <td class="px-1 py-1 text-center text-gray-800 border-b border-gray-200 text-xs" x-text="transaction.keterangan || '-'"></td>
                    <td class="px-2 py-1 text-center text-gray-800 border-b border-gray-200 hidden-column text-xs" style="display: none;" x-text="transaction.user"></td>
                    <td class="px-2 py-1 text-center whitespace-nowrap text-gray-800 border-b border-gray-200 hidden-column text-xs" style="display: none;" x-text="formatDateTime(transaction.created_at)"></td>
                </tr>
            </template>
        </tbody>
    </table>
</div>

<!-- Download Modal -->
<div x-show="isDownloadModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4 py-4 sm:py-8">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true" @click="isDownloadModalOpen = false">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <div x-show="isDownloadModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all max-w-sm sm:max-w-lg w-full p-4 sm:p-6">
            <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                <h3 class="text-sm sm:text-lg font-semibold text-gray-900">Unduh Data Transaksi</h3>
                <button @click="isDownloadModalOpen = false" class="text-gray-400 hover:text-gray-600 transition duration-150 p-1">
                    <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mt-3 sm:mt-4">
                <p class="text-xs text-gray-500 mb-3">Pilih rentang tanggal untuk data yang ingin diunduh.</p>
                <div class="space-y-3">
                    <div>
                        <label for="downloadDateStart" class="block text-xs font-medium text-gray-700 mb-0.5">Tanggal Awal</label>
                        <input type="date" id="downloadDateStart" x-model="downloadFilters.tanggal_awal" class="w-full p-1.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150 ease-in-out text-xs">
                    </div>
                    <div>
                        <label for="downloadDateEnd" class="block text-xs font-medium text-gray-700 mb-0.5">Tanggal Akhir</label>
                        <input type="date" id="downloadDateEnd" x-model="downloadFilters.tanggal_akhir" class="w-full p-1.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150 ease-in-out text-xs">
                    </div>
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <button @click="isDownloadModalOpen = false" class="px-3 py-1.5 text-xs sm:text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 transition duration-150 mr-2">
                    Batal
                </button>
                <button @click="downloadExcel()" class="px-3 py-1.5 text-xs sm:text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 transition duration-150">
                    Unduh Data (Excel)
                </button>
            </div>
        </div>
    </div>
</div>