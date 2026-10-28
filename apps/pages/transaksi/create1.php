<?php

$role = $_SESSION['user_role'] ?? 'Guest'; // Pastikan 'user_role' disetel saat login
?>

<!-- Library HTML5-QRCode untuk Barcode Scanning via Kamera -->
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<main
  class="flex-1 min-w-0 transition-all duration-300"
  x-data="optimizedTransactionForm()"
  x-init="initForm()">
  <div class="py-4 sm:p-6">
    <div
      class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
      <div class="flex-shrink-0">
        <a
          href="?page=transaksi/"
          class="inline-flex items-center px-3 py-1.5 bg-blue-500 text-white text-sm md:text-xs rounded-md shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-75 transition duration-300">
          <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">
          Tambah Transaksi Baru
        </h1>
      </div>
      <div>
        <div class="grid grid-cols-2 gap-2">
          <button
            type="button"
            @click="saveAsPending()"
            class="px-4 py-2 bg-yellow-500 text-white font-semibold rounded-md shadow-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-opacity-75 transition duration-300 text-sm md:text-xs sm:text-base"
            :disabled="isLoading || disablePendingButtons">
            <i class="fas fa-pause-circle mr-2"></i> Pending Transaksi
          </button>

          <button
            type="button"
            @click="showPendingListModal = true; fetchPendingTransactions();"
            class="px-4 py-2 bg-indigo-500 text-white font-semibold rounded-md shadow-md hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-opacity-75 transition duration-300 text-sm md:text-xs sm:text-base"
            :disabled="isLoading || disablePendingButtons">
            <i class="fas fa-list-alt mr-2"></i> List Pending (<span
              x-text="pendingTransactions.length">0</span>)
          </button>
        </div>

        <button
          type="button"
          @click="openMergeModal()"
          class="mt-2 w-full px-4 py-2 bg-purple-600 text-white font-semibold rounded-md shadow-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-opacity-75 transition duration-300 text-sm md:text-xs sm:text-base"
          :disabled="isLoading">
          <i class="fas fa-puzzle-piece mr-2"></i> Gabungan / Pindah Barcode
        </button>

        <div x-show="showPendingListModal" class="modal"></div>
      </div>
    </div>

    <div
      x-show="isLoading"
      x-transition
      class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-lg shadow-lg flex items-center">
        <i class="fas fa-spinner fa-spin mr-3 text-blue-500"></i>
        <span>Menyimpan data...</span>
      </div>
    </div>

    <form
      @submit.prevent="submitForm($event)"
      id="transactionForm"
      class="sm:p-6 md:p-8"
      :disabled="disablePendingButtons">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-1 md:gap-8 md:mb-8">
        <div class="grid md:grid-cols-1 space-y-0 md:space-y-6">
          <div class="grid grid-cols-2 md:grid-cols-1 gap-4 mb-3">
            <!-- BARCODE IN -->
            <div
              x-show="!isBarcodeInHidden"
              class="flex flex-col gap-2 p-2 border border-blue-600 rounded-lg bg-blue-100 shadow-md">
              <label
                for="barcodeIn"
                class="hidden md:block text-blue-800 font-bold text-base items-center mb-1">
                <i
                  class="fas fa-barcode mr-2 text-blue-700 md:text-lg text-sm"></i>
                Barcode In <span class="text-xs font-normal text-blue-600">(Maks. 4 Digit)</span>
              </label>
              <div class="flex items-center gap-2">
                <input
                  type="text"
                  inputmode="numeric"
                  pattern="[0-9]*"
                  maxlength="4"
                  id="barcodeIn"
                  x-model="formData.barcode_in"
                  x-ref="barcodeIn"
                  class="flex-1 p-2 border border-blue-400 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-600 text-base bg-white"
                  placeholder="Barcode IN (maks. 4 digit)"
                  :required="isBarcodeInRequired"
                  autocomplete="off"
                  x-on:input="formData.barcode_in = formData.barcode_in.slice(0, 4)"
                  x-on:keydown.enter.prevent="focusFromLocation()"
                  x-on:change="formData.barcode_in = formData.barcode_in.trim()" />
              </div>
              <div class="grid grid-cols-3 gap-2 mt-1">
                <button
                  type="button"
                  x-on:click="resetBarcodeInFields()"
                  class="px-2 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-opacity-50 text-xs sm:text-sm"
                  title="Kosongkan Barcode In">
                  <i class="fas fa-times-circle mr-1"></i> Reset
                </button>
                <button
                  type="button"
                  x-on:click="$refs.barcodeIn.focus();"
                  class="px-2 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-opacity-50 text-xs sm:text-sm"
                  title="Fokuskan Input Barcode In">
                  <i class="fas fa-keyboard mr-1"></i> Input
                </button>
                <button
                  type="button"
                  x-on:click="openScanner('in')"
                  class="px-2 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-opacity-50 text-xs sm:text-sm"
                  title="Buka Kamera Barcode In">
                  <i class="fas fa-camera mr-1"></i> Kamera
                </button>
              </div>
            </div>

            <!-- BARCODE OUT MOBILE -->
            <div
              class="flex flex-col md:hidden gap-2 p-2 border border-red-600 rounded-lg bg-red-100 shadow-md"
              x-show="!isBarcodeOutHidden">
              <label
                for="barcodeOut"
                class="hidden text-red-800 font-bold text-base items-center mb-1">
                <i
                  class="fas fa-barcode mr-2 text-red-700 md:text-lg text-sm"></i>
                Barcode Out <span class="text-xs font-normal text-red-600">(Maks. 4 Digit)</span>
              </label>
              <div class="grid grid-cols-5 items-center gap-2">
                <input
                  type="text"
                  inputmode="numeric"
                  pattern="[0-9]*"
                  maxlength="4"
                  id="barcodeOut"
                  x-model="formData.barcode_out"
                  x-ref="barcodeOut"
                  class="col-span-4 p-2 border border-red-400 rounded-md focus:outline-none focus:ring-2 focus:ring-red-600 text-base bg-white"
                  placeholder="Barcode OUT (maks. 4 digit)"
                  autocomplete="off"
                  x-on:input="formData.barcode_out = formData.barcode_out.slice(0, 4)"
                  x-on:keydown.enter.prevent="handleBarcodeOutScan()"
                  x-on:change="handleBarcodeOutScanIfFilled()" />
                <button
                  type="button"
                  x-on:click="handleBarcodeOutScan()"
                  class="col-span-1 px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-opacity-50 text-sm md:text-xs flex-shrink-0"
                  title="Cari Barcode Out">
                  <i class="fas fa-search"></i>
                </button>
              </div>
              <div class="grid grid-cols-3 gap-2 mt-1">
                <button
                  type="button"
                  x-on:click="resetBarcodeOutFields()"
                  class="px-2 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-opacity-50 text-xs sm:text-sm"
                  title="Reset Barcode Out">
                  <i class="fas fa-times-circle mr-1"></i> Reset
                </button>
                <button
                  type="button"
                  x-on:click="$refs.barcodeOut.focus();"
                  class="px-2 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-opacity-50 text-xs sm:text-sm"
                  title="Fokuskan Input Barcode Out">
                  <i class="fas fa-keyboard mr-1"></i> Input
                </button>
                <button
                  type="button"
                  x-on:click="openScanner('out')"
                  class="px-2 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-opacity-50 text-xs sm:text-sm"
                  title="Buka Kamera Barcode Out">
                  <i class="fas fa-camera mr-1"></i> Kamera
                </button>
              </div>
            </div>
          </div>

          <div class="md:flex md:flex-col grid grid-cols-3 py-2">
            <div
              class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 md:mb-8"
              x-data="{
                        todayDate: new Date().toISOString().slice(0, 10),
                        yesterdayDate: new Date(new Date().setDate(new Date().getDate() - 1)).toISOString().slice(0, 10),
                    }">

              <?php if ($role === 'superadmin'): ?>
                <label
                  for="tanggal"
                  class="w-24 text-gray-700 font-medium text-sm md:text-xs sm:text-base">Tanggal:</label>
                <input
                  type="date"
                  id="tanggal"
                  x-model="formData.date"
                  @change="console.log('Tanggal diubah menjadi:', formData.date)"
                  class="flex-1 p-2 sm:p-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm md:text-xs sm:text-base"
                  required />
              <?php else: ?>
                <label
                  for="tanggal"
                  class="w-24 text-gray-700 font-medium text-sm md:text-xs sm:text-base">Tanggal:</label>
                <input
                  type="date"
                  id="tanggal"
                  x-model="formData.date"
                  @change="console.log('Tanggal diubah menjadi:', formData.date)"
                  x-bind:min="yesterdayDate"
                  x-bind:max="todayDate"
                  class="flex-1 p-2 sm:p-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm md:text-xs sm:text-base"
                  required />
              <?php endif ?>
            </div>

            <div
              class="w-full md:mt-4 flex md:gap-2 md:items-center flex-col md:flex-row">
              <label
                for="selectFromLocation"
                class="block text-gray-700 md:w-32 font-medium text-sm md:text-xs sm:text-base mb-1">Dari</label>
              <select
                id="selectFromLocation"
                x-ref="selectFromLocation"
                x-model="formData.from_location"
                class="p-2 sm:p-2.5 w-full border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs sm:text-base"
                :disabled="isFromLocationDisabled"
                :class="{'bg-gray-100 cursor-not-allowed': isFromLocationDisabled}"
                required>
                <option value="">Pilih Lokasi</option>
                <template
                  x-for="location in filteredLocationsFrom"
                  :key="location.lokasi">
                  <option
                    :value="location.lokasi"
                    x-text="location.lokasi"></option>
                </template>
              </select>
            </div>

            <div
              class="w-full md:mt-4 flex md:gap-2 md:items-center flex-col md:flex-row">
              <label
                for="selectToLocation"
                class="block text-gray-700 md:w-32 font-medium text-sm md:text-xs sm:text-base mb-1">Tujuan</label>
              <select
                id="selectToLocation"
                x-ref="selectToLocation"
                x-model="formData.to_location"
                class="p-2 sm:p-2.5 w-full border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs sm:text-base"
                required>
                <option value="">Pilih Lokasi</option>
                <template
                  x-for="location in masterData.locations"
                  :key="location.lokasi">
                  <option
                    :value="location.lokasi"
                    x-text="location.lokasi"></option>
                </template>
              </select>
            </div>
          </div>
        </div>

        <div class="md:space-y-6">
          <!-- BARCODE OUT DESKTOP -->
          <div
            class="flex-col hidden md:block gap-2 mb-12 p-2 border border-red-600 rounded-lg bg-red-100 shadow-md"
            x-show="!isBarcodeOutHidden">
            <label
              for="barcodeOut"
              class="text-red-800 font-bold text-base flex items-center mb-3">
              <i class="fas fa-barcode mr-2 text-red-700 md:text-lg text-sm"></i>
              Barcode Out <span class="ml-2 text-xs font-normal text-red-600">(Maks. 4 Digit)</span>
            </label>
            <div class="grid grid-cols-5 items-center gap-2">
              <input
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="4"
                id="barcodeOut"
                x-model="formData.barcode_out"
                x-ref="barcodeOut"
                class="col-span-4 p-2 border border-red-400 rounded-md focus:outline-none focus:ring-2 focus:ring-red-600 text-base bg-white"
                placeholder="Barcode OUT (maks. 4 digit)"
                autocomplete="off"
                x-on:input="formData.barcode_out = formData.barcode_out.slice(0, 4)"
                x-on:keydown.enter.prevent="handleBarcodeOutScan()"
                x-on:change="handleBarcodeOutScanIfFilled()" />
              <button
                type="button"
                x-on:click="handleBarcodeOutScan()"
                class="col-span-1 px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-opacity-50 text-sm md:text-xs flex-shrink-0"
                title="Cari Barcode Out">
                <i class="fas fa-search"></i>
              </button>
            </div>
            <div class="grid grid-cols-3 gap-2 mt-3">
              <button
                type="button"
                x-on:click="resetBarcodeOutFields()"
                class="px-2 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-opacity-50 text-xs sm:text-sm"
                title="Reset Barcode Out">
                <i class="fas fa-times-circle mr-1"></i> Reset
              </button>
              <button
                type="button"
                x-on:click="$refs.barcodeOut.focus();"
                class="px-2 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-opacity-50 text-xs sm:text-sm"
                title="Fokuskan Input Barcode Out">
                <i class="fas fa-keyboard mr-1"></i> Input
              </button>
              <button
                type="button"
                x-on:click="openScanner('out')"
                class="px-2 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-opacity-50 text-xs sm:text-sm"
                title="Buka Kamera Barcode Out">
                <i class="fas fa-camera mr-1"></i> Kamera
              </button>
            </div>
          </div>

          <div
            class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 mb-3">
            <label
              for="item"
              class="w-24 text-gray-700 font-medium text-sm md:text-xs sm:text-base">Item</label>
            <div class="flex-1 relative flex items-center gap-2">
              <select
                id="selectItem"
                x-ref="selectItem"
                style="width: 100%"
                class="p-2 sm:p-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm md:text-xs sm:text-base"
                :disabled="isItemDisabled"
                :class="{'bg-gray-100 cursor-not-allowed': isItemDisabled}"
                required>
                <option value="">Pilih Item</option>
              </select>
              <div class="flex flex-col">
                <span
                  x-show="shouldShowStock()"
                  class="text-xs sm:text-sm md:text-xs text-gray-600 whitespace-nowrap"
                  x-text="`Stok (Item/Storage): ${currentStock.ekor_pack} Ekor/Pack, ${currentStock.quantity_kg} Kg`"></span>

                <span
                  x-show="shouldShowStock()"
                  class="text-xs sm:text-sm md:text-xs text-gray-600 whitespace-nowrap ml-2"
                  x-text="`Stok (Barcode): ${currentStock.ekor_barcode} Ekor/Pack, ${currentStock.kg_barcode} Kg`"></span>
              </div>
            </div>
          </div>

          <div class="text-xs sm:text-sm md:text-xs text-gray-600 mt-2 mb-2">
            Jika Anda tidak menemukan item yang dicari, silahkan
            <a
              href="?page=master/item"
              class="text-blue-600 hover:underline font-medium ml-1">
              Aktifkan Item
            </a>
          </div>

          <div class="grid grid-cols-2 md:grid-cols-1 mb-4 items-start gap-x-5 md:gap-y-4">
            <div x-show="!isSubitemHidden" class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
              <label for="selectSubitem" class="w-24 text-gray-700 font-medium text-sm md:text-xs sm:text-base">Sub Item</label>
              <div class="flex-1 relative">
                <select
                  id="selectSubitem"
                  x-ref="selectSubitem"
                  x-model="formData.subitem"
                  class="p-1 sm:p-2.5 w-full border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs sm:text-base"
                  :disabled="isSubitemDisabled"
                  :class="{'bg-gray-100 cursor-not-allowed': isSubitemDisabled}">
                  <option value="">Pilih Sub Item (Opsional)</option>
                </select>
              </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:gap-3 gap-2">
              <label for="keterangan" class="w-24 text-gray-700 font-medium text-sm md:text-xs sm:text-base">Keterangan</label>
              <div class="flex-1 flex flex-col sm:flex-row sm:items-center gap-2">
                <input
                  type="text"
                  id="keterangan"
                  x-model="formData.keterangan"
                  class="flex-1 p-2 sm:p-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm md:text-xs sm:text-base"
                  placeholder="Tambahkan keterangan (opsional)" />
              </div>
            </div>
            <div
              class="flex flex-1 gap-2"
              x-show="barcodeOutScanned"
              x-transition>
              <div class="flex flex-col flex-1 p-2 sm:p-2.5 bg-gray-100 border border-gray-300 rounded-md text-sm md:text-xs sm:text-base">
                <span class="font-medium text-gray-700">Tgl. Stok:</span>
                <span x-text="formData.tanggal_stock" class="font-bold"></span>
              </div>

              <div
                class="flex flex-col flex-1 p-2 sm:p-2.5 border rounded-md text-sm md:text-xs sm:text-base"
                :class="{
                      'bg-green-100 text-green-800 border-green-400': formData.status_bahan === 'baru',
                      'bg-red-100 text-red-800 border-red-400': formData.status_bahan === 'lama'
                    }">
                <span class="font-medium">Status Bahan:</span>
                <span x-text="formData.status_bahan" class="font-bold"></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-2 md:gap-4 mb-8">
        <div
          class="flex flex-col gap-2 p-1 border border-gray-200 rounded-lg shadow-sm">
          <label for="ekor" class="text-gray-700 font-medium text-base sm:text-lg">Ekor/Pack
            <span
              x-show="shouldShowStock()"
              class="text-blue-700"
              x-text="`Stok masuk: ${currentStock.ekor_masuk} Ekor`"></span></label>
          <input
            type="text"
            id="ekor"
            x-model="formData.ekor_pack"
            class="p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-base sm:text-lg w-full"
            placeholder="Jumlah ekor atau pack"
            readonly
            required />

          <div class="text-center">
            <div class="grid grid-cols-8 gap-1 md:gap-x-2 mt-2">
              <button
                type="button"
                @click="appendValue('1', 'ekor_pack')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400">
                1
              </button>
              <button
                type="button"
                @click="appendValue('2', 'ekor_pack')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400">
                2
              </button>
              <button
                type="button"
                @click="appendValue('3', 'ekor_pack')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400">
                3
              </button>
              <button
                type="button"
                @click="calculate('+', 'ekor')"
                class="col-span-1 px-1 py-1 rounded-2xl border border-gray-400 operator bg-orange-500 text-white">
                <i class="fa-solid fa-plus"></i>
              </button>
              <button
                type="button"
                @click="calculate('-', 'ekor')"
                class="col-span-1 px-1 py-1 rounded-2xl border border-gray-400 operator bg-orange-500 text-white">
                <i class="fa-solid fa-minus"></i>
              </button>
            </div>

            <div class="grid grid-cols-8 gap-x-1 md:gap-x-2 mt-2">
              <button
                type="button"
                @click="appendValue('4', 'ekor_pack')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400">
                4
              </button>
              <button
                type="button"
                @click="appendValue('5', 'ekor_pack')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400">
                5
              </button>
              <button
                type="button"
                @click="appendValue('6', 'ekor_pack')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400">
                6
              </button>
              <button
                type="button"
                @click="calculate('*', 'ekor')"
                class="col-span-1 px-1 py-1 rounded-2xl border border-gray-400 operator bg-orange-500 text-white">
                <i class="fa-solid fa-xmark"></i>
              </button>
              <button
                type="button"
                @click="calculate('/', 'ekor')"
                class="col-span-1 px-1 py-1 rounded-2xl border border-gray-400 operator bg-orange-500 text-white">
                <i class="fa-solid fa-divide"></i>
              </button>
            </div>

            <div class="grid grid-cols-8 gap-x-1 md:gap-x-4 mt-2">
              <button
                type="button"
                @click="appendValue('7', 'ekor_pack')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400">
                7
              </button>
              <button
                type="button"
                @click="appendValue('8', 'ekor_pack')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400">
                8
              </button>
              <button
                type="button"
                @click="appendValue('9', 'ekor_pack')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400">
                9
              </button>
              <button
                type="button"
                @click="calculate('=', 'ekor')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400 equals bg-green-500 text-white">
                <i class="fa-solid fa-equals"></i>
              </button>
            </div>

            <div class="grid grid-cols-8 gap-x-1 md:gap-x-4 mt-2">
              <button
                type="button"
                @click="appendValue('C', 'ekor_pack')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400 clear bg-red-500 text-white clear">
                C
              </button>
              <button
                type="button"
                @click="appendValue('0', 'ekor_pack')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400">
                0
              </button>
              <button
                type="button"
                @click="appendValue('.', 'ekor_pack')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400">
                .
              </button>
              <button
                type="button"
                @click="calculate('⌫', 'ekor')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400 backspace bg-yellow-500 text-white">
                <i class="fa-solid fa-backspace"></i>
              </button>
            </div>
          </div>
        </div>

        <div
          class="flex flex-col gap-2 p-1 border border-gray-200 rounded-lg shadow-sm">
          <label
            for="berat"
            class="text-gray-700 font-medium text-base sm:text-lg">Berat (Kg)
            <span
              x-show="shouldShowStock()"
              class="text-blue-700"
              x-text="`Stok masuk: ${currentStock.kg_masuk} Kg`"></span></label>
          <input
            type="text"
            id="berat"
            x-model="formData.quantity_kg"
            class="p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-base sm:text-lg w-full"
            placeholder="Berat dalam Kilogram"
            readonly
            required />

          <div class="text-center">
            <div class="grid grid-cols-8 gap-x-1 md:gap-x-2 mt-2">
              <button
                type="button"
                @click="appendValue('1', 'quantity_kg')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400">
                1
              </button>
              <button
                type="button"
                @click="appendValue('2', 'quantity_kg')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400">
                2
              </button>
              <button
                type="button"
                @click="appendValue('3', 'quantity_kg')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400">
                3
              </button>
              <button
                type="button"
                @click="calculate('+', 'berat')"
                class="col-span-1 px-1 py-1 rounded-2xl border border-gray-400 operator bg-orange-500 text-white">
                <i class="fa-solid fa-plus"></i>
              </button>
              <button
                type="button"
                @click="calculate('-', 'berat')"
                class="col-span-1 px-1 py-1 rounded-2xl border border-gray-400 operator bg-orange-500 text-white">
                <i class="fa-solid fa-minus"></i>
              </button>
            </div>

            <div class="grid grid-cols-8 gap-x-1 md:gap-x-2 mt-2">
              <button
                type="button"
                @click="appendValue('4', 'quantity_kg')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400">
                4
              </button>
              <button
                type="button"
                @click="appendValue('5', 'quantity_kg')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400">
                5
              </button>
              <button
                type="button"
                @click="appendValue('6', 'quantity_kg')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400">
                6
              </button>
              <button
                type="button"
                @click="calculate('*', 'berat')"
                class="col-span-1 px-1 py-1 rounded-2xl border border-gray-400 operator bg-orange-500 text-white">
                <i class="fa-solid fa-xmark"></i>
              </button>
              <button
                type="button"
                @click="calculate('/', 'berat')"
                class="col-span-1 px-1 py-1 rounded-2xl border border-gray-400 operator bg-orange-500 text-white">
                <i class="fa-solid fa-divide"></i>
              </button>
            </div>

            <div class="grid grid-cols-8 gap-x-1 md:gap-x-4 mt-2">
              <button
                type="button"
                @click="appendValue('7', 'quantity_kg')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400">
                7
              </button>
              <button
                type="button"
                @click="appendValue('8', 'quantity_kg')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400">
                8
              </button>
              <button
                type="button"
                @click="appendValue('9', 'quantity_kg')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400">
                9
              </button>
              <button
                type="button"
                @click="calculate('=', 'berat')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400 equals bg-green-500 text-white">
                <i class="fa-solid fa-equals"></i>
              </button>
            </div>

            <div class="grid grid-cols-8 gap-x-1 md:gap-x-4 mt-2">
              <button
                type="button"
                @click="appendValue('C', 'quantity_kg')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400 clear bg-red-500 text-white clear">
                C
              </button>
              <button
                type="button"
                @click="appendValue('0', 'quantity_kg')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400">
                0
              </button>
              <button
                type="button"
                @click="appendValue('.', 'quantity_kg')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400">
                .
              </button>
              <button
                type="button"
                @click="calculate('⌫', 'berat')"
                class="col-span-2 px-1 py-1 rounded-2xl border border-gray-400 backspace bg-yellow-500 text-white">
                <i class="fa-solid fa-backspace"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="flex flex-col sm:flex-row gap-3 sm:gap-5 mt-2">
        <button
          type="button"
          @click="submitForm($event, true)"
          class="flex-1 sm:flex-none px-4 py-2.5 sm:px-6 sm:py-3 bg-blue-700 text-white font-semibold rounded-lg shadow-md hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:ring-opacity-75 transition duration-300 text-sm md:text-xs sm:text-base"
          :disabled="isLoading">
          <span x-show="!isLoading">Simpan & Lanjutkan</span>
          <span
            x-show="isLoading"
            class="flex items-center justify-center text-sm md:text-xs sm:text-base">
            <i class="fas fa-spinner fa-spin mr-2"></i>
            Menyimpan...
          </span>
        </button>

        <button
          type="button"
          @click="submitForm($event, false)"
          class="flex-1 sm:flex-none px-4 py-2.5 sm:px-6 sm:py-3 bg-blue-500 text-white font-semibold rounded-lg shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-75 transition duration-300 text-sm md:text-xs sm:text-base"
          :disabled="isLoading">
          <span x-show="!isLoading">Simpan</span>
          <span
            x-show="isLoading"
            class="flex items-center justify-center text-sm md:text-xs sm:text-base">
            <i class="fas fa-spinner fa-spin mr-2"></i>
            Menyimpan...
          </span>
        </button>
      </div>
    </form>

    <!-- MODAL CAMERA SCANNER -->
    <div
      x-show="showScannerModal"
      style="display: none;"
      x-transition:enter="ease-out duration-300"
      x-transition:enter-start="opacity-0 scale-95"
      x-transition:enter-end="opacity-100 scale-100"
      x-transition:leave="ease-in duration-200"
      x-transition:leave-start="opacity-100 scale-100"
      x-transition:leave-end="opacity-0 scale-95"
      class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center p-3 z-50">
      <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-4 relative flex flex-col items-center">
        <div class="flex justify-between items-center w-full mb-3">
          <h3 class="text-lg font-bold text-gray-800 flex items-center">
            <i class="fas fa-camera text-blue-600 mr-2"></i>
            Scan Barcode <span class="ml-1 uppercase text-blue-600" x-text="scannerTarget"></span>
          </h3>
          <button
            type="button"
            @click="closeScanner()"
            class="text-gray-400 hover:text-gray-700 text-xl font-bold">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <!-- Area Video Scanner -->
        <div id="barcode-reader-container" class="w-full bg-black rounded-lg overflow-hidden border-2 border-dashed border-gray-300 min-h-[280px] flex items-center justify-center relative">
          <div id="barcode-reader" class="w-full"></div>
        </div>

        <p class="text-xs text-gray-500 mt-3 text-center">
          Arahkan kamera ke barcode. Barcode akan terbaca otomatis.
        </p>

        <button
          type="button"
          @click="closeScanner()"
          class="mt-4 px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm font-semibold transition">
          Tutup Scanner
        </button>
      </div>
    </div>

    <!-- MODAL PENDING TRANSAKSI -->
    <div
      x-show="showPendingListModal"
      class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50 p-4"
      x-transition:enter="ease-out duration-300"
      x-transition:enter-start="opacity-0 scale-90"
      x-transition:enter-end="opacity-100 scale-100"
      x-transition:leave="ease-in duration-200"
      x-transition:leave-start="opacity-100 scale-100"
      x-transition:leave-end="opacity-0 scale-90">
      <div
        class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto p-6 relative">
        <h2 class="text-2xl font-bold mb-4 text-gray-800">
          Daftar Transaksi Pending
        </h2>
        <button
          type="button"
          @click="showPendingListModal = false; if (!currentPendingId) disablePendingButtons = false;"
          class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-xl">
          <i class="fas fa-times"></i>
        </button>

        <div
          x-show="pendingTransactions.length === 0"
          class="text-center text-gray-600 py-8">
          <p>Tidak ada transaksi pending.</p>
        </div>

        <div x-show="pendingTransactions.length > 0" class="overflow-x-auto">
          <table class="min-w-full bg-white border border-gray-200">
            <thead>
              <tr
                class="bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                <th class="py-3 px-4 border-b">Barcode Out</th>
                <th class="py-3 px-4 border-b">Item</th>
                <th class="py-3 px-4 border-b">Dari</th>
                <th class="py-3 px-4 border-b">Tujuan</th>
                <th class="py-3 px-4 border-b">Ekor/Pack</th>
                <th class="py-3 px-4 border-b">Kg</th>
                <th class="py-3 px-4 border-b">Batas Ambil</th>
                <th class="py-3 px-4 border-b">Kesempatan</th>
                <th class="py-3 px-4 border-b">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <template x-for="p in pendingTransactions" :key="p.id">
                <tr
                  class="text-sm md:text-xs text-gray-700"
                  :class="{'bg-red-100 hover:bg-red-200': isExpired(p.created_at)}">
                  <td
                    class="py-3 px-4 border-b"
                    x-text="p.barcode_out || '-'"></td>
                  <td class="py-3 px-4 border-b" x-text="p.item || '-'"></td>
                  <td
                    class="py-3 px-4 border-b"
                    x-text="p.from_location || '-'"></td>
                  <td
                    class="py-3 px-4 border-b"
                    x-text="p.to_location || '-'"></td>
                  <td class="py-3 px-4 border-b" x-text="p.ekor_pack || '0'"></td>
                  <td
                    class="py-3 px-4 border-b"
                    x-text="parseFloat(p.quantity_kg).toFixed(2) || '0.00'"></td>
                  <td class="py-3 px-4 border-b">
                    <span
                      x-text="formatExpirationTime(p.created_at)"
                      :class="{'text-red-600 font-semibold': isExpired(p.created_at)}"></span>
                  </td>
                  <td
                    class="py-3 px-4 border-b"
                    x-text="p.pending_count + '/2'"></td>
                  <td class="py-3 px-4 border-b flex gap-2">
                    <form @submit.prevent="loadPendingTransaction(p)">
                      <button
                        type="submit"
                        :class="{
                                'bg-green-500 hover:bg-green-600': true,
                                'cursor-not-allowed opacity-50': isLoading
                            }"
                        :disabled="isLoading"
                        class="px-3 py-1 text-white text-xs rounded-md">
                        Ambil
                      </button>
                    </form>
                    <form @submit.prevent="confirmDeletePending(p.id)">
                      <button
                        type="submit"
                        class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white text-xs rounded-md">
                        Hapus
                      </button>
                    </form>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
        <p class="mt-4 text-sm md:text-xs text-gray-600">
          Catatan: Transaksi pending akan ditandai kadaluarsa setelah 1 menit dari
          waktu dibuat. Batas kesempatan pending adalah 2 kali.
        </p>
      </div>
    </div>

    <!-- MODAL GABUNGAN -->
    <div x-show="showMergeModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center p-2 z-50 min-h-screen">
      <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full p-2 relative transform transition-all duration-300 max-h-[600px] overflow-y-auto">
        <button @click="closeMergeModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors duration-200 text-2xl" aria-label="Close">
          <i class="fas fa-times"></i>
        </button>
        <h2 class="text-2xl font-bold mb-2 text-gray-800">Gabungan / Pindah Barcode</h2>
        <p class="text-sm md:text-xs text-gray-500 mb-2">Gabungkan stok dari beberapa barcode asal ke satu barcode tujuan.</p>

        <form @submit.prevent="handleMergeTransaction()">
          <div class="space-y-6">
            <div class="p-2 border border-blue-200 rounded-lg bg-blue-50">
              <label for="mergeStorage" class="block text-sm md:text-xs font-medium text-blue-700">Pilih Lokasi Penyimpanan</label>
              <select id="mergeStorage" x-model="mergeFormData.storage" class="mt-1 block w-full border-blue-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm md:text-xs p-2 transition-colors duration-200">
                <template x-for="chiller in chillerLocations" :key="chiller">
                  <option :value="chiller" x-text="chiller"></option>
                </template>
              </select>
              <div x-show="mergeFromBarcodes.length > 0" class="mt-2 p-2 bg-blue-100 rounded-md border border-blue-300">
                <div class="flex items-center text-blue-800 font-semibold">
                  <i class="fas fa-info-circle mr-2"></i>
                  <p>Item yang Digabung</p>
                </div>
                <p class="text-sm md:text-xs text-blue-700 mt-1"><strong>Item:</strong> <span x-text="mergeFromBarcodes[0].item"></span></p>
                <p class="text-sm md:text-xs text-blue-700"><strong>Subitem:</strong> <span x-text="mergeFromBarcodes[0].subitem || '-'"></span></p>
              </div>
            </div>

            <div class="p-2 border border-gray-200 rounded-lg bg-gray-50">
              <label for="barcodeFromInput" class="block text-sm md:text-xs font-medium text-gray-700">Barcode Asal</label>
              <div class="flex items-center mt-1">
                <input type="text" inputmode="numeric" pattern="[0-9]*" id="barcodeFromInput" x-model="currentBarcodeFrom" @keydown.enter.prevent="addBarcodeFrom()" class="flex-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm md:text-xs p-2 transition-colors duration-200" placeholder="Scan atau ketik barcode asal" autocomplete="off" />
                <button type="button" @click="addBarcodeFrom()" :disabled="isLoadingMerge" class="ml-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                  <i class="fas fa-plus" x-show="!isLoadingMerge"></i>
                  <i class="fas fa-spinner fa-spin" x-show="isLoadingMerge"></i>
                </button>
              </div>
              <p x-show="mergeErrors.add_barcode_from" x-text="mergeErrors.add_barcode_from" class="text-red-500 text-xs mt-1"></p>

              <div x-show="mergeFromBarcodes.length > 0" class="mt-2">
                <p class="text-sm md:text-xs text-gray-500 mb-2">Daftar barcode asal:</p>
                <div class="max-h-60 overflow-y-auto border border-gray-200 rounded-lg">
                  <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100 sticky top-0">
                      <tr>
                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Barcode</th>
                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ekor</th>
                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Berat (kg)</th>
                        <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                      <template x-for="(barcode, index) in mergeFromBarcodes" :key="index">
                        <tr>
                          <td class="px-4 py-2 whitespace-nowrap text-sm md:text-xs text-gray-900" x-text="barcode.barcode"></td>
                          <td class="px-4 py-2 whitespace-nowrap text-sm md:text-xs text-gray-900">
                            <input type="number" x-model.number="barcode.ekor_pack_from" @input="updateTotals(barcode)" class="w-24 text-center border rounded-md p-1 text-sm md:text-xs focus:border-blue-500 focus:ring-blue-500" min="1" :max="barcode.ekor_stock_current">
                            <p class="text-xs text-gray-500">Stok: <span x-text="barcode.ekor_stock_current"></span></p>
                          </td>
                          <td class="px-4 py-2 whitespace-nowrap text-sm md:text-xs text-gray-900">
                            <input type="number" step="0.01" x-model.number="barcode.quantity_kg_from" @input="updateTotals()" class="w-24 text-center border rounded-md p-1 text-sm md:text-xs focus:border-blue-500 focus:ring-blue-500" min="0.01" :max="barcode.berat_stock_current">
                            <p class="text-xs text-gray-500">Stok: <span x-text="barcode.berat_stock_current"></span> kg</p>
                          </td>
                          <td class="px-4 py-2 whitespace-nowrap text-right text-sm md:text-xs font-medium">
                            <button type="button" @click="removeBarcodeFrom(index)" class="text-red-600 hover:text-red-800 transition-colors duration-200" aria-label="Hapus">
                              <i class="fas fa-trash"></i>
                            </button>
                          </td>
                        </tr>
                      </template>
                    </tbody>
                  </table>
                </div>
                <div class="mt-2 p-3 bg-green-100 rounded-lg border border-green-200 text-green-800">
                  <p class="font-semibold">Total Gabungan</p>
                  <p class="text-sm">Jumlah Ekor: <span x-text="totalEkorFrom"></span></p>
                  <p class="text-sm">Total Berat: <span x-text="totalBeratFrom"></span> kg</p>
                </div>
              </div>
            </div>

            <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
              <label for="barcodeTo" class="block text-sm md:text-xs font-medium text-gray-700 flex items-center">
                Barcode Tujuan
                <input type="checkbox" x-model="isNewBarcodeTo" @change="handleNewBarcodeToToggle()" class="ml-2 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 transition-colors duration-200" />
                <span class="ml-2 text-sm md:text-xs text-gray-600">Barcode Baru</span>
              </label>
              <div class="flex items-center mt-1">
                <input
                  type="text"
                  inputmode="numeric"
                  pattern="[0-9]*"
                  id="barcodeTo"
                  x-model="mergeFormData.barcode_to"
                  @keydown.enter.prevent="searchBarcodeTo()"
                  class="flex-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm md:text-xs p-3 transition-colors duration-200"
                  placeholder="Scan atau ketik barcode tujuan"
                  autocomplete="off" />
                <button
                  type="button"
                  @click="searchBarcodeTo()"
                  :disabled="isNewBarcodeTo || isLoadingMerge"
                  class="ml-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                  <i class="fas fa-search"></i>
                </button>
              </div>
              <p x-show="mergeErrors.barcode_to" x-text="mergeErrors.barcode_to" class="text-red-500 text-xs mt-1"></p>

              <div x-show="mergeBarcodeData.to.item && !isNewBarcodeTo" class="mt-4 p-3 bg-gray-100 rounded-md border border-gray-200">
                <p class="text-sm md:text-xs text-gray-700"><strong>Item:</strong> <span x-text="mergeBarcodeData.to.item"></span></p>
                <p class="text-sm md:text-xs text-gray-700"><strong>Subitem:</strong> <span x-text="mergeBarcodeData.to.subitem || '-'"></span></p>
                <p class="text-sm md:text-xs text-gray-700"><strong>Stok:</strong> <span x-text="mergeBarcodeData.to.ekor_stock_current"></span> ekor / <span x-text="mergeBarcodeData.to.berat_stock_current"></span> kg</p>
              </div>

              <div x-show="isNewBarcodeTo && mergeFromBarcodes.length > 0" class="mt-4 p-3 bg-green-100 rounded-md border border-green-200">
                <div class="flex items-center text-green-800">
                  <i class="fas fa-magic mr-2"></i>
                  <p class="text-sm md:text-xs font-semibold">Barcode Tujuan Baru akan dibuat.</p>
                </div>
                <p class="text-xs text-green-700 mt-1">Barcode baru akan berisi item dan total stok dari semua barcode asal.</p>
              </div>
            </div>

            <div class="flex justify-end gap-2 mt-2">
              <button type="button" @click="closeMergeModal()" class="px-6 py-2 bg-gray-300 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400 transition-colors duration-200 text-sm md:text-xs sm:text-base">
                <i class="fas fa-ban mr-2"></i>Batal
              </button>
              <button type="submit" :disabled="isLoadingMerge" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-lg shadow-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200 text-sm md:text-xs sm:text-base">
                <i class="fas fa-exchange-alt mr-2" x-show="!isLoadingMerge"></i>
                <i class="fas fa-spinner fa-spin mr-2" x-show="isLoadingMerge"></i>
                Gabung
              </button>
            </div>

            <p x-show="mergeErrorMessage" x-text="mergeErrorMessage" class="text-red-500 text-sm md:text-xs mt-3 text-center"></p>
            <p x-show="mergeSuccessMessage" x-text="mergeSuccessMessage" class="text-green-600 text-sm md:text-xs mt-3 text-center"></p>
          </div>
        </form>
      </div>
    </div>
