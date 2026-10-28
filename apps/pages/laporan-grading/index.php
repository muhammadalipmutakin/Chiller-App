<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<main class="flex-1 min-w-0 p-3 sm:p-4 md:p-6 transition-all duration-300" x-data="laporanGradingApp()" x-init="init()">
    <!-- Header Page -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-layer-group text-blue-600"></i>
                Laporan Hasil Grading
            </h1>
        </div>

        <div>
            <button @click="exportToExcel()" class="w-full sm:w-auto px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-semibold rounded-lg shadow-sm transition duration-200 flex items-center justify-center gap-2 cursor-pointer">
                <i class="fas fa-file-excel"></i>
                <span>Unduh Excel</span>
            </button>
        </div>
    </div>

    <!-- Filter Section Card (Responsif Tablet & Mobile) -->
    <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-3.5 mb-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2.5 items-end">
            <div>
                <label for="startDate" class="text-gray-700 font-medium text-xs mb-1 block">Dari Tanggal</label>
                <input type="date" id="startDate" x-model="startDate"
                    class="p-2 text-xs sm:text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none w-full">
            </div>

            <div>
                <label for="endDate" class="text-gray-700 font-medium text-xs mb-1 block">Sampai Tanggal</label>
                <input type="date" id="endDate" x-model="endDate"
                    class="p-2 text-xs sm:text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none w-full">
            </div>

            <div>
                <label for="storage" class="text-gray-700 font-medium text-xs mb-1 block">Penyimpanan / Chiller</label>
                <select id="storage" x-model="selectedStorage"
                    class="p-2 text-xs sm:text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none w-full bg-white">
                    <option value="Semua">Semua Chiller</option>
                    <option value="Chiller 1">Chiller 1</option>
                    <option value="Chiller 2">Chiller 2</option>
                    <option value="Chiller 3">Chiller 3</option>
                </select>
            </div>

            <div>
                <label for="filterSubitem" class="text-gray-700 font-medium text-xs mb-1 flex items-center justify-between">
                    <span>Subitem</span>
                    <span class="text-[10px] text-blue-600 font-bold" x-show="selectedSubitem !== 'Semua'">(Aktif)</span>
                </label>
                <select id="filterSubitem" x-model="selectedSubitem"
                    class="p-2 text-xs sm:text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white w-full">
                    <option value="Semua">Semua Subitem</option>
                    <option value="__NO_SUBITEM__">Tanpa Subitem</option>
                    <template x-for="sub in availableSubitems" :key="sub">
                        <option :value="sub" x-text="sub"></option>
                    </template>
                </select>
            </div>

            <div>
                <label for="searchQuery" class="text-gray-700 font-medium text-xs mb-1 block">Cari Item / Lokasi</label>
                <input type="text" id="searchQuery" x-model="searchQuery" placeholder="Ketik kata kunci..."
                    class="p-2 text-xs sm:text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none w-full">
            </div>

            <div class="flex gap-2">
                <button @click="fetchData()" :disabled="loading"
                    class="flex-1 px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-semibold rounded-lg shadow-xs transition duration-200 flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fas fa-filter text-[11px]" x-show="!loading"></i>
                    <i class="fas fa-spinner fa-spin text-[11px]" x-show="loading"></i>
                    <span>Terapkan</span>
                </button>
                <button @click="resetFilter()"
                    class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs sm:text-sm font-semibold rounded-lg transition duration-200 cursor-pointer border border-gray-300" title="Reset Filter">
                    <i class="fas fa-undo"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Summary KPI Cards (Proporsional & Ringan) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
        <div class="bg-white p-3.5 rounded-xl border border-blue-100 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-lg shrink-0">
                <i class="fas fa-boxes-stacked"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] text-gray-500 font-medium truncate">Masuk Grading (Ekor)</p>
                <h3 class="text-base sm:text-lg font-bold text-gray-800" x-text="formatNumber(filteredTotalsIn.ekor)"></h3>
                <span class="text-[10px] text-blue-600 font-semibold" x-text="`${filteredTotalsIn.transaksi} Transaksi`"></span>
            </div>
        </div>

        <div class="bg-white p-3.5 rounded-xl border border-blue-100 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-lg shrink-0">
                <i class="fas fa-weight-hanging"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] text-gray-500 font-medium truncate">Masuk Grading (Kg)</p>
                <h3 class="text-base sm:text-lg font-bold text-blue-600" x-text="formatNumber(filteredTotalsIn.kg, 2) + ' Kg'"></h3>
                <span class="text-[10px] text-gray-400">Total Netto</span>
            </div>
        </div>

        <div class="bg-white p-3.5 rounded-xl border border-rose-100 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-lg shrink-0">
                <i class="fas fa-truck-ramp-box"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] text-gray-500 font-medium truncate">Distribusi OUT CS (Ekor)</p>
                <h3 class="text-base sm:text-lg font-bold text-gray-800" x-text="formatNumber(totals.out.ekor)"></h3>
                <span class="text-[10px] text-rose-600 font-semibold" x-text="`${totals.out.transaksi} Transaksi`"></span>
            </div>
        </div>

        <div class="bg-white p-3.5 rounded-xl border border-rose-100 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-lg shrink-0">
                <i class="fas fa-scale-unbalanced-flip"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] text-gray-500 font-medium truncate">Distribusi OUT CS (Kg)</p>
                <h3 class="text-base sm:text-lg font-bold text-rose-600" x-text="formatNumber(totals.out.kg, 2) + ' Kg'"></h3>
                <span class="text-[10px] text-gray-400">Total Netto</span>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-gray-200 mb-5">
        <button @click="activeTab = 'grading_in'"
            :class="activeTab === 'grading_in' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700'"
            class="py-2.5 px-3 sm:px-4 border-b-2 text-xs sm:text-sm flex items-center gap-2 transition duration-150 cursor-pointer">
            <i class="fas fa-arrow-down-left text-xs"></i>
            <span>Rekap Item Dari Grading</span>
            <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-[11px] font-bold" x-text="filteredGradingIn.length"></span>
        </button>

        <button @click="activeTab = 'out_cs'"
            :class="activeTab === 'out_cs' ? 'border-rose-600 text-rose-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700'"
            class="py-2.5 px-3 sm:px-4 border-b-2 text-xs sm:text-sm flex items-center gap-2 transition duration-150 cursor-pointer">
            <i class="fas fa-arrow-up-right text-xs"></i>
            <span>OUT Chiller Per Regu</span>
            <span class="bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full text-[11px] font-bold" x-text="filteredOutCs.length"></span>
        </button>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 1: DAFTAR ITEM MASUK GRADING                                          -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'grading_in'">
        <div class="hidden md:block bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-3 py-2.5 text-center text-xs font-bold text-gray-600 uppercase w-14">No.</th>
                            <th class="px-4 py-2.5 text-left text-xs font-bold text-gray-600 uppercase">Item</th>
                            <th class="px-3 py-2.5 text-left text-xs font-bold text-gray-600 uppercase">Subitem</th>
                            <th class="px-3 py-2.5 text-left text-xs font-bold text-gray-600 uppercase">Penyimpanan</th>
                            <th class="px-3 py-2.5 text-left text-xs font-bold text-gray-600 uppercase">Asal</th>
                            <th class="px-3 py-2.5 text-center text-xs font-bold text-gray-600 uppercase">Transaksi</th>
                            <th class="px-4 py-2.5 text-right text-xs font-bold text-blue-800 uppercase bg-blue-50/70">Total Ekor/Pack</th>
                            <th class="px-4 py-2.5 text-right text-xs font-bold text-blue-900 uppercase bg-blue-100/70">Total Berat (Kg)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-if="loading">
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-gray-500 text-xs sm:text-sm">
                                    <i class="fas fa-spinner fa-spin text-blue-600 text-2xl mb-2"></i>
                                    <p>Memuat data grading...</p>
                                </td>
                            </tr>
                        </template>

                        <template x-if="!loading && filteredGradingIn.length === 0">
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-gray-500 text-xs sm:text-sm">
                                    <i class="fas fa-inbox text-gray-300 text-3xl mb-2"></i>
                                    <p>Tidak ada data grading ditemukan untuk filter ini.</p>
                                </td>
                            </tr>
                        </template>

                        <template x-for="(row, index) in filteredGradingIn" :key="index">
                            <tr class="hover:bg-blue-50/40 transition duration-150">
                                <td class="px-3 py-2 text-center">
                                    <button type="button" @click="openTransaksiGradingIn(row)"
                                        class="w-7 h-7 mx-auto rounded-lg bg-blue-100 hover:bg-blue-600 text-blue-700 hover:text-white text-xs font-bold transition flex items-center justify-center shadow-xs cursor-pointer group"
                                        title="Klik untuk melihat transaksi baris ini secara detail">
                                        <span x-text="index + 1" class="group-hover:hidden"></span>
                                        <i class="fas fa-external-link-alt text-[9px] hidden group-hover:inline"></i>
                                    </button>
                                </td>
                                <td class="px-4 py-2 text-xs font-bold text-gray-800" x-text="row.item_name"></td>
                                <td class="px-3 py-2 text-xs text-gray-700 font-semibold" x-text="row.subitem_name || '-'"></td>
                                <td class="px-3 py-2 text-xs text-gray-600">
                                    <span class="inline-block px-2.5 py-0.5 bg-gray-100 text-gray-700 border border-gray-200 rounded text-[11px] font-medium" x-text="row.storage || '-'"></span>
                                </td>
                                <td class="px-3 py-2 text-xs text-gray-600" x-text="row.from_location"></td>
                                <td class="px-3 py-2 text-xs text-center text-gray-700 font-semibold" x-text="row.total_transaksi"></td>
                                <td class="px-4 py-2 text-xs font-bold text-right text-blue-700 bg-blue-50/30" x-text="formatNumber(row.total_ekor)"></td>
                                <td class="px-4 py-2 text-xs font-extrabold text-right text-blue-900 bg-blue-100/30" x-text="formatNumber(row.total_kg, 2)"></td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="bg-gray-50 font-bold border-t-2 border-gray-200" x-show="filteredGradingIn.length > 0">
                        <tr>
                            <td colspan="5" class="px-4 py-2.5 text-xs text-gray-800 text-center uppercase tracking-wide">
                                TOTAL ITEM CHILLER DARI GRADING
                                <span class="text-blue-600 font-normal" x-show="selectedSubitem !== 'Semua'" x-text="`[Subitem: ${selectedSubitem === '__NO_SUBITEM__' ? 'Tanpa Subitem' : selectedSubitem}]`"></span>
                            </td>
                            <td class="px-3 py-2.5 text-xs text-center text-gray-900" x-text="filteredTotalsIn.transaksi"></td>
                            <td class="px-4 py-2.5 text-xs text-right text-blue-800 bg-blue-100/80" x-text="formatNumber(filteredTotalsIn.ekor)"></td>
                            <td class="px-4 py-2.5 text-xs text-right text-blue-950 bg-blue-200/80" x-text="formatNumber(filteredTotalsIn.kg, 2) + ' Kg'"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Mobile View Tab 1 -->
        <div class="block md:hidden space-y-2.5">
            <template x-for="(row, index) in filteredGradingIn" :key="index">
                <div class="bg-white rounded-xl border border-gray-200 p-3.5 shadow-xs">
                    <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                        <div class="flex items-center gap-2">
                            <button type="button" @click="openTransaksiGradingIn(row)"
                                class="w-6 h-6 rounded-md bg-blue-600 text-white text-xs font-bold flex items-center justify-center shadow-xs cursor-pointer"
                                title="Buka detail transaksi">
                                <span x-text="index + 1"></span>
                            </button>
                            <h3 class="text-xs sm:text-sm font-bold text-gray-800" x-text="row.item_name"></h3>
                        </div>
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded bg-blue-50 text-blue-700" x-text="row.storage"></span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 mt-2.5 text-xs">
                        <div>
                            <span class="text-gray-400 text-[11px]">Subitem:</span>
                            <p class="font-bold text-blue-700" x-text="row.subitem_name || '-'"></p>
                        </div>
                        <div>
                            <span class="text-gray-400 text-[11px]">Transaksi:</span>
                            <p class="font-semibold text-gray-800" x-text="`${row.total_transaksi} baris`"></p>
                        </div>
                        <div class="p-2 bg-blue-50 rounded-lg">
                            <span class="text-gray-500 text-[11px]">Ekor/Pack:</span>
                            <p class="text-xs sm:text-sm font-bold text-blue-700" x-text="formatNumber(row.total_ekor)"></p>
                        </div>
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <span class="text-gray-500 text-[11px]">Berat (Kg):</span>
                            <p class="text-xs sm:text-sm font-bold text-blue-900" x-text="formatNumber(row.total_kg, 2) + ' Kg'"></p>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 2: DISTRIBUSI OUT CHILLER PER REGU (HIERARKIS PER ROW BERSIH)         -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'out_cs'" style="display: none;">
        <template x-if="loading">
            <div class="bg-white rounded-xl p-10 text-center text-gray-500 text-xs sm:text-sm border border-gray-200">
                <i class="fas fa-spinner fa-spin text-rose-600 text-2xl mb-2"></i>
                <p class="font-medium">Memuat data distribusi CS...</p>
            </div>
        </template>

        <template x-if="!loading && filteredOutCs.length === 0">
            <div class="bg-white rounded-xl p-10 text-center text-gray-500 text-xs sm:text-sm border border-gray-200">
                <i class="fas fa-inbox text-gray-300 text-3xl mb-2"></i>
                <p class="font-medium">Tidak ada transaksi OUT CS ditemukan untuk periode ini.</p>
            </div>
        </template>

        <div class="space-y-4" x-show="!loading && filteredOutCs.length > 0">
            <template x-for="(posRow, posIndex) in filteredOutCs" :key="posIndex">
                <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden">
                    <!-- HEADER POS / TUJUAN -->
                    <div class="p-3 bg-gradient-to-r from-rose-50/80 via-gray-50 to-white border-b border-gray-200 flex flex-wrap items-center justify-between gap-2.5">
                        <div class="flex items-center gap-2.5">
                            <button type="button" @click="openTransaksiOutCs(posRow)"
                                class="w-7 h-7 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs flex items-center justify-center shadow-xs cursor-pointer transition group"
                                title="Klik untuk membuka transaksi pos ini di tab baru">
                                <span x-text="posIndex + 1" class="group-hover:hidden"></span>
                                <i class="fas fa-external-link-alt text-[9px] hidden group-hover:inline"></i>
                            </button>

                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-extrabold text-gray-900" x-text="posRow.to_location"></h3>
                                <span class="px-2 py-0.5 bg-gray-100 border border-gray-200 text-gray-700 rounded text-[11px] font-semibold" x-text="posRow.storage"></span>
                                <button type="button" @click="openTransaksiOutCs(posRow)"
                                    class="text-xs text-rose-600 hover:text-rose-800 font-semibold underline inline-flex items-center gap-1 cursor-pointer">
                                    <span>Lihat Transaksi</span>
                                    <i class="fas fa-arrow-up-right-from-square text-[9px]"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Ringkasan Pos -->
                        <div class="flex items-center gap-3 text-xs bg-white px-3 py-1 rounded-lg border border-gray-200 shadow-2xs">
                            <div>
                                <span class="text-gray-400 text-[10px] block">Transaksi</span>
                                <span class="font-bold text-gray-800" x-text="posRow.total_transaksi"></span>
                            </div>
                            <div class="border-l border-gray-200 pl-3">
                                <span class="text-gray-400 text-[10px] block">Total Ekor</span>
                                <span class="font-bold text-rose-700" x-text="formatNumber(posRow.total_ekor) + ' E/P'"></span>
                            </div>
                            <div class="border-l border-gray-200 pl-3">
                                <span class="text-gray-400 text-[10px] block">Total Berat</span>
                                <span class="font-extrabold text-rose-900" x-text="formatNumber(posRow.total_kg, 2) + ' Kg'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- TABEL RINCIAN ROW-BY-ROW DENGAN LINK PADA NOMOR URUT -->
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead class="bg-gray-50/90 border-b border-gray-200">
                                <tr>
                                    <th class="px-3 py-2 text-center text-[11px] font-bold text-gray-600 uppercase w-14">No</th>
                                    <th class="px-4 py-2 text-left text-[11px] font-bold text-gray-600 uppercase">Item</th>
                                    <th class="px-3 py-2 text-left text-[11px] font-bold text-gray-600 uppercase">Subitem</th>
                                    <th class="px-3 py-2 text-center text-[11px] font-bold text-gray-600 uppercase w-24">Transaksi</th>
                                    <th class="px-4 py-2 text-right text-[11px] font-bold text-rose-800 uppercase bg-rose-50/60">Total Ekor/Pack</th>
                                    <th class="px-4 py-2 text-right text-[11px] font-bold text-rose-900 uppercase bg-rose-100/60">Total Berat (Kg)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-xs">
                                <template x-for="(itemRow, itemIdx) in posRow.items_breakdown" :key="itemIdx">
                                    <tr class="hover:bg-rose-50/30 transition duration-150">
                                        <!-- Link Transaksi pada Nomor Urut -->
                                        <td class="px-3 py-1.5 text-center">
                                            <button type="button" @click="openTransaksiOutCs(posRow, itemRow)"
                                                class="w-6 h-6 mx-auto rounded-md bg-rose-50 hover:bg-rose-600 text-rose-700 hover:text-white border border-rose-200 transition text-[11px] font-bold flex items-center justify-center cursor-pointer group"
                                                title="Klik untuk melihat transaksi item ini">
                                                <span x-text="itemIdx + 1" class="group-hover:hidden"></span>
                                                <i class="fas fa-external-link-alt text-[8px] hidden group-hover:inline"></i>
                                            </button>
                                        </td>
                                        <td class="px-4 py-1.5 font-bold text-gray-800" x-text="itemRow.item_name"></td>
                                        <td class="px-3 py-1.5 text-gray-600 font-semibold" x-text="itemRow.subitem_name || '-'"></td>
                                        <td class="px-3 py-1.5 text-center font-semibold text-gray-700" x-text="itemRow.total_transaksi"></td>
                                        <td class="px-4 py-1.5 text-right font-bold text-rose-700 bg-rose-50/20" x-text="formatNumber(itemRow.total_ekor)"></td>
                                        <td class="px-4 py-1.5 text-right font-extrabold text-rose-950 bg-rose-100/20" x-text="formatNumber(itemRow.total_kg, 2)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>

            <!-- FOOTER TOTAL DISTRIBUSI OUT CS KESELURUHAN -->
            <div class="bg-gray-100 rounded-xl p-3.5 border border-gray-300 flex flex-wrap items-center justify-between gap-3 font-bold">
                <span class="text-xs uppercase text-gray-800 tracking-wider">TOTAL KESELURUHAN OUT CHILLER PER REGU (SELAIN ABF & EKSPEDISI)</span>
                <div class="flex items-center gap-5 text-xs">
                    <span>Total Transaksi: <strong class="text-gray-900" x-text="totals.out.transaksi"></strong></span>
                    <span>Total Ekor: <strong class="text-rose-700" x-text="formatNumber(totals.out.ekor) + ' E/P'"></strong></span>
                    <span>Total Berat: <strong class="text-rose-900" x-text="formatNumber(totals.out.kg, 2) + ' Kg'"></strong></span>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    function laporanGradingApp() {
        return {
            startDate: '',
            endDate: '',
            selectedStorage: 'Semua',
            selectedSubitem: 'Semua',
            searchQuery: '',
            activeTab: 'grading_in',
            loading: false,

            gradingInData: [],
            outCsData: [],
            totals: {
                in: {
                    transaksi: 0,
                    ekor: 0,
                    kg: 0
                },
                out: {
                    transaksi: 0,
                    ekor: 0,
                    kg: 0
                }
            },

            init() {
                this.setDefaultDates();
                this.fetchData();
            },

            setDefaultDates() {
                const today = new Date();
                const formattedToday = today.toISOString().slice(0, 10);
                this.startDate = formattedToday;
                this.endDate = formattedToday;
            },

            // Buka halaman transaksi untuk item masuk grading
            openTransaksiGradingIn(row) {
                const params = new URLSearchParams({
                    page: 'transaksi',
                    tanggal_awal: this.startDate,
                    tanggal_akhir: this.endDate,
                    type_transaksi: 'in',
                    from_location: row.from_location || 'Grading',
                    item: row.item_name || row.full_item_name
                });

                if (row.subitem_name && row.subitem_name !== '-' && row.subitem_name.trim() !== '') {
                    params.append('subitem', row.subitem_name.trim());
                }

                if (row.storage && row.storage !== 'Semua') {
                    params.append('storage', row.storage);
                }
                window.open(`?${params.toString()}`, '_blank');
            },

            // Buka halaman transaksi untuk OUT CS per pos / item
            openTransaksiOutCs(posRow, itemDetail = null) {
                const params = new URLSearchParams({
                    page: 'transaksi',
                    tanggal_awal: this.startDate,
                    tanggal_akhir: this.endDate,
                    type_transaksi: 'out',
                    to_location: posRow.to_location
                });

                if (posRow.storage && posRow.storage !== 'Semua') {
                    params.append('storage', posRow.storage);
                }

                if (itemDetail) {
                    params.append('item', itemDetail.item_name || itemDetail.full_item);
                    if (itemDetail.subitem_name && itemDetail.subitem_name !== '-' && itemDetail.subitem_name.trim() !== '') {
                        params.append('subitem', itemDetail.subitem_name.trim());
                    }
                }

                window.open(`?${params.toString()}`, '_blank');
            },

            async fetchData() {
                if (this.startDate && this.endDate && new Date(this.startDate) > new Date(this.endDate)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validasi Tanggal',
                        text: 'Tanggal akhir tidak boleh lebih kecil dari tanggal awal.'
                    });
                    return;
                }

                this.loading = true;
                try {
                    const params = new URLSearchParams({
                        action: 'get_grading_report',
                        startDate: this.startDate,
                        endDate: this.endDate,
                        storage: this.selectedStorage
                    });

                    const response = await fetch(`./controller/laporan/laporan_grading.php?${params.toString()}`);
                    const result = await response.json();

                    if (result.status === 'success') {
                        this.gradingInData = result.data.grading_in;
                        this.outCsData = result.data.out_cs;
                        this.totals = result.data.totals;
                    } else {
                        Swal.fire('Error', result.message || 'Gagal memuat data laporan.', 'error');
                        this.gradingInData = [];
                        this.outCsData = [];
                    }
                } catch (error) {
                    console.error('Fetch error:', error);
                    Swal.fire('Error', 'Terjadi kesalahan saat memuat data: ' + error.message, 'error');
                } finally {
                    this.loading = false;
                }
            },

            resetFilter() {
                this.setDefaultDates();
                this.selectedStorage = 'Semua';
                this.selectedSubitem = 'Semua';
                this.searchQuery = '';
                this.fetchData();
            },

            get availableSubitems() {
                const subs = new Set();
                this.gradingInData.forEach(item => {
                    if (item.subitem_name && item.subitem_name !== '-' && item.subitem_name.trim() !== '') {
                        subs.add(item.subitem_name.trim());
                    }
                });
                return Array.from(subs).sort((a, b) =>
                    a.localeCompare(b, undefined, {
                        numeric: true,
                        sensitivity: 'base'
                    })
                );
            },

            get filteredGradingIn() {
                let data = this.gradingInData;

                if (this.selectedSubitem !== 'Semua') {
                    if (this.selectedSubitem === '__NO_SUBITEM__') {
                        data = data.filter(item => !item.subitem_name || item.subitem_name === '-' || item.subitem_name === '');
                    } else {
                        data = data.filter(item => item.subitem_name === this.selectedSubitem);
                    }
                }

                if (this.searchQuery.trim()) {
                    const q = this.searchQuery.toLowerCase();
                    data = data.filter(item =>
                        (item.item_name && item.item_name.toLowerCase().includes(q)) ||
                        (item.subitem_name && item.subitem_name.toLowerCase().includes(q)) ||
                        (item.storage && item.storage.toLowerCase().includes(q)) ||
                        (item.from_location && item.from_location.toLowerCase().includes(q))
                    );
                }

                return data;
            },

            get filteredTotalsIn() {
                return this.filteredGradingIn.reduce((acc, row) => {
                    acc.transaksi += (row.total_transaksi || 0);
                    acc.ekor += (row.total_ekor || 0);
                    acc.kg += (row.total_kg || 0);
                    return acc;
                }, {
                    transaksi: 0,
                    ekor: 0,
                    kg: 0
                });
            },

            get filteredOutCs() {
                if (!this.searchQuery.trim()) return this.outCsData;
                const q = this.searchQuery.toLowerCase();
                return this.outCsData.filter(item =>
                    (item.to_location && item.to_location.toLowerCase().includes(q)) ||
                    (item.storage && item.storage.toLowerCase().includes(q))
                );
            },

            formatNumber(val, decimals = 0) {
                if (val === null || val === undefined || isNaN(val)) return '0';
                return parseFloat(val).toLocaleString('id-ID', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals
                });
            },

            // =========================================================================
            // EKSPOR EXCEL PROFESIONAL: AUTO-WIDTH, PEWARNAAN, DAN HIERARKI REGULER
            // =========================================================================
            exportToExcel() {
                if (this.filteredGradingIn.length === 0 && this.outCsData.length === 0) {
                    Swal.fire('Info', 'Tidak ada data untuk diekspor.', 'info');
                    return;
                }

                const workbook = XLSX.utils.book_new();

                // -------------------------------------------------------------
                // 1. SHEET 1: Rekap Item Dari Grading (IN)
                // -------------------------------------------------------------
                const subitemFilterText = this.selectedSubitem === '__NO_SUBITEM__' ? 'Tanpa Subitem' : this.selectedSubitem;
                const sheet1Headers = [
                    [`LAPORAN REKAP ITEM DARI GRADING (IN)`],
                    [`Periode: ${this.startDate} s/d ${this.endDate}`],
                    [`Storage: ${this.selectedStorage} | Filter Subitem: ${subitemFilterText}`],
                    [],
                    ['No.', 'Item', 'Subitem', 'Penyimpanan', 'Lokasi Asal', 'Jumlah Transaksi', 'Total Ekor/Pack', 'Total Berat (Kg)']
                ];

                const sheet1Rows = this.filteredGradingIn.map((row, idx) => [
                    idx + 1,
                    row.item_name,
                    row.subitem_name || '-',
                    row.storage || '-',
                    row.from_location,
                    row.total_transaksi,
                    row.total_ekor,
                    row.total_kg
                ]);

                const sheet1Total = [
                    'TOTAL', '', '', '', '',
                    this.filteredTotalsIn.transaksi,
                    this.filteredTotalsIn.ekor,
                    this.filteredTotalsIn.kg
                ];

                const ws1Data = [...sheet1Headers, ...sheet1Rows, [], sheet1Total];
                const ws1 = XLSX.utils.aoa_to_sheet(ws1Data);

                // Merges Judul & Total Sheet 1
                ws1['!merges'] = [{
                        s: {
                            r: 0,
                            c: 0
                        },
                        e: {
                            r: 0,
                            c: 7
                        }
                    },
                    {
                        s: {
                            r: 1,
                            c: 0
                        },
                        e: {
                            r: 1,
                            c: 7
                        }
                    },
                    {
                        s: {
                            r: 2,
                            c: 0
                        },
                        e: {
                            r: 2,
                            c: 7
                        }
                    },
                    {
                        s: {
                            r: ws1Data.length - 1,
                            c: 0
                        },
                        e: {
                            r: ws1Data.length - 1,
                            c: 4
                        }
                    }
                ];

                // Auto Column Width Sheet 1
                ws1['!cols'] = this.calculateAutoWidths(ws1Data);

                // Styling Sheet 1
                this.applySheetStyles(ws1, ws1Data, 4, '1E40AF', 'DBEAFE');
                XLSX.utils.book_append_sheet(workbook, ws1, 'Grading IN');

                // -------------------------------------------------------------
                // 2. SHEET 2: OUT Chiller Per Regu (Hierarkis / Berjenjang per Pos)
                // -------------------------------------------------------------
                const sheet2Data = [
                    [`LAPORAN DISTRIBUSI OUT CHILLER PER REGU`],
                    [`Periode: ${this.startDate} s/d ${this.endDate}`],
                    [`Storage: ${this.selectedStorage}`],
                    []
                ];

                const sheet2Merges = [{
                        s: {
                            r: 0,
                            c: 0
                        },
                        e: {
                            r: 0,
                            c: 5
                        }
                    },
                    {
                        s: {
                            r: 1,
                            c: 0
                        },
                        e: {
                            r: 1,
                            c: 5
                        }
                    },
                    {
                        s: {
                            r: 2,
                            c: 0
                        },
                        e: {
                            r: 2,
                            c: 5
                        }
                    }
                ];

                const headerRowIndexes = [];
                const groupHeaderIndexes = [];

                this.outCsData.forEach((pos) => {
                    const groupRowIdx = sheet2Data.length;
                    groupHeaderIndexes.push(groupRowIdx);

                    // Baris Header Pos Regu
                    sheet2Data.push([
                        `REGU TUJUAN: ${pos.to_location.toUpperCase()}`,
                        '',
                        `STORAGE: ${pos.storage}`,
                        `${pos.total_transaksi} Transaksi`,
                        `${this.formatNumber(pos.total_ekor)} E/P`,
                        `${this.formatNumber(pos.total_kg, 2)} Kg`
                    ]);

                    sheet2Merges.push({
                        s: {
                            r: groupRowIdx,
                            c: 0
                        },
                        e: {
                            r: groupRowIdx,
                            c: 1
                        }
                    });

                    // Sub-Header Tabel Item
                    const subHeaderIdx = sheet2Data.length;
                    headerRowIndexes.push(subHeaderIdx);
                    sheet2Data.push(['No.', 'Item', 'Subitem', 'Transaksi', 'Total Ekor/Pack', 'Total Berat (Kg)']);

                    // Baris Data Item
                    if (pos.items_breakdown && pos.items_breakdown.length > 0) {
                        pos.items_breakdown.forEach((item, subIdx) => {
                            sheet2Data.push([
                                subIdx + 1,
                                item.item_name,
                                item.subitem_name || '-',
                                item.total_transaksi,
                                item.total_ekor,
                                item.total_kg
                            ]);
                        });
                    }
                    sheet2Data.push([]); // Spasi antar pos
                });

                // Baris Grand Total Sheet 2
                const grandTotalRowIdx = sheet2Data.length;
                sheet2Data.push([
                    'TOTAL KESELURUHAN OUT CHILLER PER REGU', '', '',
                    this.totals.out.transaksi,
                    this.totals.out.ekor,
                    this.totals.out.kg
                ]);
                sheet2Merges.push({
                    s: {
                        r: grandTotalRowIdx,
                        c: 0
                    },
                    e: {
                        r: grandTotalRowIdx,
                        c: 2
                    }
                });

                const ws2 = XLSX.utils.aoa_to_sheet(sheet2Data);
                ws2['!merges'] = sheet2Merges;
                ws2['!cols'] = this.calculateAutoWidths(sheet2Data);

                // Styling Sheet 2
                this.applyGroupedSheetStyles(ws2, sheet2Data, groupHeaderIndexes, headerRowIndexes, grandTotalRowIdx);

                // Ubah Nama Sheet sesuai instruksi: 'OUT Chiller Per Regu'
                XLSX.utils.book_append_sheet(workbook, ws2, 'OUT Chiller Per Regu');

                // Download File Excel
                XLSX.writeFile(workbook, `Laporan_Grading_${this.startDate}_sd_${this.endDate}.xlsx`);
            },

            // Helper Auto-Width Kolom Sesuai Isi Karakter Terpanjang
            calculateAutoWidths(matrix) {
                const colWidths = [];
                matrix.forEach(row => {
                    row.forEach((cell, colIdx) => {
                        const len = cell !== null && cell !== undefined ? String(cell).length : 0;
                        colWidths[colIdx] = Math.max(colWidths[colIdx] || 8, len + 3);
                    });
                });
                return colWidths.map(w => ({
                    wch: Math.min(Math.max(w, 10), 45)
                }));
            },

            // Helper Styling untuk Sheet 1
            applySheetStyles(ws, data, headerRowIdx, themeColor, lightColor) {
                const range = XLSX.utils.decode_range(ws['!ref']);
                for (let R = range.s.r; R <= range.e.r; ++R) {
                    for (let C = range.s.c; C <= range.e.c; ++C) {
                        const cellRef = XLSX.utils.encode_cell({
                            r: R,
                            c: C
                        });
                        if (!ws[cellRef]) continue;

                        if (R <= 2) {
                            ws[cellRef].s = {
                                font: {
                                    bold: true,
                                    sz: R === 0 ? 14 : 11
                                },
                                alignment: {
                                    horizontal: 'left',
                                    vertical: 'center'
                                }
                            };
                        } else if (R === headerRowIdx) {
                            ws[cellRef].s = {
                                fill: {
                                    fgColor: {
                                        rgb: lightColor
                                    }
                                },
                                font: {
                                    bold: true,
                                    color: {
                                        rgb: themeColor
                                    }
                                },
                                alignment: {
                                    horizontal: C === 0 || C === 5 ? 'center' : (C >= 6 ? 'right' : 'left')
                                }
                            };
                        } else if (R === data.length - 1) {
                            ws[cellRef].s = {
                                font: {
                                    bold: true
                                },
                                fill: {
                                    fgColor: {
                                        rgb: 'F3F4F6'
                                    }
                                },
                                alignment: {
                                    horizontal: C >= 5 ? 'right' : 'center'
                                }
                            };
                        }
                    }
                }
            },

            // Helper Styling Khusus Berjenjang untuk Sheet 2
            applyGroupedSheetStyles(ws, data, groupRows, headerRows, grandTotalRowIdx) {
                const range = XLSX.utils.decode_range(ws['!ref']);
                for (let R = range.s.r; R <= range.e.r; ++R) {
                    for (let C = range.s.c; C <= range.e.c; ++C) {
                        const cellRef = XLSX.utils.encode_cell({
                            r: R,
                            c: C
                        });
                        if (!ws[cellRef]) continue;

                        if (R <= 2) {
                            ws[cellRef].s = {
                                font: {
                                    bold: true,
                                    sz: R === 0 ? 14 : 11
                                },
                                alignment: {
                                    horizontal: 'left'
                                }
                            };
                        } else if (groupRows.includes(R)) {
                            ws[cellRef].s = {
                                fill: {
                                    fgColor: {
                                        rgb: 'FFE4E6'
                                    }
                                }, // Rose Light Accent
                                font: {
                                    bold: true,
                                    color: {
                                        rgb: 'BE123C'
                                    }
                                },
                                alignment: {
                                    horizontal: C >= 3 ? 'right' : 'left'
                                }
                            };
                        } else if (headerRows.includes(R)) {
                            ws[cellRef].s = {
                                fill: {
                                    fgColor: {
                                        rgb: 'F3F4F6'
                                    }
                                },
                                font: {
                                    bold: true
                                },
                                alignment: {
                                    horizontal: C === 0 || C === 3 ? 'center' : (C >= 4 ? 'right' : 'left')
                                }
                            };
                        } else if (R === grandTotalRowIdx) {
                            ws[cellRef].s = {
                                font: {
                                    bold: true,
                                    sz: 12
                                },
                                fill: {
                                    fgColor: {
                                        rgb: 'E5E7EB'
                                    }
                                },
                                alignment: {
                                    horizontal: C >= 3 ? 'right' : 'left'
                                }
                            };
                        }
                    }
                }
            }
        }
    }
</script>