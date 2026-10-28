<?php
// Pastikan ini ada di bagian paling atas file PHP yang merender halaman ini
// session_start(); // Make sure session_start() is called at the very top of your script

// Ambil peran pengguna dari sesi, berikan nilai default jika tidak ada
// Menggunakan $userRole untuk konsistensi
$userRole = $_SESSION['user_role'] ?? 'guest'; // Sesuaikan 'user_role' dengan nama kunci sesi Anda
?>

<main class="flex-1 min-w-0 transition-all duration-300" x-data="itemManager('<?php echo htmlspecialchars($userRole); ?>')" x-init="init()">

    <h1 class="text-sm sm:text-lg font-bold mb-4 text-gray-800">Daftar Item</h1>

    <div class="flex flex-col sm:flex-row justify-between items-center mb-3 space-y-2 sm:space-y-0 sm:space-x-3">
        <?php if ($userRole === 'superadmin'): ?>
            <a href="?page=master/item/create" class="inline-block px-3 py-1.5 text-xs bg-blue-600 text-white font-semibold rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-75 transition duration-300 w-full sm:w-auto text-center">
                Tambah data item
            </a>
        <?php endif; ?>
    </div>

    <div class="bg-white shadow-md rounded-lg p-3 sm:p-4">

        <!-- Search Bar -->
        <div class="mb-3">
            <input type="text" x-model.debounce.500ms="searchQuery" @input="fetchItems(1)" placeholder="Cari berdasarkan SKU, Item, atau Kategori..."
                class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto rounded-md border border-gray-200">
            <table class="min-w-full border-collapse">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">No</th>
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">SKU</th>
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">Item</th>
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">Kategori</th>
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">Aktif</th>
                        <template x-if="userRole === 'superadmin'">
                            <th class="px-2 py-2 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">Aksi</th>
                        </template>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="loading">
                        <tr>
                            <td :colspan="userRole === 'superadmin' ? 6 : 5" class="px-2 py-3 text-center text-xs text-gray-500 border-b border-gray-200">Memuat data...</td>
                        </tr>
                    </template>

                    <template x-if="!loading && items.length === 0">
                        <tr>
                            <td :colspan="userRole === 'superadmin' ? 6 : 5" class="px-2 py-3 text-center text-xs text-gray-500 border-b border-gray-200">Tidak ada data item ditemukan.</td>
                        </tr>
                    </template>

                    <template x-for="(item, index) in items" :key="item.sku">
                        <tr class="even:bg-gray-50 hover:bg-gray-100">
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-800 border-b border-gray-200" x-text="(currentPage - 1) * itemsPerPage + index + 1"></td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-800 border-b border-gray-200" x-text="item.sku"></td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-800 border-b border-gray-200" x-text="item.item"></td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-800 border-b border-gray-200" x-text="item.kategory"></td>

                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-800 border-b border-gray-200">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" :checked="item.isActive == 1" @change="toggleItemStatus(item)">
                                    <div class="relative w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                    <span class="ms-2 text-xs font-medium text-gray-900 dark:text-gray-300" x-text="item.isActive == 1 ? 'Ya' : 'Tidak'"></span>
                                </label>
                            </td>

                            <template x-if="userRole === 'superadmin'">
                                <td class="px-2 py-2 text-center text-xs text-gray-800 border-b border-gray-200 space-x-1">
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

        <!-- Mobile Cards -->
        <div class="block md:hidden space-y-3">
            <!-- Loading State -->
            <template x-if="loading">
                <div class="text-center py-8">
                    <div class="inline-flex flex-col items-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-4 border-blue-200 border-t-blue-600 mb-3"></div>
                        <p class="text-gray-600 text-sm">Memuat data item...</p>
                    </div>
                </div>
            </template>

            <!-- Empty State -->
            <template x-if="!loading && items.length === 0">
                <div class="text-center py-8 bg-white rounded-lg border border-gray-200">
                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-box text-gray-400 text-lg"></i>
                    </div>
                    <p class="text-gray-600 font-medium text-sm">Tidak ada data item</p>
                    <p class="text-gray-500 text-xs mt-1">Coba ubah kata kunci pencarian</p>
                </div>
            </template>

            <!-- Item Cards -->
            <template x-for="(item, index) in items" :key="item.sku">
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-200">
                    <!-- Card Header -->
                    <div class="p-3 border-b border-gray-100">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center space-x-2">
                                <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                    <span x-text="(currentPage - 1) * itemsPerPage + index + 1"></span>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-800" x-text="item.item"></h3>
                                    <p class="text-xs text-gray-500" x-text="'SKU: ' + item.sku"></p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-1">
                                <span class="text-xs px-2 py-1 rounded-full"
                                    :class="item.isActive == 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                    x-text="item.isActive == 1 ? 'Aktif' : 'Nonaktif'">
                                </span>
                            </div>
                        </div>

                        <div class="text-xs text-gray-600">
                            <span class="font-medium">Kategori:</span>
                            <span x-text="item.kategory"></span>
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <template x-if="userRole === 'superadmin'">
                        <div class="p-3 bg-gray-50 border-t border-gray-100">
                            <div class="flex space-x-2">
                                <a :href="'?page=master/item/edit&sku=' + item.sku"
                                    class="flex-1 bg-yellow-500 text-white text-xs font-medium py-2 rounded text-center hover:bg-yellow-600 transition-colors">
                                    Edit
                                </a>
                                <button @click="confirmDelete(item)"
                                    class="flex-1 bg-red-600 text-white text-xs font-medium py-2 rounded hover:bg-red-700 transition-colors">
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Status Toggle -->
                    <div class="p-3 bg-gray-50 border-t border-gray-100">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-gray-700">Status Aktif:</span>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer" :checked="item.isActive == 1" @change="toggleItemStatus(item)">
                                <div class="relative w-8 h-4 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[1px] after:left-[1px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Pagination -->
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
</main>

