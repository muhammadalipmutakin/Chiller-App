<div class="bg-white shadow-lg rounded-lg p-4">
    <!-- Header dengan Export -->
    <div class="flex flex-row justify-between items-center mb-6">
        <h2 class="text-lg font-bold text-gray-800">Daftar Stok</h2>
        <button @click="exportToExcel()" class="px-4 py-2 bg-green-500 text-white text-sm rounded-lg shadow-md hover:bg-green-600 transition-all duration-200 flex items-center gap-2">
            <i class="fas fa-file-excel"></i>
            <span>Excel</span>
        </button>
    </div>

    <!-- Filter Controls yang Lebih Compact -->
    <div class="mb-6 bg-gray-50 rounded-xl p-4 border border-gray-200">
        <div class="flex items-center justify-between mb-3">
            <label class="text-sm font-semibold text-gray-700">Filter Stok:</label>
            <select x-model="stockFilter" @change="applyFilters" class="block w-36 pl-3 pr-8 py-2 border border-gray-300 text-sm rounded-lg bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                <option value="all">Semua Stok</option>
                <option value="positive">Stok Positif</option>
                <option value="negative">Stok Negatif</option>
                <option value="zero">Stok Nol</option>
            </select>
        </div>

        <label class="flex items-center space-x-3 p-2 bg-white rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors cursor-pointer">
            <input type="checkbox" x-model="hideZeroStock" @change="applyFilters" class="form-checkbox h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
            <span class="text-sm font-medium text-gray-700">Sembunyikan stok 0</span>
        </label>
    </div>

    <!-- Summary Cards -->
    <div class="mb-6 grid grid-cols-2 gap-3">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-md">
            <p class="text-xs opacity-90 mb-1">Total Item</p>
            <p class="text-xl font-bold" x-text="stockData.length"></p>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white shadow-md">
            <p class="text-xs opacity-90 mb-1">Stok Saat Ini</p>
            <p class="text-xl font-bold" x-text="parseFloat(totals.stock_after_adj_kg).toFixed(2) + ' Kg'"></p>
        </div>
    </div>

    <!-- Loading State -->
    <template x-if="loading">
        <div class="text-center py-12">
            <div class="inline-flex flex-col items-center">
                <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-200 border-t-blue-600 mb-4"></div>
                <p class="text-gray-600 font-medium">Memuat data stok...</p>
                <p class="text-sm text-gray-500 mt-1">Silakan tunggu sebentar</p>
            </div>
        </div>
    </template>

    <!-- Empty State -->
    <template x-if="!loading && stockData.length === 0">
        <div class="text-center py-12">
            <div class="inline-flex flex-col items-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-box-open text-gray-400 text-2xl"></i>
                </div>
                <p class="text-gray-600 font-medium mb-2">Tidak ada data stok</p>
                <p class="text-sm text-gray-500">Coba ubah filter atau periode tanggal</p>
            </div>
        </div>
    </template>

    <!-- Mobile Cards yang Lebih Modern -->
    <div class="space-y-4" x-data="{ expandedCard: null }">
        <template x-for="(stockItem, index) in stockData" :key="stockItem.item + stockItem.storage">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden"
                :class="{ 'ring-2 ring-blue-500 ring-opacity-50': expandedCard === index }">

                <!-- Card Header yang Lebih Informatif -->
                <div class="p-4 border-b border-gray-100">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-bold"
                                x-text="index + 1"></div>
                            <div>
                                <h3 class="text-sm font-semibold text-gray-800" x-text="stockItem.item"></h3>
                                <p class="text-xs text-gray-500" x-text="stockItem.storage || 'Semua Chiller'"></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold"
                                :class="{
                                     'text-green-600': parseFloat(stockItem.stock_after_adj_kg) > 0,
                                     'text-red-600': parseFloat(stockItem.stock_after_adj_kg) < 0,
                                     'text-gray-500': parseFloat(stockItem.stock_after_adj_kg) === 0
                                 }"
                                x-text="parseFloat(stockItem.stock_after_adj_kg).toFixed(2) + ' Kg'">
                            </div>
                            <p class="text-xs text-gray-500" x-text="stockItem.stock_after_adj_ep + ' E/P'"></p>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="flex space-x-2 mt-3">
                        <button @click="expandedCard = expandedCard === index ? null : index"
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium py-2 rounded-lg transition-colors flex items-center justify-center space-x-1">
                            <i class="fas" :class="expandedCard === index ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                            <span x-text="expandedCard === index ? 'Sembunyikan' : 'Detail'"></span>
                        </button>
                        <button @click="openTracingPage(stockItem.item, selectedStorage)"
                            class="flex-1 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs font-medium py-2 rounded-lg transition-colors flex items-center justify-center space-x-1">
                            <i class="fas fa-search"></i>
                            <span>Tracing</span>
                        </button>
                    </div>
                </div>

                <!-- Expanded Content dengan Progress Steps -->
                <div x-show="expandedCard === index" x-collapse class="border-t border-gray-100">
                    <div class="p-4">
                        <!-- Progress Visualization -->
                        <div class="mb-6">
                            <div class="flex items-center justify-between text-xs text-gray-500 mb-2">
                                <span>Stok Awal</span>
                                <span>Masuk</span>
                                <span>Keluar</span>
                                <span>Adjustment</span>
                                <span class="font-semibold text-gray-700">Stok Akhir</span>
                            </div>
                            <div class="flex items-center">
                                <div class="h-2 bg-blue-400 rounded-l-full flex-1"></div>
                                <div class="h-2 bg-green-400 flex-1"></div>
                                <div class="h-2 bg-red-400 flex-1"></div>
                                <div class="h-2 bg-yellow-400 flex-1"></div>
                                <div class="h-2 bg-purple-500 rounded-r-full flex-1"></div>
                            </div>
                        </div>

                        <!-- Detail Grid yang Lebih Rapi -->
                        <div class="grid grid-cols-2 gap-3">
                            <!-- Stok Awal -->
                            <div class="bg-blue-50 rounded-lg p-3 border border-blue-100">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-semibold text-blue-700">Stok Awal</span>
                                    <i class="fas fa-play text-blue-500 text-xs"></i>
                                </div>
                                <div class="space-y-1">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-blue-600">E/P:</span>
                                        <span class="font-medium" x-text="stockItem.initial_ep"></span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-blue-600">Kg:</span>
                                        <span class="font-medium" x-text="parseFloat(stockItem.initial_kg).toFixed(2)"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Masuk -->
                            <div class="bg-green-50 rounded-lg p-3 border border-green-100">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-semibold text-green-700">Masuk</span>
                                    <i class="fas fa-arrow-down text-green-500 text-xs"></i>
                                </div>
                                <div class="space-y-1">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-green-600">E/P:</span>
                                        <span class="font-medium" x-text="stockItem.in_ep"></span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-green-600">Kg:</span>
                                        <span class="font-medium" x-text="parseFloat(stockItem.in_kg).toFixed(2)"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Keluar -->
                            <div class="bg-red-50 rounded-lg p-3 border border-red-100">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-semibold text-red-700">Keluar</span>
                                    <i class="fas fa-arrow-up text-red-500 text-xs"></i>
                                </div>
                                <div class="space-y-1">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-red-600">E/P:</span>
                                        <span class="font-medium" x-text="'-' + stockItem.out_ep"></span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-red-600">Kg:</span>
                                        <span class="font-medium" x-text="'-' + parseFloat(stockItem.out_kg).toFixed(2)"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Adjustment -->
                            <div class="bg-yellow-50 rounded-lg p-3 border border-yellow-100">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-semibold text-yellow-700">Adjustment</span>
                                    <i class="fas fa-adjust text-yellow-500 text-xs"></i>
                                </div>
                                <div class="space-y-1">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-yellow-600">E/P:</span>
                                        <span class="font-medium" x-text="stockItem.adjustment_ep"></span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-yellow-600">Kg:</span>
                                        <span class="font-medium" x-text="parseFloat(stockItem.adjustment_kg).toFixed(2)"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Stok Akhir Section -->
                        <div class="mt-4 bg-gradient-to-r from-purple-50 to-blue-50 rounded-lg p-4 border border-purple-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-purple-800 mb-1">Stok Akhir</p>
                                    <p class="text-xs text-purple-600">Setelah semua proses</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-purple-700"
                                        x-text="stockItem.stock_after_adj_ep + ' E/P'"></p>
                                    <p class="text-sm font-semibold text-purple-600"
                                        x-text="parseFloat(stockItem.stock_after_adj_kg).toFixed(2) + ' Kg'"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-4 flex space-x-2">
                            <button @click="openTracingPage(stockItem.item, selectedStorage)"
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-3 rounded-lg transition-all duration-200 flex items-center justify-center space-x-2 shadow-md">
                                <i class="fas fa-chart-line"></i>
                                <span>Lihat Detail Tracing</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Footer dengan Total -->
    <div class="mt-6 bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 border border-gray-200">
        <h3 class="text-sm font-semibold text-gray-800 mb-3 text-center">Ringkasan Total</h3>
        <div class="grid grid-cols-2 gap-4">
            <div class="text-center bg-white rounded-lg p-3 border border-gray-200">
                <p class="text-xs text-gray-500 mb-1">Total E/P</p>
                <p class="text-lg font-bold text-gray-800" x-text="totals.stock_after_adj_ep"></p>
            </div>
            <div class="text-center bg-white rounded-lg p-3 border border-gray-200">
                <p class="text-xs text-gray-500 mb-1">Total Kg</p>
                <p class="text-lg font-bold text-gray-800" x-text="parseFloat(totals.stock_after_adj_kg).toFixed(2)"></p>
            </div>
        </div>
    </div>
</div>