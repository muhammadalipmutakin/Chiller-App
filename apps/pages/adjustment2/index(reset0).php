<main class="container mx-auto px-4 py-8" x-data="adjustmentApp()" x-init="init()" x-cloak>

    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Adjustment Barcode</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
            <div>
                <label class="block text-xs font-medium text-gray-700">Tanggal Adjustment:</label>
                <input type="date" x-model="adjustmentDate"
                    class="w-full px-3 py-2 border rounded-md shadow-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700">Storage:</label>
                <select x-model="selectedStorage" @change="onFilterChange()"
                    class="w-full px-3 py-2 border rounded-md shadow-sm">
                    <option value="">Pilih Storage</option>
                    <template x-for="storage in allStorages" :key="storage">
                        <option :value="storage" x-text="storage"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700">Tipe Adjustment:</label>
                <select x-model="adjustmentType"
                    class="w-full px-3 py-2 border rounded-md shadow-sm">
                    <option value="">Pilih Tipe</option>
                    <option value="stok_opname_mingguan">Stok Opname Mingguan</option>
                    <option value="stok_chiller_harian">Stok Chiller Harian</option>
                </select>
            </div>
        </div>
        <div class="flex justify-end">
            <button @click="resetAllForms" type="button"
                class="inline-flex items-center px-4 py-2 border border-red-300 text-xs font-medium rounded-md text-red-600 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-200">
                <i class="fas fa-undo mr-2"></i> Reset Semua Data
            </button>
        </div>
    </div>

    <div class="mb-4">
        <label for="userFilter" class="block text-xs font-medium text-gray-700">Filter by User:</label>
        <select id="userFilter" x-model="selectedUser" @change="onFilterChange()"
            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-xs rounded-md">
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
    <div class="overflow-x-auto bg-white rounded-lg shadow-md mb-8 table-container">
        <table class="min-w-full divide-y divide-gray-200 hidden sm:table">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-1 py-3 text-left text-xs font-medium uppercase tracking-wider min-w-[20px]">No</th>
                    <th class="px-1 py-3 text-left text-xs font-medium uppercase tracking-wider min-w-[100px]">Barcode</th>
                    <th class="px-1 py-3 text-left text-xs font-medium uppercase tracking-wider min-w-[80px]">Detail</th>
                    <th class="px-1 py-3 text-left text-xs font-medium uppercase tracking-wider bg-fuchsia-100 text-fuchsia-800 min-w-[100px]">Ekor Baru</th>
                    <th class="px-1 py-3 text-left text-xs font-medium uppercase tracking-wider bg-fuchsia-100 text-fuchsia-800 min-w-[100px]">Berat Baru</th>
                    <th class="px-1 py-3 text-left text-xs font-medium uppercase tracking-wider min-w-[200px]">Keterangan</th>
                    <th class="px-1 py-3 text-left text-xs font-medium uppercase tracking-wider min-w-[80px]">User Input</th>
                    <th class="px-1 py-3 text-left text-xs font-medium uppercase tracking-wider min-w-[80px]">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="(row, index) in adjustmentList" :key="row.id">
                    <tr :class="{'bg-yellow-50': row.isEdited}" class="hover:bg-gray-100 transition-colors" :data-row-id="row.id">
                        <td class="px-1 py-2 text-xs text-gray-900" x-text="index + 1"></td>
                        <td class="px-1 py-2 text-xs text-gray-900">
                            <div class="flex items-center space-x-2">
                                <input type="text" x-model="row.barcode" name="barcode"
                                    @keydown.enter.prevent="searchBarcode(row.id)" :disabled="isProcessing"
                                    class="w-full px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <button type="button" @click="searchBarcode(row.id)"
                                    :disabled="isProcessing || !row.barcode"
                                    class="p-1 rounded-full text-white bg-blue-500 hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:bg-gray-400 transition-colors">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </td>
                        <td class="px-1 py-2 text-[10px] md:text-xs text-gray-700 detail-cell">
                            <div x-show="row.item">
                                <p>
                                    <strong class="text-xs">Item:</strong><br>
                                    <span x-text="row.item" class="text-xs"></span>
                                </p>
                                <p x-show="row.subitem">
                                    <strong class="text-xs">Sub-item:</strong><br>
                                    <span x-text="row.subitem" class="text-xs"></span>
                                </p>

                                <div class="flex items-center justify-between mt-2">
                                    <p class="text-xs text-gray-500">Detail Lanjut:</p>
                                    <button @click="row.isDetailsCollapsed = !row.isDetailsCollapsed" type="button"
                                        class="text-gray-400 hover:text-gray-600 focus:outline-none transition-colors duration-150 p-1">
                                        <i class="fas"
                                            :class="{'fa-plus-circle': row.isDetailsCollapsed, 'fa-minus-circle': !row.isDetailsCollapsed}"></i>
                                    </button>
                                </div>

                                <div x-show="!row.isDetailsCollapsed" x-transition.duration.300ms>
                                    <p>
                                        <strong>Storage:</strong><br>
                                        <span x-text="row.storage"></span>
                                    </p>
                                    <p>
                                        <strong>Tanggal Masuk:</strong><br>
                                        <span x-text="row.created_at || '-'"></span>
                                    </p>
                                    <p>
                                        <strong>User Masuk:</strong><br>
                                        <span x-text="row.user_in || '-'"></span>
                                    </p>
                                    <p>
                                        <strong>Stok Awal:</strong><br>
                                        <span x-text="row.ekor_stock_current || '0'"></span> Ekor / <span
                                            x-text="(row.berat_stock_current) || '0.00'"></span> KG
                                    </p>
                                    <p>
                                        <strong>Stok Item Global:</strong><br>
                                        <span x-text="row.ekor_item_global_stock || '0'"></span> Ekor / <span
                                            x-text="(row.berat_item_global_stock) || '0.00'"></span> KG
                                    </p>
                                    <p>
                                        <span x-text="row.id_in || '-'" class=""></span>
                                    </p>
                                </div>
                            </div>
                            <div x-show="isProcessing && row.barcode && !row.item"
                                class="text-gray-500 animate-pulse">Memuat...</div>
                        </td>
                        <td class="px-1 py-2">
                            <input type="number" x-model.number="row.ekor_new" name="ekor_new" @input="updateRowStatus(row); saveToLocalStorage()"
                                class="w-full text-center px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </td>
                        <td class="px-1 py-2">
                            <input type="number" step="0.01" x-model.number="row.berat_new" name="berat_new" @input="updateRowStatus(row); saveToLocalStorage()"
                                class="w-full text-center px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </td>
                        <td class="px-1 py-2">
                            <input type="text" x-model="row.keterangan" name="keterangan"
                                @input="updateRowStatus(row); saveToLocalStorage()"
                                class="w-full px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </td>
                        <td class="px-1 py-2 text-xs text-gray-900" x-text="row.user_input || '-'"></td>
                        <td class="px-1 py-2 text-xs font-medium">
                            <button @click="removeRow(row.id)" class="text-red-600 hover:text-red-900">Hapus</button>
                        </td>
                    </tr>
                </template>
                <tr x-show="adjustmentList.length === 0">
                    <td colspan="8" class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 text-center">No adjustments found.</td>
                </tr>
            </tbody>
        </table>

        <div class="sm:hidden grid grid-cols-1 md:grid-cols-2 gap-4 p-4">
            <template x-for="(row, index) in adjustmentList" :key="row.id">
                <div class="bg-white rounded-lg shadow-md p-4 border" :class="{'border-yellow-300 bg-yellow-50': row.isEdited}" :data-row-id="row.id">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-semibold text-gray-700">No: <span x-text="index + 1"></span></span>
                        <button @click="removeRow(row.id)" class="text-red-600 hover:text-red-900 text-xs font-medium">Hapus</button>
                    </div>

                    <div class="mb-3">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Barcode</label>
                        <div class="flex items-center space-x-2">
                            <input type="text" x-model="row.barcode" name="barcode"
                                @keydown.enter.prevent="searchBarcode(row.id)" :disabled="isProcessing"
                                class="w-full px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs">
                            <button type="button" @click="searchBarcode(row.id)"
                                :disabled="isProcessing || !row.barcode"
                                class="p-1 rounded-full text-white bg-blue-500 hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:bg-gray-400 transition-colors">
                                <i class="fas fa-search text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <div class="border-t pt-3 mt-3">
                        <p x-show="row.item" class="mb-1"><strong>Item:</strong> <span x-text="row.item"></span></p>
                        <p x-show="row.subitem" class="mb-1"><strong>Sub-item:</strong> <span x-text="row.subitem"></span></p>
                        <p class="mb-1"><strong>User Input:</strong> <span x-text="row.user_input || '-'"></span></p>
                        <div class="flex items-center justify-between mb-2 mt-2">
                            <h4 class="text-xs font-semibold text-gray-700">Info Lainnya</h4>
                            <button @click="row.isDetailsCollapsed = !row.isDetailsCollapsed" type="button"
                                class="text-blue-500 hover:text-blue-700 focus:outline-none transition-colors duration-150 p-1">
                                <i class="fas"
                                    :class="{'fa-chevron-down': row.isDetailsCollapsed, 'fa-chevron-up': !row.isDetailsCollapsed}"></i>
                            </button>
                        </div>

                        <div x-show="!row.isDetailsCollapsed" x-transition.duration.300ms class="space-y-1 text-xs text-gray-700">
                            <p><strong>Storage:</strong> <span x-text="row.storage"></span></p>
                            <p><strong>Tanggal Masuk:</strong> <span x-text="row.created_at || '-'"></span></p>
                            <p><strong>User Masuk:</strong> <span x-text="row.user_in || '-'"></span></p>
                            <p><strong>Stok Awal:</strong> <span x-text="row.ekor_stock_current || '0'"></span> Ekor / <span x-text="(row.berat_stock_current) || '0.00'"></span> KG</p>
                            <p><strong>Stok Item Global:</strong> <span x-text="row.ekor_item_global_stock || '0'"></span> Ekor / <span x-text="(row.berat_item_global_stock) || '0.00'"></span> KG</p>
                            <p class="text-xs text-gray-500" x-text="row.id_in || '-'"></p>
                        </div>

                        <div x-show="isProcessing && row.barcode && !row.item"
                            class="text-gray-500 animate-pulse text-xs mt-2">Memuat...</div>

                        <div class="grid grid-cols-2 gap-3 mt-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Ekor Baru</label>
                                <input type="number" x-model.number="row.ekor_new" name="ekor_new" @input="updateRowStatus(row); saveToLocalStorage()"
                                    class="w-full px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Berat Baru (KG)</label>
                                <input type="number" step="0.01" x-model.number="row.berat_new" name="berat_new" @input="updateRowStatus(row); saveToLocalStorage()"
                                    class="w-full px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs">
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
            <div x-show="adjustmentList.length === 0" class="col-span-full text-center text-gray-500 p-4">
                No adjustments found.
            </div>
        </div>
    </div>



    <div class="flex justify-between mt-8 gap-x-10">
        <div class="flex space-x-2">
            <button @click="addRow" type="button"
                class="inline-flex items-center px-2 md:px-4 py-2 border border-transparent text-xs md:text-xs font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                <i class="fas fa-plus mr-2"></i> Tambah Baris
            </button>
            <button @click="openAddDataModal()" type="button" :disabled="!isFormValid"
                class="inline-flex items-center px-2 md:px-4 py-2 border border-transparent text-xs md:text-xs font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-plus-circle mr-2"></i> Tambah Data
            </button>
        </div>
        <button @click="openAdjustmentModal" :disabled="!isFormValid || !hasChanges || isProcessing" type="button"
            class="inline-flex items-center px-2 md:px-6 py-3 border border-transparent text-xs md:text-xs font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed">
            <i class="fas fa-save mr-2"></i> Simpan Semua Adjustment
        </button>
    </div>

    <!-- Modal Konfirmasi Adjustment -->
    <div x-show="modal.show" class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(0,0,0,0.5);">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="modal.show" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative bg-white rounded-lg shadow-xl w-full max-w-4xl p-6">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-xl font-semibold text-gray-900">Konfirmasi Adjustment</h3>
                    <button @click="modal.show = false" type="button"
                        class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="mt-4 max-h-[70vh] overflow-y-auto">
                    <p class="text-xs font-medium text-gray-700 mb-2">
                        Tanggal: <span x-text="adjustmentDate"></span> | Storage: <span x-text="selectedStorage"></span> | Tipe: <span x-text="adjustmentType"></span>
                        <span x-show="selectedUser"> | User: <span x-text="selectedUser"></span></span>
                    </p>

                    <div x-show="modal.data.barcodes.length > 0">
                        <h4 class="text-lg font-bold my-4">Adjustment Berdasarkan Barcode</h4>
                        <table class="min-w-full divide-y divide-gray-200 mb-6">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase w-10">No</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase">Barcode</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase">Item</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase">Stok Awal</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase">Stok Baru</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase">Adjustment</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="(item, index) in modal.data.barcodes" :key="item.id">
                                    <tr :class="{'bg-red-50': item.ekor_adjustment < 0 || item.berat_adjustment < 0, 'bg-green-50': item.ekor_adjustment > 0 || item.berat_adjustment > 0}">
                                        <td class="px-4 py-2 text-xs text-gray-900" x-text="index + 1"></td>
                                        <td class="px-4 py-2 text-xs text-gray-900" x-text="item.barcode || '-'"></td>
                                        <td class="px-4 py-2 text-xs text-gray-900" x-text="item.item + (item.subitem ? ' : ' + item.subitem : '')"></td>
                                        <td class="px-4 py-2 text-xs text-gray-900">
                                            <span x-text="item.ekor_stock_current"></span> Ekor<br>
                                            <span x-text="(item.berat_stock_current).toFixed(2)"></span> KG
                                        </td>
                                        <td class="px-4 py-2 text-xs text-gray-900 font-semibold">
                                            <span x-text="item.ekor_new"></span> Ekor<br>
                                            <span x-text="(item.berat_new).toFixed(2)"></span> KG
                                        </td>
                                        <td class="px-4 py-2 text-xs font-bold" :class="{'text-red-700': item.ekor_adjustment < 0 || item.berat_adjustment < 0, 'text-green-700': item.ekor_adjustment > 0 || item.berat_adjustment > 0}">
                                            <span x-text="item.ekor_adjustment > 0 ? '+' + item.ekor_adjustment : item.ekor_adjustment"></span> Ekor<br>
                                            <span x-text="item.berat_adjustment > 0 ? '+' + (item.berat_adjustment).toFixed(2) : (item.berat_adjustment).toFixed(2)"></span> KG
                                        </td>
                                        <td class="px-4 py-2 text-xs text-gray-900" x-text="item.keterangan || '-'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div x-show="modal.data.global.length > 0">
                        <h4 class="text-lg font-bold my-4">Adjustment Stok Global (Tidak Tercatat)</h4>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase w-10">No</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase">Barcode</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase">Item</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase">Stok Awal</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase">Stok Baru</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase">Adjustment</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="(item, index) in modal.data.global" :key="item.item + item.subitem">
                                    <tr :class="{'bg-red-50': item.ekor_adjustment < 0 || item.berat_adjustment < 0, 'bg-green-50': item.ekor_adjustment > 0 || item.berat_adjustment > 0}">
                                        <td class="px-4 py-2 text-xs text-gray-900" x-text="index + 1"></td>
                                        <td class="px-4 py-2 text-xs text-gray-900" x-text="item.barcode || '-'"></td>
                                        <td class="px-4 py-2 text-xs text-gray-900" x-text="item.item + (item.subitem ? ' : ' + item.subitem : '')"></td>
                                        <td class="px-4 py-2 text-xs text-gray-900">
                                            <span x-text="item.ekor_stock_current"></span> Ekor<br>
                                            <span x-text="(item.berat_stock_current).toFixed(2)"></span> KG
                                        </td>
                                        <td class="px-4 py-2 text-xs text-gray-900 font-semibold">
                                            <span x-text="item.ekor_new"></span> Ekor<br>
                                            <span x-text="(item.berat_new).toFixed(2)"></span> KG
                                        </td>
                                        <td class="px-4 py-2 text-xs font-bold" :class="{'text-red-700': item.ekor_adjustment < 0 || item.berat_adjustment < 0, 'text-green-700': item.ekor_adjustment > 0 || item.berat_adjustment > 0}">
                                            <span x-text="item.ekor_adjustment > 0 ? '+' + item.ekor_adjustment : item.ekor_adjustment"></span> Ekor<br>
                                            <span x-text="item.berat_adjustment > 0 ? '+' + (item.berat_adjustment).toFixed(2) : (item.berat_adjustment).toFixed(2)"></span> KG
                                        </td>
                                        <td class="px-4 py-2 text-xs text-gray-900" x-text="item.keterangan || '-'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button @click="modal.show = false" type="button"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-xs font-medium rounded-md shadow-sm text-gray-700 bg-gray-200 hover:bg-gray-300">
                        Batal
                    </button>
                    <button @click="saveAdjustments" type="button" :disabled="isSaving"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-text="isSaving ? 'Menyimpan...' : 'Simpan Adjustment'"></span>
                        <i x-show="isSaving" class="fas fa-spinner fa-spin ml-2 h-4 w-4 text-white"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Data Manual -->
    <div x-show="addDataModal.show" class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(0,0,0,0.5);">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="addDataModal.show" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative bg-white rounded-lg shadow-xl w-full max-w-lg p-6">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-xl font-semibold text-gray-900">Tambah Data Tanpa Barcode</h3>
                    <button @click="addDataModal.show = false" type="button"
                        class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mt-4 space-y-4">
                    <div>
                        <label for="new-barcode" class="block text-xs font-medium text-gray-700">Barcode Baru</label>
                        <input type="text" id="new-barcode" x-model="addDataModal.data.barcode"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" autocomplete="off">
                    </div>

                    <div class="relative"
                        x-data="{
                        itemDropdownOpen: false,
                        selectItem(item) {
                            addDataModal.data.item = item; // Update the actual data model
                            this.itemSearchQuery = item; // Set the input field's value to the selected item
                            this.itemDropdownOpen = false;
                            // addDataModal.data.subitem = ''; // Hapus ini jika ingin subitem tetap ada setelah ganti item, atau biarkan jika ini perilaku yang diinginkan
                        },
                        get filteredItems() {
                            const query = this.itemSearchQuery.toLowerCase();
                            return itemOptions.filter(item => item.toLowerCase().includes(query));
                        }
                    }"
                        x-init="
                        $watch('addDataModal.data.item', (value) => {
                            if (value !== this.itemSearchQuery) {
                                this.itemSearchQuery = value;
                            }
                        });
                    "
                        @click.away="itemDropdownOpen = false">
                        <label for="new-item-custom" class="block text-xs font-medium text-gray-700">Item</label>
                        <div class="mt-1 relative">
                            <input
                                id="new-item-custom"
                                type="text"
                                x-model="itemSearchQuery"
                                @focus="itemDropdownOpen = true"
                                @input="itemDropdownOpen = true"
                                @keydown.escape.stop.prevent="itemDropdownOpen = false"
                                @keydown.arrow-down.prevent="$event.target.nextElementSibling && $event.target.nextElementSibling.focus()"
                                :placeholder="!itemSearchQuery && !addDataModal.data.item ? 'Pilih atau cari Item...' : ''"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 cursor-pointer text-black"
                                :class="{'bg-gray-100': isFetchingItems}"
                                :disabled="isFetchingItems"
                                autocomplete="off">
                            <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400"
                                @click="itemSearchQuery = ''; selectItem('');">
                                <i class="fas fa-times" x-show="itemSearchQuery || addDataModal.data.item"></i>
                            </button>
                        </div>

                        <div x-show="itemDropdownOpen && filteredItems.length > 0"
                            class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-60 overflow-y-auto"
                            @keydown.tab.prevent="itemDropdownOpen = false">
                            <template x-for="item in filteredItems" :key="item">
                                <div @click="selectItem(item)"
                                    class="px-3 py-2 cursor-pointer hover:bg-blue-500 hover:text-white text-black"
                                    :class="{'bg-blue-500 text-white': addDataModal.data.item === item}"
                                    x-text="item"
                                    tabindex="0"
                                    @keydown.enter.prevent="selectItem(item)"
                                    @keydown.arrow-down.prevent="$el.nextElementSibling && $el.nextElementSibling.focus()"
                                    @keydown.arrow-up.prevent="$el.previousElementSibling && $el.previousElementSibling.focus()">
                                </div>
                            </template>
                        </div>
                        <div x-show="itemDropdownOpen && filteredItems.length === 0 && itemSearchQuery.length > 0" class="px-3 py-2 text-gray-500">Tidak ada hasil ditemukan.</div>
                        <div x-show="isFetchingItems" class="text-xs text-gray-500 animate-pulse mt-1">Memuat daftar item...</div>
                    </div>

                    <div class="relative"
                        x-data="{
                        subitemSearchQuery: addDataModal.data.subitem || '', // Initialize with current subitem or empty
                        subitemDropdownOpen: false,
                        selectSubitem(subitem) {
                            addDataModal.data.subitem = subitem; // Update the actual data model
                            this.subitemSearchQuery = subitem; // Set the input field's value to the selected subitem
                            this.subitemDropdownOpen = false;
                        },
                        get filteredSubitems() {
                            const query = this.subitemSearchQuery.toLowerCase();
                            // Pastikan subitemOptions terdefinisi di scope parent atau global
                            return subitemOptions.filter(subitem => subitem.toLowerCase().includes(query));
                        }
                    }"
                        x-init="
                        $watch('addDataModal.data.subitem', (value) => {
                            if (value !== this.subitemSearchQuery) {
                                this.subitemSearchQuery = value;
                            }
                        });
                        // Jika item kosong, pastikan subitem juga direset
                        $watch('addDataModal.data.item', (newItem) => {
                            if (!newItem) {
                                this.subitemSearchQuery = '';
                                addDataModal.data.subitem = '';
                                this.subitemDropdownOpen = false;
                            }
                        });
                    "
                        @click.away="subitemDropdownOpen = false">
                        <label for="new-subitem-custom" class="block text-xs font-medium text-gray-700">Sub-item (Opsional)</label>
                        <div class="mt-1 relative">
                            <input
                                id="new-subitem-custom"
                                type="text"
                                x-model="subitemSearchQuery"
                                @focus="subitemDropdownOpen = true"
                                @input="subitemDropdownOpen = true"
                                @keydown.escape.stop.prevent="subitemDropdownOpen = false"
                                @keydown.arrow-down.prevent="$event.target.nextElementSibling && $event.target.nextElementSibling.focus()"
                                :placeholder="!subitemSearchQuery && !addDataModal.data.subitem ? 'Pilih atau cari Sub-item...' : ''"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 cursor-pointer text-black"
                                :disabled="!addDataModal.data.item"
                                autocomplete="off">
                            <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400"
                                @click="subitemSearchQuery = ''; selectSubitem('');">
                                <i class="fas fa-times" x-show="subitemSearchQuery || addDataModal.data.subitem"></i>
                            </button>
                        </div>

                        <div x-show="subitemDropdownOpen && filteredSubitems.length > 0"
                            class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-60 overflow-y-auto"
                            @keydown.tab.prevent="subitemDropdownOpen = false">
                            <template x-for="subitem in filteredSubitems" :key="subitem">
                                <div @click="selectSubitem(subitem)"
                                    class="px-3 py-2 cursor-pointer hover:bg-blue-500 hover:text-white text-black"
                                    :class="{'bg-blue-500 text-white': addDataModal.data.subitem === subitem}"
                                    x-text="subitem"
                                    tabindex="0"
                                    @keydown.enter.prevent="selectSubitem(subitem)"
                                    @keydown.arrow-down.prevent="$el.nextElementSibling && $el.nextElementSibling.focus()"
                                    @keydown.arrow-up.prevent="$el.previousElementSibling && $el.previousElementSibling.focus()">
                                </div>
                            </template>
                        </div>
                        <div x-show="subitemDropdownOpen && filteredSubitems.length === 0 && subitemSearchQuery.length > 0" class="px-3 py-2 text-gray-500">Tidak ada hasil ditemukan.</div>
                    </div>

                    <div>
                        <label for="new-ekor" class="block text-xs font-medium text-gray-700">Jumlah Ekor</label>
                        <input type="number" id="new-ekor" x-model.number="addDataModal.data.ekor_new"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="new-berat" class="block text-xs font-medium text-gray-700">Berat (KG)</label>
                        <input type="number" step="0.01" id="new-berat" x-model.number="addDataModal.data.berat_new"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="new-keterangan" class="block text-xs font-medium text-gray-700">Keterangan</label>
                        <input type="text" id="new-keterangan" x-model="addDataModal.data.keterangan"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button @click="addDataModal.show = false" type="button"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-xs font-medium rounded-md shadow-sm text-gray-700 bg-gray-200 hover:bg-gray-300">
                        Batal
                    </button>
                    <button @click="addNewRow" type="button"
                        :disabled="!addDataModal.data.barcode || !addDataModal.data.item || addDataModal.data.ekor_new === null || addDataModal.data.berat_new === null"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Tambah Data
                    </button>
                </div>
            </div>
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
            allAdjustments: [], // Akan menampung semua data adjustment dari backend (tanpa filter user)
            isProcessing: false,
            isSaving: false,
            isFetchingItems: false,
            itemOptions: [],
            subitemOptions: [],
            allStorages: ['Chiller 1', 'Chiller 2', 'Chiller 3'],
            apiEndpoint: './controller/adjustment/process_and_adjust.php',
            adjustTmpApiEndpoint: './controller/adjustment/adjust_tmp.php',
            csvDataUrl: '',
            userOptions: [],
            selectedUser: '', // Variabel yang akan menyimpan user yang dipilih di dropdown

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

            // Ambil nama user dari PHP session. Ini adalah user yang sedang login.
            currentUserFromSession: '<?php echo isset($_SESSION['user_nama']) ? addslashes($_SESSION['user_nama']) : ''; ?>',

            init() {
                this.adjustmentDate = new Date().toISOString().substring(0, 10);
                // Saat inisialisasi, user yang terpilih adalah user yang sedang login.
                this.selectedUser = this.currentUserFromSession;
                this.fetchInitialData();

                // Watchers untuk memicu pengambilan data atau filter ulang saat filter utama berubah
                this.$watch('selectedStorage', () => this.fetchAdjustmentsAndApplyFilter());
                this.$watch('adjustmentDate', () => this.fetchAdjustmentsAndApplyFilter());
                this.$watch('adjustmentType', () => this.fetchAdjustmentsAndApplyFilter());
                // Ini adalah watcher kunci untuk filter user: saat selectedUser berubah, terapkan filter
                this.$watch('selectedUser', () => this.applyUserFilter());
            },

            async fetchInitialData() {
                this.isFetchingItems = true;
                try {
                    // Ambil daftar user dari backend
                    const userResponse = await fetch(`${this.adjustTmpApiEndpoint}?action=get_user_list`);
                    const userResult = await userResponse.json();

                    if (userResult.success) {
                        let fetchedUsers = userResult.data;
                        // Pastikan user yang sedang login ada di daftar opsi dan terpilih secara default
                        if (this.currentUserFromSession && !fetchedUsers.includes(this.currentUserFromSession)) {
                            fetchedUsers.unshift(this.currentUserFromSession); // Tambahkan di awal jika belum ada
                        }
                        this.userOptions = fetchedUsers;

                        // Set selectedUser ke user yang sedang login jika ada dalam daftar
                        if (this.currentUserFromSession && this.userOptions.includes(this.currentUserFromSession)) {
                            this.selectedUser = this.currentUserFromSession;
                        } else if (this.currentUserFromSession && this.selectedUser === '') {
                            // Fallback jika currentUserFromSession ada tapi selectedUser masih kosong
                            this.selectedUser = this.currentUserFromSession;
                        }
                    } else {
                        console.error('Failed to load user list:', userResult.message);
                        // Jika gagal memuat daftar user, setidaknya masukkan user yang sedang login
                        if (this.currentUserFromSession && !this.userOptions.includes(this.currentUserFromSession)) {
                            this.userOptions.unshift(this.currentUserFromSession);
                            this.selectedUser = this.currentUserFromSession;
                        } else {
                            this.userOptions = []; // Jika tidak ada user lain, kosongkan opsi
                            this.selectedUser = ''; // Dan set selectedUser menjadi kosong
                        }
                    }

                    // Ambil daftar item dan subitem (tidak berubah)
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

                    // Setelah semua data awal diambil, baru ambil adjustment dan terapkan filter
                    await this.fetchAdjustmentsAndApplyFilter();

                } catch (error) {
                    console.error('Error loading initial data:', error);
                } finally {
                    this.isFetchingItems = false;
                }
            },

            async fetchAdjustmentsAndApplyFilter() {
                // Cek jika filter utama (tanggal, gudang, tipe) belum lengkap, jangan fetch dari DB
                if (!this.adjustmentDate || !this.selectedStorage || !this.adjustmentType) {
                    console.log("Filter utama (tanggal, gudang, tipe) belum lengkap, tidak mengambil dari DB.");
                    this.allAdjustments = []; // Kosongkan semua data
                    this.adjustmentList = []; // Kosongkan data yang ditampilkan
                    this.addRow(); // Tambahkan baris kosong untuk input baru
                    return;
                }

                this.isProcessing = true;
                try {
                    // Fetch SEMUA adjustment dari backend berdasarkan tanggal, gudang, dan tipe
                    // Tanpa memfilter berdasarkan user di sisi backend
                    const response = await fetch(`${this.adjustTmpApiEndpoint}?action=get_adjustments&date=${this.adjustmentDate}&storage=${this.selectedStorage}&type=${this.adjustmentType}`);
                    const result = await response.json();

                    if (result.success) {
                        this.allAdjustments = result.data.map(row => ({
                            ...row,
                            id: row.row_unique_id,
                            ekor_stock_current: parseFloat(row.ekor_stock_current || '0'),
                            berat_stock_current: parseFloat(row.berat_stock_current || '0'),
                            ekor_new: parseFloat(row.ekor_new || '0'),
                            berat_new: parseFloat(row.berat_new || '0'),
                            isEdited: row.is_edited == 1,
                            isDetailsCollapsed: row.is_details_collapsed == 1,
                            user_input: row.user_input || 'N/A' // Pastikan user_input selalu ada
                        }));
                        // Setelah mendapatkan semua data, terapkan filter user yang sedang aktif
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
                    // Jika setelah filter tidak ada data, tambahkan baris kosong
                    if (this.adjustmentList.length === 0) {
                        this.addRow();
                    }
                }
            },

            applyUserFilter() {
                if (this.selectedUser === '') {
                    // Jika 'Semua User' dipilih, tampilkan semua adjustment
                    this.adjustmentList = this.allAdjustments;
                } else {
                    // Filter adjustment berdasarkan user yang dipilih
                    this.adjustmentList = this.allAdjustments.filter(row => row.user_input === this.selectedUser);
                }
                // Penting: Pastikan selalu ada baris kosong untuk input baru jika daftar kosong
                // Ini akan membedakan antara user yang tidak memiliki data vs. filter "Semua User" yang kosong
                if (this.adjustmentList.length === 0) {
                    this.addRow();
                }
            },

            // saveToLocalStorage() akan diganti dengan saveToDatabase()
            async saveToDatabase(row) {
                // Pastikan data penting ada sebelum menyimpan
                if (!this.adjustmentDate || !this.selectedStorage || !this.adjustmentType || !row.item) {
                    console.error("Attempted to save incomplete row:", row);
                    return;
                }

                this.isSaving = true;
                try {
                    const dataToSave = {
                        action: 'save_adjustment',
                        user_input: this.currentUser, // Ambil dari variabel Alpine `currentUser`
                        adjustment_date: this.adjustmentDate,
                        storage: this.selectedStorage,
                        adjustment_type: this.adjustmentType,
                        row_unique_id: row.id, // ID unik dari Alpine.js
                        id_in: row.id_in,
                        barcode: row.barcode,
                        item: row.item,
                        subitem: row.subitem,
                        ekor_stock_current: row.ekor_stock_current,
                        berat_stock_current: row.berat_stock_current,
                        ekor_new: row.ekor_new,
                        berat_new: row.berat_new,
                        keterangan: row.keterangan,
                        is_edited: row.isEdited ? 1 : 0, // Konversi boolean ke int
                        is_details_collapsed: row.isDetailsCollapsed ? 1 : 0 // Konversi boolean ke int
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
                        // Jika ini adalah data baru yang disimpan, dan ID di DB terbentuk,
                        // Anda mungkin perlu memperbarui `id_in` atau `row_unique_id` jika backend mengembalikan ID DB.
                        // Saat ini, kita pakai row.id (Date.now()) sebagai row_unique_id.
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
                const lastRowIndex = this.adjustmentList.length - 1;
                let shouldAddNewRow = false;

                if (this.adjustmentList.length === 0) {
                    shouldAddNewRow = true;
                } else {
                    const lastRow = this.adjustmentList[lastRowIndex];

                    const hasProductInfo = (lastRow.item || '').trim() !== '' || (lastRow.barcode || '').trim() !== '';
                    const hasNewQuantity = (parseFloat(lastRow.ekor_new) || 0) !== 0 || (parseFloat(lastRow.berat_new) || 0) !== 0;

                    shouldAddNewRow = hasProductInfo && hasNewQuantity;
                }

                if (shouldAddNewRow) {
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
                        // Tetapkan user_input untuk baris baru ke user yang sedang dipilih di filter
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
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Perhatian!',
                        text: 'Mohon isi setidaknya Barcode atau Item, serta Ekor Baru atau Berat Baru (dengan nilai bukan nol) pada baris yang sedang Anda kerjakan sebelum menambah baris baru.',
                        confirmButtonText: 'Oke'
                    });

                    if (lastRowIndex >= 0) {
                        const lastRow = this.adjustmentList[lastRowIndex];
                        const lastRowElement = this.$el.querySelector(`[data-row-id="${lastRow.id}"]`);

                        if (lastRowElement) {
                            const barcodeInput = lastRowElement.querySelector(`input[name="barcode"]`);
                            const itemInput = lastRowElement.querySelector(`input[name="item"]`);
                            const ekorNewInput = lastRowElement.querySelector(`input[name="ekor_new"]`);
                            const beratNewInput = lastRowElement.querySelector(`input[name="berat_new"]`);

                            if ((lastRow.barcode || '').trim() === '' && (lastRow.item || '').trim() === '') {
                                if (barcodeInput) barcodeInput.focus();
                                else if (itemInput) itemInput.focus();
                            } else if ((parseFloat(lastRow.ekor_new) || 0) === 0 && (parseFloat(lastRow.berat_new) || 0) === 0) {
                                if (ekorNewInput) ekorNewInput.focus();
                                else if (beratNewInput) beratNewInput.focus();
                            } else if (barcodeInput) {
                                barcodeInput.focus();
                            }
                        }
                    }
                }
            },

            async removeRow(rowId) {
                const index = this.adjustmentList.findIndex(row => row.id === rowId);
                if (index > -1) {
                    const rowToDelete = this.adjustmentList[index];

                    // Check if the row has been saved to the database (has id_in or barcode set from a successful search/save)
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
                                    row_unique_id: rowToDelete.id, // Use frontend unique ID
                                    adjustment_date: this.adjustmentDate,
                                    storage: this.selectedStorage,
                                    adjustment_type: this.adjustmentType,
                                    barcode: rowToDelete.barcode, // Also send barcode for server-side verification if needed
                                    id_in: rowToDelete.id_in // Send id_in if available for server-side deletion
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
                        // If the row hasn't been saved to the database, just remove it from the UI
                        this.adjustmentList.splice(index, 1);
                    }
                }

                // Ensure there's always at least one row if the list becomes empty
                if (this.adjustmentList.length === 0) {
                    this.addRow();
                }
            },

            async searchBarcode(rowId) {
                const row = this.adjustmentList.find(r => r.id === rowId);
                const barcode = (row.barcode || '').trim(); // Ensure barcode is a string and trimmed

                if (!this.isFormValid) {
                    Swal.fire('Peringatan', 'Silakan isi Tanggal, Storage, dan Tipe Adjustment terlebih dahulu.', 'warning');
                    return;
                }

                if (!barcode) {
                    // If barcode is empty, clear the row's data if it was previously populated by a search
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
                        // Optionally, save this empty state to the database if it implies a deletion or an unsaved entry
                        await this.saveToDatabase(row);
                    }
                    return; // Do not proceed with search if barcode is empty
                }

                // Check for existing barcode in other rows (excluding the current row being edited)
                const existingRow = this.adjustmentList.find(r => r.barcode === barcode && r.id !== rowId);
                if (existingRow) {
                    Swal.fire('Informasi', 'Barcode sudah ada di daftar. Silakan edit langsung pada baris yang sesuai.', 'info');
                    row.barcode = ''; // Clear the current row's barcode to prevent duplicate entry
                    this.isProcessing = false;
                    return;
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
                            storage: this.selectedStorage
                        })
                    });
                    const result = await response.json();

                    if (result.success) {
                        const data = result.data;
                        row.id_in = data.id_in;
                        row.item = data.item;
                        row.subitem = data.subitem;
                        row.storage = data.storage;
                        // row.created_at = data.created_at; // Keep if needed for display
                        // row.user_in = data.user_in;     // Keep if needed for display
                        row.ekor_stock_current = parseFloat(data.ekor_stock_current) || 0;
                        row.berat_stock_current = parseFloat(data.berat_stock_current) || 0;

                        row.ekor_item_global_stock = parseFloat(data.stok_ekor_global) || 0;
                        row.berat_item_global_stock = parseFloat(data.stok_berat_global) || 0;

                        // Pre-fill new values with current stock for convenience
                        row.ekor_new = row.ekor_stock_current;
                        row.berat_new = row.berat_stock_current;
                        row.keterangan = '';
                        row.isEdited = false; // Initially not edited after search fill

                        await this.saveToDatabase(row); // Save the newly found data to the temporary database
                        this.addRow(); // Add a new empty row for the next entry
                    } else {
                        Swal.fire('Tidak Ditemukan', `Barcode '${barcode}' ${result.message}`, 'error');
                        // If barcode not found, clear the barcode input and potentially other related fields for the current row
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
                        // No need to remove the row here, just clear its content. The user can then re-enter or remove it manually.
                        // If the intent is to remove the row entirely when barcode is not found, keep `this.removeRow(row.id);`
                        // But usually, clearing is better UX, letting the user correct the barcode.
                    }
                } catch (error) {
                    Swal.fire('Error', 'Terjadi kesalahan saat mencari barcode: ' + error.message, 'error');
                    console.error('Fetch error:', error);
                    // On error, also clear the row data
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
                } finally {
                    this.isProcessing = false;
                }
            },

            async updateRowStatus(row) {
                // Convert to numbers for comparison, default to 0 if null/empty string
                const ekorNew = parseFloat(row.ekor_new) || 0;
                const ekorCurrent = parseFloat(row.ekor_stock_current) || 0;
                const beratNew = parseFloat(row.berat_new) || 0;
                const beratCurrent = parseFloat(row.berat_stock_current) || 0;

                row.isEdited = (ekorNew !== ekorCurrent) ||
                    (beratNew !== beratCurrent) ||
                    (row.keterangan && row.keterangan.trim() !== '');

                // Save to database only if there's an actual change in edited status or initial data.
                // This avoids unnecessary API calls if a user just types and deletes the same value.
                if (row.isEdited || row.id_in === null) { // Also save if it's a new unsaved row (id_in is null)
                    await this.saveToDatabase(row);
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
                            user_input: this.currentUser
                        })
                    });
                    const result = await response.json();

                    if (result.success) {
                        this.adjustmentList = [];
                        this.addRow(); // Add a fresh row after reset
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

            openAddDataModal() {
                if (!this.isFormValid) {
                    Swal.fire('Peringatan', 'Silakan isi Tanggal, Storage, dan Tipe Adjustment terlebih dahulu.', 'warning');
                    return;
                }

                // 1. **Reset data utama modal secara MENYELURUH.**
                // Ensure ALL properties that might store previous transaction data are reset to their default values.
                this.addDataModal.data = {
                    barcode: '',
                    item: '',
                    subitem: '',
                    id_in: null, // Reset to NULL as it's for new entries
                    ekor_stock_current: 0, // Should be 0 for new manual entries
                    berat_stock_current: 0, // Should be 0 for new manual entries
                    ekor_new: null, // Set to null for empty numeric input
                    berat_new: null, // Set to null for empty numeric input
                    keterangan: 'Adjustment manual (dari modal)' // Default description for manual additions
                };

                // 2. Display the modal
                this.addDataModal.show = true;

                // 3. Use $nextTick to reset item/subitem search inputs and close dropdowns
                this.$nextTick(() => {
                    // Assuming itemSearchQuery, itemDropdownOpen, subitemSearchQuery, subitemDropdownOpen
                    // are properties within an Alpine.js component scope accessible via __alpine.bound
                    const itemInputEl = this.$el.querySelector('#new-item-custom');
                    const subitemInputEl = this.$el.querySelector('#new-subitem-custom');

                    if (itemInputEl && itemInputEl.__alpine && itemInputEl.__alpine.bound) {
                        itemInputEl.__alpine.bound.itemSearchQuery = '';
                        itemInputEl.__alpine.bound.itemDropdownOpen = false;
                    }

                    if (subitemInputEl && subitemInputEl.__alpine && subitemInputEl.__alpine.bound) {
                        subitemInputEl.__alpine.bound.subitemSearchQuery = '';
                        subitemInputEl.__alpine.bound.subitemDropdownOpen = false;
                    }

                    // Optional: Focus on the barcode input when the modal opens
                    const newBarcodeInput = this.$el.querySelector('#new-barcode');
                    if (newBarcodeInput) {
                        newBarcodeInput.focus();
                    }
                });
            },

            async addNewRow() {
                // More robust validation for ekor_new and berat_new
                const ekorNewValue = parseFloat(this.addDataModal.data.ekor_new);
                const beratNewValue = parseFloat(this.addDataModal.data.berat_new);

                if (
                    !this.addDataModal.data.barcode.trim() || // Barcode must be non-empty after trimming
                    !this.addDataModal.data.item.trim() || // Item must be non-empty after trimming
                    (isNaN(ekorNewValue) && isNaN(beratNewValue)) || // Both are not numbers
                    (ekorNewValue === 0 && beratNewValue === 0) // Both are zero
                ) {
                    Swal.fire('Peringatan', 'Silakan isi Barcode, Item, dan setidaknya salah satu dari Ekor Baru atau Berat Baru dengan nilai yang valid (bukan nol).', 'warning');
                    return;
                }

                // Check for existing barcode only within the current adjustmentList to prevent duplicates in the UI
                const existingRowInCurrentList = this.adjustmentList.find(r => r.barcode === this.addDataModal.data.barcode.trim());
                if (existingRowInCurrentList) {
                    Swal.fire('Informasi', 'Barcode sudah ada di daftar utama. Silakan edit langsung pada baris yang sesuai.', 'info');
                    this.addDataModal.show = false; // Close modal if barcode already exists
                    return;
                }

                const newRow = {
                    id: Date.now(), // Use a unique ID for the frontend row
                    id_in: null, // This should be null initially for a new row not yet in DB
                    barcode: this.addDataModal.data.barcode.trim(),
                    storage: this.selectedStorage,
                    item: this.addDataModal.data.item.trim(),
                    subitem: this.addDataModal.data.subitem ? this.addDataModal.data.subitem.trim() : '',
                    ekor_stock_current: 0, // For manually added new items, current stock is typically 0
                    berat_stock_current: 0, // For manually added new items, current stock is typically 0
                    ekor_new: ekorNewValue,
                    berat_new: beratNewValue,
                    keterangan: this.addDataModal.data.keterangan ? this.addDataModal.data.keterangan.trim() : 'Adjustment manual (dari modal)',
                    isEdited: true, // A manually added row is always considered 'edited' or new
                    isDetailsCollapsed: true,
                    user_input: this.selectedUser,
                };

                // Add to adjustmentList, which is the displayed list
                this.adjustmentList.push(newRow);

                // Save the new row to the database
                try {
                    await this.saveToDatabase(newRow);
                    Swal.fire('Berhasil!', 'Baris baru telah ditambahkan dan disimpan.', 'success');
                    // Clear modal data after successful addition and save
                    this.addDataModal.data = {
                        barcode: '',
                        item: '',
                        subitem: '',
                        id_in: null,
                        ekor_stock_current: 0,
                        berat_stock_current: 0,
                        ekor_new: null,
                        berat_new: null,
                        keterangan: 'Adjustment manual (dari modal)'
                    };
                    // Close the modal
                    this.addDataModal.show = false;
                    // Re-focus on the first input of a new row if addRow creates one
                    this.addRow();
                } catch (error) {
                    Swal.fire('Error', 'Gagal menyimpan baris baru ke database: ' + error.message, 'error');
                    // If saving fails, remove the row from the UI to prevent inconsistent state
                    const index = this.adjustmentList.findIndex(r => r.id === newRow.id);
                    if (index > -1) {
                        this.adjustmentList.splice(index, 1);
                    }
                    console.error('Error saving new row:', error);
                }
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
                this.modal.data.barcodes = []; // Hapus data sebelumnya
                this.modal.data.global = []; // Hapus data sebelumnya

                try {
                    // 1. Ambil data stok saat ini dari backend (tidak terpengaruh oleh filter user di UI)
                    console.log('Mengambil stok saat ini untuk gudang:', this.selectedStorage);
                    const stockResponse = await fetch(`${this.apiEndpoint}?action=get_current_stock&storage=${this.selectedStorage}`);
                    const stockResult = await stockResponse.json();

                    if (!stockResult.success) {
                        Swal.fire('Error', stockResult.message, 'error');
                        return;
                    }

                    console.log('Data stok dari backend (raw):', stockResult.data);

                    const backendAggregatedStockRemaining = new Map();

                    stockResult.data.forEach(stockItem => {
                        const key = `${stockItem.item}:${stockItem.subitem || ''}`;
                        const currentEkor = parseFloat(stockItem.total_ekor) || 0;
                        const currentBerat = parseFloat(stockItem.total_berat) || 0;

                        if (backendAggregatedStockRemaining.has(key)) {
                            const existing = backendAggregatedStockRemaining.get(key);
                            existing.ekor_stock_current += currentEkor;
                            existing.berat_stock_current += currentBerat;
                        } else {
                            backendAggregatedStockRemaining.set(key, {
                                item: stockItem.item,
                                subitem: stockItem.subitem || '',
                                ekor_stock_current: currentEkor,
                                berat_stock_current: currentBerat,
                                _isHandledCompletely: false // Flag untuk melacak item yang sudah di-adjust oleh user
                            });
                        }
                    });
                    console.log('Stok Agregat Backend Tersisa (awal):', backendAggregatedStockRemaining);

                    const barcodeAdjustments = [];
                    const globalAdjustments = [];

                    // Proses daftar adjustment yang sudah difilter oleh user (this.adjustmentList)
                    console.log('Memproses daftar adjustment input user (sudah difilter):', this.adjustmentList);
                    this.adjustmentList.filter(row => row.item || row.barcode).forEach(row => {
                        const userItemSubitemKey = `${row.item}:${row.subitem || ''}`;
                        const stockInBackend = backendAggregatedStockRemaining.get(userItemSubitemKey);

                        const initialEkorInRow = parseFloat(row.ekor_stock_current) || 0;
                        const initialBeratInRow = parseFloat(row.berat_stock_current) || 0;

                        const ekorNew = parseFloat(row.ekor_new) || 0;
                        const beratNew = parseFloat(row.berat_new) || 0;

                        const ekorAdjustment = ekorNew - initialEkorInRow;
                        const beratAdjustment = beratNew - initialBeratInRow;

                        // Jika ada perubahan atau ini adalah item baru yang ditambahkan (initialEkorInRow === 0)
                        if (ekorAdjustment !== 0 || beratAdjustment !== 0 || initialEkorInRow === 0) {
                            barcodeAdjustments.push({
                                id: row.id,
                                id_in: row.id_in || null,
                                barcode: row.barcode || '-',
                                item: row.item,
                                subitem: row.subitem || '',
                                storage: this.selectedStorage,
                                ekor_stock_current: initialEkorInRow,
                                berat_stock_current: initialBeratInRow,
                                ekor_new: ekorNew,
                                berat_new: beratNew,
                                ekor_adjustment: ekorAdjustment,
                                berat_adjustment: beratAdjustment,
                                keterangan: row.keterangan || 'Adjustment via barcode',
                                source: 'barcode'
                            });
                            console.log('Ditambahkan ke barcodeAdjustments:', barcodeAdjustments[barcodeAdjustments.length - 1]);
                        } else {
                            console.log('Melewatkan adjustment barcode (tidak ada perubahan):', row);
                        }

                        if (stockInBackend) {
                            // Perbarui stok sisa di backendAggregatedStockRemaining
                            stockInBackend.ekor_stock_current -= (initialEkorInRow - ekorNew);
                            stockInBackend.berat_stock_current -= (initialBeratInRow - beratNew);

                            if (stockInBackend.ekor_stock_current < 0) {
                                stockInBackend.ekor_stock_current = 0;
                            }
                            if (stockInBackend.berat_stock_current < 0) {
                                stockInBackend.berat_stock_current = 0;
                            }
                            // Tandai item ini sudah ditangani oleh input user
                            stockInBackend._isHandledCompletely = true;
                            console.log(`Diperbarui backendAggregatedStockRemaining untuk ${userItemSubitemKey}: Ekor=${stockInBackend.ekor_stock_current}, Berat=${stockInBackend.berat_stock_current}`);

                        } else {
                            // Jika item di adjustmentList tidak ada di stok backend (benar-benar baru ditambahkan user)
                            // Tandai sebagai sudah ditangani agar tidak masuk ke global adjustments sebagai "hilang"
                            backendAggregatedStockRemaining.set(userItemSubitemKey, {
                                item: row.item,
                                subitem: row.subitem || '',
                                ekor_stock_current: ekorNew, // Stok awalnya 0, jadi stok baru adalah ekorNew
                                berat_stock_current: beratNew,
                                _isHandledCompletely: true
                            });
                            console.log(`Menambahkan item baru ke backendAggregatedStockRemaining dari input user: ${userItemSubitemKey}, Ekor=${ekorNew}, Berat=${beratNew}`);
                        }
                    });
                    console.log('Array barcodeAdjustments akhir:', barcodeAdjustments);
                    console.log('Stok Agregat Backend Tersisa (setelah pemrosesan barcode):', backendAggregatedStockRemaining);

                    console.log('Memproses item yang tersisa di backendAggregatedStockRemaining untuk adjustment global...');
                    backendAggregatedStockRemaining.forEach(stockItem => {
                        // Hanya proses item yang belum ditangani secara eksplisit oleh input user
                        if (!stockItem._isHandledCompletely) {
                            const globalInitialEkor = stockItem.ekor_stock_current;
                            const globalInitialBerat = stockItem.berat_stock_current;

                            // Untuk adjustment global (stok yang tidak tercatat), kuantitas baru adalah 0
                            const ekorNewGlobal = 0;
                            const beratNewGlobal = 0;

                            const ekorAdjustment = ekorNewGlobal - globalInitialEkor;
                            const beratAdjustment = beratNewGlobal - globalInitialBerat;

                            if (ekorAdjustment !== 0 || beratAdjustment !== 0) {
                                globalAdjustments.push({
                                    item: stockItem.item,
                                    subitem: stockItem.subitem || '',
                                    barcode: '-', // Indikasi ini adalah adjustment global
                                    ekor_stock_current: globalInitialEkor,
                                    berat_stock_current: globalInitialBerat,
                                    ekor_new: ekorNewGlobal,
                                    berat_new: beratNewGlobal,
                                    ekor_adjustment: ekorAdjustment,
                                    berat_adjustment: beratAdjustment,
                                    keterangan: 'Stok tidak tercatat / hilang (sisa dari total Chiller)',
                                    source: 'global'
                                });
                                console.log('Ditambahkan ke globalAdjustments (sisa tidak terhitung):', globalAdjustments[globalAdjustments.length - 1]);
                            } else {
                                console.log('Melewatkan adjustment global (stok sisa saat ini 0 atau tidak ada perubahan):', stockItem);
                            }
                        }
                    });

                    // Tambahkan item baru dari adjustmentList yang tidak memiliki barcode
                    // dan belum tercatat di backendAggregatedStockRemaining (karena user input tanpa barcode)
                    this.adjustmentList.filter(row => !row.barcode && (parseFloat(row.ekor_new) > 0 || parseFloat(row.berat_new) > 0))
                        .forEach(row => {
                            const userItemSubitemKey = `${row.item}:${row.subitem || ''}`;
                            // Cek apakah kombinasi item/subitem ini sudah ditangani sebelumnya
                            // baik melalui barcode adjustment atau sebagai item baru global_new
                            const alreadyHandledInModalData =
                                barcodeAdjustments.some(adj => adj.item === row.item && adj.subitem === (row.subitem || '') && adj.barcode === '-') ||
                                globalAdjustments.some(adj => adj.item === row.item && adj.subitem === (row.subitem || '') && adj.source === 'global_new');

                            // Juga cek apakah item ini memang tidak ada di stok backend awalnya
                            const notInBackendStock = !backendAggregatedStockRemaining.has(userItemSubitemKey) ||
                                (backendAggregatedStockRemaining.get(userItemSubitemKey).ekor_stock_current === 0 &&
                                    backendAggregatedStockRemaining.get(userItemSubitemKey).berat_stock_current === 0 &&
                                    !backendAggregatedStockRemaining.get(userItemSubitemKey)._isHandledCompletely);

                            if (!alreadyHandledInModalData && notInBackendStock) {
                                const ekorNew = parseFloat(row.ekor_new) || 0;
                                const beratNew = parseFloat(row.berat_new) || 0;

                                globalAdjustments.push({
                                    id: row.id,
                                    item: row.item,
                                    subitem: row.subitem || '',
                                    barcode: '-',
                                    ekor_stock_current: 0,
                                    berat_stock_current: 0,
                                    ekor_new: ekorNew,
                                    berat_new: beratNew,
                                    ekor_adjustment: ekorNew,
                                    berat_adjustment: beratNew,
                                    keterangan: row.keterangan || 'Penambahan stok baru (tidak tercatat sebelumnya)',
                                    source: 'global_new'
                                });
                                console.log('Ditambahkan ke globalAdjustments (item baru tanpa barcode input user):', globalAdjustments[globalAdjustments.length - 1]);
                            }
                        });

                    console.log('Array globalAdjustments akhir:', globalAdjustments);

                    if (barcodeAdjustments.length === 0 && globalAdjustments.length === 0) {
                        Swal.fire('Informasi', 'Tidak ada data yang perlu disesuaikan.', 'info');
                        return;
                    }

                    this.modal.data.barcodes = barcodeAdjustments;
                    this.modal.data.global = globalAdjustments;
                    this.modal.show = true;

                    console.log('Data Modal (barcodes):', this.modal.data.barcodes);
                    console.log('Data Modal (global):', this.modal.data.global);

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
                    // Send barcode adjustments
                    const barcodePayload = {
                        action: 'save_barcode_adjustments', // Specific action for barcode adjustments
                        adjustment_date: this.adjustmentDate,
                        adjustment_type: this.adjustmentType,
                        storage: this.selectedStorage,
                        details: this.modal.data.barcodes.map(item => ({
                            id_in: item.id_in || null,
                            barcode: item.barcode || '',
                            item: item.item,
                            subitem: item.subitem || '',
                            ekor_stock_current: item.ekor_stock_current,
                            berat_stock_current: item.berat_stock_current,
                            ekor_new: item.ekor_new,
                            berat_new: item.berat_new,
                            ekor_adjustment: item.ekor_adjustment,
                            berat_adjustment: item.berat_adjustment,
                            keterangan: item.keterangan
                        }))
                    };

                    // --- TAMBAHAN console.log UNTUK BARCODE PAYLOAD ---
                    console.log('Sending barcodePayload:', barcodePayload);
                    // --- AKHIR TAMBAHAN ---


                    // Send global adjustments
                    const globalPayload = {
                        action: 'save_global_adjustments', // Specific action for global adjustments
                        adjustment_date: this.adjustmentDate,
                        adjustment_type: this.adjustmentType,
                        storage: this.selectedStorage,
                        details: this.modal.data.global.map(item => ({
                            item: item.item,
                            subitem: item.subitem || '',
                            ekor_stock_current: item.ekor_stock_current,
                            berat_stock_current: item.berat_stock_current,
                            ekor_new: item.ekor_new,
                            berat_new: item.berat_new,
                            ekor_adjustment: item.ekor_adjustment,
                            berat_adjustment: item.berat_adjustment,
                            keterangan: item.keterangan
                        }))
                    };

                    // --- TAMBAHAN console.log UNTUK GLOBAL PAYLOAD ---
                    console.log('Sending globalPayload:', globalPayload);
                    // --- AKHIR TAMBAHAN ---

                    let barcodeSaveResult = {
                        success: true,
                        message: ''
                    };
                    if (this.modal.data.barcodes.length > 0) {
                        const barcodeResponse = await fetch(this.apiEndpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(barcodePayload),
                        });
                        barcodeSaveResult = await barcodeResponse.json();
                    }

                    let globalSaveResult = {
                        success: true,
                        message: ''
                    };
                    if (this.modal.data.global.length > 0) {
                        const globalResponse = await fetch(this.apiEndpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(globalPayload),
                        });
                        globalSaveResult = await globalResponse.json();
                    }

                    if (barcodeSaveResult.success && globalSaveResult.success) {
                        Swal.fire('Berhasil', 'Adjustment stok berhasil disimpan.', 'success');
                        this.modal.show = false;
                        this._performReset(); // Reset form after successful save
                    } else {
                        // Combine messages if one fails or both have messages
                        let errorMessage = '';
                        if (!barcodeSaveResult.success) {
                            errorMessage += `Adjustment Barcode Gagal: ${barcodeSaveResult.message}. `;
                        }
                        if (!globalSaveResult.success) {
                            errorMessage += `Adjustment Global Gagal: ${globalSaveResult.message}.`;
                        }
                        Swal.fire('Gagal', errorMessage || 'Terjadi kesalahan saat menyimpan Adjustment.', 'error');
                    }

                } catch (error) {
                    console.error('Error saat menyimpan adjustment:', error);
                    Swal.fire('Error', 'Gagal menyimpan data adjustment: ' + error.message, 'error');
                } finally {
                    this.isSaving = false;
                }
            }
        }));
    });
</script>