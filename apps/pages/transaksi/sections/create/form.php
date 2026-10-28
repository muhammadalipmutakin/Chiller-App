<form @submit.prevent="submitForm($event)" id="transactionForm" class="p-4 sm:p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mb-4 md:mb-6">
        <div class="space-y-3 sm:space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                <label for="barcodeOut" class="w-20 text-gray-700 font-medium text-xs sm:text-sm">
                    Barcode Out <span class="text-[10px] text-gray-400 font-normal block sm:inline">(Maks 4)</span>
                </label>
                <div class="flex-1">
                    <input type="number" id="barcodeOut" x-model="formData.barcodeOut"
                        max="9999"
                        oninput="if(this.value.length > 4) this.value = this.value.slice(0, 4);"
                        class="w-full p-1.5 sm:p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs sm:text-sm"
                        placeholder="Tambahkan barcodeOut (maks. 4 digit)" @keydown.enter="fetchDataOut()">
                </div>
            </div>


            <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2 mb-2 md:mb-4">
                <label for="Tanggal" class="w-20 text-gray-700 font-medium text-xs sm:text-sm">Tanggal</label>
                <input type="date" id="Tanggal" x-model="formData.date"
                    class="flex-1 p-1.5 sm:p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs sm:text-sm"
                    required>
            </div>

            <div class="flex flex-col gap-3 sm:gap-4">
                <div class="w-full flex items-center gap-2">
                    <label for="selectFromLocation" class="block text-gray-700 w-20 font-medium text-xs sm:text-sm">Dari</label>
                    <select id="selectFromLocation" x-ref="selectFromLocation" x-model="formData.from_location"
                        class="p-1 sm:p-1.5 w-full border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs sm:text-sm"
                        required>
                        <option value="">Pilih Lokasi</option>
                        <template x-for="location in locations" :key="location.lokasi">
                            <option :value="location.lokasi" x-text="location.lokasi"></option>
                        </template>
                    </select>
                </div>

                <div class="w-full flex items-center gap-2">
                    <label for="selectToLocation" class="block text-gray-700 w-20 font-medium text-xs sm:text-sm">Tujuan</label>
                    <select id="selectToLocation" x-ref="selectToLocation" x-model="formData.to_location"
                        class="p-1 sm:p-1.5 w-full border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs sm:text-sm"
                        required>
                        <option value="">Pilih Lokasi</option>
                        <template x-for="location in locations" :key="location.lokasi">
                            <option :value="location.lokasi" x-text="location.lokasi"></option>
                        </template>
                    </select>
                </div>
            </div>
        </div>

        <div class="space-y-3 sm:space-y-4 pt-4 md:pt-0">
            <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2 mb-1">
                <label for="item" class="w-20 text-gray-700 font-medium text-xs sm:text-sm">Item</label>
                <div class="flex-1 relative">
                    <select id="selectItem" x-ref="selectItem" style="width: 100%;"
                        class="p-1.5 sm:p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs sm:text-sm" required>
                        <option value="">Pilih Item</option>
                        <template x-for="item in items" :key="item.item">
                            <option :value="item.item"
                                :data-stock-ekor="item.ekor_pack || 0"
                                :data-stock-kg="item.quantity_kg || 0"
                                x-text="`${item.item} (Stok: ${item.ekor_pack || 0} Ekor, ${item.quantity_kg || 0} Kg)`"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div id=itemBarcode>
            </div>

            <div class="text-xs text-gray-600 mt-1 mb-1">
                Jika Anda tidak menemukan item yang dimaksud, pastikan item tersebut sudah aktif.
                <a href="?page=master/item" class="text-blue-600 hover:underline font-medium ml-1">
                    Aktifkan Item
                </a>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2 w-full mt-3"
                x-show="showSubitemField">
                <label for="subitem" class="w-20 text-gray-700 font-medium text-xs sm:text-sm">Sub Item</label>
                <div class="flex-1 relative">
                    <select id="selectSubitem" x-ref="selectSubitem" x-model="formData.subitem"
                        class="p-1 sm:p-1.5 w-full border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs sm:text-sm">
                        <option value="">Pilih Sub Item (Opsional)</option>
                        <template x-for="subitem in subitems" :key="subitem.subitem">
                            <option :value="subitem.subitem" x-text="subitem.subitem"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2" x-show="showKeteranganField">
                <label for="keterangan" class="w-20 text-gray-700 font-medium text-xs sm:text-sm">Keterangan</label>
                <input type="text" id="keterangan" x-model="formData.keterangan"
                    class="flex-1 p-1.5 sm:p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs sm:text-sm"
                    placeholder="Tambahkan keterangan (opsional)">
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2" x-show="showBarcodeInField">
                <label for="barcodeIn" class="w-20 text-gray-700 font-medium text-xs sm:text-sm">
                    Barcode In <span class="text-[10px] text-gray-400 font-normal block sm:inline">(Maks 4)</span>
                </label>
                <div class="flex-1">
                    <input type="number" id="barcodeIn" x-model="formData.barcodeIn"
                        max="9999"
                        oninput="if(this.value.length > 4) this.value = this.value.slice(0, 4);"
                        class="w-full p-1.5 sm:p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs sm:text-sm"
                        placeholder="Tambahkan barcodeIn (maks. 4 digit)">
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 md:gap-6 mb-6">
        <div class="flex flex-col gap-2 p-2 border border-gray-200 rounded-lg shadow-sm">
            <label for="ekor" class="text-gray-700 font-medium text-sm">Ekor/Pack</label>
            <input type="text" id="ekor" x-model="formData.ekor_pack"
                class="p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm w-full"
                placeholder="Jumlah ekor atau pack" readonly required>

            <div class="text-center">
                <div class="grid grid-cols-8 gap-1 mt-2">
                    <button type="button" @click="appendValue('1', 'ekor_pack')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 text-xs">1</button>
                    <button type="button" @click="appendValue('2', 'ekor_pack')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 text-xs">2</button>
                    <button type="button" @click="appendValue('3', 'ekor_pack')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 text-xs">3</button>
                    <button type="button" @click="calculate('+', 'ekor')" class="col-span-1 px-1 py-1 rounded-md border border-gray-400 operator bg-orange-500 text-white text-xs"><i class="fa-solid fa-plus"></i></button>
                    <button type="button" @click="calculate('-', 'ekor')" class="col-span-1 px-1 py-1 rounded-md border border-gray-400 operator bg-orange-500 text-white text-xs"><i class="fa-solid fa-minus"></i></button>
                </div>

                <div class="grid grid-cols-8 gap-1 mt-2">
                    <button type="button" @click="appendValue('4', 'ekor_pack')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 text-xs">4</button>
                    <button type="button" @click="appendValue('5', 'ekor_pack')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 text-xs">5</button>
                    <button type="button" @click="appendValue('6', 'ekor_pack')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 text-xs">6</button>
                    <button type="button" @click="calculate('*', 'ekor')" class="col-span-1 px-1 py-1 rounded-md border border-gray-400 operator bg-orange-500 text-white text-xs"><i class="fa-solid fa-xmark"></i></button>
                    <button type="button" @click="calculate('/', 'ekor')" class="col-span-1 px-1 py-1 rounded-md border border-gray-400 operator bg-orange-500 text-white text-xs"><i class="fa-solid fa-divide"></i></button>
                </div>

                <div class="grid grid-cols-8 gap-1 mt-2">
                    <button type="button" @click="appendValue('7', 'ekor_pack')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 text-xs">7</button>
                    <button type="button" @click="appendValue('8', 'ekor_pack')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 text-xs">8</button>
                    <button type="button" @click="appendValue('9', 'ekor_pack')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 text-xs">9</button>
                    <button type="button" @click="calculate('=', 'ekor')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 equals bg-green-500 text-white text-xs"><i class="fa-solid fa-equals"></i></button>
                </div>

                <div class="grid grid-cols-8 gap-1 mt-2">
                    <button type="button" @click="appendValue('C', 'ekor_pack')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 bg-red-500 text-white clear text-xs">C</button>
                    <button type="button" @click="appendValue('0', 'ekor_pack')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 text-xs">0</button>
                    <button type="button" @click="appendValue('.', 'ekor_pack')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 text-xs">.</button>
                    <button type="button" @click="calculate('⌫', 'ekor')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 backspace bg-yellow-500 text-white text-xs">
                        <i class="fa-solid fa-backspace"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-2 p-2 border border-gray-200 rounded-lg shadow-sm">
            <label for="berat" class="text-gray-700 font-medium text-sm">Berat (Kg)</label>
            <input type="text" id="berat" x-model="formData.quantity_kg"
                class="p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm w-full"
                placeholder="Berat dalam Kilogram" readonly required>

            <div class="text-center">
                <div class="grid grid-cols-8 gap-1 mt-2">
                    <button type="button" @click="appendValue('1', 'quantity_kg')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 text-xs">1</button>
                    <button type="button" @click="appendValue('2', 'quantity_kg')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 text-xs">2</button>
                    <button type="button" @click="appendValue('3', 'quantity_kg')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 text-xs">3</button>
                    <button type="button" @click="calculate('+', 'berat')" class="col-span-1 px-1 py-1 rounded-md border border-gray-400 operator bg-orange-500 text-white text-xs"><i class="fa-solid fa-plus"></i></button>
                    <button type="button" @click="calculate('-', 'berat')" class="col-span-1 px-1 py-1 rounded-md border border-gray-400 operator bg-orange-500 text-white text-xs"><i class="fa-solid fa-minus"></i></button>
                </div>

                <div class="grid grid-cols-8 gap-1 mt-2">
                    <button type="button" @click="appendValue('4', 'quantity_kg')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 text-xs">4</button>
                    <button type="button" @click="appendValue('5', 'quantity_kg')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 text-xs">5</button>
                    <button type="button" @click="appendValue('6', 'quantity_kg')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 text-xs">6</button>
                    <button type="button" @click="calculate('*', 'berat')" class="col-span-1 px-1 py-1 rounded-md border border-gray-400 operator bg-orange-500 text-white text-xs"><i class="fa-solid fa-xmark"></i></button>
                    <button type="button" @click="calculate('/', 'berat')" class="col-span-1 px-1 py-1 rounded-md border border-gray-400 operator bg-orange-500 text-white text-xs"><i class="fa-solid fa-divide"></i></button>
                </div>

                <div class="grid grid-cols-8 gap-1 mt-2">
                    <button type="button" @click="appendValue('7', 'quantity_kg')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 text-xs">7</button>
                    <button type="button" @click="appendValue('8', 'quantity_kg')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 text-xs">8</button>
                    <button type="button" @click="appendValue('9', 'quantity_kg')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 text-xs">9</button>
                    <button type="button" @click="calculate('=', 'berat')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 equals bg-green-500 text-white text-xs"><i class="fa-solid fa-equals"></i></button>
                </div>

                <div class="grid grid-cols-8 gap-1 mt-2">
                    <button type="button" @click="appendValue('C', 'quantity_kg')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 clear bg-red-500 text-white clear text-xs">C</button>
                    <button type="button" @click="appendValue('0', 'quantity_kg')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 text-xs">0</button>
                    <button type="button" @click="appendValue('.', 'quantity_kg')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 text-xs">.</button>
                    <button type="button" @click="calculate('⌫', 'berat')" class="col-span-2 px-1 py-1 rounded-md border border-gray-400 backspace bg-yellow-500 text-white text-xs">
                        <i class="fa-solid fa-backspace"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row gap-3 mt-4">
        <button type="button" @click="submitForm($event, true)"
            class="flex-1 sm:flex-none px-4 py-2 sm:px-5 sm:py-2.5 bg-blue-700 text-white font-semibold rounded-lg shadow-md hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:ring-opacity-75 transition duration-300 text-xs sm:text-sm"
            :disabled="isLoading">
            <span x-show="!isLoading">Simpan & Lanjutkan</span>
            <span x-show="isLoading" class="flex items-center justify-center text-xs sm:text-sm">
                <i class="fas fa-spinner fa-spin mr-2"></i>
                Menyimpan...
            </span>
        </button>
        <button type="submit"
            class="flex-1 sm:flex-none px-4 py-2 sm:px-5 sm:py-2.5 bg-blue-500 text-white font-semibold rounded-lg shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-75 transition duration-300 text-xs sm:text-sm"
            :disabled="isLoading">
            <span x-show="!isLoading">Simpan</span>
            <span x-show="isLoading" class="flex items-center justify-center text-xs sm:text-sm">
                <i class="fas fa-spinner fa-spin mr-2"></i>
                Menyimpan...
            </span>
        </button>
    </div>
</form>