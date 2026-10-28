<?php

$role = $_SESSION['user_role'] ?? 'Guest'; // Pastikan 'user_role' disetel saat login
?>
<div x-show="isModalOpen" x-cloak x-init="$watch('isModalOpen', val => { if (!val) { /* Optional: clean up when modal closes */ } })"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="relative bg-white w-11/12 max-w-xl rounded-lg shadow-lg p-4 sm:p-6 transform transition-all duration-300 overflow-y-auto max-h-[90vh]"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95">

        <button @click="closeModal()" aria-label="Tutup modal"
            class="absolute top-2 right-2 sm:top-3 sm:right-3 text-gray-500 hover:text-gray-800 text-lg sm:text-xl font-bold p-1 leading-none">
            &times;
        </button>

        <h3 class="text-sm sm:text-lg font-semibold text-gray-800 mb-3 sm:mb-4" x-text="isEditing ? 'Edit Transaksi' : 'Detail Transaksi'"></h3>

        <div x-show="selectedTransaction" class="text-gray-700 space-y-2 sm:space-y-3">
            <div x-show="!isEditing" class="space-y-1 sm:space-y-2 text-xs sm:text-sm">
                <p><strong>ID Transaksi:</strong> <span x-text="selectedTransaction.id"></span></p>
                <p><strong>Barcode:</strong> <span x-text="selectedTransaction.barcode || '-'"></span></p>
                <p><strong>Tanggal:</strong> <span x-text="selectedTransaction.date"></span></p>
                <p><strong>Item:</strong> <span x-text="selectedTransaction.item"></span></p>
                <p><strong>Ekor/Pack:</strong> <span x-text="selectedTransaction.ekor_pack"></span></p>
                <p><strong>Berat (Kg):</strong> <span x-text="selectedTransaction.quantity_kg"></span></p>
                <p><strong>Dari Lokasi:</strong> <span x-text="selectedTransaction.from_location"></span></p>
                <p><strong>Tujuan Lokasi:</strong> <span x-text="selectedTransaction.to_location"></span></p>
                <p><strong>Storage:</strong> <span x-text="selectedTransaction.storage"></span></p>
                <p><strong>Type Transaksi:</strong> <span x-text="formatTransactionType(selectedTransaction.transaction_type)"></span></p>
                <p><strong>Keterangan:</strong> <span x-text="selectedTransaction.keterangan || '-'"></span></p>
                <p><strong>User:</strong> <span x-text="selectedTransaction.user"></span></p>
                <p><strong>Dibuat Pada:</strong> <span x-text="formatDateTime(selectedTransaction.created_at)"></span></p>
                <p x-show="selectedTransaction.updated_at"><strong>Diperbarui Pada:</strong> <span x-text="formatDateTime(selectedTransaction.updated_at)"></span></p>
            </div>

            <div x-show="isEditing" class="grid grid-cols-2 gap-x-3 gap-y-2">
                <div>
                    <label class="block text-xs font-medium text-gray-700">ID Transaksi:</label>
                    <input type="text" x-model="editableTransaction.id" disabled
                        class="mt-0.5 block w-full p-1.5 border border-gray-300 bg-gray-100 rounded-md shadow-sm text-xs sm:text-sm">
                </div>


                <?php if ($role === 'superadmin'): ?>
                    <div>
                        <label for="editDate" class="block text-xs font-medium text-gray-700">Tanggal:</label>
                        <input type="date" x-model="editableTransaction.date" id="editDate"
                            class="mt-0.5 block w-full p-1.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs sm:text-sm">
                    </div>
                <?php else: ?>
                    <div>
                        <label for="editDate" class="block text-xs font-medium text-gray-700">Tanggal:</label>
                        <input type="date" x-model="editableTransaction.date" id="editDate" disabled
                            class="mt-0.5 block w-full p-1.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs sm:text-sm bg-gray-100">
                    </div>
                <?php endif ?>


                <div>
                    <label for="editFrom" class="block text-xs font-medium text-gray-700">Dari Lokasi:</label>
                    <select x-model="editableTransaction.from_location" id="editFrom"
                        class="mt-0.5 block w-full p-1.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs sm:text-sm"
                        @change="handleLocationChange('from')">
                        <option value="">-Semua-</option>
                        <template x-for="location in filterOptions.locations" :key="location.lokasi">
                            <option :value="location.lokasi" x-text="location.lokasi"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label for="editTo" class="block text-xs font-medium text-gray-700">Tujuan Lokasi:</label>
                    <select x-model="editableTransaction.to_location" id="editTo"
                        class="mt-0.5 block w-full p-1.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs sm:text-sm"
                        @change="handleLocationChange('to')">
                        <option value="">-Semua-</option>
                        <template x-for="location in filterOptions.locations" :key="location.lokasi">
                            <option :value="location.lokasi" x-text="location.lokasi"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label for="editItem" class="block text-xs font-medium text-gray-700">Item:</label>
                    <select x-model="editableTransaction.item_raw" id="editItem" x-ref="editItemSelect"
                        class="mt-0.5 block w-full p-1.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs sm:text-sm">
                        <option value="">-Semua-</option>
                        <template x-for="item in filterOptions.items" :key="item.name">
                            <option :value="item.name" x-text="item.name"></option>
                        </template>
                    </select>
                    <div class="text-xs text-gray-600 mt-1 mb-1">
                        Jika Anda tidak menemukan item yang dimaksud, pastikan item tersebut sudah aktif.
                        <a href="?page=master/item" class="text-blue-600 hover:underline font-medium ml-1">
                            Aktifkan Item
                        </a>
                    </div>
                </div>
                <div>
                    <label for="editSubitem" class=" block text-xs font-medium text-gray-700">Subitem:</label>
                    <select x-model="editableTransaction.subitem_raw" id="editSubitem" x-ref="editSubitemSelect"
                        :disabled="isSubitemEditDisabled"
                        class="mt-0.5 block w-full p-1.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs sm:text-sm disabled:bg-gray-100">
                        <option value=""></option>
                        <template x-for="subitem in filteredSubitems" :key="subitem.subitem">
                            <option :value="subitem.subitem" x-text="subitem.subitem"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label for="editEp" class="block text-xs font-medium text-gray-700">Ekor/Pack:</label>
                    <input type="text" inputmode="numeric"
                        pattern="[0-9]*" x-model.number="editableTransaction.ekor_pack" id="editEp"
                        class="mt-0.5 block w-full p-1.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs sm:text-sm">
                </div>
                <div>
                    <label for="editBerat" class="block text-xs font-medium text-gray-700">Berat (Kg):</label>
                    <input type="text" inputmode="numeric"
                        pattern="[0-9]*" x-model.number="editableTransaction.quantity_kg" id="editBerat"
                        class="mt-0.5 block w-full p-1.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs sm:text-sm">
                </div>

                <div>
                    <label for="editBarcode" class="block text-xs font-medium text-gray-700">Barcode:</label>
                    <input type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        x-model="editableTransaction.barcode"
                        id="editBarcode"
                        class="block w-full p-1.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs sm:text-sm 
               disabled:bg-gray-100 disabled:text-gray-500 disabled:border-gray-300"
                        disabled>
                </div>

                <div x-show="showStockInfo" class="col-span-full bg-gray-100 p-2 rounded-md text-xs sm:text-sm">
                    <p class="font-medium">Stok Saat Ini di <span x-text="editableTransaction.from_location"></span>:</p>
                    <p>Ekor/Pack: <span x-text="currentStock.ekor_pack"></span></p>
                    <p>Berat (Kg): <span x-text="currentStock.quantity_kg"></span></p>
                </div>
                <div class="col-span-full">
                    <label for="editKeterangan" class="block text-xs font-medium text-gray-700">Keterangan:</label>
                    <textarea x-model="editableTransaction.keterangan" id="editKeterangan" rows="3"
                        class="mt-0.5 block w-full p-1.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs sm:text-sm" required></textarea>
                </div>
            </div>

            <div class="flex justify-end space-x-2 pt-3 sm:pt-4">
                <button x-show="!isEditing" @click="toggleEditMode()"
                    class="px-3 py-1.5 bg-yellow-500 text-white rounded-md hover:bg-yellow-600 text-xs sm:text-sm">
                    Edit
                </button>
                <button x-show="isEditing" @click="saveTransaction()" :disabled="saving"
                    class="px-3 py-1.5 bg-green-600 text-white rounded-md hover:bg-green-700 text-xs sm:text-sm">
                    <span x-show="!saving">Simpan Perubahan</span>
                    <span x-show="saving" class="flex items-center justify-center text-xs sm:text-sm">
                        <i class="fas fa-spinner fa-spin mr-1.5"></i> Menyimpan...
                    </span>
                </button>
                <button x-show="isEditing" @click="cancelEdit()"
                    class="px-3 py-1.5 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 text-xs sm:text-sm">
                    Batal
                </button>
                <button x-show="isSuperAdmin" @click="confirmDelete(selectedTransaction.id, selectedTransaction.item, selectedTransaction.transaction_type, selectedTransaction.date)"
                    class="px-3 py-1.5 bg-red-600 text-white rounded-md hover:bg-red-700 text-xs sm:text-sm">
                    Hapus Transaksi
                </button>
            </div>
        </div>
        <div x-show="!selectedTransaction" class="text-gray-700 text-center py-4 text-xs sm:text-sm">
            <p>Memuat detail transaksi...</p>
            <i class="fas fa-spinner fa-spin mt-2 text-gray-500 text-lg sm:text-2xl"></i>
        </div>
    </div>
</div>