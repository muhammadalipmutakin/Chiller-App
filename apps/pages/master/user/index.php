<main class="flex-1 min-w-0 transition-all duration-300" x-data="userManager()" x-init="fetchUsers()">
    <h1 class="text-sm sm:text-lg font-bold mb-4 text-gray-800">Daftar Pengguna</h1>

    <a href="?page=master/user/create" class="inline-block px-3 py-1.5 text-xs bg-blue-600 text-white font-semibold rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-75 transition duration-300 mb-4">
        Tambah Data Pengguna
    </a>

    <div class="bg-white shadow-md rounded-lg p-3 sm:p-4">
        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto rounded-md border border-gray-200">
            <table class="min-w-full border-collapse">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">No</th>
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">Nama</th>
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">Role</th>
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">Username</th>
                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-b border-gray-200">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="loading">
                        <tr>
                            <td colspan="5" class="px-2 py-3 text-center text-xs text-gray-500 border-b border-gray-200">
                                <div class="flex items-center justify-center space-x-1">
                                    <i class="fas fa-spinner fa-spin -ml-1 mr-1 h-3 w-3 text-gray-500"></i>
                                    <span>Memuat data...</span>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-if="!loading && errorMessage">
                        <tr>
                            <td colspan="5" class="px-2 py-3 text-center text-xs text-red-600 bg-red-50 border-b border-gray-200">
                                <span x-text="errorMessage"></span>
                            </td>
                        </tr>
                    </template>

                    <template x-if="!loading && !errorMessage && users.length === 0">
                        <tr>
                            <td colspan="5" class="px-2 py-3 text-center text-xs text-gray-500 border-b border-gray-200">Tidak ada data pengguna ditemukan.</td>
                        </tr>
                    </template>

                    <template x-for="(user, index) in users" :key="user.id">
                        <tr class="even:bg-gray-50 hover:bg-gray-100">
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-800 border-b border-gray-200" x-text="index + 1"></td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-800 border-b border-gray-200" x-text="user.nama"></td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-800 border-b border-gray-200" x-text="user.role"></td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-800 border-b border-gray-200" x-text="user.username"></td>
                            <td class="px-2 py-2 whitespace-nowrap text-xs border-b border-gray-200 space-x-1">
                                <a :href="'?page=master/user/edit&id=' + user.id"
                                    class="inline-block px-2 py-1 text-xs bg-yellow-500 text-white font-semibold rounded-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-400 transition duration-300">
                                    Edit
                                </a>
                                <button @click="confirmDelete(user.id, user.username)"
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
                        <p class="text-gray-600 text-sm">Memuat data pengguna...</p>
                    </div>
                </div>
            </template>

            <!-- Error State -->
            <template x-if="!loading && errorMessage">
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                    <i class="fas fa-exclamation-triangle text-red-500 text-lg mb-2"></i>
                    <p class="text-red-700 text-sm font-medium">Gagal Memuat Data</p>
                    <p class="text-red-600 text-xs mt-1" x-text="errorMessage"></p>
                </div>
            </template>

            <!-- Empty State -->
            <template x-if="!loading && !errorMessage && users.length === 0">
                <div class="text-center py-8 bg-white rounded-lg border border-gray-200">
                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-users text-gray-400 text-lg"></i>
                    </div>
                    <p class="text-gray-600 font-medium text-sm">Tidak ada data pengguna</p>
                    <p class="text-gray-500 text-xs mt-1">Mulai dengan menambahkan pengguna baru</p>
                </div>
            </template>

            <!-- User Cards -->
            <template x-for="(user, index) in users" :key="user.id">
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-200">
                    <!-- Card Header -->
                    <div class="p-3 border-b border-gray-100">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center space-x-2">
                                <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                    <span x-text="index + 1"></span>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-800" x-text="user.nama"></h3>
                                    <p class="text-xs text-gray-500" x-text="'@' + user.username"></p>
                                </div>
                            </div>
                            <div>
                                <span class="text-xs px-2 py-1 rounded-full"
                                    :class="{
                                          'bg-purple-100 text-purple-800': user.role === 'superadmin',
                                          'bg-blue-100 text-blue-800': user.role === 'admin',
                                          'bg-green-100 text-green-800': user.role === 'user'
                                      }"
                                    x-text="user.role">
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- User Info -->
                    <div class="p-3 bg-gray-50 border-b border-gray-100">
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="text-center">
                                <div class="text-gray-500">Username</div>
                                <div class="font-medium text-gray-800" x-text="user.username"></div>
                            </div>
                            <div class="text-center">
                                <div class="text-gray-500">Role</div>
                                <div class="font-medium"
                                    :class="{
                                         'text-purple-600': user.role === 'superadmin',
                                         'text-blue-600': user.role === 'admin',
                                         'text-green-600': user.role === 'user'
                                     }"
                                    x-text="user.role">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <div class="p-3 bg-gray-50 border-t border-gray-100">
                        <div class="flex space-x-2">
                            <a :href="'?page=master/user/edit&id=' + user.id"
                                class="flex-1 bg-yellow-500 text-white text-xs font-medium py-2 rounded text-center hover:bg-yellow-600 transition-colors flex items-center justify-center space-x-1">
                                <i class="fas fa-edit text-xs"></i>
                                <span>Edit</span>
                            </a>
                            <button @click="confirmDelete(user.id, user.username)"
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
        Alpine.data('userManager', () => ({
            users: [],
            loading: true,
            errorMessage: '',

            async fetchUsers() {
                this.loading = true;
                this.errorMessage = '';
                try {
                    const response = await fetch('./controller/master/user.php');

                    if (!response.ok) {
                        const errorData = response.headers.get('Content-Type')?.includes('application/json') ?
                            await response.json() : {
                                error: `Server error: ${response.statusText}`
                            };
                        throw new Error(errorData.error || `Failed to load user data (Status: ${response.status}).`);
                    }

                    const data = await response.json();

                    if (data.success) {
                        this.users = data.data;
                    } else {
                        this.errorMessage = data.error || data.message || 'Failed to load user data.';
                        console.error('API responded with success: false:', this.errorMessage);
                    }
                } catch (error) {
                    console.error('Error fetching users:', error);
                    this.errorMessage = error.message || 'An error occurred while loading user data.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Memuat Data!',
                        text: this.errorMessage,
                        confirmButtonText: 'Oke'
                    });
                } finally {
                    this.loading = false;
                }
            },

            confirmDelete(id, username) {
                Swal.fire({
                    title: 'Anda yakin?',
                    text: `Pengguna "${username}" akan dihapus permanen!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.deleteUser(id);
                    }
                });
            },

            async deleteUser(id) {
                try {
                    const response = await fetch(`./controller/master/user.php?id=${id}`, {
                        method: 'DELETE'
                    });

                    if (!response.ok) {
                        const errorData = response.headers.get('Content-Type')?.includes('application/json') ?
                            await response.json() : {
                                error: `Server error: ${response.statusText}`
                            };
                        throw new Error(errorData.error || `Failed to delete user (Status: ${response.status}).`);
                    }

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire(
                            'Dihapus!',
                            data.message || 'Pengguna berhasil dihapus.',
                            'success'
                        );
                        await this.fetchUsers();
                    } else {
                        Swal.fire(
                            'Gagal!',
                            data.error || data.message || 'Gagal menghapus pengguna.',
                            'error'
                        );
                    }
                } catch (error) {
                    console.error('Error deleting user:', error);
                    Swal.fire(
                        'Error!',
                        'Gagal menghapus pengguna: ' + (error.message || 'Terjadi kesalahan tidak dikenal.'),
                        'error'
                    );
                }
            }
        }));
    });
</script>