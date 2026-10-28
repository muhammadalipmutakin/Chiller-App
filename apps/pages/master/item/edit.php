<main class="flex-1 min-w-0 transition-all duration-300" x-data="itemEditor()" x-init="init()">
    <a href="?page=master/item" class="inline-flex items-center px-3 py-1.5 text-xs bg-gray-200 text-gray-700 rounded-md shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-75 transition duration-300 mb-4">
        <i class="fas fa-arrow-left mr-1"></i> Kembali
    </a>

    <h1 class="text-sm sm:text-lg font-bold mb-4 text-gray-800">Edit Item</h1>

    <div class="bg-white shadow-md rounded-lg p-3 sm:p-4">
        <div x-show="isLoading" class="mb-3 p-2 text-xs bg-blue-100 border border-blue-400 text-blue-700 rounded">
            Memuat data item...
        </div>

        <form x-show="!isLoading" @submit.prevent="submitForm" class="space-y-3 sm:space-y-4">

            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                <label for="sku" class="w-20 sm:w-28 text-xs text-gray-700 font-medium">SKU</label>
                <input type="text" id="sku" x-model="formData.sku" readonly
                    class="flex-1 p-1.5 text-xs border border-gray-300 rounded-md bg-gray-100 cursor-not-allowed">
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                <label for="itemNs" class="w-20 sm:w-28 text-xs text-gray-700 font-medium">Item Ns</label>
                <input type="text" id="itemNs" x-model="formData.itemNs" required
                    class="flex-1 p-1.5 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Masukkan nama item">
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                <label for="kategoryNs" class="w-20 sm:w-28 text-xs text-gray-700 font-medium">Kategory Ns</label>
                <input type="text" id="kategoryNs" x-model="formData.kategoryNs" required
                    class="flex-1 p-1.5 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Masukkan kategori">
            </div>

            <div class="flex justify-end pt-3">
                <button type="submit" :disabled="isSubmitting"
                    class="px-4 py-2 text-xs bg-blue-600 text-white font-semibold rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-75 transition duration-300 flex items-center justify-center"
                    :class="{'opacity-50 cursor-not-allowed': isSubmitting}">

                    <span x-show="!isSubmitting">Perbarui Item</span>
                    <span x-show="isSubmitting" class="flex items-center text-xs">
                        <i class="fas fa-spinner fa-spin -ml-1 mr-1 h-3 w-3 text-white"></i>
                        Memproses...
                    </span>
                </button>
            </div>
        </form>
    </div>
</main>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('itemEditor', () => ({
            formData: {
                sku: '',
                itemNs: '',
                kategoryNs: '',
                isActive: 0 // Initialize isActive as 0 (false)
            },
            isLoading: false,
            isSubmitting: false,

            init() {
                // Get SKU from URL
                const urlParams = new URLSearchParams(window.location.search);
                const sku = urlParams.get('sku');

                if (!sku) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Kesalahan!',
                        text: 'SKU tidak ditemukan di URL. Tidak dapat memuat data item.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = '?page=master/item';
                    });
                    return;
                }

                this.fetchItemData(sku);
            },

            async fetchItemData(sku) {
                this.isLoading = true;
                try {
                    const response = await fetch(`./controller/master/item.php?sku=${sku}`);
                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        throw new Error(data.error || 'Gagal memuat data item');
                    }

                    // Assuming data.data will contain an array, and we need the first item
                    if (data.data.length === 0) {
                        throw new Error('Item tidak ditemukan');
                    }

                    const item = data.data[0];
                    this.formData = {
                        sku: item.sku,
                        itemNs: item.item,
                        kategoryNs: item.kategory,
                        isActive: item.isActive // Populate isActive from fetched data
                    };

                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Memuat Data',
                        text: error.message || 'Terjadi kesalahan saat memuat data item.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = '?page=master/item';
                    });
                } finally {
                    this.isLoading = false;
                }
            },

            async submitForm() {
                this.isSubmitting = true;

                try {
                    const response = await fetch(`./controller/master/item.php?sku=${this.formData.sku}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            item: this.formData.itemNs,
                            kategory: this.formData.kategoryNs,
                            isActive: this.formData.isActive // Include isActive in the update payload
                        })
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        throw new Error(data.error || 'Gagal memperbarui item');
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Item berhasil diperbarui!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = '?page=master/item';
                    });

                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Memperbarui!',
                        text: error.message || 'Terjadi kesalahan saat memperbarui item.',
                        confirmButtonText: 'OK'
                    });
                } finally {
                    this.isSubmitting = false;
                }
            }
        }));
    });
</script>