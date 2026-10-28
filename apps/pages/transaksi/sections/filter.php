<div class="mb-4 p-4 bg-white rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-sm sm:text-lg font-semibold text-gray-800">Filter Transaksi</h2>
        <button @click="showFilters = !showFilters"
            class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-full bg-blue-500 text-white text-xs sm:text-sm hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-75 transition duration-150 ease-in-out flex items-center space-x-1 sm:space-x-2"
            :aria-expanded="showFilters.toString()" aria-controls="filter-panel">
            <template x-if="showFilters">
                <i class="fas fa-filter text-xs"></i> </template>
            <template x-if="!showFilters">
                <i class="fas fa-filter text-xs"></i> </template>
            <span x-text="showFilters ? 'Sembunyikan Filter' : 'Tampilkan Filter'"></span>
        </button>
    </div>

    <div x-show="showFilters" x-cloak id="filter-panel"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform -translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform -translate-y-2">

        <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-x-3 gap-y-3 sm:gap-4">
            <div class="md:col-span-2 lg:col-span-1">
                <label for="filterDateStart" class="block text-gray-700 text-xs sm:text-sm font-medium mb-0.5">Tanggal Awal:</label>
                <input type="date" x-model="filters.tanggal_awal" @change="fetchTransactions"
                    class="w-full p-1.5 sm:p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150 ease-in-out text-xs sm:text-sm">
            </div>

            <div class="md:col-span-2 lg:col-span-1">
                <label for="filterDateEnd" class="block text-gray-700 text-xs sm:text-sm font-medium mb-0.5">Tanggal Akhir:</label>
                <input type="date" x-model="filters.tanggal_akhir" @change="fetchTransactions"
                    class="w-full p-1.5 sm:p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150 ease-in-out text-xs sm:text-sm">
            </div>

            <div>
                <label for="filterType" class="block text-gray-700 text-xs sm:text-sm font-medium mb-0.5">Type Transaksi:</label>
                <select x-model="filters.type_transaksi" @change="fetchTransactions"
                    class="w-full p-1.5 sm:p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150 ease-in-out text-xs sm:text-sm">
                    <option value="" class="text-xs sm:text-sm">-Semua-</option>
                    <option value="in" class="text-xs sm:text-sm">Masuk (In)</option>
                    <option value="out" class="text-xs sm:text-sm">Keluar (Out)</option>
                    <option value="transfer" class="text-xs sm:text-sm">Transfer</option>
                    <option value="adjustment" class="text-xs sm:text-sm">Adjustment</option>
                    <option value="adjustment-new" class="text-xs sm:text-sm">Adjustment-New</option>
                </select>
            </div>

            <div>
                <label for="filterUser" class="block text-gray-700 text-xs sm:text-sm font-medium mb-0.5">User:</label>
                <select id="filterUser" x-model="filters.user_name" @change="fetchTransactions"
                    class="w-full p-1.5 sm:p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150 ease-in-out text-xs sm:text-sm">
                    <option value="" class="text-xs sm:text-sm">-Semua-</option>
                    <template x-for="user in filterOptions.users" :key="user.nama">
                        <option :value="user.nama" x-text="user.nama" class="text-xs sm:text-sm"></option>
                    </template>
                </select>
            </div>

            <div>
                <label for="filterFrom" class="block text-xs sm:text-sm font-medium text-gray-700 mb-0.5">Dari Lokasi:</label>
                <select id="filterFrom" x-model="filters.from_location" @change="fetchTransactions"
                    class="w-full p-1.5 sm:p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150 ease-in-out text-xs sm:text-sm">
                    <option value="" class="text-xs sm:text-sm">-Semua-</option>
                    <template x-for="location in filterOptions.locations" :key="location.lokasi">
                        <option :value="location.lokasi" x-text="location.lokasi" class="text-xs sm:text-sm"></option>
                    </template>
                </select>
            </div>

            <div>
                <label for="filterTo" class="block text-xs sm:text-sm font-medium text-gray-700 mb-0.5">Tujuan Lokasi:</label>
                <select id="filterTo" x-model="filters.to_location" @change="fetchTransactions"
                    class="w-full p-1.5 sm:p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150 ease-in-out text-xs sm:text-sm">
                    <option value="" class="text-xs sm:text-sm">-Semua-</option>
                    <template x-for="location in filterOptions.locations" :key="location.lokasi">
                        <option :value="location.lokasi" x-text="location.lokasi" class="text-xs sm:text-sm"></option>
                    </template>
                </select>
            </div>

            <div>
                <label for="filterStorage" class="block text-xs sm:text-sm font-medium text-gray-700 mb-0.5">Storage:</label>
                <select id="filterStorage" x-model="filters.storage" @change="fetchTransactions"
                    class="w-full p-1.5 sm:p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150 ease-in-out text-xs sm:text-sm">
                    <option value="" class="text-xs sm:text-sm">-Semua-</option>
                    <option value="chiller 1" class="text-xs sm:text-sm">Chiller 1</option>
                    <option value="chiller 2" class="text-xs sm:text-sm">Chiller 2</option>
                    <option value="chiller 3" class="text-xs sm:text-sm">Chiller 3</option>
                </select>
            </div>

            <div>
                <label for="filterItem" class="block text-xs sm:text-sm font-medium text-gray-700 mb-0.5">Item:</label>
                <select id="filterItem" x-ref="filterItem" x-model="filters.item" multiple="multiple"
                    class="w-full p-1.5 sm:p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150 ease-in-out text-xs sm:text-sm">
                </select>
            </div>

            <div>
                <label for="filterSubitem" class="block text-xs sm:text-sm font-medium text-gray-700 mb-0.5">Subitem:</label>
                <select id="filterSubitem" x-ref="filterSubitem" x-model="filters.subitem" multiple="multiple"
                    class="w-full p-1.5 sm:p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150 ease-in-out text-xs sm:text-sm">
                    <option value="" class="text-xs sm:text-sm">-Semua-</option>
                    <option value="__NO_SUBITEM__" class="text-xs sm:text-sm">-Tanpa Subitem-</option>
                </select>
            </div>

            <div>
                <label for="filterLimit" class="block text-xs sm:text-sm font-medium text-gray-700 mb-0.5">Limit:</label>
                <select id="filterLimit" x-model="filters.limit" @change="fetchTransactions"
                    class="w-full p-1.5 sm:p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150 ease-in-out text-xs sm:text-sm">
                    <option value="5" class="text-xs sm:text-sm">5</option>
                    <option value="500" class="text-xs sm:text-sm">500</option>
                    <option value="1000" class="text-xs sm:text-sm">1000</option>
                    <option value="2000" class="text-xs sm:text-sm">2000</option>
                    <option value="5000" class="text-xs sm:text-sm">5000</option>
                    <option value="10000" class="text-xs sm:text-sm">10000</option>
                </select>
            </div>

            <div>
                <label for="filterBarcode" class="block text-xs sm:text-sm font-medium text-gray-700 mb-0.5">Barcode:</label>
                <input
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    id="filterBarcode"
                    x-model="filters.barcode"
                    x-on:keydown.enter="fetchTransactions()"
                    placeholder="Cari Barcode..."
                    class="w-full p-1.5 sm:p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-xs sm:text-sm">
            </div>

            <div class="md:col-span-2 lg:col-span-3 flex justify-end mt-2">
                <button @click="resetFilters()"
                    class="px-4 py-1.5 sm:px-5 sm:py-2 bg-gray-200 text-gray-700 text-xs sm:text-sm rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 transition duration-300 ease-in-out">
                    Reset Filter
                </button>
            </div>
        </div>
    </div>
</div>