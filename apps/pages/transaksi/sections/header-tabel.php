<tr class="bg-gray-50">
    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200 sticky top-0 bg-gray-50 z-20">No</th>

    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200 sticky top-0 bg-gray-50 z-20">
        <div x-data="{ showFilterDropdown: false, filterSearch: '' }" @click.away="showFilterDropdown = false" class="relative inline-block text-left">
            <div class="flex items-center justify-center">
                <span>Code</span>
                <button type="button" @click="showFilterDropdown = !showFilterDropdown" class="ml-1 text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="h-3 w-3" :class="{ 'text-blue-500': activeFilters.barcode && activeFilters.barcode.length > 0 }" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 6h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div x-show="showFilterDropdown" x-transition class="block origin-top-left absolute left-0 mt-1 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 p-1">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-semibold">Filter Barcode</span>
                    <button x-show="activeFilters.barcode && activeFilters.barcode.length > 0" @click="resetFilter('barcode'); showFilterDropdown = false" class="text-xs text-red-500 hover:text-red-700 font-semibold">Reset</button>
                </div>
                <input type="text" x-model.debounce.300ms="filterSearch" placeholder="Cari..." class="w-full px-2 py-1 mb-1 text-xs border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="max-h-48 overflow-y-auto">
                    <template x-for="value in getUniqueValues('barcode').filter(v => String(v).toLowerCase().includes(filterSearch.toLowerCase()))" :key="value">
                        <div class="flex items-center p-1 hover:bg-gray-100 rounded">
                            <input type="checkbox" :id="'filter-barcode-' + value" :checked="isFiltered('barcode', value)" @click.stop="toggleFilter('barcode', value)" class="mr-1 rounded text-blue-600 focus:ring-blue-500">
                            <label :for="'filter-barcode-' + value" class="text-xs text-gray-700 w-full cursor-pointer" x-text="value"></label>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </th>

    <!-- Kolom Baru: Info Produksi -->
    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200 sticky top-0 bg-gray-50 z-20">
        <div x-data="{ showFilterDropdown: false, filterSearch: '' }" @click.away="showFilterDropdown = false" class="relative inline-block text-left">
            <div class="flex items-center justify-center">
                <span>Produksi</span>
                <button type="button" @click="showFilterDropdown = !showFilterDropdown" class="ml-1 text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="h-3 w-3" :class="{ 'text-blue-500': activeFilters.info_produksi && activeFilters.info_produksi.length > 0 }" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 6h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div x-show="showFilterDropdown" x-transition class="block origin-top-left absolute left-0 mt-1 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 p-1">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-semibold">Filter Info Produksi</span>
                    <button x-show="activeFilters.info_produksi && activeFilters.info_produksi.length > 0" @click="resetFilter('info_produksi'); showFilterDropdown = false" class="text-xs text-red-500 hover:text-red-700 font-semibold">Reset</button>
                </div>
                <input type="text" x-model.debounce.300ms="filterSearch" placeholder="Cari..." class="w-full px-2 py-1 mb-1 text-xs border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="max-h-48 overflow-y-auto">
                    <template x-for="value in getUniqueValues('info_produksi').filter(v => String(v).toLowerCase().includes(filterSearch.toLowerCase()))" :key="value">
                        <div class="flex items-center p-1 hover:bg-gray-100 rounded">
                            <input type="checkbox" :id="'filter-produksi-' + value" :checked="isFiltered('info_produksi', value)" @click.stop="toggleFilter('info_produksi', value)" class="mr-1 rounded text-blue-600 focus:ring-blue-500">
                            <label :for="'filter-produksi-' + value" class="text-xs text-gray-700 w-full cursor-pointer" x-text="value"></label>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </th>

    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200 sticky top-0 bg-gray-50 z-20">
        <div x-data="{ showFilterDropdown: false, filterSearch: '' }" @click.away="showFilterDropdown = false" class="relative inline-block text-left">
            <div class="flex items-center justify-center">
                <span>Tanggal</span>
                <button type="button" @click="showFilterDropdown = !showFilterDropdown" class="ml-1 text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="h-3 w-3" :class="{ 'text-blue-500': activeFilters.date && activeFilters.date.length > 0 }" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 6h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div x-show="showFilterDropdown" x-transition class="block origin-top-left absolute left-0 mt-1 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 p-1">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-semibold">Filter Tanggal</span>
                    <button x-show="activeFilters.date && activeFilters.date.length > 0" @click="resetFilter('date'); showFilterDropdown = false" class="text-xs text-red-500 hover:text-red-700 font-semibold">Reset</button>
                </div>
                <input type="text" x-model.debounce.300ms="filterSearch" placeholder="Cari..." class="w-full px-2 py-1 mb-1 text-xs border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="max-h-48 overflow-y-auto">
                    <template x-for="value in getUniqueValues('date').filter(v => String(v).toLowerCase().includes(filterSearch.toLowerCase()))" :key="value">
                        <div class="flex items-center p-1 hover:bg-gray-100 rounded">
                            <input type="checkbox" :id="'filter-date-' + value" :checked="isFiltered('date', value)" @click.stop="toggleFilter('date', value)" class="mr-1 rounded text-blue-600 focus:ring-blue-500">
                            <label :for="'filter-date-' + value" class="text-xs text-gray-700 w-full cursor-pointer" x-text="value"></label>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </th>

    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200 sticky top-0 bg-gray-50 z-20">
        <div x-data="{ showFilterDropdown: false, filterSearch: '' }" @click.away="showFilterDropdown = false" class="relative inline-block text-left">
            <div class="flex items-center justify-center">
                <span>Item</span>
                <button type="button" @click="showFilterDropdown = !showFilterDropdown" class="ml-1 text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="h-3 w-3" :class="{ 'text-blue-500': activeFilters.item && activeFilters.item.length > 0 }" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 6h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div x-show="showFilterDropdown" x-transition class="block origin-top-left absolute left-0 mt-1 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 p-1">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-semibold">Filter Item</span>
                    <button x-show="activeFilters.item && activeFilters.item.length > 0" @click="resetFilter('item'); showFilterDropdown = false" class="text-xs text-red-500 hover:text-red-700 font-semibold">Reset</button>
                </div>
                <input type="text" x-model.debounce.300ms="filterSearch" placeholder="Cari..." class="w-full px-2 py-1 mb-1 text-xs border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="max-h-48 overflow-y-auto">
                    <template x-for="value in getUniqueValues('item').filter(v => String(v).toLowerCase().includes(filterSearch.toLowerCase()))" :key="value">
                        <div class="flex items-center p-1 hover:bg-gray-100 rounded">
                            <input type="checkbox" :id="'filter-item-' + value" :checked="isFiltered('item', value)" @click.stop="toggleFilter('item', value)" class="mr-1 rounded text-blue-600 focus:ring-blue-500">
                            <label :for="'filter-item-' + value" class="text-xs text-gray-700 w-full cursor-pointer" x-text="value"></label>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </th>

    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200 sticky top-0 bg-gray-50 z-20">
        <div x-data="{ showFilterDropdown: false, filterSearch: '' }" @click.away="showFilterDropdown = false" class="relative inline-block text-left">
            <div class="flex flex-col">
                <div class="flex items-center justify-center">
                    <span>E/P</span>
                    <button type="button" @click="showFilterDropdown = !showFilterDropdown" class="ml-1 text-gray-400 hover:text-gray-600 focus:outline-none">
                        <svg class="h-3 w-3" :class="{ 'text-blue-500': activeFilters.ekor_pack && activeFilters.ekor_pack.length > 0 }" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 6h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <span class="text-xs font-normal text-right transition-colors duration-200"
                    :class="{
                        'text-emerald-600': totals.ekor_pack > 0,
                        'text-rose-600': totals.ekor_pack < 0,
                        'text-gray-600': totals.ekor_pack === 0
                    }"
                    x-text="formatNumber(totals.ekor_pack, 0)"></span>
            </div>
            <div x-show="showFilterDropdown" x-transition class="block origin-top-left absolute left-0 mt-1 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 p-1">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-semibold">Filter E/P</span>
                    <button x-show="activeFilters.ekor_pack && activeFilters.ekor_pack.length > 0" @click="resetFilter('ekor_pack'); showFilterDropdown = false" class="text-xs text-red-500 hover:text-red-700 font-semibold">Reset</button>
                </div>
                <input type="text" x-model.debounce.300ms="filterSearch" placeholder="Cari..." class="w-full px-2 py-1 mb-1 text-xs border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="max-h-48 overflow-y-auto">
                    <template x-for="value in getUniqueValues('ekor_pack').filter(v => String(v).toLowerCase().includes(filterSearch.toLowerCase()))" :key="value">
                        <div class="flex items-center p-1 hover:bg-gray-100 rounded">
                            <input type="checkbox" :id="'filter-ep-' + value" :checked="isFiltered('ekor_pack', value)" @click.stop="toggleFilter('ekor_pack', value)" class="mr-1 rounded text-blue-600 focus:ring-blue-500">
                            <label :for="'filter-ep-' + value" class="text-xs text-gray-700 w-full cursor-pointer" x-text="value"></label>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </th>

    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200 sticky top-0 bg-gray-50 z-20">
        <div x-data="{ showFilterDropdown: false, filterSearch: '' }" @click.away="showFilterDropdown = false" class="relative inline-block text-left">
            <div class="flex flex-col">
                <div class="flex items-center justify-center">
                    <span>Kg</span>
                    <button type="button" @click="showFilterDropdown = !showFilterDropdown" class="ml-1 text-gray-400 hover:text-gray-600 focus:outline-none">
                        <svg class="h-3 w-3" :class="{ 'text-blue-500': activeFilters.quantity_kg && activeFilters.quantity_kg.length > 0 }" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 6h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <span class="text-xs font-normal text-right transition-colors duration-200"
                    :class="{
                        'text-emerald-600': totals.quantity_kg > 0,
                        'text-rose-600': totals.quantity_kg < 0,
                        'text-gray-600': totals.quantity_kg === 0
                    }"
                    x-text="formatNumber(totals.quantity_kg, 2)"></span>
            </div>
            <div x-show="showFilterDropdown" x-transition class="block origin-top-left absolute left-0 mt-1 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 p-1">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-semibold">Filter Kg</span>
                    <button x-show="activeFilters.quantity_kg && activeFilters.quantity_kg.length > 0" @click="resetFilter('quantity_kg'); showFilterDropdown = false" class="text-xs text-red-500 hover:text-red-700 font-semibold">Reset</button>
                </div>
                <input type="text" x-model.debounce.300ms="filterSearch" placeholder="Cari..." class="w-full px-2 py-1 mb-1 text-xs border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="max-h-48 overflow-y-auto">
                    <template x-for="value in getUniqueValues('quantity_kg').filter(v => String(v).toLowerCase().includes(filterSearch.toLowerCase()))" :key="value">
                        <div class="flex items-center p-1 hover:bg-gray-100 rounded">
                            <input type="checkbox" :id="'filter-kg-' + value" :checked="isFiltered('quantity_kg', value)" @click.stop="toggleFilter('quantity_kg', value)" class="mr-1 rounded text-blue-600 focus:ring-blue-500">
                            <label :for="'filter-kg-' + value" class="text-xs text-gray-700 w-full cursor-pointer" x-text="value"></label>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </th>

    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200 sticky top-0 bg-gray-50 z-20">
        <div x-data="{ showFilterDropdown: false, filterSearch: '' }" @click.away="showFilterDropdown = false" class="relative inline-block text-left">
            <div class="flex flex-col">
                <div class="flex items-center justify-center">
                    <span>Stok E/P</span>
                    <button type="button" @click="showFilterDropdown = !showFilterDropdown" class="ml-1 text-gray-400 hover:text-gray-600 focus:outline-none">
                        <svg class="h-3 w-3" :class="{ 'text-blue-500': activeFilters.stock_ekor && activeFilters.stock_ekor.length > 0 }" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 6h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
            <div x-show="showFilterDropdown" x-transition class="block origin-top-left absolute left-0 mt-1 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 p-1">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-semibold">Filter Stok E/P</span>
                    <button x-show="activeFilters.stock_ekor && activeFilters.stock_ekor.length > 0" @click="resetFilter('stock_ekor'); showFilterDropdown = false" class="text-xs text-red-500 hover:text-red-700 font-semibold">Reset</button>
                </div>
                <input type="text" x-model.debounce.300ms="filterSearch" placeholder="Cari..." class="w-full px-2 py-1 mb-1 text-xs border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="max-h-48 overflow-y-auto">
                    <template x-for="value in getUniqueValues('stock_ekor').filter(v => String(v).toLowerCase().includes(filterSearch.toLowerCase()))" :key="value">
                        <div class="flex items-center p-1 hover:bg-gray-100 rounded">
                            <input type="checkbox" :id="'filter-se-' + value" :checked="isFiltered('stock_ekor', value)" @click.stop="toggleFilter('stock_ekor', value)" class="mr-1 rounded text-blue-600 focus:ring-blue-500">
                            <label :for="'filter-se-' + value" class="text-xs text-gray-700 w-full cursor-pointer" x-text="value"></label>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </th>

    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200 sticky top-0 bg-gray-50 z-20">
        <div x-data="{ showFilterDropdown: false, filterSearch: '' }" @click.away="showFilterDropdown = false" class="relative inline-block text-left">
            <div class="flex flex-col">
                <div class="flex items-center justify-center">
                    <span>Stok KG</span>
                    <button type="button" @click="showFilterDropdown = !showFilterDropdown" class="ml-1 text-gray-400 hover:text-gray-600 focus:outline-none">
                        <svg class="h-3 w-3" :class="{ 'text-blue-500': activeFilters.stock_berat && activeFilters.stock_berat.length > 0 }" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 6h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
            <div x-show="showFilterDropdown" x-transition class="block origin-top-left absolute left-0 mt-1 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 p-1">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-semibold">Filter Stok KG</span>
                    <button x-show="activeFilters.stock_berat && activeFilters.stock_berat.length > 0" @click="resetFilter('stock_berat'); showFilterDropdown = false" class="text-xs text-red-500 hover:text-red-700 font-semibold">Reset</button>
                </div>
                <input type="text" x-model.debounce.300ms="filterSearch" placeholder="Cari..." class="w-full px-2 py-1 mb-1 text-xs border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="max-h-48 overflow-y-auto">
                    <template x-for="value in getUniqueValues('stock_berat').filter(v => String(v).toLowerCase().includes(filterSearch.toLowerCase()))" :key="value">
                        <div class="flex items-center p-1 hover:bg-gray-100 rounded">
                            <input type="checkbox" :id="'filter-sk-' + value" :checked="isFiltered('stock_berat', value)" @click.stop="toggleFilter('stock_berat', value)" class="mr-1 rounded text-blue-600 focus:ring-blue-500">
                            <label :for="'filter-sk-' + value" class="text-xs text-gray-700 w-full cursor-pointer" x-text="value"></label>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </th>

    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200 sticky top-0 bg-gray-50 z-20">
        <div x-data="{ showFilterDropdown: false, filterSearch: '' }" @click.away="showFilterDropdown = false" class="relative inline-block text-left">
            <div class="flex items-center justify-center">
                <span>Dari</span>
                <button type="button" @click="showFilterDropdown = !showFilterDropdown" class="ml-1 text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="h-3 w-3" :class="{ 'text-blue-500': activeFilters.from_location && activeFilters.from_location.length > 0 }" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 6h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div x-show="showFilterDropdown" x-transition class="block origin-top-left absolute left-0 mt-1 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 p-1">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-semibold">Filter Lokasi Asal</span>
                    <button x-show="activeFilters.from_location && activeFilters.from_location.length > 0" @click="resetFilter('from_location'); showFilterDropdown = false" class="text-xs text-red-500 hover:text-red-700 font-semibold">Reset</button>
                </div>
                <input type="text" x-model.debounce.300ms="filterSearch" placeholder="Cari..." class="w-full px-2 py-1 mb-1 text-xs border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="max-h-48 overflow-y-auto">
                    <template x-for="value in getUniqueValues('from_location').filter(v => String(v).toLowerCase().includes(filterSearch.toLowerCase()))" :key="value">
                        <div class="flex items-center p-1 hover:bg-gray-100 rounded">
                            <input type="checkbox" :id="'filter-from-' + value" :checked="isFiltered('from_location', value)" @click.stop="toggleFilter('from_location', value)" class="mr-1 rounded text-blue-600 focus:ring-blue-500">
                            <label :for="'filter-from-' + value" class="text-xs text-gray-700 w-full cursor-pointer" x-text="value"></label>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </th>

    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200 sticky top-0 bg-gray-50 z-20">
        <div x-data="{ showFilterDropdown: false, filterSearch: '' }" @click.away="showFilterDropdown = false" class="relative inline-block text-left">
            <div class="flex items-center justify-center">
                <span>Tujuan</span>
                <button type="button" @click="showFilterDropdown = !showFilterDropdown" class="ml-1 text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="h-3 w-3" :class="{ 'text-blue-500': activeFilters.to_location && activeFilters.to_location.length > 0 }" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 6h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div x-show="showFilterDropdown" x-transition class="block origin-top-left absolute left-0 mt-1 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 p-1">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-semibold">Filter Lokasi Tujuan</span>
                    <button x-show="activeFilters.to_location && activeFilters.to_location.length > 0" @click="resetFilter('to_location'); showFilterDropdown = false" class="text-xs text-red-500 hover:text-red-700 font-semibold">Reset</button>
                </div>
                <input type="text" x-model.debounce.300ms="filterSearch" placeholder="Cari..." class="w-full px-2 py-1 mb-1 text-xs border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="max-h-48 overflow-y-auto">
                    <template x-for="value in getUniqueValues('to_location').filter(v => String(v).toLowerCase().includes(filterSearch.toLowerCase()))" :key="value">
                        <div class="flex items-center p-1 hover:bg-gray-100 rounded">
                            <input type="checkbox" :id="'filter-to-' + value" :checked="isFiltered('to_location', value)" @click.stop="toggleFilter('to_location', value)" class="mr-1 rounded text-blue-600 focus:ring-blue-500">
                            <label :for="'filter-to-' + value" class="text-xs text-gray-700 w-full cursor-pointer" x-text="value"></label>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </th>

    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200 sticky top-0 bg-gray-50 z-20">
        <div x-data="{ showFilterDropdown: false, filterSearch: '' }" @click.away="showFilterDropdown = false" class="relative inline-block text-left">
            <div class="flex items-center justify-center">
                <span>Storage</span>
                <button type="button" @click="showFilterDropdown = !showFilterDropdown" class="ml-1 text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="h-3 w-3" :class="{ 'text-blue-500': activeFilters.storage && activeFilters.storage.length > 0 }" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 6h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div x-show="showFilterDropdown" x-transition class="block origin-top-left absolute left-0 mt-1 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 p-1">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-semibold">Filter Storage</span>
                    <button x-show="activeFilters.storage && activeFilters.storage.length > 0" @click="resetFilter('storage'); showFilterDropdown = false" class="text-xs text-red-500 hover:text-red-700 font-semibold">Reset</button>
                </div>
                <input type="text" x-model.debounce.300ms="filterSearch" placeholder="Cari..." class="w-full px-2 py-1 mb-1 text-xs border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="max-h-48 overflow-y-auto">
                    <template x-for="value in getUniqueValues('storage').filter(v => String(v).toLowerCase().includes(filterSearch.toLowerCase()))" :key="value">
                        <div class="flex items-center p-1 hover:bg-gray-100 rounded">
                            <input type="checkbox" :id="'filter-storage-' + value" :checked="isFiltered('storage', value)" @click.stop="toggleFilter('storage', value)" class="mr-1 rounded text-blue-600 focus:ring-blue-500">
                            <label :for="'filter-storage-' + value" class="text-xs text-gray-700 w-full cursor-pointer" x-text="value"></label>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </th>

    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200 hidden-column sticky top-0 bg-gray-50 z-20" style="display: none;">
        <div x-data="{ showFilterDropdown: false, filterSearch: '' }" @click.away="showFilterDropdown = false" class="relative inline-block text-left">
            <div class="flex items-center justify-center">
                <span>Type Transaksi</span>
                <button type="button" @click="showFilterDropdown = !showFilterDropdown" class="ml-1 text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="h-3 w-3" :class="{ 'text-blue-500': activeFilters.transaction_type && activeFilters.transaction_type.length > 0 }" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 6h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div x-show="showFilterDropdown" x-transition class="block origin-top-left absolute left-0 mt-1 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 p-1">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-semibold">Filter Tipe Transaksi</span>
                    <button x-show="activeFilters.transaction_type && activeFilters.transaction_type.length > 0" @click="resetFilter('transaction_type'); showFilterDropdown = false" class="text-xs text-red-500 hover:text-red-700 font-semibold">Reset</button>
                </div>
                <input type="text" x-model.debounce.300ms="filterSearch" placeholder="Cari..." class="w-full px-2 py-1 mb-1 text-xs border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="max-h-48 overflow-y-auto">
                    <template x-for="value in getUniqueValues('transaction_type').filter(v => formatTransactionType(v).toLowerCase().includes(filterSearch.toLowerCase()))" :key="value">
                        <div class="flex items-center p-1 hover:bg-gray-100 rounded">
                            <input type="checkbox" :id="'filter-type-' + value" :checked="isFiltered('transaction_type', value)" @click.stop="toggleFilter('transaction_type', value)" class="mr-1 rounded text-blue-600 focus:ring-blue-500">
                            <label :for="'filter-type-' + value" class="text-xs text-gray-700 w-full cursor-pointer" x-text="formatTransactionType(value)"></label>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </th>

    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200 sticky top-0 bg-gray-50 z-20">
        <div x-data="{ showFilterDropdown: false, filterSearch: '' }" @click.away="showFilterDropdown = false" class="relative inline-block text-left">
            <div class="flex items-center justify-center">
                <span>Keterangan</span>
                <button type="button" @click="showFilterDropdown = !showFilterDropdown" class="ml-1 text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="h-3 w-3" :class="{ 'text-blue-500': activeFilters.keterangan && activeFilters.keterangan.length > 0 }" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 6h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div x-show="showFilterDropdown" x-transition class="block origin-top-left absolute left-0 mt-1 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 p-1">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-semibold">Filter Keterangan</span>
                    <button x-show="activeFilters.keterangan && activeFilters.keterangan.length > 0" @click="resetFilter('keterangan'); showFilterDropdown = false" class="text-xs text-red-500 hover:text-red-700 font-semibold">Reset</button>
                </div>
                <input type="text" x-model.debounce.300ms="filterSearch" placeholder="Cari..." class="w-full px-2 py-1 mb-1 text-xs border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="max-h-48 overflow-y-auto">
                    <template x-for="value in getUniqueValues('keterangan').filter(v => String(v).toLowerCase().includes(filterSearch.toLowerCase()))" :key="value">
                        <div class="flex items-center p-1 hover:bg-gray-100 rounded">
                            <input type="checkbox" :id="'filter-keterangan-' + value" :checked="isFiltered('keterangan', value)" @click.stop="toggleFilter('keterangan', value)" class="mr-1 rounded text-blue-600 focus:ring-blue-500">
                            <label :for="'filter-keterangan-' + value" class="text-xs text-gray-700 w-full cursor-pointer" x-text="value"></label>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </th>

    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200 hidden-column sticky top-0 bg-gray-50 z-20" style="display: none;">
        <div x-data="{ showFilterDropdown: false, filterSearch: '' }" @click.away="showFilterDropdown = false" class="relative inline-block text-left">
            <div class="flex items-center justify-center">
                <span>User</span>
                <button type="button" @click="showFilterDropdown = !showFilterDropdown" class="ml-1 text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="h-3 w-3" :class="{ 'text-blue-500': activeFilters.user && activeFilters.user.length > 0 }" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 6h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div x-show="showFilterDropdown" x-transition class="block origin-top-left absolute left-0 mt-1 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 p-1">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-semibold">Filter User</span>
                    <button x-show="activeFilters.user && activeFilters.user.length > 0" @click="resetFilter('user'); showFilterDropdown = false" class="text-xs text-red-500 hover:text-red-700 font-semibold">Reset</button>
                </div>
                <input type="text" x-model.debounce.300ms="filterSearch" placeholder="Cari..." class="w-full px-2 py-1 mb-1 text-xs border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="max-h-48 overflow-y-auto">
                    <template x-for="value in getUniqueValues('user').filter(v => String(v).toLowerCase().includes(filterSearch.toLowerCase()))" :key="value">
                        <div class="flex items-center p-1 hover:bg-gray-100 rounded">
                            <input type="checkbox" :id="'filter-user-' + value" :checked="isFiltered('user', value)" @click.stop="toggleFilter('user', value)" class="mr-1 rounded text-blue-600 focus:ring-blue-500">
                            <label :for="'filter-user-' + value" class="text-xs text-gray-700 w-full cursor-pointer" x-text="value"></label>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </th>

    <th class="px-2 py-1 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200 hidden-column sticky top-0 bg-gray-50 z-20" style="display: none;">
        <div x-data="{ showFilterDropdown: false, filterSearch: '' }" @click.away="showFilterDropdown = false" class="relative inline-block text-left">
            <div class="flex items-center justify-center">
                <span>Created At</span>
                <button type="button" @click="showFilterDropdown = !showFilterDropdown" class="ml-1 text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="h-3 w-3" :class="{ 'text-blue-500': activeFilters.created_at_date && activeFilters.created_at_date.length > 0 }" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 6h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div x-show="showFilterDropdown" x-transition class="block origin-top-left absolute left-0 mt-1 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 p-1">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-semibold">Filter Created At</span>
                    <button x-show="activeFilters.created_at_date && activeFilters.created_at_date.length > 0" @click="resetFilter('created_at_date'); showFilterDropdown = false" class="text-xs text-red-500 hover:text-red-700 font-semibold">Reset</button>
                </div>
                <input type="text" x-model.debounce.300ms="filterSearch" placeholder="Cari..." class="w-full px-2 py-1 mb-1 text-xs border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="max-h-48 overflow-y-auto">
                    <template x-for="value in getUniqueValues('created_at_date').filter(v => String(v).toLowerCase().includes(filterSearch.toLowerCase()))" :key="value">
                        <div class="flex items-center p-1 hover:bg-gray-100 rounded">
                            <input type="checkbox" :id="'filter-created-at-' + value" :checked="isFiltered('created_at_date', value)" @click.stop="toggleFilter('created_at_date', value)" class="mr-1 rounded text-blue-600 focus:ring-blue-500">
                            <label :for="'filter-created-at-' + value" class="text-xs text-gray-700 w-full cursor-pointer" x-text="value"></label>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </th>
</tr>