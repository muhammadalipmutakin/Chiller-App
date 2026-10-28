<script src="../../assets/js/xlsx.full.min.js"></script>

<main class="flex-1 min-w-0 transition-all duration-300" x-data="adjustmentApp()" x-init="init()" x-cloak>
    <div class="bg-white rounded-lg shadow-md p-4 mb-4">
        <h1 class="text-sm sm:text-lg font-bold text-gray-800 mb-1">Adjustment Stok</h1>
        <p class="text-xs text-gray-600">
            Lakukan penyesuaian stok dengan mengunggah file CSV. Pastikan format file sesuai dengan template.
        </p>
    </div>

    <div class="bg-white rounded-lg shadow-md p-4 mb-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div class="mb-3 sm:mb-0">
                <h2 class="text-sm font-semibold text-gray-800 mb-1">
                    <i class="fas fa-file-csv text-blue-500 mr-2"></i>Template CSV
                </h2>
                <p class="text-xs text-gray-600">
                    Unduh template untuk memastikan format file yang benar.
                </p>
            </div>
            <a href="./controller/adjustment/format.php"
                class="inline-flex items-center px-3 py-2 text-xs bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-200">
                <i class="fas fa-download mr-2"></i> Unduh Template
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-4 mb-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div class="mb-3 sm:mb-0">
                <h2 class="text-sm font-semibold text-gray-800 mb-1">
                    <i class="fas fa-edit text-green-500 mr-2"></i>Adjustment Manual
                </h2>
                <p class="text-xs text-gray-600">
                    Lakukan penyesuaian stok secara manual tanpa mengunggah file CSV.
                </p>
            </div>
            <a href="?page=adjustment/adjustmment_manual"
                class="inline-flex items-center px-3 py-2 text-xs bg-green-600 text-white rounded-md hover:bg-green-700 transition duration-200">
                <i class="fas fa-arrow-right mr-2"></i> Lanjut ke Adjustment Manual
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-4">
        <form @submit.prevent="processFile" enctype="multipart/form-data" class="space-y-3">
            <div>
                <label for="adjustment_date" class="block text-xs font-medium text-gray-700 mb-1">
                    Tanggal Adjustment <span class="text-red-500">*</span>
                </label>
                <input type="date" id="adjustment_date" x-model="adjustmentDate" required
                    class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label for="adjustment_type" class="block text-xs font-medium text-gray-700 mb-1">
                    Tipe Adjustment <span class="text-red-500">*</span>
                </label>
                <select id="adjustment_type" x-model="adjustmentType" required
                    class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Pilih Tipe Adjustment</option>
                    <option value="stok_chiller_harian">Stok Chiller Harian</option>
                    <option value="stok_opname_mingguan">Stok Opname Mingguan</option>
                </select>
            </div>

            <div>
                <label for="selected_storage" class="block text-xs font-medium text-gray-700 mb-1">
                    Storage <span class="text-red-500">*</span>
                </label>
                <select id="selected_storage" x-model="selectedStorage" required
                    @change="updateProcessButtonState"
                    class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Pilih Storage</option>
                    <template x-for="storage in allStorages" :key="storage">
                        <option :value="storage" x-text="storage"></option>
                    </template>
                </select>
            </div>

            <div>
                <label for="adjustment_file" class="block text-xs font-medium text-gray-700 mb-1">
                    File CSV <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center">
                    <input type="file" id="adjustment_file" @change="fileChanged" accept=".csv" required
                        class="block w-full text-xs text-gray-500
                                file:mr-3 file:py-1.5 file:px-3
                                file:rounded-md file:border-0
                                file:text-xs file:font-semibold
                                file:bg-blue-50 file:text-blue-700
                                hover:file:bg-blue-100">
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    Format yang didukung: CSV (Maks. 10MB)
                </p>
            </div>

            <div>
                <button type="submit" :disabled="isProcessing || !adjustmentDate || !adjustmentType || !selectedStorage || !selectedFile"
                    class="w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed transition duration-200">
                    <span x-text="isProcessing ? 'Memproses...' : 'Proses File'"></span>
                    <i x-show="isProcessing" class="fas fa-spinner fa-spin ml-2"></i>
                </button>
            </div>
        </form>

        <!-- Desktop Preview Table -->
        <div x-show="showPreview" x-transition class="hidden md:block mt-6 pt-4 border-t border-gray-200">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-800 mb-2 sm:mb-0">
                    Preview Data (<span x-text="parsedData.length"></span> baris valid)
                </h3>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex flex-col">
                        <label for="stock_date_until" class="text-xs font-medium text-gray-700 mb-1">Stok s/d:</label>
                        <input type="date" id="stock_date_until" x-model="stockDateUntil"
                            :required="parsedData.length > 0 && !isAdjustmentCalculated"
                            :disabled="isAdjustmentCalculated"
                            class="w-full px-2 py-1.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs">
                    </div>

                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" type="button"
                            class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition duration-200 h-9"
                            :disabled="isProcessing || parsedData.length === 0 || isAdjustmentCalculated">
                            <span x-text="adjustmentMode === 'csv_only' ? 'Jenis: Sesuai CSV' : 'Jenis: Semua Data'"></span>
                            <i class="ml-2 fas fa-chevron-down" :class="{ 'fa-chevron-up': open }"></i>
                        </button>
                        <div x-show="open" @click.outside="open = false"
                            class="absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                            <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                                <a href="#" @click.prevent="setAdjustmentMode('csv_only'); open = false"
                                    class="block px-4 py-2 text-xs text-gray-700 hover:bg-gray-100"
                                    :class="{ 'bg-blue-50 text-blue-700 font-semibold': adjustmentMode === 'csv_only' }">
                                    Sesuai CSV (Hanya yang di-upload)
                                </a>
                                <a href="#" @click.prevent="setAdjustmentMode('all_data'); open = false"
                                    class="block px-4 py-2 text-xs text-gray-700 hover:bg-gray-100"
                                    :class="{ 'bg-blue-50 text-blue-700 font-semibold': adjustmentMode === 'all_data' }">
                                    Semua Data (Stok + CSV)
                                </a>
                            </div>
                        </div>
                    </div>

                    <button x-show="!isAdjustmentCalculated" @click="calculateAdjustment"
                        :disabled="isProcessing || parsedData.length === 0 || !stockDateUntil"
                        class="inline-flex items-center px-3 py-2 text-xs font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 disabled:opacity-50 disabled:cursor-not-allowed transition duration-200 h-9">
                        <i class="fas fa-calculator mr-2"></i> Hitung Adjustment
                    </button>
                    <button x-show="isAdjustmentCalculated" @click="cancelAdjustmentCalculation"
                        :disabled="isProcessing"
                        class="inline-flex items-center px-3 py-2 text-xs font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed transition duration-200 h-9">
                        <i class="fas fa-times mr-2"></i> Batal Hitung
                    </button>
                    <button @click="confirmAdjustment" :disabled="isProcessing || parsedData.length === 0 || !adjustmentType || !isAdjustmentCalculated"
                        class="inline-flex items-center px-3 py-2 text-xs font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition duration-200 h-9">
                        <i class="fas fa-save mr-2"></i> Konfirmasi & Simpan
                    </button>
                </div>
            </div>

            <div x-show="parsedData.length > 0" class="mb-3 flex flex-wrap gap-2">
                <button @click="checkAllRows" type="button" class="px-2 py-1 bg-gray-200 text-gray-700 rounded-md text-xs hover:bg-gray-300">
                    Check Semua
                </button>
                <button @click="uncheckAllRows" type="button" class="px-2 py-1 bg-gray-200 text-gray-700 rounded-md text-xs hover:bg-gray-300">
                    Uncheck Semua
                </button>
                <button x-show="hasZeroAdjustmentRows" @click="toggleZeroAdjustmentRows" type="button" class="px-2 py-1 bg-gray-200 text-gray-700 rounded-md text-xs hover:bg-gray-300">
                    <span x-text="showZeroAdjustmentRows ? 'Sembunyikan Baris Nol' : 'Tampilkan Baris Nol'"></span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 border">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider bg-sky-100 text-sky-800">Storage</th>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider bg-lime-100 text-lime-800">Item</th>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider bg-amber-100 text-amber-800">Subitem</th>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider bg-fuchsia-100 text-fuchsia-800">Ekor/Pack (CSV)</th>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider bg-fuchsia-100 text-fuchsia-800">Quantity (KG) (CSV)</th>
                            <th x-show="isAdjustmentCalculated" class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider bg-violet-100 text-violet-800">Stok Ekor (Saat Ini)</th>
                            <th x-show="isAdjustmentCalculated" class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider bg-violet-100 text-violet-800">Stok Berat (Saat Ini)</th>
                            <th x-show="isAdjustmentCalculated" class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider bg-rose-100 text-rose-800">Adjustment Ekor/Pack</th>
                            <th x-show="isAdjustmentCalculated" class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider bg-rose-100 text-rose-800">Adjustment Quantity (KG)</th>
                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider bg-gray-100 text-gray-700">Keterangan</th>
                        </tr>
                        <tr class="bg-gray-100 font-bold text-xs text-gray-700 border-t border-gray-300">
                            <td class="px-2 py-2 whitespace-nowrap text-right" colspan="5">Total:</td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-fuchsia-700 font-semibold" x-text="totals.ekor_csv"></td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-fuchsia-700 font-semibold" x-text="formatNumber(totals.berat_csv)"></td>
                            <td x-show="isAdjustmentCalculated" class="px-2 py-2 whitespace-nowrap text-xs text-violet-700 font-semibold" x-text="totals.stok_ekor"></td>
                            <td x-show="isAdjustmentCalculated" class="px-2 py-2 whitespace-nowrap text-xs text-violet-700 font-semibold" x-text="formatNumber(totals.stok_berat)"></td>
                            <td x-show="isAdjustmentCalculated" class="px-2 py-2 whitespace-nowrap text-xs text-rose-700 font-semibold"
                                :class="{
                                        'text-green-600': totals.adjustment_ekor > 0,
                                        'text-red-600': totals.adjustment_ekor < 0,
                                        'text-gray-600': totals.adjustment_ekor === 0
                                    }"
                                x-text="totals.adjustment_ekor">
                            </td>
                            <td x-show="isAdjustmentCalculated" class="px-2 py-2 whitespace-nowrap text-xs text-rose-700 font-semibold"
                                :class="{
                                        'text-green-600': totals.adjustment_berat > 0,
                                        'text-red-600': totals.adjustment_berat < 0,
                                        'text-gray-600': totals.adjustment_berat === 0
                                    }"
                                x-text="formatNumber(totals.adjustment_berat)">
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-700"></td>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 text-xs">
                        <template x-for="(row, index) in filteredParsedData" :key="row.uniqueId">
                            <tr :class="{
                                'bg-blue-50': row.source === 'csv',
                                'bg-yellow-50': row.source === 'stock_only',
                                'text-gray-900': true
                            }">
                                <td class="px-2 py-2 whitespace-nowrap">
                                    <input type="checkbox" x-model="row.isChecked" :disabled="row.isDisabled" class="form-checkbox h-3 w-3 text-blue-600">
                                </td>
                                <td class="px-2 py-2 whitespace-nowrap font-semibold" x-text="index + 1"></td>
                                <td class="px-2 py-2 whitespace-nowrap font-semibold text-sky-800" x-text="row.storage"></td>
                                <td class="px-2 py-2 whitespace-nowrap font-semibold text-lime-800" x-text="row.item"></td>
                                <td class="px-2 py-2 whitespace-nowrap font-semibold text-amber-800" x-text="row.subitem || '-'"></td>

                                <td class="px-2 py-2 whitespace-nowrap font-semibold text-fuchsia-700" x-text="row.ekor_pack"></td>

                                <td class="px-2 py-2 whitespace-nowrap font-semibold text-fuchsia-700" x-text="formatNumber(row.quantity_kg)"></td>

                                <td x-show="isAdjustmentCalculated" class="px-2 py-2 whitespace-nowrap font-semibold text-violet-700"
                                    :class="{ 'text-red-600': row.current_ekor_pack < 0 }"
                                    x-text="row.current_ekor_pack !== undefined ? row.current_ekor_pack : '-'">
                                </td>
                                <td x-show="isAdjustmentCalculated" class="px-2 py-2 whitespace-nowrap font-semibold text-violet-700"
                                    :class="{ 'text-red-600': row.current_quantity_kg < 0 }"
                                    x-text="row.current_quantity_kg !== undefined ? formatNumber(row.current_quantity_kg) : '-'">
                                </td>

                                <td x-show="isAdjustmentCalculated" class="px-2 py-2 whitespace-nowrap font-semibold text-rose-700"
                                    :class="{
                                        'text-green-600': row.adjustment_ekor_pack > 0,
                                        'text-red-600': row.adjustment_ekor_pack < 0,
                                        'text-gray-600': row.adjustment_ekor_pack === 0
                                    }"
                                    x-text="row.adjustment_ekor_pack !== undefined ? row.adjustment_ekor_pack : '-'">
                                </td>
                                <td x-show="isAdjustmentCalculated" class="px-2 py-2 whitespace-nowrap font-semibold text-rose-700"
                                    :class="{
                                        'text-green-600': row.adjustment_quantity_kg > 0,
                                        'text-red-600': row.adjustment_quantity_kg < 0,
                                        'text-gray-600': row.adjustment_quantity_kg === 0
                                    }"
                                    x-text="row.adjustment_quantity_kg !== undefined ? formatNumber(row.adjustment_quantity_kg) : '-'">
                                </td>

                                <td class="px-2 py-2 whitespace-nowrap text-gray-700" x-text="row.keterangan || '-'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile Preview Cards -->
        <div x-show="showPreview" x-transition class="block md:hidden mt-6 pt-4 border-t border-gray-200">
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">
                    Preview Data (<span x-text="parsedData.length"></span> baris valid)
                </h3>

                <!-- Mobile Controls -->
                <div class="space-y-3 mb-4">
                    <div>
                        <label class="text-xs font-medium text-gray-700 mb-1 block">Stok s/d:</label>
                        <input type="date" x-model="stockDateUntil"
                            :required="parsedData.length > 0 && !isAdjustmentCalculated"
                            :disabled="isAdjustmentCalculated"
                            class="w-full px-2 py-1.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <button x-show="!isAdjustmentCalculated" @click="calculateAdjustment"
                            :disabled="isProcessing || parsedData.length === 0 || !stockDateUntil"
                            class="w-full inline-flex items-center justify-center px-3 py-2 text-xs font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition duration-200">
                            <i class="fas fa-calculator mr-2"></i> Hitung
                        </button>
                        <button x-show="isAdjustmentCalculated" @click="cancelAdjustmentCalculation"
                            :disabled="isProcessing"
                            class="w-full inline-flex items-center justify-center px-3 py-2 text-xs font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition duration-200">
                            <i class="fas fa-times mr-2"></i> Batal
                        </button>
                    </div>

                    <button @click="confirmAdjustment" :disabled="isProcessing || parsedData.length === 0 || !adjustmentType || !isAdjustmentCalculated"
                        class="w-full inline-flex items-center justify-center px-3 py-2 text-xs font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition duration-200">
                        <i class="fas fa-save mr-2"></i> Konfirmasi & Simpan
                    </button>
                </div>

                <!-- Mobile Action Buttons -->
                <div x-show="parsedData.length > 0" class="flex flex-wrap gap-2 mb-3">
                    <button @click="checkAllRows" type="button" class="flex-1 px-2 py-1 bg-gray-200 text-gray-700 rounded-md text-xs hover:bg-gray-300">
                        Check Semua
                    </button>
                    <button @click="uncheckAllRows" type="button" class="flex-1 px-2 py-1 bg-gray-200 text-gray-700 rounded-md text-xs hover:bg-gray-300">
                        Uncheck Semua
                    </button>
                    <button x-show="hasZeroAdjustmentRows" @click="toggleZeroAdjustmentRows" type="button" class="flex-1 px-2 py-1 bg-gray-200 text-gray-700 rounded-md text-xs hover:bg-gray-300">
                        <span x-text="showZeroAdjustmentRows ? 'Sembunyikan Nol' : 'Tampilkan Nol'"></span>
                    </button>
                </div>
            </div>

            <!-- Mobile Cards -->
            <div class="space-y-3">
                <template x-for="(row, index) in filteredParsedData" :key="row.uniqueId">
                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden"
                        :class="{
                             'border-blue-200 bg-blue-50': row.source === 'csv',
                             'border-yellow-200 bg-yellow-50': row.source === 'stock_only'
                         }">

                        <!-- Card Header -->
                        <div class="p-3 border-b border-gray-100">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center space-x-2">
                                    <input type="checkbox" x-model="row.isChecked" :disabled="row.isDisabled"
                                        class="form-checkbox h-4 w-4 text-blue-600 rounded">
                                    <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                        <span x-text="index + 1"></span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs px-2 py-1 rounded-full"
                                        :class="row.source === 'csv' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800'"
                                        x-text="row.source === 'csv' ? 'CSV' : 'Stok'">
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500">Storage:</span>
                                    <span class="font-semibold text-sky-800" x-text="row.storage"></span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500">Item:</span>
                                    <span class="font-semibold text-lime-800" x-text="row.item"></span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500">Subitem:</span>
                                    <span class="font-semibold text-amber-800" x-text="row.subitem || '-'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- CSV Data -->
                        <div class="p-3 bg-fuchsia-50 border-b border-fuchsia-100">
                            <h4 class="text-xs font-semibold text-fuchsia-800 mb-2">Data CSV</h4>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div>
                                    <span class="text-fuchsia-600">Ekor/Pack:</span>
                                    <span class="font-semibold text-fuchsia-800 block" x-text="row.ekor_pack"></span>
                                </div>
                                <div>
                                    <span class="text-fuchsia-600">Quantity (KG):</span>
                                    <span class="font-semibold text-fuchsia-800 block" x-text="formatNumber(row.quantity_kg)"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Current Stock (if calculated) -->
                        <template x-if="isAdjustmentCalculated">
                            <div class="p-3 bg-violet-50 border-b border-violet-100">
                                <h4 class="text-xs font-semibold text-violet-800 mb-2">Stok Saat Ini</h4>
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <span class="text-violet-600">Ekor:</span>
                                        <span class="font-semibold block"
                                            :class="{ 'text-red-600': row.current_ekor_pack < 0, 'text-violet-800': row.current_ekor_pack >= 0 }"
                                            x-text="row.current_ekor_pack !== undefined ? row.current_ekor_pack : '-'">
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-violet-600">Berat:</span>
                                        <span class="font-semibold block"
                                            :class="{ 'text-red-600': row.current_quantity_kg < 0, 'text-violet-800': row.current_quantity_kg >= 0 }"
                                            x-text="row.current_quantity_kg !== undefined ? formatNumber(row.current_quantity_kg) : '-'">
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Adjustment (if calculated) -->
                        <template x-if="isAdjustmentCalculated">
                            <div class="p-3 bg-rose-50 border-b border-rose-100">
                                <h4 class="text-xs font-semibold text-rose-800 mb-2">Adjustment</h4>
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <span class="text-rose-600">Ekor/Pack:</span>
                                        <span class="font-semibold block"
                                            :class="{
                                                  'text-green-600': row.adjustment_ekor_pack > 0,
                                                  'text-red-600': row.adjustment_ekor_pack < 0,
                                                  'text-gray-600': row.adjustment_ekor_pack === 0
                                              }"
                                            x-text="row.adjustment_ekor_pack !== undefined ? row.adjustment_ekor_pack : '-'">
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-rose-600">Quantity (KG):</span>
                                        <span class="font-semibold block"
                                            :class="{
                                                  'text-green-600': row.adjustment_quantity_kg > 0,
                                                  'text-red-600': row.adjustment_quantity_kg < 0,
                                                  'text-gray-600': row.adjustment_quantity_kg === 0
                                              }"
                                            x-text="row.adjustment_quantity_kg !== undefined ? formatNumber(row.adjustment_quantity_kg) : '-'">
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Keterangan -->
                        <div class="p-3 bg-gray-50">
                            <h4 class="text-xs font-semibold text-gray-700 mb-1">Keterangan</h4>
                            <p class="text-xs text-gray-600" x-text="row.keterangan || '-'"></p>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Mobile Totals -->
            <div x-show="parsedData.length > 0" class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                <h4 class="text-xs font-semibold text-gray-800 mb-2 text-center">Total Keseluruhan</h4>
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="text-center">
                        <div class="text-fuchsia-600">Ekor CSV</div>
                        <div class="font-bold text-fuchsia-800" x-text="totals.ekor_csv"></div>
                    </div>
                    <div class="text-center">
                        <div class="text-fuchsia-600">Berat CSV</div>
                        <div class="font-bold text-fuchsia-800" x-text="formatNumber(totals.berat_csv)"></div>
                    </div>
                    <template x-if="isAdjustmentCalculated">
                        <>
                            <div class="text-center">
                                <div class="text-violet-600">Stok Ekor</div>
                                <div class="font-bold text-violet-800" x-text="totals.stok_ekor"></div>
                            </div>
                            <div class="text-center">
                                <div class="text-violet-600">Stok Berat</div>
                                <div class="font-bold text-violet-800" x-text="formatNumber(totals.stok_berat)"></div>
                            </div>
                            <div class="text-center">
                                <div class="text-rose-600">Adj. Ekor</div>
                                <div class="font-bold"
                                    :class="{
                                         'text-green-600': totals.adjustment_ekor > 0,
                                         'text-red-600': totals.adjustment_ekor < 0,
                                         'text-gray-600': totals.adjustment_ekor === 0
                                     }"
                                    x-text="totals.adjustment_ekor">
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-rose-600">Adj. Berat</div>
                                <div class="font-bold"
                                    :class="{
                                         'text-green-600': totals.adjustment_berat > 0,
                                         'text-red-600': totals.adjustment_berat < 0,
                                         'text-gray-600': totals.adjustment_berat === 0
                                     }"
                                    x-text="formatNumber(totals.adjustment_berat)">
                                </div>
                            </div>
                        </>
                    </template>
                </div>
            </div>
        </div>

        <button x-show="showScrollToTop" @click="scrollToTop" x-transition
            class="fixed bottom-4 right-4 p-2 bg-gray-800 text-white rounded-full shadow-lg text-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-600 transition duration-200">
            <i class="fas fa-arrow-up"></i>
        </button>
    </div>
