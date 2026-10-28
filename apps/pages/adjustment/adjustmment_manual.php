<style>
    /* Tetap mempertahankan styling Select2 */
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
    .mobile-only {
        display: none;
    }

    .desktop-only {
        display: block;
    }

    /* Show mobile-only on small screens (e.g., up to 640px, default Tailwind 'sm' breakpoint) */
    @media (max-width: 639px) {
        .mobile-only {
            display: block;
        }

        .desktop-only {
            display: none;
        }
    }
</style>

<main class="container mx-auto p-2 sm:p-4" x-data="adjustmentApp()">
    <div class="bg-white rounded-lg shadow-lg p-4 sm:p-6">
        <h1 class="text-xl sm:text-2xl font-bold mb-4 text-gray-800 flex items-center">
            <i class="fa-solid fa-screwdriver-wrench mr-2 text-blue-500"></i> Manual Stock Adjustment
        </h1>
        <p class="text-xs text-gray-600 mb-4">
            Lakukan penyesuaian stok secara manual untuk item dan subitem tertentu.
        </p>

        <div class="bg-blue-100 p-3 rounded-md mb-4 flex flex-wrap justify-between items-center text-blue-800 font-semibold shadow text-xs">
            <div class="flex items-center mb-1 sm:mb-0">
                <i class="fa-solid fa-layer-group mr-1 text-blue-600"></i>
                <span>Total Item:</span>
                <span class="ml-1" x-text="totalItemsCount"></span>
            </div>
            <div class="flex items-center mb-1 sm:mb-0">
                <i class="fa-solid fa-cube mr-1 text-blue-600"></i>
                <span>Total Ekor:</span>
                <span class="ml-1" x-text="totalEkor"></span>
            </div>
            <div class="flex items-center">
                <i class="fa-solid fa-weight-hanging mr-1 text-blue-600"></i>
                <span>Total KG:</span>
                <span class="ml-1" x-text="totalKg.toFixed(2)"></span>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
            <div>
                <label for="adjustment_date" class="block text-xs font-medium text-gray-700 mb-1">
                    Tanggal Adjustment <span class="text-red-500">*</span>
                </label>
                <input type="date" id="adjustment_date" x-model="adjustmentDate"
                    class="w-full p-2 text-xs border rounded-md focus:ring focus:ring-blue-200 focus:border-blue-500 transition duration-150 ease-in-out" required>
            </div>
            <div>
                <label for="adjustment_type" class="block text-xs font-medium text-gray-700 mb-1">
                    Tipe Adjustment <span class="text-red-500">*</span>
                </label>
                <select id="adjustment_type" x-model="adjustmentType"
                    class="w-full p-2 text-xs border rounded-md focus:ring focus:ring-blue-200 focus:border-blue-500 transition duration-150 ease-in-out" required>
                    <option value="">Pilih Tipe Adjustment</option>
                    <option value="stok_chiller_harian">Stok Chiller Harian</option>
                    <option value="stok_opname_mingguan">Stok Opname Mingguan</option>
                </select>
            </div>
            <div>
                <label for="storage" class="block text-xs font-medium text-gray-700 mb-1">
                    Lokasi Penyimpanan (Storage) <span class="text-red-500">*</span>
                </label>
                <select id="storage" x-model="storage"
                    class="w-full p-2 text-xs border rounded-md focus:ring focus:ring-blue-200 focus:border-blue-500 transition duration-150 ease-in-out" required>
                    <option value="">Pilih Storage</option>
                    <option value="Chiller 1">Chiller 1</option>
                    <option value="Chiller 2">Chiller 2</option>
                    <option value="Chiller 3">Chiller 3</option>
                </select>
            </div>
        </div>

        <div class="desktop-only">
            <div class="overflow-x-auto shadow-md rounded-lg">
                <table class="w-full text-xs text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th scope="col" class="py-2 px-3 sm:px-4 text-center">No</th>
                            <th scope="col" class="py-2 px-3 sm:px-4">Barcode</th>
                            <th scope="col" class="py-2 px-3 sm:px-4">Item <span class="text-red-500">*</span></th>
                            <th scope="col" class="py-2 px-3 sm:px-4">Subitem</th>
                            <th scope="col" class="py-2 px-3 sm:px-4">Ekor/Pack <span class="text-red-500">*</span></th>
                            <th scope="col" class="py-2 px-3 sm:px-4">Quantity (KG) <span class="text-red-500">*</span></th>
                            <th scope="col" class="py-2 px-3 sm:px-4">Keterangan</th>
                            <th scope="col" class="py-2 px-3 sm:px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs">
                        <template x-for="(row, index) in rows" :key="row.id">
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="py-2 px-3 sm:px-4 text-center font-semibold" x-text="index + 1"></td>
                                <td class="py-2 px-3 sm:px-4">
                                    <input type="text" x-model="row.barcode"
                                        class="w-24 p-1 text-xs border rounded-md focus:ring-blue-200" placeholder="Opsional">
                                </td>
                                <td class="py-2 px-3 sm:px-4">
                                    <select :id="'item-select-' + row.id" class="item-select w-full min-w-[120px] text-xs" required>
                                        <option value=""></option>
                                    </select>
                                </td>
                                <td class="py-2 px-3 sm:px-4">
                                    <select :id="'subitem-select-' + row.id" class="subitem-select w-full min-w-[120px] text-xs">
                                        <option value=""></option>
                                    </select>
                                </td>
                                <td class="py-2 px-3 sm:px-4">
                                    <input type="number" step="1" x-model="row.ekor_pack" @input="updateTotals"
                                        class="w-20 p-1 text-xs border rounded-md text-right focus:ring-blue-200" placeholder="0">
                                </td>
                                <td class="py-2 px-3 sm:px-4">
                                    <input type="number" step="0.01" x-model="row.quantity_kg" @input="updateTotals"
                                        class="w-20 p-1 text-xs border rounded-md text-right focus:ring-blue-200" placeholder="0.00">
                                </td>
                                <td class="py-2 px-3 sm:px-4">
                                    <input type="text" x-model="row.keterangan"
                                        class="w-full p-1 text-xs border rounded-md min-w-[120px] focus:ring-blue-200" placeholder="Keterangan (opsional)">
                                </td>
                                <td class="py-2 px-3 sm:px-4 text-center">
                                    <button @click="removeRow(row.id)" class="text-red-500 hover:text-red-700 transition-colors duration-150 ease-in-out" :disabled="rows.length === 1">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mobile-only">
            <div class="space-y-4">
                <template x-for="(row, index) in rows" :key="row.id">
                    <div class="bg-white border rounded-lg shadow-sm p-3 relative">
                        <div class="flex justify-between items-start mb-2 border-b pb-2">
                            <h3 class="font-bold text-sm text-gray-800">
                                Baris Item #<span x-text="index + 1"></span>
                            </h3>
                            <button @click="removeRow(row.id)" class="text-red-500 hover:text-red-700 transition-colors duration-150 ease-in-out" :disabled="rows.length === 1" aria-label="Hapus Item">
                                <i class="fa-solid fa-trash text-base"></i>
                            </button>
                        </div>

                        <div class="space-y-3 text-xs">
                            <div>
                                <label :for="'item-select-mobile-' + row.id" class="block font-medium text-gray-700 mb-1">
                                    Item <span class="text-red-500">*</span>
                                </label>
                                <select :id="'item-select-mobile-' + row.id" class="item-select w-full text-xs" required>
                                    <option value=""></option>
                                </select>
                            </div>

                            <div>
                                <label :for="'subitem-select-mobile-' + row.id" class="block font-medium text-gray-700 mb-1">
                                    Subitem
                                </label>
                                <select :id="'subitem-select-mobile-' + row.id" class="subitem-select w-full text-xs">
                                    <option value=""></option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label for="ekor_pack" class="block font-medium text-gray-700 mb-1">
                                        Ekor/Pack <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" step="1" x-model="row.ekor_pack" @input="updateTotals"
                                        class="w-full p-2 text-xs border rounded-md text-right focus:ring-blue-200" placeholder="0">
                                </div>
                                <div>
                                    <label for="quantity_kg" class="block font-medium text-gray-700 mb-1">
                                        Quantity (KG) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" step="0.01" x-model="row.quantity_kg" @input="updateTotals"
                                        class="w-full p-2 text-xs border rounded-md text-right focus:ring-blue-200" placeholder="0.00">
                                </div>
                            </div>

                            <div>
                                <label for="barcode" class="block font-medium text-gray-700 mb-1">
                                    Barcode (Opsional)
                                </label>
                                <input type="text" x-model="row.barcode"
                                    class="w-full p-2 text-xs border rounded-md focus:ring-blue-200" placeholder="Barcode">
                            </div>

                            <div>
                                <label for="keterangan" class="block font-medium text-gray-700 mb-1">
                                    Keterangan (Opsional)
                                </label>
                                <input type="text" x-model="row.keterangan"
                                    class="w-full p-2 text-xs border rounded-md focus:ring-blue-200" placeholder="Detail penyesuaian">
                            </div>

                        </div>
                    </div>
                </template>
            </div>
        </div>
        <div class="mt-3 flex justify-end gap-2">
            <button @click="resetForm()" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-1 px-3 text-xs rounded-md transition duration-150 ease-in-out flex items-center">
                <i class="fa-solid fa-sync-alt mr-1"></i> Reset Form
            </button>
            <button @click="addRow()" class="bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-3 text-xs rounded-md transition duration-150 ease-in-out flex items-center">
                <i class="fa-solid fa-plus-circle mr-1"></i> Tambah Line
            </button>
        </div>

        <div class="mt-6 pt-3 border-t border-gray-200 flex justify-center">
            <button @click="confirmAndSave()" :disabled="isLoading"
                class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 text-sm rounded-full shadow-lg transition duration-300 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center">
                <span x-show="!isLoading"><i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Adjustment</span>
                <span x-show="isLoading" class="text-sm">
                    <i class="fa-solid fa-spinner animate-spin mr-2"></i> Menyimpan...
                </span>
            </button>
        </div>
    </div>
