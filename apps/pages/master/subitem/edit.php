<main class="flex-1 min-w-0 transition-all duration-300" x-data="subitemEditor()" x-init="init()">
    <a href="?page=master/subitem" class="inline-flex items-center px-3 py-1.5 text-xs bg-gray-200 text-gray-700 rounded-md shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-75 transition duration-300 mb-4">
        <i class="fas fa-arrow-left mr-1"></i> Kembali
    </a>

    <h1 class="text-sm sm:text-lg font-bold mb-4 text-gray-800">Edit Subitem</h1>

    <div x-show="isLoading" class="bg-white shadow-md rounded-lg p-3 sm:p-4 text-center text-xs text-blue-600">
        <i class="fas fa-spinner fa-spin -ml-1 mr-1 h-3 w-3 text-blue-600"></i>
        <span>Memuat data subitem...</span>
    </div>

    <form x-show="!isLoading" @submit.prevent="submitForm" class="bg-white shadow-md rounded-lg p-3 sm:p-4 space-y-3 sm:space-y-4">

        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
            <label for="subitem" class="w-20 sm:w-28 text-xs text-gray-700 font-medium">Subitem</label>
            <input type="text" id="subitem" x-model.trim="formData.subitem" required
                class="flex-1 p-1.5 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Masukkan subitem" autocomplete="off">
        </div>

        <div class="flex justify-end pt-3">
            <button type="submit"
                :disabled="isSubmitting"
                class="px-4 py-2 text-xs bg-blue-600 text-white font-semibold rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-75 transition duration-300 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                aria-live="polite">

                <span x-show="!isSubmitting" x-cloak>Perbarui Data</span>
                <span x-show="isSubmitting" x-cloak class="flex items-center text-xs">
                    <i class="fas fa-spinner fa-spin -ml-1 mr-1 h-3 w-3 text-white"></i>
                    <span>Memproses...</span>
                </span>
            </button>
        </div>
    </form>
</main>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('subitemEditor', () => ({
            formData: {
                id: '',
                subitem: ''
            },
            isLoading: true, // Start true as we fetch data on init
            isSubmitting: false, // Used specifically for form submission status

            init() {
                this.fetchSubitemData(); // Call function to fetch subitem data
            },

            // Function to fetch subitem data based on ID from URL
            async fetchSubitemData() {
                this.isLoading = true; // Set loading status

                const urlParams = new URLSearchParams(window.location.search);
                const id = urlParams.get('id');

                // Validate if ID is not found in URL
                if (!id) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Kesalahan URL!',
                        text: 'ID subitem tidak ditemukan di URL. Tidak dapat memuat data item.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = './index.php?page=master/subitem'; // Redirect back to list page
                    });
                    this.isLoading = false; // Stop loading if no ID
                    return; // Stop further execution
                }

                // Store ID to formData for easy access during submit
                this.formData.id = id;

                try {
                    const response = await fetch(`./controller/master/subitem.php?id=${id}`, {
                        headers: {
                            'Accept': 'application/json' // Explicitly request JSON response
                        }
                    });

                    // Handle non-OK HTTP responses
                    if (!response.ok) {
                        const errorData = response.headers.get('Content-Type')?.includes('application/json') ?
                            await response.json() : {
                                error: `Respons server error: ${response.statusText}`
                            };
                        throw new Error(errorData.error || `Gagal memuat data subitem (Status: ${response.status}).`);
                    }

                    const data = await response.json();

                    // Handle if JSON response does not indicate success
                    if (!data.success) {
                        throw new Error(data.error || 'Gagal memuat data subitem.');
                    }

                    // Ensure data.data exists and is not empty
                    if (!data.data || data.data.length === 0) {
                        throw new Error('Data subitem tidak ditemukan untuk ID ini.');
                    }

                    // Populate formData with received data
                    const subitemData = data.data[0];
                    this.formData.subitem = subitemData.subitem;

                } catch (error) {
                    console.error('Error fetching subitem data:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Memuat Data',
                        text: error.message || 'Terjadi kesalahan saat memuat data subitem.'
                    }).then(() => {
                        window.location.href = './index.php?page=master/subitem'; // Redirect on fetch error
                    });
                } finally {
                    this.isLoading = false; // Always set loading to false after completion (success or error)
                }
            },

            // Function to submit the update form
            async submitForm() {
                this.isSubmitting = true; // Set submitting status

                // Input validation for subitem
                if (this.formData.subitem.trim() === '') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Input Kosong!',
                        text: 'Nama subitem tidak boleh kosong.'
                    });
                    this.isSubmitting = false;
                    return;
                }

                // Use the ID from formData which was populated by fetchSubitemData
                const idToSubmit = this.formData.id;

                // Validate ID before sending update (optional, already validated in fetch)
                if (!idToSubmit) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Kesalahan!',
                        text: 'ID subitem untuk pembaruan tidak ditemukan.'
                    });
                    this.isSubmitting = false;
                    return;
                }

                try {
                    const response = await fetch('./controller/master/subitem.php', {
                        method: 'PUT', // Use PUT method for update
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            id: idToSubmit, // Send subitem ID for update from formData
                            subitem: this.formData.subitem.trim(), // Trim whitespace from subitem name
                        })
                    });

                    // Handle non-OK HTTP responses
                    if (!response.ok) {
                        const errorData = response.headers.get('Content-Type')?.includes('application/json') ?
                            await response.json() : {
                                error: `Respons server error: ${response.statusText}`
                            };
                        throw new Error(errorData.error || `Gagal memperbarui subitem (Status: ${response.status}).`);
                    }

                    const data = await response.json();

                    // Handle if JSON response does not indicate success
                    if (!data.success) {
                        throw new Error(data.error || 'Gagal memperbarui subitem.');
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Subitem berhasil diperbarui!',
                        showConfirmButton: false, // No confirm button needed for success
                        timer: 1500 // Automatically close after 1.5 seconds
                    }).then(() => {
                        window.location.href = './index.php?page=master/subitem'; // Redirect after success
                    });

                } catch (error) {
                    console.error('Error submitting form:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Memperbarui!',
                        text: error.message || 'Terjadi kesalahan saat memperbarui data subitem.'
                    });
                } finally {
                    this.isSubmitting = false; // Always set submitting status to false after completion
                }
            }
        }));
    });
</script>