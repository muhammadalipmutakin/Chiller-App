<div x-data="{ showInitial: false, showIn: false, showOut: false, showBeforeAdj: false, showAdjustment: false, stockFilter: 'all' }" class="bg-white shadow-lg rounded-lg p-4 sm:p-6">
    <div class="flex flex-row justify-between items-center mb-4">
        <h2 class="text-lg sm:text-xl font-semibold text-gray-700">Daftar Stok</h2>
        <button @click="exportToExcel()" class="px-3 py-1.5 bg-green-500 text-white text-xs rounded-md shadow-md hover:bg-green-600 transition-colors duration-200 flex items-center gap-1.5">
            <i class="fas fa-file-excel"></i>
            <span>Unduh Excel</span>
        </button>
    </div>

    <div class="mb-3 flex flex-wrap items-center gap-3">
        <div class="flex flex-wrap gap-x-3 gap-y-1 md:hidden">
            <label class="inline-flex items-center">
                <input type="checkbox" x-model="showInitial" class="form-checkbox h-3.5 w-3.5 text-blue-600 rounded">
                <span class="ml-1 text-gray-700 text-xs">Stok Awal</span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" x-model="showIn" class="form-checkbox h-3.5 w-3.5 text-green-600 rounded">
                <span class="ml-1 text-gray-700 text-xs">Masuk</span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" x-model="showOut" class="form-checkbox h-3.5 w-3.5 text-red-600 rounded">
                <span class="ml-1 text-gray-700 text-xs">Keluar</span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" x-model="showBeforeAdj" class="form-checkbox h-3.5 w-3.5 text-purple-600 rounded">
                <span class="ml-1 text-gray-700 text-xs">Stok Akhir (Sblm Adj)</span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" x-model="showAdjustment" class="form-checkbox h-3.5 w-3.5 text-yellow-600 rounded">
                <span class="ml-1 text-gray-700 text-xs">Adjustment</span>
            </label>
        </div>

        <div class="ml-auto flex items-center gap-2">
            <label for="stockFilter" class="text-xs font-medium text-gray-700 whitespace-nowrap">Stok Akhir:</label>
            <select id="stockFilter" x-model="stockFilter" @change="applyFilters" class="block w-auto pl-2 pr-8 py-1.5 border-black border-2 text-xs rounded-md">
                <option value="all">Semua</option>
                <option value="positive">Positif</option>
                <option value="negative">Negatif</option>
                <option value="zero">Nol (0)</option>
            </select>
        </div>

        <label class="inline-flex items-center">
            <input type="checkbox" x-model="hideZeroStock" @change="applyFilters" class="form-checkbox h-3.5 w-3.5 text-gray-600 rounded">
            <span class="ml-1 text-gray-700 text-xs whitespace-nowrap">Sembunyikan Stok Nol (0)</span>
        </label>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <style>
            .full-row-hover tr:hover td {
                background-color: #bfdbfe;
                /* bg-blue-200 */
                cursor: pointer;
            }

            .full-row-hover tr:hover td.bg-blue-200 {
                background-color: #bfdbfe;
                /* slightly darker for header cells (optional adjustment, kept original color value) */
            }
        </style>
        <table class="min-w-full border-collapse full-row-hover">
            <thead>
                <tr class="bg-gray-50">
                    <th rowspan="2"
                        class="px-2 py-1.5 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">
                        No.</th>
                    <th rowspan="2"
                        class="px-2 py-1.5 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">
                        Item</th>
                    <th colspan="2"
                        class="px-2 py-1.5 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200 bg-blue-100 sm:table-cell"
                        :class="{'hidden': !showInitial && window.innerWidth < 640 }">
                        Stok Awal</th>
                    <th colspan="2"
                        class="px-2 py-1.5 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200 bg-green-100 sm:table-cell"
                        :class="{'hidden': !showIn && window.innerWidth < 640 }">
                        Masuk</th>
                    <th colspan="2"
                        class="px-2 py-1.5 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200 bg-red-100 sm:table-cell"
                        :class="{'hidden': !showOut && window.innerWidth < 640 }">
                        Keluar</th>
                    <th colspan="2"
                        class="px-2 py-1.5 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200 bg-orange-100 sm:table-cell"
                        :class="{'hidden': !showBeforeAdj && window.innerWidth < 640 }">
                        Stok Akhir (Sebelum Adj)</th>
                    <th colspan="2"
                        class="px-2 py-1.5 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200 bg-yellow-100 sm:table-cell"
                        :class="{'hidden': !showAdjustment && window.innerWidth < 640 }">
                        Adjustment</th>
                    <th colspan="2"
                        class="px-2 py-1.5 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200 bg-purple-100">
                        Stok Saat Ini</th>
                </tr>
                <tr class="bg-gray-50">
                    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider border-b border-gray-200 bg-blue-100 sm:table-cell"
                        :class="{'hidden': !showInitial && window.innerWidth < 640 }">
                        E/P</th>
                    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider border-b border-gray-200 bg-blue-100 sm:table-cell"
                        :class="{'hidden': !showInitial && window.innerWidth < 640 }">
                        Kg</th>
                    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider border-b border-gray-200 bg-green-100 sm:table-cell"
                        :class="{'hidden': !showIn && window.innerWidth < 640 }">
                        E/P</th>
                    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider border-b border-gray-200 bg-green-100 sm:table-cell"
                        :class="{'hidden': !showIn && window.innerWidth < 640 }">
                        Kg</th>
                    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider border-b border-gray-200 bg-red-100 sm:table-cell"
                        :class="{'hidden': !showOut && window.innerWidth < 640 }">
                        E/P</th>
                    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider border-b border-gray-200 bg-red-100 sm:table-cell"
                        :class="{'hidden': !showOut && window.innerWidth < 640 }">
                        Kg</th>
                    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider border-b border-gray-200 bg-orange-100 sm:table-cell"
                        :class="{'hidden': !showBeforeAdj && window.innerWidth < 640 }">
                        E/P</th>
                    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider border-b border-gray-200 bg-orange-100 sm:table-cell"
                        :class="{'hidden': !showBeforeAdj && window.innerWidth < 640 }">
                        Kg</th>
                    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider border-b border-gray-200 bg-yellow-100 sm:table-cell"
                        :class="{'hidden': !showAdjustment && window.innerWidth < 640 }">
                        E/P</th>
                    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider border-b border-gray-200 bg-yellow-100 sm:table-cell"
                        :class="{'hidden': !showAdjustment && window.innerWidth < 640 }">
                        Kg</th>
                    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider border-b border-gray-200 bg-purple-100">
                        E/P</th>
                    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider border-b border-gray-200 bg-purple-100">
                        Kg</th>
                </tr>
                <tr class="bg-gray-100 font-bold">
                    <th class="px-2 py-1.5 text-xs whitespace-nowrap text-gray-900 border-b border-gray-300" colspan="2">Total</th>
                    <th class="px-2 py-1.5 text-xs whitespace-nowrap text-gray-900 border-b border-gray-300 bg-blue-200 sm:table-cell text-center"
                        :class="{'hidden': !showInitial && window.innerWidth < 640 }"
                        x-text="totals.initial_ep"></th>
                    <th class="px-2 py-1.5 text-xs whitespace-nowrap text-gray-900 border-b border-gray-300 bg-blue-200 sm:table-cell text-center"
                        :class="{'hidden': !showInitial && window.innerWidth < 640 }"
                        x-text="parseFloat(totals.initial_kg).toFixed(2)"></th>
                    <th class="px-2 py-1.5 text-xs whitespace-nowrap text-gray-900 border-b border-gray-300 bg-green-200 sm:table-cell text-right"
                        :class="{'hidden': !showIn && window.innerWidth < 640 }"
                        x-text="totals.in_ep"></th>
                    <th class="px-2 py-1.5 text-xs whitespace-nowrap text-gray-900 border-b border-gray-300 bg-green-200 sm:table-cell text-right"
                        :class="{'hidden': !showIn && window.innerWidth < 640 }"
                        x-text="parseFloat(totals.in_kg).toFixed(2)"></th>
                    <th class="px-2 py-1.5 text-xs whitespace-nowrap text-gray-900 border-b border-gray-300 bg-red-200 sm:table-cell text-right"
                        :class="{'hidden': !showOut && window.innerWidth < 640 }"
                        x-text="`-` + totals.out_ep"></th>
                    <th class="px-2 py-1.5 text-xs whitespace-nowrap text-gray-900 border-b border-gray-300 bg-red-200 sm:table-cell text-right"
                        :class="{'hidden': !showOut && window.innerWidth < 640 }"
                        x-text="`-` + parseFloat(totals.out_kg).toFixed(2)"></th>
                    <th class="px-2 py-1.5 text-xs whitespace-nowrap text-gray-900 border-b border-gray-300 bg-orange-200 sm:table-cell text-right"
                        :class="{'hidden': !showBeforeAdj && window.innerWidth < 640 }"
                        x-text="totals.stock_end_before_adj_ep"></th>
                    <th class="px-2 py-1.5 text-xs whitespace-nowrap text-gray-900 border-b border-gray-300 bg-orange-200 sm:table-cell text-right"
                        :class="{'hidden': !showBeforeAdj && window.innerWidth < 640 }"
                        x-text="parseFloat(totals.stock_end_before_adj_kg).toFixed(2)"></th>
                    <th class="px-2 py-1.5 text-xs whitespace-nowrap text-gray-900 border-b border-gray-300 bg-yellow-200 sm:table-cell text-right"
                        :class="{'hidden': !showAdjustment && window.innerWidth < 640 }"
                        x-text="totals.adjustment_ep"></th>
                    <th class="px-2 py-1.5 text-xs whitespace-nowrap text-gray-900 border-b border-gray-300 bg-yellow-200 sm:table-cell text-right"
                        :class="{'hidden': !showAdjustment && window.innerWidth < 640 }"
                        x-text="parseFloat(totals.adjustment_kg).toFixed(2)"></th>
                    <th class="px-2 py-1.5 text-xs whitespace-nowrap text-gray-900 border-b border-gray-300 bg-purple-200 text-right"
                        x-text="totals.stock_after_adj_ep"></th>
                    <th class="px-2 py-1.5 text-xs whitespace-nowrap text-gray-900 border-b border-gray-300 bg-purple-200 text-right"
                        x-text="parseFloat(totals.stock_after_adj_kg).toFixed(2)"></th>
                </tr>
            </thead>
            <tbody id="stockTableBody">
                <template x-if="loading">
                    <tr>
                        <td colspan="12" class="px-2 py-1.5 text-center text-xs text-gray-500 border-b border-gray-200">
                            Memuat data...</td>
                    </tr>
                </template>
                <template x-if="!loading && stockData.length === 0">
                    <tr>
                        <td colspan="12" class="px-2 py-1.5 text-center text-xs text-gray-500 border-b border-gray-200">
                            Tidak ada data stok ditemukan.</td>
                    </tr>
                </template>
                <template x-for="(stockItem, index) in stockData" :key="stockItem.item + stockItem.storage">
                    <tr class="even:bg-gray-50 transition-colors duration-150">
                        <td class="px-2 py-1.5 rounded-2xl bg-blue-200 text-center whitespace-nowrap text-xs text-gray-800 border-b border-gray-200"
                            @click="openTracingPage(stockItem.item, selectedStorage)">
                            <span x-text="index + 1"></span>
                        </td>
                        <td class="px-2 py-1.5 whitespace-nowrap text-xs text-gray-800 border-b border-gray-200"
                            x-text="stockItem.item">
                        </td>
                        <td class="px-2 py-1.5 text-right whitespace-nowrap text-xs text-gray-800 border-b border-gray-200 bg-blue-100 sm:table-cell"
                            :class="{'hidden': !showInitial && window.innerWidth < 640 }"
                            x-text="stockItem.initial_ep"></td>
                        <td class="px-2 py-1.5 text-right whitespace-nowrap text-xs text-gray-800 border-b border-gray-200 bg-blue-100 sm:table-cell"
                            :class="{'hidden': !showInitial && window.innerWidth < 640 }"
                            x-text="parseFloat(stockItem.initial_kg).toFixed(2)"></td>
                        <td class="px-2 py-1.5 text-right whitespace-nowrap text-xs text-gray-800 border-b border-gray-200 bg-green-100 sm:table-cell"
                            :class="{'hidden': !showIn && window.innerWidth < 640 }"
                            x-text="stockItem.in_ep"></td>
                        <td class="px-2 py-1.5 text-right whitespace-nowrap text-xs text-gray-800 border-b border-gray-200 bg-green-100 sm:table-cell"
                            :class="{'hidden': !showIn && window.innerWidth < 640 }"
                            x-text="parseFloat(stockItem.in_kg).toFixed(2)"></td>
                        <td class="px-2 py-1.5 text-right whitespace-nowrap text-xs text-gray-800 border-b border-gray-200 bg-red-100 sm:table-cell"
                            :class="{'hidden': !showOut && window.innerWidth < 640 }"
                            x-text="`-` + stockItem.out_ep"></td>
                        <td class="px-2 py-1.5 text-right whitespace-nowrap text-xs text-gray-800 border-b border-gray-200 bg-red-100 sm:table-cell"
                            :class="{'hidden': !showOut && window.innerWidth < 640 }"
                            x-text="`-` + parseFloat(stockItem.out_kg).toFixed(2)"></td>
                        <td class="px-2 py-1.5 text-right whitespace-nowrap text-xs text-gray-800 border-b border-gray-200 bg-orange-100 sm:table-cell"
                            :class="{'hidden': !showBeforeAdj && window.innerWidth < 640 }"
                            x-text="stockItem.stock_end_before_adj_ep"></td>
                        <td class="px-2 py-1.5 text-right whitespace-nowrap text-xs text-gray-800 border-b border-gray-200 bg-orange-100 sm:table-cell"
                            :class="{'hidden': !showBeforeAdj && window.innerWidth < 640 }"
                            x-text="parseFloat(stockItem.stock_end_before_adj_kg).toFixed(2)"></td>
                        <td class="px-2 py-1.5 text-right whitespace-nowrap text-xs text-gray-800 border-b border-gray-200 bg-yellow-100 sm:table-cell"
                            :class="{'hidden': !showAdjustment && window.innerWidth < 640 }"
                            x-text="stockItem.adjustment_ep"></td>
                        <td class="px-2 py-1.5 text-right whitespace-nowrap text-xs text-gray-800 border-b border-gray-200 bg-yellow-100 sm:table-cell"
                            :class="{'hidden': !showAdjustment && window.innerWidth < 640 }"
                            x-text="parseFloat(stockItem.adjustment_kg).toFixed(2)"></td>
                        <td class="px-2 py-1.5 text-right whitespace-nowrap text-xs text-gray-800 border-b border-gray-200 bg-purple-100"
                            x-text="stockItem.stock_after_adj_ep"></td>
                        <td class="px-2 py-1.5 text-right whitespace-nowrap text-xs text-gray-800 border-b border-gray-200 bg-purple-100"
                            x-text="parseFloat(stockItem.stock_after_adj_kg).toFixed(2)"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>