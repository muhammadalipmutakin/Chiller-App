<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<main class="flex-1 min-w-0 transition-all duration-300" x-data="transactionManager()" x-init="initData()">
    <h1 class="text-xl sm:text-2xl font-bold mb-4 text-gray-800">Daftar Transaksi</h1>

    <a href="?page=transaksi/create" class="inline-block px-3 py-1.5 text-xs sm:px-4 sm:py-2 sm:text-sm bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-75 transition duration-300 mb-4">
        Tambah Transaksi
    </a>

    <div class="bg-white shadow-lg rounded-lg p-3 sm:p-5">
        <?php include 'sections/filter.php' ?>
        <?php include 'sections/tabel.php' ?>
    </div>

    <button x-data="{ showScrollToTop: false }"
        x-init="window.addEventListener('scroll', () => { showScrollToTop = (window.pageYOffset > 200) });"
        x-show="showScrollToTop"
        @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
        class="fixed bottom-3 right-3 bg-blue-500 hover:bg-blue-600 text-white p-2 rounded-full shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 transition-all duration-300 ease-in-out"
        style="display: none;">
        <i class="fas fa-arrow-up h-4 w-4 text-sm"></i>
    </button>

    <?php include 'sections/modal.php' ?>
</main>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('transactionManager', () => ({
            showHiddenColumns: false,
            filteredSubitems: [],
            isSubitemEditDisabled: false,
            totals: {
                ekor_pack: 0,
                quantity_kg: 0
            },
            showFilters: false,
            isSuperAdmin: true,
            transactions: [],
            activeFilters: {},
            filterOptions: {
                users: [],
                locations: [],
                items: [],
                subitems: []
            },
            filters: {
                tanggal_awal: new Date().toISOString().slice(0, 10),
                tanggal_akhir: new Date().toISOString().slice(0, 10),
                type_transaksi: '',
                user_name: '',
                from_location: '',
                to_location: '',
                storage: '',
                item: [],
                subitem: [],
                search: '',
                barcode: '',
                limit: '500'
            },
            pendingUrlFilters: {}, // Menyimpan parameter URL sementara
            loading: false,
            errorMessage: '',
            isModalOpen: false,
            selectedTransaction: null,
            editableTransaction: {},
            isEditing: false,
            saving: false,
            chillerLocations: ['Chiller 1', 'Chiller 2', 'Chiller 3'],
            currentStock: {
                ekor_pack: 0,
                quantity_kg: 0
            },
            showStockInfo: false,
            no_barcode_in_count: 0,
            no_barcode_out_count: 0,
            no_barcode_total_count: 0,

            isDownloadModalOpen: false,
            downloadFilters: {
                tanggal_awal: new Date().toISOString().slice(0, 10),
                tanggal_akhir: new Date().toISOString().slice(0, 10),
            },

            // =========================================================================
            // 1. MEMBACA PARAMETER URL
            // =========================================================================
            loadFiltersFromUrl() {
                const urlParams = new URLSearchParams(window.location.search);
                this.pendingUrlFilters = {};

                if (urlParams.has('tanggal_awal')) {
                    this.filters.tanggal_awal = urlParams.get('tanggal_awal');
                    this.pendingUrlFilters.tanggal_awal = urlParams.get('tanggal_awal');
                }
                if (urlParams.has('tanggal_akhir')) {
                    this.filters.tanggal_akhir = urlParams.get('tanggal_akhir');
                    this.pendingUrlFilters.tanggal_akhir = urlParams.get('tanggal_akhir');
                }
                if (urlParams.has('type_transaksi')) {
                    this.filters.type_transaksi = urlParams.get('type_transaksi');
                    this.pendingUrlFilters.type_transaksi = urlParams.get('type_transaksi');
                }
                if (urlParams.has('user_name')) {
                    this.filters.user_name = urlParams.get('user_name');
                    this.pendingUrlFilters.user_name = urlParams.get('user_name');
                }
                if (urlParams.has('from_location')) {
                    this.pendingUrlFilters.from_location = urlParams.get('from_location');
                }
                if (urlParams.has('to_location')) {
                    this.pendingUrlFilters.to_location = urlParams.get('to_location');
                }
                if (urlParams.has('storage')) {
                    this.pendingUrlFilters.storage = urlParams.get('storage');
                }
                if (urlParams.has('barcode')) {
                    this.filters.barcode = urlParams.get('barcode');
                    this.pendingUrlFilters.barcode = urlParams.get('barcode');
                }
                if (urlParams.has('limit')) {
                    this.filters.limit = urlParams.get('limit');
                }

                if (urlParams.has('item')) {
                    const itemParam = urlParams.get('item');
                    if (itemParam) {
                        this.filters.item = itemParam.split(',').map(s => s.trim()).filter(Boolean);
                    }
                }

                if (urlParams.has('subitem')) {
                    const subitemParam = urlParams.get('subitem');
                    if (subitemParam) {
                        this.filters.subitem = subitemParam.split(',').map(s => s.trim()).filter(Boolean);
                    }
                }

                if (Object.keys(this.pendingUrlFilters).length > 0 || this.filters.item.length > 0) {
                    this.showFilters = true;
                }
            },

            // =========================================================================
            // 2. MEMASANG NILAI FILTER SETELAH OPSI LOKASI SELESAI DIRENDER DI DOM
            // =========================================================================
            applyUrlFiltersToState() {
                if (this.pendingUrlFilters.from_location) {
                    this.filters.from_location = this.pendingUrlFilters.from_location;
                }
                if (this.pendingUrlFilters.to_location) {
                    this.filters.to_location = this.pendingUrlFilters.to_location;
                }

                // Cocokkan storage secara fleksibel (Chiller 1 / chiller 1)
                if (this.pendingUrlFilters.storage) {
                    const targetStg = this.pendingUrlFilters.storage.toLowerCase().trim();
                    const $stgSelect = document.getElementById('filterStorage');
                    if ($stgSelect) {
                        let matched = false;
                        for (let opt of $stgSelect.options) {
                            if (opt.value.toLowerCase() === targetStg || opt.text.toLowerCase() === targetStg) {
                                this.filters.storage = opt.value;
                                matched = true;
                                break;
                            }
                        }
                        if (!matched) {
                            this.filters.storage = this.pendingUrlFilters.storage;
                        }
                    } else {
                        this.filters.storage = this.pendingUrlFilters.storage;
                    }
                }
            },

            async downloadExcel() {
                this.isDownloadModalOpen = false;
                const tanggal_awal = this.downloadFilters.tanggal_awal;
                const tanggal_akhir = this.downloadFilters.tanggal_akhir;

                if (!tanggal_awal || !tanggal_akhir) {
                    alert('Mohon pilih tanggal awal dan tanggal akhir.');
                    return;
                }

                try {
                    this.loading = true;
                    const queryParams = `action=downloadExcel&tanggal_awal=${tanggal_awal}&tanggal_akhir=${tanggal_akhir}`;
                    const response = await fetch(`./controller/transaksi/index.php?${queryParams}`);

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const responseData = await response.json();
                    const data = responseData.data;

                    if (data.length === 0) {
                        alert('Tidak ada data yang ditemukan untuk rentang tanggal tersebut.');
                        return;
                    }

                    this.exportToExcel(data, tanggal_awal, tanggal_akhir);

                } catch (error) {
                    this.errorMessage = error.message;
                    console.error('Error fetching data for download:', error);
                } finally {
                    this.loading = false;
                }
            },

            exportToExcel(data, startDate, endDate) {
                const workbook = XLSX.utils.book_new();

                const tableHeaders = [
                    ['No.', 'Tanggal', 'Barcode', 'Info Produksi', 'Item', 'Qty (Ekor/Pack)', 'Berat (Kg)', 'Stok Ekor', 'Stok Berat', 'Lokasi Asal', 'Lokasi Tujuan', 'Penyimpanan', 'Tipe Transaksi', 'Keterangan', 'User', 'Dibuat Pada'],
                ];

                const tableData = data.map((item, index) => [
                    index + 1,
                    item.date,
                    item.barcode || '-',
                    item.info_produksi || '-',
                    item.item,
                    item.ekor_pack,
                    parseFloat(item.quantity_kg).toFixed(2),
                    parseFloat(item.stock_ekor).toFixed(0),
                    parseFloat(item.stock_berat).toFixed(2),
                    item.from_location,
                    item.to_location,
                    item.storage,
                    item.transaction_type,
                    item.keterangan || '-',
                    item.user,
                    item.created_at,
                ]);

                const combinedData = [
                    [`DATA TRANSAKSI`],
                    [`Periode: ${startDate} s/d ${endDate}`],
                    [],
                    ...tableHeaders,
                    ...tableData,
                ];

                const dataWs = XLSX.utils.aoa_to_sheet(combinedData);

                dataWs['!merges'] = [{
                        s: {
                            r: 0,
                            c: 0
                        },
                        e: {
                            r: 0,
                            c: tableHeaders[0].length - 1
                        }
                    },
                    {
                        s: {
                            r: 1,
                            c: 0
                        },
                        e: {
                            r: 1,
                            c: tableHeaders[0].length - 1
                        }
                    },
                ];

                const columnWidths = [{
                        wch: 5
                    }, {
                        wch: 12
                    }, {
                        wch: 15
                    }, {
                        wch: 25
                    },
                    {
                        wch: 30
                    }, {
                        wch: 12
                    }, {
                        wch: 12
                    }, {
                        wch: 12
                    },
                    {
                        wch: 12
                    }, {
                        wch: 12
                    }, {
                        wch: 12
                    }, {
                        wch: 12
                    },
                    {
                        wch: 15
                    }, {
                        wch: 30
                    }, {
                        wch: 15
                    }, {
                        wch: 20
                    }
                ];
                dataWs['!cols'] = columnWidths;

                const range = XLSX.utils.decode_range(dataWs['!ref']);
                for (let R = range.s.r; R <= range.e.r; ++R) {
                    for (let C = range.s.c; C <= range.e.c; ++C) {
                        const cellRef = XLSX.utils.encode_cell({
                            r: R,
                            c: C
                        });
                        if (dataWs[cellRef] && dataWs[cellRef].v) {
                            if (R < 3) {
                                dataWs[cellRef].s = {
                                    alignment: {
                                        horizontal: 'center'
                                    }
                                };
                            } else {
                                dataWs[cellRef].s = {
                                    alignment: {
                                        horizontal: 'left'
                                    }
                                };
                            }
                        }
                    }
                }

                XLSX.utils.book_append_sheet(workbook, dataWs, 'Data Transaksi');
                XLSX.writeFile(workbook, `Data_Transaksi_${startDate}_${endDate}.xlsx`);
            },

            get visibleTransactions() {
                const activeFilterColumns = Object.keys(this.activeFilters).filter(column => this.activeFilters[column] && this.activeFilters[column].length > 0);

                if (activeFilterColumns.length === 0) {
                    return this.transactions;
                }

                return this.transactions.filter(transaction => {
                    return activeFilterColumns.every(column => {
                        const transactionValue = String(transaction[column] || '');
                        return this.activeFilters[column].includes(transactionValue);
                    });
                });
            },

            get totals() {
                return this.visibleTransactions.reduce((acc, transaction) => {
                    acc.ekor_pack += parseFloat(transaction.ekor_pack || 0);
                    acc.quantity_kg += parseFloat(transaction.quantity_kg || 0);
                    return acc;
                }, {
                    ekor_pack: 0,
                    quantity_kg: 0
                });
            },

            getUniqueValues(column) {
                if (this.distinctData && this.distinctData[column]) {
                    return this.distinctData[column].map(value => String(value || ''));
                }
                const values = new Set(this.transactions.map(t => String(t[column] || '')));
                return Array.from(values).sort();
            },

            toggleFilter(column, value) {
                if (!this.activeFilters[column]) {
                    this.activeFilters[column] = [];
                }

                const index = this.activeFilters[column].indexOf(value);
                if (index > -1) {
                    this.activeFilters[column].splice(index, 1);
                } else {
                    this.activeFilters[column].push(value);
                }

                this.calculateTotals();
            },

            resetFilter(column) {
                if (this.activeFilters[column]) {
                    this.activeFilters[column] = [];
                }
                this.calculateTotals();
            },

            isFiltered(column, value) {
                return this.activeFilters[column] && this.activeFilters[column].includes(value);
            },

            async fetchTransactions() {
                this.loading = true;
                this.errorMessage = '';
                try {
                    const queryParams = new URLSearchParams({
                        ...this.filters,
                        item: Array.isArray(this.filters.item) ? this.filters.item.join(',') : this.filters.item,
                        subitem: Array.isArray(this.filters.subitem) ? this.filters.subitem.join(',') : this.filters.subitem
                    }).toString();

                    const response = await fetch(`./controller/transaksi/index.php?action=getTransactions&${queryParams}`);
                    if (!response.ok) {
                        const errorText = await response.text();
                        throw new Error(`Failed to fetch transactions: ${errorText}`);
                    }
                    const data = await response.json();

                    if (data.success) {
                        this.transactions = data.data;
                        this.distinctData = data.distinct_data;
                        this.no_barcode_in_count = data.no_barcode_in_count;
                        this.no_barcode_out_count = data.no_barcode_out_count;
                        this.no_barcode_total_count = data.no_barcode_total_count;

                        this.calculateTotals();
                    } else {
                        this.errorMessage = data.message || 'Gagal memuat data transaksi.';
                    }
                } catch (error) {
                    console.error('Error fetching transactions:', error);
                    this.errorMessage = error.message || 'Terjadi kesalahan saat mengambil data transaksi.';
                } finally {
                    this.loading = false;
                }
            },

            async initData() {
                // 1. Ambil semua parameter URL
                this.loadFiltersFromUrl();

                // 2. Ambil master option lokasi, user, item
                await this.fetchFilterOptions();
                await this.$nextTick();

                // 3. Pasang nilai lokasi & storage yang sesuai dengan option yang sudah ada
                this.applyUrlFiltersToState();

                // 4. Inisialisasi Select2
                this.initItemSelect2();
                this.initSubitemSelect2();

                // 5. Pasang item & subitem dari URL ke Select2
                if (this.filters.item && this.filters.item.length > 0) {
                    const $itemSelect = $(this.$refs.filterItem);
                    this.filters.item.forEach(itm => {
                        if ($itemSelect.find(`option[value="${itm}"]`).length === 0) {
                            $itemSelect.append(new Option(itm, itm, true, true));
                        }
                    });
                    $itemSelect.val(this.filters.item).trigger('change.select2');
                }

                if (this.filters.subitem && this.filters.subitem.length > 0) {
                    const $subitemSelect = $(this.$refs.filterSubitem);
                    this.filters.subitem.forEach(sub => {
                        if ($subitemSelect.find(`option[value="${sub}"]`).length === 0) {
                            $subitemSelect.append(new Option(sub, sub, true, true));
                        }
                    });
                    $subitemSelect.val(this.filters.subitem).trigger('change.select2');
                }

                // 6. Jalankan pengambilan data langsung
                this.fetchTransactions();
                this.toggleHiddenColumns();
            },

            initItemSelect2() {
                $(this.$refs.filterItem).select2({
                    placeholder: 'Pilih Item (bisa multiple)',
                    allowClear: true,
                    multiple: true,
                    minimumInputLength: 0,
                    width: '100%',
                    ajax: {
                        url: './controller/transaksi/index.php?action=searchItems',
                        dataType: 'json',
                        delay: 500,
                        data: function(params) {
                            return {
                                q: params.term,
                                page: params.page || 1
                            };
                        },
                        processResults: function(data, params) {
                            params.page = params.page || 1;
                            return {
                                results: data.results,
                                pagination: {
                                    more: data.pagination.more
                                }
                            };
                        },
                        cache: true
                    },
                    escapeMarkup: function(markup) {
                        return markup;
                    },
                    templateResult: function(item) {
                        if (item.loading) return item.text;
                        return item.text;
                    },
                    templateSelection: function(item) {
                        return item.text;
                    }
                }).on('change', () => {
                    this.filters.item = $(this.$refs.filterItem).val() || [];
                    this.fetchTransactions();
                });
            },

            initSubitemSelect2() {
                const $filterSubitemSelect = $(this.$refs.filterSubitem);

                if ($filterSubitemSelect.hasClass('select2-hidden-accessible')) {
                    $filterSubitemSelect.select2('destroy');
                }

                $filterSubitemSelect.select2({
                    placeholder: 'Pilih Subitem (bisa multiple)',
                    allowClear: true,
                    multiple: true,
                    minimumInputLength: 0,
                    width: '100%',
                    ajax: {
                        url: './controller/transaksi/index.php?action=searchSubitems',
                        dataType: 'json',
                        delay: 500,
                        data: function(params) {
                            return {
                                q: params.term,
                                page: params.page || 1
                            };
                        },
                        processResults: function(data, params) {
                            params.page = params.page || 1;
                            let results = data.results;

                            if (!results.some(s => s.id === '__NO_SUBITEM__')) {
                                results.unshift({
                                    id: '__NO_SUBITEM__',
                                    text: 'Tanpa Subitem'
                                });
                            }

                            return {
                                results: results,
                                pagination: {
                                    more: data.pagination.more
                                }
                            };
                        },
                        cache: true
                    },
                    escapeMarkup: function(markup) {
                        return markup;
                    },
                    templateResult: function(subitem) {
                        if (subitem.loading) return subitem.text;
                        return subitem.text;
                    },
                    templateSelection: function(subitem) {
                        return subitem.text;
                    }
                }).on('change', () => {
                    let selectedSubitems = $filterSubitemSelect.val() || [];

                    if (selectedSubitems && selectedSubitems.includes('__NO_SUBITEM__') && selectedSubitems.length > 1) {
                        $filterSubitemSelect.val('__NO_SUBITEM__').trigger('change.select2');
                        this.filters.subitem = ['__NO_SUBITEM__'];
                        Swal.fire('Info', 'Anda tidak dapat memilih "Tanpa Subitem" bersama dengan subitem lainnya.', 'info');
                    } else if (selectedSubitems && selectedSubitems.length > 0 && selectedSubitems.includes('')) {
                        const indexAll = selectedSubitems.indexOf('');
                        if (indexAll > -1) {
                            selectedSubitems.splice(indexAll, 1);
                            $filterSubitemSelect.val(selectedSubitems).trigger('change.select2');
                        }
                        this.filters.subitem = selectedSubitems;
                    } else {
                        this.filters.subitem = selectedSubitems;
                    }

                    this.fetchTransactions();
                });
            },

            formatNumber(value, decimals = 0) {
                if (value === null || value === undefined) return '0';
                const num = parseFloat(value);
                if (isNaN(num)) return '0';

                const formatted = num.toFixed(decimals);
                if (num > 0) return `${formatted}`;
                if (num < 0) return formatted;
                return formatted;
            },

            toggleHiddenColumns() {
                const hiddenColumns = document.querySelectorAll('.hidden-column');
                hiddenColumns.forEach(col => {
                    col.style.display = this.showHiddenColumns ? '' : 'none';
                });

                const emptyColspan = this.showHiddenColumns ? 11 : 9;
                document.querySelectorAll('tr td[colspan]').forEach(td => {
                    td.setAttribute('colspan', emptyColspan);
                });
            },

            async fetchFilterOptions() {
                try {
                    const [usersRes, locationsRes, itemsRes, subitemsRes] = await Promise.all([
                        fetch('./controller/transaksi/index.php?action=getUsers'),
                        fetch('./controller/transaksi/index.php?action=getLocations'),
                        fetch('./controller/transaksi/index.php?action=getAllItems'),
                        fetch('./controller/transaksi/index.php?action=getAllSubitems')
                    ]);

                    const [usersData, locationsData, itemsData, subitemsData] = await Promise.all([
                        usersRes.json(),
                        locationsRes.json(),
                        itemsRes.json(),
                        subitemsRes.json()
                    ]);

                    this.filterOptions.users = usersData;
                    this.filterOptions.locations = locationsData;
                    this.filterOptions.items = itemsData;
                    this.filterOptions.subitems = subitemsData;

                } catch (error) {
                    console.error('Error fetching filter options:', error);
                    this.errorMessage = 'Gagal memuat opsi filter.';
                }
            },

            calculateTotals() {
                this.totals = {
                    ekor_pack: 0,
                    quantity_kg: 0
                };

                this.transactions.forEach(transaction => {
                    this.totals.ekor_pack += parseFloat(transaction.ekor_pack);
                    this.totals.quantity_kg += parseFloat(transaction.quantity_kg);
                });
            },

            formatTransactionType(type) {
                switch (type) {
                    case 'in':
                        return 'Masuk';
                    case 'out':
                        return 'Keluar';
                    case 'transfer-in':
                        return 'Transfer Masuk';
                    case 'transfer-out':
                        return 'Transfer Keluar';
                    case 'transfer':
                        return 'Transfer';
                    case 'adjustment':
                        return 'Adjustment';
                    default:
                        return type;
                }
            },

            formatDateTime(timestamp) {
                if (!timestamp) return '-';
                const date = new Date(timestamp);
                return date.toLocaleString('id-ID', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            },

            resetFilters() {
                this.filters = {
                    tanggal_awal: new Date().toISOString().slice(0, 10),
                    tanggal_akhir: new Date().toISOString().slice(0, 10),
                    type_transaksi: '',
                    user_name: '',
                    from_location: '',
                    to_location: '',
                    storage: '',
                    item: [],
                    subitem: [],
                    search: ''
                };
                $(this.$refs.filterItem).val(null).trigger('change.select2');
                $(this.$refs.filterSubitem).val(null).trigger('change.select2');
                this.fetchTransactions();
            },

            async openModal(transactionId) {
                this.isModalOpen = true;
                this.isEditing = false;
                this.selectedTransaction = null;

                try {
                    const response = await fetch(`./controller/transaksi/index.php?action=getTransactionDetail&id=${transactionId}`);
                    if (!response.ok) throw new Error('Failed to fetch transaction detail');
                    const data = await response.json();

                    if (data.success) {
                        this.selectedTransaction = data.data;

                        let itemRaw = data.data.item;
                        let subitemRaw = '';
                        if (data.data.item && data.data.item.includes(' : ')) {
                            [itemRaw, subitemRaw] = data.data.item.split(' : ', 2);
                        }

                        this.editableTransaction = {
                            id: data.data.id,
                            date: data.data.date,
                            from_location: data.data.from_location,
                            to_location: data.data.to_location,
                            item_raw: itemRaw,
                            subitem_raw: subitemRaw,
                            ekor_pack: Math.abs(parseFloat(data.data.ekor_pack)),
                            quantity_kg: Math.abs(parseFloat(data.data.quantity_kg)),
                            keterangan: data.data.keterangan,
                            barcode: data.data.barcode,
                            original_transaction_type: data.data.transaction_type
                        };

                        this.$nextTick(() => {
                            this.initModalSelect2(itemRaw, subitemRaw);
                        });
                    }
                } catch (error) {
                    console.error('Error opening modal:', error);
                    Swal.fire('Error', 'Gagal memuat detail transaksi', 'error');
                    this.closeModal();
                }
            },

            initModalSelect2(initialItem, initialSubitem) {
                if ($(this.$refs.editItemSelect).hasClass("select2-hidden-accessible")) {
                    $(this.$refs.editItemSelect).select2('destroy');
                }
                if ($(this.$refs.editSubitemSelect).hasClass("select2-hidden-accessible")) {
                    $(this.$refs.editSubitemSelect).select2('destroy');
                }

                $(this.$refs.editItemSelect).select2({
                    placeholder: 'Pilih Item',
                    allowClear: true,
                    dropdownParent: $(this.$refs.editItemSelect).parent(),
                    minimumInputLength: 0,
                    width: '100%',
                    ajax: {
                        url: './controller/transaksi/index.php?action=searchItems',
                        dataType: 'json',
                        delay: 500,
                        data: function(params) {
                            return {
                                q: params.term,
                                page: params.page || 1
                            };
                        },
                        processResults: function(data, params) {
                            params.page = params.page || 1;
                            return {
                                results: data.results.map(item => ({
                                    id: item.text,
                                    text: item.text
                                })),
                                pagination: {
                                    more: data.pagination.more
                                }
                            };
                        },
                        cache: true
                    }
                });

                if (initialItem) {
                    const itemOption = new Option(initialItem, initialItem, true, true);
                    $(this.$refs.editItemSelect).append(itemOption).trigger('change');
                }

                $(this.$refs.editSubitemSelect).select2({
                    placeholder: 'Pilih Subitem',
                    allowClear: true,
                    dropdownParent: $(this.$refs.editSubitemSelect).parent(),
                    minimumInputLength: 0,
                    width: '100%',
                    ajax: {
                        url: './controller/transaksi/index.php?action=searchSubitems',
                        dataType: 'json',
                        delay: 500,
                        data: function(params) {
                            return {
                                q: params.term,
                                page: params.page || 1
                            };
                        },
                        processResults: function(data, params) {
                            params.page = params.page || 1;
                            return {
                                results: data.results.map(subitem => ({
                                    id: subitem.text,
                                    text: subitem.text
                                })),
                                pagination: {
                                    more: data.pagination.more
                                }
                            };
                        },
                        cache: true
                    }
                });

                if (initialSubitem) {
                    setTimeout(() => {
                        const subitemOption = new Option(initialSubitem, initialSubitem, true, true);
                        $(this.$refs.editSubitemSelect).append(subitemOption).trigger('change');
                    }, 300);
                }

                $(this.$refs.editItemSelect).on('change', () => {
                    this.editableTransaction.item_raw = $(this.$refs.editItemSelect).val();
                    this.updateSubitemEditOptions();
                });

                $(this.$refs.editSubitemSelect).on('change', () => {
                    this.editableTransaction.subitem_raw = $(this.$refs.editSubitemSelect).val();
                });

                this.updateSubitemEditOptions();
            },

            async updateSubitemEditOptions() {
                if (this.editableTransaction.item_raw) {
                    this.isSubitemEditDisabled = false;
                    $(this.$refs.editSubitemSelect).prop('disabled', false);
                } else {
                    this.isSubitemEditDisabled = true;
                    $(this.$refs.editSubitemSelect).prop('disabled', true);
                    $(this.$refs.editSubitemSelect).val(null).trigger('change');
                    this.editableTransaction.subitem_raw = '';
                }
            },

            async handleLocationChange(type) {
                const fromChiller = this.chillerLocations.includes(this.editableTransaction.from_location);
                const toChiller = this.chillerLocations.includes(this.editableTransaction.to_location);

                this.showStockInfo = fromChiller && !toChiller;

                if (this.showStockInfo && this.editableTransaction.from_location && this.editableTransaction.item_raw) {
                    try {
                        const response = await fetch(`./controller/transaksi/index.php?action=getItemStock&location=${encodeURIComponent(this.editableTransaction.from_location)}&item=${encodeURIComponent(this.editableTransaction.item_raw)}`);
                        if (response.ok) {
                            this.currentStock = await response.json();
                        }
                    } catch (error) {
                        console.error("Failed to fetch current stock:", error);
                        this.currentStock = {
                            ekor_pack: 0,
                            quantity_kg: 0
                        };
                    }
                }
            },

            cancelEdit() {
                this.isEditing = false;
                if (this.selectedTransaction) {
                    let itemRaw = this.selectedTransaction.item;
                    let subitemRaw = '';
                    if (this.selectedTransaction.item && this.selectedTransaction.item.includes(' : ')) {
                        [itemRaw, subitemRaw] = this.selectedTransaction.item.split(' : ', 2);
                    }
                    this.editableTransaction = {
                        id: this.selectedTransaction.id,
                        date: this.selectedTransaction.date,
                        from_location: this.selectedTransaction.from_location,
                        to_location: this.selectedTransaction.to_location,
                        item_raw: itemRaw,
                        subitem_raw: subitemRaw,
                        ekor_pack: Math.abs(parseFloat(this.selectedTransaction.ekor_pack)),
                        quantity_kg: Math.abs(parseFloat(this.selectedTransaction.quantity_kg)),
                        keterangan: this.selectedTransaction.keterangan,
                        barcode: this.selectedTransaction.barcode
                    };

                    $(this.$refs.editItemSelect).val(itemRaw).trigger('change');
                    setTimeout(() => {
                        $(this.$refs.editSubitemSelect).val(subitemRaw).trigger('change');
                    }, 300);
                }
            },

            async saveTransaction() {
                this.saving = true;
                this.errorMessage = '';

                if (!this.editableTransaction.item_raw) {
                    Swal.fire('Error', 'Item harus diisi', 'error');
                    this.saving = false;
                    return;
                }

                const fromChiller = this.chillerLocations.includes(this.editableTransaction.from_location);
                const toChiller = this.chillerLocations.includes(this.editableTransaction.to_location);

                let transactionType = '';
                let storageLocation = '';

                if (toChiller && !fromChiller) {
                    transactionType = 'in';
                    storageLocation = this.editableTransaction.to_location;
                } else if (fromChiller && !toChiller) {
                    transactionType = 'out';
                    storageLocation = this.editableTransaction.from_location;
                } else if (fromChiller && toChiller) {
                    transactionType = 'transfer-out';
                    storageLocation = this.editableTransaction.from_location;
                } else {
                    Swal.fire('Validasi', 'Jenis transaksi tidak valid. Pilih lokasi dari dan/atau tujuan yang sesuai.', 'warning');
                    this.saving = false;
                    return;
                }

                const dataToSend = {
                    id: this.editableTransaction.id,
                    date: this.editableTransaction.date,
                    from_location: this.editableTransaction.from_location,
                    to_location: this.editableTransaction.to_location,
                    item: this.editableTransaction.item_raw,
                    subitem: this.editableTransaction.subitem_raw,
                    ekor_pack: this.editableTransaction.ekor_pack,
                    quantity_kg: this.editableTransaction.quantity_kg,
                    keterangan: this.editableTransaction.keterangan,
                    user: '<?php echo isset($_SESSION["user_nama"]) ? $_SESSION["user_nama"] : "Guest"; ?>',
                    transaction_type_frontend: transactionType,
                    storage: storageLocation,
                    barcode: this.editableTransaction.barcode
                };

                try {
                    const response = await fetch('./controller/transaksi/index.php?action=updateTransaction', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(dataToSend)
                    });

                    const result = await response.json();

                    if (result.success) {
                        Swal.fire('Berhasil!', result.message, 'success').then(() => {
                            this.isModalOpen = false;
                            this.isEditing = false;
                            this.fetchTransactions();
                        });
                    } else {
                        Swal.fire('Gagal!', result.message || 'Terjadi kesalahan saat menyimpan perubahan.', 'error');
                    }
                } catch (error) {
                    console.error('Error saving transaction:', error);
                    Swal.fire('Error!', 'Terjadi kesalahan jaringan atau server saat menyimpan perubahan.', 'error');
                } finally {
                    this.saving = false;
                }
            },

            toggleEditMode() {
                const userRole = '<?php echo isset($_SESSION["user_role"]) ? $_SESSION["user_role"] : "guest"; ?>';
                const today = new Date();
                const todayString = today.toISOString().slice(0, 10);

                const yesterday = new Date(today);
                yesterday.setDate(today.getDate() - 1);
                const yesterdayString = yesterday.toISOString().slice(0, 10);

                const transactionDate = this.selectedTransaction.date;

                if (!this.isEditing) {
                    this.editableTransaction = {
                        ...this.selectedTransaction
                    };
                    this.editableTransaction.barcode = String(this.editableTransaction.barcode || '');

                    if (this.editableTransaction.item) {
                        const parts = this.editableTransaction.item.split(':').map(part => part.trim());
                        this.editableTransaction.item_raw = parts[0] || '';
                        this.editableTransaction.subitem_raw = parts[1] || '';
                    } else {
                        this.editableTransaction.item_raw = '';
                        this.editableTransaction.subitem_raw = '';
                    }

                    this.updateSubitemEditOptions();
                }

                if (userRole === 'superadmin') {
                    this.isEditing = !this.isEditing;
                    if (this.isEditing) {
                        this.$nextTick(() => {
                            this.initModalSelect2();
                            this.handleLocationChange();
                        });
                    }
                    return;
                }

                if (this.selectedTransaction.transaction_type && (this.selectedTransaction.transaction_type.includes('transfer') || this.selectedTransaction.transaction_type === 'adjustment')) {
                    Swal.fire('Info', 'Transaksi transfer/adjustment tidak dapat diedit. Silakan hubungi administrator.', 'info');
                    return;
                }

                if (transactionDate !== todayString && transactionDate !== yesterdayString) {
                    Swal.fire('Info', 'Transaksi hanya dapat diedit untuk tanggal hari yang sama atau H-1. Silakan hubungi administrator.', 'info');
                    return;
                }

                this.isEditing = !this.isEditing;
                if (this.isEditing) {
                    this.$nextTick(() => {
                        this.initModalSelect2();
                        this.handleLocationChange();
                    });
                }
            },

            confirmDelete(id, itemDisplay, transactionType, transactionDate) {
                const userRole = '<?php echo isset($_SESSION["user_role"]) ? $_SESSION["user_role"] : "guest"; ?>';
                const today = new Date();
                const todayString = today.toISOString().slice(0, 10);

                const yesterday = new Date(today);
                yesterday.setDate(today.getDate() - 1);
                const yesterdayString = yesterday.toISOString().slice(0, 10);

                if (userRole === 'superadmin') {
                    Swal.fire({
                        title: 'Anda yakin?',
                        text: `Anda akan menghapus transaksi item "${itemDisplay}". Tindakan ini tidak dapat dibatalkan.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Ya, hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.deleteTransaction(id);
                        }
                    });
                    return;
                }

                if (transactionType.includes('transfer') || transactionType === 'adjustment') {
                    Swal.fire('Info', 'Transaksi transfer/adjustment tidak dapat dihapus. Silakan hubungi administrator.', 'info');
                    return;
                }

                if (transactionDate !== todayString && transactionDate !== yesterdayString) {
                    Swal.fire('Info', 'Transaksi hanya dapat dihapus untuk tanggal hari yang sama atau H-1. Silakan hubungi administrator.', 'info');
                    return;
                }

                Swal.fire({
                    title: 'Anda yakin?',
                    text: `Anda akan menghapus transaksi item "${itemDisplay}". Tindakan ini tidak dapat dibatalkan.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.deleteTransaction(id);
                    }
                });
            },

            async deleteTransaction(id) {
                try {
                    const response = await fetch('./controller/transaksi/index.php?action=deleteTransaction', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id: id
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        Swal.fire('Dihapus!', result.message, 'success').then(() => {
                            this.isModalOpen = false;
                            this.fetchTransactions();
                        });
                    } else {
                        Swal.fire('Gagal!', result.message || 'Transaksi tidak ditemukan atau sudah dihapus.', 'error');
                    }
                } catch (error) {
                    console.error('Error deleting transaction:', error);
                    Swal.fire('Error!', 'Terjadi kesalahan jaringan atau server saat menghapus transaksi.', 'error');
                }
            },

            closeModal() {
                this.isModalOpen = false;
                this.isEditing = false;
                this.selectedTransaction = null;
                if ($(this.$refs.editItemSelect).hasClass('select2-hidden-accessible')) {
                    $(this.$refs.editItemSelect).select2('destroy');
                }
            }
        }));
    });
</script>