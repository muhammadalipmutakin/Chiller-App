<script src="../../assets/js/chart.js"></script>
<script src="../../assets/js/chartjs-plugin-datalabels@2.2.0.js"></script>
<main class="flex-1 min-w-0 transition-all duration-300" x-data="chillerDashboardApp()">
    <h1 class="text-sm sm:text-lg font-extrabold text-center mb-4 sm:mb-6 text-gray-800">Dashboard Data Chiller</h1>

    <section class="mb-4 sm:mb-6">
        <h2 class="text-sm font-semibold mb-3 text-gray-700">Ringkasan Data</h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <template x-for="(item, index) in [
                { label: 'Total Masuk (Ekor)', value: formatNumber(summaryStats.total_in_ekor), icon: 'fa-drumstick-bite', color: 'text-green-600' },
                { label: 'Total Masuk (Kg)', value: formatNumber(summaryStats.total_in_kg, true) + ' Kg', icon: 'fa-scale-balanced', color: 'text-green-600' },
                { label: 'Total Keluar (Ekor)', value: formatNumber(summaryStats.total_out_ekor), icon: 'fa-drumstick-bite', color: 'text-red-600' },
                { label: 'Total Keluar (Kg)', value: formatNumber(summaryStats.total_out_kg, true) + ' Kg', icon: 'fa-scale-balanced', color: 'text-red-600' }
            ]" :key="index">
                <div class="bg-white p-3 sm:p-4 rounded-md shadow flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500" x-text="item.label"></p>
                        <p class="text-sm font-bold text-gray-900" x-text="item.value"></p>
                    </div>
                    <i :class="`fa-solid ${item.icon} text-xl sm:text-2xl ${item.color} opacity-75`"></i>
                </div>
            </template>
        </div>
    </section>

    <section class="mb-4 sm:mb-6">
        <div class="bg-white p-3 sm:p-4 rounded-md shadow h-[400px] sm:h-[500px]">
            <h3 class="text-sm font-semibold mb-3 text-gray-800">Stok dan Kapasitas Berdasarkan Lokasi Chiller</h3>
            <canvas id="stockByLocationChart"></canvas>
            <p x-show="loading.stockByLocation" class="text-center text-xs text-gray-500 mt-2">Memuat data...</p>
            <p x-show="!loading.stockByLocation && stockByLocationData.length === 0" class="text-center text-xs text-gray-500 mt-2">Tidak ada data stok per lokasi.</p>
        </div>
    </section>

    <section class="mb-4 sm:mb-6">
        <div class="bg-white p-3 sm:p-4 rounded-md shadow h-[400px] sm:h-[500px]">
            <h3 class="text-sm font-semibold mb-3 text-gray-800">Penyesuaian Per Chiller (7 Hari Terakhir)</h3>
            <p class="text-xs text-gray-600 mb-3" x-text="`Data dari tanggal ${formatDateRange(dailyAdjustmentData)}`"></p>
            <canvas id="dailyAdjustmentsPerChillerChart"></canvas>
            <p x-show="loading.dailyAdjustments" class="text-center text-xs text-gray-500 mt-2">Memuat data...</p>
            <p x-show="!loading.dailyAdjustments && dailyAdjustmentData.length === 0" class="text-center text-xs text-gray-500 mt-2">Tidak ada data penyesuaian harian per chiller.</p>
        </div>
    </section>

    <section class="mb-4 sm:mb-6">
        <div class="bg-white p-3 sm:p-4 rounded-md shadow h-[400px] sm:h-[500px]">
            <h3 class="text-sm font-semibold mb-3 text-gray-800">Total Penyesuaian Harian (7 Hari Terakhir)</h3>
            <p class="text-xs text-gray-600 mb-3" x-text="`Data dari tanggal ${formatDateRange(dailyAdjustmentData)}`"></p>
            <canvas id="dailyAdjustmentsTotalChart"></canvas>
            <p x-show="loading.dailyAdjustments" class="text-center text-xs text-gray-500 mt-2">Memuat data...</p>
            <p x-show="!loading.dailyAdjustments && dailyAdjustmentData.length === 0" class="text-center text-xs text-gray-500 mt-2">Tidak ada data total penyesuaian harian.</p>
        </div>
    </section>
