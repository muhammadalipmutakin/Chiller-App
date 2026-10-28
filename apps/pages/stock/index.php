<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<main class="flex-1 min-w-0 transition-all duration-300" x-data="chillerStockApp()">
    <h1 class="text-xl font-bold mb-4 text-gray-800">Data Stock Chiller</h1>

    <?php include 'sections/filter.php' ?>

    <!-- Desktop Table (hidden on mobile) -->
    <div class="hidden md:block">
        <?php include 'sections/table.php' ?>
    </div>

    <!-- Mobile Cards (hidden on desktop) -->
    <div class="block md:hidden" x-data="mobileStockCards()">
        <?php include 'sections/mobile-cards.php' ?>
    </div>

</main>

<script>
    function chillerStockApp() {
        return {
            startDate: '',
            endDate: '',
            selectedStorage: '',
            selectedItems: [],
            selectedSubitems: [],
            stockFilter: 'all',
            hideZeroStock: false,
            filterOptions: {
                locations: ['Semua', 'Chiller 1', 'Chiller 2', 'Chiller 3'],
                items: [],
                subitems: []
            },
            allStockData: [],
            stockData: [],
            loading: true,
            totals: {
                initial_ep: 0,
                initial_kg: 0,
                in_ep: 0,
                in_kg: 0,
                out_ep: 0,
                out_kg: 0,
                stock_end_before_adj_ep: 0,
                stock_end_before_adj_kg: 0,
                adjustment_ep: 0,
                adjustment_kg: 0,
                stock_after_adj_ep: 0,
                stock_after_adj_kg: 0,
            },

            init() {
                this.setDefaultDates();

                this.fetchFullStockData().then(() => {
                    this.initSelect2();
                    this.applyFilters();
                }).finally(() => {
                    this.loading = false;
                });

                this.$watch('startDate', () => this.handleDateChange());
                this.$watch('endDate', () => this.handleDateChange());

                this.$watch('selectedStorage', () => this.applyFilters());

                $('#itemFilter').on('change', (e) => {
                    this.selectedItems = $(e.target).val() || [];
                    this.applyFilters();
                });

                $('#subitemFilter').on('change', (e) => {
                    const currentSelection = $(e.target).val() || [];
                    const noSubitemValue = '__NO_SUBITEM__';

                    if (currentSelection.includes(noSubitemValue) && currentSelection.length > 1) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Peringatan!',
                            text: 'Tidak dapat memilih "Tanpa Subitem" bersamaan dengan subitem lainnya.',
                            confirmButtonText: 'Oke'
                        });
                        $(e.target).val([noSubitemValue]).trigger('change.select2');
                        this.selectedSubitems = [noSubitemValue];
                    } else {
                        this.selectedSubitems = currentSelection;
                    }
                    this.applyFilters();
                });

                this.$watch('stockFilter', () => this.applyFilters());
                this.$watch('hideZeroStock', () => this.applyFilters());

                this.$watch('stockData', () => this.calculateTotals());
            },

            setDefaultDates() {
                const today = new Date();
                this.endDate = today.toISOString().slice(0, 10);
                const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                this.startDate = firstDayOfMonth.toISOString().slice(0, 10);
            },

            async fetchFullStockData() {
                this.loading = true;
                try {
                    const params = new URLSearchParams({
                        action: 'get_full_stock_data',
                        startDate: this.startDate,
                        endDate: this.endDate,
                    });

                    const response = await fetch(`./controller/stock/index.php?${params.toString()}`);
                    const result = await response.json();

                    if (result.status === 'success') {
                        this.allStockData = result.data.stock_data;
                        this.filterOptions.items = result.data.items_for_filter;
                        this.filterOptions.subitems = [{
                            id: '__NO_SUBITEM__',
                            text: 'Tanpa Subitem'
                        }, ...result.data.subitems_for_filter.map(subitem => ({
                            id: subitem,
                            text: subitem
                        }))];
                    } else {
                        Swal.fire('Error', 'Gagal memuat data awal: ' + result.message, 'error');
                        this.allStockData = [];
                        this.filterOptions.items = [];
                        this.filterOptions.subitems = [];
                    }
                } catch (error) {
                    Swal.fire('Error', 'Terjadi kesalahan saat memuat data awal: ' + error.message, 'error');
                    this.allStockData = [];
                    this.filterOptions.items = [];
                    this.filterOptions.subitems = [];
                } finally {
                    this.loading = false;
                }
            },

            applyFilters() {
                let filteredData = [...this.allStockData];

                if (this.selectedStorage && this.selectedStorage !== 'Semua') {
                    filteredData = filteredData.filter(item => item.storage === this.selectedStorage);
                }

                if (this.selectedItems.length > 0) {
                    filteredData = filteredData.filter(item => {
                        return this.selectedItems.includes(item.item_name_parsed);
                    });
                }

                if (this.selectedSubitems.length > 0) {
                    const noSubitemSelected = this.selectedSubitems.includes('__NO_SUBITEM__');
                    if (noSubitemSelected) {
                        filteredData = filteredData.filter(item => {
                            return item.subitem_name_parsed === '';
                        });
                    } else {
                        filteredData = filteredData.filter(item => {
                            return this.selectedSubitems.includes(item.subitem_name_parsed);
                        });
                    }
                }

                if (this.hideZeroStock) {
                    filteredData = filteredData.filter(item => {
                        return parseFloat(item.stock_after_adj_ep) !== 0 || parseFloat(item.stock_after_adj_kg) !== 0;
                    });
                }

                if (this.stockFilter !== 'all') {
                    filteredData = filteredData.filter(item => {
                        const finalStock = parseFloat(item.stock_after_adj_kg);
                        if (this.stockFilter === 'positive') {
                            return finalStock > 0;
                        } else if (this.stockFilter === 'negative') {
                            return finalStock < 0;
                        } else if (this.stockFilter === 'zero') {
                            return finalStock === 0;
                        }
                    });
                }

                this.stockData = filteredData;
            },

            async handleDateChange() {
                if (this.startDate && this.endDate && new Date(this.startDate) > new Date(this.endDate)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Tanggal akhir tidak boleh lebih kecil dari tanggal awal!',
                    });
                    this.allStockData = [];
                    this.stockData = [];
                    this.loading = false;
                    return;
                }
                await this.fetchFullStockData();
                this.initSelect2();
                this.applyFilters();
            },

            initSelect2() {
                if ($('#itemFilter').data('select2')) {
                    $('#itemFilter').select2('destroy');
                }
                if ($('#subitemFilter').data('select2')) {
                    $('#subitemFilter').select2('destroy');
                }

                $('#itemFilter').select2({
                    placeholder: 'Pilih Item',
                    data: this.filterOptions.items.map(item => ({
                        id: item,
                        text: item
                    })),
                    maximumSelectionLength: 10,
                    tags: false,
                });

                $('#subitemFilter').select2({
                    placeholder: 'Pilih Subitem',
                    data: this.filterOptions.subitems,
                    maximumSelectionLength: 10,
                    tags: false,
                });

                $('#itemFilter').val(this.selectedItems).trigger('change');
                $('#subitemFilter').val(this.selectedSubitems).trigger('change');
            },

            calculateTotals() {
                this.totals = {
                    initial_ep: 0,
                    initial_kg: 0,
                    in_ep: 0,
                    in_kg: 0,
                    out_ep: 0,
                    out_kg: 0,
                    stock_end_before_adj_ep: 0,
                    stock_end_before_adj_kg: 0,
                    adjustment_ep: 0,
                    adjustment_kg: 0,
                    stock_after_adj_ep: 0,
                    stock_after_adj_kg: 0,
                };

                this.stockData.forEach(item => {
                    this.totals.initial_ep += parseFloat(item.initial_ep) || 0;
                    this.totals.initial_kg += parseFloat(item.initial_kg) || 0;
                    this.totals.in_ep += parseFloat(item.in_ep) || 0;
                    this.totals.in_kg += parseFloat(item.in_kg) || 0;
                    this.totals.out_ep += parseFloat(item.out_ep) || 0;
                    this.totals.out_kg += parseFloat(item.out_kg) || 0;
                    this.totals.stock_end_before_adj_ep += parseFloat(item.stock_end_before_adj_ep) || 0;
                    this.totals.stock_end_before_adj_kg += parseFloat(item.stock_end_before_adj_kg) || 0;
                    this.totals.adjustment_ep += parseFloat(item.adjustment_ep) || 0;
                    this.totals.adjustment_kg += parseFloat(item.adjustment_kg) || 0;
                    this.totals.stock_after_adj_ep += parseFloat(item.stock_after_adj_ep) || 0;
                    this.totals.stock_after_adj_kg += parseFloat(item.stock_after_adj_kg) || 0;
                });
            },

            resetFilters() {
                this.setDefaultDates();

                this.selectedStorage = '';
                this.stockFilter = 'all';
                this.hideZeroStock = false;

                $('#itemFilter').val(null).trigger('change');
                $('#subitemFilter').val(null).trigger('change');
                this.selectedItems = [];
                this.selectedSubitems = [];

                this.fetchFullStockData().then(() => {
                    this.applyFilters();
                });
            },

            openTracingPage(item, storage) {
                const url = `?page=tracing&item=${encodeURIComponent(item)}&storage=${encodeURIComponent(storage)}`;
                window.open(url, '_blank');
            },

            exportToExcel() {
                const workbook = XLSX.utils.book_new();

                const chillerHeader = this.selectedStorage && this.selectedStorage !== 'Semua' ?
                    `STOCK CHILLER: ${this.selectedStorage.toUpperCase()}` :
                    `STOCK CHILLER: SEMUA CHILLER`;
                const periodHeader = `PERIODE: ${this.startDate} s/d ${this.endDate}`;

                const tableHeaders = [
                    ['No.', 'Item', 'Stok Awal', null, 'Masuk', null, 'Keluar', null, 'Stok Akhir (Sebelum Adj)', null, 'Adjustment', null, 'Stok Saat Ini', null],
                    [null, null, 'E/P', 'Kg', 'E/P', 'Kg', 'E/P', 'Kg', 'E/P', 'Kg', 'E/P', 'Kg', 'E/P', 'Kg']
                ];

                const tableData = this.stockData.map((item, index) => [
                    String(index + 1),
                    item.item,
                    String(item.initial_ep),
                    parseFloat(item.initial_kg).toFixed(2),
                    String(item.in_ep),
                    parseFloat(item.in_kg).toFixed(2),
                    String(item.out_ep),
                    parseFloat(item.out_kg).toFixed(2),
                    String(item.stock_end_before_adj_ep),
                    parseFloat(item.stock_end_before_adj_kg).toFixed(2),
                    String(item.adjustment_ep),
                    parseFloat(item.adjustment_kg).toFixed(2),
                    String(item.stock_after_adj_ep),
                    parseFloat(item.stock_after_adj_kg).toFixed(2)
                ]);

                const totalsRow = [
                    'Total', '',
                    String(this.totals.initial_ep),
                    parseFloat(this.totals.initial_kg).toFixed(2),
                    String(this.totals.in_ep),
                    parseFloat(this.totals.in_kg).toFixed(2),
                    String(this.totals.out_ep),
                    parseFloat(this.totals.out_kg).toFixed(2),
                    String(this.totals.stock_end_before_adj_ep),
                    parseFloat(this.totals.stock_end_before_adj_kg).toFixed(2),
                    String(this.totals.adjustment_ep),
                    parseFloat(this.totals.adjustment_kg).toFixed(2),
                    String(this.totals.stock_after_adj_ep),
                    parseFloat(this.totals.stock_after_adj_kg).toFixed(2)
                ];

                const combinedData = [
                    [chillerHeader],
                    [periodHeader],
                    [],
                    ...tableHeaders,
                    ...tableData,
                    [],
                    totalsRow
                ];

                const dataWs = XLSX.utils.aoa_to_sheet(combinedData);

                dataWs['!merges'] = [{
                        s: {
                            r: 0,
                            c: 0
                        },
                        e: {
                            r: 0,
                            c: 13
                        }
                    },
                    {
                        s: {
                            r: 1,
                            c: 0
                        },
                        e: {
                            r: 1,
                            c: 13
                        }
                    },
                    {
                        s: {
                            r: 3,
                            c: 2
                        },
                        e: {
                            r: 3,
                            c: 3
                        }
                    },
                    {
                        s: {
                            r: 4,
                            c: 2
                        },
                        e: {
                            r: 4,
                            c: 2
                        }
                    },
                    {
                        s: {
                            r: 4,
                            c: 3
                        },
                        e: {
                            r: 4,
                            c: 3
                        }
                    },
                    {
                        s: {
                            r: 3,
                            c: 4
                        },
                        e: {
                            r: 3,
                            c: 5
                        }
                    },
                    {
                        s: {
                            r: 4,
                            c: 4
                        },
                        e: {
                            r: 4,
                            c: 4
                        }
                    },
                    {
                        s: {
                            r: 4,
                            c: 5
                        },
                        e: {
                            r: 4,
                            c: 5
                        }
                    },
                    {
                        s: {
                            r: 3,
                            c: 6
                        },
                        e: {
                            r: 3,
                            c: 7
                        }
                    },
                    {
                        s: {
                            r: 4,
                            c: 6
                        },
                        e: {
                            r: 4,
                            c: 6
                        }
                    },
                    {
                        s: {
                            r: 4,
                            c: 7
                        },
                        e: {
                            r: 4,
                            c: 7
                        }
                    },
                    {
                        s: {
                            r: 3,
                            c: 8
                        },
                        e: {
                            r: 3,
                            c: 9
                        }
                    },
                    {
                        s: {
                            r: 4,
                            c: 8
                        },
                        e: {
                            r: 4,
                            c: 8
                        }
                    },
                    {
                        s: {
                            r: 4,
                            c: 9
                        },
                        e: {
                            r: 4,
                            c: 9
                        }
                    },
                    {
                        s: {
                            r: 3,
                            c: 10
                        },
                        e: {
                            r: 3,
                            c: 11
                        }
                    },
                    {
                        s: {
                            r: 4,
                            c: 10
                        },
                        e: {
                            r: 4,
                            c: 10
                        }
                    },
                    {
                        s: {
                            r: 4,
                            c: 11
                        },
                        e: {
                            r: 4,
                            c: 11
                        }
                    },
                    {
                        s: {
                            r: 3,
                            c: 12
                        },
                        e: {
                            r: 3,
                            c: 13
                        }
                    },
                    {
                        s: {
                            r: 4,
                            c: 12
                        },
                        e: {
                            r: 4,
                            c: 12
                        }
                    },
                    {
                        s: {
                            r: 4,
                            c: 13
                        },
                        e: {
                            r: 4,
                            c: 13
                        }
                    },
                    {
                        s: {
                            r: 3,
                            c: 0
                        },
                        e: {
                            r: 4,
                            c: 0
                        }
                    },
                    {
                        s: {
                            r: 3,
                            c: 1
                        },
                        e: {
                            r: 4,
                            c: 1
                        }
                    },
                ];

                const totalRowIndex = combinedData.length - 1;
                dataWs['!merges'].push({
                    s: {
                        r: totalRowIndex,
                        c: 0
                    },
                    e: {
                        r: totalRowIndex,
                        c: 1
                    }
                });

                const columnWidths = [{
                        wch: 5
                    }, {
                        wch: 30
                    }, {
                        wch: 10
                    }, {
                        wch: 10
                    },
                    {
                        wch: 10
                    }, {
                        wch: 10
                    }, {
                        wch: 10
                    }, {
                        wch: 10
                    },
                    {
                        wch: 25
                    }, {
                        wch: 10
                    }, {
                        wch: 15
                    }, {
                        wch: 10
                    },
                    {
                        wch: 15
                    }, {
                        wch: 10
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
                            if (R <= 1) {
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

                XLSX.utils.book_append_sheet(workbook, dataWs, 'Stock Chiller');
                XLSX.writeFile(workbook, `Data_Stok_Chiller_${this.selectedStorage}_${this.startDate}_${this.endDate}.xlsx`);
            }
        }
    }

    // Mobile Cards Component
    function mobileStockCards() {
        return {
            expandedCard: null,
            toggleCard(index) {
                this.expandedCard = this.expandedCard === index ? null : index;
            },
            openTracingPage(item, storage) {
                const url = `?page=tracing&item=${encodeURIComponent(item)}&storage=${encodeURIComponent(storage)}`;
                window.open(url, '_blank');
            }
        }
    }
</script>