<div class="bg-white shadow-md rounded-lg p-3 mb-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 items-end">
    <div class="flex flex-col gap-1">
        <label for="startDate" class="text-gray-700 font-medium text-xs">Dari Tanggal</label>
        <input type="date" id="startDate" x-model="startDate" @change="fetchStockData"
            class="p-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div class="flex flex-col gap-1">
        <label for="endDate" class="text-gray-700 font-medium text-xs">Sampai Dengan</label>
        <input type="date" id="endDate" x-model="endDate" @change="fetchStockData"
            class="p-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div class="flex flex-col gap-1">
        <label for="storageFilter" class="text-gray-700 font-medium text-xs">Storage</label>
        <select id="storageFilter" x-model="selectedStorage" @change="fetchStockData"
            class="p-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <template x-for="storage in filterOptions.locations" :key="storage">
                <option :value="storage" x-text="storage"></option>
            </template>
        </select>
    </div>
    <div class="flex flex-col gap-1">
        <label for="itemFilter" class="text-gray-700 font-medium text-xs">Item</label>
        <select id="itemFilter" multiple="multiple" style="width: 100%;"
            class="p-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </select>
    </div>
    <div class="flex flex-col gap-1">
        <label for="subitemFilter" class="text-gray-700 font-medium text-xs">Subitem</label>
        <select id="subitemFilter" multiple="multiple" style="width: 100%;"
            class="p-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </select>
    </div>
    <div class="flex items-end col-span-2 sm:col-span-1 lg:col-span-1">
        <button @click="resetFilters"
            class="px-3 py-2 bg-gray-300 text-gray-800 text-sm font-semibold rounded-md shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-opacity-75 transition duration-300 w-full">
            Reset Filter
        </button>
    </div>
</div>