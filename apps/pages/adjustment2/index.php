<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<style>
    .highlight-existing-row {
        animation: pulse-highlight 2s ease-in-out;
        background-color: #fef3c7 !important;
        border-left: 4px solid #f59e0b !important;
    }

    @keyframes pulse-highlight {

        0%,
        100% {
            background-color: #fef3c7;
        }

        50% {
            background-color: #fde68a;
        }
    }
</style>
<main class="flex-1 min-w-0 transition-all duration-300" x-data="adjustmentApp()" x-init="init()" x-cloak>

    <div class="bg-white rounded-lg shadow-md p-4 mb-4">
        <h2 class="text-sm font-semibold text-gray-800 mb-3">Adjustment Barcode</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-4">
            <div>
                <label class="block text-xs font-medium text-gray-700">Tanggal Adjustment:</label>
                <input type="date" x-model="adjustmentDate"
                    class="w-full px-2 py-1 border rounded-md shadow-sm text-xs">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700">Storage:</label>
                <select x-model="selectedStorage" @change="onFilterChange()"
                    class="w-full px-2 py-1 border rounded-md shadow-sm text-xs">
                    <option value="">Pilih Storage</option>
                    <template x-for="storage in allStorages" :key="storage">
                        <option :value="storage" x-text="storage"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700">Tipe Adjustment:</label>
                <select x-model="adjustmentType"
                    class="w-full px-2 py-1 border rounded-md shadow-sm text-xs">
                    <option value="">Pilih Tipe</option>
                    <option value="stok_opname_mingguan">Stok Opname Mingguan</option>
                    <option value="stok_chiller_harian">Stok Chiller Harian</option>
                </select>
            </div>
        </div>
        <div class="flex justify-end">
            <button @click="resetAllForms" type="button"
                class="inline-flex items-center px-3 py-1 border border-red-300 text-xs font-medium rounded-md text-red-600 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-200">
                <i class="fas fa-undo mr-1"></i> Reset Semua Data
            </button>
        </div>
    </div>

    <div class="mb-3">
        <label for="userFilter" class="block text-xs font-medium text-gray-700">Filter by User:</label>
        <select id="userFilter" x-model="selectedUser" @change="onFilterChange()"
            class="mt-1 block w-full pl-2 pr-8 py-1 text-xs border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md">
            <option value="">Semua User</option>
            <?php
            // Pastikan $_SESSION["user_nama"] ada dan tidak kosong
            $currentUser = isset($_SESSION["user_nama"]) ? $_SESSION["user_nama"] : '';
            ?>
            <option value="<?php echo htmlspecialchars($currentUser); ?>" selected><?php echo htmlspecialchars($currentUser); ?></option>

            <template x-for="user in userOptions" :key="user">
                <template x-if="user !== '<?php echo htmlspecialchars($currentUser); ?>'">
                    <option :value="user" x-text="user"></option>
                </template>
            </template>
        </select>
    </div>
    <div class="overflow-x-auto bg-white rounded-lg shadow-md mb-6 table-container">
        <table class="min-w-full divide-y divide-gray-200 hidden sm:table">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-1 py-2 text-left text-xs font-medium uppercase tracking-wider min-w-[20px]">No</th>
                    <th class="px-1 py-2 text-left text-xs font-medium uppercase tracking-wider min-w-[100px]">Barcode</th>
                    <th class="px-1 py-2 text-left text-xs font-medium uppercase tracking-wider min-w-[80px]">Detail</th>
                    <th class="px-1 py-2 text-left text-xs font-medium uppercase tracking-wider bg-fuchsia-100 text-fuchsia-800 min-w-[100px]">Ekor Baru</th>
                    <th class="px-1 py-2 text-left text-xs font-medium uppercase tracking-wider bg-fuchsia-100 text-fuchsia-800 min-w-[100px]">Berat Baru</th>
                    <th class="px-1 py-2 text-left text-xs font-medium uppercase tracking-wider min-w-[200px]">Keterangan</th>
                    <th class="px-1 py-2 text-left text-xs font-medium uppercase tracking-wider min-w-[80px]">User Input</th>
                    <th class="px-1 py-2 text-left text-xs font-medium uppercase tracking-wider min-w-[80px]">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="(row, index) in adjustmentList" :key="row.id">
                    <tr :class="{'bg-yellow-50': row.isEdited}" class="hover:bg-gray-100 transition-colors" :data-row-id="row.id">
                        <td class="px-1 py-1 text-xs text-gray-900" x-text="index + 1"></td>
                        <td class="px-1 py-1 text-xs text-gray-900">
                            <div class="flex items-center space-x-1">
                                <input type="text" inputmode="numeric"
                                    pattern="[0-9]*" x-model="row.barcode" name="barcode"
                                    @keydown.enter.prevent="onBarcodeInputKeydown($event, row.id)"
                                    @keydown="if(event.key === 'Enter') event.preventDefault()"
                                    :disabled="isProcessing"
                                    class="w-full px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs"
                                    autocomplete="off">
                                <button type="button" @click="searchBarcode(row.id)"
                                    :disabled="isProcessing || !row.barcode"
                                    class="p-1 rounded-full text-white bg-blue-500 hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:bg-gray-400 transition-colors">
                                    <i class="fas fa-search text-xs"></i>
                                </button>
                            </div>
                        </td>
                        <td class="px-1 py-1 text-xs text-gray-700 detail-cell">
                            <div x-show="row.item">
                                <p>
                                    <strong class="text-xs">Item:</strong><br>
                                    <span x-text="row.item" class="text-xs"></span>
                                </p>
                                <p x-show="row.subitem">
                                    <strong class="text-xs">Sub-item:</strong><br>
                                    <span x-text="row.subitem" class="text-xs"></span>
                                </p>

                                <div class="flex items-center justify-between mt-1">
                                    <p class="text-xs text-gray-500">Detail Lanjut:</p>
                                    <button @click="row.isDetailsCollapsed = !row.isDetailsCollapsed" type="button"
                                        class="text-gray-400 hover:text-gray-600 focus:outline-none transition-colors duration-150 p-1">
                                        <i class="fas text-xs"
                                            :class="{'fa-plus-circle': row.isDetailsCollapsed, 'fa-minus-circle': !row.isDetailsCollapsed}"></i>
                                    </button>
                                </div>

                                <div x-show="!row.isDetailsCollapsed" x-transition.duration.300ms>
                                    <p>
                                        <strong class="text-xs">Storage:</strong><br>
                                        <span x-text="row.storage" class="text-xs"></span>
                                    </p>
                                    <p>
                                        <strong class="text-xs">Tanggal Masuk:</strong><br>
                                        <span x-text="row.created_at || '-'" class="text-xs"></span>
                                    </p>
                                    <p>
                                        <strong class="text-xs">User Masuk:</strong><br>
                                        <span x-text="row.user_in || '-'" class="text-xs"></span>
                                    </p>
                                    <p>
                                        <strong class="text-xs">Stok Saat Ini:</strong><br>
                                        <span x-text="row.ekor_stock_current || '0'" class="text-xs"></span> Ekor / <span
                                            x-text="(row.berat_stock_current) || '0.00'" class="text-xs"></span> KG
                                    </p>
                                    <p>
                                        <strong class="text-xs">Stok Item Global:</strong><br>
                                        <span x-text="row.ekor_item_global_stock || '0'" class="text-xs"></span> Ekor / <span
                                            x-text="(row.berat_item_global_stock) || '0.00'" class="text-xs"></span> KG
                                    </p>
                                    <p>
                                        <span x-text="row.id_in || '-'" class="text-xs"></span>
                                    </p>
                                </div>
                            </div>
                            <div x-show="isProcessing && row.barcode && !row.item"
                                class="text-gray-500 animate-pulse text-xs">Memuat...</div>
                        </td>
                        <td class="px-1 py-1">
                            <input type="text" inputmode="numeric"
                                pattern="[0-9]*" x-model.number="row.ekor_new" name="ekor_new" @input="updateRowStatus(row); saveToLocalStorage()"
                                class="w-full text-center px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs" autocomplete="off">
                        </td>
                        <td class="px-1 py-1">
                            <input type="text" inputmode="numeric"
                                pattern="[0-9]*" x-model.number="row.berat_new" name="berat_new" @input="updateRowStatus(row); saveToLocalStorage()"
                                class="w-full text-center px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs" autocomplete="off">
                        </td>
                        <td class="px-1 py-1">
                            <input type="text" x-model="row.keterangan" name="keterangan"
                                @input="updateRowStatus(row); saveToLocalStorage()"
                                class="w-full px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs">
                        </td>
                        <td class="px-1 py-1 text-xs text-gray-900" x-text="row.user_input || '-'"></td>
                        <td class="px-1 py-1 text-xs font-medium space-x-1">
                            <button @click="removeRow(row.id)"
                                class="text-red-600 hover:text-red-900 text-xs bg-red-50 hover:bg-red-100 px-2 py-1 rounded border border-red-200 transition-colors">
                                <i class="fas fa-trash mr-1"></i>Hapus
                            </button>
                        </td>
                    </tr>
                </template>
                <tr x-show="adjustmentList.length === 0">
                    <td colspan="8" class="px-4 py-3 whitespace-nowrap text-xs text-gray-500 text-center">No adjustments found.</td>
                </tr>
            </tbody>
        </table>

        <div class="sm:hidden grid grid-cols-1 md:grid-cols-2 gap-3 p-3">
            <template x-for="(row, index) in adjustmentList" :key="row.id">
                <div class="bg-white rounded-lg shadow-md p-3 border" :class="{'border-yellow-300 bg-yellow-50': row.isEdited}" :data-row-id="row.id">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-semibold text-gray-700">No: <span x-text="index + 1"></span></span>
                        <div class="space-x-1">
                            <button @click="removeRow(row.id)"
                                class="text-red-600 hover:text-red-900 text-xs font-medium bg-red-50 hover:bg-red-100 px-2 py-1 rounded border border-red-200 transition-colors">
                                <i class="fas fa-trash mr-1"></i>Hapus
                            </button>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Barcode</label>
                        <div class="flex items-center space-x-1">
                            <input type="text"
                                x-model="row.barcode"
                                name="barcode"
                                inputmode="numeric"
                                @keydown.enter.prevent="searchBarcode(row.id)"
                                :disabled="isProcessing"
                                class="w-full px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs" autocomplete="off">
                            <button type="button"
                                @click="searchBarcode(row.id)"
                                :disabled="isProcessing || !row.barcode"
                                class="p-1 rounded-full text-white bg-blue-500 hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:bg-gray-400 transition-colors">
                                <i class="fas fa-search text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <div class="border-t pt-2 mt-2">
                        <p x-show="row.item" class="mb-1 text-xs"><strong>Item:</strong> <span x-text="row.item"></span></p>
                        <p x-show="row.subitem" class="mb-1 text-xs"><strong>Sub-item:</strong> <span x-text="row.subitem"></span></p>
                        <p class="mb-1 text-xs"><strong>User Input:</strong> <span x-text="row.user_input || '-'"></span></p>
                        <div class="flex items-center justify-between mb-1 mt-1">
                            <h4 class="text-xs font-semibold text-gray-700">Info Lainnya</h4>
                            <button @click="row.isDetailsCollapsed = !row.isDetailsCollapsed" type="button"
                                class="text-blue-500 hover:text-blue-700 focus:outline-none transition-colors duration-150 p-1">
                                <i class="fas text-xs"
                                    :class="{'fa-chevron-down': row.isDetailsCollapsed, 'fa-chevron-up': !row.isDetailsCollapsed}"></i>
                            </button>
                        </div>

                        <div x-show="!row.isDetailsCollapsed" x-transition.duration.300ms class="space-y-1 text-xs text-gray-700">
                            <p class="text-xs"><strong>Storage:</strong> <span x-text="row.storage"></span></p>
                            <p class="text-xs"><strong>Tanggal Masuk:</strong> <span x-text="row.created_at || '-'"></span></p>
                            <p class="text-xs"><strong>User Masuk:</strong> <span x-text="row.user_in || '-'"></span></p>
                            <p class="text-xs"><strong>Stok Saat Ini:</strong> <span x-text="row.ekor_stock_current || '0'"></span> Ekor / <span x-text="(row.berat_stock_current) || '0.00'"></span> KG</p>
                            <p class="text-xs"><strong>Stok Item Global:</strong> <span x-text="row.ekor_item_global_stock || '0'"></span> Ekor / <span x-text="(row.berat_item_global_stock) || '0.00'"></span> KG</p>
                            <p class="text-xs text-gray-500" x-text="row.id_in || '-'"></p>
                        </div>

                        <div x-show="isProcessing && row.barcode && !row.item"
                            class="text-gray-500 animate-pulse text-xs mt-1">Memuat...</div>

                        <div class="grid grid-cols-2 gap-2 mt-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Ekor Baru</label>
                                <input type="text" inputmode="numeric"
                                    pattern="[0-9]*" x-model.number="row.ekor_new" name="ekor_new" @input="updateRowStatus(row); saveToLocalStorage()"
                                    class="w-full px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs" autocomplete="off">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Berat Baru (KG)</label>
                                <input type="text" inputmode="numeric" x-model.number="row.berat_new" name="berat_new" @input="updateRowStatus(row); saveToLocalStorage()"
                                    class="w-full px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs" autocomplete="off">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Keterangan</label>
                                <input type="text" x-model="row.keterangan" name="keterangan"
                                    @input="updateRowStatus(row); saveToLocalStorage()"
                                    class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs">
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <div x-show="adjustmentList.length === 0" class="col-span-full text-center text-gray-500 p-3 text-xs">
                No adjustments found.
            </div>
        </div>
    </div>

    <div class="flex justify-between mt-6 gap-x-8">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
            <button @click="addRowButton" type="button"
                class="flex items-center justify-center px-3 py-2 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                <i class="fas fa-plus mr-1"></i> Tambah Baris
            </button>
            <button @click="openAddDataModal()" type="button" :disabled="!isFormValid"
                class="flex items-center justify-center px-3 py-2 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-plus-circle mr-1"></i> Tambah Data
            </button>
            <button
                type="button"
                @click="openMergeModal()"
                class="col-span-2 md:col-span-1 flex items-center justify-center px-3 py-2 bg-purple-600 text-white font-semibold rounded-md shadow-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-opacity-75 transition duration-300 text-xs disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="isLoading">
                <i class="fas fa-puzzle-piece mr-1"></i> Gabungan / Pindah Barcode
            </button>
        </div>
        <button @click="openAdjustmentModal" :disabled="!isFormValid || !hasChanges || isProcessing" type="button"
            class="inline-flex items-center px-3 py-2 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed">
            <i class="fas fa-save mr-1"></i> Simpan Semua Adjustment
        </button>
    </div>

    <!-- Modal Konfirmasi Adjustment - Desktop Version -->
    <div x-show="modal.show" class="fixed inset-0 z-50 overflow-y-auto hidden md:flex" style="background-color: rgba(0,0,0,0.5);">
        <div class="flex items-center justify-center min-h-screen px-3 w-full">
            <div x-show="modal.show" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative bg-white rounded-lg shadow-xl w-full max-w-4xl p-4">
                <div class="flex justify-between items-center pb-2 border-b">
                    <h3 class="text-sm font-semibold text-gray-900">Konfirmasi Adjustment</h3>
                    <button @click="modal.show = false" type="button"
                        class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                <div class="mt-3 max-h-[70vh] overflow-y-auto">
                    <p class="text-xs font-medium text-gray-700 mb-2">
                        Tanggal: <span x-text="adjustmentDate"></span> | Storage: <span x-text="selectedStorage"></span> | Tipe: <span x-text="adjustmentType"></span>
                    </p>

                    <div x-show="modal.data.barcodes.length > 0">
                        <h4 class="text-sm font-bold my-3">Adjustment Berdasarkan Barcode</h4>
                        <table class="min-w-full divide-y divide-gray-200 mb-4">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-1 text-left text-xs font-medium uppercase w-10">No</th>
                                    <th class="px-3 py-1 text-left text-xs font-medium uppercase">Barcode</th>
                                    <th class="px-3 py-1 text-left text-xs font-medium uppercase">Item</th>
                                    <th class="px-3 py-1 text-left text-xs font-medium uppercase">Adjustment</th>
                                    <th class="px-3 py-1 text-left text-xs font-medium uppercase">User Input</th>
                                    <th class="px-3 py-1 text-left text-xs font-medium uppercase">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dividey divide-gray-200">
                                <template x-for="(item, index) in modal.data.barcodes" :key="item.barcode">
                                    <tr :class="{'bg-red-50': item.ekor_adjustment < 0 || item.berat_adjustment < 0, 'bg-green-50': item.ekor_adjustment > 0 || item.berat_adjustment > 0}">
                                        <td class="px-3 py-1 text-xs text-gray-900" x-text="index + 1"></td>
                                        <td class="px-3 py-1 text-xs text-gray-900" x-text="item.barcode || '-'"></td>
                                        <td class="px-3 py-1 text-xs text-gray-900" x-text="item.item + (item.subitem ? ' : ' + item.subitem : '')"></td>
                                        <td class="px-3 py-1 text-xs font-bold" :class="{'text-red-700': item.ekor_adjustment < 0 || item.berat_adjustment < 0, 'text-green-700': item.ekor_adjustment > 0 || item.berat_adjustment > 0}">
                                            <span x-text="item.ekor_adjustment > 0 ? '+' + item.ekor_adjustment : item.ekor_adjustment"></span> Ekor<br>
                                            <span x-text="item.berat_adjustment > 0 ? '+' + (item.berat_adjustment).toFixed(2) : (item.berat_adjustment).toFixed(2)"></span> KG
                                        </td>
                                        <td class="px-3 py-1 text-xs text-gray-700" x-text="item.user_input || '-'"></td>
                                        <td class="px-3 py-1 text-xs text-gray-900" x-text="item.keterangan || '-'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div x-show="modal.data.global.length > 0">
                        <h4 class="text-sm font-bold my-3">Adjustment Stok Global</h4>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-1 text-left text-xs font-medium uppercase w-10">No</th>
                                    <th class="px-3 py-1 text-left text-xs font-medium uppercase">Barcode</th>
                                    <th class="px-3 py-1 text-left text-xs font-medium uppercase">Item</th>
                                    <th class="px-3 py-1 text-left text-xs font-medium uppercase">Stok Saat Ini</th>
                                    <th class="px-3 py-1 text-left text-xs font-medium uppercase">Adjustment</th>
                                    <th class="px-3 py-1 text-left text-xs font-medium uppercase">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="(item, index) in modal.data.global" :key="item.item + item.subitem">
                                    <tr :class="{'bg-red-50': item.ekor_adjustment < 0 || item.berat_adjustment < 0, 'bg-green-50': item.ekor_adjustment > 0 || item.berat_adjustment > 0}">
                                        <td class="px-3 py-1 text-xs text-gray-900" x-text="index + 1"></td>
                                        <td class="px-3 py-1 text-xs text-gray-900" x-text="item.barcode || '-'"></td>
                                        <td class="px-3 py-1 text-xs text-gray-900" x-text="item.item + (item.subitem ? ' : ' + item.subitem : '')"></td>
                                        <td class="px-3 py-1 text-xs text-gray-900">
                                            <span x-text="item.ekor_stock_current"></span> Ekor<br>
                                            <span x-text="(item.berat_stock_current).toFixed(2)"></span> KG
                                        </td>
                                        <td class="px-3 py-1 text-xs font-bold" :class="{'text-red-700': item.ekor_adjustment < 0 || item.berat_adjustment < 0, 'text-green-700': item.ekor_adjustment > 0 || item.berat_adjustment > 0}">
                                            <span x-text="item.ekor_adjustment > 0 ? '+' + item.ekor_adjustment : item.ekor_adjustment"></span> Ekor<br>
                                            <span x-text="item.berat_adjustment > 0 ? '+' + (item.berat_adjustment).toFixed(2) : (item.berat_adjustment).toFixed(2)"></span> KG
                                        </td>
                                        <td class="px-3 py-1 text-xs text-gray-900" x-text="item.keterangan || '-'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Total Summary -->
                    <div x-show="modal.totals" class="mt-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <h4 class="text-sm font-bold mb-3 text-gray-800">Ringkasan Total Adjustment</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                            <div class="bg-blue-50 p-3 rounded border border-blue-200">
                                <h5 class="font-semibold text-blue-800 mb-1">
                                    Adjustment Barcode
                                    <span class="text-blue-600 font-normal">(<span x-text="modal.totals.barcode.count"></span> data)</span>
                                </h5>
                                <p class="text-blue-700">
                                    Ekor: <span x-text="modal.totals.barcode.ekor > 0 ? '+' + modal.totals.barcode.ekor : modal.totals.barcode.ekor"></span><br>
                                    Berat: <span x-text="modal.totals.barcode.berat > 0 ? '+' + modal.totals.barcode.berat.toFixed(2) : modal.totals.barcode.berat.toFixed(2)"></span> KG
                                </p>
                            </div>
                            <div class="bg-purple-50 p-3 rounded border border-purple-200">
                                <h5 class="font-semibold text-purple-800 mb-1">
                                    Adjustment Global
                                    <span class="text-purple-600 font-normal">(<span x-text="modal.totals.global.count"></span> data)</span>
                                </h5>
                                <p class="text-purple-700">
                                    Ekor: <span x-text="modal.totals.global.ekor > 0 ? '+' + modal.totals.global.ekor : modal.totals.global.ekor"></span><br>
                                    Berat: <span x-text="modal.totals.global.berat > 0 ? '+' + modal.totals.global.berat.toFixed(2) : modal.totals.global.berat.toFixed(2)"></span> KG
                                </p>
                            </div>
                            <div class="bg-green-50 p-3 rounded border border-green-200">
                                <h5 class="font-semibold text-green-800 mb-1">
                                    Total Keseluruhan
                                    <span class="text-green-600 font-normal">(<span x-text="modal.totals.grand.count"></span> data)</span>
                                </h5>
                                <p class="text-green-700">
                                    Ekor: <span x-text="modal.totals.grand.ekor > 0 ? '+' + modal.totals.grand.ekor : modal.totals.grand.ekor"></span><br>
                                    Berat: <span x-text="modal.totals.grand.berat > 0 ? '+' + modal.totals.grand.berat.toFixed(2) : modal.totals.grand.berat.toFixed(2)"></span> KG
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <button @click="modal.show = false" type="button"
                        class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-gray-700 bg-gray-200 hover:bg-gray-300">
                        Batal
                    </button>
                    <button @click="saveAdjustments" type="button" :disabled="isSaving"
                        class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-text="isSaving ? 'Menyimpan...' : 'Simpan Adjustment'"></span>
                        <i x-show="isSaving" class="fas fa-spinner fa-spin ml-1 h-3 w-3 text-white"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Adjustment - Mobile Version -->
    <div x-show="modal.show" class="fixed inset-0 z-50 overflow-y-auto flex md:hidden" style="background-color: rgba(0,0,0,0.5);">
        <div class="flex items-start justify-center min-h-screen p-2 w-full pt-10">
            <div x-show="modal.show" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative bg-white rounded-lg shadow-xl w-full max-w-full mx-2">
                <div class="sticky top-0 bg-white rounded-t-lg border-b z-10">
                    <div class="flex justify-between items-center p-3">
                        <h3 class="text-sm font-semibold text-gray-900">Konfirmasi Adjustment</h3>
                        <button @click="modal.show = false" type="button"
                            class="text-gray-400 hover:text-gray-600 transition-colors p-1">
                            <i class="fas fa-times text-sm"></i>
                        </button>
                    </div>
                    <div class="px-3 pb-2">
                        <p class="text-xs font-medium text-gray-700">
                            <span x-text="adjustmentDate"></span> | <span x-text="selectedStorage"></span> | <span x-text="adjustmentType"></span>
                        </p>
                    </div>
                </div>

                <div class="max-h-[80vh] overflow-y-auto p-3">
                    <!-- Barcode Adjustments Cards -->
                    <div x-show="modal.data.barcodes.length > 0" class="mb-4">
                        <h4 class="text-sm font-bold mb-3 text-gray-800 border-b pb-1">Adjustment Barcode</h4>
                        <div class="space-y-3">
                            <template x-for="(item, index) in modal.data.barcodes" :key="item.barcode">
                                <div class="bg-white border rounded-lg p-3 shadow-sm"
                                    :class="{'border-red-200 bg-red-50': item.ekor_adjustment < 0 || item.berat_adjustment < 0, 'border-green-200 bg-green-50': item.ekor_adjustment > 0 || item.berat_adjustment > 0}">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="text-xs font-medium text-gray-500" x-text="'#' + (index + 1)"></span>
                                        <span class="text-xs font-semibold"
                                            :class="{'text-red-700': item.ekor_adjustment < 0 || item.berat_adjustment < 0, 'text-green-700': item.ekor_adjustment > 0 || item.berat_adjustment > 0}"
                                            x-text="item.ekor_adjustment > 0 ? '+' + item.ekor_adjustment + ' Ekor' : item.ekor_adjustment + ' Ekor'"></span>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-xs"><strong>Barcode:</strong> <span x-text="item.barcode || '-'" class="font-mono"></span></p>
                                        <p class="text-xs"><strong>Item:</strong> <span x-text="item.item"></span></p>
                                        <p x-show="item.subitem" class="text-xs"><strong>Subitem:</strong> <span x-text="item.subitem"></span></p>
                                        <p class="text-xs font-semibold"
                                            :class="{'text-red-700': item.berat_adjustment < 0, 'text-green-700': item.berat_adjustment > 0}"
                                            x-text="item.berat_adjustment > 0 ? '+' + item.berat_adjustment.toFixed(2) + ' KG' : item.berat_adjustment.toFixed(2) + ' KG'"></p>
                                        <p class="text-xs text-gray-600 mt-1"><strong>Keterangan:</strong> <span x-text="item.keterangan || '-'"></span></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Global Adjustments Cards -->
                    <div x-show="modal.data.global.length > 0" class="mb-4">
                        <h4 class="text-sm font-bold mb-3 text-gray-800 border-b pb-1">Adjustment Global</h4>
                        <div class="space-y-3">
                            <template x-for="(item, index) in modal.data.global" :key="item.item + item.subitem">
                                <div class="bg-white border rounded-lg p-3 shadow-sm"
                                    :class="{'border-red-200 bg-red-50': item.ekor_adjustment < 0 || item.berat_adjustment < 0, 'border-green-200 bg-green-50': item.ekor_adjustment > 0 || item.berat_adjustment > 0}">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="text-xs font-medium text-gray-500" x-text="'#' + (index + 1)"></span>
                                        <span class="text-xs font-semibold"
                                            :class="{'text-red-700': item.ekor_adjustment < 0 || item.berat_adjustment < 0, 'text-green-700': item.ekor_adjustment > 0 || item.berat_adjustment > 0}"
                                            x-text="item.ekor_adjustment > 0 ? '+' + item.ekor_adjustment + ' Ekor' : item.ekor_adjustment + ' Ekor'"></span>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-xs"><strong>Item:</strong> <span x-text="item.item"></span></p>
                                        <p x-show="item.subitem" class="text-xs"><strong>Subitem:</strong> <span x-text="item.subitem"></span></p>
                                        <p class="text-xs text-gray-600">
                                            <strong>Stok Saat Ini:</strong>
                                            <span x-text="item.ekor_stock_current"></span> Ekor /
                                            <span x-text="item.berat_stock_current.toFixed(2)"></span> KG
                                        </p>
                                        <p class="text-xs font-semibold"
                                            :class="{'text-red-700': item.berat_adjustment < 0, 'text-green-700': item.berat_adjustment > 0}"
                                            x-text="item.berat_adjustment > 0 ? '+' + item.berat_adjustment.toFixed(2) + ' KG' : item.berat_adjustment.toFixed(2) + ' KG'"></p>
                                        <p class="text-xs text-gray-600 mt-1"><strong>Keterangan:</strong> <span x-text="item.keterangan || '-'"></span></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Total Summary Mobile -->
                    <div x-show="modal.totals" class="bg-gray-50 rounded-lg border border-gray-200 p-3">
                        <h4 class="text-sm font-bold mb-3 text-gray-800">Ringkasan Total</h4>
                        <div class="space-y-3">
                            <div class="bg-blue-50 p-3 rounded border border-blue-200">
                                <h5 class="font-semibold text-blue-800 text-xs mb-1">
                                    Adjustment Barcode (<span x-text="modal.totals.barcode.count"></span>)
                                </h5>
                                <p class="text-blue-700 text-xs">
                                    <span x-text="modal.totals.barcode.ekor > 0 ? '+' + modal.totals.barcode.ekor : modal.totals.barcode.ekor"></span> Ekor<br>
                                    <span x-text="modal.totals.barcode.berat > 0 ? '+' + modal.totals.barcode.berat.toFixed(2) : modal.totals.barcode.berat.toFixed(2)"></span> KG
                                </p>
                            </div>
                            <div class="bg-purple-50 p-3 rounded border border-purple-200">
                                <h5 class="font-semibold text-purple-800 text-xs mb-1">
                                    Adjustment Global (<span x-text="modal.totals.global.count"></span>)
                                </h5>
                                <p class="text-purple-700 text-xs">
                                    <span x-text="modal.totals.global.ekor > 0 ? '+' + modal.totals.global.ekor : modal.totals.global.ekor"></span> Ekor<br>
                                    <span x-text="modal.totals.global.berat > 0 ? '+' + modal.totals.global.berat.toFixed(2) : modal.totals.global.berat.toFixed(2)"></span> KG
                                </p>
                            </div>
                            <div class="bg-green-50 p-3 rounded border border-green-200">
                                <h5 class="font-semibold text-green-800 text-xs mb-1">
                                    Total Keseluruhan (<span x-text="modal.totals.grand.count"></span>)
                                </h5>
                                <p class="text-green-700 text-xs">
                                    <span x-text="modal.totals.grand.ekor > 0 ? '+' + modal.totals.grand.ekor : modal.totals.grand.ekor"></span> Ekor<br>
                                    <span x-text="modal.totals.grand.berat > 0 ? '+' + modal.totals.grand.berat.toFixed(2) : modal.totals.grand.berat.toFixed(2)"></span> KG
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sticky bottom-0 bg-white border-t p-3 rounded-b-lg">
                    <div class="flex justify-end gap-2">
                        <button @click="modal.show = false" type="button"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-xs font-medium rounded-md shadow-sm text-gray-700 bg-gray-200 hover:bg-gray-300 flex-1 justify-center">
                            Batal
                        </button>
                        <button @click="saveAdjustments" type="button" :disabled="isSaving"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed flex-1 justify-center">
                            <span x-text="isSaving ? 'Menyimpan...' : 'Simpan'"></span>
                            <i x-show="isSaving" class="fas fa-spinner fa-spin ml-1 h-3 w-3 text-white"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Data Manual -->
    <div
        x-show="addDataModal.show"
        class="fixed inset-0 z-50 overflow-y-auto"
        style="background-color: rgba(0,0,0,0.5);">
        <div class="flex items-center justify-center min-h-screen px-3">
            <div
                x-show="addDataModal.show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative w-full max-w-lg rounded-lg bg-white p-4 shadow-xl">
                <div class="flex items-center justify-between border-b pb-2">
                    <h3 class="text-sm font-semibold text-gray-900">
                        Tambah Data Tanpa Barcode
                    </h3>
                    <button
                        @click="addDataModal.show = false"
                        type="button"
                        class="text-gray-400 transition-colors hover:text-gray-600">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                <div class="mt-3 grid grid-cols-2 gap-2 space-y-3 md:grid-cols-1">
                    <div class="col-span-2 md:col-span-1">
                        <label for="new-barcode" class="block text-xs font-medium text-gray-700">Barcode Baru</label>
                        <input
                            type="text"
                            id="new-barcode"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            x-model="addDataModal.data.barcode"
                            class="block w-full rounded-md border border-gray-300 px-2 py-1 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 text-xs"
                            autocomplete="off" />
                    </div>

                    <div class="relative" @click.away="itemDropdownOpen = false">
                        <label for="new-item-custom" class="mb-1 block text-xs font-medium text-gray-700">Item</label>
                        <div class="relative mt-1">
                            <input
                                id="new-item-custom"
                                type="text"
                                x-model="itemSearchQuery"
                                @input="itemDropdownOpen = true"
                                @focus="itemDropdownOpen = true"
                                @keydown.escape.stop.prevent="itemDropdownOpen = false"
                                @keydown.arrow-down.prevent="$event.target.nextElementSibling && $event.target.nextElementSibling.focus()"
                                :placeholder="!itemSearchQuery && !addDataModal.data.item ? 'Pilih atau cari Item...' : ''"
                                class="w-full cursor-pointer rounded-md border border-gray-300 bg-white px-2 py-1 text-black shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 text-xs"
                                :class="{'bg-gray-100': isFetchingItems}"
                                :disabled="isFetchingItems"
                                autocomplete="off" />
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 flex items-center pr-2 text-gray-400"
                                @click="itemSearchQuery = ''; selectItem('');">
                                <i class="fas fa-times text-xs" x-show="itemSearchQuery || addDataModal.data.item"></i>
                            </button>
                        </div>

                        <div
                            x-show="itemDropdownOpen && filteredItems.length > 0"
                            class="absolute z-10 mt-1 max-h-60 w-full overflow-y-auto rounded-md border border-gray-300 bg-white shadow-lg"
                            @keydown.tab.prevent="itemDropdownOpen = false">
                            <template x-for="item in filteredItems" :key="item">
                                <div
                                    @click="selectItem(item)"
                                    class="cursor-pointer px-2 py-1 text-black hover:bg-blue-500 hover:text-white text-xs"
                                    :class="{'bg-blue-500 text-white': addDataModal.data.item === item}"
                                    x-text="item"
                                    tabindex="0"
                                    @keydown.enter.prevent="selectItem(item)"
                                    @keydown.arrow-down.prevent="$el.nextElementSibling && $el.nextElementSibling.focus()"
                                    @keydown.arrow-up.prevent="$el.previousElementSibling && $el.previousElementSibling.focus()"></div>
                            </template>
                        </div>
                        <div
                            x-show="itemDropdownOpen && filteredItems.length === 0 && itemSearchQuery.length > 0"
                            class="px-2 py-1 text-gray-500 text-xs">
                            Tidak ada hasil ditemukan.
                        </div>
                        <div x-show="isFetchingItems" class="mt-1 animate-pulse text-xs text-gray-500">
                            Memuat daftar item...
                        </div>
                    </div>

                    <div class="relative" @click.away="subitemDropdownOpen = false">
                        <label for="new-subitem-custom" class="mb-1 block text-xs font-medium text-gray-700">Sub-item (Opsional)</label>
                        <div class="relative mt-1">
                            <input
                                id="new-subitem-custom"
                                type="text"
                                x-model="subitemSearchQuery"
                                @input="subitemDropdownOpen = true"
                                @focus="subitemDropdownOpen = true"
                                @keydown.escape.stop.prevent="subitemDropdownOpen = false"
                                @keydown.arrow-down.prevent="$event.target.nextElementSibling && $event.target.nextElementSibling.focus()"
                                :placeholder="!subitemSearchQuery && !addDataModal.data.subitem ? 'Pilih atau cari Sub-item...' : ''"
                                class="w-full cursor-pointer rounded-md border border-gray-300 px-2 py-1 text-black shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 text-xs"
                                autocomplete="off" />
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 flex items-center pr-2 text-gray-400"
                                @click="subitemSearchQuery = ''; selectSubitem('');">
                                <i class="fas fa-times text-xs" x-show="subitemSearchQuery || addDataModal.data.subitem"></i>
                            </button>
                        </div>

                        <div
                            x-show="subitemDropdownOpen && filteredSubitems.length > 0"
                            class="absolute z-10 mt-1 max-h-60 w-full overflow-y-auto rounded-md border border-gray-300 bg-white shadow-lg"
                            @keydown.tab.prevent="subitemDropdownOpen = false">
                            <template x-for="subitem in filteredSubitems" :key="subitem">
                                <div
                                    @click="selectSubitem(subitem)"
                                    class="cursor-pointer px-2 py-1 text-black hover:bg-blue-500 hover:text-white text-xs"
                                    :class="{'bg-blue-500 text-white': addDataModal.data.subitem === subitem}"
                                    x-text="subitem"
                                    tabindex="0"
                                    @keydown.enter.prevent="selectSubitem(subitem)"
                                    @keydown.arrow-down.prevent="$el.nextElementSibling && $el.nextElementSibling.focus()"
                                    @keydown.arrow-up.prevent="$el.previousElementSibling && $el.previousElementSibling.focus()"></div>
                            </template>
                        </div>
                        <div
                            x-show="subitemDropdownOpen && filteredSubitems.length === 0 && subitemSearchQuery.length > 0"
                            class="px-2 py-1 text-gray-500 text-xs">
                            Tidak ada hasil ditemukan.
                        </div>
                    </div>

                    <div>
                        <label for="new-ekor" class="mb-1 block text-xs font-medium text-gray-700">Jumlah Ekor</label>
                        <input
                            type="text"
                            id="new-ekor"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            x-model.number="addDataModal.data.ekor_new"
                            class="block w-full rounded-md border border-gray-300 px-2 py-1 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 text-xs" />
                    </div>
                    <div>
                        <label for="new-berat" class="mb-1 block text-xs font-medium text-gray-700">Berat (KG)</label>
                        <input
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            id="new-berat"
                            x-model.number="addDataModal.data.berat_new"
                            class="block w-full rounded-md border border-gray-300 px-2 py-1 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 text-xs" />
                    </div>
                    <div class="col-span-2 md:col-span-1">
                        <label for="new-keterangan" class="mb-1 block text-xs font-medium text-gray-700">Keterangan</label>
                        <input
                            type="text"
                            id="new-keterangan"
                            x-model="addDataModal.data.keterangan"
                            class="block w-full rounded-md border border-gray-300 px-2 py-1 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 text-xs" />
                    </div>
                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <button
                        @click="resetAddDataForm()"
                        type="button"
                        class="inline-flex items-center rounded-md border border-transparent bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700 shadow-sm hover:bg-blue-200">
                        Reset
                    </button>
                    <button
                        @click="addDataModal.show = false"
                        type="button"
                        class="inline-flex items-center rounded-md border border-transparent bg-gray-200 px-3 py-1 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-300">
                        Batal
                    </button>
                    <button
                        @click="addNewRow"
                        type="button"
                        :disabled="
            !addDataModal.data.barcode || addDataModal.data.barcode.trim() === '' ||
            !addDataModal.data.item || addDataModal.data.item.trim() === '' ||
            ((typeof addDataModal.data.ekor_new !== 'number' || isNaN(addDataModal.data.ekor_new) || addDataModal.data.ekor_new === 0) &&
            (typeof addDataModal.data.berat_new !== 'number' || isNaN(addDataModal.data.berat_new) || addDataModal.data.berat_new === 0))
          "
                        class="inline-flex items-center rounded-md border border-transparent bg-green-600 px-3 py-1 text-xs font-medium text-white shadow-sm transition-colors hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50">
                        Tambah Data
                    </button>

                </div>
            </div>
        </div>
    </div>

    <!-- Modal Gabungan / Pindah Barcode -->
    <div x-show="showMergeModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center p-2 z-50 min-h-screen">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full p-2 relative transform transition-all duration-300 max-h-[600px] overflow-y-auto">
            <button @click="closeMergeModal()" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 transition-colors duration-200 text-lg" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
            <h2 class="text-sm font-bold mb-2 text-gray-800">Gabungan / Pindah Barcode</h2>
            <p class="text-xs text-gray-500 mb-2">Gabungkan stok dari beberapa barcode asal ke satu barcode tujuan.</p>

            <form @submit.prevent="handleMergeTransaction()">
                <div class="space-y-4">
                    <!-- Informasi Tanggal Adjustment (Menggunakan tanggal dari form utama) -->
                    <div class="p-2 border border-blue-200 rounded-lg bg-blue-50">
                        <div class="flex items-center mb-1">
                            <i class="fas fa-calendar-alt text-blue-600 mr-2 text-xs"></i>
                            <span class="text-xs font-medium text-blue-700">Tanggal Adjustment: <span x-text="adjustmentDate" class="font-bold"></span></span>
                        </div>
                        <label for="mergeStorage" class="block text-xs font-medium text-blue-700">Pilih Lokasi Penyimpanan</label>
                        <select id="mergeStorage" x-model="mergeFormData.storage" class="mt-1 block w-full border-blue-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs p-1 transition-colors duration-200">
                            <template x-for="chiller in chillerLocations" :key="chiller">
                                <option :value="chiller" x-text="chiller"></option>
                            </template>
                        </select>
                        <div x-show="mergeFromBarcodes.length > 0" class="mt-2 p-2 bg-blue-100 rounded-md border border-blue-300">
                            <div class="flex items-center text-blue-800 font-semibold">
                                <i class="fas fa-info-circle mr-1 text-xs"></i>
                                <p class="text-xs">Item yang Digabung</p>
                            </div>
                            <p class="text-xs text-blue-700 mt-1"><strong>Item:</strong> <span x-text="mergeFromBarcodes[0].item"></span></p>
                            <p class="text-xs text-blue-700"><strong>Subitem:</strong> <span x-text="mergeFromBarcodes[0].subitem || '-'"></span></p>
                        </div>
                    </div>

                    <!-- Barcode Asal -->
                    <div class="p-2 border border-gray-200 rounded-lg bg-gray-50">
                        <label for="barcodeFromInput" class="block text-xs font-medium text-gray-700">Barcode Asal</label>
                        <div class="flex items-center mt-1">
                            <input type="text" inputmode="numeric" pattern="[0-9]*" id="barcodeFromInput" x-model="currentBarcodeFrom" @keydown.enter.prevent="addBarcodeFrom()" class="flex-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs p-1 transition-colors duration-200" placeholder="Scan atau ketik barcode asal" autocomplete="off" />
                            <button type="button" @click="addBarcodeFrom()" :disabled="isLoadingMerge" class="ml-1 px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                                <i class="fas fa-plus text-xs" x-show="!isLoadingMerge"></i>
                                <i class="fas fa-spinner fa-spin text-xs" x-show="isLoadingMerge"></i>
                            </button>
                        </div>
                        <p x-show="mergeErrors.add_barcode_from" x-text="mergeErrors.add_barcode_from" class="text-red-500 text-xs mt-1"></p>

                        <div x-show="mergeFromBarcodes.length > 0" class="mt-2">
                            <p class="text-xs text-gray-500 mb-1">Daftar barcode asal:</p>
                            <div class="max-h-60 overflow-y-auto border border-gray-200 rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-100 sticky top-0">
                                        <tr>
                                            <th scope="col" class="px-3 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Barcode</th>
                                            <th scope="col" class="px-3 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ekor</th>
                                            <th scope="col" class="px-3 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Berat (kg)</th>
                                            <th scope="col" class="px-3 py-1 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <template x-for="(barcode, index) in mergeFromBarcodes" :key="index">
                                            <tr>
                                                <td class="px-3 py-1 whitespace-nowrap text-xs text-gray-900" x-text="barcode.barcode"></td>
                                                <td class="px-3 py-1 whitespace-nowrap text-xs text-gray-900">
                                                    <input type="number" x-model.number="barcode.ekor_pack_from" @input="updateTotals(barcode)" class="w-20 text-center border rounded-md p-1 text-xs focus:border-blue-500 focus:ring-blue-500" min="1" :max="barcode.ekor_stock_current">
                                                    <p class="text-xs text-gray-500">Stok: <span x-text="barcode.ekor_stock_current"></span></p>
                                                </td>
                                                <td class="px-3 py-1 whitespace-nowrap text-xs text-gray-900">
                                                    <input type="number" step="0.01" x-model.number="barcode.quantity_kg_from" @input="updateTotals()" class="w-20 text-center border rounded-md p-1 text-xs focus:border-blue-500 focus:ring-blue-500" min="0.01" :max="barcode.berat_stock_current">
                                                    <p class="text-xs text-gray-500">Stok: <span x-text="barcode.berat_stock_current"></span> kg</p>
                                                </td>
                                                <td class="px-3 py-1 whitespace-nowrap text-right text-xs font-medium">
                                                    <button type="button" @click="removeBarcodeFrom(index)" class="text-red-600 hover:text-red-800 transition-colors duration-200" aria-label="Hapus">
                                                        <i class="fas fa-trash text-xs"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-2 p-2 bg-green-100 rounded-lg border border-green-200 text-green-800">
                                <p class="font-semibold text-xs">Total Gabungan</p>
                                <p class="text-xs">Jumlah Ekor: <span x-text="totalEkorFrom"></span></p>
                                <p class="text-xs">Total Berat: <span x-text="totalBeratFrom"></span> kg</p>
                            </div>
                        </div>
                    </div>

                    <!-- Barcode Tujuan -->
                    <div class="p-3 border border-gray-200 rounded-lg bg-gray-50">
                        <label for="barcodeTo" class="block text-xs font-medium text-gray-700 flex items-center">
                            Barcode Tujuan
                            <input type="checkbox" x-model="isNewBarcodeTo" @change="handleNewBarcodeToToggle()" class="ml-1 h-3 w-3 text-blue-600 border-gray-300 rounded focus:ring-blue-500 transition-colors duration-200" />
                            <span class="ml-1 text-xs text-gray-600">Barcode Baru</span>
                        </label>
                        <div class="flex items-center mt-1">
                            <input
                                type="text"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                id="barcodeTo"
                                x-model="mergeFormData.barcode_to"
                                @keydown.enter.prevent="searchBarcodeTo()"
                                class="flex-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs p-2 transition-colors duration-200"
                                placeholder="Scan atau ketik barcode tujuan"
                                autocomplete="off" />
                            <button
                                type="button"
                                @click="searchBarcodeTo()"
                                :disabled="isNewBarcodeTo || isLoadingMerge"
                                class="ml-1 px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                                <i class="fas fa-search text-xs"></i>
                            </button>
                        </div>
                        <p x-show="mergeErrors.barcode_to" x-text="mergeErrors.barcode_to" class="text-red-500 text-xs mt-1"></p>

                        <div x-show="mergeBarcodeData.to.item && !isNewBarcodeTo" class="mt-3 p-2 bg-gray-100 rounded-md border border-gray-200">
                            <p class="text-xs text-gray-700"><strong>Item:</strong> <span x-text="mergeBarcodeData.to.item"></span></p>
                            <p class="text-xs text-gray-700"><strong>Subitem:</strong> <span x-text="mergeBarcodeData.to.subitem || '-'"></span></p>
                            <p class="text-xs text-gray-700"><strong>Stok:</strong> <span x-text="mergeBarcodeData.to.ekor_stock_current"></span> ekor / <span x-text="mergeBarcodeData.to.berat_stock_current"></span> kg</p>
                        </div>

                        <div x-show="isNewBarcodeTo && mergeFromBarcodes.length > 0" class="mt-3 p-2 bg-green-100 rounded-md border border-green-200">
                            <div class="flex items-center text-green-800">
                                <i class="fas fa-magic mr-1 text-xs"></i>
                                <p class="text-xs font-semibold">Barcode Tujuan Baru akan dibuat.</p>
                            </div>
                            <p class="text-xs text-green-700 mt-1">Barcode baru akan berisi item dan total stok dari semua barcode asal.</p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-2">
                        <button type="button" @click="closeMergeModal()" class="px-4 py-1 bg-gray-300 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400 transition-colors duration-200 text-xs">
                            <i class="fas fa-ban mr-1"></i>Batal
                        </button>
                        <button type="submit" :disabled="isLoadingMerge" class="px-4 py-1 bg-green-600 text-white font-semibold rounded-lg shadow-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200 text-xs">
                            <i class="fas fa-exchange-alt mr-1 text-xs" x-show="!isLoadingMerge"></i>
                            <i class="fas fa-spinner fa-spin mr-1 text-xs" x-show="isLoadingMerge"></i>
                            Gabung
                        </button>
                    </div>

                    <p x-show="mergeErrorMessage" x-text="mergeErrorMessage" class="text-red-500 text-xs mt-2 text-center"></p>
                    <p x-show="mergeSuccessMessage" x-text="mergeSuccessMessage" class="text-green-600 text-xs mt-2 text-center"></p>
                </div>
            </form>
        </div>
    </div>

