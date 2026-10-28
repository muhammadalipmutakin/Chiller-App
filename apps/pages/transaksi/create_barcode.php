<main class="flex-1 p-2 sm:p-4 lg:p-6" x-data="optimizedTransactionForm()" x-init="initForm()">
    <div class="container p-2 sm:p-4">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 mb-4">
            <div class="flex-shrink-0">
                <a href="?page=transaksi/" class="inline-flex items-center px-3 py-1 bg-blue-500 text-white text-xs sm:text-sm rounded-md shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-75 transition duration-300">
                    <i class="fas fa-arrow-left mr-1 sm:mr-2 text-xs"></i> Kembali
                </a>
                <h1 class="text-sm sm:text-lg font-bold text-gray-900 mt-2">Tambah Transaksi Baru</h1>
            </div>
        </div>
    </div>


    <div x-show="isLoading" x-transition class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-4 rounded-lg shadow-lg flex items-center text-sm">
            <i class="fas fa-spinner fa-spin mr-2 text-blue-500"></i>
            <span>Menyimpan data...</span>
        </div>
    </div>

    <form @submit.prevent="submitForm($event)" id="transactionForm" class="p-2 sm:p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-6 mb-4 sm:mb-6">
            <div class="space-y-2 sm:space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center gap-1 p-2 border border-purple-300 rounded-lg bg-purple-50" x-show="!isBarcodeOutHidden">
                    <label for="barcodeOut" class="w-20 sm:w-24 text-purple-700 font-bold text-xs sm:text-sm flex items-center">
                        <i class="fas fa-barcode mr-1 text-purple-600"></i> Barcode Out
                    </label>
                    <input type="text" id="barcodeOut" x-model="formData.barcode_out" x-ref="barcodeOut"
                        class="flex-1 p-1.5 border border-purple-400 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-600 text-xs sm:text-sm bg-white"
                        placeholder="Scan atau masukkan Barcode Out (wajib)" autocomplete="off"
                        x-on:keydown.enter="handleBarcodeOutScan()">
                    <button type="button"
                        x-on:click="handleBarcodeOutScan()"
                        class="ml-1 sm:ml-2 px-3 py-1.5 bg-purple-600 text-white font-semibold rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-opacity-50 text-xs sm:text-sm mt-1 sm:mt-0">
                        Cari
                    </button>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center gap-1 mb-2 sm:mb-4">
                    <label for="Tanggal" class="w-20 sm:w-24 text-gray-700 font-medium text-xs sm:text-sm">Tanggal</label>
                    <input type="date" id="Tanggal" x-model="formData.date"
                        class="flex-1 p-1.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs sm:text-sm"
                        required>
                </div>

                <div class="flex flex-col gap-2 sm:gap-4">
                    <div class="w-full flex items-center gap-2">
                        <label for="selectFromLocation" class="block text-gray-700 w-20 sm:w-24 font-medium text-xs sm:text-sm">Dari</label>
                        <select id="selectFromLocation" x-ref="selectFromLocation" x-model="formData.from_location"
                            class="p-1.5 w-full border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs sm:text-sm"
                            :disabled="isFromLocationDisabled"
                            :class="{'bg-gray-100 cursor-not-allowed': isFromLocationDisabled}"
                            required>
                            <option value="">Pilih Lokasi</option>
                            <template x-for="location in filteredLocationsFrom" :key="location.lokasi">
                                <option :value="location.lokasi" x-text="location.lokasi"></option>
                            </template>
                        </select>
                    </div>

                    <div class="w-full flex items-center gap-2">
                        <label for="selectToLocation" class="block text-gray-700 w-20 sm:w-24 font-medium text-xs sm:text-sm">Tujuan</label>
                        <select id="selectToLocation" x-ref="selectToLocation" x-model="formData.to_location"
                            class="p-1.5 w-full border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs sm:text-sm"
                            required>
                            <option value="">Pilih Lokasi</option>
                            <template x-for="location in masterData.locations" :key="location.lokasi">
                                <option :value="location.lokasi" x-text="location.lokasi"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>

            <div class="space-y-2 sm:space-y-4 mt-4 sm:mt-0">
                <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 mb-1">
                    <label for="item" class="w-20 sm:w-24 text-gray-700 font-medium text-xs sm:text-sm">Item</label>
                    <div class="flex-1 relative flex flex-col gap-1">
                        <select id="selectItem" x-ref="selectItem" style="width: 100%;"
                            class="p-1.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs sm:text-sm"
                            :disabled="isItemDisabled"
                            :class="{'bg-gray-100 cursor-not-allowed': isItemDisabled}"
                            required>
                            <option value="">Pilih Item</option>
                        </select>
                        <div class="flex flex-col">
                            <span x-show="shouldShowStock()" class="text-xs text-gray-600 whitespace-nowrap"
                                x-text="`Stok (Item/Storage): ${currentStock.ekor_pack} Ekor/Pack, ${currentStock.quantity_kg} Kg`"></span>

                            <span x-show="shouldShowStock()" class="text-xs text-gray-600 whitespace-nowrap"
                                x-text="`Stok (Barcode): ${currentStock.ekor_barcode} Ekor/Pack, ${currentStock.kg_barcode} Kg`"></span>
                        </div>
                    </div>
                </div>


                <div class="text-xs text-gray-600 mb-2">
                    Jika Anda tidak menemukan item yang dimaksud, pastikan item tersebut sudah aktif.
                    <a href="?page=master/item" class="text-blue-600 hover:underline font-medium ml-1">
                        Aktifkan Item
                    </a>
                </div>

                <div x-show="!isSubitemHidden" class="flex flex-col sm:flex-row sm:items-center gap-1 mt-2">
                    <label for="selectSubitem" class="w-20 sm:w-24 text-gray-700 font-medium text-xs sm:text-sm">Sub Item</label>
                    <div class="flex-1 relative">
                        <select id="selectSubitem" x-ref="selectSubitem" x-model="formData.subitem"
                            class="p-1.5 w-full border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs sm:text-sm"
                            :disabled="isSubitemDisabled"
                            :class="{'bg-gray-100 cursor-not-allowed': isSubitemDisabled}">
                            <option value="">Pilih Sub Item (Opsional)</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center gap-1">
                    <label for="keterangan" class="w-20 sm:w-24 text-gray-700 font-medium text-xs sm:text-sm">Keterangan</label>
                    <input type="text" id="keterangan" x-model="formData.keterangan"
                        class="flex-1 p-1.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs sm:text-sm"
                        placeholder="Tambahkan keterangan (opsional)">
                </div>

                <div x-show="!isBarcodeInHidden" class="flex flex-col sm:flex-row sm:items-center gap-1 mt-2 p-2 border border-blue-300 rounded-lg bg-blue-50">
                    <label for="barcodeIn" class="w-20 sm:w-24 text-blue-700 font-bold text-xs sm:text-sm flex items-center">
                        <i class="fas fa-barcode mr-1 text-blue-600"></i> Barcode In
                    </label>
                    <input type="text" id="barcodeIn" x-model="formData.barcode_in"
                        class="flex-1 p-1.5 border border-blue-400 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-600 text-xs sm:text-sm bg-white"
                        placeholder="Scan atau masukkan Barcode In" :required="isBarcodeInRequired" autocomplete="off"
                        x-on:keydown.enter.prevent>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-4 mb-4 sm:mb-6">
            <div class="flex flex-col gap-2 p-2 border border-gray-200 rounded-lg shadow-sm">
                <label for="ekor" class="text-gray-700 font-medium text-sm">Ekor/Pack <span x-show="shouldShowStock()" class="text-blue-700 text-xs"
                        x-text="`Stok masuk: ${currentStock.ekor_masuk} Ekor`"></span></label>
                <input type="text" id="ekor" x-model="formData.ekor_pack"
                    class="p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm w-full"
                    placeholder="Jumlah ekor atau pack" readonly required>

                <div class="text-center">
                    <div class="grid grid-cols-8 gap-1 mt-2">
                        <button type="button" @click="appendValue('1', 'ekor_pack')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 text-sm">1</button>
                        <button type="button" @click="appendValue('2', 'ekor_pack')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 text-sm">2</button>
                        <button type="button" @click="appendValue('3', 'ekor_pack')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 text-sm">3</button>
                        <button type="button" @click="calculate('+', 'ekor')" class="col-span-1 px-1 py-1 rounded-lg border border-gray-400 operator bg-orange-500 text-white text-sm"><i class="fa-solid fa-plus"></i></button>
                        <button type="button" @click="calculate('-', 'ekor')" class="col-span-1 px-1 py-1 rounded-lg border border-gray-400 operator bg-orange-500 text-white text-sm"><i class="fa-solid fa-minus"></i></button>
                    </div>

                    <div class="grid grid-cols-8 gap-1 mt-2">
                        <button type="button" @click="appendValue('4', 'ekor_pack')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 text-sm">4</button>
                        <button type="button" @click="appendValue('5', 'ekor_pack')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 text-sm">5</button>
                        <button type="button" @click="appendValue('6', 'ekor_pack')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 text-sm">6</button>
                        <button type="button" @click="calculate('*', 'ekor')" class="col-span-1 px-1 py-1 rounded-lg border border-gray-400 operator bg-orange-500 text-white text-sm"><i class="fa-solid fa-xmark"></i></button>
                        <button type="button" @click="calculate('/', 'ekor')" class="col-span-1 px-1 py-1 rounded-lg border border-gray-400 operator bg-orange-500 text-white text-sm"><i class="fa-solid fa-divide"></i></button>
                    </div>

                    <div class="grid grid-cols-8 gap-1 mt-2">
                        <button type="button" @click="appendValue('7', 'ekor_pack')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 text-sm">7</button>
                        <button type="button" @click="appendValue('8', 'ekor_pack')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 text-sm">8</button>
                        <button type="button" @click="appendValue('9', 'ekor_pack')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 text-sm">9</button>
                        <button type="button" @click="calculate('=', 'ekor')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 equals bg-green-500 text-white text-sm"><i class="fa-solid fa-equals"></i></button>
                    </div>

                    <div class="grid grid-cols-8 gap-1 mt-2">
                        <button type="button" @click="appendValue('C', 'ekor_pack')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 clear bg-red-500 text-white text-sm">C</button>
                        <button type="button" @click="appendValue('0', 'ekor_pack')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 text-sm">0</button>
                        <button type="button" @click="appendValue('.', 'ekor_pack')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 text-sm">.</button>
                        <button type="button" @click="calculate('⌫', 'ekor')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 backspace bg-yellow-500 text-white text-sm">
                            <i class="fa-solid fa-backspace"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-2 p-2 border border-gray-200 rounded-lg shadow-sm mt-4 sm:mt-0">
                <label for="berat" class="text-gray-700 font-medium text-sm">Berat (Kg) <span x-show="shouldShowStock()" class="text-blue-700 text-xs"
                        x-text="`Stok masuk: ${currentStock.kg_masuk} Kg`"></span></label>
                <input type="text" id="berat" x-model="formData.quantity_kg"
                    class="p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm w-full"
                    placeholder="Berat dalam Kilogram" readonly required>

                <div class="text-center">
                    <div class="grid grid-cols-8 gap-1 mt-2">
                        <button type="button" @click="appendValue('1', 'quantity_kg')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 text-sm">1</button>
                        <button type="button" @click="appendValue('2', 'quantity_kg')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 text-sm">2</button>
                        <button type="button" @click="appendValue('3', 'quantity_kg')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 text-sm">3</button>
                        <button type="button" @click="calculate('+', 'berat')" class="col-span-1 px-1 py-1 rounded-lg border border-gray-400 operator bg-orange-500 text-white text-sm"><i class="fa-solid fa-plus"></i></button>
                        <button type="button" @click="calculate('-', 'berat')" class="col-span-1 px-1 py-1 rounded-lg border border-gray-400 operator bg-orange-500 text-white text-sm"><i class="fa-solid fa-minus"></i></button>
                    </div>

                    <div class="grid grid-cols-8 gap-1 mt-2">
                        <button type="button" @click="appendValue('4', 'quantity_kg')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 text-sm">4</button>
                        <button type="button" @click="appendValue('5', 'quantity_kg')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 text-sm">5</button>
                        <button type="button" @click="appendValue('6', 'quantity_kg')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 text-sm">6</button>
                        <button type="button" @click="calculate('*', 'berat')" class="col-span-1 px-1 py-1 rounded-lg border border-gray-400 operator bg-orange-500 text-white text-sm"><i class="fa-solid fa-xmark"></i></button>
                        <button type="button" @click="calculate('/', 'berat')" class="col-span-1 px-1 py-1 rounded-lg border border-gray-400 operator bg-orange-500 text-white text-sm"><i class="fa-solid fa-divide"></i></button>
                    </div>

                    <div class="grid grid-cols-8 gap-1 mt-2">
                        <button type="button" @click="appendValue('7', 'quantity_kg')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 text-sm">7</button>
                        <button type="button" @click="appendValue('8', 'quantity_kg')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 text-sm">8</button>
                        <button type="button" @click="appendValue('9', 'quantity_kg')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 text-sm">9</button>
                        <button type="button" @click="calculate('=', 'berat')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 equals bg-green-500 text-white text-sm"><i class="fa-solid fa-equals"></i></button>
                    </div>

                    <div class="grid grid-cols-8 gap-1 mt-2">
                        <button type="button" @click="appendValue('C', 'quantity_kg')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 clear bg-red-500 text-white text-sm">C</button>
                        <button type="button" @click="appendValue('0', 'quantity_kg')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 text-sm">0</button>
                        <button type="button" @click="appendValue('.', 'quantity_kg')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 text-sm">.</button>
                        <button type="button" @click="calculate('⌫', 'berat')" class="col-span-2 px-1 py-1 rounded-lg border border-gray-400 backspace bg-yellow-500 text-white text-sm">
                            <i class="fa-solid fa-backspace"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 sm:gap-4 mt-4">
            <button type="button" @click="submitForm($event, true)"
                class="flex-1 sm:flex-none px-4 py-2 sm:px-5 sm:py-2.5 bg-blue-700 text-white font-semibold rounded-lg shadow-md hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:ring-opacity-75 transition duration-300 text-sm"
                :disabled="isLoading">
                <span x-show="!isLoading">Simpan & Lanjutkan</span>
                <span x-show="isLoading" class="flex items-center justify-center text-sm">
                    <i class="fas fa-spinner fa-spin mr-2"></i>
                    Menyimpan...
                </span>
            </button>

            <button type="button" @click="submitForm($event, false)"
                class="flex-1 sm:flex-none px-4 py-2 sm:px-5 sm:py-2.5 bg-blue-500 text-white font-semibold rounded-lg shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-75 transition duration-300 text-sm"
                :disabled="isLoading">
                <span x-show="!isLoading">Simpan</span>
                <span x-show="isLoading" class="flex items-center justify-center text-sm">
                    <i class="fas fa-spinner fa-spin mr-2"></i>
                    Menyimpan...
                </span>
            </button>
        </div>
    </form>
