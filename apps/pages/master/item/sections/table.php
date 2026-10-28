<div class="bg-white shadow-md rounded-lg p-3 sm:p-4" x-data="itemManager()">
    <div class="mb-3">
        <input type="text" x-model.debounce.500ms="searchQuery" placeholder="Cari berdasarkan SKU, Item, atau Kategori..."
            class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
    </div>

    <div class="overflow-x-auto rounded-md border border-gray-200">
        <table class="min-w-full border-collapse">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">No</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">SKU</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">Item</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">Kategori</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">Aktif</th>
                    <template x-if="userRole === 'superadmin'">
                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">Aksi</th>
                    </template>
                </tr>
            </thead>
            <tbody>
                <template x-if="loading">
                    <tr>
                        <td :colspan="userRole === 'superadmin' ? 6 : 5" class="px-3 py-3 text-center text-xs text-gray-500 border-b border-gray-200">Memuat data...</td>
                    </tr>
                </template>

                <template x-if="!loading && items.length === 0">
                    <tr>
                        <td :colspan="userRole === 'superadmin' ? 6 : 5" class="px-3 py-3 text-center text-xs text-gray-500 border-b border-gray-200">Tidak ada data item ditemukan.</td>
                    </tr>
                </template>

                <template x-for="(item, index) in items" :key="item.sku">
                    <tr class="even:bg-gray-50 hover:bg-gray-100">
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-800 border-b border-gray-200" x-text="(currentPage - 1) * itemsPerPage + index + 1"></td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-800 border-b border-gray-200" x-text="item.sku"></td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-800 border-b border-gray-200" x-text="item.item"></td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-800 border-b border-gray-200" x-text="item.kategory"></td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-800 border-b border-gray-200">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer" :checked="item.isActive == 1" @change="toggleItemStatus(item)">
                                <div class="relative w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                <span class="ms-2 text-xs font-medium text-gray-900 dark:text-gray-300" x-text="item.isActive == 1 ? 'Ya' : 'Tidak'"></span>
                            </label>
                        </td>
                        <template x-if="userRole === 'superadmin'">
                            <td class="px-3 py-2 text-center text-xs text-gray-800 border-b border-gray-200 space-x-1">
                                <a :href="'?page=master/item/edit&sku=' + item.sku"
                                    class="inline-block px-2 py-1 text-xs bg-yellow-500 text-white font-semibold rounded-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-400 transition duration-300">
                                    Edit
                                </a>
                                <button @click="confirmDelete(item)"
                                    class="inline-block px-2 py-1 text-xs bg-red-600 text-white font-semibold rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-400 transition duration-300">
                                    Hapus
                                </button>
                            </td>
                        </template>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <nav class="flex justify-center items-center space-x-1 mt-4" aria-label="Pagination">
        <button @click="fetchItems(currentPage - 1)" :disabled="currentPage === 1"
            class="px-2 py-1 text-xs font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed">
            Sebelumnya
        </button>
        <template x-for="page in paginationPages" :key="page">
            <button @click="fetchItems(page)"
                :class="{ 'bg-blue-600 text-white': page === currentPage, 'bg-gray-200 text-gray-700 hover:bg-gray-300': page !== currentPage }"
                class="px-2.5 py-1 text-xs font-medium rounded-md">
                <span x-text="page"></span>
            </button>
        </template>
        <button @click="fetchItems(currentPage + 1)" :disabled="currentPage === totalPages"
            class="px-2 py-1 text-xs font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed">
            Selanjutnya
        </button>
    </nav>
</div>