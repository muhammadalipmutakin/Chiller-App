<style>
    /* General print styles */
    @media print {
        body {
            font-size: 8px !important;
            /* Smaller font for print */
            -webkit-print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        .no-print {
            display: none !important;
        }

        table,
        th,
        td {
            border-collapse: collapse !important;
            border: 1px solid #666 !important;
            /* Darker, thinner borders for print */
            padding: 1.5px 2.5px !important;
            font-size: 8px !important;
            height: 12px !important;
        }

        /* Ensure background colors are printed for specific elements */
        .header-company-bg,
        .header-section-bg,
        .total-row-bg,
        .grand-total-row-bg,
        .category-title-bg,
        .category-table-header-bg,
        .category-total-row-bg,
        .chiller-total-bg,
        .category-row-bg-0,
        .category-row-bg-1,
        .category-row-bg-2,
        .category-row-bg-3,
        .category-row-bg-4,
        .category-row-bg-5,
        .category-row-bg-6,
        .category-row-bg-7 {
            -webkit-print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        /* Adjust grid for print to fit better on page */
        .print-grid-cols-1 {
            grid-template-columns: 1fr !important;
        }

        .print-grid-cols-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        .print-grid-cols-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        }
    }

    /* Fixed backgrounds for grand totals and chiller totals */
    .chiller-total-bg {
        background-image: linear-gradient(to right, #4F46E5, #6366F1);
        /* Indigo gradient */
    }

    .grand-total-row-bg {
        background-image: linear-gradient(to right, #4338CA, #4F46E5);
        /* Darker indigo gradient */
    }

    /* Default text color for dynamic backgrounds */
    .category-title-text-color {
        color: #1F2937;
        /* Dark gray for category titles as per image */
    }

    .category-header-text-color {
        color: #1F2937;
        /* Dark gray for category headers */
    }

    .category-total-text-color {
        color: white;
        /* White for category totals */
    }


    /* Define specific background classes for table rows (for print fallback) */
    /* These are the light shades */
    .category-row-bg-0 {
        background-color: #dbeafe;
    }

    /* blue-100 */
    .category-row-bg-1 {
        background-color: #dcfce7;
    }

    /* green-100 */
    .category-row-bg-2 {
        background-color: #ede9fe;
    }

    /* purple-100 */
    .category-row-bg-3 {
        background-color: #fee2e2;
    }

    /* red-100 */
    .category-row-bg-4 {
        background-color: #fffbeb;
    }

    /* yellow-100 */
    .category-row-bg-5 {
        background-color: #fce7f3;
    }

    /* pink-100 */
    .category-row-bg-6 {
        background-color: #e6fffa;
    }

    /* teal-100 */
    .category-row-bg-7 {
        background-color: #eef2ff;
    }

    /* indigo-100 */

    /* Define specific background classes for category title/header (for print fallback) */
    /* These are the slightly darker shades, matching `categoryHeaderGradientClasses` */
    .category-title-bg-0 {
        background-color: #bfdbfe;
    }

    /* blue-200 */
    .category-title-bg-1 {
        background-color: #bbf7d0;
    }

    /* green-200 */
    .category-title-bg-2 {
        background-color: #ddd6fe;
    }

    /* purple-200 */
    .category-title-bg-3 {
        background-color: #fecaca;
    }

    /* red-200 */
    .category-title-bg-4 {
        background-color: #fef08a;
    }

    /* yellow-200 */
    .category-title-bg-5 {
        background-color: #fbcfe8;
    }

    /* pink-200 */
    .category-title-bg-6 {
        background-color: #99f6e4;
    }

    /* teal-200 */
    .category-title-bg-7 {
        background-color: #c7d2fe;
    }

    /* indigo-200 */


    /* Define specific background classes for category total (for print fallback) */
    /* These are the darker shades, matching `categoryTotalGradientClasses` */
    .category-total-bg-0 {
        background-color: #1e40af;
        color: white;
    }

    /* blue-800 */
    .category-total-bg-1 {
        background-color: #166534;
        color: white;
    }

    /* green-800 */
    .category-total-bg-2 {
        background-color: #4c1d95;
        color: white;
    }

    /* purple-800 */
    .category-total-bg-3 {
        background-color: #991b1b;
        color: white;
    }

    /* red-800 */
    .category-total-bg-4 {
        background-color: #a16207;
        color: white;
    }

    /* yellow-800 */
    .category-total-bg-5 {
        background-color: #831843;
        color: white;
    }

    /* pink-800 */
    .category-total-bg-6 {
        background-color: #0f766e;
        color: white;
    }

    /* teal-800 */
    .category-total-bg-7 {
        background-color: #3730a3;
        color: white;
    }

    /* indigo-800 */
</style>

<main class="flex-1 min-w-0 transition-all duration-300"
    x-data="chillerReport()">
    <div class="max-w-full mx-auto bg-white shadow-lg rounded-lg p-3">
        <div
            class="no-print flex flex-col sm:flex-row items-center gap-2 mb-4 p-3 bg-indigo-50 rounded-lg shadow-sm border border-indigo-200">
            <div class="flex-grow grid grid-cols-1 sm:grid-cols-2 gap-2 w-full sm:w-auto">
                <div class="flex items-center gap-2">
                    <label for="tanggal" class="font-semibold text-indigo-800 flex items-center text-xs w-16">
                        <i class="fas fa-calendar-alt text-indigo-500 mr-1"></i> Tanggal:
                    </label>
                    <input type="date" id="tanggal" x-model="selectedDate"
                        class="border border-indigo-300 rounded-md px-2 py-1 text-xs focus:ring-indigo-500 focus:border-indigo-500 shadow-sm w-full">
                </div>
                <div class="flex items-center gap-2">
                    <label for="storage" class="font-semibold text-indigo-800 flex items-center text-xs w-16">
                        <i class="fas fa-warehouse text-indigo-500 mr-1"></i> Storage:
                    </label>
                    <select id="storage" x-model="selectedStorage"
                        class="border border-indigo-300 rounded-md px-2 py-1 text-xs focus:ring-indigo-500 focus:border-indigo-500 shadow-sm w-full">
                        <template x-for="storageOption in storageOptions" :key="storageOption">
                            <option :value="storageOption" x-text="storageOption"></option>
                        </template>
                    </select>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 mt-2 sm:mt-0 w-full sm:w-auto">
                <button @click="fetchReport"
                    class="btn-action bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-1.5 px-3 rounded-md shadow-sm transition duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-75 text-xs w-full sm:w-auto">
                    <i class="fas fa-search mr-1.5"></i> Tampilkan Data
                </button>
                <button @click="resetFilters"
                    class="btn-action bg-gray-500 hover:bg-gray-600 text-white font-semibold py-1.5 px-3 rounded-md shadow-sm transition duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-75 text-xs w-full sm:w-auto">
                    <i class="fas fa-redo mr-1.5"></i> Reset
                </button>
                <button @click="downloadReport" :disabled="!reportFetched || noData"
                    class="btn-action bg-green-600 hover:bg-green-700 text-white font-semibold py-1.5 px-3 rounded-md shadow-sm transition duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-75 disabled:opacity-50 disabled:cursor-not-allowed text-xs w-full sm:w-auto">
                    <i class="fas fa-download mr-1.5"></i> Download
                </button>
            </div>
        </div>

        <div x-show="loading" class="text-center text-indigo-600 py-6">
            <i class="fas fa-spinner fa-spin text-2xl mb-1"></i>
            <p class="text-xs font-semibold">Memuat data laporan...</p>
        </div>

        <template x-if="reportFetched && !noData && !loading">
            <div class="report-content">
                <div class="mb-3 pb-1.5 border-b-2 border-indigo-300">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-1">
                        <div class="text-left mb-1 sm:mb-0">
                            <h3 class="text-sm font-extrabold text-indigo-900 mb-0" x-text="meta.perusahaan"></h3>
                            <p class="text-indigo-600 italic text-xs" x-text="meta.slogan"></p>
                        </div>
                        <div class="text-right text-gray-700 text-xs grid grid-cols-2 gap-x-1.5">
                            <p class="font-bold text-gray-800 uppercase">No. Dokumen :</p>
                            <p><span x-text="meta.noDokumen"></span></p>
                            <p class="font-bold text-gray-800 uppercase">Tanggal :</p>
                            <p><span x-text="formatDate(selectedDate)"></span></p>
                            <p class="font-bold text-gray-800 uppercase">Storage :</p>
                            <p><span x-text="selectedStorage"></span></p>
                        </div>
                    </div>
                    <h2 class="text-center text-sm font-bold text-gray-900 mt-2 mb-1.5">DATA STOCK CHILLER</h2>
                </div>

                <template x-for="(chillerName) in Object.keys(processedChillerData).sort()" :key="chillerName">
                    <div class="mb-4 p-2 border border-gray-200 rounded-lg shadow-sm bg-gray-50">
                        <h3
                            class="text-sm font-bold text-gray-800 mb-2 pb-1 border-b border-gray-300 flex items-center">
                            <i class="fas fa-warehouse mr-1.5 text-indigo-500"></i>
                            <span x-text="chillerName"></span>
                        </h3>

                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2 mb-3">
                            <template
                                x-for="(categoryKey, categoryIndex) in getCategoryKeys(processedChillerData[chillerName].data, processedChillerData[chillerName].categoryTotals)"
                                :key="chillerName + '-' + categoryKey">
                                <template
                                    x-for="(chunk, chunkIndex) in processedChillerData[chillerName].chunkedData[categoryKey]"
                                    :key="chillerName + '-' + categoryKey + '-' + chunkIndex">
                                    <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
                                        <h4
                                            class="category-title-text-color font-extrabold text-xs py-1 px-2 mb-0 flex items-center transition-colors duration-300 rounded-t-lg"
                                            :class="getCategoryTitleClass(categoryIndex)">
                                            <span x-text="formatCategoryName(categoryKey)"></span>
                                            <template x-if="chunkIndex > 0">
                                                <span class="ml-auto text-gray-700 text-xs flex items-center">
                                                    <i class="fas fa-arrow-right-long mr-0.5"></i> (Lanjutan)
                                                </span>
                                            </template>
                                        </h4>

                                        <div class="p-1 pt-0.5">
                                            <table
                                                class="w-full border-collapse border border-gray-400 text-xs table-fixed">
                                                <thead>
                                                    <tr>
                                                        <th
                                                            class="category-header-text-color font-bold text-center w-[10%] p-1 border border-gray-400"
                                                            :class="getCategoryHeaderClass(categoryIndex)">
                                                            No</th>
                                                        <th
                                                            class="category-header-text-color font-bold text-center w-[50%] p-1 border border-gray-400"
                                                            :class="getCategoryHeaderClass(categoryIndex)">
                                                            Item</th>
                                                        <th
                                                            class="category-header-text-color font-bold text-center w-[20%] p-1 border border-gray-400"
                                                            :class="getCategoryHeaderClass(categoryIndex)">
                                                            E/P</th>
                                                        <th
                                                            class="category-header-text-color font-bold text-center w-[20%] p-1 border border-gray-400"
                                                            :class="getCategoryHeaderClass(categoryIndex)">
                                                            Kg</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="(item, itemIndex) in chunk" :key="itemIndex">
                                                        <tr :class="getCategoryRowClass(categoryIndex)">
                                                            <td class="text-center border border-gray-400 p-0.5"
                                                                x-text="chunkIndex * 20 + itemIndex + 1"></td>
                                                            <td class="text-left border border-gray-400 p-0.5"
                                                                x-text="item.item"></td>
                                                            <td class="text-right border border-gray-400 p-0.5"
                                                                x-text="item.e_p"></td>
                                                            <td class="text-right border border-gray-400 p-0.5"
                                                                x-text="item.kg"></td>
                                                        </tr>
                                                    </template>
                                                    <template
                                                        x-if="chunkIndex === processedChillerData[chillerName].chunkedData[categoryKey].length - 1">
                                                        <tr class="category-total-text-color font-bold"
                                                            :class="getCategoryTotalClass(categoryIndex)">
                                                            <td colspan="2"
                                                                class="border-t-2 text-right p-0.5 border-gray-400">
                                                                Total :</td>
                                                            <td
                                                                class="border-t-2 text-right p-0.5 border-gray-400"
                                                                x-text="processedChillerData[chillerName].categoryTotals[categoryKey].total_e_p">
                                                            </td>
                                                            <td
                                                                class="border-t-2 text-right p-0.5 border-gray-400"
                                                                x-text="processedChillerData[chillerName].categoryTotals[categoryKey].total_kg">
                                                            </td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </template>
                            </template>
                        </div>

                        <div
                            class="mt-3 rounded-lg shadow-md p-2 flex items-center justify-between text-white chiller-total-bg text-sm">
                            <h4 class="text-xs font-extrabold flex items-center text-white">
                                <i class="fas fa-dolly mr-2 text-white/80"></i> TOTAL <span
                                    x-text="chillerName.toUpperCase()"></span> :
                            </h4>
                            <div class="flex gap-4 text-xs font-bold text-white">
                                <p>E/P: <span x-text="processedChillerData[chillerName].chillerTotal.total_e_p"></span>
                                </p>
                                <p>Kg: <span x-text="processedChillerData[chillerName].chillerTotal.total_kg"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </template>

                <template x-if="reportData && reportData.grandTotals && selectedStorage === 'All'">
                    <div
                        class="mt-4 text-white rounded-lg shadow-lg p-3 flex items-center justify-between grand-total-row-bg text-sm">
                        <h3 class="text-sm font-extrabold flex items-center">
                            <i class="fas fa-globe-americas mr-2 text-indigo-200"></i> GRAND TOTAL KESELURUHAN :
                        </h3>
                        <div class="flex gap-4 text-sm font-bold">
                            <p>E/P: <span x-text="reportData.grandTotals.grand_total_e_p"></span></p>
                            <p>Kg: <span x-text="reportData.grandTotals.grand_total_kg"></span></p>
                        </div>
                    </div>
                </template>

                <div class="text-right mt-3 text-gray-700 text-xs">
                    <p class="mb-0">Dibuat Oleh:</p>
                    <p class="font-bold text-gray-800" x-text="meta.dibuatOleh"></p>
                </div>
            </div>
        </template>

        <template x-if="reportFetched && noData && !loading">
            <div
                class="text-center text-gray-500 py-6 border border-dashed border-gray-300 rounded-md mt-4 bg-gray-50">
                <i class="fas fa-exclamation-triangle text-2xl text-yellow-500 mb-1"></i>
                <p class="text-xs font-semibold">Tidak ada data laporan untuk tanggal dan storage yang dipilih.</p>
                <p class="text-xs text-gray-400 mt-1">Pastikan kombinasi tanggal dan storage sudah benar.</p>
            </div>
        </template>
    </div>
</main>

<script>
    const loggedInUserId = "<?php echo isset($_SESSION['user_nama']) ? htmlspecialchars($_SESSION['user_nama']) : 'Guest'; ?>";

    function chillerReport() {
        return {
            selectedDate: '',
            selectedStorage: 'All',
            storageOptions: ['All', 'Chiller 1', 'Chiller 2', 'Chiller 3'],
            reportData: null,
            processedChillerData: {},
            reportFetched: false,
            noData: false,
            loading: false,
            meta: {
                perusahaan: 'CITRAGUNA LESTARI',
                slogan: 'We Deliver Fresh and Frozen Chicken',
                noDokumen: '2018/PRO-ADM/RPA/CGL/FORM-04',
                dibuatOleh: ''
            },
            // Updated color schemes to match the image more closely
            categoryTitleClasses: [
                'bg-blue-200 category-title-bg-0', // Lighter blue for title (like image header)
                'bg-emerald-200 category-title-bg-1', // Lighter green for title
                'bg-purple-200 category-title-bg-2',
                'bg-red-200 category-title-bg-3',
                'bg-yellow-200 category-title-bg-4',
                'bg-pink-200 category-title-bg-5',
                'bg-teal-200 category-title-bg-6',
                'bg-indigo-200 category-title-bg-7',
            ],
            categoryHeaderClasses: [
                'bg-blue-200 category-title-bg-0', // Same as title for header
                'bg-emerald-200 category-title-bg-1',
                'bg-purple-200 category-title-bg-2',
                'bg-red-200 category-title-bg-3',
                'bg-yellow-200 category-title-bg-4',
                'bg-pink-200 category-title-bg-5',
                'bg-teal-200 category-title-bg-6',
                'bg-indigo-200 category-title-bg-7',
            ],
            categoryRowClasses: [
                'bg-blue-50 category-row-bg-0', // Very light blue for rows
                'bg-emerald-50 category-row-bg-1', // Very light green for rows
                'bg-purple-50 category-row-bg-2',
                'bg-red-50 category-row-bg-3',
                'bg-yellow-50 category-row-bg-4',
                'bg-pink-50 category-row-bg-5',
                'bg-teal-50 category-row-bg-6',
                'bg-indigo-50 category-row-bg-7',
            ],
            categoryTotalClasses: [
                'bg-blue-700 category-total-bg-0', // Darker blue for total, white text
                'bg-emerald-700 category-total-bg-1', // Darker green for total
                'bg-purple-700 category-total-bg-2',
                'bg-red-700 category-total-bg-3',
                'bg-yellow-700 category-total-bg-4',
                'bg-pink-700 category-total-bg-5',
                'bg-teal-700 category-total-bg-6',
                'bg-indigo-700 category-total-bg-7',
            ],

            getCategoryTitleClass(index) {
                return this.categoryTitleClasses[index % this.categoryTitleClasses.length];
            },
            getCategoryHeaderClass(index) {
                return this.categoryHeaderClasses[index % this.categoryHeaderClasses.length];
            },
            getCategoryRowClass(index) {
                return this.categoryRowClasses[index % this.categoryRowClasses.length];
            },
            getCategoryTotalClass(index) {
                return this.categoryTotalClasses[index % this.categoryTotalClasses.length];
            },

            formatDate(dateString) {
                if (!dateString) return '';
                const [year, month, day] = dateString.split('-');
                return `${day}-${month}-${year}`;
            },
            chunkArray(array, chunkSize) {
                const result = [];
                for (let i = 0; i < array.length; i += chunkSize) {
                    result.push(array.slice(i, i + chunkSize));
                }
                return result;
            },
            getCategoryKeys(categoryData, categoryTotals) {
                console.log('--- getCategoryKeys called ---');
                console.log('Raw categoryData from BE (before filtering):', categoryData);

                if (categoryData && categoryTotals) {
                    let keysWithDataOrTotals = Object.keys(categoryData).filter(key => {
                        const itemsExist = categoryData[key]?.length > 0;
                        const totalsExist = categoryTotals[key];
                        if (totalsExist) {
                            return itemsExist || this.parseFormattedNumber(totalsExist.total_e_p) > 0 || this.parseFormattedNumber(totalsExist.total_kg) > 0;
                        }
                        return itemsExist;
                    });

                    // Reverse the order of the keys
                    keysWithDataOrTotals = keysWithDataOrTotals.reverse();

                    console.log('Category keys after filtering and reversing:', keysWithDataOrTotals);
                    return keysWithDataOrTotals;
                }
                console.log('No categoryData or categoryTotals provided to getCategoryKeys.');
                return [];
            },
            parseFormattedNumber(formattedString) {
                if (typeof formattedString !== 'string' || formattedString === '') {
                    return 0;
                }
                return parseFloat(formattedString.replace(/\./g, '').replace(',', '.'));
            },
            formatCategoryName(name) {
                // This function already makes it uppercase. We will use font-extrabold for visual boldness
                const formatted = name.replace(/([A-Z])/g, ' $1').replace(/^./, str => str.toUpperCase()).toUpperCase().trim();
                console.log(`Formatting: "${name}" -> "${formatted}"`);
                return formatted;
            },

            async init() {
                const today = new Date();
                this.selectedDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
                this.meta.dibuatOleh = loggedInUserId;
            },

            async fetchReport() {
                if (!this.selectedDate || !this.selectedStorage) {
                    alert('Tanggal dan Storage harus dipilih.');
                    return;
                }

                this.loading = true;
                this.reportFetched = false;
                this.noData = false;
                this.reportData = null;
                this.processedChillerData = {};

                try {
                    const params = new URLSearchParams({
                        action: 'get_report',
                        date: this.selectedDate,
                        storage: this.selectedStorage
                    }).toString();

                    const response = await fetch(`./controller/report/stok_chiller_api.php?${params}`);
                    const data = await response.json();

                    console.log('--- Raw data from Backend (BE) response: ---');
                    console.log(JSON.stringify(data, null, 2)); // Pretty print JSON

                    if (data.success && data.data) {
                        this.reportData = data.data;
                        let hasAnyData = false;

                        if (this.selectedStorage === 'All' && this.reportData.all_chiller_data) {
                            for (const chillerName in this.reportData.all_chiller_data) {
                                const chillerDetails = this.reportData.all_chiller_data[chillerName];
                                this.processedChillerData[chillerName] = {
                                    data: chillerDetails.data,
                                    categoryTotals: chillerDetails.categoryTotals,
                                    chillerTotal: chillerDetails.chillerTotal,
                                    chunkedData: {}
                                };

                                let chillerHasData = false;
                                // Use getCategoryKeys to get the keys with the desired order (reversed)
                                const categories = this.getCategoryKeys(chillerDetails.data, chillerDetails.categoryTotals);
                                for (const categoryKey of categories) {
                                    if (chillerDetails.data[categoryKey] && chillerDetails.data[categoryKey].length > 0) {
                                        this.processedChillerData[chillerName].chunkedData[categoryKey] = this.chunkArray(chillerDetails.data[categoryKey], 20);
                                        chillerHasData = true;
                                        hasAnyData = true;
                                    } else {
                                        this.processedChillerData[chillerName].chunkedData[categoryKey] = [];
                                    }
                                }
                                if (!chillerHasData && this.parseFormattedNumber(chillerDetails.chillerTotal.total_e_p) === 0 && this.parseFormattedNumber(chillerDetails.chillerTotal.total_kg) === 0) {
                                    delete this.processedChillerData[chillerName];
                                } else {
                                    hasAnyData = true;
                                }
                            }
                        } else if (this.selectedStorage !== 'All' && this.reportData) {
                            const chillerName = this.selectedStorage;
                            const chillerDetails = this.reportData;
                            this.processedChillerData[chillerName] = {
                                data: chillerDetails.data,
                                categoryTotals: chillerDetails.categoryTotals,
                                chillerTotal: chillerDetails.chillerTotal,
                                chunkedData: {}
                            };
                            let chillerHasData = false;
                            // Use getCategoryKeys to get the keys with the desired order (reversed)
                            const categories = this.getCategoryKeys(chillerDetails.data, chillerDetails.categoryTotals);
                            for (const categoryKey of categories) {
                                if (chillerDetails.data[categoryKey] && chillerDetails.data[categoryKey].length > 0) {
                                    this.processedChillerData[chillerName].chunkedData[categoryKey] = this.chunkArray(chillerDetails.data[categoryKey], 20);
                                    chillerHasData = true;
                                    hasAnyData = true;
                                } else {
                                    this.processedChillerData[chillerName].chunkedData[categoryKey] = [];
                                }
                            }
                            if (!chillerHasData && this.parseFormattedNumber(chillerDetails.chillerTotal.total_e_p) === 0 && this.parseFormattedNumber(chillerDetails.chillerTotal.total_kg) === 0) {
                                delete this.processedChillerData[chillerName];
                            } else {
                                hasAnyData = true;
                            }
                        }
                        this.noData = !hasAnyData;
                    } else {
                        this.noData = true;
                    }
                } catch (error) {
                    console.error('Error fetching report:', error);
                    alert('Terjadi kesalahan saat mengambil laporan. Silakan coba lagi.');
                    this.noData = true;
                } finally {
                    this.loading = false;
                    this.reportFetched = true;
                    console.log('--- Processed Chiller Data (final state for rendering): ---');
                    console.log(JSON.stringify(this.processedChillerData, null, 2));
                }
            },

            resetFilters() {
                const today = new Date();
                this.selectedDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
                this.selectedStorage = 'All';
                this.reportData = null;
                this.processedChillerData = {};
                this.reportFetched = false;
                this.noData = false;
                this.loading = false;
            },

            downloadReport() {
                window.print();
            }
        }
    }
</script>