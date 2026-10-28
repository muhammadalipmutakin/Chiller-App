<main class="flex-1 min-w-0 transition-all duration-300" x-data="userCreator()">
    <a href="?page=master/user" class="inline-flex items-center px-3 py-1.5 text-xs bg-gray-200 text-gray-700 rounded-md shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-75 transition duration-300 mb-4">
        <i class="fas fa-arrow-left mr-1"></i> Kembali
    </a>

    <h1 class="text-sm sm:text-lg font-bold mb-4 text-gray-800">Tambah User</h1>

    <div class="bg-white shadow-md rounded-lg p-3 sm:p-4">
        <form @submit.prevent="submitForm" class="space-y-3 sm:space-y-4">

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
                    <input type="password" id="password" x-model.trim="formData.password" required
                        class="w-full p-1.5 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Masukkan password" autocomplete="new-password">
                    <p class="text-xs text-gray-500 mt-1">Password default adalah "<strong class="font-semibold">password</strong>". Mohon informasikan kepada user untuk segera mengubahnya.</p>
                </div>
            </div>

            <div class="flex justify-end pt-3">
                <button type="submit"
                    :disabled="isLoading"
                    class="px-4 py-2 text-xs bg-blue-600 text-white font-semibold rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-75 transition duration-300 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                    aria-live="polite">
                    <span x-show="!isLoading" x-cloak>Simpan User</span>

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
        Alpine.data('userCreator', () => ({
            formData: {
                nama: '',
                role: '',
                username: '',
                password: 'password' // Default password
            },
            isLoading: false,
            // errorMessage: '',   // Removed, SweetAlert2 handles display
            // successMessage: '', // Removed, SweetAlert2 handles display
            // messageTimeout: null, // No longer directly managing timeouts for messages

            async submitForm() {
                this.isLoading = true;
                // this.errorMessage = ''; // Clear previous messages
                // this.successMessage = '';
                // clearTimeout(this.messageTimeout); // No longer needed

                // Basic client-side validation using SweetAlert2
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
                if (this.formData.password.trim() === '') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Input Kosong!',
                        text: 'Password tidak boleh kosong.',
                        confirmButtonText: 'Oke'
                    });
                    this.isLoading = false;
                    return;
                }

                try {
                    const response = await fetch('./controller/master/user.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(this.formData)
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
                        // Handle specific error messages from your PHP backend
                        throw new Error(data.error || data.message || 'Gagal menyimpan user.');
                    }

                    // Show success message using SweetAlert2
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'User berhasil ditambahkan!',
                        showConfirmButton: false, // No confirm button needed for success
                        timer: 1500 // Automatically close after 1.5 seconds
                    }).then(() => {
                        // Clear the form after success
                        this.formData.nama = '';
                        this.formData.role = '';
                        this.formData.username = '';
                        this.formData.password = 'password'; // Reset to default password

                        // Redirect after SweetAlert closes
                        window.location.href = '?page=master/user'; // Redirect to user list page
                    });

                } catch (error) {
                    console.error('Error:', error);
                    // Show error message using SweetAlert2
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyimpan!',
                        text: error.message || 'Terjadi kesalahan tidak terduga saat menyimpan data.',
                        confirmButtonText: 'Oke'
                    });
                } finally {
                    this.isLoading = false;
                }
            }
        }));
    });
</script>