</main>

<script>
    Chart.register(ChartDataLabels);

    function chillerDashboardApp() {
        return {
            summaryStats: {
                total_in_ekor: 0,
                total_in_kg: 0,
                total_out_ekor: 0,
                total_out_kg: 0
            },
            stockByLocationData: [],
            dailyAdjustmentData: [],
            totalStock: {
                current_kg: 0,
                capacity_kg: 0
            },
            loading: {
                summary: true,
                stockByLocation: true,
                dailyAdjustments: true
            },
            charts: {
                stockByLocation: null,
                dailyAdjustmentsPerChiller: null,
                dailyAdjustmentsTotal: null
            },
            formatNumber(num, isDecimal = false) {
                if (num === null || typeof num === 'undefined' || isNaN(num)) return '0';
                const sign = num < 0 ? '-' : '';
                const absNum = Math.abs(num);
                return sign + (isDecimal ?
                    parseFloat(absNum).toLocaleString('id-ID', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }) :
                    parseInt(absNum).toLocaleString('id-ID'));
            },
            formatDate(dateStr) {
                const date = new Date(dateStr);
                const options = {
                    day: 'numeric',
                    month: 'long'
                };
                return date.toLocaleDateString('id-ID', options);
            },
            formatDateRange(data) {
                if (data.length === 0) return 'Tidak ada data';
                const startDate = new Date(data[0].date);
                const endDate = new Date(data[data.length - 1].date);
                const options = {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                };
                return `${startDate.toLocaleDateString('id-ID', options)} hingga ${endDate.toLocaleDateString('id-ID', options)}`;
            },
            init() {
                this.fetchSummaryStats();
                this.fetchStockByLocation();
                this.fetchDailyAdjustments();
            },
            async fetchData(action) {
                try {
                    const response = await fetch(`./controller/dashboard/index.php?action=${action}`);
                    const result = await response.json();
                    return result.status === 'success' ? result.data : [];
                } catch (error) {
                    console.error('Error fetching data:', error);
                    return [];
                }
            },
            async fetchSummaryStats() {
                this.loading.summary = true;
                const data = await this.fetchData('get_summary_stats');
                if (data) this.summaryStats = data;
                this.loading.summary = false;
            },
            async fetchStockByLocation() {
                this.loading.stockByLocation = true;
                this.stockByLocationData = await this.fetchData('get_stock_by_location');
                const totalData = this.stockByLocationData.find(d => d.location_name === 'Total');
                if (totalData) {
                    this.totalStock.current_kg = totalData.current_kg;
                    this.totalStock.capacity_kg = totalData.capacity_kg;
                }
                this.loading.stockByLocation = false;
                this.renderStockByLocationChart();
            },
            async fetchDailyAdjustments() {
                this.loading.dailyAdjustments = true;
                this.dailyAdjustmentData = await this.fetchData('get_daily_adjustments_table');
                this.loading.dailyAdjustments = false;
                this.renderDailyAdjustmentsPerChillerChart();
                this.renderDailyAdjustmentsTotalChart();
            },
            renderStockByLocationChart() {
                if (this.charts.stockByLocation) {
                    this.charts.stockByLocation.destroy();
                }
                if (this.stockByLocationData.length === 0) return;
                const chartData = this.stockByLocationData.filter(d => d.location_name !== 'Total');
                const labels = chartData.map(d => `${d.location_name}\n(${this.formatNumber(d.capacity_kg, true)} Kg)`);
                labels.push(`Total\n(${this.formatNumber(this.totalStock.capacity_kg, true)} Kg)`);
                const capacityData = chartData.map(d => parseFloat(d.capacity_kg || 0));
                const stockData = chartData.map(d => parseFloat(d.current_kg || 0));
                const remainingData = chartData.map(d => parseFloat(d.capacity_kg || 0) - parseFloat(d.current_kg || 0));
                capacityData.push(this.totalStock.capacity_kg);
                stockData.push(this.totalStock.current_kg);
                remainingData.push(this.totalStock.capacity_kg - this.totalStock.current_kg);
                const stockPercentage = stockData.map((d, i) => {
                    const capacity = capacityData[i];
                    return capacity > 0 ? (d / capacity * 100) : 0;
                });
                const remainingPercentage = remainingData.map((d, i) => {
                    const capacity = capacityData[i];
                    return capacity > 0 ? (d / capacity * 100) : 0;
                });
                const ctx = document.getElementById('stockByLocationChart').getContext('2d');
                this.charts.stockByLocation = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Stok Terisi',
                            data: stockPercentage,
                            backgroundColor: 'rgba(239, 68, 68, 0.9)',
                            borderColor: 'rgba(185, 28, 28, 1)',
                            borderWidth: 1
                        }, {
                            label: 'Kapasitas Tersisa',
                            data: remainingPercentage,
                            backgroundColor: 'rgba(34, 197, 94, 0.7)',
                            borderColor: 'rgba(22, 163, 74, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'x',
                        layout: {
                            padding: {
                                bottom: 70,
                                top: 10
                            }
                        },
                        scales: {
                            x: {
                                stacked: true,
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    }
                                }
                            },
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    display: false
                                },
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom',
                                labels: {
                                    padding: 20
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: (context) => {
                                        const i = context.dataIndex;
                                        const percentage = context.parsed.y;
                                        const valueKg = context.datasetIndex === 0 ? stockData[i] : remainingData[i];
                                        return `${context.dataset.label}: ${percentage.toFixed(1)}% (${this.formatNumber(valueKg, true)} Kg)`;
                                    },
                                    footer: (context) => {
                                        const i = context[0].dataIndex;
                                        return `Total Kapasitas: ${this.formatNumber(capacityData[i], true)} Kg`;
                                    }
                                }
                            },
                            datalabels: {
                                display: true,
                                color: (ctx) => {
                                    return ctx.datasetIndex === 0 ? '#fff' : '#000';
                                },
                                font: {
                                    weight: 'bold',
                                    size: 12
                                },
                                formatter: (value, ctx) => {
                                    const i = ctx.dataIndex;
                                    const valueKg = ctx.datasetIndex === 0 ? stockData[i] : remainingData[i];
                                    return `${value.toFixed(1)}%\n(${this.formatNumber(valueKg, true)} Kg)`;
                                },
                                anchor: 'center',
                                align: 'center'
                            }
                        }
                    }
                });
            },
            renderDailyAdjustmentsPerChillerChart() {
                if (this.charts.dailyAdjustmentsPerChiller) {
                    this.charts.dailyAdjustmentsPerChiller.destroy();
                }
                if (this.dailyAdjustmentData.length === 0) return;
                const labels = this.dailyAdjustmentData.map(d => this.formatDate(d.date));
                const chiller1Data = this.dailyAdjustmentData.map(d => parseFloat(d.chiller_1 || 0));
                const chiller2Data = this.dailyAdjustmentData.map(d => parseFloat(d.chiller_2 || 0));
                const chiller3Data = this.dailyAdjustmentData.map(d => parseFloat(d.chiller_3 || 0));
                const ctx = document.getElementById('dailyAdjustmentsPerChillerChart').getContext('2d');
                this.charts.dailyAdjustmentsPerChiller = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Chiller 1',
                            data: chiller1Data,
                            backgroundColor: 'rgba(59, 130, 246, 0.2)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 2,
                            tension: 0.1,
                            fill: true
                        }, {
                            label: 'Chiller 2',
                            data: chiller2Data,
                            backgroundColor: 'rgba(16, 185, 129, 0.2)',
                            borderColor: 'rgba(16, 185, 129, 1)',
                            borderWidth: 2,
                            tension: 0.1,
                            fill: true
                        }, {
                            label: 'Chiller 3',
                            data: chiller3Data,
                            backgroundColor: 'rgba(251, 191, 36, 0.2)',
                            borderColor: 'rgba(251, 191, 36, 1)',
                            borderWidth: 2,
                            tension: 0.1,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'x',
                        layout: {
                            // PERBAIKAN: Menambah padding di semua sisi agar tidak ada nilai yang terpotong
                            padding: {
                                left: 20,
                                right: 20,
                                bottom: 70,
                                top: 20
                            }
                        },
                        scales: {
                            x: {
                                title: {
                                    display: true,
                                    text: 'Tanggal',
                                    font: {
                                        weight: 'bold'
                                    }
                                }
                            },
                            y: {
                                beginAtZero: false,
                                title: {
                                    display: true,
                                    text: 'Jumlah (Kg)',
                                    font: {
                                        weight: 'bold'
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'bottom'
                            },
                            tooltip: {
                                callbacks: {
                                    label: (context) => {
                                        const value = context.parsed.y;
                                        return `${context.dataset.label}: ${this.formatNumber(value, true)} Kg`;
                                    }
                                }
                            },
                            datalabels: {
                                display: true,
                                color: (context) => {
                                    return context.dataset.borderColor;
                                },
                                backgroundColor: 'rgba(255, 255, 255, 0.8)',
                                borderColor: 'rgba(0, 0, 0, 0.1)',
                                borderWidth: 1,
                                borderRadius: 4,
                                font: {
                                    weight: 'bold',
                                    size: 10
                                },
                                formatter: (value) => {
                                    // PERBAIKAN: Menampilkan nilai 0.00 Kg dan memformat nilai lainnya
                                    return `${this.formatNumber(value, true)} Kg`;
                                },
                                align: (context) => {
                                    const value = context.dataset.data[context.dataIndex];
                                    return value > 0 ? 'top' : 'bottom';
                                },
                                offset: 5
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        }
                    }
                });
            },
            renderDailyAdjustmentsTotalChart() {
                if (this.charts.dailyAdjustmentsTotal) {
                    this.charts.dailyAdjustmentsTotal.destroy();
                }
                if (this.dailyAdjustmentData.length === 0) return;
                const labels = this.dailyAdjustmentData.map(d => this.formatDate(d.date));
                const totalAdjustmentData = this.dailyAdjustmentData.map(d => parseFloat(d.total_adjustment || 0));
                const ctx = document.getElementById('dailyAdjustmentsTotalChart').getContext('2d');
                this.charts.dailyAdjustmentsTotal = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Total Penyesuaian (Kg)',
                            data: totalAdjustmentData,
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 2,
                            tension: 0.1,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'x',
                        layout: {
                            // PERBAIKAN: Menambah padding di kiri dan kanan
                            padding: {
                                left: 20,
                                right: 20,
                                bottom: 70,
                                top: 10
                            }
                        },
                        scales: {
                            x: {
                                title: {
                                    display: true,
                                    text: 'Tanggal',
                                    font: {
                                        weight: 'bold'
                                    }
                                }
                            },
                            y: {
                                beginAtZero: false,
                                title: {
                                    display: true,
                                    text: 'Jumlah (Kg)',
                                    font: {
                                        weight: 'bold'
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: (context) => {
                                        const value = context.parsed.y;
                                        return `${context.dataset.label}: ${this.formatNumber(value, true)} Kg`;
                                    }
                                }
                            },
                            datalabels: {
                                // MENGUBAH konfigurasi datalabels
                                display: true,
                                color: (context) => {
                                    return context.dataset.borderColor;
                                },
                                backgroundColor: 'rgba(255, 255, 255, 0.8)',
                                borderColor: 'rgba(0, 0, 0, 0.1)',
                                borderWidth: 1,
                                borderRadius: 4,
                                font: {
                                    weight: 'bold',
                                    size: 10
                                },
                                formatter: (value) => {
                                    return `${this.formatNumber(value, true)} Kg`;
                                },
                                align: (context) => {
                                    const value = context.dataset.data[context.dataIndex];
                                    return value > 0 ? 'top' : 'bottom';
                                },
                                offset: 5
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        }
                    }
                });
            }
        }
    }
</script>