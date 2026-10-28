<main class="flex-1 min-w-0 transition-all duration-300">
    <div class="container mx-auto px-2">
        <div x-data="stockReportForm()" x-init="init()" class="mb-4">
            <div class="bg-white p-3 rounded-lg shadow-md mb-4 no-print">
                <h2 class="text-sm font-semibold mb-3 text-gray-800">Filter Laporan Stok</h2>
                <form @submit.prevent="generateReport()" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label for="reportDate" class="block text-xs font-medium text-gray-700 mb-1">Tanggal Laporan</label>
                        <input type="date" id="reportDate" x-model="filters.reportDate"
                            class="w-full text-xs px-2 py-1.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            required>
                    </div>
                    <div>
                        <label for="storage" class="block text-xs font-medium text-gray-700 mb-1">Lokasi Stok</label>
                        <select id="storage" x-model="filters.storage"
                            class="w-full text-xs px-2 py-1.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="Semua">Semua Lokasi</option>
                            <template x-for="loc in availableLocations" :key="loc">
                                <option :value="loc" x-text="loc"></option>
                            </template>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit"
                            class="flex-1 text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md flex items-center justify-center"
                            :disabled="isLoading">
                            <span x-show="!isLoading" class="flex items-center">
                                <i class="fas fa-search w-3 h-3 mr-1"></i>
                                Tampilkan Laporan
                            </span>
                            <span x-show="isLoading" class="flex items-center">
                                <i class="fas fa-spinner fa-spin -ml-0.5 mr-1 h-3 w-3 text-white"></i>
                                Memuat...
                            </span>
                        </button>
                        <button type="button" @click="downloadReport()" x-show="reportData.length > 0"
                            class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-md flex items-center"
                            :disabled="isLoading">
                            <i class="fas fa-arrow-down-to-bracket w-3 h-3 mr-1"></i>
                            Download PDF
                        </button>
                    </div>
                </form>
            </div>

            <div x-show="isLoading"
                class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 no-print" x-transition>
                <div class="bg-white p-4 rounded-lg shadow-lg flex items-center">
                    <i class="fas fa-spinner fa-spin -ml-0.5 mr-2 h-6 w-6 text-blue-600"></i>
                    <span class="text-sm font-medium text-gray-700">Memuat data laporan...</span>
                </div>
            </div>

            <div x-show="reportData.length > 0" x-ref="reportContent" class="bg-white p-4 rounded-lg shadow-md" x-transition>
                <div class="fixed inset-0 flex items-center justify-center pointer-events-none opacity-10 text-xl sm:text-3xl font-bold text-gray-500 -z-10">PT CITRAGUNA LESTARI</div>
                <div class="flex items-start mb-3">
                    <img src="./assets/img/logo.png" alt="Company Logo" class="w-16 sm:w-20 mr-2">
                    <div class="flex flex-col">
                        <h1 class="text-sm font-bold text-black">LAPORAN STOK HARIAN</h1>
                        <h1 class="text-sm font-bold text-black">PT CITRAGUNA LESTARI</h1>
                    </div>
                </div>

                <div class="mb-3 border rounded-md overflow-hidden p-2">
                    <div class="flex flex-col gap-1 text-xs">
                        <div class="items-center">
                            <span class="text-gray-600 mr-1">Stok Akhir per:</span>
                            <strong x-text="formatDate(filters.reportDate)"></strong>
                        </div>
                        <div class="flex items-center">
                            <span class="text-gray-600 mr-1">No. Dokumen:</span>
                            <strong>2025/PRO/RPA/CGL/FORM-26</strong>
                        </div>
                    </div>
                </div>

                <div class="mb-3 border rounded-md overflow-hidden">
                    <table class="w-full border-collapse">
                        <tbody>
                            <tr>
                                <td class="border p-1 text-center w-1/3">
                                    <div class="text-xs font-semibold">Membuat</div>
                                    <div class="border-t border-black mt-6 mb-1 mx-2"></div>
                                    <div class="text-xs font-medium" x-text="loggedInUserName"></div>
                                    <div class="text-xs text-black">Petugas Chiller</div>
                                </td>
                                <td class="border p-1 text-center w-1/3">
                                    <div class="text-xs font-semibold">Menyetujui</div>
                                    <div class="border-t border-black mt-6 mb-1 mx-2"></div>
                                    <div class="text-xs font-medium">Ridho/Bambang</div>
                                    <div class="text-xs text-black">Supervisor Produksi</div>
                                </td>
                                <td class="border p-1 text-center w-1/3">
                                    <div class="text-xs font-semibold">Mengetahui</div>
                                    <div class="border-t border-black mt-6 mb-1 mx-2"></div>
                                    <div class="text-xs font-medium">Yayah Pajriah</div>
                                    <div class="text-xs text-black">Manager Produksi</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 mb-3 border rounded-md overflow-hidden bg-white shadow">
                    <h4 class="text-xs font-semibold px-2 pt-2 pb-1 text-gray-800 border-b">Total Stok per Chiller</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs border-collapse text-center">
                            <thead class="bg-gray-100 text-gray-700 font-medium">
                                <tr>
                                    <th class="border p-1.5 text-left bg-gray-50"> </th>
                                    <template x-for="loc in reportData" :key="loc.location">
                                        <th class="border p-1.5" x-text="loc.location"></th>
                                    </template>
                                    <th class="border p-1.5 bg-blue-50 text-blue-700">Grand Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="hover:bg-gray-50">
                                    <td class="border p-1.5 font-semibold text-left bg-gray-50">E/P</td>
                                    <template x-for="loc in reportData" :key="loc.location">
                                        <td class="border p-1.5"
                                            x-text="loc.items.reduce((sum, item) => sum + (parseInt(item.current_ep) || 0), 0)">
                                        </td>
                                    </template>
                                    <td class="border p-1.5 font-bold bg-blue-50 text-blue-700"
                                        x-text="reportData.reduce((total, loc) => total + loc.items.reduce((sum, item) => sum + (parseInt(item.current_ep) || 0), 0), 0)">
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50">
                                    <td class="border p-1.5 font-semibold text-left bg-gray-50">KG</td>
                                    <template x-for="loc in reportData" :key="loc.location">
                                        <td class="border p-1.5"
                                            x-text="loc.items.reduce((sum, item) => sum + (parseFloat(item.current_kg) || 0), 0).toFixed(2)">
                                        </td>
                                    </template>
                                    <td class="border p-1.5 font-bold bg-blue-50 text-blue-700"
                                        x-text="reportData.reduce((total, loc) => total + loc.items.reduce((sum, item) => sum + (parseFloat(item.current_kg) || 0), 0), 0).toFixed(2)">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="text-xs text-gray-600 mb-3">
                    * Apabila terdapat ketidaksesuaian jumlah stok, silakan coret angka yang keliru dan isikan angka yang
                    benar di sampingnya.
                </div>

                <template x-for="(locationData, locIndex) in reportData" :key="locIndex">
                    <div class="mb-4">
                        <h3 class="text-xs font-semibold mb-1 text-blue-700" x-text="locationData.location"></h3>
                        <div class="flex flex-wrap -mx-0.5">
                            <div class="w-full sm:w-1/2 px-0.5">
                                <table class="w-full border-collapse text-xs">
                                    <thead>
                                        <tr class="bg-gray-100">
                                            <th class="border p-1 font-semibold text-gray-700 text-center w-8">No.</th>
                                            <th class="border p-1 font-semibold text-gray-700 text-left">Nama Item</th>
                                            <th class="border p-1 font-semibold text-gray-700 text-center w-10">E/P</th>
                                            <th class="border p-1 font-semibold text-gray-700 text-center w-12">KG</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(item, idx) in locationData.items.slice(0, Math.ceil(locationData.items.length/2))" :key="idx">
                                            <tr>
                                                <td class="border p-1 text-center"
                                                    x-text="(locIndex === 0 ? 0 : reportData.slice(0, locIndex).reduce((sum, ld) => sum + ld.items.length, 0)) + idx + 1">
                                                </td>
                                                <td class="border p-1 text-left" x-text="item.item"></td>
                                                <td class="border p-1 text-center" x-text="formatNumber(item.current_ep)"></td>
                                                <td class="border p-1 text-center" x-text="formatNumber(item.current_kg, 2)">
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                            <div class="w-full sm:w-1/2 px-0.5"
                                x-show="locationData.items.length > Math.ceil(locationData.items.length/2)">
                                <table class="w-full border-collapse text-xs">
                                    <thead>
                                        <tr class="bg-gray-100">
                                            <th class="border p-1 font-semibold text-gray-700 text-center w-8">No.</th>
                                            <th class="border p-1 font-semibold text-gray-700 text-left">Nama Item</th>
                                            <th class="border p-1 font-semibold text-gray-700 text-center w-10">E/P</th>
                                            <th class="border p-1 font-semibold text-gray-700 text-center w-12">KG</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(item, idx) in locationData.items.slice(Math.ceil(locationData.items.length/2))" :key="idx">
                                            <tr>
                                                <td class="border p-1 text-center"
                                                    x-text="(locIndex === 0 ? 0 : reportData.slice(0, locIndex).reduce((sum, ld) => sum + ld.items.length, 0)) + Math.ceil(locationData.items.length/2) + idx + 1">
                                                </td>
                                                <td class="border p-1 text-left" x-text="item.item"></td>
                                                <td class="border p-1 text-center" x-text="formatNumber(item.current_ep)"></td>
                                                <td class="border p-1 text-center" x-text="formatNumber(item.current_kg, 2)">
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="mt-4" x-show="filters.storage === 'Semua' || reportData.length > 0">
                    <h4 class="text-xs font-semibold mb-1 text-gray-800">Tabel Tambahan (Data Aktual)</h4>
                    <table class="w-full border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border p-1 font-semibold text-gray-700 text-center w-8">No.</th>
                                <th class="border p-1 font-semibold text-gray-700 text-left">Nama Item</th>
                                <th class="border p-1 font-semibold text-gray-700 text-center">Ekor/Pack Actual</th>
                                <th class="border p-1 font-semibold text-gray-700 text-center">Berat (Kg) Actual</th>
                                <th class="border p-1 font-semibold text-gray-700 text-center">Chiller</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="i in 10" :key="i">
                                <tr>
                                    <td class="border p-1 text-center" x-text="i"></td>
                                    <td class="border p-1 text-left"></td>
                                    <td class="border p-1 text-center"></td>
                                    <td class="border p-1 text-center"></td>
                                    <td class="border p-1 text-center"
                                        x-text="filters.storage !== 'Semua' ? filters.storage : ''"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 text-left text-xs text-gray-500">
                    Dicetak pada: <span x-text="new Date().toLocaleString('id-ID')"></span>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('stockReportForm', () => ({
            filters: {
                reportDate: new Date().toISOString().slice(0, 10),
                storage: 'Semua',
            },
            availableLocations: [],
            isLoading: false,
            errorMessage: '',
            successMessage: '',
            loggedInUserName: "<?php echo $_SESSION['user_nama'] ?? 'Petugas Chiller'; ?>",
            loggedInUserId: "<?php echo $_SESSION['user_id'] ?? null; ?>",
            reportData: [],

            init() {
                this.fetchFilters();
            },

            async fetchFilters() {
                this.isLoading = true;
                try {
                    const response = await fetch('./controller/report/report_api.php?action=get_filters');
                    if (!response.ok) throw new Error('Failed to load filters');
                    const result = await response.json();
                    if (result.status === 'success') {
                        this.availableLocations = result.data.locations;
                    } else {
                        this.errorMessage = result.message || 'Failed to load filters';
                    }
                } catch (error) {
                    console.error('Error fetching filters:', error);
                    this.errorMessage = 'Error loading filters';
                } finally {
                    this.isLoading = false;
                }
            },

            async generateReport() {
                this.isLoading = true;
                this.errorMessage = '';
                this.reportData = [];

                try {
                    const params = new URLSearchParams({
                        action: 'get_stock_data',
                        reportDate: this.filters.reportDate,
                        storage: this.filters.storage,
                    });

                    const response = await fetch(`./controller/report/report_api.php?${params}`);

                    if (!response.ok) {
                        const errorText = await response.text();
                        throw new Error(`Failed to generate report: ${response.status} ${response.statusText} - ${errorText}`);
                    }
                    const result = await response.json();

                    if (result.status === 'success') {
                        this.reportData = result.data;
                        if (this.reportData.length === 0) {
                            this.errorMessage = 'No stock data found for selected criteria';
                            console.warn('No data found for report.');
                        } else {
                            this.successMessage = 'Data loaded successfully. Ready to download.';
                        }
                    } else {
                        this.errorMessage = result.message || 'Failed to generate report';
                    }
                } catch (error) {
                    console.error('Error generating report data:', error);
                    this.errorMessage = 'Error generating report data. Please check console for details.';
                } finally {
                    this.isLoading = false;
                }
            },

            splitData(items) {
                const half = Math.ceil(items.length / 2);
                return [items.slice(0, half), items.slice(half)];
            },

            getItemNumber(locIndex, colIndex, idx, totalItemsInLocation) {
                let base = 0;
                for (let i = 0; i < locIndex; i++) {
                    base += this.reportData[i].items.length;
                }
                if (colIndex === 1) {
                    base += Math.ceil(totalItemsInLocation / 2);
                }
                return base + idx + 1;
            },

            formatDate(dateString) {
                if (!dateString) return '';
                const options = {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric'
                };
                return new Date(dateString).toLocaleDateString('id-ID', options);
            },

            formatNumber(value, decimals = 0) {
                return new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals
                }).format(value);
            },

            async downloadReport() {
                if (this.reportData.length === 0) {
                    alert('No data to download. Please generate the report data first.');
                    console.warn('Download attempt aborted: No report data available.');
                    return;
                }

                this.isLoading = true;
                this.errorMessage = '';
                this.successMessage = '';

                try {
                    if (!window.jspdf || !window.html2canvas) {
                        throw new Error('Required libraries (jsPDF or html2canvas) are not loaded. Please ensure script tags are correct.');
                    }

                    const {
                        jsPDF
                    } = window.jspdf;
                    const pdf = new jsPDF({
                        orientation: 'portrait',
                        unit: 'mm',
                        format: 'a4'
                    });

                    const margin = 5;
                    const pageHeight = pdf.internal.pageSize.getHeight();
                    const pageWidth = pdf.internal.pageSize.getWidth();

                    // Fixed constants for better spacing
                    const HEADER_HEIGHT_MM = 20;
                    const INFO_BOX_HEIGHT_MM = 10;
                    const SIGNATURE_TABLE_HEIGHT_MM = 22;
                    const VERTICAL_SPACING_HEADER_CONTENT = 5;
                    const VERTICAL_SPACING_BETWEEN_ELEMENTS = 4;

                    const initialContentStartY = margin + HEADER_HEIGHT_MM + INFO_BOX_HEIGHT_MM + VERTICAL_SPACING_HEADER_CONTENT + SIGNATURE_TABLE_HEIGHT_MM + VERTICAL_SPACING_BETWEEN_ELEMENTS;
                    const subsequentPageContentStartY = margin + 5;
                    const FOOTER_HEIGHT_MM = 10;

                    let currentY = 0;
                    let isFirstPage = true;
                    let isFirstContentOnPage = true;

                    const currentDateTime = new Date().toLocaleString('id-ID', {
                        day: '2-digit',
                        month: 'long',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    // --- Enhanced Watermark Generation ---
                    let watermarkDataUrl = '';
                    try {
                        const watermarkDiv = document.createElement('div');
                        watermarkDiv.style.width = `${pageWidth}mm`;
                        watermarkDiv.style.height = `${pageHeight}mm`;
                        watermarkDiv.style.position = 'absolute';
                        watermarkDiv.style.left = '-9999px';
                        watermarkDiv.style.top = '-9999px';
                        watermarkDiv.style.overflow = 'hidden';

                        watermarkDiv.innerHTML = `
                            <!-- Watermark SVG pattern: chicken motif, very light opacity 
                           <i class="fas fa-chicken"></i>
                            -->
                        `;

                        document.body.appendChild(watermarkDiv);
                        const watermarkCanvas = await html2canvas(watermarkDiv, {
                            scale: 1,
                            useCORS: true,
                            logging: false,
                            backgroundColor: null,
                        });
                        document.body.removeChild(watermarkDiv);
                        watermarkDataUrl = watermarkCanvas.toDataURL('image/png', 0.3);
                    } catch (error) {
                        console.warn('Watermark generation failed:', error);
                        watermarkDataUrl = '';
                    }

                    // --- Header Generation Function ---
                    const addHeader = async (pdfDoc) => {

                        if (watermarkDataUrl) {
                            pdfDoc.addImage(watermarkDataUrl, 'PNG', 0, 0, pageWidth, pageHeight);
                        }

                        // Logo (existing code - no change needed here)
                        const logoImg = new Image();
                        logoImg.src = './assets/img/logo.png';
                        await new Promise((resolve) => {
                            logoImg.onload = resolve;
                            logoImg.onerror = resolve;
                        });

                        if (logoImg.complete && logoImg.naturalHeight > 0) {
                            const logoWidth = 20;
                            const logoHeight = (logoImg.naturalHeight * logoWidth) / logoImg.naturalWidth;
                            const logoX = margin;
                            const logoY = margin;

                            pdfDoc.addImage(logoImg, 'PNG', logoX, logoY, logoWidth, logoHeight);

                            // Judul (existing code - no change needed here)
                            pdfDoc.setFontSize(11);
                            pdfDoc.setTextColor(0, 0, 0);
                            pdfDoc.text('LAPORAN STOK HARIAN   |   PT CITRAGUNA LESTARI', pageWidth / 2, logoY + 6, {
                                align: 'center'
                            });

                            // Info Box (existing code - no change needed here)
                            const infoBoxY = logoY + 10;
                            const infoBoxHeight = 12;
                            const boxX = margin;
                            const contentWidth = pageWidth - margin * 2;

                            pdfDoc.setDrawColor(0);
                            pdfDoc.setLineWidth(0.2);
                            pdfDoc.rect(boxX, infoBoxY, contentWidth, infoBoxHeight);

                            pdfDoc.setFontSize(8);

                            const labelX = boxX + 8;
                            const valueX = boxX + 30;

                            pdfDoc.text('Stok Akhir per:', labelX, infoBoxY + 5.2);
                            pdfDoc.text(this.formatDate(this.filters.reportDate), valueX, infoBoxY + 5.2);

                            const docNumber = '2025/PRO/RPA/CGL/FORM-26';
                            pdfDoc.text('No. Dokumen:', labelX, infoBoxY + 9.2);
                            pdfDoc.text(docNumber, valueX, infoBoxY + 9.2);

                            // Signature Table (existing code - no change needed here)
                            const tableY = infoBoxY + infoBoxHeight + 4;
                            const tableHeight = 22;
                            const colWidth = contentWidth / 3;

                            pdfDoc.setDrawColor(0);
                            pdfDoc.setLineWidth(0.2);
                            pdfDoc.rect(boxX, tableY, contentWidth, tableHeight);

                            pdfDoc.line(boxX + colWidth, tableY, boxX + colWidth, tableY + tableHeight);
                            pdfDoc.line(boxX + colWidth * 2, tableY, boxX + colWidth * 2, tableY + tableHeight);

                            pdfDoc.setFontSize(8);
                            const signatureTextTopPadding = 3;
                            pdfDoc.text('Membuat', boxX + colWidth / 2, tableY + signatureTextTopPadding + 2, {
                                align: 'center'
                            });
                            pdfDoc.text('Menyetujui', boxX + colWidth * 1.5, tableY + signatureTextTopPadding + 2, {
                                align: 'center'
                            });
                            pdfDoc.text('Mengetahui', boxX + colWidth * 2.5, tableY + signatureTextTopPadding + 2, {
                                align: 'center'
                            });

                            const lineY = tableY + 13;
                            pdfDoc.line(boxX + colWidth * 0.2, lineY, boxX + colWidth * 0.8, lineY);
                            pdfDoc.line(boxX + colWidth * 1.2, lineY, boxX + colWidth * 1.8, lineY);
                            pdfDoc.line(boxX + colWidth * 2.2, lineY, boxX + colWidth * 2.8, lineY);

                            pdfDoc.setFontSize(7.5);
                            pdfDoc.text(this.loggedInUserName, boxX + colWidth / 2, lineY + 4, {
                                align: 'center'
                            });
                            pdfDoc.text('Ridho/Bambang', boxX + colWidth * 1.5, lineY + 4, {
                                align: 'center'
                            });
                            pdfDoc.text('Yayah Pajriah', boxX + colWidth * 2.5, lineY + 4, {
                                align: 'center'
                            });

                            pdfDoc.setFontSize(7);
                            pdfDoc.text('Petugas Chiller', boxX + colWidth / 2, lineY + 7, {
                                align: 'center'
                            });
                            pdfDoc.text('Supervisor Produksi', boxX + colWidth * 1.5, lineY + 7, {
                                align: 'center'
                            });
                            pdfDoc.text('Manager Produksi', boxX + colWidth * 2.5, lineY + 7, {
                                align: 'center'
                            });

                            // --- START OF CHANGES ---
                            const extraSpacingAfterSignatureTable = 0; // PEPETKAN, set to 0
                            currentY = tableY + tableHeight + VERTICAL_SPACING_BETWEEN_ELEMENTS + extraSpacingAfterSignatureTable;
                            // --- END OF CHANGES ---

                            // Calculate totals per location directly from reportData
                            const locations = (this.reportData || []).map(loc => loc.location);
                            const epValues = [];
                            const kgValues = [];
                            let grandTotalEP = 0;
                            let grandTotalKG = 0;

                            for (const loc of (this.reportData || [])) {
                                const ep = loc.items.reduce((sum, item) => sum + (parseInt(item.current_ep) || 0), 0);
                                const kg = loc.items.reduce((sum, item) => sum + (parseFloat(item.current_kg) || 0), 0);
                                epValues.push(ep);
                                kgValues.push(kg.toFixed(2));
                                grandTotalEP += ep;
                                grandTotalKG += kg;
                            }

                            const grandTotals = [grandTotalEP, grandTotalKG.toFixed(2)];


                            // Draw the total stock table
                            const tableHeaderHeight = 7;
                            const dataRowHeight = 7;
                            const tableBorderWidth = 0.4; // Increased border width for better visibility

                            // --- START OF CHANGES ---
                            // PEPETKAN: set tableStartY directly to currentY, no extra padding
                            const tableStartY = currentY; // Initial Y for the entire table block, now no extra space
                            // --- END OF CHANGES ---

                            // Define colors based on Tailwind CSS equivalents
                            const gray50 = [249, 250, 251];
                            const gray100 = [243, 244, 246];
                            const black = [0, 0, 0];
                            const blue50 = [239, 246, 255];
                            const blue700 = [29, 78, 216];
                            const white = [255, 255, 255];


                            // Add the "Total Stok per Chiller" title for the table (like h4 in HTML)
                            pdfDoc.setFontSize(7);
                            pdfDoc.setTextColor(black[0], black[1], black[2]);
                            pdfDoc.text('Total Stok per Chiller', boxX + 4, tableStartY); // px-4 pt-3 pb-1 roughly translates to this position
                            pdfDoc.setDrawColor(black[0], black[1], black[2]);
                            pdfDoc.setLineWidth(tableBorderWidth);
                            pdfDoc.line(boxX, tableStartY + 4, boxX + contentWidth, tableStartY + 4); // Line below title

                            // Adjust table content start Y after the title and its border
                            let currentTableContentY = tableStartY + 1; // Start drawing table cells below the title

                            const colCount = locations.length;
                            const colW = (contentWidth - 30) / (colCount + 1);

                            // Set font to bold for all table text
                            pdfDoc.setFont(undefined, 'bold');
                            pdfDoc.setTextColor(black[0], black[1], black[2]);

                            // --- Table Header Row ---
                            let y = currentTableContentY;
                            let colX = boxX;

                            pdfDoc.setFillColor(gray50[0], gray50[1], gray50[2]);
                            pdfDoc.rect(colX, y, 30, tableHeaderHeight, 'F');
                            pdfDoc.text('Label', colX + 2, y + tableHeaderHeight / 2 + 1);

                            colX += 30;

                            for (let i = 0; i < colCount; i++) {
                                pdfDoc.setFillColor(gray100[0], gray100[1], gray100[2]);
                                pdfDoc.rect(colX, y, colW, tableHeaderHeight, 'F');
                                pdfDoc.text(locations[i], colX + colW / 2, y + tableHeaderHeight / 2 + 1, {
                                    align: 'center'
                                });
                                colX += colW;
                            }

                            pdfDoc.setFillColor(blue50[0], blue50[1], blue50[2]);
                            pdfDoc.rect(colX, y, colW, tableHeaderHeight, 'F');
                            pdfDoc.setTextColor(blue700[0], blue700[1], blue700[2]);
                            pdfDoc.text('Grand Total', colX + colW / 2, y + tableHeaderHeight / 2 + 1, {
                                align: 'center'
                            });


                            // --- E/P Data Row ---
                            pdfDoc.setTextColor(black[0], black[1], black[2]);
                            y += tableHeaderHeight;
                            colX = boxX;

                            pdfDoc.setFillColor(gray50[0], gray50[1], gray50[2]);
                            pdfDoc.rect(colX, y, 30, dataRowHeight, 'F');
                            pdfDoc.text('E/P', colX + 2, y + dataRowHeight / 2 + 1);

                            colX += 30;

                            for (let i = 0; i < colCount; i++) {
                                pdfDoc.setFillColor(white[0], white[1], white[2]);
                                pdfDoc.rect(colX, y, colW, dataRowHeight, 'F');
                                pdfDoc.text(epValues[i].toString(), colX + colW / 2, y + dataRowHeight / 2 + 1, {
                                    align: 'center'
                                });
                                colX += colW;
                            }

                            pdfDoc.setFillColor(blue50[0], blue50[1], blue50[2]);
                            pdfDoc.rect(colX, y, colW, dataRowHeight, 'F');
                            pdfDoc.setTextColor(blue700[0], blue700[1], blue700[2]);
                            pdfDoc.text(grandTotals[0].toString(), colX + colW / 2, y + dataRowHeight / 2 + 1, {
                                align: 'center'
                            });


                            // --- KG Data Row ---
                            pdfDoc.setTextColor(black[0], black[1], black[2]);
                            y += dataRowHeight;
                            colX = boxX;

                            pdfDoc.setFillColor(gray50[0], gray50[1], gray50[2]);
                            pdfDoc.rect(colX, y, 30, dataRowHeight, 'F');
                            pdfDoc.text('KG', colX + 2, y + dataRowHeight / 2 + 1);

                            colX += 30;

                            for (let i = 0; i < colCount; i++) {
                                pdfDoc.setFillColor(white[0], white[1], white[2]);
                                pdfDoc.rect(colX, y, colW, dataRowHeight, 'F');
                                pdfDoc.text(kgValues[i], colX + colW / 2, y + dataRowHeight / 2 + 1, {
                                    align: 'center'
                                });
                                colX += colW;
                            }

                            pdfDoc.setFillColor(blue50[0], blue50[1], blue50[2]);
                            pdfDoc.rect(colX, y, colW, dataRowHeight, 'F');
                            pdfDoc.setTextColor(blue700[0], blue700[1], blue700[2]);
                            pdfDoc.text(grandTotals[1], colX + colW / 2, y + dataRowHeight / 2 + 1, {
                                align: 'center'
                            });

                            // Reset font to normal
                            pdfDoc.setFont(undefined, 'normal');

                            // Now, draw all the borders at once
                            pdfDoc.setDrawColor(black[0], black[1], black[2]);
                            pdfDoc.setLineWidth(tableBorderWidth);

                            // Outer border of the entire table area (minus the title bar)
                            pdfDoc.rect(boxX, currentTableContentY, contentWidth, (tableHeaderHeight + dataRowHeight * 2));

                            // Horizontal borders
                            pdfDoc.line(boxX, currentTableContentY + tableHeaderHeight, boxX + contentWidth, currentTableContentY + tableHeaderHeight);
                            pdfDoc.line(boxX, currentTableContentY + tableHeaderHeight + dataRowHeight, boxX + contentWidth, currentTableContentY + tableHeaderHeight + dataRowHeight);

                            // Vertical borders
                            pdfDoc.line(boxX + 30, currentTableContentY, boxX + 30, currentTableContentY + tableHeaderHeight + dataRowHeight * 2);
                            let borderColX = boxX + 30;
                            for (let i = 0; i < colCount; i++) {
                                borderColX += colW;
                                pdfDoc.line(borderColX, currentTableContentY, borderColX, currentTableContentY + tableHeaderHeight + dataRowHeight * 2);
                            }

                            currentY = y + dataRowHeight + VERTICAL_SPACING_BETWEEN_ELEMENTS;
                            // The final currentY should be adjusted to clear the entire table
                            // It's the bottom of the last cell + a little extra spacing
                            currentY = currentTableContentY + tableHeaderHeight + (dataRowHeight * 2) + VERTICAL_SPACING_BETWEEN_ELEMENTS;

                            // Keterangan Catatan (existing code - no change needed here)
                            pdfDoc.setFontSize(7);
                            pdfDoc.setTextColor(80);
                            pdfDoc.text('* Apabila terdapat ketidaksesuaian jumlah stok, silakan coret angka yang keliru dan isikan angka yang benar di sampingnya.', boxX, currentY);
                            currentY += 1; // Move currentY forward for any future elements on the page

                        } // end of if (logoImg.complete && logoImg.naturalHeight > 0)
                    };

                    // --- Add "Total Stok per Chiller" Section ---
                    const totalStokDiv = document.querySelector('[x-ref="totalStokSection"]');
                    if (totalStokDiv) {
                        await Alpine.nextTick(); // 🔥 Tunggu Alpine selesai render

                        const table = totalStokDiv.querySelector('table');
                        if (!table) {
                            console.warn('Tabel tidak ditemukan dalam totalStokSection.');
                        } else {
                            const headerCells = table.querySelectorAll('thead tr th');
                            const locations = Array.from(headerCells).slice(1, -1).map(th => th.textContent.trim());
                            const grandTotals = [];

                            const epValues = [];
                            const kgValues = [];

                            const rows = table.querySelectorAll('tbody tr');
                            rows.forEach((row) => {
                                const cells = row.querySelectorAll('td');
                                const label = cells[0].textContent.trim();
                                const values = Array.from(cells).slice(1).map(td => td.textContent.trim());

                                if (label === 'E/P') {
                                    epValues.push(...values.slice(0, -1));
                                    grandTotals[0] = values.at(-1);
                                } else if (label === 'KG') {
                                    kgValues.push(...values.slice(0, -1));
                                    grandTotals[1] = values.at(-1);
                                }
                            });

                            // Gambar tabel ke PDF
                            const tableStartY = currentY + 3;
                            const colCount = locations.length;
                            const colW = (contentWidth - 30) / (colCount + 1);
                            let x = boxX;
                            let y = tableStartY;

                            pdf.setFontSize(8);
                            pdf.setTextColor(0, 0, 0);

                            // Header
                            pdf.setFillColor(240, 240, 240);
                            pdf.rect(x, y, 30, 7, 'F');
                            pdf.text(' ', x + 2, y + 5);
                            let colX = x + 30;
                            for (let i = 0; i < colCount; i++) {
                                pdf.setFillColor(240, 240, 240);
                                pdf.rect(colX, y, colW, 7, 'F');
                                pdf.text(locations[i], colX + 2, y + 5);
                                colX += colW;
                            }
                            pdf.setFillColor(220, 235, 255);
                            pdf.rect(colX, y, colW, 7, 'F');
                            pdf.setTextColor(30, 64, 175);
                            pdf.text('Grand Total', colX + 2, y + 5);

                            // E/P row
                            y += 7;
                            pdf.setTextColor(0, 0, 0);
                            pdf.setFillColor(245, 245, 245);
                            pdf.rect(x, y, 30, 7, 'F');
                            pdf.text('E/P', x + 2, y + 5);
                            colX = x + 30;
                            for (let i = 0; i < colCount; i++) {
                                pdf.rect(colX, y, colW, 7, 'F');
                                pdf.text(epValues[i], colX + 2, y + 5);
                                colX += colW;
                            }
                            pdf.setFillColor(220, 235, 255);
                            pdf.rect(colX, y, colW, 7, 'F');
                            pdf.setTextColor(30, 64, 175);
                            pdf.text(grandTotals[0], colX + 2, y + 5);

                            // KG row
                            y += 7;
                            pdf.setTextColor(0, 0, 0);
                            pdf.setFillColor(245, 245, 245);
                            pdf.rect(x, y, 30, 7, 'F');
                            pdf.text('KG', x + 2, y + 5);
                            colX = x + 30;
                            for (let i = 0; i < colCount; i++) {
                                pdf.rect(colX, y, colW, 7, 'F');
                                pdf.text(kgValues[i], colX + 2, y + 5);
                                colX += colW;
                            }
                            pdf.setFillColor(220, 235, 255);
                            pdf.rect(colX, y, colW, 7, 'F');
                            pdf.setTextColor(30, 64, 175);
                            pdf.text(grandTotals[1], colX + 2, y + 5);

                            currentY = y + 7 + VERTICAL_SPACING_BETWEEN_ELEMENTS;
                        }
                    }


                    // --- Footer Generation Function ---
                    const addFooter = (pdfDoc, pageNum, totalPages) => {
                        pdfDoc.setFontSize(7);
                        pdfDoc.setTextColor(100, 100, 100);

                        // Date/time at bottom left
                        pdfDoc.text(`Dicetak pada: ${currentDateTime}`, margin, pageHeight - FOOTER_HEIGHT_MM + 5);

                        // Page number at bottom right
                        const pageText = `Halaman ${pageNum} dari ${totalPages}`;
                        const textWidth = pdfDoc.getStringUnitWidth(pageText) * 7 / pdfDoc.internal.scaleFactor;
                        pdfDoc.text(pageText, pageWidth - margin - textWidth, pageHeight - FOOTER_HEIGHT_MM + 5);
                    };

                    // --- New Page Function ---
                    const addNewPage = () => {
                        pdf.addPage();
                        if (watermarkDataUrl) {
                            pdf.addImage(watermarkDataUrl, 'PNG', 0, 0, pageWidth, pageHeight);
                        }
                        currentY = subsequentPageContentStartY;
                        isFirstPage = false;
                        isFirstContentOnPage = true;
                    };

                    // --- Content Generation Function ---
                    const addContentElement = async (elementDiv, elementName) => {

                        document.body.appendChild(elementDiv);
                        const canvas = await html2canvas(elementDiv, {
                            scale: 1.5,
                            useCORS: true,
                            logging: false,
                            backgroundColor: '#FFFFFF',
                        });
                        document.body.removeChild(elementDiv);

                        const imgData = canvas.toDataURL('image/jpeg', 0.9);
                        const pxToMm = 25.4 / 96;
                        const imgWidthPx = canvas.width;
                        const imgHeightPx = canvas.height;

                        const contentWidthMm = pageWidth - (2 * margin);
                        const scaledHeightMm = (imgHeightPx / imgWidthPx) * contentWidthMm;


                        const spaceNeeded = scaledHeightMm + VERTICAL_SPACING_BETWEEN_ELEMENTS;

                        let remainingSpace;
                        if (isFirstPage && isFirstContentOnPage) {
                            remainingSpace = pageHeight - FOOTER_HEIGHT_MM - initialContentStartY;
                        } else {
                            remainingSpace = pageHeight - FOOTER_HEIGHT_MM - currentY;
                        }


                        if (spaceNeeded > remainingSpace - 5) {
                            addNewPage();
                        }

                        pdf.addImage(imgData, 'JPEG', margin, currentY, contentWidthMm, scaledHeightMm);
                        currentY += scaledHeightMm + VERTICAL_SPACING_BETWEEN_ELEMENTS;

                        isFirstContentOnPage = false;
                    };

                    // --- Initialize First Page ---
                    await addHeader(pdf);

                    // --- Estimate row height for dynamic calculations ---
                    const dummyRow = document.createElement('tr');
                    dummyRow.innerHTML = `
                        <td style="border:1px solid #dee2e6; padding:3px; text-align:center;">1</td>
                        <td style="border:1px solid #dee2e6; padding:3px; text-align:left;">Dummy Item Name</td>
                        <td style="border:1px solid #dee2e6; padding:3px; text-align:center;">100</td>
                        <td style="border:1px solid #dee2e6; padding:3px; text-align:center;">10.00</td>
                    `;
                    const dummyTable = document.createElement('table');
                    dummyTable.style.position = 'absolute';
                    dummyTable.style.left = '-9999px';
                    dummyTable.style.top = '-9999px';
                    dummyTable.style.borderCollapse = 'collapse';
                    dummyTable.style.fontFamily = 'Arial,sans-serif';
                    dummyTable.style.fontSize = '11px';
                    dummyTable.appendChild(document.createElement('tbody')).appendChild(dummyRow);
                    document.body.appendChild(dummyTable);

                    const dummyCanvas = await html2canvas(dummyRow, {
                        scale: 1.5,
                        useCORS: true,
                        logging: false,
                        backgroundColor: '#FFFFFF',
                    });
                    document.body.removeChild(dummyTable);

                    const pxToMm = 25.4 / 96;
                    const estimatedRowHeightMm = (dummyCanvas.height * pxToMm) / pdf.internal.scaleFactor;

                    const headerRowTableHeight = estimatedRowHeightMm * 1.5;
                    const contentRowTableHeight = estimatedRowHeightMm;

                    const estimatedTableUnitHeight = headerRowTableHeight + contentRowTableHeight * 0.95;

                    // Perhitungan untuk halaman pertama
                    const maxContentHeightPerPageFirst = pageHeight - initialContentStartY - FOOTER_HEIGHT_MM - VERTICAL_SPACING_BETWEEN_ELEMENTS;
                    const maxRowsPerGridFirstPage = Math.floor((maxContentHeightPerPageFirst / 2 - headerRowTableHeight) / contentRowTableHeight);
                    const MAX_ROWS_PER_GRID_FIRST_PAGE_CAP = 20; // Ini sudah sesuai dengan yang Anda sebutkan
                    const finalMaxRowsPerGridFirstPage = Math.min(maxRowsPerGridFirstPage, MAX_ROWS_PER_GRID_FIRST_PAGE_CAP);

                    // Perhitungan untuk halaman-halaman berikutnya
                    const maxContentHeightPerPageSubsequent = pageHeight - subsequentPageContentStartY - FOOTER_HEIGHT_MM - VERTICAL_SPACING_BETWEEN_ELEMENTS;
                    const maxRowsPerGridSubsequentPages = Math.floor((maxContentHeightPerPageSubsequent / 2 - headerRowTableHeight) / contentRowTableHeight);
                    const MAX_ROWS_PER_GRID_SUBSEQUENT_PAGES_CAP = 39; // Diperbaiki dari 40 menjadi 39
                    const finalMaxRowsPerGridSubsequentPages = Math.min(maxRowsPerGridSubsequentPages, MAX_ROWS_PER_GRID_SUBSEQUENT_PAGES_CAP);

                    console.log('Calculated finalMaxRowsPerGridFirstPage:', finalMaxRowsPerGridFirstPage);
                    console.log('Calculated finalMaxRowsPerGridSubsequentPages:', finalMaxRowsPerGridSubsequentPages);

                    // --- Process Location Data ---
                    let globalItemNumber = 1;
                    for (const [locIndex, locationData] of this.reportData.entries()) {
                        globalItemNumber = 1;

                        const items = locationData.items;
                        const totalItems = items.length;

                        let itemsProcessed = 0;
                        while (itemsProcessed < totalItems) {
                            const currentMaxItemsPerGrid = pdf.internal.getNumberOfPages() === 1 && isFirstPage ? finalMaxRowsPerGridFirstPage : finalMaxRowsPerGridSubsequentPages;
                            const itemsToProcessThisPage = Math.min(currentMaxItemsPerGrid * 2, totalItems - itemsProcessed);

                            const itemsOnThisPageSlice = items.slice(itemsProcessed, itemsProcessed + itemsToProcessThisPage);

                            const locationDiv = document.createElement('div');
                            locationDiv.style.width = `${pageWidth - (2 * margin)}mm`;
                            locationDiv.style.padding = '0';
                            locationDiv.style.margin = '0';
                            locationDiv.style.backgroundColor = '#fff';
                            locationDiv.style.position = 'absolute';
                            locationDiv.style.left = '-9999px';
                            locationDiv.style.top = '-9999px';

                            if (itemsProcessed === 0) {
                                const titleHtml = `<h3 style="font-size:14px; font-weight:600; margin:0 0 8px 0; color:#1d4ed8; padding:0;">${locationData.location}</h3>`;
                                locationDiv.innerHTML = titleHtml;
                            }

                            const gridsContainer = document.createElement('div');
                            gridsContainer.style.display = 'flex';
                            gridsContainer.style.justifyContent = 'space-between';
                            // Mengurangi gap menjadi 2mm
                            gridsContainer.style.gap = '2mm';

                            const table1 = document.createElement('table');
                            // Menyesuaikan lebar tabel untuk gap baru
                            table1.style.width = 'calc(50% - 1mm)';
                            table1.style.borderCollapse = 'collapse';
                            table1.style.fontFamily = 'Arial,sans-serif';
                            table1.style.fontSize = '11px';
                            const thead1 = document.createElement('thead');
                            thead1.innerHTML = `
                                <tr style="background-color:#f8f9fa;">
                                    <th style="border:1px solid #000000; padding:3px; font-weight:1000; text-align:center; width:4%;">No.</th>
                                    <th style="border:1px solid #000000; padding:3px; font-weight:1000; text-align:left; width:40%;">Nama Item</th> 
                                    <th style="border:1px solid #000000; padding:3px; font-weight:1000; text-align:center; width:15%;">E/P</th> 
                                    <th style="border:1px solid #000000; padding:3px; font-weight:1000; text-align:center; width:15%;">KG</th> 
                                </tr>
                            `;
                            table1.appendChild(thead1);
                            const tbody1 = document.createElement('tbody');
                            table1.appendChild(tbody1);

                            const table2 = document.createElement('table');
                            // Menyesuaikan lebar tabel untuk gap baru
                            table2.style.width = 'calc(50% - 1mm)';
                            table2.style.borderCollapse = 'collapse';
                            table2.style.fontFamily = 'Arial,sans-serif';
                            table2.style.fontSize = '12px';
                            const thead2 = document.createElement('thead');
                            thead2.innerHTML = `
                                <tr style="background-color:#f8f9fa;">
                                    <th style="border:1px solid #000000; padding:3px; font-weight:1000; text-align:center; width:4%;">No.</th>
                                    <th style="border:1px solid #000000; padding:3px; font-weight:1000; text-align:left; width:40%;">Nama Item</th> 
                                    <th style="border:1px solid #000000; padding:3px; font-weight:1000; text-align:center; width:15%;">E/P</th> 
                                    <th style="border:1px solid #000000; padding:3px; font-weight:1000; text-align:center; width:15%;">KG</th> 
                                </tr>
                            `;
                            table2.appendChild(thead2);
                            const tbody2 = document.createElement('tbody');
                            table2.appendChild(tbody2);

                            const numItemsInCol1 = Math.ceil(itemsOnThisPageSlice.length / 2);

                            for (let i = 0; i < itemsOnThisPageSlice.length; i++) {
                                const item = itemsOnThisPageSlice[i];
                                const targetTbody = (i < numItemsInCol1) ? tbody1 : tbody2;

                                const tr = document.createElement('tr');
                                tr.innerHTML = `
                                    <td style="border:1px solid #000000; padding:3px; font-weight:1000; text-align:center;">${globalItemNumber}</td>
                                    <td style="border:1px solid #000000; padding:3px; font-weight:1000; text-align:left;">${item.item}</td>
                                    <td style="border:1px solid #000000; padding:3px; font-weight:1000; text-align:center;"></td>
                                    <td style="border:1px solid #000000; padding:3px; font-weight:1000; text-align:center;"></td>
                                `;
                                targetTbody.appendChild(tr);
                                globalItemNumber++;
                            }

                            gridsContainer.appendChild(table1);
                            gridsContainer.appendChild(table2);

                            locationDiv.appendChild(gridsContainer);

                            await addContentElement(locationDiv, `Location: ${locationData.location} (Items ${itemsProcessed + 1}-${itemsProcessed + itemsOnThisPageSlice.length})`);
                            itemsProcessed += itemsOnThisPageSlice.length;
                        }
                    }
                    // --- Add Additional Table ---
                    const additionalTableDiv = document.createElement('div');
                    additionalTableDiv.style.width = `${pageWidth - (2 * margin)}mm`;
                    additionalTableDiv.style.padding = '0';
                    additionalTableDiv.style.margin = '0';
                    additionalTableDiv.style.backgroundColor = '#fff';
                    additionalTableDiv.style.position = 'absolute';
                    additionalTableDiv.style.left = '-9999px';
                    additionalTableDiv.style.top = '-9999px';

                    let additionalTableHtml = '<h4 style="font-size:12px; font-weight:1000; margin:0 0 8px 0; color:#374151;">Tabel Tambahan (Data Aktual)</h4>';
                    additionalTableHtml += '<table style="width:100%; border-collapse:collapse; font-family:Arial,sans-serif; font-size:8px;">';
                    additionalTableHtml += '<thead><tr style="background-color:#f8f9fa;">';
                    additionalTableHtml += '<th style="border:1px solid #000000; padding:8px; font-weight:1000; font-size:12px;  text-align:center; width:8%;">No.</th>';
                    additionalTableHtml += '<th style="border:1px solid #000000; padding:8px; font-weight:1000; font-size:12px; text-align:left; width:35%;">Nama Item</th>';
                    additionalTableHtml += '<th style="border:1px solid #000000; padding:8px; font-weight:1000; font-size:12px; text-align:center; width:19%;">Ekor/Pack Actual</th>';
                    additionalTableHtml += '<th style="border:1px solid #000000; padding:8px; font-weight:1000; font-size:12px; text-align:center; width:19%;">Berat (Kg) Actual</th>';
                    additionalTableHtml += '<th style="border:1px solid #000000; padding:8px; font-weight:1000; font-size:12px; text-align:center; width:19%;">Chiller</th>';
                    additionalTableHtml += '</tr></thead><tbody>';

                    for (let i = 1; i <= 10; i++) {
                        additionalTableHtml += '<tr>';
                        additionalTableHtml += `<td style="border:1px solid #000000; padding:8px; font-weight:1000; font-size:12px; text-align:center; height:18px;">${i}</td>`;
                        additionalTableHtml += `<td style="border:1px solid #000000; padding:8px; font-weight:1000; font-size:12px; text-align:left;"></td>`;
                        additionalTableHtml += `<td style="border:1px solid #000000; padding:8px; font-weight:1000; font-size:12px; text-align:center;"></td>`;
                        additionalTableHtml += `<td style="border:1px solid #000000; padding:8px; font-weight:1000; font-size:12px; text-align:center;"></td>`;
                        additionalTableHtml += `<td style="border:1px solid #000000; padding:8px; font-weight:1000; font-size:12px; text-align:center;">${this.filters.storage !== 'Semua' ? this.filters.storage : ''}</td>`;
                        additionalTableHtml += '</tr>';
                    }

                    additionalTableHtml += '</tbody></table>';
                    additionalTableDiv.innerHTML = additionalTableHtml;

                    await addContentElement(additionalTableDiv, 'Additional Table');

                    // --- Add Footer to All Pages ---
                    const totalPages = pdf.internal.getNumberOfPages();
                    for (let i = 1; i <= totalPages; i++) {
                        pdf.setPage(i);
                        addFooter(pdf, i, totalPages);
                    }

                    // --- Generate and Download PDF ---
                    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
                    const reportDateFormatted = this.filters.reportDate.split('-').reverse().join('-');
                    const tempFilenameForClientSave = `Laporan_Stok_${this.filters.storage}_${reportDateFormatted}_${timestamp}.pdf`;

                    const pdfBlob = pdf.output('blob');

                    const logResult = await this.logDownload(tempFilenameForClientSave, pdfBlob);

                    if (logResult.status === 'success' && logResult.fileName) {
                        const finalDownloadFilename = logResult.fileName;
                        pdf.save(finalDownloadFilename);
                        this.successMessage = 'Laporan berhasil diunduh!';
                    } else {
                        throw new Error('Server did not return a valid filename for download.');
                    }

                } catch (error) {
                    console.error('CRITICAL ERROR during PDF generation:', error);
                    this.errorMessage = `Error generating PDF: ${error.message || 'Unknown error'}`;
                    alert(`Error generating PDF: ${error.message || 'Unknown error. Check console for details.'}`);
                } finally {
                    this.isLoading = false;
                }
            },

            async logDownload(filename, pdfBlob) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'save_and_log_pdf');
                    formData.append('pdfFile', pdfBlob, filename); // Keep this for the initial upload
                    formData.append('createdBy', this.loggedInUserName);
                    formData.append('loggedInUserId', this.loggedInUserId);
                    formData.append('reportDate', this.filters.reportDate);
                    formData.append('storage', this.filters.storage);
                    formData.append('jenis_dokumen', 'SO Chiller');

                    const response = await fetch('./controller/report/download_report.php', {
                        method: 'POST',
                        body: formData
                    });

                    if (!response.ok) {
                        const errorText = await response.text();
                        throw new Error(`Server failed to log download: ${response.status} ${response.statusText} - ${errorText}`);
                    }

                    const result = await response.json();
                    if (result.status === 'success') {
                        // Return the fileName received from the server
                        return {
                            status: 'success',
                            fileName: result.fileName
                        };
                    } else {
                        throw new Error(result.message || 'Failed to log download');
                    }
                } catch (error) {
                    console.error('Error logging download:', error);
                    throw error;
                }
            }

        }));
    });
</script>