</main>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('optimizedTransactionForm', () => ({
            chillerLocations: ['Chiller 1', 'Chiller 2', 'Chiller 3'],
            isLoading: false,
            errorMessage: '',
            lastInputWasOperator: false,
            barcodeOutScanned: false,
            formData: {
                date: new Date().toISOString().slice(0, 10),
                from_location: '',
                to_location: '',
                item: '',
                subitem: '',
                ekor_pack: '',
                ekor_pack_value: null,
                quantity_kg: '',
                quantity_kg_value: null,
                keterangan: '',
                barcode_in: '',
                barcode_out: '',
                user: '<?php echo $_SESSION["user_nama"] ?? "Guest"; ?>',
                id_in: null
            },

            masterData: {
                locations: [],
                items: [],
                subitems: [],
                itemsWithStock: []
            },

            currentStock: {
                ekor_pack: 0,
                quantity_kg: 0
            },

            async initForm() {
                this.isLoading = true;
                try {
                    await Promise.all([
                        this.fetchMasterData('locations', './controller/transaksi/transaksi_api.php?action=getLocations'),
                        this.fetchMasterData('items', './controller/transaksi/transaksi_api.php?action=getAllItems'),
                        this.fetchMasterData('subitems', './controller/transaksi/transaksi_api.php?action=getAllSubitems')
                    ]);

                    this.initSelect2ForItems();
                    this.setupEventListeners();
                    this.filterSubitems();
                } catch (error) {
                    console.error('Initialization error:', error);
                    this.showError('Failed to load initial data. Please refresh the page.');
                } finally {
                    this.isLoading = false;
                }
            },

            async fetchMasterData(key, url) {
                const response = await fetch(url);
                if (!response.ok) throw new Error(`Failed to fetch ${key}: ${response.statusText}`);
                this.masterData[key] = await response.json();
            },

            initSelect2ForItems() {
                const select2Config = {
                    placeholder: "Select an Item",
                    allowClear: true,
                    width: '100%',
                    minimumResultsForSearch: 5
                };

                if (this.$refs.selectItem) {
                    $(this.$refs.selectItem).select2({
                        ...select2Config,
                        data: this.masterData.items.map(item => ({
                            id: item.item,
                            text: item.item,
                            has_subitem: item.has_subitem
                        }))
                    }).on('change', () => {
                        this.formData.item = $(this.$refs.selectItem).val();
                        this.updateCurrentStock();
                        this.filterSubitems();
                    });
                    if (this.formData.item) {
                        $(this.$refs.selectItem).val(this.formData.item).trigger('change');
                    }
                }

                if (this.$refs.selectSubitem) {
                    $(this.$refs.selectSubitem).select2({
                        ...select2Config,
                        placeholder: "Select Sub Item (Optional)"
                    }).on('change', () => {
                        this.formData.subitem = $(this.$refs.selectSubitem).val();
                    });
                    if (this.formData.subitem) {}
                }
            },

            filterSubitems() {
                const selectedItem = this.formData.item;
                const transactionType = this.transactionType;
                let filteredSubitems = [];

                if (!selectedItem) {
                    if (transactionType === 'in' || !transactionType) {
                        filteredSubitems = this.masterData.subitems;
                    } else {
                        filteredSubitems = [];
                    }
                } else if (transactionType === 'in') {
                    const relatedSubitems = this.masterData.subitems.filter(sub => sub.item_related === selectedItem);
                    filteredSubitems = relatedSubitems.length > 0 ? relatedSubitems : this.masterData.subitems;
                } else if (selectedItem === 'Retur' || selectedItem === 'Thawing') {
                    filteredSubitems = this.masterData.subitems;
                } else {
                    const itemData = this.masterData.items.find(item => item.item === selectedItem);
                    if (itemData?.has_subitem) {
                        filteredSubitems = this.masterData.subitems.filter(sub => sub.item_related === selectedItem);
                    } else {
                        filteredSubitems = [];
                    }
                }

                this.updateSubitemsDropdown(filteredSubitems);

                if (this.formData.subitem && !filteredSubitems.some(sub => sub.subitem === this.formData.subitem)) {
                    this.formData.subitem = '';
                    if (this.$refs.selectSubitem) $(this.$refs.selectSubitem).val('').trigger('change');
                }
            },

            updateSubitemsDropdown(subitems) {
                if (!this.$refs.selectSubitem) return;

                const currentValue = $(this.$refs.selectSubitem).val();

                if ($(this.$refs.selectSubitem).data('select2')) {
                    $(this.$refs.selectSubitem).select2('destroy');
                }

                $(this.$refs.selectSubitem).empty().select2({
                    data: subitems.map(sub => ({
                        id: sub.subitem,
                        text: sub.subitem
                    })),
                    placeholder: "Select Sub Item (Optional)",
                    allowClear: true,
                    width: '100%',
                    minimumResultsForSearch: 5
                });

                if (currentValue && subitems.some(sub => sub.subitem === currentValue)) {
                    $(this.$refs.selectSubitem).val(currentValue).trigger('change');
                } else {
                    $(this.$refs.selectSubitem).val(null).trigger('change');
                }
            },

            setupEventListeners() {
                if (this.$refs.selectFromLocation) {
                    this.$refs.selectFromLocation.addEventListener('change', (e) => {
                        this.handleLocationChange(e.target.value);
                    });
                }

                if (this.$refs.selectToLocation) {
                    this.$refs.selectToLocation.addEventListener('change', (e) => {
                        this.formData.to_location = e.target.value;
                        this.filterSubitems();
                    });
                }

                if (this.$refs.barcodeOut) {
                    this.$refs.barcodeOut.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            this.handleBarcodeOutScan();
                        }
                    });
                }
            },

            handleLocationChange(location) {
                this.formData.from_location = location;
                if (!this.barcodeOutScanned || (this.barcodeOutScanned && !this.isChillerLocation(location))) {
                    this.resetItemAndSubitem();
                }

                this.filterSubitems();
            },

            resetItemAndSubitem() {
                this.formData.item = '';
                this.formData.subitem = '';
                if (this.$refs.selectItem) $(this.$refs.selectItem).val('').trigger('change');
                if (this.$refs.selectSubitem) $(this.$refs.selectSubitem).val('').trigger('change');
                this.filterSubitems();
            },

            async fetchItemsWithStock(location) {
                if (!this.isChillerLocation(location)) return;

                this.isLoading = true;
                try {
                    const response = await fetch(`./controller/transaksi/transaksi_api.php?action=getItemsWithStock&location=${encodeURIComponent(location)}`);

                    if (!response.ok) {
                        throw new Error(`Gagal mengambil stok: ${response.status}`);
                    }

                    this.masterData.itemsWithStock = await response.json();

                    this.updateItemDropdownWithStock();

                } catch (error) {
                    console.error('Error:', error);
                    this.showError('Gagal memuat stok item');
                    this.masterData.itemsWithStock = [];
                } finally {
                    this.isLoading = false;
                }
            },


            updateItemDropdownWithStock() {
                if (!this.$refs.selectItem) return;

                if ($(this.$refs.selectItem).data('select2')) {
                    $(this.$refs.selectItem).select2('destroy');
                }

                const itemsData = this.masterData.items.map(item => {
                    const stockItem = this.masterData.itemsWithStock.find(s => s.item === item.item);
                    return {
                        id: item.item,
                        text: item.item,
                        stock: stockItem || null
                    };
                });

                $(this.$refs.selectItem).select2({
                    data: itemsData,
                    templateResult: (item) => {
                        if (!item.id) return item.text;

                        if (item.stock) {
                            return $(`<span>${item.text} <small class="text-gray-500">(Stok: ${item.stock.ekor_pack} Ekor, ${item.stock.quantity_kg} Kg)</small></span>`);
                        }
                        return item.text;
                    },
                    templateSelection: (item) => {
                        return item.text.split(' (Stok:')[0]; // Hanya tampilkan nama item saat dipilih
                    },
                    width: '100%'
                }).on('change', () => {
                    this.formData.item = $(this.$refs.selectItem).val();
                    this.updateCurrentStock();
                });

                if (this.formData.item) {
                    $(this.$refs.selectItem).val(this.formData.item).trigger('change');
                }
            },

            updateCurrentStock() {
                if (!this.formData.item || !this.barcodeOutScanned) {
                    this.currentStock = {
                        ekor_pack: 0,
                        quantity_kg: 0
                    };
                    return;
                }

                const stockItem = this.masterData.itemsWithStock.find(item => item.item === this.formData.item);

                if (stockItem) {
                    this.currentStock = {
                        ekor_pack: stockItem.ekor_pack,
                        quantity_kg: stockItem.quantity_kg
                    };

                    // Auto-set nilai form jika berbeda
                    if (this.formData.ekor_pack !== stockItem.ekor_pack.toString()) {
                        this.formData.ekor_pack = stockItem.ekor_pack.toString();
                    }
                    if (this.formData.quantity_kg !== stockItem.quantity_kg.toString()) {
                        this.formData.quantity_kg = stockItem.quantity_kg.toString();
                    }
                } else {
                    this.currentStock = {
                        ekor_pack: 0,
                        quantity_kg: 0
                    };
                }

                this.updateNumericValue('ekor_pack');
                this.updateNumericValue('quantity_kg');
            },

            get transactionType() {
                const fromChiller = this.isChillerLocation(this.formData.from_location);
                const toChiller = this.isChillerLocation(this.formData.to_location);

                if (this.barcodeOutScanned) { // Prioritize scanned barcode for OUT/TRANSFER
                    if (fromChiller && toChiller) return 'transfer';
                    return 'out';
                }

                // If no barcode is scanned out, then check locations to determine IN, OUT, TRANSFER, or OTHER
                // This is the new crucial part for default state:
                if (!this.formData.from_location && !this.formData.to_location) {
                    return 'in'; // <--- **This line changed.** Default to 'in' when both locations are empty.
                }

                if (!fromChiller && toChiller) {
                    return 'in';
                }
                if (fromChiller && !toChiller) {
                    return 'out';
                }
                if (fromChiller && toChiller) {
                    return 'transfer';
                }

                return 'other';
            },

            get allLocations() {
                return this.masterData.locations;
            },

            get filteredLocationsFrom() {
                if (this.barcodeOutScanned && this.isChillerLocation(this.formData.from_location)) {
                    return this.masterData.locations;
                }
                return this.masterData.locations.filter(loc => !this.chillerLocations.includes(loc.lokasi));
            },

            get isBarcodeInHidden() {
                return this.barcodeOutScanned || ['out', 'transfer', 'other'].includes(this.transactionType);
            },
            get isBarcodeOutHidden() {
                return ['other'].includes(this.transactionType);
            },

            get isBarcodeInRequired() {
                return this.transactionType === 'in';
            },

            get isFromLocationDisabled() {
                return this.barcodeOutScanned;
            },

            get isItemDisabled() {
                return this.barcodeOutScanned;
            },

            get isSubitemHidden() {
                return this.barcodeOutScanned;
            },

            get isSubitemDisabled() {
                return this.barcodeOutScanned || !this.formData.item;
            },

            shouldShowStock() {
                return this.barcodeOutScanned && this.formData.item;
            },

            isChillerLocation(location) {
                return this.chillerLocations.includes(location);
            },

            appendValue(value, field) {
                if (value === 'C') {
                    this.formData[field] = '';
                    this.updateNumericValue(field);
                    this.lastInputWasOperator = false;
                    return;
                }
                if (this.formData[field] === 'Error') {
                    this.formData[field] = '';
                }
                if (value === '.') {
                    const parts = this.formData[field].split(/[\+\-\*\/]/);
                    const currentNumberPart = parts[parts.length - 1];
                    if (currentNumberPart.includes('.')) return;
                    if (this.lastInputWasOperator || this.formData[field] === '') {
                        this.formData[field] += '0.';
                    } else {
                        this.formData[field] += value;
                    }
                } else if (!isNaN(value)) {
                    this.formData[field] += value;
                }
                this.lastInputWasOperator = false;
                this.updateNumericValue(field);
            },

            calculate(operator, field) {
                let targetField = field === 'ekor' ? 'ekor_pack' : 'quantity_kg';

                if (operator === '=') {
                    this.evaluateExpression(targetField);
                } else if (operator === '⌫') {
                    this.handleBackspace(targetField);
                } else {
                    this.handleOperator(operator, targetField);
                }
            },

            evaluateExpression(field) {
                try {
                    let expression = this.formData[field].replace(/÷/g, '/').replace(/×/g, '*');
                    expression = expression.replace(/[\+\-\*\/.]+$/, '');

                    if (expression === '') {
                        this.formData[field] = '';
                        this.updateNumericValue(field);
                        return;
                    }
                    let result = eval(expression);

                    if (isNaN(result) || !isFinite(result)) {
                        this.formData[field] = 'Error';
                    } else {
                        if (field === 'ekor_pack') {
                            result = Math.round(result);
                            this.formData[field] = result.toString();
                        } else {
                            result = parseFloat(result.toFixed(2));
                            this.formData[field] = result.toFixed(2);
                        }
                    }
                    this.updateNumericValue(field);
                } catch (e) {
                    this.formData[field] = 'Error';
                    this.updateNumericValue(field);
                }
                this.lastInputWasOperator = false;
            },

            handleBackspace(field) {
                if (this.formData[field] === 'Error') {
                    this.formData[field] = '';
                } else {
                    this.formData[field] = this.formData[field].slice(0, -1);
                }
                this.updateNumericValue(field);
                const lastChar = this.formData[field].slice(-1);
                this.lastInputWasOperator = ['+', '-', '*', '/'].includes(lastChar);
            },

            handleOperator(operator, field) {
                let currentValue = this.formData[field];
                if (currentValue === '' || currentValue === 'Error') {
                    if (operator === '-') {
                        this.formData[field] = '-';
                        this.lastInputWasOperator = true;
                    }
                    return;
                }
                const lastChar = currentValue.slice(-1);
                if (['+', '-', '*', '/'].includes(lastChar)) {
                    this.formData[field] = currentValue.slice(0, -1) + operator;
                } else if (lastChar === '.') {
                    this.formData[field] = currentValue + '0' + operator;
                } else {
                    this.formData[field] += operator;
                }
                this.lastInputWasOperator = true;
                this.updateNumericValue(field);
            },

            updateNumericValue(field) {
                let valueField = `${field}_value`;
                let expression = this.formData[field].replace(/÷/g, '/').replace(/×/g, '*');
                expression = expression.replace(/[\+\-\*\/.]+$/, '');

                if (expression === '') {
                    this.formData[valueField] = null;
                    return;
                }
                try {
                    let result = eval(expression);
                    if (isNaN(result) || !isFinite(result)) {
                        this.formData[valueField] = null;
                    } else {
                        if (field === 'ekor_pack') {
                            this.formData[valueField] = Math.round(result);
                        } else {
                            this.formData[valueField] = parseFloat(result.toFixed(2));
                        }
                    }
                } catch (e) {
                    this.formData[valueField] = null;
                }
            },

            validateForm() {
                this.errorMessage = '';
                let isValid = true;

                document.querySelectorAll('[data-validate]').forEach(el => el.setCustomValidity(''));

                // Validasi Berat (Kg)
                const beratValue = parseFloat(this.formData.quantity_kg);
                if (isNaN(beratValue) || beratValue <= 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Input Tidak Valid',
                        text: 'Berat (Kg) harus berupa angka lebih dari 0.'
                    });
                    return false;
                }

                // Validasi Barcode berdasarkan jenis transaksi
                const currentTransactionType = this.transactionType;

                if (currentTransactionType === 'in') {
                    if (!this.formData.barcode_in) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Barcode Tidak Lengkap',
                            text: 'Barcode In diperlukan untuk transaksi IN.'
                        });
                        return false;
                    }
                } else if (currentTransactionType === 'out' || currentTransactionType === 'transfer') {
                    if (!this.formData.barcode_out) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Barcode Tidak Lengkap',
                            text: 'Barcode Out diperlukan untuk transaksi OUT/Transfer.'
                        });
                        return false;
                    }
                }

                // Validasi Lokasi Asal
                if (!this.isFromLocationDisabled && !this.formData.from_location) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lokasi Asal Kosong',
                        text: 'Lokasi asal harus diisi.'
                    });
                    return false;
                }

                // Validasi Lokasi Tujuan
                if (!this.formData.to_location) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lokasi Tujuan Kosong',
                        text: 'Lokasi tujuan harus diisi.'
                    });
                    return false;
                }

                // Validasi Item
                if (!this.isItemDisabled && !this.formData.item) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Item Tidak Dipilih',
                        text: 'Silakan pilih item terlebih dahulu.'
                    });
                    return false;
                }

                return true;
            },

            setValidationError(elementId, message) {
                const element = document.getElementById(elementId);
                if (element) {
                    element.setCustomValidity(message);
                    element.reportValidity();
                }
            },

            async handleBarcodeOutScan() {
                const barcode = this.formData.barcode_out.trim();
                if (!barcode) {
                    this.resetBarcodeOutFields();

                    return;
                }

                this.isLoading = true;
                try {
                    const response = await fetch(`./controller/transaksi/transaksi_api.php?action=getTransactionByBarcodeIn&barcode_in=${encodeURIComponent(barcode)}`);

                    if (!response.ok) {
                        throw new Error(`Gagal mengambil data: ${response.status}`);
                    }

                    const result = await response.json();
                    console.log("API Response from getTransactionByBarcodeIn:", result);

                    if (result.success && result.data) {
                        const transaction = result.data;

                        this.formData = {
                            ...this.formData,
                            id_in: transaction.id,
                            from_location: transaction.storage,
                            item: transaction.item,
                            subitem: transaction.subitem || '',
                            ekor_pack: transaction.current_ekor_barcode.toString(),
                            quantity_kg: transaction.current_kg_barcode.toString(),
                        };

                        this.barcodeOutScanned = true;

                        this.$nextTick(async () => {
                            if (!$.fn.select2) {
                                console.error('Select2 not loaded');
                                return;
                            }

                            if (this.$refs.selectFromLocation) {
                                this.$refs.selectFromLocation.value = transaction.storage;
                                this.$refs.selectFromLocation.dispatchEvent(new Event('change'));
                            }

                            if (this.$refs.selectItem) {
                                const $selectItem = $(this.$refs.selectItem);

                                if ($selectItem.find(`option[value="${transaction.item}"]`).length > 0) {
                                    $selectItem.val(transaction.item).trigger('change');
                                } else {
                                    $selectItem.append(new Option(transaction.item, transaction.item, true, true));
                                    $selectItem.trigger('change');
                                }
                            }

                            if (transaction.subitem && this.$refs.selectSubitem) {
                                const $selectSubitem = $(this.$refs.selectSubitem);
                                if ($selectSubitem.find(`option[value="${transaction.subitem}"]`).length > 0) {
                                    $selectSubitem.val(transaction.subitem).trigger('change');
                                }
                            }

                            this.currentStock = {
                                ekor_pack: transaction.current_ekor_stock,
                                quantity_kg: transaction.current_kg_stock,
                                ekor_barcode: transaction.current_ekor_barcode,
                                kg_barcode: transaction.current_kg_barcode,
                                ekor_masuk: transaction.ekor_pack,
                                kg_masuk: transaction.quantity_kg
                            };

                            // await this.fetchItemsWithStock(transaction.storage); // Jika diperlukan
                            // this.updateCurrentStock(); // Jika diperlukan
                        });
                    } else {
                        this.showError(result.message || 'Barcode tidak ditemukan');
                        this.resetBarcodeOutFields(); // Pastikan ini mereset semua field relevan
                    }
                } catch (error) {
                    console.error('Error in handleBarcodeOutScan:', error);
                    this.showError('Gagal memindai barcode. Silakan coba lagi.');
                    this.resetBarcodeOutFields(); // Pastikan ini mereset semua field relevan
                } finally {
                    this.isLoading = false;
                }
            },

            loadBarcodeData(transaction, barcode) {
                console.log('Transaction data in loadBarcodeData:', transaction); // Add this line
                this.formData.id_in = transaction.id;
                this.formData.item = transaction.item;
                this.formData.subitem = transaction.subitem || '';
                this.formData.ekor_pack = transaction.ekor_pack.toString();
                this.formData.quantity_kg = transaction.quantity_kg.toString();
                this.formData.from_location = transaction.storage;
                this.formData.keterangan = ``;

                if (this.$refs.selectFromLocation) {
                    this.$refs.selectFromLocation.value = this.formData.from_location;
                    this.$refs.selectFromLocation.dispatchEvent(new Event('change'));
                }

                if (this.$refs.selectItem) {
                    if ($(this.$refs.selectItem).hasClass('select2-hidden-accessible')) {
                        $(this.$refs.selectItem).select2('destroy');
                    }
                    $(this.$refs.selectItem).select2({
                        data: this.masterData.items.map(item => ({
                            id: item.item,
                            text: item.item
                        })),
                        width: '100%'
                    }).val(this.formData.item).trigger('change');
                }

                if (this.$refs.selectSubitem) {
                    $(this.$refs.selectSubitem).val(this.formData.subitem).trigger('change');
                }

                this.updateNumericValue('ekor_pack');
                this.updateNumericValue('quantity_kg');
                this.updateCurrentStock();
            },
            resetBarcodeOutFields() {
                this.formData.id_in = null;
                this.formData.barcode_out = '';
                this.formData.item = '';
                this.formData.subitem = '';
                this.formData.ekor_pack = '';
                this.formData.ekor_pack_value = null;
                this.formData.quantity_kg = '';
                this.formData.quantity_kg_value = null;
                this.formData.keterangan = '';

                this.barcodeOutScanned = false; // Ensures this is false

                // Explicitly reset locations to ensure the transactionType getter re-evaluates
                // to a state that shows Barcode In
                this.formData.from_location = '';
                this.formData.to_location = '';


                // Reset UI elements (Select2)
                // Use $nextTick to ensure DOM is ready for Select2 operations
                this.$nextTick(() => {
                    if (this.$refs.selectItem) {
                        $(this.$refs.selectItem).val('').trigger('change');
                    }
                    if (this.$refs.selectSubitem) {
                        $(this.$refs.selectSubitem).val('').trigger('change');
                    }
                    if (this.$refs.selectFromLocation) {
                        // This will also trigger handleLocationChange
                        $(this.$refs.selectFromLocation).val('').trigger('change');
                    }
                    if (this.$refs.selectToLocation) {
                        $(this.$refs.selectToLocation).val('').trigger('change');
                    }
                });


                this.currentStock = {
                    ekor_pack: 0,
                    quantity_kg: 0
                };
                this.masterData.itemsWithStock = [];
                this.updateItemDropdownWithStock();

                // The call to handleLocationChange with empty location will ensure
                // filterSubitems and potentially fetchItemsWithStock are called.
                // this.handleLocationChange(this.formData.from_location); // Already triggered by selectFromLocation change
            },

            loadSavedFormDataForContinue() {
                try {
                    const storedData = localStorage.getItem('lastTransactionData');
                    if (storedData) {
                        const {
                            from_location,
                            to_location,
                            user
                        } = JSON.parse(storedData);

                        this.formData.from_location = from_location || '';
                        this.formData.to_location = to_location || '';
                        if (user && user !== 'Guest' && this.formData.user === 'Guest') {
                            this.formData.user = user;
                        } else if (user && user === 'Guest' && this.formData.user === 'Guest') {
                            this.formData.user = user;
                        }


                        if (this.$refs.selectFromLocation) this.$refs.selectFromLocation.value = this.formData.from_location;
                        if (this.$refs.selectToLocation) this.$refs.selectToLocation.value = this.formData.to_location;

                    }
                } catch (error) {
                    console.error('Error loading saved data for continue:', error);
                    localStorage.removeItem('lastTransactionData');
                }
            },

            prepareSubmitData() {
                return {
                    action: 'addUpdateTransaction',
                    date: this.formData.date,
                    from_location: this.formData.from_location,
                    to_location: this.formData.to_location,
                    item: this.formData.item,
                    subitem: this.formData.subitem,
                    ekor_pack: this.formData.ekor_pack,
                    quantity_kg: this.formData.quantity_kg,
                    keterangan: this.formData.keterangan,
                    barcode_in: this.formData.barcode_in,
                    barcode_out: this.formData.barcode_out,
                    user: this.formData.user,
                    id_in: this.formData.id_in,
                    transaction_type: this.transactionType
                };
            },

            async submitForm(event, saveAndContinue = false) {
                event.preventDefault(); // Prevent default form submission

                // 1. Validate Form
                if (!this.validateForm()) return;

                // 2. Check for Same Locations
                if (this.formData.from_location === this.formData.to_location && this.formData.from_location !== '') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Lokasi Sama',
                        text: 'Lokasi "Dari" dan "Tujuan" tidak boleh sama untuk transaksi ini.',
                        confirmButtonText: 'Oke'
                    });
                    return; // Stop submission if locations are identical
                }

                this.isLoading = true; // Show loading indicator

                try {
                    const submitData = this.prepareSubmitData();

                    // Log data being sent for debugging
                    console.log('Data sent to backend:', submitData);
                    console.log('Action executed (via URL): POST /controller/transaksi/transaksi_api.php');

                    // Send data to the backend
                    const response = await fetch('./controller/transaksi/transaksi_api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(submitData)
                    });

                    // Check for HTTP errors before parsing JSON
                    if (!response.ok) {
                        const errorText = await response.text();
                        console.error('HTTP Error Response (not OK):', response.status, response.statusText, errorText);
                        throw new Error(`HTTP error! Status: ${response.status}, Details: ${errorText}`);
                    }

                    const result = await response.json();

                    // Log response from backend for debugging
                    console.log('Response from backend:', result);

                    if (result.success) {
                        // Handle success based on button clicked
                        this.handleSuccess(result.message, saveAndContinue, submitData); // Pass submitData for local storage
                    } else {
                        this.showError(result.message || 'Gagal menyimpan data.');
                    }
                } catch (error) {
                    console.error('Submission error:', error);
                    this.showError(`Terjadi kesalahan: ${error.message}`);
                } finally {
                    this.isLoading = false; // Hide loading indicator
                }
            },

            handleSuccess(message, saveAndContinue, submittedData) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    if (saveAndContinue) {
                        // Save & Continue logic
                        this.saveLastTransaction(submittedData); // Save certain data to local storage
                        this.resetFormForContinue(); // Reset only quantity/weight for new entry
                    } else {
                        window.location.href = '?page=transaksi/';
                    }
                });
            },

            saveLastTransaction(data) {
                const dataToStore = {
                    date: data.date,
                    from_location: data.from_location,
                    to_location: data.to_location,
                    item: data.item,
                    subitem: data.subitem,
                    user: data.user,
                    storage: data.storage, // Storage might be dynamic based on transaction type, adjust if needed
                    // Do NOT save ekor_pack and quantity_kg as they should be fresh for next entry
                };
                localStorage.setItem('lastTransactionData', JSON.stringify(dataToStore));
            },

            loadLastTransaction() {
                const savedData = localStorage.getItem('lastTransactionData');
                if (savedData) {
                    try {
                        const parsedData = JSON.parse(savedData);
                        // Populate formData, but ensure quantities are reset
                        this.formData.date = parsedData.date;
                        this.formData.from_location = parsedData.from_location;
                        this.formData.to_location = parsedData.to_location;
                        this.formData.item = parsedData.item;
                        this.formData.subitem = parsedData.subitem;
                        this.formData.user = parsedData.user;
                        this.formData.storage = parsedData.storage;
                        // Reset quantities for a new entry
                        this.formData.ekor_pack = 0;
                        this.formData.quantity_kg = 0.0;
                        this.formData.keterangan = ''; // Clear notes
                        this.formData.barcode_in = ''; // Clear barcodes
                        this.formData.barcode_out = '';
                        this.formData.id_in = '';

                        // You might want to trigger UI updates for select elements here
                        // e.g., if using custom select components that need an update hook
                    } catch (e) {
                        console.error("Error parsing last transaction data from local storage:", e);
                        localStorage.removeItem('lastTransactionData'); // Clear bad data
                    }
                }
            },

            resetFormForContinue() {
                this.formData.ekor_pack = '';
                this.formData.quantity_kg = '';
                this.formData.keterangan = '';
                this.formData.barcode_in = '';
                this.formData.barcode_out = '';
                this.formData.id_in = '';
            },

            showError(message) {
                this.errorMessage = message;
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: message,
                    showConfirmButton: true
                });
            }
        }));
    });
</script>