</main>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('adjustmentApp', () => ({
            adjustmentDate: '',
            selectedStorage: '',
            adjustmentType: '',
            adjustmentList: [],
            allAdjustments: [],
            isProcessing: false,
            isSaving: false,
            isFetchingItems: false,
            itemOptions: [],
            subitemOptions: [],
            allStorages: ['Chiller 1', 'Chiller 2', 'Chiller 3'],
            apiEndpoint: './controller/adjustment/process_and_adjust.php',
            adjustTmpApiEndpoint: './controller/adjustment/adjust_tmp.php',
            mergeApiEndpoint: './controller/adjustment/merge.php',
            csvDataUrl: '',
            userOptions: [],
            selectedUser: '',
            itemOptions: [],
            subitemOptions: [],
            itemSearchQuery: '',
            itemDropdownOpen: false,
            currentUserFromSession: '<?php echo isset($_SESSION['user_nama']) ? addslashes($_SESSION['user_nama']) : ''; ?>',

            // Modal data
            modal: {
                show: false,
                data: {
                    barcodes: [],
                    global: []
                }
            },
            addDataModal: {
                show: false,
                data: {
                    barcode: '',
                    item: '',
                    subitem: '',
                    ekor_stock_current: 0,
                    berat_stock_current: 0,
                    ekor_new: null,
                    berat_new: null,
                    keterangan: 'Adjustment data tambahan'
                }
            },

            // Merge modal data
            showMergeModal: false,
            isNewBarcodeTo: false,
            chillerLocations: ['Chiller 1', 'Chiller 2', 'Chiller 3'],
            currentBarcodeFrom: '',
            mergeFromBarcodes: [],
            totalEkorFrom: 0,
            totalBeratFrom: 0,
            mergeFormData: {
                storage: 'Chiller 1',
                barcode_to: '',
            },
            mergeBarcodeData: {
                to: {
                    id: null,
                    barcode: '',
                    item: '',
                    subitem: '',
                    ekor_stock_current: null,
                    berat_stock_current: null,
                    id_in: null,
                },
            },
            mergeErrors: {
                add_barcode_from: '',
                barcode_to: '',
            },
            mergeErrorMessage: '',
            mergeSuccessMessage: '',
            isLoadingMerge: false,

            init() {
                this.adjustmentDate = new Date().toISOString().substring(0, 10);
                this.selectedUser = this.currentUserFromSession;
                this.fetchInitialData();

                this.$watch('selectedStorage', () => this.fetchAdjustmentsAndApplyFilter());
                this.$watch('adjustmentDate', () => this.fetchAdjustmentsAndApplyFilter());
                this.$watch('adjustmentType', () => this.fetchAdjustmentsAndApplyFilter());
                this.$watch('selectedUser', () => this.applyUserFilter());
            },

            // ========== PERBAIKAN: VALIDASI BARCODE SUDAH ADA DI ADJUST_TMP ==========
            async checkBarcodeInAdjustTmp(barcode) {
                if (!this.adjustmentDate || !this.selectedStorage || !this.adjustmentType) {
                    return {
                        is_exists: false,
                        user_input: '',
                        created_at: '',
                        is_current_user: false
                    };
                }

                try {
                    const response = await fetch('./controller/adjustment/process_and_adjust.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'check_barcode_in_adjust_tmp',
                            barcode: barcode,
                            adjustment_date: this.adjustmentDate,
                            storage: this.selectedStorage,
                            adjustment_type: this.adjustmentType,
                            current_user: this.selectedUser // Tambahkan current_user
                        })
                    });

                    const result = await response.json();
                    if (result.success) {
                        return result.data;
                    }
                } catch (error) {
                    console.error('Error checking barcode in adjust_tmp:', error);
                }

                return {
                    is_exists: false,
                    user_input: '',
                    created_at: '',
                    is_current_user: false
                };
            },

            // --- METODE MERGE/GABUNGAN BARCODE DIPERBAIKI ---
            openMergeModal() {
                if (!this.isFormValid) {
                    Swal.fire('Peringatan', 'Silakan isi Tanggal, Storage, dan Tipe Adjustment terlebih dahulu.', 'warning');
                    return;
                }
                this.showMergeModal = true;
                this.resetMergeForm();
            },

            closeMergeModal() {
                this.showMergeModal = false;
                this.resetMergeForm();
            },

            resetMergeForm() {
                this.mergeFormData = {
                    storage: this.selectedStorage || this.chillerLocations[0],
                    barcode_to: '',
                };
                this.mergeFromBarcodes = [];
                this.currentBarcodeFrom = '';
                this.totalEkorFrom = 0;
                this.totalBeratFrom = 0;

                this.mergeBarcodeData.to = {
                    id: null,
                    barcode: '',
                    item: '',
                    subitem: '',
                    ekor_stock_current: null,
                    berat_stock_current: null,
                    id_in: null,
                };

                this.mergeErrors = {
                    add_barcode_from: '',
                    barcode_to: '',
                };
                this.mergeErrorMessage = '';
                this.mergeSuccessMessage = '';
                this.isLoadingMerge = false;
                this.isNewBarcodeTo = false;
            },

            async addBarcodeFrom() {
                const barcode = this.currentBarcodeFrom.trim();
                if (!barcode) {
                    this.mergeErrors.add_barcode_from = 'Barcode asal tidak boleh kosong.';
                    return;
                }

                // Cek duplikasi dalam daftar yang sudah ditambahkan
                if (this.mergeFromBarcodes.find(item => item.barcode === barcode)) {
                    this.mergeErrors.add_barcode_from = 'Barcode ini sudah ditambahkan.';
                    return;
                }

                // ========== VALIDASI DARI BACKEND: CEK APAKAH BARCODE SUDAH ADA DI ADJUST_TMP ==========
                const checkResult = await this.checkBarcodeInAdjustTmp(barcode);
                if (checkResult.is_exists) {
                    // Cari nomor urut baris yang sudah ada
                    const rowNumber = this.getRowNumberByBarcode(barcode);

                    // JIKA USER SAMA, TIDAK BISA DIGUNAKAN UNTUK GABUNGAN
                    if (checkResult.is_current_user) {
                        if (rowNumber) {
                            this.mergeErrors.add_barcode_from = `Barcode sudah Anda gunakan di Baris No. ${rowNumber} (${checkResult.created_at}). Tidak bisa digunakan untuk gabungan.`;
                        } else {
                            this.mergeErrors.add_barcode_from = `Barcode sudah Anda gunakan sebelumnya (${checkResult.created_at}). Tidak bisa digunakan untuk gabungan.`;
                        }
                        this.currentBarcodeFrom = '';
                        return;
                    } else {
                        // JIKA USER BERBEDA, BLOCK TOTALLY
                        this.mergeErrors.add_barcode_from = `Barcode sudah digunakan oleh user lain: ${checkResult.user_input} pada ${checkResult.created_at}`;
                        this.currentBarcodeFrom = '';
                        return;
                    }
                }

                this.isLoadingMerge = true;
                this.mergeErrors.add_barcode_from = '';
                this.mergeErrorMessage = '';
                this.mergeSuccessMessage = '';

                try {
                    const response = await fetch('./controller/adjustment/process_and_adjust.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'search_barcode',
                            barcode: barcode,
                            storage: this.mergeFormData.storage,
                            adjustment_date: this.adjustmentDate,
                            adjustment_type: this.adjustmentType,
                            user_input: '<?php echo $_SESSION["user_nama"] ?? "Guest"; ?>'
                        }),
                    });
                    const result = await response.json();

                    if (result.success) {
                        // Validasi apakah barcode sudah discan
                        if (result.data.is_already_scanned && result.data.scanned_by_user !== this.selectedUser) {
                            this.mergeErrors.add_barcode_from = `Barcode sudah discan oleh user: ${result.data.scanned_by_user} pada ${result.data.scanned_at}`;
                            this.isLoadingMerge = false;
                            return;
                        }

                        // Validasi item dan subitem harus sama dengan barcode pertama (jika ada)
                        if (this.mergeFromBarcodes.length > 0) {
                            const firstItem = this.mergeFromBarcodes[0];
                            if (result.data.item !== firstItem.item) {
                                this.mergeErrors.add_barcode_from = 'Item barcode harus sama dengan item barcode yang lain.';
                                this.isLoadingMerge = false;
                                return;
                            }
                            if ((result.data.subitem || '') !== (firstItem.subitem || '')) {
                                this.mergeErrors.add_barcode_from = 'Subitem barcode harus sama dengan subitem barcode yang lain.';
                                this.isLoadingMerge = false;
                                return;
                            }
                        }

                        // Validasi tidak boleh sama dengan barcode tujuan
                        if (!this.isNewBarcodeTo && this.mergeFormData.barcode_to === result.data.barcode) {
                            this.mergeErrors.add_barcode_from = 'Barcode asal tidak boleh sama dengan barcode tujuan.';
                            this.isLoadingMerge = false;
                            return;
                        }

                        // Tambahkan ke daftar dengan perhitungan berat otomatis
                        this.mergeFromBarcodes.push({
                            ...result.data,
                            ekor_pack_from: result.data.ekor_stock_current,
                            quantity_kg_from: result.data.berat_stock_current,
                        });

                        this.updateTotals();
                        this.currentBarcodeFrom = '';
                    } else {
                        this.mergeErrors.add_barcode_from = result.message || 'Barcode tidak ditemukan';
                    }
                } catch (error) {
                    console.error('Error searching barcode:', error);
                    this.mergeErrors.add_barcode_from = 'Gagal mencari barcode. Silakan coba lagi.';
                } finally {
                    this.isLoadingMerge = false;
                }
            },

            removeBarcodeFrom(index) {
                this.mergeFromBarcodes.splice(index, 1);
                this.updateTotals();
            },

            // ========== PERBAIKAN: UPDATE TOTALS DENGAN PERHITUNGAN BERAT YANG AKURAT ==========
            updateTotals(barcodeItem = null) {
                if (barcodeItem) {
                    // Pastikan berat tidak melebihi stok
                    const maxEkor = barcodeItem.ekor_stock_current;
                    const maxBerat = barcodeItem.berat_stock_current;

                    // Batasi input ekor
                    if (barcodeItem.ekor_pack_from > maxEkor) {
                        barcodeItem.ekor_pack_from = maxEkor;
                    }

                    // PERBAIKAN: HITUNG BERAT BERDASARKAN RASIO STOK
                    // Hitung berat per ekor
                    const averageWeightPerItem = (maxEkor > 0 && maxBerat > 0) ? (maxBerat / maxEkor) : 0;

                    // Hitung berat berdasarkan rasio
                    const calculatedWeight = barcodeItem.ekor_pack_from * averageWeightPerItem;

                    // Gunakan berat yang lebih kecil antara input dan perhitungan
                    if (calculatedWeight > 0 && barcodeItem.quantity_kg_from > calculatedWeight) {
                        barcodeItem.quantity_kg_from = parseFloat(calculatedWeight.toFixed(2));
                    }

                    // Batasi berat tidak melebihi stok
                    if (barcodeItem.quantity_kg_from > maxBerat) {
                        barcodeItem.quantity_kg_from = maxBerat;
                    }

                    // Update nilai display
                    barcodeItem.quantity_kg_from = parseFloat(barcodeItem.quantity_kg_from.toFixed(2));
                }

                this.totalEkorFrom = this.mergeFromBarcodes.reduce((sum, item) =>
                    sum + (parseFloat(item.ekor_pack_from) || 0), 0);

                this.totalBeratFrom = this.mergeFromBarcodes.reduce((sum, item) =>
                    sum + (parseFloat(item.quantity_kg_from) || 0), 0);

                // Format dengan 2 decimal
                this.totalBeratFrom = parseFloat(this.totalBeratFrom.toFixed(2));
            },

            handleNewBarcodeToToggle() {
                if (this.isNewBarcodeTo) {
                    // Reset data barcode tujuan
                    this.mergeFormData.barcode_to = '';
                    this.clearBarcodeData('to');
                    this.mergeErrors.barcode_to = '';

                    // Jika ada barcode asal, set item dan subitem dari barcode pertama
                    if (this.mergeFromBarcodes.length > 0) {
                        const firstItem = this.mergeFromBarcodes[0];
                        // Data ini akan digunakan saat membuat barcode baru
                    }
                } else {
                    // Mode barcode existing, clear data
                    this.clearBarcodeData('to');
                    if (this.mergeFromBarcodes.length > 0) {
                        this.mergeErrors.barcode_to = 'Silakan cari barcode tujuan yang sudah ada.';
                    }
                }
            },

            async searchBarcodeTo() {
                const barcode = this.mergeFormData.barcode_to.trim();
                if (!barcode) {
                    this.mergeErrors.barcode_to = 'Barcode tujuan wajib diisi.';
                    this.clearBarcodeData('to');
                    return;
                }

                if (this.mergeFromBarcodes.length > 0 && this.mergeFromBarcodes.find(item => item.barcode === barcode)) {
                    this.mergeErrors.barcode_to = 'Barcode tujuan tidak boleh sama dengan barcode asal.';
                    this.clearBarcodeData('to');
                    return;
                }

                // ========== VALIDASI DARI BACKEND: CEK APAKAH BARCODE SUDAH ADA DI ADJUST_TMP ==========
                const checkResult = await this.checkBarcodeInAdjustTmp(barcode);
                if (checkResult.is_exists) {
                    // Cari nomor urut baris yang sudah ada
                    const rowNumber = this.getRowNumberByBarcode(barcode);

                    // JIKA USER SAMA, TIDAK BISA DIGUNAKAN SEBAGAI TUJUAN
                    if (checkResult.is_current_user) {
                        if (rowNumber) {
                            this.mergeErrors.barcode_to = `Barcode sudah Anda gunakan di Baris No. ${rowNumber} (${checkResult.created_at}). Tidak bisa digunakan sebagai tujuan gabungan.`;
                        } else {
                            this.mergeErrors.barcode_to = `Barcode sudah Anda gunakan sebelumnya (${checkResult.created_at}). Tidak bisa digunakan sebagai tujuan gabungan.`;
                        }
                        this.clearBarcodeData('to');
                        return;
                    } else {
                        // JIKA USER BERBEDA, BLOCK TOTALLY
                        this.mergeErrors.barcode_to = `Barcode sudah digunakan oleh user lain: ${checkResult.user_input} pada ${checkResult.created_at}`;
                        this.clearBarcodeData('to');
                        return;
                    }
                }

                this.isLoadingMerge = true;
                this.mergeErrors.barcode_to = '';
                this.mergeErrorMessage = '';

                try {
                    const response = await fetch('./controller/adjustment/process_and_adjust.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'search_barcode',
                            barcode: barcode,
                            storage: this.mergeFormData.storage,
                            adjustment_date: this.adjustmentDate,
                            adjustment_type: this.adjustmentType,
                            user_input: '<?php echo $_SESSION["user_nama"] ?? "Guest"; ?>'
                        }),
                    });
                    const result = await response.json();

                    if (result.success) {
                        // Validasi apakah barcode sudah discan
                        if (result.data.is_already_scanned && result.data.scanned_by_user !== this.selectedUser) {
                            this.mergeErrors.barcode_to = `Barcode sudah discan oleh user: ${result.data.scanned_by_user} pada ${result.data.scanned_at}`;
                            this.clearBarcodeData('to');
                            this.isLoadingMerge = false;
                            return;
                        }

                        // Validasi item harus sama dengan barcode asal
                        if (this.mergeFromBarcodes.length > 0 && result.data.item !== this.mergeFromBarcodes[0].item) {
                            this.mergeErrors.barcode_to = 'Item barcode tujuan harus sama dengan item barcode asal.';
                            this.clearBarcodeData('to');
                            this.isLoadingMerge = false;
                            return;
                        }

                        this.mergeBarcodeData.to = {
                            id: result.data.id,
                            barcode: result.data.barcode,
                            item: result.data.item,
                            subitem: result.data.subitem,
                            ekor_stock_current: result.data.ekor_stock_current,
                            berat_stock_current: result.data.berat_stock_current,
                            id_in: result.data.id_in,
                        };
                    } else {
                        this.mergeErrors.barcode_to = result.message;
                        this.clearBarcodeData('to');
                    }
                } catch (error) {
                    console.error('Error searching barcode:', error);
                    this.mergeErrors.barcode_to = 'Gagal mencari barcode. Silakan coba lagi.';
                } finally {
                    this.isLoadingMerge = false;
                }
            },

            async onBarcodeInputKeydown(event, rowId) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    const row = this.adjustmentList.find(r => r.id === rowId);
                    const barcode = (row.barcode || '').trim();

                    if (barcode) {
                        // Cek dulu di adjust_tmp sebelum search
                        const checkResult = await this.checkBarcodeInAdjustTmp(barcode);
                        if (checkResult.is_exists) {
                            // Cari nomor urut baris yang sudah ada
                            const rowNumber = this.getRowNumberByBarcode(barcode);

                            // JIKA USER SAMA, TIDAK BISA TAMBAH BARU
                            if (checkResult.is_current_user) {
                                if (rowNumber) {
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'Barcode Sudah Digunakan',
                                        html: `Barcode <strong>${barcode}</strong> sudah Anda gunakan sebelumnya.<br><br>
                                               Data sudah ada di <strong>Baris No. ${rowNumber}</strong><br>
                                               Waktu: ${checkResult.created_at}<br><br>
                                               <strong>Anda tidak bisa menambah data baru.</strong><br>
                                               Silakan edit data di Baris No. ${rowNumber}.`,
                                        confirmButtonText: 'Mengerti'
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'Barcode Sudah Digunakan',
                                        html: `Barcode <strong>${barcode}</strong> sudah Anda gunakan sebelumnya.<br>
                                               Waktu: ${checkResult.created_at}<br><br>
                                               <strong>Anda tidak bisa menambah data baru.</strong>`,
                                        confirmButtonText: 'Mengerti'
                                    });
                                }

                                // Kosongkan barcode di input baru
                                row.barcode = '';
                                return;
                            } else {
                                // JIKA USER BERBEDA, BLOCK TOTALLY
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Barcode Sudah Digunakan',
                                    html: `Barcode <strong>${barcode}</strong> sudah digunakan oleh user lain:<br><br>
                                           User: <strong>${checkResult.user_input}</strong><br>
                                           Pada: ${checkResult.created_at}<br><br>
                                           Anda tidak dapat menggunakan barcode yang sama dengan user lain.`,
                                    confirmButtonText: 'Mengerti'
                                });
                                row.barcode = '';
                                return;
                            }
                        }
                    }

                    // Jika lolos validasi, lanjutkan search
                    await this.searchBarcode(rowId);
                }
            },


            clearBarcodeData(type) {
                this.mergeBarcodeData[type] = {
                    id: null,
                    barcode: '',
                    item: '',
                    subitem: '',
                    ekor_stock_current: null,
                    berat_stock_current: null,
                    id_in: null,
                };
            },

            validateMergeForm() {
                this.mergeErrors = {
                    add_barcode_from: '',
                    barcode_to: '',
                };
                let isValid = true;

                if (this.mergeFromBarcodes.length === 0) {
                    this.mergeErrors.add_barcode_from = 'Minimal satu barcode asal harus ditambahkan.';
                    isValid = false;
                } else {
                    const firstItem = this.mergeFromBarcodes[0].item;
                    const firstSubitem = this.mergeFromBarcodes[0].subitem || '';

                    for (const item of this.mergeFromBarcodes) {
                        if (!item.ekor_pack_from || item.ekor_pack_from <= 0) {
                            this.mergeErrors.add_barcode_from = `Ekor/Pack untuk barcode ${item.barcode} tidak valid.`;
                            isValid = false;
                            break;
                        }
                        if (!item.quantity_kg_from || item.quantity_kg_from <= 0) {
                            this.mergeErrors.add_barcode_from = `Berat untuk barcode ${item.barcode} tidak valid.`;
                            isValid = false;
                            break;
                        }
                        if (item.ekor_pack_from > item.ekor_stock_current) {
                            this.mergeErrors.add_barcode_from = `Ekor/Pack untuk barcode ${item.barcode} melebihi stok.`;
                            isValid = false;
                            break;
                        }
                        if (item.quantity_kg_from > item.berat_stock_current) {
                            this.mergeErrors.add_barcode_from = `Berat untuk barcode ${item.barcode} melebihi stok.`;
                            isValid = false;
                            break;
                        }

                        // Validasi item dan subitem harus sama
                        if (item.item !== firstItem) {
                            this.mergeErrors.add_barcode_from = `Semua barcode asal harus memiliki item yang sama.`;
                            isValid = false;
                            break;
                        }

                        if ((item.subitem || '') !== firstSubitem) {
                            this.mergeErrors.add_barcode_from = `Semua barcode asal harus memiliki subitem yang sama.`;
                            isValid = false;
                            break;
                        }
                    }
                }

                if (isValid) {
                    if (!this.isNewBarcodeTo) {
                        if (!this.mergeFormData.barcode_to) {
                            this.mergeErrors.barcode_to = 'Barcode tujuan wajib diisi jika bukan barcode baru.';
                            isValid = false;
                        } else if (!this.mergeBarcodeData.to.item) {
                            this.mergeErrors.barcode_to = 'Barcode tujuan tidak ditemukan atau tidak valid.';
                            isValid = false;
                        } else if (this.mergeBarcodeData.to.item !== this.mergeFromBarcodes[0].item) {
                            this.mergeErrors.barcode_to = 'Item barcode tujuan harus sama dengan item barcode asal.';
                            isValid = false;
                        }
                    } else {
                        if (!this.mergeFormData.barcode_to) {
                            this.mergeErrors.barcode_to = 'Barcode baru wajib diisi.';
                            isValid = false;
                        } else if (this.mergeFromBarcodes.some(item => item.barcode === this.mergeFormData.barcode_to)) {
                            this.mergeErrors.barcode_to = 'Barcode baru tidak boleh sama dengan barcode asal.';
                            isValid = false;
                        }
                    }
                }

                return isValid;
            },


            addRowButton() {
                // Cek apakah sudah ada row kosong
                const hasEmptyRow = this.adjustmentList.some(row =>
                    (!row.barcode || row.barcode.trim() === '') &&
                    (!row.item || row.item.trim() === '') &&
                    (row.ekor_new === null || row.ekor_new === 0) &&
                    (row.berat_new === null || row.berat_new === 0)
                );

                if (hasEmptyRow) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Ada Baris Kosong',
                        text: 'Masih ada baris kosong yang bisa diisi. Silakan isi baris tersebut terlebih dahulu.',
                        confirmButtonText: 'OK'
                    });

                    // Focus ke baris kosong pertama
                    const emptyRow = this.adjustmentList.find(row =>
                        (!row.barcode || row.barcode.trim() === '') &&
                        (!row.item || row.item.trim() === '') &&
                        (row.ekor_new === null || row.ekor_new === 0) &&
                        (row.berat_new === null || row.berat_new === 0)
                    );

                    if (emptyRow) {
                        this.$nextTick(() => {
                            const barcodeInput = this.$el.querySelector(`[data-row-id="${emptyRow.id}"] input[name="barcode"]`);
                            if (barcodeInput) {
                                barcodeInput.focus();
                            }
                        });
                    }

                    return;
                }

                // Jika tidak ada baris kosong, buat baru
                this.addEmptyRow();
            },

            async handleMergeTransaction() {
                this.isLoadingMerge = true;
                this.mergeErrorMessage = '';
                this.mergeSuccessMessage = '';

                if (!this.validateMergeForm()) {
                    this.mergeErrorMessage = 'Mohon perbaiki kesalahan pada formulir.';
                    this.isLoadingMerge = false;
                    return;
                }

                try {
                    const payload = {
                        action: 'save_merge_transaction',
                        date: this.adjustmentDate,
                        storage: this.mergeFormData.storage,
                        adjustment_type: this.adjustmentType,
                        is_new_barcode_to: this.isNewBarcodeTo,
                        from_barcodes: this.mergeFromBarcodes.map(item => ({
                            barcode: item.barcode,
                            item: item.item,
                            subitem: item.subitem || '',
                            ekor_pack_from: item.ekor_pack_from,
                            quantity_kg_from: item.quantity_kg_from,
                            id_in: item.id_in
                        })),
                        barcode_to_data: {
                            barcode: this.mergeFormData.barcode_to,
                            item: this.isNewBarcodeTo ? this.mergeFromBarcodes[0].item : this.mergeBarcodeData.to.item,
                            subitem: this.isNewBarcodeTo ? (this.mergeFromBarcodes[0].subitem || '') : (this.mergeBarcodeData.to.subitem || ''),
                        },
                        user: '<?php echo $_SESSION["user_nama"] ?? "Guest"; ?>',
                    };

                    const response = await fetch(this.mergeApiEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload),
                    });

                    const result = await response.json();

                    if (result.success) {
                        this.mergeSuccessMessage = result.message;

                        // ========== PERBAIKAN: TUTUP MODAL DAN REFRESH DATA ==========
                        setTimeout(() => {
                            this.closeMergeModal();

                            // Refresh data dari adjust_tmp
                            this.fetchAdjustmentsAndApplyFilter().then(() => {
                                // Cari barcode tujuan yang baru ditambahkan
                                const mergedRow = this.adjustmentList.find(row =>
                                    row.barcode === result.data.barcode
                                );

                                if (mergedRow) {
                                    // Update keterangan jika ada
                                    if (result.data.keterangan) {
                                        mergedRow.keterangan = result.data.keterangan;
                                    }

                                    // ========== PERBAIKAN: TAMBAH ROW KOSONG DAN FOCUS KE BARCODE HASIL GABUNGAN ==========
                                    setTimeout(() => {
                                        this.addEmptyRowAndFocusToScannedRow(mergedRow.id);
                                    }, 500);
                                }
                            });

                        }, 1500);
                    } else {
                        this.mergeErrorMessage = result.message || 'Gagal menyimpan transaksi gabungan';
                    }
                } catch (error) {
                    console.error('Merge error:', error);
                    this.mergeErrorMessage = 'Terjadi kesalahan jaringan: ' + error.message;
                } finally {
                    this.isLoadingMerge = false;
                }
            },

            async refreshAdjustmentList() {
                if (!this.adjustmentDate || !this.selectedStorage || !this.adjustmentType) {
                    return;
                }

                this.isProcessing = true;
                try {
                    const response = await fetch(`${this.adjustTmpApiEndpoint}?action=get_adjustments&date=${this.adjustmentDate}&storage=${this.selectedStorage}&type=${this.adjustmentType}`);
                    const result = await response.json();

                    if (result.success) {
                        // Simpan data lama untuk referensi
                        const oldData = [...this.adjustmentList];

                        this.allAdjustments = result.data.map(row => ({
                            ...row,
                            id: row.row_unique_id,
                            ekor_stock_current: parseFloat(row.ekor_stock_current || '0'),
                            berat_stock_current: parseFloat(row.berat_stock_current || '0'),
                            ekor_new: parseFloat(row.ekor_new || '0'),
                            berat_new: parseFloat(row.berat_new || '0'),
                            isEdited: row.is_edited == 1,
                            isDetailsCollapsed: row.is_details_collapsed == 1,
                            user_input: row.user_input || 'N/A'
                        }));

                        // Terapkan filter user
                        this.applyUserFilter();

                        // Cari barcode yang baru ditambahkan
                        const newBarcodes = this.adjustmentList.filter(newRow =>
                            !oldData.some(oldRow => oldRow.barcode === newRow.barcode)
                        );

                        // Jika ada barcode baru, focus ke yang pertama
                        if (newBarcodes.length > 0) {
                            this.$nextTick(() => {
                                setTimeout(() => {
                                    const newRow = newBarcodes[0];
                                    const rowElement = this.$el.querySelector(`[data-row-id="${newRow.id}"]`);
                                    if (rowElement) {
                                        rowElement.scrollIntoView({
                                            behavior: 'smooth',
                                            block: 'center'
                                        });

                                        // Focus ke input ekor_new
                                        const ekorInput = rowElement.querySelector(`input[name="ekor_new"]`);
                                        if (ekorInput) {
                                            ekorInput.focus();
                                        }
                                    }
                                }, 300);
                            });
                        }
                    }
                } catch (error) {
                    console.error('Error refreshing adjustment list:', error);
                } finally {
                    this.isProcessing = false;
                }
            },

            // ========== PERBAIKAN: TAMBAH DATA MANUAL DENGAN VALIDASI ==========
            async addNewRow() {
                const ekorNewValue = parseFloat(this.addDataModal.data.ekor_new);
                const beratNewValue = parseFloat(this.addDataModal.data.berat_new);
                const barcodeToCheck = this.addDataModal.data.barcode.trim();

                // Validasi 1: Cek field required
                if (
                    !barcodeToCheck ||
                    !this.addDataModal.data.item.trim() ||
                    (isNaN(ekorNewValue) && isNaN(beratNewValue)) ||
                    (ekorNewValue === 0 && beratNewValue === 0)
                ) {
                    Swal.fire('Peringatan', 'Silakan isi Barcode, Item, dan setidaknya salah satu dari Ekor Baru atau Berat Baru dengan nilai yang valid (bukan nol).', 'warning');
                    return;
                }

                // Validasi 2: Cek apakah barcode sudah ada di daftar adjustmentList saat ini
                const existingRowInCurrentList = this.adjustmentList.find(r =>
                    r.barcode === barcodeToCheck &&
                    r.storage === this.selectedStorage &&
                    r.tanggal === this.adjustmentDate
                );

                if (existingRowInCurrentList) {
                    // Cari nomor urut baris yang sudah ada
                    const rowNumber = this.getRowNumberByBarcode(barcodeToCheck);

                    if (rowNumber) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Barcode Sudah Ada',
                            html: `Barcode <strong>${barcodeToCheck}</strong> sudah ada di daftar utama.<br><br>
                                   Data sudah ada di <strong>Baris No. ${rowNumber}</strong><br><br>
                                   Silakan edit langsung pada baris tersebut.`,
                            confirmButtonText: 'Mengerti'
                        });
                    } else {
                        Swal.fire('Informasi', 'Barcode sudah ada di daftar utama untuk storage dan tanggal ini. Silakan edit langsung pada baris yang sesuai.', 'info');
                    }

                    this.addDataModal.show = false;
                    return;
                }

                // ========== VALIDASI 3: CEK DI ADJUST_TMP DARI BACKEND ==========
                const checkResult = await this.checkBarcodeInAdjustTmp(barcodeToCheck);
                if (checkResult.is_exists) {
                    // Cari nomor urut baris yang sudah ada
                    const rowNumber = this.getRowNumberByBarcode(barcodeToCheck);

                    // JIKA USER SAMA, TIDAK BISA TAMBAH BARU
                    if (checkResult.is_current_user) {
                        if (rowNumber) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Barcode Sudah Digunakan',
                                html: `Barcode <strong>${barcodeToCheck}</strong> sudah Anda gunakan sebelumnya.<br><br>
                                       Data sudah ada di <strong>Baris No. ${rowNumber}</strong><br>
                                       Waktu: ${checkResult.created_at}<br><br>
                                       <strong>Anda tidak bisa menambah data baru dengan barcode yang sama.</strong><br>
                                       Silakan edit data di Baris No. ${rowNumber}.`,
                                confirmButtonText: 'Mengerti'
                            });
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Barcode Sudah Digunakan',
                                html: `Barcode <strong>${barcodeToCheck}</strong> sudah Anda gunakan sebelumnya.<br>
                                       Waktu: ${checkResult.created_at}<br><br>
                                       <strong>Anda tidak bisa menambah data baru dengan barcode yang sama.</strong>`,
                                confirmButtonText: 'Mengerti'
                            });
                        }

                        this.addDataModal.show = false;
                        return;
                    } else {
                        // JIKA USER BERBEDA, BLOCK TOTALLY
                        Swal.fire({
                            icon: 'error',
                            title: 'Barcode Sudah Digunakan',
                            html: `Barcode <strong>${barcodeToCheck}</strong> sudah digunakan oleh user lain:<br><br>
                                   User: <strong>${checkResult.user_input}</strong><br>
                                   Pada: ${checkResult.created_at}<br><br>
                                   Anda tidak dapat menggunakan barcode yang sama dengan user lain.`,
                            confirmButtonText: 'Mengerti'
                        });
                        this.addDataModal.show = false;
                        return;
                    }
                }

                // Validasi 4: Cek apakah barcode ini sudah discan (dari endpoint gabungan barcode)
                this.isProcessing = true;
                try {
                    const checkScannedResponse = await fetch('./controller/adjustment/process_and_adjust.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'search_barcode',
                            barcode: barcodeToCheck,
                            storage: this.selectedStorage,
                            adjustment_date: this.adjustmentDate,
                            adjustment_type: this.adjustmentType,
                            user_input: this.selectedUser
                        })
                    });

                    const scannedResult = await checkScannedResponse.json();

                    // Jika barcode sudah discan
                    if (scannedResult.success && scannedResult.data && scannedResult.data.is_already_scanned) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Barcode Sudah Discan',
                            html: `Barcode <strong>${barcodeToCheck}</strong> sudah discan oleh user: <strong>${scannedResult.data.scanned_by_user}</strong><br>
                           Pada: ${scannedResult.data.scanned_at}<br><br>
                           Silakan gunakan barcode lain atau hubungi user tersebut.`,
                            confirmButtonText: 'OK'
                        });
                        this.isProcessing = false;
                        this.addDataModal.show = false;
                        return;
                    }
                } catch (error) {
                    console.error('Error checking barcode:', error);
                } finally {
                    this.isProcessing = false;
                }

                // Lanjutkan proses tambah data jika semua validasi lolos
                const emptyRow = this.adjustmentList.find(row =>
                    (!row.barcode || row.barcode.trim() === '') &&
                    (!row.item || row.item.trim() === '') &&
                    (row.ekor_new === null || row.ekor_new === 0) &&
                    (row.berat_new === null || row.berat_new === 0)
                );

                let newRowId = null;

                if (emptyRow) {
                    emptyRow.barcode = barcodeToCheck;
                    emptyRow.item = this.addDataModal.data.item.trim();
                    emptyRow.subitem = this.addDataModal.data.subitem ? this.addDataModal.data.subitem.trim() : '';
                    emptyRow.ekor_stock_current = this.addDataModal.data.ekor_stock_current || 0;
                    emptyRow.berat_stock_current = this.addDataModal.data.berat_stock_current || 0;
                    emptyRow.ekor_new = ekorNewValue;
                    emptyRow.berat_new = beratNewValue;

                    // Format keterangan: "SO-YYYY-MM-DD - [keterangan]" jika ada keterangan
                    const baseKeterangan = `SO-${this.adjustmentDate}`;
                    let finalKeterangan = '';

                    if (this.addDataModal.data.keterangan && this.addDataModal.data.keterangan.trim() !== '') {
                        // Cek apakah keterangan sudah dalam format yang benar
                        if (this.addDataModal.data.keterangan.includes(`SO-${this.adjustmentDate}`)) {
                            finalKeterangan = this.addDataModal.data.keterangan.trim();
                        } else {
                            finalKeterangan = `${baseKeterangan} - ${this.addDataModal.data.keterangan.trim()}`;
                        }
                    } else {
                        finalKeterangan = baseKeterangan;
                    }

                    emptyRow.keterangan = finalKeterangan;
                    emptyRow.isEdited = true;
                    emptyRow.user_input = this.selectedUser;
                    emptyRow.user_in = '';
                    emptyRow.created_at = '';

                    newRowId = emptyRow.id;

                    try {
                        await this.saveToDatabase(emptyRow);
                        Swal.fire('Berhasil!', 'Data telah ditambahkan ke baris yang tersedia.', 'success');

                        // Reset form modal
                        this.resetAddDataForm();
                        this.addDataModal.show = false;

                        // ========== PERBAIKAN: TAMBAH ROW KOSONG DAN FOCUS KE BARIS YANG DITAMBAH ==========
                        await this.addEmptyRowAndFocusToScannedRow(newRowId);

                    } catch (error) {
                        Swal.fire('Error', 'Gagal menyimpan data: ' + error.message, 'error');
                        console.error('Error saving data:', error);
                    }
                } else {
                    // Format keterangan: "SO-YYYY-MM-DD - [keterangan]" jika ada keterangan
                    const baseKeterangan = `SO-${this.adjustmentDate}`;
                    let finalKeterangan = '';

                    if (this.addDataModal.data.keterangan && this.addDataModal.data.keterangan.trim() !== '') {
                        // Cek apakah keterangan sudah dalam format yang benar
                        if (this.addDataModal.data.keterangan.includes(`SO-${this.adjustmentDate}`)) {
                            finalKeterangan = this.addDataModal.data.keterangan.trim();
                        } else {
                            finalKeterangan = `${baseKeterangan} - ${this.addDataModal.data.keterangan.trim()}`;
                        }
                    } else {
                        finalKeterangan = baseKeterangan;
                    }

                    const newRow = {
                        id: Date.now(),
                        id_in: this.addDataModal.data.id_in || null,
                        barcode: barcodeToCheck,
                        storage: this.selectedStorage,
                        item: this.addDataModal.data.item.trim(),
                        subitem: this.addDataModal.data.subitem ? this.addDataModal.data.subitem.trim() : '',
                        ekor_stock_current: this.addDataModal.data.ekor_stock_current || 0,
                        berat_stock_current: this.addDataModal.data.berat_stock_current || 0,
                        ekor_new: ekorNewValue,
                        berat_new: beratNewValue,
                        keterangan: finalKeterangan,
                        isEdited: true,
                        isDetailsCollapsed: true,
                        user_input: this.selectedUser,
                        user_in: '',
                        created_at: '',
                    };

                    newRowId = newRow.id;
                    this.adjustmentList.push(newRow);

                    try {
                        await this.saveToDatabase(newRow);
                        Swal.fire('Berhasil!', 'Baris baru telah ditambahkan dan disimpan.', 'success');

                        // Reset form modal
                        this.resetAddDataForm();
                        this.addDataModal.show = false;

                        // ========== PERBAIKAN: TAMBAH ROW KOSONG DAN FOCUS KE BARIS YANG DITAMBAH ==========
                        await this.addEmptyRowAndFocusToScannedRow(newRowId);

                    } catch (error) {
                        Swal.fire('Error', 'Gagal menyimpan baris baru ke database: ' + error.message, 'error');
                        const index = this.adjustmentList.findIndex(r => r.id === newRow.id);
                        if (index > -1) {
                            this.adjustmentList.splice(index, 1);
                        }
                        console.error('Error saving new row:', error);
                    }
                }
            },

            focusToExistingRow(barcode) {
                // Cari baris yang sudah ada dengan barcode tersebut
                const existingRow = this.adjustmentList.find(row =>
                    row.barcode === barcode
                );

                if (existingRow) {
                    this.$nextTick(() => {
                        setTimeout(() => {
                            const rowElement = this.$el.querySelector(`[data-row-id="${existingRow.id}"]`);
                            if (rowElement) {
                                // Scroll ke baris yang sudah ada
                                rowElement.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'center'
                                });

                                // Tambah highlight untuk memperjelas
                                rowElement.classList.add('bg-yellow-100', 'border-l-4', 'border-yellow-500');

                                // Focus ke input ekor_new
                                const ekorInput = rowElement.querySelector(`input[name="ekor_new"]`);
                                if (ekorInput) {
                                    ekorInput.focus();
                                    ekorInput.select();
                                }

                                // Hapus highlight setelah 3 detik
                                setTimeout(() => {
                                    rowElement.classList.remove('bg-yellow-100', 'border-l-4', 'border-yellow-500');
                                }, 3000);
                            }
                        }, 300);
                    });

                    return true;
                }

                return false;
            },

            getRowNumberByBarcode(barcode) {
                const rowIndex = this.adjustmentList.findIndex(row =>
                    row.barcode === barcode
                );

                if (rowIndex !== -1) {
                    return rowIndex + 1; // Karena array index mulai dari 0
                }

                // Jika belum ada di adjustmentList, coba cari di allAdjustments
                const allRowIndex = this.allAdjustments.findIndex(row =>
                    row.barcode === barcode
                );

                if (allRowIndex !== -1) {
                    return allRowIndex + 1;
                }

                return null; // Tidak ditemukan
            },

            async addEmptyRowAndFocusToScannedRow(scannedRowId) {
                // Tunggu sebentar untuk memastikan DOM sudah update
                await this.$nextTick();

                // 1. FOCUS KE BARIS YANG BARU DI-SCAN
                setTimeout(() => {
                    const scannedRowElement = this.$el.querySelector(`[data-row-id="${scannedRowId}"]`);
                    if (scannedRowElement) {
                        // Scroll ke baris yang baru di-scan
                        scannedRowElement.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });

                        // Focus ke input ekor_new untuk edit
                        const ekorInput = scannedRowElement.querySelector(`input[name="ekor_new"]`);
                        if (ekorInput) {
                            ekorInput.focus();
                            ekorInput.select();
                        }
                    }
                }, 300);

                // 2. TAMBAH ROW KOSONG BARU SETELAH FOCUS
                setTimeout(() => {
                    this.addEmptyRow();
                }, 800);
            },

            // ========== PERBAIKAN: FUNGSI TAMBAH ROW KOSONG ==========
            addEmptyRow() {
                // Cek dulu apakah sudah ada row kosong di akhir
                const lastRow = this.adjustmentList[this.adjustmentList.length - 1];
                const hasEmptyLastRow = lastRow &&
                    (!lastRow.barcode || lastRow.barcode.trim() === '') &&
                    (!lastRow.item || lastRow.item.trim() === '') &&
                    (lastRow.ekor_new === null || lastRow.ekor_new === 0) &&
                    (lastRow.berat_new === null || lastRow.berat_new === 0);

                if (hasEmptyLastRow) {
                    // Sudah ada row kosong di akhir, focus ke sana
                    this.$nextTick(() => {
                        const barcodeInput = this.$el.querySelector(`[data-row-id="${lastRow.id}"] input[name="barcode"]`);
                        if (barcodeInput) {
                            barcodeInput.focus();
                        }
                    });
                    return;
                }

                // Buat row kosong baru
                const newRow = {
                    id: Date.now(),
                    id_in: null,
                    barcode: '',
                    storage: this.selectedStorage,
                    item: '',
                    subitem: '',
                    ekor_stock_current: null,
                    berat_stock_current: null,
                    ekor_new: null,
                    berat_new: null,
                    keterangan: '',
                    isEdited: false,
                    isDetailsCollapsed: true,
                    user_input: this.selectedUser || this.currentUserFromSession,
                    tanggal: this.adjustmentDate,
                    type: this.adjustmentType,
                };
                this.adjustmentList.push(newRow);

                // Focus ke input barcode row baru setelah delay
                this.$nextTick(() => {
                    setTimeout(() => {
                        const newBarcodeInput = this.$el.querySelector(`[data-row-id="${newRow.id}"] input[name="barcode"]`);
                        if (newBarcodeInput) {
                            newBarcodeInput.focus();
                        }
                    }, 500);
                });
            },

            // ========== PERBAIKAN: SEARCH BARCODE DENGAN LOGIC BARU ==========
            async searchBarcode(rowId) {
                const row = this.adjustmentList.find(r => r.id === rowId);
                const barcode = (row.barcode || '').trim();

                if (!this.isFormValid) {
                    Swal.fire('Peringatan', 'Silakan isi Tanggal, Storage, dan Tipe Adjustment terlebih dahulu.', 'warning');
                    return;
                }

                if (!barcode) {
                    if (row.item || row.subitem || row.ekor_stock_current || row.berat_stock_current) {
                        row.id_in = null;
                        row.item = '';
                        row.subitem = '';
                        row.ekor_stock_current = null;
                        row.berat_stock_current = null;
                        row.ekor_new = null;
                        row.berat_new = null;
                        row.keterangan = '';
                        row.isEdited = false;
                        row.user_in = '';
                        row.created_at = '';
                        await this.saveToDatabase(row);
                    }
                    return;
                }

                // ========== VALIDASI: CEK APAKAH BARCODE SUDAH ADA DI ADJUST_TMP ==========
                const checkResult = await this.checkBarcodeInAdjustTmp(barcode);
                if (checkResult.is_exists) {
                    // Cari nomor urut baris yang sudah ada
                    const rowNumber = this.getRowNumberByBarcode(barcode);

                    // JIKA USER SAMA, TIDAK BISA TAMBAH BARU - HANYA BISA EDIT
                    if (checkResult.is_current_user) {
                        if (rowNumber) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Barcode Sudah Digunakan',
                                html: `Barcode <strong>${barcode}</strong> sudah Anda gunakan sebelumnya.<br><br>
                                       Data sudah ada di <strong>Baris No. ${rowNumber}</strong><br>
                                       Waktu: ${checkResult.created_at}<br><br>
                                       <strong>Anda tidak bisa menambah data baru.</strong><br>
                                       Silakan edit data di Baris No. ${rowNumber}.`,
                                confirmButtonText: 'Mengerti'
                            });
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Barcode Sudah Digunakan',
                                html: `Barcode <strong>${barcode}</strong> sudah Anda gunakan sebelumnya.<br>
                                       Waktu: ${checkResult.created_at}<br><br>
                                       <strong>Anda tidak bisa menambah data baru.</strong>`,
                                confirmButtonText: 'Mengerti'
                            });
                        }

                        // Kosongkan barcode di input baru
                        row.barcode = '';
                        return;
                    } else {
                        // JIKA USER BERBEDA, BLOCK TOTALLY
                        Swal.fire({
                            icon: 'error',
                            title: 'Barcode Sudah Digunakan',
                            html: `Barcode <strong>${barcode}</strong> sudah digunakan oleh user lain:<br><br>
                                   User: <strong>${checkResult.user_input}</strong><br>
                                   Pada: ${checkResult.created_at}<br><br>
                                   Anda tidak dapat menggunakan barcode yang sama dengan user lain.`,
                            confirmButtonText: 'Mengerti'
                        });
                        row.barcode = '';
                        return;
                    }
                }

                this.isProcessing = true;

                try {
                    const response = await fetch(this.apiEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'search_barcode',
                            barcode: barcode,
                            storage: this.selectedStorage,
                            adjustment_date: this.adjustmentDate,
                            adjustment_type: this.adjustmentType,
                            user_input: this.selectedUser
                        })
                    });
                    const result = await response.json();

                    if (result.success) {
                        // CEK APAKAH BARCODE SUDAH DISCAN (VALIDASI DARI BACKEND)
                        if (result.data.is_already_scanned) {
                            // PERIKSA APAKAH INI USER YANG SAMA
                            if (result.data.scanned_by_user === this.selectedUser) {
                                // User yang sama, lanjutkan saja
                                Swal.fire('Informasi', 'Anda sudah menscan barcode ini sebelumnya.', 'info');
                            } else {
                                // User berbeda, tampilkan pesan
                                Swal.fire('Informasi', `Barcode sudah discan oleh user: ${result.data.scanned_by_user} pada ${result.data.scanned_at}`, 'info');
                                row.barcode = '';
                                this.isProcessing = false;
                                return;
                            }
                        }

                        const data = result.data;
                        row.id_in = data.id_in;
                        row.item = data.item;
                        row.subitem = data.subitem;
                        row.storage = data.storage;
                        row.user_in = data.user_in || '';
                        row.created_at = data.created_at || '';
                        row.ekor_stock_current = parseFloat(data.ekor_stock_current) || 0;
                        row.berat_stock_current = parseFloat(data.berat_stock_current) || 0;
                        row.ekor_item_global_stock = parseFloat(data.stok_ekor_global) || 0;
                        row.berat_item_global_stock = parseFloat(data.stok_berat_global) || 0;

                        // Untuk barcode hasil gabungan, set nilai baru = stok saat ini
                        if (row.keterangan === 'Hasil gabungan barcode') {
                            row.ekor_new = row.ekor_stock_current;
                            row.berat_new = row.berat_stock_current;
                        } else {
                            // Untuk barcode biasa, prefilled dengan stok saat ini
                            row.ekor_new = row.ekor_stock_current;
                            row.berat_new = row.berat_stock_current;
                        }

                        row.keterangan = row.keterangan || '';
                        row.isEdited = false;

                        await this.saveToDatabase(row);

                        // ========== PERBAIKAN: TAMBAH ROW KOSONG DAN FOCUS KE BARIS YANG DI-SCAN ==========
                        await this.addEmptyRowAndFocusToScannedRow(rowId);

                    } else {
                        Swal.fire('Tidak Ditemukan', `Barcode '${barcode}' ${result.message}`, 'error');
                        row.barcode = '';
                        row.id_in = null;
                        row.item = '';
                        row.subitem = '';
                        row.ekor_stock_current = null;
                        row.berat_stock_current = null;
                        row.ekor_new = null;
                        row.berat_new = null;
                        row.keterangan = '';
                        row.isEdited = false;
                        row.user_in = '';
                        row.created_at = '';
                    }
                } catch (error) {
                    Swal.fire('Error', 'Terjadi kesalahan saat mencari barcode: ' + error.message, 'error');
                    console.error('Fetch error:', error);
                    row.barcode = '';
                    row.id_in = null;
                    row.item = '';
                    row.subitem = '';
                    row.ekor_stock_current = null;
                    row.berat_stock_current = null;
                    row.ekor_new = null;
                    row.berat_new = null;
                    row.keterangan = '';
                    row.isEdited = false;
                    row.user_in = '';
                    row.created_at = '';
                } finally {
                    this.isProcessing = false;
                }
            },

            loadExistingDataFromAdjustTmp(existingData, currentRow) {
                if (!existingData) return;

                // Cari apakah data sudah ada di adjustmentList
                const existingRow = this.adjustmentList.find(row =>
                    row.id === existingData.row_unique_id
                );

                if (existingRow) {
                    // Data sudah ada, focus saja
                    this.focusToExistingRow(existingRow.barcode);
                    return;
                }

                // Jika belum ada, tambahkan ke adjustmentList
                const newRow = {
                    id: existingData.row_unique_id,
                    id_in: null,
                    barcode: currentRow.barcode, // Gunakan barcode dari input
                    storage: this.selectedStorage,
                    item: existingData.item || '',
                    subitem: existingData.subitem || '',
                    ekor_stock_current: parseFloat(existingData.ekor_stock_current) || 0,
                    berat_stock_current: parseFloat(existingData.berat_stock_current) || 0,
                    ekor_new: parseFloat(existingData.ekor_new) || 0,
                    berat_new: parseFloat(existingData.berat_new) || 0,
                    keterangan: existingData.keterangan || '',
                    isEdited: false,
                    isDetailsCollapsed: true,
                    user_input: this.selectedUser,
                    user_in: '',
                    created_at: '',
                };

                // Tambahkan ke daftar
                this.adjustmentList.push(newRow);

                // Sortir berdasarkan created_at (data lama di atas, baru di bawah)
                this.sortAdjustmentList();

                // Focus ke baris yang baru ditambahkan
                setTimeout(() => {
                    this.focusToExistingRow(newRow.barcode);
                }, 500);
            },

            sortAdjustmentList() {
                // Asumsi: row.id yang lebih kecil = dibuat lebih dulu
                this.adjustmentList.sort((a, b) => a.id - b.id);
            },

            // ========== METODE LAINNYA (TIDAK BERUBAH) ==========
            async fetchInitialData() {
                this.isFetchingItems = true;
                try {
                    const userResponse = await fetch(`${this.adjustTmpApiEndpoint}?action=get_user_list`);
                    const userResult = await userResponse.json();

                    if (userResult.success) {
                        let fetchedUsers = userResult.data;
                        if (this.currentUserFromSession && !fetchedUsers.includes(this.currentUserFromSession)) {
                            fetchedUsers.unshift(this.currentUserFromSession);
                        }
                        this.userOptions = fetchedUsers;

                        if (this.currentUserFromSession && this.userOptions.includes(this.currentUserFromSession)) {
                            this.selectedUser = this.currentUserFromSession;
                        } else if (this.currentUserFromSession && this.selectedUser === '') {
                            this.selectedUser = this.currentUserFromSession;
                        }
                    } else {
                        console.error('Failed to load user list:', userResult.message);
                        if (this.currentUserFromSession && !this.userOptions.includes(this.currentUserFromSession)) {
                            this.userOptions.unshift(this.currentUserFromSession);
                            this.selectedUser = this.currentUserFromSession;
                        } else {
                            this.userOptions = [];
                            this.selectedUser = '';
                        }
                    }

                    const [itemResponse, subitemResponse] = await Promise.all([
                        fetch(`${this.apiEndpoint}?action=get_item_list`),
                        fetch(`${this.apiEndpoint}?action=get_subitem_list`)
                    ]);

                    const itemResult = await itemResponse.json();
                    if (itemResult.success) {
                        this.itemOptions = itemResult.data.map(item => item.item);
                    } else {
                        console.error('Failed to load item list:', itemResult.message);
                    }

                    const subitemResult = await subitemResponse.json();
                    if (subitemResult.success) {
                        this.subitemOptions = subitemResult.data.map(item => item.subitem);
                    } else {
                        console.error('Failed to load subitem list:', subitemResult.message);
                    }

                    await this.fetchAdjustmentsAndApplyFilter();

                } catch (error) {
                    console.error('Error loading initial data:', error);
                } finally {
                    this.isFetchingItems = false;
                }
            },

            cleanItemSubitem(row) {
                if (!row.item) return;

                // Jika item mengandung ' : ' dan subitem ada, bersihkan item
                if (row.item.includes(' : ') && row.subitem) {
                    const parts = row.item.split(' : ');
                    // Jika bagian terakhir sama dengan subitem, hapus dari item
                    if (parts.length > 1 && parts[parts.length - 1] === row.subitem) {
                        row.item = parts.slice(0, -1).join(' : ').trim();
                    }
                }

                // Jika setelah pembersihan, item masih mengandung subitem yang sama
                if (row.item.endsWith(' : ' + row.subitem)) {
                    row.item = row.item.replace(' : ' + row.subitem, '');
                }
            },

            async fetchAdjustmentsAndApplyFilter() {
                if (!this.adjustmentDate || !this.selectedStorage || !this.adjustmentType) {
                    console.log("Filter utama belum lengkap, tidak mengambil dari DB.");
                    this.allAdjustments = [];
                    this.adjustmentList = [];
                    this.addRow();
                    return;
                }

                this.isProcessing = true;
                try {
                    const response = await fetch(`${this.adjustTmpApiEndpoint}?action=get_adjustments&date=${this.adjustmentDate}&storage=${this.selectedStorage}&type=${this.adjustmentType}`);
                    const result = await response.json();

                    if (result.success) {
                        this.allAdjustments = result.data.map(row => {
                            // Bersihkan data double subitem
                            let item = row.item || '';
                            let subitem = row.subitem || '';

                            // Bersihkan item jika mengandung subitem ganda
                            if (item.includes(' : ') && subitem) {
                                const parts = item.split(' : ');
                                // Jika bagian terakhir sama dengan subitem
                                if (parts.length > 1 && parts[parts.length - 1] === subitem) {
                                    item = parts.slice(0, -1).join(' : ').trim();
                                }
                                // Jika item masih berakhir dengan subitem
                                if (item.endsWith(' : ' + subitem)) {
                                    item = item.replace(' : ' + subitem, '');
                                }
                            }

                            return {
                                ...row,
                                id: row.row_unique_id,
                                item: item,
                                subitem: subitem,
                                ekor_stock_current: parseFloat(row.ekor_stock_current || '0'),
                                berat_stock_current: parseFloat(row.berat_stock_current || '0'),
                                ekor_new: parseFloat(row.ekor_new || '0'),
                                berat_new: parseFloat(row.berat_new || '0'),
                                isEdited: row.is_edited == 1,
                                isDetailsCollapsed: row.is_details_collapsed == 1,
                                user_input: row.user_input || 'N/A'
                            };
                        });

                        this.applyUserFilter();
                    } else {
                        console.error('Gagal mengambil adjustment:', result.message);
                        this.allAdjustments = [];
                        this.adjustmentList = [];
                    }
                } catch (error) {
                    console.error('Error saat mengambil adjustment:', error);
                    this.allAdjustments = [];
                    this.adjustmentList = [];
                } finally {
                    this.isProcessing = false;
                    if (this.adjustmentList.length === 0) {
                        this.addRow();
                    }
                }
            },

            applyUserFilter() {
                if (this.selectedUser === '') {
                    this.adjustmentList = this.allAdjustments;
                } else {
                    this.adjustmentList = this.allAdjustments.filter(row => row.user_input === this.selectedUser);
                }
                if (this.adjustmentList.length === 0) {
                    this.addRow();
                }
            },

            cleanItemBeforeSave(row) {
                if (!row.item) return row;

                // Buat salinan row untuk menghindari mutasi langsung
                const cleanedRow = {
                    ...row
                };

                // Jika item mengandung ' : ' dan subitem sudah ada
                if (cleanedRow.item.includes(' : ') && cleanedRow.subitem) {
                    const parts = cleanedRow.item.split(' : ');
                    // Jika bagian terakhir sama dengan subitem, hapus dari item
                    if (parts.length > 1 && parts[parts.length - 1] === cleanedRow.subitem) {
                        cleanedRow.item = parts.slice(0, -1).join(' : ').trim();
                    }
                }

                // Jika item masih mengandung subitem (kasus double)
                if (cleanedRow.item.endsWith(' : ' + cleanedRow.subitem)) {
                    cleanedRow.item = cleanedRow.item.replace(' : ' + cleanedRow.subitem, '');
                }

                return cleanedRow;
            },

            async saveToDatabase(row) {
                if (!this.adjustmentDate || !this.selectedStorage || !this.adjustmentType || !row.item) {
                    console.error("Attempted to save incomplete row:", row);
                    return;
                }

                // Bersihkan data sebelum disimpan
                const cleanedRow = this.cleanItemBeforeSave(row);

                this.isSaving = true;
                try {
                    const dataToSave = {
                        action: 'save_adjustment',
                        user_input: this.currentUserFromSession,
                        adjustment_date: this.adjustmentDate,
                        storage: this.selectedStorage,
                        adjustment_type: this.adjustmentType,
                        row_unique_id: cleanedRow.id,
                        id_in: cleanedRow.id_in,
                        barcode: cleanedRow.barcode,
                        item: cleanedRow.item, // Sudah dibersihkan
                        subitem: cleanedRow.subitem, // Subitem terpisah
                        ekor_stock_current: cleanedRow.ekor_stock_current,
                        berat_stock_current: cleanedRow.berat_stock_current,
                        ekor_new: cleanedRow.ekor_new,
                        berat_new: cleanedRow.berat_new,
                        keterangan: cleanedRow.keterangan,
                        is_edited: cleanedRow.isEdited ? 1 : 0,
                        is_details_collapsed: cleanedRow.isDetailsCollapsed ? 1 : 0
                    };

                    const response = await fetch(this.adjustTmpApiEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(dataToSave)
                    });
                    const result = await response.json();

                    if (result.success) {
                        console.log('Row saved to database:', result.message);
                    } else {
                        console.error('Failed to save row to database:', result.message);
                        Swal.fire('Error', 'Gagal menyimpan data ke database: ' + result.message, 'error');
                    }
                } catch (error) {
                    console.error('Error saving row:', error);
                    Swal.fire('Error', 'Terjadi kesalahan saat menyimpan data.', 'error');
                } finally {
                    this.isSaving = false;
                }
            },

            addRow() {
                // Cek apakah ada baris kosong yang bisa diisi
                const emptyRow = this.adjustmentList.find(row =>
                    (!row.barcode || row.barcode.trim() === '') &&
                    (!row.item || row.item.trim() === '') &&
                    (row.ekor_new === null || row.ekor_new === 0) &&
                    (row.berat_new === null || row.berat_new === 0)
                );

                if (emptyRow) {
                    // Ada baris kosong, focus ke baris tersebut
                    this.$nextTick(() => {
                        const barcodeInput = this.$el.querySelector(`[data-row-id="${emptyRow.id}"] input[name="barcode"]`);
                        if (barcodeInput) {
                            barcodeInput.focus();
                        }
                    });

                    Swal.fire({
                        icon: 'info',
                        title: 'Ada Baris Kosong',
                        text: 'Masih ada baris kosong yang bisa diisi. Silakan isi baris tersebut terlebih dahulu.',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                // Jika tidak ada baris kosong, buat baris baru
                const newRow = {
                    id: Date.now(),
                    id_in: null,
                    barcode: '',
                    storage: this.selectedStorage,
                    item: '',
                    subitem: '',
                    ekor_stock_current: null,
                    berat_stock_current: null,
                    ekor_new: null,
                    berat_new: null,
                    keterangan: '',
                    isEdited: false,
                    isDetailsCollapsed: true,
                    user_input: this.selectedUser || this.currentUserFromSession,
                    tanggal: this.adjustmentDate,
                    type: this.adjustmentType,
                };
                this.adjustmentList.push(newRow);

                this.$nextTick(() => {
                    const newBarcodeInput = this.$el.querySelector(`[data-row-id="${newRow.id}"] input[name="barcode"]`);
                    if (newBarcodeInput) {
                        newBarcodeInput.focus();
                    } else {
                        const fallbackInput = this.$el.querySelector(`tbody tr:last-child input[name="barcode"]`);
                        if (fallbackInput) fallbackInput.focus();
                    }
                });
            },

            async removeRow(rowId) {
                const index = this.adjustmentList.findIndex(row => row.id === rowId);
                if (index > -1) {
                    const rowToDelete = this.adjustmentList[index];

                    if (rowToDelete.id_in || (rowToDelete.barcode && rowToDelete.barcode.trim() !== '')) {
                        const confirmDelete = await Swal.fire({
                            title: 'Konfirmasi Hapus',
                            text: "Anda yakin ingin menghapus baris ini dari daftar? Ini juga akan menghapus dari database jika sudah tersimpan.",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Ya, hapus!',
                            cancelButtonText: 'Batal'
                        });

                        if (!confirmDelete.isConfirmed) {
                            return;
                        }

                        try {
                            const response = await fetch(this.adjustTmpApiEndpoint, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    action: 'delete_adjustment',
                                    row_unique_id: rowToDelete.id,
                                    adjustment_date: this.adjustmentDate,
                                    storage: this.selectedStorage,
                                    adjustment_type: this.adjustmentType,
                                    barcode: rowToDelete.barcode,
                                    id_in: rowToDelete.id_in
                                })
                            });
                            const result = await response.json();

                            if (result.success) {
                                Swal.fire('Dihapus!', 'Data telah dihapus dari database.', 'success');
                                this.adjustmentList.splice(index, 1);
                            } else {
                                Swal.fire('Error', 'Gagal menghapus data dari database: ' + result.message, 'error');
                            }
                        } catch (error) {
                            Swal.fire('Error', 'Terjadi kesalahan saat menghapus data: ' + error.message, 'error');
                            console.error('Fetch error:', error);
                        }
                    } else {
                        this.adjustmentList.splice(index, 1);
                    }
                }

                if (this.adjustmentList.length === 0) {
                    this.addRow();
                }
            },

            async updateRowStatus(row) {
                const ekorNew = parseFloat(row.ekor_new) || 0;
                const ekorCurrent = parseFloat(row.ekor_stock_current) || 0;
                const beratNew = parseFloat(row.berat_new) || 0;
                const beratCurrent = parseFloat(row.berat_stock_current) || 0;

                row.isEdited = (ekorNew !== ekorCurrent) ||
                    (beratNew !== beratCurrent) ||
                    (row.keterangan && row.keterangan.trim() !== '');

                if (row.isEdited || row.id_in === null) {
                    await this.saveToDatabase(row);

                    // Jika user edit nilai, focus tetap di input yang sama
                    this.$nextTick(() => {
                        const rowElement = this.$el.querySelector(`[data-row-id="${row.id}"]`);
                        if (rowElement) {
                            const activeElement = document.activeElement;
                            const isEditingThisRow = rowElement.contains(activeElement);

                            if (isEditingThisRow && activeElement) {
                                // User masih di input yang sama, biarkan focus
                                setTimeout(() => {
                                    activeElement.focus();
                                }, 100);
                            }
                        }
                    });
                }
            },


            get isFormValid() {
                return this.adjustmentDate && this.selectedStorage && this.adjustmentType;
            },

            get hasChanges() {
                return this.adjustmentList.some(row => row.isEdited);
            },

            async _performReset() {
                this.isProcessing = true;
                try {
                    const response = await fetch(this.adjustTmpApiEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'reset_adjustments',
                            adjustment_date: this.adjustmentDate,
                            storage: this.selectedStorage,
                            adjustment_type: this.adjustmentType,
                            user_input: this.currentUserFromSession
                        })
                    });
                    const result = await response.json();

                    if (result.success) {
                        this.adjustmentList = [];
                        this.addRow();
                        Swal.fire('Dihapus!', 'Semua data Adjustment telah direset dari database.', 'success');
                    } else {
                        Swal.fire('Error', 'Gagal mereset data: ' + result.message, 'error');
                    }
                } catch (error) {
                    Swal.fire('Error', 'Terjadi kesalahan saat mereset data: ' + error.message, 'error');
                    console.error('Fetch error:', error);
                } finally {
                    this.isProcessing = false;
                }
            },

            resetAllForms() {
                Swal.fire({
                    title: 'Konfirmasi Reset',
                    text: "Anda yakin ingin menghapus semua data Adjustment yang belum disimpan dan yang sudah tersimpan untuk filter ini?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, hapus semua!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this._performReset();
                    }
                });
            },

            watch: {
                itemSearchQuery: function(value) {
                    this.addDataModal.data.item = value;
                },
                subitemSearchQuery: function(value) {
                    this.addDataModal.data.subitem = value;
                }
            },

            selectItem(item) {
                this.itemSearchQuery = item;
                this.addDataModal.data.item = item;
                this.itemDropdownOpen = false;
            },

            get filteredItems() {
                if (!this.itemSearchQuery) return this.itemOptions;
                const query = this.itemSearchQuery.toLowerCase();
                return this.itemOptions.filter(item => item.toLowerCase().includes(query));
            },

            subitemSearchQuery: '',
            subitemDropdownOpen: false,

            selectSubitem(subitem) {
                this.subitemSearchQuery = subitem;
                this.addDataModal.data.subitem = subitem;
                this.subitemDropdownOpen = false;
            },

            get filteredSubitems() {
                if (!this.subitemSearchQuery && this.subitemDropdownOpen) {
                    return this.subitemOptions;
                }
                if (this.subitemSearchQuery) {
                    const query = this.subitemSearchQuery.toLowerCase();
                    return this.subitemOptions.filter(subitem => subitem.toLowerCase().includes(query));
                }
                return [];
            },

            resetAddDataForm() {
                this.addDataModal.data = {
                    barcode: '',
                    item: '',
                    subitem: '',
                    id_in: null,
                    ekor_stock_current: 0,
                    berat_stock_current: 0,
                    ekor_new: null,
                    berat_new: null,
                    keterangan: 'Adjustment data tambahan'
                };
                this.itemSearchQuery = '';
                this.itemDropdownOpen = false;
                this.subitemSearchQuery = '';
                this.subitemDropdownOpen = false;

                this.$nextTick(() => {
                    const itemInputEl = this.$el.querySelector('#new-item-custom');
                    const subitemInputEl = this.$el.querySelector('#new-subitem-custom');

                    if (itemInputEl) itemInputEl.value = '';
                    if (subitemInputEl) subitemInputEl.value = '';
                });
            },

            openAddDataModal() {
                if (!this.adjustmentDate || !this.selectedStorage || !this.adjustmentType) {
                    Swal.fire('Peringatan', 'Silakan isi Tanggal, Storage, dan Tipe Adjustment terlebih dahulu.', 'warning');
                    return;
                }

                this.itemSearchQuery = this.addDataModal.data.item;
                this.subitemSearchQuery = this.addDataModal.data.subitem;

                this.addDataModal.data.barcode = '';
                this.addDataModal.data.ekor_stock_current = 0;
                this.addDataModal.data.berat_stock_current = 0;
                this.addDataModal.data.ekor_new = null;
                this.addDataModal.data.berat_new = null;
                this.addDataModal.data.keterangan = 'Adjustment manual (dari modal)';

                this.itemDropdownOpen = false;
                this.subitemDropdownOpen = false;

                this.addDataModal.show = true;

                this.$nextTick(() => {
                    const newBarcodeInput = this.$el.querySelector('#new-barcode');
                    if (newBarcodeInput) {
                        newBarcodeInput.focus();
                    }
                });
            },

            async openAdjustmentModal() {
                if (!this.selectedStorage) {
                    Swal.fire('Peringatan', 'Silakan pilih gudang (storage) terlebih dahulu.', 'warning');
                    return;
                }
                if (!this.adjustmentDate || !this.adjustmentType) {
                    Swal.fire('Peringatan', 'Silakan isi tanggal dan tipe Adjustment.', 'warning');
                    return;
                }

                this.isProcessing = true;
                this.modal.data.barcodes = [];
                this.modal.data.global = [];

                try {
                    console.log('Mengambil stok saat ini untuk gudang:', this.selectedStorage, 'Tanggal:', this.adjustmentDate);
                    // PERBAIKAN: Kirim adjustment_date sebagai parameter
                    const stockResponse = await fetch(`${this.apiEndpoint}?action=get_current_stock&storage=${this.selectedStorage}&adjustment_date=${this.adjustmentDate}`);
                    const stockResult = await stockResponse.json();

                    if (!stockResult.success) {
                        Swal.fire('Error', stockResult.message, 'error');
                        return;
                    }

                    console.log('Data stok dari backend untuk tanggal', this.adjustmentDate, ':', stockResult.data);

                    console.log('=== MEMPROSES ADJUSTMENT BARCODE ===');
                    const barcodeAdjustments = [];

                    const barcodeRows = this.adjustmentList.filter(row => row.barcode && row.barcode.trim() !== '');
                    console.log('Total rows dengan barcode:', barcodeRows.length);

                    barcodeRows.forEach((row, index) => {
                        const ekorNew = parseFloat(row.ekor_new) || 0;
                        const beratNew = parseFloat(row.berat_new) || 0;

                        const ekorAdjustment = ekorNew;
                        const beratAdjustment = beratNew;

                        // Format keterangan: "SO-YYYY-MM-DD - [keterangan]" jika ada keterangan
                        let finalKeterangan = '';
                        const baseKeterangan = `SO-${this.adjustmentDate}`;

                        if (row.keterangan && row.keterangan.trim() !== '') {
                            // Jika keterangan sudah mengandung format SO-YYYY-MM-DD, gunakan aslinya
                            if (row.keterangan.includes(`SO-${this.adjustmentDate}`)) {
                                finalKeterangan = row.keterangan;
                            } else {
                                // Jika ada keterangan tambahan, gabungkan dengan format
                                finalKeterangan = `${baseKeterangan} - ${row.keterangan}`;
                            }
                        } else {
                            // Jika tidak ada keterangan, hanya gunakan base
                            finalKeterangan = baseKeterangan;
                        }

                        barcodeAdjustments.push({
                            id: row.id,
                            id_in: row.id_in || null,
                            barcode: row.barcode,
                            item: row.item,
                            subitem: row.subitem || '',
                            storage: this.selectedStorage,
                            ekor_adjustment: ekorAdjustment,
                            berat_adjustment: beratAdjustment,
                            keterangan: finalKeterangan,
                            user_in: row.user_in,
                            created_at: row.created_at,
                            user_input: row.user_input,
                        });

                        console.log(`Barcode ${index + 1}: ${row.barcode} - Adjustment: ${ekorAdjustment} ekor, ${beratAdjustment.toFixed(2)} kg - Keterangan: ${finalKeterangan}`);
                    });

                    console.log('Total Barcode Adjustments:', barcodeAdjustments.length);

                    console.log('=== MEMPROSES ADJUSTMENT GLOBAL ===');
                    const globalAdjustments = [];

                    stockResult.data.forEach((stockItem, index) => {
                        const totalEkor = parseFloat(stockItem.total_ekor) || 0;
                        const totalBerat = parseFloat(stockItem.total_berat) || 0;

                        if (totalEkor !== 0 || totalBerat !== 0) {
                            globalAdjustments.push({
                                item: stockItem.item,
                                subitem: stockItem.subitem || '',
                                barcode: '-',
                                ekor_stock_current: totalEkor,
                                berat_stock_current: totalBerat,
                                ekor_adjustment: -totalEkor,
                                berat_adjustment: -totalBerat,
                                keterangan: `Reset stok global ${stockItem.item}${stockItem.subitem ? ':' + stockItem.subitem : ''}`
                            });

                            console.log(`Global ${index + 1}: ${stockItem.item}:${stockItem.subitem} - Stok: ${totalEkor} ekor, ${totalBerat.toFixed(2)} kg → Adjustment: ${-totalEkor} ekor, ${-totalBerat.toFixed(2)} kg`);
                        }
                    });

                    console.log('Total Global Adjustments:', globalAdjustments.length);

                    console.log('=== MEMPROSES ITEM BARU (MANUAL) ===');
                    const manualRows = this.adjustmentList.filter(row => !row.barcode || row.barcode.trim() === '');
                    console.log('Total rows manual (tanpa barcode):', manualRows.length);

                    manualRows.forEach((row, index) => {
                        const ekorNew = parseFloat(row.ekor_new) || 0;
                        const beratNew = parseFloat(row.berat_new) || 0;

                        if (ekorNew > 0 || beratNew > 0) {
                            // Format keterangan: "SO-YYYY-MM-DD - [keterangan]" jika ada keterangan
                            const baseKeterangan = `SO-${this.adjustmentDate}`;
                            let finalKeterangan = '';

                            if (row.keterangan && row.keterangan.trim() !== '') {
                                // Jika keterangan sudah mengandung format SO-YYYY-MM-DD, gunakan aslinya
                                if (row.keterangan.includes(`SO-${this.adjustmentDate}`)) {
                                    finalKeterangan = row.keterangan;
                                } else {
                                    // Jika ada keterangan tambahan, gabungkan dengan format
                                    finalKeterangan = `${baseKeterangan} - ${row.keterangan}`;
                                }
                            } else {
                                // Jika tidak ada keterangan, hanya gunakan base
                                finalKeterangan = baseKeterangan;
                            }

                            globalAdjustments.push({
                                id: row.id,
                                item: row.item,
                                subitem: row.subitem || '',
                                barcode: '-',
                                ekor_stock_current: 0,
                                berat_stock_current: 0,
                                ekor_adjustment: ekorNew,
                                berat_adjustment: beratNew,
                                keterangan: finalKeterangan,
                                user_input: row.user_input,
                            });

                            console.log(`Item Baru ${index + 1}: ${row.item}:${row.subitem} - Adjustment: +${ekorNew} ekor, +${beratNew.toFixed(2)} kg - Keterangan: ${finalKeterangan}`);
                        }
                    });

                    console.log('=== HASIL AKHIR ===');
                    console.log('Total semua rows di adjustmentList:', this.adjustmentList.length);
                    console.log('Barcode Adjustments:', barcodeAdjustments.length);
                    console.log('Manual/Global Adjustments:', globalAdjustments.length);
                    console.log('Total semua adjustments:', barcodeAdjustments.length + globalAdjustments.length);

                    if (barcodeAdjustments.length === 0 && globalAdjustments.length === 0) {
                        Swal.fire('Informasi', 'Tidak ada data yang perlu disesuaikan.', 'info');
                        return;
                    }

                    const totalBarcodeEkor = barcodeAdjustments.reduce((sum, adj) => sum + adj.ekor_adjustment, 0);
                    const totalBarcodeBerat = barcodeAdjustments.reduce((sum, adj) => sum + adj.berat_adjustment, 0);
                    const totalGlobalEkor = globalAdjustments.reduce((sum, adj) => sum + adj.ekor_adjustment, 0);
                    const totalGlobalBerat = globalAdjustments.reduce((sum, adj) => sum + adj.berat_adjustment, 0);

                    console.log(`Total Barcode: ${totalBarcodeEkor} ekor, ${totalBarcodeBerat.toFixed(2)} kg`);
                    console.log(`Total Global: ${totalGlobalEkor} ekor, ${totalGlobalBerat.toFixed(2)} kg`);
                    console.log(`Grand Total: ${totalBarcodeEkor + totalGlobalEkor} ekor, ${(totalBarcodeBerat + totalGlobalBerat).toFixed(2)} kg`);

                    this.modal.data.barcodes = barcodeAdjustments;
                    this.modal.data.global = globalAdjustments;

                    this.modal.totals = {
                        barcode: {
                            ekor: totalBarcodeEkor,
                            berat: totalBarcodeBerat,
                            count: barcodeAdjustments.length
                        },
                        global: {
                            ekor: totalGlobalEkor,
                            berat: totalGlobalBerat,
                            count: globalAdjustments.length
                        },
                        grand: {
                            ekor: totalBarcodeEkor + totalGlobalEkor,
                            berat: totalBarcodeBerat + totalGlobalBerat,
                            count: barcodeAdjustments.length + globalAdjustments.length
                        }
                    };

                    this.modal.show = true;

                } catch (error) {
                    Swal.fire('Error', 'Terjadi kesalahan saat membuka modal Adjustment: ' + error.message, 'error');
                    console.error('Fetch error:', error);
                } finally {
                    this.isProcessing = false;
                }
            },

            async saveAdjustments() {
                this.isSaving = true;

                try {
                    let globalSaveResult = {
                        success: true,
                        message: ''
                    };
                    let barcodeSaveResult = {
                        success: true,
                        message: ''
                    };

                    if (this.modal.data.global.length > 0) {
                        const globalPayload = {
                            action: 'save_global_adjustments',
                            adjustment_date: this.adjustmentDate,
                            adjustment_type: this.adjustmentType,
                            storage: this.selectedStorage,
                            details: this.modal.data.global.map(item => ({
                                item: item.item,
                                subitem: item.subitem || '',
                                ekor_stock_current: item.ekor_stock_current,
                                berat_stock_current: item.berat_stock_current,
                                ekor_new: 0,
                                berat_new: 0,
                                ekor_adjustment: item.ekor_adjustment,
                                berat_adjustment: item.berat_adjustment,
                                keterangan: item.keterangan,
                                user_input: item.user_input || ''
                            }))
                        };

                        console.log('Mengirim Global Payload:', globalPayload);

                        const globalResponse = await fetch(this.apiEndpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(globalPayload),
                        });
                        globalSaveResult = await globalResponse.json();
                        console.log('Response Global:', globalSaveResult);
                    }

                    if (globalSaveResult.success && this.modal.data.barcodes.length > 0) {
                        const barcodePayload = {
                            action: 'save_barcode_adjustments',
                            adjustment_date: this.adjustmentDate,
                            adjustment_type: this.adjustmentType,
                            storage: this.selectedStorage,
                            details: this.modal.data.barcodes.map(item => ({
                                id_in: item.id_in || null,
                                barcode: item.barcode || '',
                                item: item.item,
                                subitem: item.subitem || '',
                                ekor_stock_current: 0,
                                berat_stock_current: 0,
                                ekor_new: item.ekor_adjustment,
                                berat_new: item.berat_adjustment,
                                ekor_adjustment: item.ekor_adjustment,
                                berat_adjustment: item.berat_adjustment,
                                keterangan: item.keterangan,
                                user_input: item.user_input
                            }))
                        };

                        console.log('Mengirim Barcode Payload:', barcodePayload);

                        const barcodeResponse = await fetch(this.apiEndpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(barcodePayload),
                        });
                        barcodeSaveResult = await barcodeResponse.json();
                        console.log('Response Barcode:', barcodeSaveResult);
                    }

                    if (globalSaveResult.success && barcodeSaveResult.success) {
                        const result = await Swal.fire({
                            title: 'Berhasil!',
                            text: 'Adjustment stok berhasil disimpan. Apakah Anda ingin mendownload laporan?',
                            icon: 'success',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Ya, Download Laporan',
                            cancelButtonText: 'Tidak, Tutup Saja'
                        });

                        if (result.isConfirmed) {
                            await this.generateAndDownloadExcelReport();
                        }

                        this.modal.show = false;
                        this._performReset();
                    } else {
                        let errorMessage = '';
                        if (!globalSaveResult.success) {
                            errorMessage += `Adjustment Global Gagal: ${globalSaveResult.message}. `;
                        }
                        if (!barcodeSaveResult.success) {
                            errorMessage += `Adjustment Barcode Gagal: ${barcodeSaveResult.message}.`;
                        }
                        Swal.fire('Gagal', errorMessage || 'Terjadi kesalahan saat menyimpan Adjustment.', 'error');
                    }

                } catch (error) {
                    console.error('Error saat menyimpan adjustment:', error);
                    Swal.fire('Error', 'Gagal menyimpan data adjustment: ' + error.message, 'error');
                } finally {
                    this.isSaving = false;
                }
            },

            async generateAndDownloadExcelReport() {
                try {
                    const allAdjustments = [
                        ...this.modal.data.barcodes.map(item => ({
                            ...item,
                            type: 'Barcode',
                            ekor_new: item.ekor_adjustment,
                            berat_new: item.berat_adjustment
                        })),
                        ...this.modal.data.global.map(item => ({
                            ...item,
                            type: 'Global',
                            ekor_new: item.ekor_stock_current + item.ekor_adjustment,
                            berat_new: item.berat_stock_current + item.berat_adjustment
                        }))
                    ];

                    const wb = XLSX.utils.book_new();
                    const wsData = [];

                    wsData.push(['LAPORAN ADJUSTMENT STOK']);
                    wsData.push(['Tanggal Adjustment', this.adjustmentDate]);
                    wsData.push(['Lokasi Storage', this.selectedStorage]);
                    wsData.push(['Tipe Adjustment', this.adjustmentType]);
                    wsData.push(['User Sistem', this.currentUserFromSession]);
                    wsData.push([]);

                    wsData.push([
                        'No',
                        'Tipe',
                        'Barcode',
                        'Item',
                        'Subitem',
                        'Stok Awal (Ekor)',
                        'Stok Baru (Ekor)',
                        'Adjustment (Ekor)',
                        'Stok Awal (Kg)',
                        'Stok Baru (Kg)',
                        'Adjustment (Kg)',
                        'User Input',
                        'Keterangan'
                    ]);

                    allAdjustments.forEach((item, index) => {
                        wsData.push([
                            index + 1,
                            item.type,
                            item.barcode || '-',
                            item.item,
                            item.subitem || '-',
                            item.ekor_stock_current || 0,
                            item.ekor_new || 0,
                            item.ekor_adjustment,
                            (item.berat_stock_current || 0).toFixed(2),
                            (item.berat_new || 0).toFixed(2),
                            item.berat_adjustment.toFixed(2),
                            item.user_input || '-',
                            item.keterangan || '-'
                        ]);
                    });

                    wsData.push([]);
                    wsData.push(['RINGKASAN TOTAL:']);
                    wsData.push([
                        '', '', '', '', '',
                        `Adjustment Barcode: ${this.modal.totals.barcode.ekor > 0 ? '+' + this.modal.totals.barcode.ekor : this.modal.totals.barcode.ekor} Ekor`,
                        `Adjustment Barcode: ${this.modal.totals.barcode.berat > 0 ? '+' + this.modal.totals.barcode.berat.toFixed(2) : this.modal.totals.barcode.berat.toFixed(2)} Kg`,
                        '', '', '', '', ''
                    ]);
                    wsData.push([
                        '', '', '', '', '',
                        `Adjustment Global: ${this.modal.totals.global.ekor > 0 ? '+' + this.modal.totals.global.ekor : this.modal.totals.global.ekor} Ekor`,
                        `Adjustment Global: ${this.modal.totals.global.berat > 0 ? '+' + this.modal.totals.global.berat.toFixed(2) : this.modal.totals.global.berat.toFixed(2)} Kg`,
                        '', '', '', '', ''
                    ]);
                    wsData.push([
                        '', '', '', '', '',
                        `TOTAL KESELURUHAN: ${this.modal.totals.grand.ekor > 0 ? '+' + this.modal.totals.grand.ekor : this.modal.totals.grand.ekor} Ekor`,
                        `TOTAL KESELURUHAN: ${this.modal.totals.grand.berat > 0 ? '+' + this.modal.totals.grand.berat.toFixed(2) : this.modal.totals.grand.berat.toFixed(2)} Kg`,
                        '', '', '', '', ''
                    ]);

                    const ws = XLSX.utils.aoa_to_sheet(wsData);

                    const colWidths = [{
                        wch: 5
                    }, {
                        wch: 10
                    }, {
                        wch: 15
                    }, {
                        wch: 20
                    }, {
                        wch: 15
                    }, {
                        wch: 15
                    }, {
                        wch: 15
                    }, {
                        wch: 15
                    }, {
                        wch: 15
                    }, {
                        wch: 15
                    }, {
                        wch: 15
                    }, {
                        wch: 15
                    }, {
                        wch: 30
                    }];
                    ws['!cols'] = colWidths;

                    XLSX.utils.book_append_sheet(wb, ws, 'Laporan Adjustment');

                    const fileName = `Laporan_Adjustment_${this.adjustmentDate}_${this.selectedStorage}_${new Date().getTime()}.xlsx`;

                    XLSX.writeFile(wb, fileName);

                } catch (error) {
                    console.error('Error generating Excel report:', error);
                    Swal.fire('Error', 'Gagal membuat laporan Excel: ' + error.message, 'error');
                }
            }
        }));
    });
</script>