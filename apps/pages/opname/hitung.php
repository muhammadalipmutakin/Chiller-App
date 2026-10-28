<style>
    .select2-container--default .select2-selection--single {
        height: 42px;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 42px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
    }

    /* Specific styles for Select2 when disabled */
    .select2-container--default .select2-selection--single.select2-selection--disabled {
        background-color: #f3f4f6;
        cursor: not-allowed;
    }

    .select2-container--default .select2-selection--single.select2-selection--disabled .select2-selection__rendered {
        color: #4b5563;
        /* Text color for disabled Select2 */
    }

    @media (max-width: 768px) {
        .responsive-table {
            display: block;
            overflow-x: auto;
            white-space: nowrap;
        }
    }

    .disabled-input {
        background-color: #f3f4f6;
        cursor: not-allowed;
    }
</style>



<div class="flex-1 min-w-0 transition-all duration-300" x-data="hitunganApp()" x-init="init()">
    <a href="?page=opname/index" class="inline-flex items-center px-3 py-1.5 text-xs bg-gray-200 text-gray-700 rounded-md shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-75 transition duration-300 mb-4">
        <i class="fas fa-chevron-left w-3 h-3 mr-1"></i>
        Kembali
    </a>

    <div class="flex justify-between items-center mb-4 mt-4">
        <h1 class="text-sm sm:text-lg font-bold text-gray-800">Stock Opname - Hitung</h1>

        <div class="flex gap-x-2">
            <a href="?page=opname/data_input" class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1.5 rounded-md flex items-center">
                <i class="fas fa-eye mr-1"></i> Lihat Data Input
            </a>

            <a href="?page=opname/proses_perhitungan" class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1.5 rounded-md flex items-center">
                <i class="fas fa-cogs mr-1"></i> Proses Perhitungan
            </a>
        </div>
    </div>

    <div class="bg-white p-3 rounded-lg shadow mb-4">
        <label class="block text-xs font-medium text-gray-700 mb-1">Pilih Storage</label>
        <select x-model="selectedStorage" @change="loadHitungData()" class="w-full p-1.5 text-xs border rounded"
            id="storageSelect">
            <option value="">-- Pilih Storage --</option>
            <template x-for="location in locations" :key="location.id">
                <option :value="location.lokasi" x-text="location.lokasi"></option>
            </template>
        </select>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Item</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Subitem</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Ekor/Pack</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Berat (kg)</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 text-xs">
                    <template x-for="(row, index) in hitungData" :key="row.id">
                        <tr>
                            <td class="px-2 py-2 whitespace-nowrap">
                                <template x-if="row.editing">
                                    <select x-model="row.editItem" class="w-full p-1 border rounded item-select text-xs"
                                        :id="'editItemSelect'+row.id"></select>
                                </template>
                                <template x-if="!row.editing">
                                    <div x-text="getItemName(row.item)" class="p-1"></div>
                                </template>
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap">
                                <template x-if="row.editing">
                                    <select x-model="row.editSubitem"
                                        class="w-full p-1 border rounded subitem-select text-xs"
                                        :id="'editSubitemSelect'+row.id"></select>
                                </template>
                                <template x-if="!row.editing">
                                    <div x-text="getSubitemName(row.item)" class="p-1"></div>
                                </template>
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap">
                                <input x-model="row.editEkorPack" type="number"
                                    x-bind:class="{'disabled-input': !row.editing}"
                                    x-bind:readonly="!row.editing" class="w-full p-1 border rounded text-xs"
                                    @keydown.enter="row.editing && $nextTick(() => { $event.target.closest('tr').querySelector('input[type=number][x-model=\'row.editQuantityKg\']').focus() })"
                                    placeholder="0">
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap">
                                <input x-model="row.editQuantityKg" type="number" step="0.01"
                                    x-bind:class="{'disabled-input': !row.editing}"
                                    x-bind:readonly="!row.editing" class="w-full p-1 border rounded text-xs"
                                    @keydown.enter="row.editing && saveEdit(row)" placeholder="0.00">
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap">
                                <template x-if="!row.editing">
                                    <div>
                                        <button @click="enableEdit(row)"
                                            class="text-blue-600 hover:text-blue-900 mr-1 text-xs">Edit</button>
                                        <button @click="confirmDelete(row.id)"
                                            class="text-red-600 hover:text-red-900 text-xs">Hapus</button>
                                    </div>
                                </template>
                                <template x-if="row.editing">
                                    <div>
                                        <button @click="saveEdit(row)"
                                            class="text-green-600 hover:text-green-900 mr-1 text-xs">Simpan</button>
                                        <button @click="cancelEdit(row)"
                                            class="text-gray-600 hover:text-gray-900 text-xs">Batal</button>
                                    </div>
                                </template>
                            </td>
                        </tr>
                    </template>

                    <tr>
                        <td class="px-2 py-2 whitespace-nowrap">
                            <select x-model="newRow.item" class="w-full p-1.5 border rounded item-select text-xs"
                                id="newItemSelect" autofocus>
                                <option value="">-- Pilih Item --</option>
                            </select>
                        </td>
                        <td class="px-2 py-2 whitespace-nowrap">
                            <select x-model="newRow.subitem" class="w-full p-1.5 border rounded subitem-select text-xs"
                                id="newSubitemSelect">
                                <option value="">-- Pilih Subitem --</option>
                            </select>
                        </td>
                        <td class="px-2 py-2 whitespace-nowrap">
                            <input x-model="newRow.ekor_pack" type="number" class="w-full p-1.5 border rounded text-xs"
                                @keydown.enter="moveToNextField($event, 'quantity_kg')" placeholder="0">
                        </td>
                        <td class="px-2 py-2 whitespace-nowrap">
                            <input x-model="newRow.quantity_kg" type="number" step="0.01"
                                class="w-full p-1.5 border rounded text-xs" @keydown.enter="addNewRow()" placeholder="0.00">
                        </td>
                        <td class="px-2 py-2 whitespace-nowrap">
                            <button @click="addNewRow()" id="addNewRowButton"
                                class="bg-blue-500 text-white px-3 py-1.5 rounded hover:bg-blue-600 text-xs">Tambah</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function hitunganApp() {
        return {
            locations: [],
            selectedStorage: '',
            hitungData: [],
            newRow: {
                item: '',
                subitem: '',
                ekor_pack: '',
                quantity_kg: '',
                storage: ''
            },
            currentEditingRowId: null,
            isAddingRow: false,

            async init() {
                await this.loadLocations();
                this.initSelect2();
                $('#storageSelect').select2({
                    placeholder: '-- Pilih Storage --',
                    allowClear: true
                });
                $('#storageSelect').on('change', (e) => {
                    this.selectedStorage = e.target.value;
                    this.loadHitungData();
                });
            },

            getItemName(fullItem) {
                return fullItem.split(' : ')[0];
            },

            getSubitemName(fullItem) {
                const parts = fullItem.split(' : ');
                return parts.length > 1 ? parts[1] : '';
            },

            async loadLocations() {
                try {
                    const response = await fetch('./controller/opname/hitung.php?action=get_locations');
                    this.locations = await response.json();
                } catch (error) {
                    console.error('Error loading locations:', error);
                    this.showError('Gagal memuat data lokasi');
                }
            },

            async loadHitungData() {
                if (!this.selectedStorage) {
                    this.hitungData = [];
                    return;
                }

                try {
                    const currentUser = '<?php echo $_SESSION['user_nama'] ?? ''; ?>';
                    const response = await fetch(`./controller/opname/hitung.php?action=get_hitung_data&storage=${encodeURIComponent(this.selectedStorage)}&user=${encodeURIComponent(currentUser)}`);
                    const data = await response.json();

                    this.hitungData = data.map(row => ({
                        ...row,
                        editing: false,
                        editItem: this.getItemName(row.item),
                        editSubitem: this.getSubitemName(row.item),
                        editEkorPack: row.ekor_pack,
                        editQuantityKg: row.quantity_kg
                    }));

                    this.newRow.storage = this.selectedStorage;
                } catch (error) {
                    console.error('Error loading hitung data:', error);
                    this.showError('Gagal memuat data hitung');
                }
            },

            initSelect2() {
                $('#newItemSelect').select2({
                    placeholder: 'Cari Item',
                    allowClear: true,
                    ajax: {
                        url: './controller/opname/hitung.php?action=get_items',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                q: params.term
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.map(item => ({
                                    id: item.item,
                                    text: `${item.sku} - ${item.item}`
                                }))
                            };
                        },
                        cache: true
                    }
                }).on('change', (e) => {
                    this.newRow.item = e.target.value;
                    this.$nextTick(() => {
                        if ($('#newSubitemSelect').length) {
                            $('#newSubitemSelect').select2('open');
                        }
                    });
                });

                $('#newSubitemSelect').select2({
                    placeholder: 'Cari Subitem',
                    allowClear: true,
                    ajax: {
                        url: './controller/opname/hitung.php?action=get_subitems',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                q: params.term
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.map(subitem => ({
                                    id: subitem.subitem,
                                    text: subitem.subitem
                                }))
                            };
                        },
                        cache: true
                    }
                }).on('change', (e) => {
                    this.newRow.subitem = e.target.value;
                    this.$nextTick(() => {
                        $('input[x-model="newRow.ekor_pack"]').focus();
                    });
                });
            },

            async initEditSelect2(row) {
                this.$nextTick(() => {
                    const itemSelect = $(`#editItemSelect${row.id}`);
                    const subitemSelect = $(`#editSubitemSelect${row.id}`);

                    itemSelect.select2({
                        placeholder: 'Cari Item',
                        allowClear: true,
                        ajax: {
                            url: './controller/opname/hitung.php?action=get_items',
                            dataType: 'json',
                            delay: 250,
                            data: function(params) {
                                return {
                                    q: params.term
                                };
                            },
                            processResults: function(data) {
                                return {
                                    results: data.map(item => ({
                                        id: item.item,
                                        text: `${item.sku} - ${item.item}`
                                    }))
                                };
                            },
                            cache: true
                        }
                    });

                    if (row.editItem) {
                        $.ajax({
                            url: './controller/opname/hitung.php?action=get_single_item',
                            data: {
                                item: row.editItem
                            },
                            dataType: 'json'
                        }).done(function(data) {
                            if (data && data.item) {
                                const optionText = `${data.sku} - ${data.item}`;
                                if (!itemSelect.find(`option[value="${row.editItem}"]`).length) {
                                    const newOption = new Option(optionText, row.editItem, true, true);
                                    itemSelect.append(newOption);
                                }
                                itemSelect.val(row.editItem).trigger('change');
                            } else {
                                if (!itemSelect.find(`option[value="${row.editItem}"]`).length) {
                                    const newOption = new Option(row.editItem, row.editItem, true, true);
                                    itemSelect.append(newOption);
                                }
                                itemSelect.val(row.editItem).trigger('change');
                            }
                        }).fail(function() {
                            if (!itemSelect.find(`option[value="${row.editItem}"]`).length) {
                                const newOption = new Option(row.editItem, row.editItem, true, true);
                                itemSelect.append(newOption);
                            }
                            itemSelect.val(row.editItem).trigger('change');
                        });
                    } else {
                        itemSelect.val(null).trigger('change');
                    }

                    itemSelect.on('change', (e) => {
                        row.editItem = e.target.value;
                        this.$nextTick(() => {
                            if (subitemSelect.length) {
                                subitemSelect.select2('open');
                            }
                        });
                    });

                    subitemSelect.select2({
                        placeholder: 'Cari Subitem',
                        allowClear: true,
                        ajax: {
                            url: './controller/opname/hitung.php?action=get_subitems',
                            dataType: 'json',
                            delay: 250,
                            data: function(params) {
                                return {
                                    q: params.term
                                };
                            },
                            processResults: function(data) {
                                return {
                                    results: data.map(subitem => ({
                                        id: subitem.subitem,
                                        text: subitem.subitem
                                    }))
                                };
                            },
                            cache: true
                        }
                    });

                    if (row.editSubitem) {
                        if (!subitemSelect.find(`option[value="${row.editSubitem}"]`).length) {
                            const newOption = new Option(row.editSubitem, row.editSubitem, true, true);
                            subitemSelect.append(newOption);
                        }
                        subitemSelect.val(row.editSubitem).trigger('change');
                    } else {
                        subitemSelect.val(null).trigger('change');
                    }

                    subitemSelect.on('change', (e) => {
                        row.editSubitem = e.target.value;
                        this.$nextTick(() => {
                            $(`#${subitemSelect.attr('id')}`).closest('tr').find('input[type=number][x-model=\'row.editEkorPack\']').focus();
                        });
                    });
                });
            },

            moveToNextField(event, targetFieldName) {
                event.preventDefault();
                const currentInput = event.target;
                const rowInputs = Array.from(currentInput.closest('tr').querySelectorAll('input, select'));

                const currentIndex = rowInputs.indexOf(currentInput);
                if (currentIndex === -1) return;

                let nextElement = null;
                if (targetFieldName === 'quantity_kg') {
                    nextElement = currentInput.closest('tr').querySelector('input[x-model="newRow.quantity_kg"]');
                } else if (targetFieldName === 'ekor_pack') {
                    nextElement = currentInput.closest('tr').querySelector('input[x-model="newRow.ekor_pack"]');
                }

                if (nextElement) {
                    if ($(nextElement).hasClass('select2-hidden-accessible')) {
                        $(nextElement).select2('open');
                    } else {
                        nextElement.focus();
                    }
                }
            },

            async addNewRow() {
                if (this.isAddingRow) return;

                if (!this.newRow.item || this.newRow.quantity_kg === null || this.newRow.quantity_kg === '') {
                    this.showError('Item dan berat harus diisi');
                    return;
                }

                if (!this.selectedStorage) {
                    this.showError('Pilih storage terlebih dahulu');
                    return;
                }

                this.isAddingRow = true;
                const addButton = document.getElementById('addNewRowButton');
                if (addButton) {
                    addButton.disabled = true;
                    addButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...';
                }

                const currentUser = '<?php echo $_SESSION['user_nama'] ?? ''; ?>';
                const opnameDate = '<?php echo date('Y-m-d'); ?>';

                try {
                    const response = await fetch('./controller/opname/hitung.php?action=save_row', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            ...this.newRow,
                            storage: this.selectedStorage,
                            user_hitung: currentUser,
                            opname_date: opnameDate
                        })
                    });

                    const result = await response.json();

                    if (result.error) {
                        this.showError(result.error);
                        return;
                    }

                    await this.loadHitungData();

                    // Reset only quantity fields
                    this.newRow.ekor_pack = '';
                    this.newRow.quantity_kg = '';

                    // Focus back to item select for next entry
                    this.$nextTick(() => {
                        $('#newItemSelect').select2('open');
                    });

                } catch (error) {
                    console.error('Error adding new row:', error);
                    this.showError('Gagal menyimpan data');
                } finally {
                    this.isAddingRow = false;
                    if (addButton) {
                        addButton.disabled = false;
                        addButton.innerHTML = 'Tambah';
                    }
                }
            },

            enableEdit(row) {
                if (this.currentEditingRowId && this.currentEditingRowId !== row.id) {
                    const currentlyEditedRow = this.hitungData.find(r => r.id === this.currentEditingRowId);
                    if (currentlyEditedRow) {
                        this.cancelEdit(currentlyEditedRow);
                    }
                }

                row.editing = true;
                this.currentEditingRowId = row.id;
                this.initEditSelect2(row);
            },

            cancelEdit(row) {
                const itemSelect = $(`#editItemSelect${row.id}`);
                const subitemSelect = $(`#editSubitemSelect${row.id}`);

                if (itemSelect.data('select2')) itemSelect.select2('destroy');
                if (subitemSelect.data('select2')) subitemSelect.select2('destroy');

                row.editing = false;
                this.currentEditingRowId = null;

                row.editItem = this.getItemName(row.item);
                row.editSubitem = this.getSubitemName(row.item);
                row.editEkorPack = row.ekor_pack;
                row.editQuantityKg = row.quantity_kg;
            },

            async saveEdit(row) {
                if (!row.editItem || row.editQuantityKg === null || row.editQuantityKg === '') {
                    this.showError('Item dan berat harus diisi');
                    return;
                }

                try {
                    const response = await fetch('./controller/opname/hitung.php?action=update_row', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id: row.id,
                            item: row.editItem,
                            subitem: row.editSubitem,
                            ekor_pack: row.editEkorPack,
                            quantity_kg: row.editQuantityKg
                        })
                    });

                    const result = await response.json();

                    if (result.error) {
                        this.showError(result.error);
                        return;
                    }

                    const itemSelect = $(`#editItemSelect${row.id}`);
                    const subitemSelect = $(`#editSubitemSelect${row.id}`);

                    if (itemSelect.data('select2')) itemSelect.select2('destroy');
                    if (subitemSelect.data('select2')) subitemSelect.select2('destroy');

                    row.item = row.editSubitem ?
                        `${row.editItem} : ${row.editSubitem}` :
                        row.editItem;
                    row.ekor_pack = row.editEkorPack;
                    row.quantity_kg = row.editQuantityKg;
                    row.editing = false;
                    this.currentEditingRowId = null;

                    this.showSuccess('Data berhasil diperbarui');

                } catch (error) {
                    console.error('Error updating row:', error);
                    this.showError('Gagal memperbarui data');
                }
            },

            confirmDelete(id) {
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data yang dihapus tidak dapat dikembalikan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.deleteRow(id);
                    }
                });
            },

            async deleteRow(id) {
                try {
                    const response = await fetch(`./controller/opname/hitung.php?action=delete_row&id=${id}`);
                    const result = await response.json();

                    if (response.ok && result.success) {
                        this.hitungData = this.hitungData.filter(row => row.id !== id);
                        this.showSuccess('Data berhasil dihapus');
                    } else {
                        this.showError(result.error || 'Gagal menghapus data. Pastikan Anda memiliki izin.');
                    }
                } catch (error) {
                    console.error('Error deleting row:', error);
                    this.showError('Gagal menghapus data');
                }
            },

            showError(message) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: message,
                });
            },

            showSuccess(message) {
                Swal.fire({
                    icon: 'success',
                    title: 'Sukses',
                    text: message,
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        };
    }
</script>