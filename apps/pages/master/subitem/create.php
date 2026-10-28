<main class="flex-1 min-w-0 transition-all duration-300" x-data="subitemCreator()">
    <a href="?page=master/subitem" class="inline-flex items-center px-3 py-1.5 text-xs bg-gray-200 text-gray-700 rounded-md shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-75 transition duration-300 mb-4">
        <i class="fas fa-arrow-left mr-1"></i> Kembali
    </a>

    <h1 class="text-sm sm:text-lg font-bold mb-4 text-gray-800">Tambah Subitem</h1>

    <div class="bg-white shadow-md rounded-lg p-3 sm:p-4">
        <form @submit.prevent="submitForm" class="space-y-3 sm:space-y-4">

            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                <label for="subitem" class="w-20 sm:w-28 text-xs text-gray-700 font-medium">Subitem</label>
                <input type="text" id="subitem" x-model.trim="formData.subitem" required
                    class="flex-1 p-1.5 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Masukkan Subitem"
                    autocomplete="off">
            </div>

            <div class="flex justify-end pt-3">
                <button type="submit"
                    :disabled="isLoading"
                    class="px-4 py-2 text-xs bg-blue-600 text-white font-semibold rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-75 transition duration-300 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                    aria-live="polite">

                    <span x-show="!isLoading" x-cloak>Simpan Data</span>

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
        Alpine.data('subitemCreator', () => ({
            formData: {
                subitem: ''
            },
            isLoading: false,
            // errorMessage: '',   // Removed as SweetAlert handles it
            // successMessage: '', // Removed as SweetAlert handles it
            // messageTimeout: null, // Removed as SweetAlert handles auto-dismissal

            async submitForm() {
                this.isLoading = true;
                // this.errorMessage = '';   // No longer needed
                // this.successMessage = ''; // No longer needed
                // clearTimeout(this.messageTimeout); // No longer needed

                // Basic client-side validation using SweetAlert2
                if (this.formData.subitem.trim() === '') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Input Kosong!',
                        text: 'Nama subitem tidak boleh kosong.',
                        confirmButtonText: 'OK'
                    });
                    this.isLoading = false;
                    return; // Stop execution if validation fails
                }

                try {
                    const response = await fetch('./controller/master/subitem.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            subitem: this.formData.subitem.trim()
                        })
                    });

                    // Check for network errors or non-2xx HTTP status
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
                        throw new Error(data.error || data.message || 'Gagal menyimpan subitem.');
                    }

                    // Show success message using SweetAlert2
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Subitem berhasil ditambahkan!',
                        showConfirmButton: false, // No confirm button needed for success
                        timer: 1500 // Automatically close after 1.5 seconds
                    }).then(() => {
                        this.formData.subitem = ''; // Clear only the 'subitem' input field on success
                        window.location.href = '?page=master/subitem'; // Redirect after success
                    });

                } catch (error) {
                    // Show error message using SweetAlert2
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyimpan!',
                        text: error.message || 'Terjadi kesalahan tidak terduga saat menyimpan data.',
                        confirmButtonText: 'OK'
                    });
                    console.error('Error:', error);
                } finally {
                    this.isLoading = false;
                }
            }
        }));
    });
</script>