</main>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('adjustmentApp', () => ({
            adjustmentDate: new Date().toISOString().split('T')[0],
            adjustmentType: '',
            stockDateUntil: '', // This will be set automatically now
            selectedFile: null,
            parsedData: [],
            originalParsedData: [], // Store original parsed data from CSV before calculation
            isProcessing: false,
            showPreview: false,
            showScrollToTop: false,
            userNama: '<?php echo $_SESSION['user_nama'] ?? 'User'; ?>',
            isAdjustmentCalculated: false, // Track if adjustment calculation has been done

            // New properties for adjustment mode
            adjustmentMode: 'all_data', // 'csv_only' or 'all_data'
            allStorages: ['Chiller 1', 'Chiller 2', 'Chiller 3'],
            selectedStorage: '', // This will hold the single selected storage
            showZeroAdjustmentRows: false, // Control visibility of zero adjustment rows

            get filteredParsedData() {
                if (this.showZeroAdjustmentRows) {
                    return this.parsedData;
                }
                return this.parsedData.filter(row => {
                    const hasZeroAdjustment = (row.adjustment_ekor_pack === 0 && row.adjustment_quantity_kg === 0);
                    if (this.isAdjustmentCalculated) {
                        return !hasZeroAdjustment;
                    }
                    return !(row.ekor_pack === 0 && row.quantity_kg === 0 && row.current_ekor_pack === 0 && row.current_quantity_kg === 0);
                });
            },

            get hasZeroAdjustmentRows() {
                return this.parsedData.some(row => {
                    const isZeroAdjustment = (row.adjustment_ekor_pack === 0 && row.adjustment_quantity_kg === 0);
                    const isZeroCsvTarget = (row.ekor_pack === 0 && row.quantity_kg === 0);
                    const isZeroCurrentStock = (row.current_ekor_pack === 0 && row.current_quantity_kg === 0);

                    if (this.isAdjustmentCalculated) {
                        return isZeroAdjustment;
                    }
                    return isZeroCsvTarget && isZeroCurrentStock;
                });
            },

            // --- Computed Properties for Table Totals ---
            get totals() {
                const initialTotals = {
                    ekor_csv: 0,
                    berat_csv: 0,
                    stok_ekor: 0,
                    stok_berat: 0,
                    adjustment_ekor: 0,
                    adjustment_berat: 0,
                };

                if (!this.parsedData || this.parsedData.length === 0) {
                    return initialTotals;
                }

                // Calculate totals for all rows (CSV and stock columns)
                const allRowsTotals = this.parsedData.reduce((acc, row) => {
                    if (row.source === 'csv' || this.adjustmentMode === 'csv_only') {
                        acc.ekor_csv += row.ekor_pack || 0;
                        acc.berat_csv += row.quantity_kg || 0;
                    }

                    if (this.isAdjustmentCalculated || row.source === 'stock_only') {
                        acc.stok_ekor += row.current_ekor_pack || 0;
                        acc.stok_berat += row.current_quantity_kg || 0;
                    }
                    return acc;
                }, {
                    ekor_csv: 0,
                    berat_csv: 0,
                    stok_ekor: 0,
                    stok_berat: 0,
                });

                // Calculate adjustment totals only for rows that are visible and checked
                const adjustmentRowsForTotal = this.filteredParsedData.filter(row => row.isChecked);

                const adjustmentTotals = adjustmentRowsForTotal.reduce((acc, row) => {
                    if (this.isAdjustmentCalculated) {
                        acc.adjustment_ekor += row.adjustment_ekor_pack || 0;
                        acc.adjustment_berat += row.adjustment_quantity_kg || 0;
                    } else {
                        acc.adjustment_ekor += row.ekor_pack || 0;
                        acc.adjustment_berat += row.quantity_kg || 0;
                    }
                    return acc;
                }, {
                    adjustment_ekor: 0,
                    adjustment_berat: 0,
                });

                return {
                    ekor_csv: allRowsTotals.ekor_csv,
                    berat_csv: allRowsTotals.berat_csv,
                    stok_ekor: allRowsTotals.stok_ekor,
                    stok_berat: allRowsTotals.stok_berat,
                    adjustment_ekor: adjustmentTotals.adjustment_ekor,
                    adjustment_berat: adjustmentTotals.adjustment_berat,
                };
            },
            // --- End Computed Properties ---

            init() {
                window.addEventListener('scroll', () => {
                    this.handleScroll();
                });
            },

            fileChanged(event) {
                this.selectedFile = event.target.files[0];
                this.showPreview = false;
                this.parsedData = [];
                this.originalParsedData = [];
                this.isAdjustmentCalculated = false;
                this.stockDateUntil = '';
                this.showZeroAdjustmentRows = false;
            },

            setAdjustmentMode(mode) {
                this.adjustmentMode = mode;
                this.isAdjustmentCalculated = false;
                // When switching mode, clear parsedData if CSV is not selected for 'all_data' mode
                // Or reset parsedData to originalParsedData if switching to 'csv_only'
                if (mode === 'all_data' && !this.selectedFile) {
                    this.parsedData = [];
                    this.originalParsedData = [];
                } else if (mode === 'csv_only' && this.originalParsedData.length > 0) {
                    this.parsedData = JSON.parse(JSON.stringify(this.originalParsedData));
                    this.parsedData.forEach(row => {
                        row.current_ekor_pack = 0;
                        row.current_quantity_kg = 0;
                        row.adjustment_ekor_pack = undefined;
                        row.adjustment_quantity_kg = undefined;
                        row.isDisabled = false;
                        row.isChecked = true;
                        row.source = 'csv';
                    });
                } else if (mode === 'all_data' && this.selectedFile) {
                    // If switching to 'all_data' and a CSV is loaded,
                    // we need to ensure stock data is fetched, but this will happen
                    // when calculateAdjustment is called. For now, keep the CSV data
                    // as a base.
                    this.parsedData.forEach(row => {
                        row.current_ekor_pack = 0;
                        row.current_quantity_kg = 0;
                        row.adjustment_ekor_pack = undefined;
                        row.adjustment_quantity_kg = undefined;
                        row.isDisabled = false;
                        row.isChecked = true;
                        row.source = 'csv';
                    });
                }
                this.showZeroAdjustmentRows = false;
            },

            async processFile() {
                if (!this.selectedStorage) {
                    this.showAlert('Peringatan', 'Silakan pilih Storage terlebih dahulu', 'warning');
                    return;
                }

                if (!this.selectedFile && this.adjustmentMode === 'csv_only') {
                    this.showAlert('Peringatan', 'Silakan pilih file CSV terlebih dahulu', 'warning');
                    return;
                }

                // If in 'all_data' mode and no file is selected, still proceed to calculate adjustment
                if (this.adjustmentMode === 'all_data' && !this.selectedFile) {
                    this.stockDateUntil = this.adjustmentDate;
                    await this.calculateAdjustment();
                    return;
                }

                const fileExt = this.selectedFile.name.split('.').pop().toLowerCase();
                if (fileExt !== 'csv') {
                    this.showAlert('Error', 'Hanya file CSV yang diperbolehkan', 'error');
                    return;
                }

                if (this.selectedFile.size > 10 * 1024 * 1024) {
                    this.showAlert('Error', 'Ukuran file terlalu besar. Maksimal 10MB', 'error');
                    return;
                }

                this.isProcessing = true;
                this.showPreview = false;
                this.parsedData = [];
                this.originalParsedData = [];
                this.isAdjustmentCalculated = false;
                this.showZeroAdjustmentRows = false;

                // Set stockDateUntil to adjustmentDate before processing
                this.stockDateUntil = this.adjustmentDate;

                const reader = new FileReader();

                reader.onload = async (e) => {
                    try {
                        const csvData = e.target.result;
                        const lines = csvData.split('\n').filter(line => line.trim() !== '');

                        if (lines.length === 0) {
                            throw new Error('File CSV kosong atau tidak memiliki data.');
                        }

                        const headers = lines[0].split(',').map(h => h.trim());

                        // --- PERBAIKAN DI SINI: Validasi Header Lebih Fleksibel ---
                        const hasItemHeader = headers.includes('Item');
                        const hasItemSubitemHeader = headers.includes('Item : Subitem');
                        const hasEkorPackHeader = headers.includes('Ekor/Pack');
                        const hasQuantityKgHeader = headers.includes('Quantity (KG)');

                        if (!hasItemHeader && !hasItemSubitemHeader) {
                            throw new Error('Kolom wajib "Item" atau "Item : Subitem" tidak ditemukan.');
                        }
                        if (!hasEkorPackHeader) {
                            throw new Error('Kolom wajib "Ekor/Pack" tidak ditemukan.');
                        }
                        if (!hasQuantityKgHeader) {
                            throw new Error('Kolom wajib "Quantity (KG)" tidak ditemukan.');
                        }
                        // --- AKHIR PERBAIKAN VALIDASI HEADER ---

                        const rawData = [];
                        let skippedRows = 0;
                        let skippedDetails = [];

                        for (let i = 1; i < lines.length; i++) {
                            const values = this.splitCsvLine(lines[i]);
                            const row = {};

                            headers.forEach((header, index) => {
                                row[header] = values[index] ? values[index].trim() : '';
                            });

                            let item = '';
                            let subitem = '';

                            // Logika parsing Item dan Subitem yang sudah benar
                            const itemColumnValue = row['Item'] || row['Item : Subitem'] || '';
                            const parts = itemColumnValue.split(':').map(p => p.trim());
                            item = parts[0] || '';
                            subitem = parts[1] || '';

                            let skipReason = '';

                            // Pastikan mengecek `item` yang sudah diparsing
                            if (!item) {
                                skipReason = 'Item kosong';
                            } else if (isNaN(parseFloat(row['Ekor/Pack'])) || isNaN(parseFloat(row['Quantity (KG)'].replace(',', '.')))) {
                                skipReason = 'Ekor/Pack atau Quantity (KG) bukan angka valid';
                            }

                            if (skipReason) {
                                skippedRows++;
                                skippedDetails.push({
                                    row: i + 1,
                                    reason: skipReason,
                                    data: row
                                });
                                continue;
                            }

                            rawData.push({
                                uniqueId: `${this.selectedStorage}|${item}|${subitem}`,
                                originalIndex: i - 1,
                                isChecked: true,
                                isDisabled: false,
                                source: 'csv',
                                storage: this.selectedStorage,
                                item: item,
                                subitem: subitem,
                                ekor_pack: parseInt(row['Ekor/Pack']) || 0,
                                quantity_kg: parseFloat(row['Quantity (KG)'].replace(',', '.')) || 0,
                                keterangan: row['Keterangan'] || '',
                                current_ekor_pack: 0,
                                current_quantity_kg: 0,
                                adjustment_ekor_pack: undefined,
                                adjustment_quantity_kg: undefined,
                            });
                        }

                        const distinctDataMap = new Map();
                        let duplicateCount = 0;

                        rawData.forEach(row => {
                            const key = `${row.storage}|${row.item}|${row.subitem}`;
                            if (distinctDataMap.has(key)) {
                                const existingRow = distinctDataMap.get(key);
                                existingRow.ekor_pack += row.ekor_pack;
                                existingRow.quantity_kg += row.quantity_kg;
                                duplicateCount++;
                            } else {
                                distinctDataMap.set(key, {
                                    ...row
                                });
                            }
                        });

                        this.parsedData = Array.from(distinctDataMap.values());
                        this.originalParsedData = JSON.parse(JSON.stringify(this.parsedData));

                        this.showPreview = true;

                        let successMsg = '';
                        if (this.parsedData.length === 0) {
                            successMsg = 'Tidak ada data valid yang ditemukan dalam file setelah pemrosesan.';
                            this.showAlert('Info', successMsg, 'info');
                        } else {
                            successMsg = `File berhasil diproses.<br>`;
                            successMsg += `Total ${rawData.length} baris data ditemukan di file CSV.<br>`;
                            if (duplicateCount > 0) {
                                successMsg += `Ditemukan <strong>${duplicateCount}</strong> duplikasi berdasarkan "Storage", "Item", dan "Subitem".<br>`;
                                successMsg += `Data duplikat telah digabungkan. Sekarang ada <strong>${this.parsedData.length}</strong> baris data unik.<br>`;
                            } else {
                                successMsg += `Tidak ditemukan duplikasi. Total <strong>${this.parsedData.length}</strong> baris data unik.<br>`;
                            }

                            if (skippedRows > 0) {
                                successMsg += `${skippedRows} baris tidak valid dilewati (misalnya, Item kosong atau nilai tidak valid).`;
                            }
                            this.showAlert('Berhasil', successMsg, 'success');
                            // Automatically calculate adjustment after successful file processing
                            await this.calculateAdjustment();
                        }

                    } catch (error) {
                        console.error('Error processing file:', error);
                        this.showAlert('Error', `Gagal memproses file: ${error.message}`, 'error');
                    } finally {
                        this.isProcessing = false;
                    }
                };

                reader.onerror = () => {
                    this.showAlert('Error', 'Gagal membaca file', 'error');
                    this.isProcessing = false;
                };

                reader.readAsText(this.selectedFile);
            },

            splitCsvLine(line) {
                const result = [];
                let inQuote = false;
                let currentField = '';
                for (let i = 0; i < line.length; i++) {
                    const char = line[i];
                    if (char === '"') {
                        inQuote = !inQuote;
                    } else if (char === ',' && !inQuote) {
                        result.push(currentField);
                        currentField = '';
                    } else {
                        currentField += char;
                    }
                }
                result.push(currentField);
                return result;
            },

            async calculateAdjustment() {
                if (!this.stockDateUntil) {
                    this.showAlert('Peringatan', 'Tanggal Stok Sampai Dengan wajib diisi untuk menghitung adjustment.', 'warning');
                    return;
                }
                if (!this.selectedStorage) { // Combined check for selectedStorage
                    this.showAlert('Peringatan', 'Silakan pilih Storage terlebih dahulu.', 'warning');
                    return;
                }
                if (this.parsedData.length === 0 && this.adjustmentMode === 'csv_only') {
                    this.showAlert('Peringatan', 'Tidak ada data dari CSV untuk dihitung adjustment.', 'warning');
                    return;
                }
                // Ensure stockDateUntil is set to adjustmentDate if not already
                if (this.stockDateUntil !== this.adjustmentDate) {
                    this.stockDateUntil = this.adjustmentDate;
                }


                this.isProcessing = true;
                this.isAdjustmentCalculated = false;
                this.showZeroAdjustmentRows = false;

                try {
                    const payloadData = this.parsedData.map(row => ({
                        storage: row.storage,
                        item: row.item,
                        subitem: row.subitem,
                        target_ekor_pack: row.ekor_pack,
                        target_quantity_kg: row.quantity_kg,
                    }));

                    const payload = {
                        stock_date_until: this.stockDateUntil,
                        adjustment_mode: this.adjustmentMode,
                        selected_storages: [this.selectedStorage],
                        data: payloadData
                    };

                    const response = await fetch('./controller/adjustment/calculate_stock.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(payload)
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const result = await response.json();

                    if (result.success) {
                        const csvDataMap = new Map();
                        this.originalParsedData.forEach(row => {
                            const key = `${row.storage}|${row.item}|${row.subitem}`;
                            csvDataMap.set(key, {
                                ...row
                            });
                        });

                        const updatedParsedData = [];

                        result.calculated_data.forEach(calculatedRow => {
                            const key = `${calculatedRow.storage}|${calculatedRow.item}|${calculatedRow.subitem}`;
                            let rowToUpdate = csvDataMap.get(key);

                            if (rowToUpdate) {
                                rowToUpdate.current_ekor_pack = calculatedRow.current_ekor_pack;
                                rowToUpdate.current_quantity_kg = calculatedRow.current_quantity_kg;
                                rowToUpdate.adjustment_ekor_pack = calculatedRow.adjustment_ekor_pack;
                                rowToUpdate.adjustment_quantity_kg = calculatedRow.adjustment_quantity_kg;
                                rowToUpdate.isDisabled = (rowToUpdate.adjustment_ekor_pack === 0 && rowToUpdate.adjustment_quantity_kg === 0);
                                rowToUpdate.isChecked = !rowToUpdate.isDisabled;
                                csvDataMap.delete(key);
                                updatedParsedData.push(rowToUpdate);
                            } else if (this.adjustmentMode === 'all_data') {
                                const newRow = {
                                    uniqueId: key,
                                    originalIndex: undefined,
                                    isChecked: true,
                                    isDisabled: false,
                                    source: 'stock_only',
                                    storage: calculatedRow.storage,
                                    item: calculatedRow.item,
                                    subitem: calculatedRow.subitem,
                                    ekor_pack: 0,
                                    quantity_kg: 0.00,
                                    keterangan: `Adjustment SO ${this.adjustmentType}, Tanggal Adjustment: ${this.adjustmentDate}, Lokasi: ${calculatedRow.storage}, Stok per tanggal: ${this.stockDateUntil}, Jenis: All, Data diluar CSV`,
                                    current_ekor_pack: calculatedRow.current_ekor_pack,
                                    current_quantity_kg: calculatedRow.current_quantity_kg,
                                    adjustment_ekor_pack: calculatedRow.adjustment_ekor_pack,
                                    adjustment_quantity_kg: calculatedRow.adjustment_quantity_kg,
                                };
                                newRow.isDisabled = (newRow.adjustment_ekor_pack === 0 && newRow.adjustment_quantity_kg === 0);
                                newRow.isChecked = !newRow.isDisabled;
                                updatedParsedData.push(newRow);
                            }
                        });

                        if (this.adjustmentMode === 'csv_only') {
                            csvDataMap.forEach(remainingCsvRow => {
                                remainingCsvRow.current_ekor_pack = 0;
                                remainingCsvRow.current_quantity_kg = 0;
                                remainingCsvRow.adjustment_ekor_pack = remainingCsvRow.ekor_pack;
                                remainingCsvRow.adjustment_quantity_kg = remainingCsvRow.quantity_kg;
                                remainingCsvRow.isDisabled = (remainingCsvRow.adjustment_ekor_pack === 0 && remainingCsvRow.adjustment_quantity_kg === 0);
                                remainingCsvRow.isChecked = !remainingCsvRow.isDisabled;
                                updatedParsedData.push(remainingCsvRow);
                            });
                        }

                        this.parsedData = updatedParsedData.sort((a, b) => {
                            if (a.source === 'csv' && b.source === 'stock_only') return -1;
                            if (a.source === 'stock_only' && b.source === 'csv') return 1;
                            if (a.source === 'csv' && b.source === 'csv') {
                                return (a.originalIndex !== undefined && b.originalIndex !== undefined) ? a.originalIndex - b.originalIndex : 0;
                            }
                            if (a.source === 'stock_only' && b.source === 'stock_only') {
                                if (a.item === b.item) {
                                    return a.subitem.localeCompare(b.subitem);
                                }
                                return a.item.localeCompare(b.item);
                            }
                            return 0;
                        });

                        this.isAdjustmentCalculated = true;
                        this.showAlert('Berhasil', result.message, 'success');
                    } else {
                        this.showAlert('Error', result.message, 'error');
                    }
                } catch (error) {
                    console.error('Error calculating adjustment:', error);
                    this.showAlert('Error', `Gagal menghitung adjustment: ${error.message}`, 'error');
                } finally {
                    this.isProcessing = false;
                }
            },

            cancelAdjustmentCalculation() {
                this.isAdjustmentCalculated = false;
                this.parsedData = JSON.parse(JSON.stringify(this.originalParsedData));
                this.parsedData.forEach(row => {
                    row.current_ekor_pack = 0;
                    row.current_quantity_kg = 0;
                    row.adjustment_ekor_pack = undefined;
                    row.adjustment_quantity_kg = undefined;
                    row.isDisabled = false;
                    row.isChecked = true;
                    row.source = 'csv';
                });
                this.stockDateUntil = '';
                this.showZeroAdjustmentRows = false;
            },

            async confirmAdjustment() {
                if (!this.adjustmentType) {
                    this.showAlert('Peringatan', 'Tipe Adjustment wajib diisi.', 'warning');
                    return;
                }

                // Get the data that will be sent
                const dataToSend = this.parsedData.filter(row => {
                    if (!row.isChecked) {
                        return false;
                    }
                    if (this.isAdjustmentCalculated) {
                        return row.adjustment_ekor_pack !== 0 || row.adjustment_quantity_kg !== 0;
                    }
                    return row.ekor_pack !== 0 || row.quantity_kg !== 0;
                });

                if (dataToSend.length === 0) {
                    this.showAlert('Peringatan', 'Tidak ada data yang dipilih atau memiliki nilai adjustment untuk disimpan.', 'warning');
                    return;
                }

                // Calculate totals for confirmation
                const totals = dataToSend.reduce((acc, row) => {
                    if (this.isAdjustmentCalculated) {
                        acc.ekor += row.adjustment_ekor_pack || 0;
                        acc.berat += row.adjustment_quantity_kg || 0;
                    } else {
                        acc.ekor += row.ekor_pack || 0;
                        acc.berat += row.quantity_kg || 0;
                    }
                    return acc;
                }, {
                    ekor: 0,
                    berat: 0
                });

                // Prepare confirmation message
                let confirmationMessage = `
                <div style="text-align: left;">
                    <p>Anda akan menyimpan adjustment dengan detail berikut:</p>
                    <ul>
                        <li>Tanggal Adjustment: <strong>${this.adjustmentDate}</strong></li>
                        <li>Tipe Adjustment: <strong>${this.adjustmentType}</strong></li>
                        <li>Jumlah Baris Data: <strong>${dataToSend.length}</strong></li>
                        <li>Total Ekor/Pack: <strong>${this.formatNumber(totals.ekor)}</strong></li>
                        <li>Total Berat (KG): <strong>${this.formatNumber(totals.berat)}</strong></li>
                    </ul>
                    <p>Apakah Anda yakin ingin melanjutkan?</p>
                </div>
            `;

                // Show confirmation dialog with details
                const result = await Swal.fire({
                    title: 'Konfirmasi Adjustment',
                    html: confirmationMessage,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Simpan',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                });

                if (!result.isConfirmed) {
                    return;
                }

                this.isProcessing = true;

                try {
                    const payload = dataToSend.map(row => {
                        const ekorAdjust = this.isAdjustmentCalculated ? row.adjustment_ekor_pack : row.ekor_pack;
                        const kgAdjust = this.isAdjustmentCalculated ? row.adjustment_quantity_kg : row.quantity_kg;

                        return {
                            storage: row.storage,
                            item: row.item,
                            subitem: row.subitem,
                            adjustment_ekor_pack: ekorAdjust,
                            adjustment_quantity_kg: parseFloat(kgAdjust.toFixed(2)),
                            keterangan: row.keterangan
                        };
                    });

                    const response = await fetch('./controller/adjustment/adjust.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            adjustment_date: this.adjustmentDate,
                            adjustment_type: this.adjustmentType,
                            user_nama: this.userNama,
                            is_calculated_adjustment: this.isAdjustmentCalculated,
                            data: payload
                        })
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const result = await response.json();

                    if (result.success) {
                        await this.showAlert('Berhasil', result.message, 'success');
                        this.resetForm();
                    } else {
                        let errorMessage = `Gagal menyimpan adjustment: ${result.message}`;
                        if (result.details && result.details.length > 0) {
                            errorMessage += `<br><br>Detail error:<br>`;
                            errorMessage += result.details.map(detail => `- ${detail}`).join('<br>');
                        }
                        this.showAlert('Error', errorMessage, 'error');
                    }
                } catch (error) {
                    console.error('Error saving adjustment:', error);
                    this.showAlert('Error', `Gagal menyimpan adjustment: ${error.message}`, 'error');
                } finally {
                    this.isProcessing = false;
                }
            },

            resetForm() {
                this.adjustmentDate = new Date().toISOString().split('T')[0];
                this.adjustmentType = '';
                this.stockDateUntil = '';
                this.selectedFile = null;
                document.getElementById('adjustment_file').value = '';
                this.parsedData = [];
                this.originalParsedData = [];
                this.isProcessing = false;
                this.showPreview = false;
                this.isAdjustmentCalculated = false;
                this.adjustmentMode = 'all_data';
                this.selectedStorage = '';
                this.showZeroAdjustmentRows = false;
            },

            checkAllRows() {
                this.parsedData.forEach(row => {
                    if (!row.isDisabled) {
                        row.isChecked = true;
                    }
                });
            },

            uncheckAllRows() {
                this.parsedData.forEach(row => {
                    row.isChecked = false;
                });
            },

            toggleZeroAdjustmentRows() {
                this.showZeroAdjustmentRows = !this.showZeroAdjustmentRows;
            },

            formatNumber(num) {
                if (num === undefined || num === null) return '-';
                const parsedNum = typeof num === 'string' ? parseFloat(num) : num;
                if (isNaN(parsedNum)) return '-';

                return new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(parsedNum);
            },

            showAlert(title, message, type) {
                return Swal.fire({
                    title: title,
                    html: message,
                    icon: type,
                    confirmButtonText: 'OK',
                    customClass: {
                        confirmButton: `swal-btn-${type}`
                    },
                });
            },

            handleScroll() {
                this.showScrollToTop = window.scrollY > 300;
            },

            scrollToTop() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
        }));
    });
</script>