</main>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('adjustmentApp', () => ({
            adjustmentDate: '',
            adjustmentType: '',
            storage: '',
            rows: [{
                id: 1,
                barcode: '',
                item: '',
                subitem: '',
                ekor_pack: '',
                quantity_kg: '',
                keterangan: 'Manual Stock Adjustment'
            }],
            isLoading: false,
            lastId: 1,
            items: [],
            subitems: [],

            init() {
                this.adjustmentDate = new Date().toISOString().slice(0, 10);
                this.fetchItems();
                this.fetchSubitems();
                this.renderSelect2('item-select', this.items);
                this.renderSelect2('subitem-select', this.subitems);
            },

            get totalEkor() {
                return this.rows.reduce((sum, row) => sum + (parseFloat(row.ekor_pack) || 0), 0);
            },

            get totalKg() {
                return this.rows.reduce((sum, row) => sum + (parseFloat(row.quantity_kg) || 0), 0);
            },

            get totalItemsCount() {
                return this.rows.filter(row => {
                    const hasItem = row.item && row.item.trim() !== '';
                    const hasQuantity = (parseFloat(row.ekor_pack) || 0) !== 0 || (parseFloat(row.quantity_kg) || 0) !== 0;
                    return hasItem && hasQuantity;
                }).length;
            },

            updateTotals() {
                // Method ini tidak diperlukan lagi karena menggunakan computed properties.
                // Tapi tetap dipertahankan agar tidak ada error saat event @input dipanggil.
            },

            fetchItems() {
                fetch('./controller/adjustment/adjustment_manual.php?action=get_item_list')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.items = [{
                                id: '',
                                text: 'Pilih Item'
                            }, ...data.data.map(item => ({
                                id: item.item,
                                text: item.item
                            }))];
                            this.renderSelect2('item-select', this.items);
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error', 'Gagal memuat daftar item. Silakan coba lagi.', 'error');
                        console.error('Error fetching items:', error);
                    });
            },

            fetchSubitems() {
                fetch('./controller/adjustment/adjustment_manual.php?action=get_subitem_list')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.subitems = [{
                                id: '',
                                text: 'Pilih Subitem'
                            }, ...data.data.map(item => ({
                                id: item.subitem,
                                text: item.subitem
                            }))];
                            this.renderSelect2('subitem-select', this.subitems);
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error', 'Gagal memuat daftar subitem. Silakan coba lagi.', 'error');
                        console.error('Error fetching subitems:', error);
                    });
            },

            renderSelect2(className, data) {
                this.$nextTick(() => {
                    $('.' + className).each((index, element) => {
                        const $element = $(element);
                        if ($element.data('select2')) {
                            $element.select2('destroy');
                        }
                        $element.select2({
                            placeholder: `Pilih ${className.includes('item') ? 'Item' : 'Subitem'}`,
                            allowClear: true,
                            data: data,
                            theme: "default",
                            dropdownCssClass: 'shadow-lg border border-gray-200'
                        });

                        $element.on('change', (e) => {
                            const rowId = $(e.target).attr('id').split('-').pop();
                            const rowIndex = this.rows.findIndex(row => row.id == rowId);
                            if (rowIndex !== -1) {
                                if (className.includes('item')) {
                                    this.rows[rowIndex].item = e.target.value;
                                } else {
                                    this.rows[rowIndex].subitem = e.target.value;
                                }
                            }
                        });
                    });
                });
            },

            addRow() {
                this.lastId++;
                const newRow = {
                    id: this.lastId,
                    barcode: '',
                    item: '',
                    subitem: '',
                    ekor_pack: '',
                    quantity_kg: '',
                    keterangan: 'Manual Stock Adjustment'
                };
                this.rows.push(newRow);
                this.$nextTick(() => {
                    this.renderSelect2('item-select', this.items);
                    this.renderSelect2('subitem-select', this.subitems);
                });
            },

            removeRow(id) {
                if (this.rows.length > 1) {
                    this.rows = this.rows.filter(row => row.id !== id);
                    this.$nextTick(() => {
                        this.rows.forEach(() => {
                            this.renderSelect2('item-select', this.items);
                            this.renderSelect2('subitem-select', this.subitems);
                        });
                    });
                }
            },

            resetForm() {
                this.adjustmentDate = new Date().toISOString().slice(0, 10);
                this.adjustmentType = '';
                this.storage = '';
                this.rows = [{
                    id: 1,
                    barcode: '',
                    item: '',
                    subitem: '',
                    ekor_pack: '',
                    quantity_kg: '',
                    keterangan: 'Manual Stock Adjustment'
                }];
                this.lastId = 1;
                // Menghancurkan dan menginisialisasi ulang Select2
                this.$nextTick(() => {
                    $('select.item-select').val(null).trigger('change');
                    $('select.subitem-select').val(null).trigger('change');
                    this.renderSelect2('item-select', this.items);
                    this.renderSelect2('subitem-select', this.subitems);
                });
            },

            confirmAndSave() {
                if (!this.adjustmentDate || !this.adjustmentType || !this.storage) {
                    Swal.fire({
                        title: 'Peringatan',
                        text: 'Tanggal, Tipe Adjustment, dan Storage harus diisi.',
                        icon: 'warning',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'bg-blue-600 text-white hover:bg-blue-700 font-bold py-2 px-4 rounded-full transition duration-300'
                        },
                        buttonsStyling: false
                    });
                    return;
                }

                const dataToSave = this.rows.map(row => {
                    const itemValue = $(`#item-select-${row.id}`).val();
                    const subitemValue = $(`#subitem-select-${row.id}`).val();
                    return {
                        barcode: row.barcode,
                        item: itemValue,
                        subitem: subitemValue,
                        ekor_pack: parseFloat(row.ekor_pack) || 0,
                        quantity_kg: parseFloat(row.quantity_kg) || 0,
                        keterangan: row.keterangan
                    };
                }).filter(row => row.item && (row.ekor_pack !== 0 || row.quantity_kg !== 0));

                if (dataToSave.length === 0) {
                    Swal.fire({
                        title: 'Peringatan',
                        text: 'Tidak ada data adjustment yang valid untuk disimpan. Pastikan Anda telah memilih item dan salah satu nilai kuantitas tidak nol.',
                        icon: 'warning',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'bg-blue-600 text-white hover:bg-blue-700 font-bold py-2 px-4 rounded-full transition duration-300'
                        },
                        buttonsStyling: false
                    });
                    return;
                }

                const totalItems = dataToSave.length;
                const totalEkor = dataToSave.reduce((sum, row) => sum + row.ekor_pack, 0);
                const totalKg = dataToSave.reduce((sum, row) => sum + row.quantity_kg, 0);

                Swal.fire({
                    title: '<span class="text-xl font-semibold text-gray-800">Konfirmasi Adjustment</span>',
                    html: `
                        <div class="text-left mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <table class="w-full text-xs text-gray-700">
                                <tr>
                                    <td class="font-medium pr-2">Tanggal:</td>
                                    <td>${this.adjustmentDate}</td>
                                </tr>
                                <tr>
                                    <td class="font-medium pr-2">Tipe:</td>
                                    <td>${this.adjustmentType}</td>
                                </tr>
                                <tr>
                                    <td class="font-medium pr-2">Storage:</td>
                                    <td>${this.storage}</td>
                                </tr>
                            </table>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <p class="font-semibold text-gray-800">Ringkasan Penyesuaian:</p>
                                <table class="w-full text-xs text-gray-700 mt-2">
                                    <tr>
                                        <td class="font-medium pr-2">Total Item:</td>
                                        <td>${totalItems}</td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium pr-2">Total Ekor:</td>
                                        <td>${totalEkor}</td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium pr-2">Total Berat (KG):</td>
                                        <td>${totalKg.toFixed(2)}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    `,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Simpan',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-xl shadow-2xl',
                        confirmButton: 'bg-blue-600 text-white hover:bg-blue-700 font-bold py-2 px-4 rounded-full transition duration-300',
                        cancelButton: 'bg-gray-400 text-white hover:bg-gray-500 font-bold py-2 px-4 rounded-full transition duration-300'
                    },
                    buttonsStyling: false,
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        return this.saveAdjustment(dataToSave).then(response => {
                            if (!response.success) {
                                throw new Error(response.message);
                            }
                            return response;
                        }).catch(error => {
                            Swal.showValidationMessage(`Permintaan gagal: ${error}`);
                        });
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: result.value.message,
                            icon: 'success',
                            customClass: {
                                confirmButton: 'bg-blue-600 text-white hover:bg-blue-700 font-bold py-2 px-4 rounded-full transition duration-300'
                            },
                            buttonsStyling: false
                        });
                        this.resetForm();
                    }
                });
            },

            saveAdjustment(dataToSave) {
                this.isLoading = true;
                const payload = {
                    action: 'save_manual_adjustments',
                    adjustment_date: this.adjustmentDate,
                    adjustment_type: this.adjustmentType,
                    storage: this.storage,
                    details: dataToSave
                };

                return new Promise((resolve, reject) => {
                    fetch('./controller/adjustment/adjustment_manual.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(payload),
                        })
                        .then(response => response.json())
                        .then(data => {
                            this.isLoading = false;
                            resolve(data);
                        })
                        .catch(error => {
                            this.isLoading = false;
                            console.error('Error:', error);
                            reject(new Error('Terjadi kesalahan saat menyimpan data. Silakan coba lagi.'));
                        });
                });
            }
        }));
    });
</script>