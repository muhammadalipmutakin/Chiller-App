<main class="flex-1 min-w-0 transition-all duration-300" x-data="userEditor()" x-init="init()">
    <a href="?page=master/user" class="inline-flex items-center px-3 py-1.5 text-xs bg-gray-200 text-gray-700 rounded-md shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-75 transition duration-300 mb-4">
        <i class="fas fa-arrow-left mr-1"></i> Kembali
    </a>

    <h1 class="text-sm sm:text-lg font-bold mb-4 text-gray-800">Edit User</h1>

    <div class="bg-white shadow-md rounded-lg p-3 sm:p-4">
        <form @submit.prevent="submitForm" class="space-y-3 sm:space-y-4">
            <input type="hidden" x-model="formData.id">

            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                <label for="nama" class="w-20 sm:w-28 text-xs text-gray-700 font-medium">Nama</label>
                <input type="text" id="nama" x-model.trim="formData.nama" required
                    class="flex-1 p-1.5 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Masukkan nama user" autocomplete="off">
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                <label for="role" class="w-20 sm:w-28 text-xs text-gray-700 font-medium">Role</label>
                <select id="role" x-model="formData.role" required
                    class="flex-1 p-1.5 text-xs border border-gray-300 rounded-md bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Pilih Role</option>
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                    <option value="superadmin">Superadmin</option>
                </select>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                <label for="username" class="w-20 sm:w-28 text-xs text-gray-700 font-medium">Username</label>
                <input type="text" id="username" x-model.trim="formData.username" required
                    class="flex-1 p-1.5 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Masukkan username" autocomplete="off">
            </div>

            <div class="flex flex-col sm:flex-row sm:items-start gap-2">
                <label for="password" class="w-20 sm:w-28 text-xs text-gray-700 font-medium mt-1.5">Password</label>
                <div class="flex-1">
                    <input type="password" id="password" x-model.trim="formData.password"
                        class="w-full p-1.5 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Biarkan kosong jika tidak ingin mengubah password" autocomplete="new-password">
                    <p class="text-xs text-gray-500 mt-1">Biarkan kosong jika tidak ingin mengubah password.</p>
                </div>
            </div>

            <div class="flex justify-end pt-3">
                <button type="submit"
                    :disabled="isLoading"
                    class="px-4 py-2 text-xs bg-blue-600 text-white font-semibold rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-75 transition duration-300 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                    aria-live="polite">
                    <span x-show="!isLoading" x-cloak>Simpan Perubahan</span>

                    <span x-show="isLoading" x-cloak class="flex items-center text-xs">
                        <i class="fas fa-spinner fa-spin -ml-1 mr-1 h-3 w-3 text-white"></i>
                        <span>Menyimpan...</span>
                    </span>
                </button>
            </div>
        </form>
    </div>
</main>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('userEditor', () => ({
            formData: {
                id: null,
                nama: '',
                role: '',
                username: '',
                password: ''
            },
            isLoading: false,
            // errorMessage: '',   // Removed, SweetAlert2 handles display
            // successMessage: '', // Removed, SweetAlert2 handles display

            // Function to fetch user data for editing
            async fetchUserData(userId) {
                this.isLoading = true;
                // this.errorMessage = ''; // No longer directly displayed

                try {
                    const response = await fetch(`./controller/master/user.php?id=${userId}`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        const errorData = response.headers.get('Content-Type')?.includes('application/json') ?
                            await response.json() : {
                                error: `Server error: ${response.status} ${response.statusText || ''}`
                            };
                        throw new Error(errorData.error || `Gagal mengambil data user: ${response.status}.`);
                    }

                    const data = await response.json();

                    if (!data.success || !data.data || data.data.length === 0) {
                        throw new Error(data.message || 'Data user tidak ditemukan.');
                    }

                    const user = data.data[0];
                    this.formData.id = user.id;
                    this.formData.nama = user.nama;
                    this.formData.role = user.role;
                    this.formData.username = user.username;
                    this.formData.password = ''; // IMPORTANT: Password should never be pre-filled from DB for security.

                } catch (error) {
                    console.error('Error fetching user data:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error Memuat Data!',
                        text: error.message || 'Terjadi kesalahan saat memuat data user. Anda akan dialihkan.',
                        confirmButtonText: 'Oke'
                    }).then(() => {
                        window.location.href = '?page=master/user'; // Redirect after error
                    });
                } finally {
                    this.isLoading = false;
                }
            },

            // Function to submit the form (update user data)
            async submitForm() {
                this.isLoading = true;
                // this.errorMessage = ''; // No longer directly displayed
                // this.successMessage = ''; // No longer directly displayed

                // Basic client-side validation
                if (!this.formData.id) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Kesalahan!',
                        text: 'ID user tidak ditemukan untuk pembaruan.',
                        confirmButtonText: 'Oke'
                    });
                    this.isLoading = false;
                    return;
                }
                if (this.formData.nama.trim() === '') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Input Kosong!',
                        text: 'Nama user tidak boleh kosong.',
                        confirmButtonText: 'Oke'
                    });
                    this.isLoading = false;
                    return;
                }
                if (this.formData.role.trim() === '') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Pilihan Kosong!',
                        text: 'Role user harus dipilih.',
                        confirmButtonText: 'Oke'
                    });
                    this.isLoading = false;
                    return;
                }
                if (this.formData.username.trim() === '') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Input Kosong!',
                        text: 'Username tidak boleh kosong.',
                        confirmButtonText: 'Oke'
                    });
                    this.isLoading = false;
                    return;
                }

                try {
                    const response = await fetch('./controller/master/user.php', {
                        method: 'PUT', // Using PUT method for updates
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(this.formData) // Sending all formData
                    });

                    if (!response.ok) {
                        const errorData = response.headers.get('Content-Type')?.includes('application/json') ?
                            await response.json() : {
                                error: `Server error: ${response.status} ${response.statusText || ''}`
                            };
                        throw new Error(errorData.error || `Gagal terhubung ke server atau terjadi masalah: ${response.status}`);
                    }

                    const data = await response.json();

                    if (!data.success) {
                        throw new Error(data.error || data.message || 'Gagal memperbarui user.');
                    }

                    // Show success message using SweetAlert2
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'User berhasil diperbarui!',
                        showConfirmButton: false,
                        timer: 1500 // Auto-close after 1.5 seconds
                    }).then(() => {
                        window.location.href = '?page=master/user'; // Redirect after success
                    });

                } catch (error) {
                    console.error('Error:', error);
                    // Show error message using SweetAlert2
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyimpan!',
                        text: error.message || 'Terjadi kesalahan tidak terduga saat memperbarui data.',
                        confirmButtonText: 'Oke'
                    });
                } finally {
                    this.isLoading = false;
                }
            },

            // Initialization logic when the Alpine component is mounted
            init() {
                const urlParams = new URLSearchParams(window.location.search);
                const userId = urlParams.get('id');
                if (userId) {
                    this.fetchUserData(userId);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'ID User Tidak Ditemukan!',
                        text: 'ID user tidak ditemukan di URL. Anda akan dialihkan.',
                        confirmButtonText: 'Oke'
                    }).then(() => {
                        window.location.href = '?page=master/user'; // Redirect if no ID
                    });
                }
            }
        }));
    });
</script>