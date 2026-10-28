<main class="flex-1 min-w-0 transition-all duration-300" x-data="subitemManager()" x-init="fetchSubitems()">
    <h1 class="text-sm sm:text-lg font-bold mb-4 text-gray-800">Daftar Subitem</h1>

    <a href="?page=master/subitem/create" class="inline-block px-3 py-1.5 text-xs bg-blue-600 text-white font-semibold rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-75 transition duration-300 mb-4">
        Tambah data Subitem
    </a>

    <div class="bg-white shadow-md rounded-lg p-3 sm:p-4">
        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto rounded-md border border-gray-200">
            <table class="min-w-full border-collapse">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">No</th>
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">Subitem</th>
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="loading">
                        <tr>
                            <td colspan="3" class="px-2 py-3 text-center text-xs text-gray-500 border-b border-gray-200">
                                <div class="flex items-center justify-center space-x-1">
                                    <i class="fas fa-spinner fa-spin -ml-1 mr-1 h-3 w-3 text-gray-500"></i>
                                    <span>Memuat data...</span>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-if="!loading && subitems && subitems.length === 0">
                        <tr>
                            <td colspan="3" class="px-2 py-3 text-center text-xs text-gray-500 border-b border-gray-200">Tidak ada data subitem ditemukan.</td>
                        </tr>
                    </template>

                    <template x-for="(item, index) in subitems" :key="item.id">
                        <tr class="even:bg-gray-50 hover:bg-gray-100">
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-800 border-b border-gray-200" x-text="index + 1"></td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-800 border-b border-gray-200" x-text="item.subitem"></td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs border-b border-gray-200 space-x-1">
                                <a :href="'?page=master/subitem/edit&id=' + item.id"
                                    class="inline-block px-2 py-1 text-xs bg-yellow-500 text-white font-semibold rounded-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-400 transition duration-300">
                                    Edit
                                </a>
                                <button @click="confirmDelete(item.id, item.subitem)"
                                    class="inline-block px-2 py-1 text-xs bg-red-600 text-white font-semibold rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-400 transition duration-300">
                                    Hapus
                                </button>
                            </td>
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
                        <p class="text-gray-600 text-sm">Memuat data subitem...</p>
                    </div>
                </div>
            </template>

            <!-- Empty State -->
            <template x-if="!loading && subitems && subitems.length === 0">
                <div class="text-center py-8 bg-white rounded-lg border border-gray-200">
                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-tags text-gray-400 text-lg"></i>
                    </div>
                    <p class="text-gray-600 font-medium text-sm">Tidak ada data subitem</p>
                    <p class="text-gray-500 text-xs mt-1">Mulai dengan menambahkan subitem baru</p>
                </div>
            </template>

            <!-- Subitem Cards -->
            <template x-for="(item, index) in subitems" :key="item.id">
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-200">
                    <!-- Card Header -->
                    <div class="p-3 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                    <span x-text="index + 1"></span>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-800" x-text="item.subitem"></h3>
                                    <p class="text-xs text-gray-500" x-text="'ID: ' + item.id"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <div class="p-3 bg-gray-50 border-t border-gray-100">
                        <div class="flex space-x-2">
                            <a :href="'?page=master/subitem/edit&id=' + item.id"
                                class="flex-1 bg-yellow-500 text-white text-xs font-medium py-2 rounded text-center hover:bg-yellow-600 transition-colors flex items-center justify-center space-x-1">
                                <i class="fas fa-edit text-xs"></i>
                                <span>Edit</span>
                            </a>
                            <button @click="confirmDelete(item.id, item.subitem)"
                                class="flex-1 bg-red-600 text-white text-xs font-medium py-2 rounded hover:bg-red-700 transition-colors flex items-center justify-center space-x-1">
                                <i class="fas fa-trash text-xs"></i>
                                <span>Hapus</span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</main>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('subitemManager', () => ({
            subitems: [],
            loading: true,

            async fetchSubitems() {
                this.loading = true;

                try {
                    const response = await fetch('./controller/master/subitem.php');
                    if (!response.ok) {
                        const errorData = response.headers.get('Content-Type')?.includes('application/json') ? await response.json() : {};
                        throw new Error(errorData.error || `Server error: ${response.statusText} (${response.status})`);
                    }

                    const data = await response.json();

                    if (data.success) {
                        this.subitems = data.data;
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Memuat Data',
                            text: data.error || data.message || 'Terjadi kesalahan saat memuat data subitem.',
                        });
                        console.error('API responded with success: false:', data.error || data.message);
                        this.subitems = [];
                    }
                } catch (error) {
                    console.error('Error fetching subitems:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error Jaringan',
                        text: error.message || 'Tidak dapat terhubung ke server. Silakan coba lagi.',
                    });
                    this.subitems = [];
                } finally {
                    this.loading = false;
                }
            },

            confirmDelete(id, name) {
                Swal.fire({
                    title: 'Anda yakin?',
                    text: `Subitem "${name}" (ID: ${id}) akan dihapus permanen!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.deleteSubitem(id);
                    }
                });
            },

            async deleteSubitem(id) {
                try {
                    const response = await fetch(`./controller/master/subitem.php?id=${id}`, {
                        method: 'DELETE'
                    });

                    if (!response.ok) {
                        const errorData = response.headers.get('Content-Type')?.includes('application/json') ? await response.json() : {};
                        throw new Error(errorData.error || `Server error: ${response.statusText} (${response.status})`);
                    }

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire(
                            'Dihapus!',
                            data.message || 'Subitem berhasil dihapus.',
                            'success'
                        );
                        await this.fetchSubitems();
                    } else {
                        Swal.fire(
                            'Gagal!',
                            data.error || data.message || 'Gagal menghapus subitem.',
                            'error'
                        );
                        console.error('API responded with success: false:', data.error || data.message);
                    }
                } catch (error) {
                    console.error('Error deleting subitem:', error);
                    Swal.fire(
                        'Error!',
                        'Terjadi kesalahan saat menghapus subitem: ' + (error.message || 'Unknown error.'),
                        'error'
                    );
                }
            }
        }));
    });
</script>