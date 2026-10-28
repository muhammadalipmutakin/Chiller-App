<main class="flex-1 min-w-0 transition-all duration-300" x-data="chillerStockBarcodeApp()">
    <h1 class="text-xl font-bold mb-4 text-gray-800">Data Stok Barcode Chiller</h1>

    <div class="mb-3 text-right">
        <button @click="showFilters = !showFilters"
            class="inline-flex items-center px-3 py-1.5 bg-gray-200 text-gray-700 text-sm rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400">
            <i class="fas fa-filter mr-1.5"></i>
            <span x-text="showFilters ? 'Sembunyikan Filter' : 'Tampilkan Filter'"></span>
        </button>
    </div>

    <section x-show="showFilters" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="bg-white shadow-md rounded-lg p-3 mb-4 border border-gray-200">
        <h2 class="text-sm font-semibold mb-3 text-gray-700">Filter Data</h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
            <div>
                <label for="startDate" class="block text-xs font-medium text-gray-700 mb-1">Tanggal Awal</label>
                <input type="date" id="startDate" x-model="startDate"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm p-1.5">
            </div>
            <div>
                <label for="endDate" class="block text-xs font-medium text-gray-700 mb-1">Tanggal Akhir</label>
                <input type="date" id="endDate" x-model="endDate"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm p-1.5">
            </div>
            <div>
                <label for="storageFilter" class="block text-xs font-medium text-gray-700 mb-1">Lokasi Storage</label>
                <select id="storageFilter" x-model="selectedStorage"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm p-1.5">
                    <option value="Semua">Semua Lokasi</option>
                    <template x-for="location in filterOptions.locations" :key="location">
                        <option :value="location" x-text="location"></option>
                    </template>
                </select>
            </div>
            <div>
                <label for="barcodeSearch" class="block text-xs font-medium text-gray-700 mb-1">Cari Barcode</label>
                <input type="number" inputmode="numeric" pattern="[0-9]*" id="barcodeSearch" x-model="searchBarcode"
                    @input="applyFilters()" placeholder="Masukkan Barcode..."
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm p-1.5">
            </div>

            <div class="col-span-full grid grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="col-span-1">
                    <label for="itemFilter" class="block text-xs font-medium text-gray-700 mb-1">Filter Item</label>
                    <select id="itemFilter" multiple="multiple" x-ref="itemSelect"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm p-1.5">
                    </select>
                </div>
                <div class="col-span-1">
                    <label for="subitemFilter" class="block text-xs font-medium text-gray-700 mb-1">Filter Subitem</label>
                    <select id="subitemFilter" multiple="multiple" x-ref="subitemSelect"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm p-1.5">
                    </select>
                </div>
            </div>

            <div class="col-span-full grid grid-cols-3 gap-3 mt-1">
                <label for="filterPositiveStock" class="flex items-center text-xs font-medium text-gray-700">
                    <input type="checkbox" id="filterPositiveStock" x-model="filterStockType.positive"
                        @change="applyFilters()"
                        class="form-checkbox h-3.5 w-3.5 text-green-600 border-gray-300 rounded focus:ring-green-500 mr-1.5">
                    Stok Positif
                </label>
                <label for="filterZeroStock" class="flex items-center text-xs font-medium text-gray-700">
                    <input type="checkbox" id="filterZeroStock" x-model="filterStockType.zero" @change="applyFilters()"
                        class="form-checkbox h-3.5 w-3.5 text-yellow-600 border-gray-300 rounded focus:ring-yellow-500 mr-1.5">
                    Stok Nol
                </label>
                <label for="filterNegativeStock" class="flex items-center text-xs font-medium text-gray-700">
                    <input type="checkbox" id="filterNegativeStock" x-model="filterStockType.negative"
                        @change="applyFilters()"
                        class="form-checkbox h-3.5 w-3.5 text-red-600 border-gray-300 rounded focus:ring-red-500 mr-1.5">
                    Stok Negatif
                </label>
            </div>
        </div>
        <div class="flex justify-end mt-3">
            <button @click="resetFilters()"
                class="px-3 py-1.5 bg-gray-200 text-gray-700 text-sm rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400">
                Reset Filter
            </button>
        </div>
    </section>

    <div x-show="loading" class="text-center py-6">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 mx-auto"></div>
        <p class="text-gray-700 text-sm mt-3">Memuat data stok barcode...</p>
    </div>

    <!-- Desktop Table (hidden on mobile) -->
    <div x-show="!loading" class="hidden md:block">
        <section class="bg-white shadow-md rounded-lg mt-4">
            <div class="p-3 bg-gray-50 border-b flex justify-between items-center">
                <h2 class="text-sm font-semibold text-gray-800">Daftar Stok Barcode</h2>
                <div class="flex space-x-2 text-sm">
                    <button x-show="hasCheckedAdjustments" @click="confirmSaveAdjustments()"
                        class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Simpan Adjustment
                    </button>
                    <button @click="confirmResetAllAdjustments()"
                        class="px-3 py-1.5 bg-gray-500 text-white text-sm rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Reset Adjustment
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto relative">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 border-y border-gray-400">
                        <tr>
                            <th scope="col"
                                class="py-2.5 px-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider pl-4 w-10">
                                <input type="checkbox" @click="toggleAllCheckboxes($event)" :checked="allChecked"
                                    :indeterminate="indeterminate" class="form-checkbox h-3.5 w-3.5 text-blue-600 rounded">
                            </th>
                            <th scope="col"
                                class="py-2.5 px-1 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-10">
                                No
                            </th>
                            <th scope="col"
                                class="py-2.5 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID Transaksi
                            </th>
                            <th scope="col"
                                class="py-2.5 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tgl Masuk
                            </th>
                            <th scope="col"
                                class="py-2.5 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Lokasi
                            </th>
                            <th scope="col"
                                class="py-2.5 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Barcode
                            </th>
                            <th scope="col"
                                class="py-2.5 px-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Item
                            </th>
                            <th scope="col"
                                class="py-2.5 px-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ekor/Pack Awal
                            </th>
                            <th scope="col"
                                class="py-2.5 px-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Berat Awal (Kg)
                            </th>
                            <th scope="col"
                                class="py-2.5 px-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ekor/Pack Saat Ini
                            </th>
                            <th scope="col"
                                class="py-2.5 px-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider pr-4">
                                Berat Saat Ini (Kg)
                            </th>
                            <th scope="col"
                                class="py-2.5 px-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider pr-4">
                                Sisa (%)
                            </th>
                            <th scope="col"
                                class="py-2.5 px-1 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">
                                Adj. Ekor
                            </th>
                            <th scope="col"
                                class="py-2.5 px-1 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">
                                Adj. Berat
                            </th>
                            <th scope="col"
                                class="py-2.5 px-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider pr-4 w-32">
                                Keterangan
                            </th>
                        </tr>
                        <tr class="bg-gray-100 border-b border-gray-400">
                            <th colspan="7" class="py-2 px-2 text-right text-sm font-bold text-gray-700 uppercase pr-3">
                                Total:</th>
                            <th class="py-2 px-2 text-right text-sm font-bold text-gray-700" x-text="totals.initial_ep"></th>
                            <th class="py-2 px-2 text-right text-sm font-bold text-gray-700"
                                x-text="totals.initial_kg.toFixed(2)"></th>
                            <th class="py-2 px-2 text-right text-sm font-bold text-gray-700" x-text="totals.current_ep"></th>
                            <th class="py-2 px-2 text-right text-sm font-bold text-gray-700 pr-4"
                                x-text="totals.current_kg.toFixed(2)"></th>
                            <th colspan="3" class="py-2 px-2 text-right text-sm font-bold text-gray-700 pr-4"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-if="stockData.length === 0">
                            <tr>
                                <td colspan="15"
                                    class="whitespace-nowrap text-center text-sm text-gray-500 py-3 px-2">
                                    Tidak ada data stok barcode yang tersedia untuk filter yang dipilih.
                                </td>
                            </tr>
                        </template>
                        <template x-for="(item, index) in stockData" :key="item.id">
                            <tr class="hover:bg-gray-50" :class="{
                                    'bg-red-50 hover:bg-red-100': item.current_berat < 0,
                                    'bg-yellow-50 hover:bg-yellow-100': item.current_berat === 0 && item.ekor_pack !== 0,
                                }">
                                <td class="whitespace-nowrap text-center py-1 pl-4">
                                    <input type="checkbox" :id="'chk-' + item.id" x-model="item.adjustment.checked"
                                        @change="handleCheckboxChange(item)"
                                        class="form-checkbox h-3.5 w-3.5 text-blue-600 rounded">
                                </td>
                                <td
                                    class="whitespace-nowrap text-center text-xs text-blue-600 font-medium py-1 px-1 rounded-full">
                                    <button @click="goToTracing(item)" class="hover:underline focus:outline-none">
                                        <span class="text-xs" x-text="index + 1"></span>
                                    </button>
                                </td>
                                <td class="whitespace-nowrap text-xs font-medium text-gray-900 py-1 px-2" x-text="item.id"></td>
                                <td class="whitespace-nowrap text-xs text-gray-500 py-1 px-2" x-text="item.date"></td>
                                <td class="whitespace-nowrap text-xs text-gray-500 py-1 px-2" x-text="item.storage"></td>
                                <td class="whitespace-nowrap text-sm font-medium text-gray-900 py-1 px-2"
                                    x-text="item.barcode"></td>
                                <td class="whitespace-nowrap text-xs text-gray-500 py-1 px-2" x-text="item.item"></td>
                                <td class="whitespace-nowrap text-xs text-gray-500 text-right py-1 px-2"
                                    x-text="item.ekor_pack"></td>
                                <td class="whitespace-nowrap text-xs text-gray-500 text-right py-1 px-2"
                                    x-text="item.quantity_kg.toFixed(2)"></td>
                                <td class="whitespace-nowrap text-xs text-gray-900 font-semibold text-right py-1 px-2"
                                    x-text="item.current_ekor"></td>
                                <td class="whitespace-nowrap text-xs text-gray-900 font-semibold text-right py-1 px-2 pr-4"
                                    x-text="item.current_berat.toFixed(2)"></td>
                                <td class="whitespace-nowrap text-xs text-gray-900 font-semibold text-right py-1 px-2 pr-4"
                                    x-text="item.percent_susut.toFixed(2)"></td>
                                <td class="whitespace-nowrap text-center py-1 px-1">
                                    <input type="number" x-model="item.adjustment.ekor" @input="markAsChecked(item)"
                                        class="w-14 text-center border rounded p-0.5 text-xs" min="0" step="1">
                                </td>
                                <td class="whitespace-nowrap text-center py-1 px-1">
                                    <input type="number" x-model="item.adjustment.berat" @input="markAsChecked(item)"
                                        class="w-14 text-center border rounded p-0.5 text-xs" min="0" step="0.01">
                                </td>
                                <td class="whitespace-nowrap py-1 px-2 pr-4">
                                    <input type="text" x-model="item.adjustment.keterangan" @input="markAsChecked(item)"
                                        class="w-full border rounded p-0.5 text-xs" placeholder="Keterangan...">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <!-- Mobile Cards (hidden on desktop) -->
    <div x-show="!loading" class="block md:hidden" x-data="{ expandedCard: null }">
        <section class="bg-white shadow-md rounded-lg mt-4">
            <!-- Header Mobile -->
            <div class="p-3 bg-gray-50 border-b">
                <div class="flex justify-between items-center mb-2">
                    <h2 class="text-sm font-semibold text-gray-800">Daftar Stok Barcode</h2>
                    <div class="text-xs text-gray-500" x-text="'Total: ' + stockData.length + ' item'"></div>
                </div>
                <div class="flex space-x-2">
                    <button x-show="hasCheckedAdjustments" @click="confirmSaveAdjustments()"
                        class="flex-1 px-3 py-2 bg-blue-600 text-white text-xs rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-center space-x-1">
                        <i class="fas fa-save text-xs"></i>
                        <span>Simpan</span>
                    </button>
                    <button @click="confirmResetAllAdjustments()"
                        class="flex-1 px-3 py-2 bg-gray-500 text-white text-xs rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 flex items-center justify-center space-x-1">
                        <i class="fas fa-undo text-xs"></i>
                        <span>Reset</span>
                    </button>
                </div>
            </div>

            <!-- Mobile Cards Content -->
            <div class="p-3 space-y-3">
                <!-- Empty State Mobile -->
                <template x-if="stockData.length === 0">
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-box-open text-gray-400 text-xl"></i>
                        </div>
                        <p class="text-gray-600 font-medium">Tidak ada data stok barcode</p>
                        <p class="text-sm text-gray-500 mt-1">Coba ubah filter atau periode tanggal</p>
                    </div>
                </template>

                <!-- Mobile Cards -->
                <template x-for="(item, index) in stockData" :key="item.id">
                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden"
                        :class="{
                             'border-red-200 bg-red-50': item.current_berat < 0,
                             'border-yellow-200 bg-yellow-50': item.current_berat === 0 && item.ekor_pack !== 0
                         }">

                        <!-- Card Header -->
                        <div class="p-3 border-b border-gray-100">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex items-center space-x-2">
                                    <input type="checkbox" :id="'mobile-chk-' + item.id" x-model="item.adjustment.checked"
                                        @change="handleCheckboxChange(item)"
                                        class="form-checkbox h-4 w-4 text-blue-600 rounded border-gray-300">
                                    <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                        <span x-text="index + 1"></span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-bold"
                                        :class="{
                                             'text-green-600': item.current_berat > 0,
                                             'text-red-600': item.current_berat < 0,
                                             'text-yellow-600': item.current_berat === 0
                                         }"
                                        x-text="item.current_berat.toFixed(2) + ' Kg'">
                                    </div>
                                    <div class="text-xs text-gray-500" x-text="item.current_ekor + ' E/P'"></div>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500">Barcode:</span>
                                    <span class="font-medium" x-text="item.barcode"></span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500">Lokasi:</span>
                                    <span class="font-medium" x-text="item.storage"></span>
                                </div>
                                <div class="text-xs text-gray-600 truncate" x-text="item.item"></div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="px-3 py-2 bg-gray-50 border-b border-gray-100 flex space-x-2">
                            <button @click="expandedCard = expandedCard === item.id ? null : item.id"
                                class="flex-1 bg-white border border-gray-300 text-gray-700 text-xs font-medium py-1.5 rounded flex items-center justify-center space-x-1">
                                <i class="fas" :class="expandedCard === item.id ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                <span x-text="expandedCard === item.id ? 'Sembunyikan' : 'Detail'"></span>
                            </button>
                            <button @click="goToTracing(item)"
                                class="flex-1 bg-blue-100 text-blue-700 text-xs font-medium py-1.5 rounded flex items-center justify-center space-x-1">
                                <i class="fas fa-search"></i>
                                <span>Tracing</span>
                            </button>
                        </div>

                        <!-- Expanded Content -->
                        <div x-show="expandedCard === item.id" x-collapse class="border-t border-gray-100">
                            <div class="p-3 space-y-3">
                                <!-- Basic Information -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="text-center bg-gray-50 rounded p-2">
                                        <p class="text-xs text-gray-500">ID Transaksi</p>
                                        <p class="text-sm font-medium" x-text="item.id"></p>
                                    </div>
                                    <div class="text-center bg-gray-50 rounded p-2">
                                        <p class="text-xs text-gray-500">Tanggal Masuk</p>
                                        <p class="text-sm font-medium" x-text="item.date"></p>
                                    </div>
                                </div>

                                <!-- Stock Information -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="bg-blue-50 rounded p-2">
                                        <p class="text-xs font-semibold text-blue-700 mb-1">Awal</p>
                                        <div class="space-y-1">
                                            <div class="flex justify-between text-xs">
                                                <span class="text-blue-600">E/P:</span>
                                                <span class="font-medium" x-text="item.ekor_pack"></span>
                                            </div>
                                            <div class="flex justify-between text-xs">
                                                <span class="text-blue-600">Kg:</span>
                                                <span class="font-medium" x-text="item.quantity_kg.toFixed(2)"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-purple-50 rounded p-2">
                                        <p class="text-xs font-semibold text-purple-700 mb-1">Saat Ini</p>
                                        <div class="space-y-1">
                                            <div class="flex justify-between text-xs">
                                                <span class="text-purple-600">E/P:</span>
                                                <span class="font-medium" x-text="item.current_ekor"></span>
                                            </div>
                                            <div class="flex justify-between text-xs">
                                                <span class="text-purple-600">Kg:</span>
                                                <span class="font-medium" x-text="item.current_berat.toFixed(2)"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Adjustment Inputs -->
                                <div class="bg-yellow-50 rounded p-3 border border-yellow-200">
                                    <p class="text-xs font-semibold text-yellow-700 mb-2">Adjustment</p>
                                    <div class="space-y-2">
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="text-xs text-yellow-600 block mb-1">Adj. Ekor</label>
                                                <input type="number" x-model="item.adjustment.ekor" @input="markAsChecked(item)"
                                                    class="w-full text-center border border-yellow-300 rounded p-1 text-xs"
                                                    min="0" step="1" placeholder="0">
                                            </div>
                                            <div>
                                                <label class="text-xs text-yellow-600 block mb-1">Adj. Berat</label>
                                                <input type="number" x-model="item.adjustment.berat" @input="markAsChecked(item)"
                                                    class="w-full text-center border border-yellow-300 rounded p-1 text-xs"
                                                    min="0" step="0.01" placeholder="0.00">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="text-xs text-yellow-600 block mb-1">Keterangan</label>
                                            <input type="text" x-model="item.adjustment.keterangan" @input="markAsChecked(item)"
                                                class="w-full border border-yellow-300 rounded p-1 text-xs"
                                                placeholder="Masukkan keterangan...">
                                        </div>
                                    </div>
                                </div>

                                <!-- Additional Info -->
                                <div class="bg-gray-50 rounded p-2">
                                    <div class="flex justify-between text-xs">
                                        <span class="text-gray-600">Sisa (%):</span>
                                        <span class="font-medium" x-text="item.percent_susut.toFixed(2)"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Mobile Totals -->
            <div class="p-3 bg-gray-50 border-t border-gray-200">
                <h3 class="text-sm font-semibold text-gray-800 mb-2 text-center">Ringkasan Total</h3>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-white rounded p-2 text-center border border-gray-200">
                        <p class="text-xs text-gray-500">Total E/P Awal</p>
                        <p class="text-sm font-bold" x-text="totals.initial_ep"></p>
                    </div>
                    <div class="bg-white rounded p-2 text-center border border-gray-200">
                        <p class="text-xs text-gray-500">Total Kg Awal</p>
                        <p class="text-sm font-bold" x-text="totals.initial_kg.toFixed(2)"></p>
                    </div>
                    <div class="bg-white rounded p-2 text-center border border-gray-200">
                        <p class="text-xs text-gray-500">Total E/P Saat Ini</p>
                        <p class="text-sm font-bold" x-text="totals.current_ep"></p>
                    </div>
                    <div class="bg-white rounded p-2 text-center border border-gray-200">
                        <p class="text-xs text-gray-500">Total Kg Saat Ini</p>
                        <p class="text-sm font-bold" x-text="totals.current_kg.toFixed(2)"></p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<script>
    function chillerStockBarcodeApp() {
        return {
            startDate: '',
            endDate: '',
            selectedStorage: 'Semua',
            selectedItems: [],
            selectedSubitems: [],
            searchBarcode: '',
            showFilters: false,
            filterStockType: {
                positive: true,
                zero: true,
                negative: true
            },
            filterOptions: {
                locations: ['Chiller 1', 'Chiller 2', 'Chiller 3'],
                items: [],
                subitems: []
            },
            allStockData: [],
            stockData: [],
            loading: true,
            totals: {
                initial_ep: 0,
                initial_kg: 0,
                current_ep: 0,
                current_kg: 0,
            },

            init() {
                this.setDefaultDates();

                this.fetchFullStockBarcodeData().then(() => {
                    this.initSelect2();
                    this.applyFilters();
                }).finally(() => {
                    this.loading = false;
                });

                this.$watch('startDate', () => this.handleDateChange());
                this.$watch('endDate', () => this.handleDateChange());
                this.$watch('selectedStorage', () => this.applyFilters());
                this.$watch('filterStockType.positive', () => this.applyFilters());
                this.$watch('filterStockType.zero', () => this.applyFilters());
                this.$watch('filterStockType.negative', () => this.applyFilters());

                $('#itemFilter').on('change', (e) => {
                    this.selectedItems = $(e.target).val() || [];
                    this.applyFilters();
                });

                $('#subitemFilter').on('change', (e) => {
                    this.selectedSubitems = $(e.target).val() || [];
                    this.applyFilters();
                });

                this.$watch('stockData', () => this.calculateTotals());
            },

            setDefaultDates() {
                const today = new Date();
                this.endDate = today.toISOString().slice(0, 10);
                this.startDate = new Date(Date.now() - 86400000).toISOString().slice(0, 10);
            },

            async fetchFullStockBarcodeData() {
                this.loading = true;
                try {
                    const params = new URLSearchParams({
                        action: 'get_full_stock_barcode_data',
                        startDate: this.startDate,
                        endDate: this.endDate,
                    });

                    const response = await fetch(`./controller/stock_barcode/index.php?${params.toString()}`);
                    const result = await response.json();

                    if (result.status === 'success') {
                        // Tambahkan struktur adjustment ke setiap item
                        this.allStockData = result.data.stock_data.map(item => ({
                            ...item,
                            adjustment: {
                                checked: false,
                                ekor: '',
                                berat: '',
                                keterangan: ''
                            }
                        }));
                        console.log('Fetched Data:', this.allStockData);

                        this.filterOptions.items = result.data.items_for_filter;
                        this.filterOptions.subitems = [{
                            id: '__NO_SUBITEM__',
                            text: 'Tanpa Subitem'
                        }, ...result.data.subitems_for_filter.map(subitem => ({
                            id: subitem,
                            text: subitem
                        }))];
                    } else {
                        Swal.fire('Error', 'Gagal memuat data awal: ' + result.message, 'error');
                        this.allStockData = [];
                        this.filterOptions.items = [];
                        this.filterOptions.subitems = [];
                    }
                } catch (error) {
                    Swal.fire('Error', 'Terjadi kesalahan saat memuat data awal: ' + error.message, 'error');
                    this.allStockData = [];
                    this.filterOptions.items = [];
                    this.filterOptions.subitems = [];
                } finally {
                    this.loading = false;
                }
            },

            applyFilters() {
                console.log('Applying filters...');
                let filteredData = [...this.allStockData];

                // Filter berdasarkan Tanggal
                const start = new Date(this.startDate);
                const end = new Date(this.endDate);
                end.setHours(23, 59, 59, 999);
                filteredData = filteredData.filter(item => {
                    const itemDate = new Date(item.date);
                    return itemDate >= start && itemDate <= end;
                });

                // Filter berdasarkan Lokasi Storage
                if (this.selectedStorage && this.selectedStorage !== 'Semua') {
                    filteredData = filteredData.filter(item => item.storage === this.selectedStorage);
                }

                // Filter berdasarkan Item
                if (this.selectedItems.length > 0) {
                    filteredData = filteredData.filter(item => {
                        return this.selectedItems.includes(item.item);
                    });
                }

                // Filter berdasarkan Subitem
                if (this.selectedSubitems.length > 0) {
                    const noSubitemSelected = this.selectedSubitems.includes('__NO_SUBITEM__');
                    filteredData = filteredData.filter(item => {
                        const subitemPart = item.item.includes(' : ') ? item.item.split(' : ')[1].trim() : '';
                        if (noSubitemSelected && subitemPart === '') {
                            return true;
                        }
                        return this.selectedSubitems.includes(subitemPart);
                    });
                }

                // Filter berdasarkan Barcode
                if (this.searchBarcode.trim() !== '') {
                    const searchTerm = this.searchBarcode.toLowerCase();
                    filteredData = filteredData.filter(item => {
                        return item.barcode && item.barcode.toString().toLowerCase() === searchTerm;
                    });
                }

                // Filter berdasarkan Tipe Stok
                const {
                    positive,
                    zero,
                    negative
                } = this.filterStockType;
                if (!positive && !zero && !negative) {
                    filteredData = [];
                } else {
                    filteredData = filteredData.filter(item => {
                        const berat = parseFloat(item.current_berat);
                        if (berat > 0 && positive) return true;
                        if (berat === 0 && zero) return true;
                        if (berat < 0 && negative) return true;
                        return false;
                    });
                }

                this.stockData = filteredData;
            },

            async handleDateChange() {
                if (this.startDate && this.endDate && new Date(this.startDate) > new Date(this.endDate)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Tanggal akhir tidak boleh lebih kecil dari tanggal awal!',
                    });
                    this.allStockData = [];
                    this.stockData = [];
                    this.loading = false;
                    return;
                }
                await this.fetchFullStockBarcodeData();
                this.initSelect2();
                this.applyFilters();
            },

            initSelect2() {
                if ($('#itemFilter').data('select2')) {
                    $('#itemFilter').select2('destroy');
                }
                if ($('#subitemFilter').data('select2')) {
                    $('#subitemFilter').select2('destroy');
                }

                $('#itemFilter').select2({
                    placeholder: 'Pilih Item',
                    data: this.filterOptions.items.map(item => ({
                        id: item,
                        text: item
                    })),
                    maximumSelectionLength: 10,
                    tags: false,
                    width: '100%',
                });

                $('#subitemFilter').select2({
                    placeholder: 'Pilih Subitem',
                    data: this.filterOptions.subitems,
                    maximumSelectionLength: 10,
                    tags: false,
                    width: '100%',
                });

                $('#itemFilter').val(this.selectedItems).trigger('change');
                $('#subitemFilter').val(this.selectedSubitems).trigger('change');
            },

            calculateTotals() {
                this.totals = {
                    initial_ep: 0,
                    initial_kg: 0,
                    current_ep: 0,
                    current_kg: 0,
                };

                this.stockData.forEach(item => {
                    this.totals.initial_ep += parseFloat(item.ekor_pack) || 0;
                    this.totals.initial_kg += parseFloat(item.quantity_kg) || 0;
                    this.totals.current_ep += parseFloat(item.current_ekor) || 0;
                    this.totals.current_kg += parseFloat(item.current_berat) || 0;
                });
            },

            resetFilters() {
                this.setDefaultDates();
                this.selectedStorage = 'Semua';
                $('#itemFilter').val(null).trigger('change');
                $('#subitemFilter').val(null).trigger('change');
                this.selectedItems = [];
                this.selectedSubitems = [];
                this.searchBarcode = '';
                this.filterStockType = {
                    positive: true,
                    zero: true,
                    negative: true
                };

                this.fetchFullStockBarcodeData().then(() => {
                    this.applyFilters();
                });
            },

            // === Adjustment Logic ===
            goToTracing(item) {
                const url = `?page=tracing&item=${encodeURIComponent(item.item)}&storage=${encodeURIComponent(item.storage)}&barcode=${encodeURIComponent(item.barcode)}`;

                // Gunakan window.open() untuk membuka URL di tab baru
                window.open(url, '_blank');
            },

            markAsChecked(item) {
                if (item.adjustment.ekor !== '' || item.adjustment.berat !== '' || item.adjustment.keterangan !== '') {
                    item.adjustment.checked = true;
                }
            },

            handleCheckboxChange(item) {
                if (!item.adjustment.checked) {
                    item.adjustment.ekor = '';
                    item.adjustment.berat = '';
                    item.adjustment.keterangan = '';
                }
            },

            get hasCheckedAdjustments() {
                return this.stockData.some(item => item.adjustment.checked);
            },

            get allChecked() {
                return this.stockData.length > 0 && this.stockData.every(item => item.adjustment.checked);
            },

            get indeterminate() {
                const checkedCount = this.stockData.filter(item => item.adjustment.checked).length;
                return checkedCount > 0 && checkedCount < this.stockData.length;
            },

            toggleAllCheckboxes(event) {
                const checked = event.target.checked;
                this.stockData.forEach(item => {
                    item.adjustment.checked = checked;
                    if (!checked) {
                        item.adjustment.ekor = '';
                        item.adjustment.berat = '';
                        item.adjustment.keterangan = '';
                    }
                });
            },

            // Konfirmasi untuk Reset Adjustment
            confirmResetAllAdjustments() {
                if (!this.stockData.some(item => item.adjustment.checked || item.adjustment.ekor !== '' || item.adjustment.berat !== '' || item.adjustment.keterangan !== '')) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Tidak Ada Data',
                        text: 'Tidak ada adjustment yang perlu direset.',
                    });
                    return;
                }

                Swal.fire({
                    title: 'Konfirmasi Reset',
                    text: "Apakah Anda yakin ingin menghapus semua data adjustment di halaman ini?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Reset Semua!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.resetAllAdjustments();
                        Swal.fire(
                            'Direset!',
                            'Semua data adjustment telah dihapus.',
                            'success'
                        )
                    }
                })
            },

            // Logika reset adjustment yang sebenarnya
            resetAllAdjustments() {
                this.stockData.forEach(item => {
                    item.adjustment.checked = false;
                    item.adjustment.ekor = '';
                    item.adjustment.berat = '';
                    item.adjustment.keterangan = '';
                });
            },

            // Konfirmasi untuk Simpan Adjustment
            confirmSaveAdjustments() {
                const adjustmentsToSave = this.stockData.filter(item => item.adjustment.checked);

                if (adjustmentsToSave.length === 0) {
                    Swal.fire('Peringatan', 'Tidak ada baris yang dicentang untuk disimpan.', 'warning');
                    return;
                }

                const invalidItems = adjustmentsToSave.filter(item =>
                    !item.adjustment.keterangan || item.adjustment.keterangan.trim() === ''
                );

                if (invalidItems.length > 0) {
                    Swal.fire('Peringatan', 'Semua baris yang dicentang harus memiliki keterangan!', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Konfirmasi Simpan',
                    text: `Anda akan menyimpan ${adjustmentsToSave.length} data adjustment. Lanjutkan?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Simpan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.saveAdjustments();
                    }
                });
            },

            // Logika simpan adjustment yang sebenarnya
            async saveAdjustments() {
                const adjustments = this.stockData
                    .filter(item => item.adjustment.checked)
                    .map(item => ({
                        id_in: item.id,
                        storage: item.storage,
                        item: item.item,
                        barcode: item.barcode,
                        adj_ekor: item.adjustment.ekor !== '' ? parseFloat(item.adjustment.ekor) : 0,
                        adj_berat: item.adjustment.berat !== '' ? parseFloat(item.adjustment.berat) : 0,
                        keterangan: item.adjustment.keterangan.trim()
                    }));

                if (adjustments.length === 0) return; // Should be handled by confirmSaveAdjustments

                this.loading = true;
                try {
                    const response = await fetch('./controller/stock_barcode/index.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'save_stock_adjustments',
                            adjustments: adjustments
                        })
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        Swal.fire('Berhasil!', 'Adjustment stok berhasil disimpan.', 'success');
                        // Reset hanya adjustment yang disimpan
                        this.stockData.forEach(item => {
                            if (item.adjustment.checked) {
                                item.adjustment.checked = false;
                                item.adjustment.ekor = '';
                                item.adjustment.berat = '';
                                item.adjustment.keterangan = '';
                            }
                        });
                        // Fetch ulang data untuk mendapatkan stok terbaru setelah adjustment
                        await this.fetchFullStockBarcodeData();
                        this.applyFilters();
                    } else {
                        Swal.fire('Gagal', 'Gagal menyimpan adjustment: ' + (result.message || 'Terjadi kesalahan.'), 'error');
                    }
                } catch (error) {
                    Swal.fire('Error', 'Terjadi kesalahan jaringan: ' + error.message, 'error');
                } finally {
                    this.loading = false;
                }
            }
        }
    }
</script>