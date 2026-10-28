<?php
// Jika Anda perlu memuat sesuatu dari session di halaman ini
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

<main class="flex-1 min-w-0 transition-all duration-300">
    <div class="container mx-auto px-2 sm:px-4 lg:px-6">
        <div x-data="downloadHistory()" x-init="init()" class="mb-4">
            <h1 class="text-sm sm:text-base font-bold mb-3 text-gray-800">Riwayat Download Laporan Stok</h1>

            <div x-show="errorMessage"
                class="mb-3 p-2 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-lg text-xs" x-transition>
                <p x-text="errorMessage"></p>
            </div>

            <div x-show="isLoading" class="flex justify-center items-center py-6">
                <div class="animate-spin rounded-full h-6 w-6 border-2 border-blue-500 border-t-transparent mr-2"></div>
                <span class="text-sm font-medium text-gray-700">Memuat riwayat download...</span>
            </div>

            <!-- Tabel untuk layar besar (lg ke atas) -->
            <div x-show="!isLoading && historyData.length > 0" class="hidden lg:block bg-white p-3 rounded-lg shadow-md overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                No.
                            </th>
                            <th scope="col"
                                class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nama File
                            </th>
                            <th scope="col"
                                class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tanggal Download
                            </th>
                            <th scope="col"
                                class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Download Oleh
                            </th>
                            <th scope="col"
                                class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Jenis Dokumen
                            </th>
                            <th scope="col"
                                class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 text-xs">
                        <template x-for="(item, index) in historyData" :key="item.id">
                            <tr>
                                <td x-text="index + 1" class="px-3 py-2 whitespace-nowrap text-gray-500"></td>
                                <td class="px-3 py-2 font-medium text-gray-900 break-words max-w-xs sm:max-w-sm">
                                    <span x-text="item.file"></span>
                                </td>
                                <td x-text="formatDateTime(item.created_at)"
                                    class="px-3 py-2 whitespace-nowrap text-gray-500"></td>
                                <td x-text="item.created_by" class="px-3 py-2 whitespace-nowrap text-gray-500"></td>
                                <td x-text="item.jenis_dokumen" class="px-3 py-2 whitespace-nowrap text-gray-500"></td>
                                <td class="px-3 py-2 whitespace-nowrap text-left font-medium">
                                    <button @click="previewFile(item.file_url)"
                                        class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <i class="fas fa-eye mr-1"></i> Preview
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Card untuk layar kecil (di bawah lg) -->
            <div x-show="!isLoading && historyData.length > 0" class="lg:hidden space-y-3">
                <template x-for="(item, index) in historyData" :key="item.id">
                    <div class="bg-white rounded-lg shadow-md p-4 border border-gray-200">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-semibold text-gray-900 truncate" x-text="item.file"></h3>
                                <p class="text-xs text-gray-500 mt-1" x-text="formatDateTime(item.created_at)"></p>
                            </div>
                            <button @click="previewFile(item.file_url)"
                                class="ml-2 inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-eye mr-1"></i> Preview
                            </button>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 mt-3">
                            <div>
                                <span class="font-medium">Download Oleh:</span>
                                <span x-text="item.created_by" class="ml-1"></span>
                            </div>
                            <div>
                                <span class="font-medium">Jenis:</span>
                                <span x-text="item.jenis_dokumen" class="ml-1"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="!isLoading && historyData.length === 0 && !errorMessage"
                class="empty-state bg-white p-6 rounded-lg shadow-md flex flex-col items-center justify-center py-6 text-gray-500 text-sm">
                <i class="fas fa-box-open text-4xl mb-2 text-gray-400"></i>
                <p>Belum ada riwayat download yang tercatat.</p>
            </div>

        </div>
    </div>
</main>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('downloadHistory', () => ({
            historyData: [],
            isLoading: false,
            errorMessage: '',

            async init() {
                await this.fetchHistory();
            },

            async fetchHistory() {
                this.isLoading = true;
                this.errorMessage = '';
                try {
                    const response = await fetch('./controller/report/download_history_api.php?action=get_download_history');
                    if (!response.ok) throw new Error('Gagal memuat riwayat download.');

                    const result = await response.json();
                    if (result.status === 'success') {
                        this.historyData = result.data;
                    } else {
                        this.errorMessage = result.message || 'Gagal memuat data riwayat.';
                    }
                } catch (error) {
                    console.error('Error fetching download history:', error);
                    this.errorMessage = 'Terjadi kesalahan saat memuat riwayat download: ' + error.message;
                } finally {
                    this.isLoading = false;
                }
            },

            formatDateTime(dateTimeString) {
                if (!dateTimeString) return '';
                const options = {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false // Format 24 jam
                };
                return new Date(dateTimeString).toLocaleString('id-ID', options);
            },

            previewFile(fileUrl) {
                window.open(fileUrl, '_blank');
            }
        }));
    });
</script>