<script>
    document.addEventListener('alpine:init', () => {
        // Menerima userRole sebagai parameter saat inisialisasi
        Alpine.data('itemManager', (initialUserRole) => ({
            items: [],
            loading: true,
            searchQuery: '',
            currentPage: 1,
            itemsPerPage: 10, // You can adjust this value
            totalItems: 0,
            totalPages: 0,
            paginationPages: [],
            userRole: initialUserRole,

            init() {
                console.log('User Role dari PHP:', this.userRole); // Untuk debugging
                // Langsung panggil fetchItems karena userRole sudah tersedia
                this.fetchItems();

                // Inisialisasi search dengan watcher
                this.$watch('searchQuery', (value) => {
                    clearTimeout(this.searchTimeout);
                    this.searchTimeout = setTimeout(() => {
                        this.fetchItems(1); // Reset ke halaman 1 saat pencarian baru
                    }, 500); // 500ms debounce
                });
            },

            async fetchItems(page = this.currentPage) {
                this.loading = true;
                this.currentPage = page;
                const offset = (this.currentPage - 1) * this.itemsPerPage;
                let url = `./controller/master/item.php?limit=${this.itemsPerPage}&offset=${offset}`;

                if (this.searchQuery && this.searchQuery.trim() !== '') {
                    url += `&search=${encodeURIComponent(this.searchQuery.trim())}`;
                }

                try {
                    const response = await fetch(url);
                    const data = await response.json();

                    if (data.success) {
                        this.items = data.data;
                        this.totalItems = data.totalItems;
                        this.totalPages = Math.ceil(this.totalItems / this.itemsPerPage);
                        this.updatePaginationPages();
                    } else {
                        console.error('Gagal memuat data:', data.error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Memuat Data',
                            text: data.error || 'Terjadi kesalahan saat memuat data item.',
                        });
                    }
                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error Jaringan',
                        text: 'Tidak dapat terhubung ke server. Silakan coba lagi.',
                    });
                } finally {
                    this.loading = false;
                }
            },

            async toggleItemStatus(item) {
                const newStatus = item.isActive == 1 ? 0 : 1;
                try {
                    const response = await fetch(`./controller/master/item.php`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            sku: item.sku,
                            isActive: newStatus
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        item.isActive = newStatus; // Update local state for immediate UI reflection
                        Swal.fire(
                            'Berhasil!',
                            `Status item '${item.item}' berhasil diubah menjadi ${newStatus ? 'Aktif' : 'Tidak Aktif'}.`,
                            'success'
                        );
                    } else {
                        Swal.fire(
                            'Gagal!',
                            'Gagal mengubah status item: ' + (data.error || 'Unknown error'),
                            'error'
                        );
                    }
                } catch (error) {
                    console.error('Error toggling item status:', error);
                    Swal.fire(
                        'Error!',
                        'Terjadi kesalahan saat mengubah status item.',
                        'error'
                    );
                }
            },

            updatePaginationPages() {
                const pages = [];
                const maxPagesToShow = 5;
                let startPage = Math.max(1, this.currentPage - Math.floor(maxPagesToShow / 2));
                let endPage = Math.min(this.totalPages, startPage + maxPagesToShow - 1);

                if (endPage - startPage + 1 < maxPagesToShow) {
                    startPage = Math.max(1, endPage - maxPagesToShow + 1);
                }

                for (let i = startPage; i <= endPage; i++) {
                    pages.push(i);
                }
                this.paginationPages = pages;
            },

            confirmDelete(item) {
                Swal.fire({
                    title: 'Anda yakin?',
                    text: `Anda tidak akan dapat mengembalikan item '${item.item}' (SKU: ${item.sku}) ini!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.deleteItem(item.sku);
                    }
                });
            },

            async deleteItem(sku) {
                try {
                    const response = await fetch(`./controller/master/item.php?sku=${sku}`, {
                        method: 'DELETE'
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.fetchItems(); // Refresh list after deletion
                        Swal.fire(
                            'Dihapus!',
                            'Item Anda telah berhasil dihapus.',
                            'success'
                        );
                    } else {
                        Swal.fire(
                            'Gagal!',
                            'Gagal menghapus item: ' + (data.error || 'Unknown error'),
                            'error'
                        );
                    }
                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire(
                        'Error!',
                        'Terjadi kesalahan saat menghapus item.',
                        'error'
                    );
                }
            }
        }));
    });
</script>