<section class="mb-4 sm:mb-6">
    <h2 class="text-sm sm:text-lg font-semibold mb-3 text-gray-700">Visualisasi Data</h2>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-4">

        <div class="bg-white p-3 sm:p-4 rounded-md shadow card chart-container">
            <h3 class="text-sm font-semibold mb-3 text-gray-800">10 Item dengan Stok Terbanyak (Berat) per Storage</h3>
            <canvas id="topItemsByStorageBeratChart"></canvas>
            <p x-show="loading.topItemsByStorageBerat" class="text-center text-xs text-gray-500 mt-2">Memuat data...</p>
            <p x-show="!loading.topItemsByStorageBerat && topItemsByStorageBeratData.length === 0" class="text-center text-xs text-gray-500 mt-2">Tidak ada data stok per storage (berat).</p>
        </div>

        <div class="bg-white p-3 sm:p-4 rounded-md shadow card chart-container">
            <h3 class="text-sm font-semibold mb-3 text-gray-800">10 Item dengan Stok Terbanyak (Ekor) per Storage</h3>
            <canvas id="topItemsByStorageEkorChart"></canvas>
            <p x-show="loading.topItemsByStorageEkor" class="text-center text-xs text-gray-500 mt-2">Memuat data...</p>
            <p x-show="!loading.topItemsByStorageEkor && topItemsByStorageEkorData.length === 0" class="text-center text-xs text-gray-500 mt-2">Tidak ada data stok per storage (ekor).</p>
        </div>

        <div class="bg-white p-3 sm:p-4 rounded-md shadow card chart-container">
            <h3 class="text-sm font-semibold mb-3 text-gray-800">Jumlah In/Out 30 Hari Terakhir (Per Minggu - Berat)</h3>
            <canvas id="weeklyInOutChart"></canvas>
            <p x-show="loading.weeklyInOut" class="text-center text-xs text-gray-500 mt-2">Memuat data...</p>
            <p x-show="!loading.weeklyInOut && weeklyInOutData.length === 0" class="text-center text-xs text-gray-500 mt-2">Tidak ada data transaksi per minggu.</p>
        </div>

        <div class="bg-white p-3 sm:p-4 rounded-md shadow card chart-container">
            <h3 class="text-sm font-semibold mb-3 text-gray-800">Stok Berdasarkan Lokasi (Storage)</h3>
            <canvas id="stockByLocationChart"></canvas>
            <p x-show="loading.stockByLocation" class="text-center text-xs text-gray-500 mt-2">Memuat data...</p>
            <p x-show="!loading.stockByLocation && stockByLocationData.length === 0" class="text-center text-xs text-gray-500 mt-2">Tidak ada data stok per lokasi.</p>
        </div>
    </div>
</section>