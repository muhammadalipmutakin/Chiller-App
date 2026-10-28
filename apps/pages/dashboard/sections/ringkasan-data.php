<section class="mb-4 sm:mb-6">
    <h2 class="text-sm sm:text-lg font-semibold mb-3 text-gray-700">Ringkasan Data</h2>

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
                    <p class="text-sm sm:text-lg font-bold text-gray-900" x-text="item.value"></p>
                </div>
                <i :class="`fa-solid ${item.icon} text-xl sm:text-2xl ${item.color} opacity-75`"></i>
            </div>
        </template>
    </div>
</section>