</main>

<script>
  document.addEventListener('alpine:init', () => {
    Alpine.data('optimizedTransactionForm', () => ({
      chillerLocations: ['Chiller 1', 'Chiller 2', 'Chiller 3'],
      isLoading: false,
      errorMessage: '',
      lastInputWasOperator: false,
      barcodeOutScanned: false,
      pendingFetchInterval: null,
      pendingCheckInterval: null,
      disablePendingButtons: false,
      currentPendingId: null,

      showPendingListModal: false,
      pendingTransactions: [],
      currentPendingCount: 0,

      // === KAMERA SCANNER STATE ===
      showScannerModal: false,
      scannerTarget: null,
      html5QrCodeScanner: null,

      formData: {
        date: new Date().toISOString().slice(0, 10),
        from_location: '',
        to_location: '',
        item: '',
        subitem: '',
        ekor_pack: '',
        ekor_pack_value: null,
        quantity_kg: '',
        quantity_kg_value: null,
        keterangan: '',
        barcode_in: '',
        barcode_out: '',
        user: '<?php echo $_SESSION["user_nama"] ?? "Guest"; ?>',
        id_in: null
      },

      masterData: {
        locations: [],
        items: [],
        subitems: [],
        itemsWithStock: []
      },

      currentStock: {
        ekor_pack: 0,
        quantity_kg: 0,
        ekor_barcode: 0,
        kg_barcode: 0,
        ekor_masuk: 0,
        kg_masuk: 0
      },

      showMergeModal: false,
      isNewBarcodeTo: false,
      currentBarcodeFrom: '',
      mergeFromBarcodes: [],
      totalEkorFrom: 0,
      totalBeratFrom: 0,
      mergeFormData: {
        storage: 'Chiller 1',
        barcode_to: '',
      },
      mergeBarcodeData: {
        to: {
          id: null,
          barcode: '',
          item: '',
          subitem: '',
          ekor_stock_current: null,
          berat_stock_current: null,
          id_in: null,
        },
      },
      mergeErrors: {
        add_barcode_from: '',
        barcode_to: '',
      },
      mergeErrorMessage: '',
      mergeSuccessMessage: '',
      isLoadingMerge: false,
      barcodeScanInProgress: false,

      async initForm() {
        this.isLoading = true;
        try {
          await Promise.all([
            this.fetchMasterData('locations', './controller/transaksi/transaksi_api.php?action=getLocations'),
            this.fetchMasterData('items', './controller/transaksi/transaksi_api.php?action=getAllItems'),
            this.fetchMasterData('subitems', './controller/transaksi/transaksi_api.php?action=getAllSubitems')
          ]);

          this.initSelect2ForItems();
          this.setupEventListeners();
          this.filterSubitems();
        } catch (error) {
          console.error('Initialization error:', error);
        } finally {
          this.isLoading = false;
        }

        this.formData.user = '<?php echo $_SESSION['user_nama'] ?? 'Unknown User'; ?>';
        await this.fetchPendingTransactions();
      },

      focusFromLocation() {
        this.$nextTick(() => {
          if (this.$refs.selectFromLocation) {
            this.$refs.selectFromLocation.focus();
          }
        });
      },

      // === METODE SCANNER KAMERA YANG DIPERBAIKI ===
      async openScanner(target) {
        this.scannerTarget = target;
        this.showScannerModal = true;

        // Tunggu modal benar-benar tampil di DOM dengan ukuran nyata
        await this.$nextTick();
        await new Promise(r => setTimeout(r, 200));

        if (this.html5QrCodeScanner) {
          try {
            await this.html5QrCodeScanner.stop();
            await this.html5QrCodeScanner.clear();
          } catch (e) {}
          this.html5QrCodeScanner = null;
        }

        const readerElem = document.getElementById("barcode-reader");
        if (readerElem) readerElem.innerHTML = "";

        this.html5QrCodeScanner = new Html5Qrcode("barcode-reader");

        const config = {
          fps: 15,
          qrbox: (viewfinderWidth, viewfinderHeight) => {
            const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
            return {
              width: Math.floor(minEdge * 0.85),
              height: Math.floor(minEdge * 0.55)
            };
          }
        };

        const onScanSuccess = (decodedText) => {
          this.handleScanResult(decodedText);
        };

        try {
          // Panggil langsung kamera belakang
          await this.html5QrCodeScanner.start({
              facingMode: "environment"
            },
            config,
            onScanSuccess,
            () => {}
          );
        } catch (err) {
          console.warn("Kamera belakang tidak tersedia langsung, mencoba daftar kamera perangkat...", err);
          try {
            // Fallback: Minta izin & deteksi kamera apa pun yang ada pada perangkat
            const devices = await Html5Qrcode.getCameras();
            if (devices && devices.length > 0) {
              // Utamakan kamera terakhir (biasanya kamera belakang di HP)
              const selectedCameraId = devices[devices.length - 1].id;
              await this.html5QrCodeScanner.start(
                selectedCameraId,
                config,
                onScanSuccess,
                () => {}
              );
            } else {
              throw new Error("Tidak ada kamera yang terdeteksi pada perangkat ini.");
            }
          } catch (fallbackErr) {
            console.error("Gagal membuka kamera:", fallbackErr);
            this.closeScanner();
            Swal.fire({
              icon: 'warning',
              title: 'Izin Kamera Diperlukan',
              html: `Kamera tidak dapat diakses.<br><br>
                     <div class="text-left text-xs bg-gray-100 p-2 rounded">
                       1. Klik ikon <b>Gembok / Pengaturan</b> di samping address bar browser.<br>
                       2. Ubah izin <b>Camera / Kamera</b> menjadi <b>Allow / Izinkan</b>.<br>
                       3. Refresh halaman web.
                     </div>`
            });
          }
        }
      },

      async closeScanner() {
        if (this.html5QrCodeScanner) {
          try {
            await this.html5QrCodeScanner.stop();
            await this.html5QrCodeScanner.clear();
          } catch (err) {
            console.warn("Scanner stop warning:", err);
          }
          this.html5QrCodeScanner = null;
        }
        this.showScannerModal = false;
        this.scannerTarget = null;
      },

      handleScanResult(scannedText) {
        if (!scannedText) return;

        let cleanBarcode = scannedText.trim();
        if (cleanBarcode.length > 4) {
          cleanBarcode = cleanBarcode.slice(0, 4);
        }

        const target = this.scannerTarget;
        this.closeScanner();

        // Audio Beep Feedback
        try {
          const ctx = new(window.AudioContext || window.webkitAudioContext)();
          const osc = ctx.createOscillator();
          osc.connect(ctx.destination);
          osc.frequency.value = 800;
          osc.start();
          osc.stop(ctx.currentTime + 0.15);
        } catch (e) {}

        if (target === 'in') {
          this.formData.barcode_in = cleanBarcode;
          this.$nextTick(() => {
            this.focusFromLocation();
          });
        } else if (target === 'out') {
          this.formData.barcode_out = cleanBarcode;
          this.$nextTick(() => {
            this.handleBarcodeOutScan();
          });
        }
      },

      // --- Metode Gabungan ---
      openMergeModal() {
        this.showMergeModal = true;
        this.resetMergeForm();
      },

      closeMergeModal() {
        this.showMergeModal = false;
        this.resetMergeForm();
      },

      resetMergeForm() {
        this.mergeFormData = {
          storage: this.chillerLocations[0] || 'Chiller 1',
          barcode_to: '',
        };
        this.mergeFromBarcodes = [];
        this.currentBarcodeFrom = '';
        this.totalEkorFrom = 0;
        this.totalBeratFrom = 0;

        this.mergeBarcodeData.to = {
          id: null,
          barcode: '',
          item: '',
          subitem: '',
          ekor_stock_current: null,
          berat_stock_current: null,
          id_in: null,
        };

        this.mergeErrors = {
          add_barcode_from: '',
          barcode_to: '',
        };
        this.mergeErrorMessage = '';
        this.mergeSuccessMessage = '';
        this.isLoadingMerge = false;
        this.isNewBarcodeTo = false;
      },

      async addBarcodeFrom() {
        const barcode = this.currentBarcodeFrom.trim();
        if (!barcode) {
          this.mergeErrors.add_barcode_from = 'Barcode asal tidak boleh kosong.';
          return;
        }
        if (this.mergeFromBarcodes.find(item => item.barcode === barcode)) {
          this.mergeErrors.add_barcode_from = 'Barcode ini sudah ditambahkan.';
          return;
        }

        this.isLoadingMerge = true;
        this.mergeErrors.add_barcode_from = '';
        this.mergeErrorMessage = '';
        this.mergeSuccessMessage = '';

        try {
          const response = await fetch('./controller/transaksi/marge.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              action: 'search_barcode',
              barcode: barcode,
              storage: this.mergeFormData.storage,
            }),
          });
          const result = await response.json();

          if (result.success) {
            if (this.mergeFromBarcodes.length > 0 && result.data.item !== this.mergeFromBarcodes[0].item) {
              this.mergeErrors.add_barcode_from = 'Item barcode harus sama dengan item barcode yang lain.';
              return;
            }

            if (!this.isNewBarcodeTo && this.mergeBarcodeData.to.barcode === result.data.barcode) {
              this.mergeErrors.add_barcode_from = 'Barcode asal tidak boleh sama dengan barcode tujuan.';
              return;
            }

            const averageWeight = result.data.ekor_stock_current > 0 ? result.data.berat_stock_current / result.data.ekor_stock_current : 0;

            this.mergeFromBarcodes.push({
              ...result.data,
              ekor_pack_from: result.data.ekor_stock_current,
              quantity_kg_from: result.data.berat_stock_current,
              original_avg_weight: averageWeight,
            });
            this.updateTotals();
            this.currentBarcodeFrom = '';
          } else {
            this.mergeErrors.add_barcode_from = result.message;
          }
        } catch (error) {
          console.error('Error searching barcode:', error);
          this.mergeErrors.add_barcode_from = 'Gagal mencari barcode. Silakan coba lagi.';
        } finally {
          this.isLoadingMerge = false;
        }
      },

      removeBarcodeFrom(index) {
        this.mergeFromBarcodes.splice(index, 1);
        this.updateTotals();
      },

      updateTotals(barcodeItem = null) {
        if (barcodeItem) {
          if (barcodeItem.ekor_pack_from > 0 && barcodeItem.original_avg_weight > 0) {
            barcodeItem.quantity_kg_from = parseFloat((barcodeItem.ekor_pack_from * barcodeItem.original_avg_weight).toFixed(2));
          } else {
            barcodeItem.quantity_kg_from = 0;
          }
        }
        this.totalEkorFrom = this.mergeFromBarcodes.reduce((sum, item) => sum + (item.ekor_pack_from || 0), 0);
        this.totalBeratFrom = this.mergeFromBarcodes.reduce((sum, item) => sum + (item.quantity_kg_from || 0), 0).toFixed(2);
      },

      handleNewBarcodeToToggle() {
        if (this.isNewBarcodeTo) {
          this.mergeFormData.barcode_to = '';
          this.clearBarcodeData('to');
          this.mergeErrors.barcode_to = '';
        } else {
          if (this.mergeFromBarcodes.length > 0) {
            this.mergeErrors.barcode_to = 'Silakan cari barcode tujuan yang sudah ada.';
          }
        }
      },

      async searchBarcodeTo() {
        const barcode = this.mergeFormData.barcode_to.trim();
        if (!barcode) {
          this.mergeErrors.barcode_to = 'Barcode tujuan wajib diisi.';
          this.clearBarcodeData('to');
          return;
        }

        if (this.mergeFromBarcodes.length > 0 && this.mergeFromBarcodes.find(item => item.barcode === barcode)) {
          this.mergeErrors.barcode_to = 'Barcode tujuan tidak boleh sama dengan barcode asal.';
          this.clearBarcodeData('to');
          return;
        }

        this.isLoadingMerge = true;
        this.mergeErrors.barcode_to = '';
        this.mergeErrorMessage = '';

        try {
          const response = await fetch('./controller/transaksi/marge.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              action: 'search_barcode',
              barcode: barcode,
              storage: this.mergeFormData.storage,
            }),
          });
          const result = await response.json();

          if (result.success) {
            if (this.mergeFromBarcodes.length > 0 && result.data.item !== this.mergeFromBarcodes[0].item) {
              this.mergeErrors.barcode_to = 'Item barcode tujuan harus sama dengan item barcode asal.';
              this.clearBarcodeData('to');
              return;
            }

            this.mergeBarcodeData.to = {
              id: result.data.id,
              barcode: result.data.barcode,
              item: result.data.item,
              subitem: result.data.subitem,
              ekor_stock_current: result.data.ekor_stock_current,
              berat_stock_current: result.data.berat_stock_current,
              id_in: result.data.id_in,
            };
          } else {
            this.mergeErrors.barcode_to = result.message;
            this.clearBarcodeData('to');
          }
        } catch (error) {
          console.error('Error searching barcode:', error);
          this.mergeErrors.barcode_to = 'Gagal mencari barcode. Silakan coba lagi.';
        } finally {
          this.isLoadingMerge = false;
        }
      },

      clearBarcodeData(type) {
        this.mergeBarcodeData[type] = {
          id: null,
          barcode: '',
          item: '',
          subitem: '',
          ekor_stock_current: null,
          berat_stock_current: null,
          id_in: null,
        };
      },

      validateMergeForm() {
        this.mergeErrors = {
          add_barcode_from: '',
          barcode_to: '',
        };
        let isValid = true;

        if (this.mergeFromBarcodes.length === 0) {
          this.mergeErrors.add_barcode_from = 'Minimal satu barcode asal harus ditambahkan.';
          isValid = false;
        } else {
          const firstItem = this.mergeFromBarcodes[0].item;
          for (const item of this.mergeFromBarcodes) {
            if (item.ekor_pack_from === null || item.ekor_pack_from <= 0) {
              this.mergeErrors.add_barcode_from = `Ekor/Pack untuk barcode ${item.barcode} tidak valid.`;
              isValid = false;
              break;
            }
            if (item.quantity_kg_from === null || item.quantity_kg_from <= 0) {
              this.mergeErrors.add_barcode_from = `Berat untuk barcode ${item.barcode} tidak valid.`;
              isValid = false;
              break;
            }
            if (item.ekor_pack_from > item.ekor_stock_current) {
              this.mergeErrors.add_barcode_from = `Ekor/Pack untuk barcode ${item.barcode} melebihi stok.`;
              isValid = false;
              break;
            }
            if (item.quantity_kg_from > item.berat_stock_current) {
              this.mergeErrors.add_barcode_from = `Berat untuk barcode ${item.barcode} melebihi stok.`;
              isValid = false;
              break;
            }
            if (item.item !== firstItem) {
              this.mergeErrors.add_barcode_from = `Semua barcode asal harus memiliki item yang sama.`;
              isValid = false;
              break;
            }
          }
        }

        if (isValid) {
          if (!this.isNewBarcodeTo) {
            if (!this.mergeFormData.barcode_to) {
              this.mergeErrors.barcode_to = 'Barcode tujuan wajib diisi jika bukan barcode baru.';
              isValid = false;
            } else if (!this.mergeBarcodeData.to.item) {
              this.mergeErrors.barcode_to = 'Barcode tujuan tidak ditemukan atau tidak valid.';
              isValid = false;
            } else if (this.mergeBarcodeData.to.item !== this.mergeFromBarcodes[0].item) {
              this.mergeErrors.barcode_to = 'Item barcode tujuan harus sama dengan item barcode asal.';
              isValid = false;
            }
          } else {
            if (!this.mergeFormData.barcode_to) {
              this.mergeErrors.barcode_to = 'Barcode baru wajib diisi.';
              isValid = false;
            } else if (this.mergeFromBarcodes.some(item => item.barcode === this.mergeFormData.barcode_to)) {
              this.mergeErrors.barcode_to = 'Barcode baru tidak boleh sama dengan barcode asal.';
              isValid = false;
            }
          }
        }

        return isValid;
      },

      async handleMergeTransaction() {
        this.isLoadingMerge = true;
        this.mergeErrorMessage = '';
        this.mergeSuccessMessage = '';

        if (!this.validateMergeForm()) {
          this.mergeErrorMessage = 'Mohon perbaiki kesalahan pada formulir.';
          this.isLoadingMerge = false;
          return;
        }

        try {
          const payload = {
            action: 'save_merge_transaction',
            date: new Date().toISOString().slice(0, 10),
            storage: this.mergeFormData.storage,
            is_new_barcode_to: this.isNewBarcodeTo,
            from_barcodes: this.mergeFromBarcodes.map(item => ({
              barcode: item.barcode,
              item: item.item,
              subitem: item.subitem,
              id_in: item.id_in,
              ekor_pack_transferred: item.ekor_pack_from,
              quantity_kg_transferred: item.quantity_kg_from,
            })),
            barcode_to_data: {
              barcode: this.mergeFormData.barcode_to,
              item: this.isNewBarcodeTo ? this.mergeFromBarcodes[0].item : this.mergeBarcodeData.to.item,
              subitem: this.isNewBarcodeTo ? (this.mergeFromBarcodes[0].subitem || null) : (this.mergeBarcodeData.to.subitem || null),
              id_in: this.isNewBarcodeTo ? null : this.mergeBarcodeData.to.id_in,
              id: this.isNewBarcodeTo ? null : this.mergeBarcodeData.to.id,
            },
            user: '<?php echo $_SESSION["user_nama"] ?? "Guest"; ?>',
          };

          const response = await fetch('./controller/transaksi/marge.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload),
          });

          const result = await response.json();

          if (result.success) {
            this.mergeSuccessMessage = result.message;
            setTimeout(() => {
              this.closeMergeModal();
            }, 1500);
          } else {
            this.mergeErrorMessage = result.message || 'Gagal menyimpan transaksi gabungan';
          }
        } catch (error) {
          console.error('Merge error:', error);
          this.mergeErrorMessage = 'Terjadi kesalahan jaringan: ' + error.message;
        } finally {
          this.isLoadingMerge = false;
        }
      },

      destroy() {
        if (this.pendingFetchInterval) clearInterval(this.pendingFetchInterval);
        if (this.pendingCheckInterval) clearInterval(this.pendingCheckInterval);
        if (this.html5QrCodeScanner) {
          this.closeScanner();
        }
        this.disablePendingButtons = false;
      },

      resetBarcodeInFields() {
        this.formData.barcode_in = '';
        this.formData.id_in = null;
        this.$refs.barcodeIn.focus();
      },

      showError(message) {
        Swal.fire({
          icon: 'error',
          title: 'Oops...',
          text: message,
        });
      },

      async fetchMasterData(key, url) {
        const response = await fetch(url);
        if (!response.ok) throw new Error(`Failed to fetch ${key}: ${response.statusText}`);
        this.masterData[key] = await response.json();
      },

      async saveAsPending() {
        if (this.isLoading) return;

        if (this.currentPendingId) {
          const currentPending = this.pendingTransactions.find(p => p.id === this.currentPendingId);
          if (currentPending && (Number(currentPending.pending_count) || 0) >= 2) {
            this.showError('Transaksi pending ini sudah mencapai batas maksimal (2 kali) dan harus diselesaikan atau dihapus.');
            return;
          }
        }

        this.isLoading = true;

        if (!this.formData.barcode_out && !this.formData.barcode_in) {
          this.showError('Setidaknya Barcode Out atau Barcode In harus diisi untuk pending.');
          this.isLoading = false;
          return;
        }

        try {
          const payload = {
            action: 'savePendingTransaction',
            id: this.currentPendingId,
            barcode_out: this.formData.barcode_out,
            barcode_in: this.formData.barcode_in,
            id_in: this.formData.id_in,
            date: this.formData.date,
            from_location: this.formData.from_location,
            to_location: this.formData.to_location,
            item: this.formData.item,
            subitem: this.formData.subitem,
            ekor_pack: Number(this.formData.ekor_pack) || 0,
            quantity_kg: Number(this.formData.quantity_kg) || 0.0,
            keterangan: this.formData.keterangan,
            user: this.formData.user,
          };

          const response = await fetch('./controller/transaksi/pending_transaksi_api.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
          });

          const result = await response.json();

          if (result.success) {
            Swal.fire('Berhasil!', result.message, 'success');
            this.fullResetForm();
            await this.fetchPendingTransactions();
          } else {
            this.showError(result.message || 'Gagal menyimpan pending transaksi.');
          }
        } catch (error) {
          console.error('Error saving pending transaction:', error);
          this.showError('Terjadi kesalahan saat menyimpan pending transaksi.');
        } finally {
          this.isLoading = false;
        }
      },

      async fetchPendingTransactions() {
        const apiUrl = './controller/transaksi/pending_transaksi_api.php?action=getPendingTransactions';
        try {
          const response = await fetch(apiUrl);
          if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP error! Status: ${response.status}, Details: ${errorText}`);
          }
          const data = await response.json();
          if (data.success) {
            this.pendingTransactions = data.data.map(p => {
              if (typeof p.created_at === 'string' && !p.created_at_date) {
                p.created_at_date = new Date(p.created_at);
              }
              return p;
            });
            this.checkAndAutoLoadPending();
          } else {
            this.pendingTransactions = [];
          }
        } catch (error) {
          console.error('FETCH: Error during fetch or JSON parse:', error);
          this.pendingTransactions = [];
        }
      },

      checkAndAutoLoadPending() {
        const expiredPendingItems = this.pendingTransactions.filter(p => this.isExpired(p.created_at));

        if (expiredPendingItems.length > 0 && !this.showPendingListModal && !this.currentPendingId) {
          Swal.fire({
            title: 'Transaksi Pending Kadaluarsa!',
            html: `Beberapa transaksi pending telah melewati batas waktu dan harus segera ditindaklanjuti.<br>Klik "Lihat" untuk memeriksa daftar transaksi.`,
            icon: 'info',
            confirmButtonText: 'Lihat',
            showCancelButton: true,
            cancelButtonText: 'Tutup',
            showCloseButton: true,
            allowOutsideClick: false,
            allowEscapeKey: false
          }).then((result) => {
            if (result.isConfirmed) {
              this.showPendingListModal = true;
              this.disablePendingButtons = false;
            } else if (result.dismiss === Swal.DismissReason.cancel || result.dismiss === Swal.DismissReason.close) {
              this.disablePendingButtons = false;
            }
          });
          this.disablePendingButtons = true;
        } else if (expiredPendingItems.length === 0 && !this.currentPendingId && !this.showPendingListModal) {
          this.disablePendingButtons = false;
        }
      },

      async loadPendingTransaction(pendingData) {
        const result = await Swal.fire({
          title: 'Ambil Transaksi Pending?',
          text: 'Data form akan diganti dengan transaksi pending ini.',
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Ya, Ambil!',
          cancelButtonText: 'Batal'
        });

        if (!result.isConfirmed) return;

        this.isLoading = true;
        try {
          this.disablePendingButtons = true;

          this.formData.barcode_out = pendingData.barcode_out || '';
          this.formData.barcode_in = pendingData.barcode_in || '';
          this.formData.id_in = pendingData.id_in || null;
          this.formData.date = pendingData.date || new Date().toISOString().slice(0, 10);
          this.formData.from_location = pendingData.from_location || '';
          this.formData.to_location = pendingData.to_location || '';
          this.formData.item = pendingData.item || '';
          this.formData.subitem = pendingData.subitem || '';
          this.formData.ekor_pack = Number(pendingData.ekor_pack) || 0;
          this.formData.quantity_kg = Number(pendingData.quantity_kg) || 0.0;
          this.formData.keterangan = pendingData.keterangan || '';

          this.currentPendingId = pendingData.id;
          this.showPendingListModal = false;

          await this.$nextTick();

          if (this.$refs.selectFromLocation) $(this.$refs.selectFromLocation).val(this.formData.from_location).trigger('change');
          if (this.$refs.selectToLocation) $(this.$refs.selectToLocation).val(this.formData.to_location).trigger('change');
          if (this.$refs.selectItem) {
            if (this.formData.item && $(this.$refs.selectItem).find(`option[value="${this.formData.item}"]`).length === 0) {
              $(this.$refs.selectItem).append(new Option(this.formData.item, this.formData.item, true, true)).trigger('change');
            } else {
              $(this.$refs.selectItem).val(this.formData.item).trigger('change');
            }
          }
          if (this.$refs.selectSubitem) {
            if (this.formData.subitem && $(this.$refs.selectSubitem).find(`option[value="${this.formData.subitem}"]`).length === 0) {
              $(this.$refs.selectSubitem).append(new Option(this.formData.subitem, this.formData.subitem, true, true)).trigger('change');
            } else {
              $(this.$refs.selectSubitem).val(this.formData.subitem).trigger('change');
            }
          }

          this.barcodeOutScanned = !!this.formData.barcode_out;

          if (this.formData.from_location && this.formData.item) {
            await this.fetchCurrentStock(this.formData.item, this.formData.from_location);
            this.updateItemDropdownWithStock();
          }

          Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Transaksi pending berhasil dimuat.',
            showConfirmButton: false,
            timer: 3000
          });

          this.disablePendingButtons = false;
        } catch (error) {
          console.error('Error in loadPendingTransaction:', error);
          this.disablePendingButtons = false;
        } finally {
          this.isLoading = false;
        }
      },

      async completePendingTransaction(pendingId) {
        if (!pendingId) return;

        try {
          const response = await fetch('./controller/transaksi/pending_transaksi_api.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              action: 'completePendingTransaction',
              id: pendingId,
            })
          });
          const result = await response.json();

          if (result.success) {
            await this.fetchPendingTransactions();
            this.fullResetForm();
          }
        } catch (error) {
          console.error('Error completing pending transaction:', error);
        }
      },

      async deletePendingTransaction(id, isConfirmed = false) {
        if (!isConfirmed) {
          const result = await Swal.fire({
            title: 'Hapus Transaksi Pending?',
            text: 'Anda tidak akan bisa mengembalikan transaksi ini!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
          });

          if (!result.isConfirmed) return;
        }

        this.isLoading = true;
        try {
          const response = await fetch('./controller/transaksi/pending_transaksi_api.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              action: 'deletePendingTransaction',
              id: id,
              soft_delete: true
            })
          });
          const result = await response.json();

          if (result.success) {
            if (isConfirmed) {
              Swal.fire('Dihapus!', result.message, 'success');
            }
            this.fetchPendingTransactions();
            if (this.currentPendingId === id) {
              this.fullResetForm();
            }
          } else {
            this.showError(result.message || 'Gagal menghapus transaksi pending.');
          }
        } catch (error) {
          console.error('Error deleting pending transaction:', error);
          this.showError('Terjadi kesalahan saat menghapus transaksi pending.');
        } finally {
          this.isLoading = false;
        }
      },

      confirmDeletePending(id) {
        this.deletePendingTransaction(id, true);
      },

      formatExpirationTime(createdAt) {
        if (!createdAt) return 'N/A';
        const createdDate = new Date(createdAt);
        if (isNaN(createdDate.getTime())) return 'Invalid Date';

        const expirationLimitMs = 5 * 60 * 1000;
        const expirationTime = new Date(createdDate.getTime() + expirationLimitMs);
        const now = new Date();

        if (now.getTime() > expirationTime.getTime()) return 'Kadaluarsa!';

        const diffMs = expirationTime - now;
        const diffMinutes = Math.floor(diffMs / (1000 * 60));
        const diffSeconds = Math.floor((diffMs % (1000 * 60)) / 1000);
        const formattedSeconds = diffSeconds < 10 ? `0${diffSeconds}` : diffSeconds;

        return `${diffMinutes}m ${formattedSeconds}s lagi`;
      },

      isExpired(createdAt) {
        if (!createdAt) return true;
        const createdDate = new Date(createdAt);
        if (isNaN(createdDate.getTime())) return true;

        const expirationLimitMs = 5 * 60 * 1000;
        const expirationTime = new Date(createdDate.getTime() + expirationLimitMs);
        return new Date().getTime() > expirationTime.getTime();
      },

      initSelect2ForItems() {
        const select2Config = {
          placeholder: "Select an Item",
          allowClear: true,
          width: '100%',
          minimumResultsForSearch: 5
        };

        if (this.$refs.selectItem) {
          $(this.$refs.selectItem).select2({
            ...select2Config,
            data: this.masterData.items.map(item => ({
              id: item.item,
              text: item.item,
              has_subitem: item.has_subitem
            }))
          }).on('change', () => {
            this.formData.item = $(this.$refs.selectItem).val();
            this.filterSubitems();
          });
          if (this.formData.item) {
            $(this.$refs.selectItem).val(this.formData.item).trigger('change');
          }
        }

        if (this.$refs.selectSubitem) {
          $(this.$refs.selectSubitem).select2({
            ...select2Config,
            placeholder: "Select Sub Item (Optional)"
          }).on('change', () => {
            this.formData.subitem = $(this.$refs.selectSubitem).val();
          });
        }
      },

      filterSubitems() {
        const selectedItem = this.formData.item;
        const transactionType = this.transactionType;
        let filteredSubitems = [];

        if (!selectedItem) {
          if (transactionType === 'in' || !transactionType) {
            filteredSubitems = this.masterData.subitems;
          } else {
            filteredSubitems = [];
          }
        } else if (transactionType === 'in') {
          const relatedSubitems = this.masterData.subitems.filter(sub => sub.item_related === selectedItem);
          filteredSubitems = relatedSubitems.length > 0 ? relatedSubitems : this.masterData.subitems;
        } else if (selectedItem === 'Retur' || selectedItem === 'Thawing') {
          filteredSubitems = this.masterData.subitems;
        } else {
          const itemData = this.masterData.items.find(item => item.item === selectedItem);
          if (itemData?.has_subitem) {
            filteredSubitems = this.masterData.subitems.filter(sub => sub.item_related === selectedItem);
          } else {
            filteredSubitems = [];
          }
        }

        this.updateSubitemsDropdown(filteredSubitems);

        if (this.formData.subitem && !filteredSubitems.some(sub => sub.subitem === this.formData.subitem)) {
          this.formData.subitem = '';
          if (this.$refs.selectSubitem) $(this.$refs.selectSubitem).val('').trigger('change');
        }
      },

      updateSubitemsDropdown(subitems) {
        if (!this.$refs.selectSubitem) return;

        const currentValue = $(this.$refs.selectSubitem).val();

        if ($(this.$refs.selectSubitem).data('select2')) {
          $(this.$refs.selectSubitem).select2('destroy');
        }

        $(this.$refs.selectSubitem).empty().select2({
          data: subitems.map(sub => ({
            id: sub.subitem,
            text: sub.subitem
          })),
          placeholder: "Select Sub Item (Optional)",
          allowClear: true,
          width: '100%',
          minimumResultsForSearch: 5
        });

        if (currentValue && subitems.some(sub => sub.subitem === currentValue)) {
          $(this.$refs.selectSubitem).val(currentValue).trigger('change');
        } else {
          $(this.$refs.selectSubitem).val(null).trigger('change');
        }
      },

      setupEventListeners() {
        this.$refs.selectFromLocation.addEventListener('change', (event) => {
          this.formData.from_location = event.target.value;
          this.updateItemOptions();
        });

        this.$refs.selectToLocation.addEventListener('change', (event) => {
          this.formData.to_location = event.target.value;
        });

        if (this.$refs.barcodeOut) {
          this.$refs.barcodeOut.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
              e.preventDefault();
              this.handleBarcodeOutScan();
            }
          });
        }
      },

      updateItemOptions() {
        this.formData.item = '';
        this.formData.subitem = '';
        $(this.$refs.selectItem).val('').trigger('change');
        this.$refs.selectSubitem.value = '';

        const fromChiller = this.chillerLocations.includes(this.formData.from_location);
        const toChiller = this.chillerLocations.includes(this.formData.to_location);

        this.showSubitemField = (!fromChiller && toChiller) || (!fromChiller && !toChiller);

        if (fromChiller && toChiller) {
          this.fetchItemsWithStock(this.formData.from_location, true);
        } else if (toChiller) {
          this.fetchAllItems(false);
        } else if (fromChiller) {
          this.fetchItemsWithStock(this.formData.from_location, true);
        } else {
          this.fetchAllItems(false, true);
        }
      },

      async fetchAllItems(showStock = false) {
        try {
          const response = await fetch('./controller/transaksi/transaksi_api.php?action=getAllItems');
          if (!response.ok) throw new Error('Failed to fetch all items');
          const data = await response.json();
          this.items = data.map(item => ({
            ...item,
            ekor_pack: 0,
            quantity_kg: 0
          }));
          this.populateSelect2WithStock(this.$refs.selectItem, this.items, showStock);
        } catch (error) {
          console.error('Error fetching all items:', error);
          this.errorMessage = 'Gagal memuat daftar item.';
        }
      },

      async fetchItemsWithStock(location, showStock = true) {
        try {
          const response = await fetch(`./controller/transaksi/transaksi_api.php?action=getItemsWithStock&location=${encodeURIComponent(location)}`);
          if (!response.ok) throw new Error(`Failed to fetch items with stock: ${response.statusText}`);
          const data = await response.json();
          this.items = data;
          this.populateSelect2WithStock(this.$refs.selectItem, this.items, showStock);
        } catch (error) {
          console.error('Error fetching items with stock:', error);
          this.errorMessage = 'Gagal memuat daftar item untuk lokasi ini.';
        }
      },

      populateSelect2WithStock(element, data, showStock = true) {
        const options = [{
          id: '',
          text: 'Pilih Item'
        }];

        data.forEach(item => {
          const ekorPack = item.ekor_pack || 0;
          const quantityKg = item.quantity_kg || 0;
          const itemText = showStock ?
            `${item.item.padEnd(30, ' ')} Stok: ${ekorPack} Ekor, ${quantityKg} Kg` :
            item.item;

          options.push({
            id: item.item,
            text: itemText,
            ekor_pack: ekorPack,
            quantity_kg: quantityKg
          });
        });

        $(element).empty().select2({
          data: options,
          templateResult: (state) => {
            if (!state.id) return state.text;

            if (showStock) {
              const parts = state.text.split('Stok:');
              const itemName = parts[0].trim();
              const stockInfo = parts[1] ? `Stok: ${parts[1].trim()}` : '';

              return $(`
                <div class="flex justify-between items-center">
                  <span>${itemName}</span>
                  <span class="text-black text-xl">${stockInfo}</span>
                </div>
              `);
            } else {
              return $(`<span>${state.text}</span>`);
            }
          },
          templateSelection: (state) => {
            if (!state.id) return state.text;
            if (showStock) {
              const parts = state.text.split('Stok:');
              return $(`<span>${parts[0].trim()}</span>`);
            } else {
              return $(`<span>${state.text}</span>`);
            }
          },
          placeholder: 'Pilih Item',
          allowClear: true,
          width: '100%'
        });
      },

      updateItemDropdownWithStock() {
        if (!this.$refs.selectItem) return;

        if ($(this.$refs.selectItem).data('select2')) {
          $(this.$refs.selectItem).select2('destroy');
        }

        const itemsData = this.masterData.items.map(item => {
          const stockItem = this.masterData.itemsWithStock.find(s => s.item === item.item);
          return {
            id: item.item,
            text: item.item,
            stock: stockItem || null
          };
        });

        $(this.$refs.selectItem).select2({
          data: itemsData,
          templateResult: (item) => {
            if (!item.id) return item.text;
            if (item.stock) {
              return $(`
                <span class="flex justify-between items-center w-full">
                    <span>${item.text}</span>
                    <small class="text-gray-700 text-base"> (Stok: ${item.stock.ekor_pack} Ekor, ${item.stock.quantity_kg} Kg)
                    </small>
                </span>
              `);
            }
            return item.text;
          },
          templateSelection: (item) => {
            return item.text.split(' (Stok:')[0];
          },
          width: '100%'
        }).on('change', () => {
          this.formData.item = $(this.$refs.selectItem).val();
        });

        if (this.formData.item) {
          $(this.$refs.selectItem).val(this.formData.item).trigger('change');
        }
      },

      get transactionType() {
        const fromChiller = this.isChillerLocation(this.formData.from_location);
        const toChiller = this.isChillerLocation(this.formData.to_location);

        if (this.barcodeOutScanned) {
          if (fromChiller && toChiller) return 'transfer';
          return 'out';
        }

        if (!this.formData.from_location && !this.formData.to_location) return 'in';
        if (!fromChiller && toChiller) return 'in';
        if (fromChiller && !toChiller) return 'out';
        if (fromChiller && toChiller) return 'transfer';

        return 'other';
      },

      get allLocations() {
        return this.masterData.locations;
      },

      get filteredLocationsFrom() {
        return this.masterData.locations;
      },

      get isBarcodeInHidden() {
        return this.barcodeOutScanned || ['out', 'transfer', 'other'].includes(this.transactionType);
      },
      get isBarcodeOutHidden() {
        return ['other'].includes(this.transactionType);
      },

      get isBarcodeInRequired() {
        return this.transactionType === 'in';
      },

      get isFromLocationDisabled() {
        return this.barcodeOutScanned;
      },

      get isItemDisabled() {
        return this.barcodeOutScanned;
      },

      get isSubitemHidden() {
        return this.barcodeOutScanned;
      },

      get isSubitemDisabled() {
        return this.barcodeOutScanned || !this.formData.item;
      },

      shouldShowStock() {
        return this.barcodeOutScanned && this.formData.item;
      },

      isChillerLocation(location) {
        return this.chillerLocations.includes(location);
      },

      appendValue(value, field) {
        if (value === 'C') {
          this.formData[field] = '';
          this.updateNumericValue(field);
          this.lastInputWasOperator = false;
          return;
        }
        if (this.formData[field] === 'Error') {
          this.formData[field] = '';
        }
        if (value === '.') {
          const parts = this.formData[field].split(/[\+\-\*\/]/);
          const currentNumberPart = parts[parts.length - 1];
          if (currentNumberPart.includes('.')) return;
          if (this.lastInputWasOperator || this.formData[field] === '') {
            this.formData[field] += '0.';
          } else {
            this.formData[field] += value;
          }
        } else if (!isNaN(value)) {
          this.formData[field] += value;
        }
        this.lastInputWasOperator = false;
        this.updateNumericValue(field);
      },

      calculate(operator, field) {
        let targetField = field === 'ekor' ? 'ekor_pack' : 'quantity_kg';

        if (operator === '=') {
          this.evaluateExpression(targetField);
        } else if (operator === '⌫') {
          this.handleBackspace(targetField);
        } else {
          this.handleOperator(operator, targetField);
        }
      },

      evaluateExpression(field) {
        try {
          let expression = this.formData[field].replace(/÷/g, '/').replace(/×/g, '*');
          expression = expression.replace(/[\+\-\*\/.]+$/, '');

          if (expression === '') {
            this.formData[field] = '';
            this.updateNumericValue(field);
            return;
          }
          let result = eval(expression);

          if (isNaN(result) || !isFinite(result)) {
            this.formData[field] = 'Error';
          } else {
            if (field === 'ekor_pack') {
              result = Math.round(result);
              this.formData[field] = result.toString();
            } else {
              result = parseFloat(result.toFixed(2));
              this.formData[field] = result.toFixed(2);
            }
          }
          this.updateNumericValue(field);
        } catch (e) {
          this.formData[field] = 'Error';
          this.updateNumericValue(field);
        }
        this.lastInputWasOperator = false;
      },

      handleBackspace(field) {
        if (this.formData[field] === 'Error') {
          this.formData[field] = '';
        } else {
          this.formData[field] = this.formData[field].slice(0, -1);
        }
        this.updateNumericValue(field);
        const lastChar = this.formData[field].slice(-1);
        this.lastInputWasOperator = ['+', '-', '*', '/'].includes(lastChar);
      },

      handleOperator(operator, field) {
        let currentValue = this.formData[field];
        if (currentValue === '' || currentValue === 'Error') {
          if (operator === '-') {
            this.formData[field] = '-';
            this.lastInputWasOperator = true;
          }
          return;
        }
        const lastChar = currentValue.slice(-1);
        if (['+', '-', '*', '/'].includes(lastChar)) {
          this.formData[field] = currentValue.slice(0, -1) + operator;
        } else if (lastChar === '.') {
          this.formData[field] = currentValue + '0' + operator;
        } else {
          this.formData[field] += operator;
        }
        this.lastInputWasOperator = true;
        this.updateNumericValue(field);
      },

      updateNumericValue(field) {
        let valueField = `${field}_value`;
        let expression = this.formData[field].replace(/÷/g, '/').replace(/×/g, '*');
        expression = expression.replace(/[\+\-\*\/.]+$/, '');

        if (expression === '') {
          this.formData[valueField] = null;
          return;
        }
        try {
          let result = eval(expression);
          if (isNaN(result) || !isFinite(result)) {
            this.formData[valueField] = null;
          } else {
            if (field === 'ekor_pack') {
              this.formData[valueField] = Math.round(result);
            } else {
              this.formData[valueField] = parseFloat(result.toFixed(2));
            }
          }
        } catch (e) {
          this.formData[valueField] = null;
        }
      },

      validateForm() {
        this.errorMessage = '';
        const beratValue = parseFloat(this.formData.quantity_kg);
        if (isNaN(beratValue) || beratValue <= 0) {
          Swal.fire({
            icon: 'error',
            title: 'Input Tidak Valid',
            text: 'Berat (Kg) harus berupa angka lebih dari 0.'
          });
          return false;
        }

        if (!this.isFromLocationDisabled && !this.formData.from_location) {
          Swal.fire({
            icon: 'error',
            title: 'Lokasi Asal Kosong',
            text: 'Lokasi asal harus diisi.'
          });
          return false;
        }

        if (!this.formData.to_location) {
          Swal.fire({
            icon: 'error',
            title: 'Lokasi Tujuan Kosong',
            text: 'Lokasi tujuan harus diisi.'
          });
          return false;
        }

        if (!this.isItemDisabled && !this.formData.item) {
          Swal.fire({
            icon: 'error',
            title: 'Item Tidak Dipilih',
            text: 'Silakan pilih item terlebih dahulu.'
          });
          return false;
        }

        return true;
      },

      async handleBarcodeOutScan() {
        if (this.barcodeScanInProgress) return;

        const barcode = this.formData.barcode_out.trim();
        const date = this.formData.date;
        if (!barcode) {
          this.resetBarcodeOutFields();
          return;
        }

        this.barcodeScanInProgress = true;
        this.isLoading = true;

        try {
          const response = await fetch(`./controller/transaksi/transaksi_api.php?action=getTransactionByBarcodeIn&barcode_in=${encodeURIComponent(barcode)}&date=${encodeURIComponent(date)}`);
          if (!response.ok) throw new Error(`Gagal mengambil data: ${response.status}`);

          const result = await response.json();

          if (result.success && result.data) {
            const transaction = result.data;

            this.formData = {
              ...this.formData,
              id_in: transaction.id,
              from_location: transaction.storage,
              item: transaction.item,
              subitem: transaction.subitem || '',
              ekor_pack: transaction.current_ekor_barcode.toString(),
              quantity_kg: transaction.current_kg_barcode.toString(),
              tanggal_stock: transaction.tanggal_stock,
              status_bahan: transaction.status_bahan,
            };

            this.barcodeOutScanned = true;

            await this.$nextTick(async () => {
              if (this.$refs.selectFromLocation) {
                this.$refs.selectFromLocation.value = transaction.storage;
                this.$refs.selectFromLocation.dispatchEvent(new Event('change'));
              }

              await this.fetchItemsWithStock(transaction.storage);

              if (this.$refs.selectItem) {
                const $selectItem = $(this.$refs.selectItem);
                if ($selectItem.find(`option[value="${transaction.item}"]`).length === 0) {
                  $selectItem.append(new Option(transaction.item, transaction.item, true, true));
                }
                $selectItem.val(transaction.item).trigger('change');
              }

              if (transaction.subitem && this.$refs.selectSubitem) {
                const $selectSubitem = $(this.$refs.selectSubitem);
                if ($selectSubitem.find(`option[value="${transaction.subitem}"]`).length > 0) {
                  $selectSubitem.val(transaction.subitem).trigger('change');
                } else {
                  $selectSubitem.append(new Option(transaction.subitem, transaction.subitem, true, true));
                  $selectSubitem.val(transaction.subitem).trigger('change');
                }
              }

              this.currentStock = {
                ekor_pack: transaction.current_ekor_stock,
                quantity_kg: transaction.current_kg_stock,
                ekor_barcode: transaction.current_ekor_barcode,
                kg_barcode: transaction.current_kg_barcode,
                ekor_masuk: transaction.ekor_pack,
                kg_masuk: transaction.quantity_kg
              };
            });
          } else {
            this.showError(result.message || 'Barcode tidak ditemukan');
            this.resetBarcodeOutFields();
          }
        } catch (error) {
          console.error('Error in handleBarcodeOutScan:', error);
          this.showError('Gagal memindai barcode. Silakan coba lagi.');
          this.resetBarcodeOutFields();
        } finally {
          this.isLoading = false;
          this.barcodeScanInProgress = false;
        }
      },

      handleBarcodeOutScanIfFilled() {
        if (this.formData.barcode_out && this.formData.barcode_out.trim().length > 0) {
          this.handleBarcodeOutScan();
        }
      },

      resetBarcodeOutFields() {
        this.formData.id_in = null;
        this.formData.barcode_out = '';
        this.formData.item = '';
        this.formData.subitem = '';
        this.formData.ekor_pack = '';
        this.formData.ekor_pack_value = null;
        this.formData.quantity_kg = '';
        this.formData.quantity_kg_value = null;
        this.formData.keterangan = '';

        this.barcodeOutScanned = false;
        this.formData.from_location = '';
        this.formData.to_location = '';

        this.$nextTick(() => {
          if (this.$refs.selectItem) $(this.$refs.selectItem).val('').trigger('change');
          if (this.$refs.selectSubitem) $(this.$refs.selectSubitem).val('').trigger('change');
          if (this.$refs.selectFromLocation) $(this.$refs.selectFromLocation).val('').trigger('change');
          if (this.$refs.selectToLocation) $(this.$refs.selectToLocation).val('').trigger('change');
        });

        this.currentStock = {
          ekor_pack: 0,
          quantity_kg: 0
        };
        this.masterData.itemsWithStock = [];
        this.updateItemDropdownWithStock();
      },

      prepareSubmitData() {
        return {
          action: 'addUpdateTransaction',
          date: this.formData.date,
          from_location: this.formData.from_location,
          to_location: this.formData.to_location,
          item: this.formData.item,
          subitem: this.formData.subitem,
          ekor_pack: this.formData.ekor_pack,
          quantity_kg: this.formData.quantity_kg,
          keterangan: this.formData.keterangan,
          barcode_in: this.formData.barcode_in,
          barcode_out: this.formData.barcode_out,
          user: this.formData.user,
          id_in: this.formData.id_in,
          transaction_type: this.transactionType
        };
      },

      async submitForm(event, saveAndContinue = false) {
        event.preventDefault();

        if (this.isLoading) return;
        if (!this.validateForm()) return;

        if (this.formData.from_location === this.formData.to_location && this.formData.from_location !== '') {
          Swal.fire({
            icon: 'warning',
            title: 'Lokasi Sama',
            text: 'Lokasi "Dari" dan "Tujuan" tidak boleh sama untuk transaksi ini.',
            confirmButtonText: 'Oke'
          });
          return;
        }

        this.isLoading = true;

        try {
          const submitData = this.prepareSubmitData();
          const response = await fetch('./controller/transaksi/transaksi_api.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify(submitData)
          });

          if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP error! Status: ${response.status}, Details: ${errorText}`);
          }

          const result = await response.json();

          if (result.success) {
            if (this.currentPendingId) {
              await this.completePendingTransaction(this.currentPendingId);
            }
            this.handleSuccess(result.message, saveAndContinue, submitData);
          } else {
            this.showError(result.message || 'Gagal menyimpan data.');
          }
        } catch (error) {
          console.error('Submission error:', error);
          this.showError(`Terjadi kesalahan: ${error.message}`);
        } finally {
          this.isLoading = false;
        }
      },

      fullResetForm() {
        this.formData.id_in = null;
        this.formData.barcode_out = '';
        this.formData.barcode_in = '';
        this.formData.item = '';
        this.formData.subitem = '';
        this.formData.ekor_pack = '';
        this.formData.quantity_kg = '';
        this.formData.keterangan = '';
        this.formData.from_location = '';
        this.formData.to_location = '';

        this.disablePendingButtons = false;
        this.barcodeOutScanned = false;
        this.currentPendingId = null;

        this.$nextTick(() => {
          if (this.$refs.selectItem) $(this.$refs.selectItem).val('').trigger('change');
          if (this.$refs.selectSubitem) $(this.$refs.selectSubitem).val('').trigger('change');
          if (this.$refs.selectFromLocation) $(this.$refs.selectFromLocation).val('').trigger('change');
          if (this.$refs.selectToLocation) $(this.$refs.selectToLocation).val('').trigger('change');
          if (this.$refs.barcodeOut) this.$refs.barcodeOut.focus();
        });

        this.currentStock = {
          ekor_pack: 0,
          quantity_kg: 0,
          ekor_barcode: 0,
          kg_barcode: 0,
          ekor_masuk: 0,
          kg_masuk: 0
        };
        this.masterData.itemsWithStock = [];
      },

      handleSuccess(message, saveAndContinue, submittedData) {
        Swal.fire({
          icon: 'success',
          title: 'Berhasil!',
          text: message,
          showConfirmButton: false,
          timer: 1500
        }).then(() => {
          if (saveAndContinue) {
            this.resetFormForContinue();
          } else {
            window.location.href = '?page=transaksi/';
          }
        });
      },

      resetFormForContinue() {
        this.formData.ekor_pack = '';
        this.formData.quantity_kg = '';
        this.formData.keterangan = '';
        this.formData.barcode_in = '';
        this.formData.barcode_out = '';
        this.formData.id_in = '';
      }
    }));
  });
</script>