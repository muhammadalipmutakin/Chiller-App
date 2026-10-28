<?php
// Ensure session is started on the main page that includes this template
// session_start(); // This should ideally be handled once at the very top of your application's entry point
$displayNamaSetting = $_SESSION['user_nama'] ?? 'Pengguna Aplikasi';
$displayRoleSetting = $_SESSION['user_role'] ?? 'guest';
$displayUserId = $_SESSION['user_id'] ?? null; // Assuming user_id is stored in session
?>

<main class="flex-1 min-w-0 transition-all duration-300" x-data="settingsManager('<?php echo htmlspecialchars($displayUserId); ?>')">

    <h1 class="text-xl font-bold mb-4 text-gray-800">Pengaturan Akun</h1>

    <div class="relative bg-white shadow-lg rounded-lg p-4 mb-6 overflow-hidden">
        <div class="absolute top-0 left-0 w-20 h-20 bg-blue-100 rounded-br-full opacity-50 -z-1"></div>
        <div class="absolute bottom-0 right-0 w-24 h-24 bg-indigo-100 rounded-tl-full opacity-50 -z-1"></div>
        <div class="absolute top-1/4 right-8 w-12 h-12 bg-green-100 rounded-full opacity-50 animate-pulse -z-1"></div>
        <h2 class="text-lg font-semibold mb-3 text-gray-700 relative z-10">Profil Pengguna</h2>

        <div class="flex flex-col items-center space-y-4 relative z-10">

            <div
                class="w-full flex justify-center p-3 bg-blue-50 bg-opacity-80 rounded-lg shadow-inner border border-blue-200">
                <div class="flex flex-col items-center">
                    <div class="relative">
                        <img :src="userData.photo_url || './assets/img/avatar.png'" alt="Profile Photo"
                            class="w-28 h-28 rounded-full object-cover border-4 border-blue-400 shadow-xl transition-transform transform hover:scale-105">
                        <span x-show="loading"
                            class="absolute inset-0 flex items-center justify-center bg-gray-800 bg-opacity-75 rounded-full text-white text-xs font-semibold">
                            Mengunggah...
                        </span>
                    </div>
                    <input type="file" @change="handlePhotoUpload" class="hidden" id="photoUploadInput" accept="image/*"
                        x-ref="photoUploadInput">
                    <button @click="$refs.photoUploadInput.click()"
                        class="mt-3 px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-full shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-75 transition duration-300 transform hover:-translate-y-0.5"
                        :disabled="loading"> <i class="fas fa-camera mr-1.5"></i> Ubah Foto Profil
                    </button>
                </div>
            </div>
            <div class="flex-grow w-full p-4 bg-gray-50 bg-opacity-80 rounded-lg shadow-inner border border-gray-200">
                <div class="grid grid-cols-1 gap-4 mb-4">
                    <div>
                        <label for="nama" class="block text-xs font-medium text-gray-700">Nama Lengkap</label>
                        <input type="text" id="nama" x-model="userData.nama"
                            class="mt-0.5 block w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-800 font-medium cursor-not-allowed"
                            disabled>
                    </div>
                    <div>
                        <label for="username" class="block text-xs font-medium text-gray-700">Username</label>
                        <input type="text" id="username" x-model="userData.username"
                            class="mt-0.5 block w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-800 font-medium cursor-not-allowed"
                            disabled>
                    </div>
                    <div>
                        <label for="role" class="block text-xs font-medium text-gray-700">Peran Pengguna</label>
                        <input type="text" id="role" x-model="userData.role"
                            class="mt-0.5 block w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-800 font-medium cursor-not-allowed"
                            disabled>
                    </div>
                </div>

                <div class="flex justify-end pt-2 border-t border-gray-200">
                    <button @click="openPasswordModal = true"
                        class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-full shadow-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-75 transition duration-300 transform hover:-translate-y-0.5">
                        <i class="fas fa-key mr-1.5"></i> Ubah Password
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-3"
        x-show="openPasswordModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-90"
        x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90">
        <div class="relative bg-white rounded-lg shadow-xl p-5 w-full max-w-sm mx-auto transform transition-all duration-300"
            @click.away="openPasswordModal = false">
            <h3 class="text-lg font-bold text-gray-900 mb-4 text-center">Ubah Password</h3>
            <form @submit.prevent="changePassword">
                <div class="mb-3">
                    <label for="current_password" class="block text-xs font-semibold text-gray-700 mb-1">Password Saat
                        Ini</label>
                    <input type="password" id="current_password" x-model="passwords.current" required
                        autocomplete="current-password"
                        class="mt-0.5 block w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out">
                </div>
                <div class="mb-3">
                    <label for="new_password" class="block text-xs font-semibold text-gray-700 mb-1">Password Baru</label>
                    <input type="password" id="new_password" x-model="passwords.new" required autocomplete="new-password"
                        class="mt-0.5 block w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out">
                </div>
                <div class="mb-4">
                    <label for="confirm_new_password" class="block text-xs font-semibold text-gray-700 mb-1">Ulangi Password
                        Baru</label>
                    <input type="password" id="confirm_new_password" x-model="passwords.confirm" required
                        autocomplete="new-password"
                        class="mt-0.5 block w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out">
                    <p x-show="passwords.new !== passwords.confirm && passwords.confirm !== ''"
                        class="text-red-600 text-xs mt-1 font-medium">
                        <i class="fas fa-exclamation-circle mr-1"></i> Password baru tidak cocok.
                    </p>
                </div>
                <div class="flex justify-end space-x-3 pt-2 border-t border-gray-200">
                    <button type="button" @click="openPasswordModal = false; resetPasswordForm()"
                        class="px-4 py-1.5 text-sm bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition duration-300">Batal</button>
                    <button type="submit"
                        class="px-4 py-1.5 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-300"
                        :disabled="passwords.new !== passwords.confirm || passwords.new === '' || passwordLoading">
                        <span x-show="!passwordLoading"><i class="fas fa-save mr-1.5"></i> Simpan</span>
                        <span x-show="passwordLoading">
                            <i class="fas fa-spinner fa-spin mr-1.5"></i> Memproses...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('settingsManager', (userId) => ({
            userId: userId,
            userData: {
                id: '',
                nama: '',
                role: '',
                username: '',
                photo: '',
                photo_url: './assets/img/avatar.png' // Default placeholder
            },
            openPasswordModal: false,
            passwords: {
                current: '',
                new: '',
                confirm: ''
            },
            loading: false, // For general loading states (including photo upload)
            passwordLoading: false, // For password change specific loading

            init() {
                this.fetchUserData();
                // Add Font Awesome (for icons) if not already present
                if (!document.querySelector('link[href*="font-awesome"]')) {
                    const link = document.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css';
                    document.head.appendChild(link);
                }
            },

            async fetchUserData() {
                this.loading = true;
                try {
                    const response = await fetch('./controller/setting/route.php?id=' + this.userId);
                    const data = await response.json();

                    if (data.success) {
                        this.userData = {
                            ...data.data,
                            photo_url: data.data.photo_url || './assets/img/avatar.png'
                        };
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Memuat Data',
                            text: data.error || 'Terjadi kesalahan saat memuat data pengguna.',
                        });
                    }
                } catch (error) {
                    console.error('Error fetching user data:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error Jaringan',
                        text: 'Tidak dapat terhubung ke server. Silakan coba lagi.',
                    });
                } finally {
                    this.loading = false;
                }
            },

            async handlePhotoUpload(event) {
                const file = event.target.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('photo', file);

                this.loading = true; // Use general loading for photo upload
                try {
                    const response = await fetch('./controller/setting/route.php', {
                        method: 'POST', // Use POST for file uploads
                        body: formData
                    });
                    const data = await response.json();

                    if (data.success) {
                        this.userData.photo_url = data.photo_url; // Update photo URL
                        Swal.fire('Berhasil!', data.message, 'success');
                    } else {
                        Swal.fire('Gagal!', data.error || 'Gagal mengunggah foto.', 'error');
                    }
                } catch (error) {
                    console.error('Error uploading photo:', error);
                    Swal.fire('Error!', 'Terjadi kesalahan saat mengunggah foto.', 'error');
                } finally {
                    this.loading = false;
                    event.target.value = ''; // Clear the input so same file can be chosen again
                }
            },

            async changePassword() {
                if (this.passwords.new !== this.passwords.confirm) {
                    Swal.fire('Error!', 'Password baru dan konfirmasi tidak cocok.', 'error');
                    return;
                }
                if (this.passwords.new === '') {
                    Swal.fire('Error!', 'Password baru tidak boleh kosong.', 'error');
                    return;
                }


                this.passwordLoading = true; // Use specific loading for password change
                try {
                    const response = await fetch('./controller/setting/route.php', {
                        method: 'PUT', // Use PUT for password update
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            current_password: this.passwords.current,
                            new_password: this.passwords.new,
                            confirm_new_password: this.passwords.confirm
                        })
                    });
                    const data = await response.json();

                    if (data.success) {
                        Swal.fire('Berhasil!', data.message, 'success');
                        this.openPasswordModal = false;
                        this.resetPasswordForm();
                    } else {
                        Swal.fire('Gagal!', data.error || 'Gagal mengubah password.', 'error');
                    }
                } catch (error) {
                    console.error('Error changing password:', error);
                    Swal.fire('Error!', 'Terjadi kesalahan saat mengubah password.', 'error');
                } finally {
                    this.passwordLoading = false;
                }
            },
            resetPasswordForm() {
                this.passwords.current = '';
                this.passwords.new = '';
                this.passwords.confirm = '';
            }
        }));
    });
</script>