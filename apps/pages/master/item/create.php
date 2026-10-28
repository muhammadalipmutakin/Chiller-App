<main class="flex-1 min-w-0 transition-all duration-300" x-data="itemCreator()">
    <a href="?page=master/item" class="inline-flex items-center px-3 py-1.5 text-xs bg-gray-200 text-gray-700 rounded-md shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-75 transition duration-300 mb-4">
        <i class="fas fa-arrow-left mr-1"></i> Kembali
    </a>

    <h1 class="text-sm sm:text-lg font-bold mb-4 text-gray-800">Tambah Item</h1>

    <div class="bg-white shadow-md rounded-lg p-3 sm:p-4">
        <form @submit.prevent="submitForm" class="space-y-3 sm:space-y-4">

            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                <label for="sku" class="w-20 sm:w-28 text-xs text-gray-700 font-medium">SKU</label>
                <input type="text" id="sku" x-model="formData.sku" required
                    class="flex-1 p-1.5 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Masukkan SKU" autocomplete="off">
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                <label for="itemNs" class="w-20 sm:w-28 text-xs text-gray-700 font-medium">Item Ns</label>
                <input type="text" id="itemNs" x-model="formData.itemNs" required
                    class="flex-1 p-1.5 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Masukkan nama item" autocomplete="off">
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                <label for="kategoryNs" class="w-20 sm:w-28 text-xs text-gray-700 font-medium">Kategory Ns</label>
                <input type="text" id="kategoryNs" x-model="formData.kategoryNs" required
                    class="flex-1 p-1.5 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Masukkan kategori" autocomplete="off">
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                <label for="isActive" class="w-20 sm:w-28 text-xs text-gray-700 font-medium">Aktif</label>
                <div class="flex-1">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="isActive" x-model="formData.isActive" class="sr-only peer" :value="1" :true-value="1" :false-value="0">
                        <div class="relative w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        <span class="ms-2 text-xs font-medium text-gray-900 dark:text-gray-300" x-text="formData.isActive ? 'Ya' : 'Tidak'"></span>
                    </label>
                </div>
            </div>

            <div class="flex justify-end pt-3">
                <button type="submit" :disabled="isLoading"
                    class="px-4 py-2 text-xs bg-blue-600 text-white font-semibold rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-75 transition duration-300 flex items-center justify-center"
                    :class="{'opacity-50 cursor-not-allowed': isLoading}">

                    <span x-show="!isLoading">Simpan Item</span>
                    <span x-show="isLoading" class="flex items-center text-xs">
                        <i class="fas fa-spinner fa-spin -ml-1 mr-1 h-3 w-3 text-white"></i>
                        Menyimpan...
                    </span>
                </button>
            </div>
        </form>
    </div>
</main>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('itemCreator', () => ({
            formData: {
                sku: '',
                itemNs: '',
                kategoryNs: '',
                isActive: 0 // Default to 0 (false)
            },
            isLoading: false,

            async submitForm() {
                this.isLoading = true;

                try {
                    const response = await fetch('./controller/master/item.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            sku: this.formData.sku,
                            item: this.formData.itemNs,
                            kategory: this.formData.kategoryNs,
                            isActive: this.formData.isActive // Include isActive in the payload
                        })
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        throw new Error(data.error || 'Gagal menyimpan item');
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Item berhasil ditambahkan!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        this.formData = {
                            sku: '',
                            itemNs: '',
                            kategoryNs: '',
                            isActive: 0 // Reset to default after success
                        };
                        window.location.href = '?page=master/item';
                    });

                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyimpan!',
                        text: error.message || 'Terjadi kesalahan saat menyimpan item.',
                        confirmButtonText: 'OK'
                    });
                } finally {
                    this.isLoading = false;
                }
            }
        }));
    });
</script>