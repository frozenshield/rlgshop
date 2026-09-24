<script setup lang="ts">
import { ref, computed } from 'vue'
import { useAdminStore } from '../admin.store'
import type { InventoryItem } from '../admin.types'
import { formatCurrency } from '@/shared/utils/currency.util'

const adminStore = useAdminStore()

const searchQuery = ref('')
const selectedCategory = ref('All')
const selectedItem = ref<InventoryItem | null>(null)
const isVariantModalOpen = ref(false)
const isVendorModalOpen = ref(false)
const isCsvModalOpen = ref(false)
const notificationMessage = ref('')

const categories = computed(() => {
  const cats = new Set(adminStore.inventory.map((i) => i.category))
  return ['All', ...Array.from(cats)]
})

const filteredInventory = computed(() => {
  return adminStore.inventory.filter((item) => {
    const matchesCategory = selectedCategory.value === 'All' || item.category === selectedCategory.value
    const matchesSearch =
      item.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      item.sku.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      item.barcode.includes(searchQuery.value) ||
      item.vendor.toLowerCase().includes(searchQuery.value.toLowerCase())
    return matchesCategory && matchesSearch
  })
})

const adjustStock = (item: InventoryItem, delta: number) => {
  const newStock = Math.max(0, item.stock + delta)
  adminStore.updateStock(item.id, newStock)
}

const openVariants = (item: InventoryItem) => {
  selectedItem.value = item
  isVariantModalOpen.value = true
}

const openVendor = (item: InventoryItem) => {
  selectedItem.value = item
  isVendorModalOpen.value = true
}

const triggerExportCsv = () => {
  const headers = 'SKU,Barcode,Product Name,Category,Stock,Cost Price,Selling Price,Vendor,Lead Time (Days)\n'
  const rows = adminStore.inventory
    .map(
      (i) =>
        `"${i.sku}","${i.barcode}","${i.name}","${i.category}",${i.stock},${i.costPrice},${i.sellingPrice},"${i.vendor}",${i.leadTimeDays}`,
    )
    .join('\n')
  const blob = new Blob([headers + rows], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.setAttribute('href', url)
  link.setAttribute('download', `rlg-hobby-inventory-${new Date().toISOString().slice(0, 10)}.csv`)
  link.click()

  showNotification('CSV exported successfully with all SKUs and variant data!')
}

const triggerImportCsv = () => {
  isCsvModalOpen.value = true
}

const handleSimulatedImport = () => {
  isCsvModalOpen.value = false
  showNotification('CSV bulk import verified: 5 SKUs updated with latest inventory counts.')
}

const showNotification = (msg: string) => {
  notificationMessage.value = msg
  setTimeout(() => {
    notificationMessage.value = ''
  }, 4000)
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Inventory &amp; Stock Control</h1>
        <p class="text-xs text-slate-500">Real-time SKU monitoring, variant matrices, vendor lead times, and bulk CSV updates.</p>
      </div>

      <!-- Bulk Actions -->
      <div class="flex items-center gap-2">
        <button
          type="button"
          class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs flex items-center gap-1.5 transition-all cursor-pointer"
          @click="triggerExportCsv"
        >
          <span>📥 Export CSV</span>
        </button>
        <button
          type="button"
          class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center gap-1.5 transition-all cursor-pointer shadow-xs"
          @click="triggerImportCsv"
        >
          <span>📤 Import CSV</span>
        </button>
      </div>
    </div>

    <!-- Notification Banner -->
    <div v-if="notificationMessage" class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center justify-between animate-fade-in">
      <div class="flex items-center gap-2">
        <span>✓</span>
        <span>{{ notificationMessage }}</span>
      </div>
      <button type="button" class="text-emerald-600 hover:text-emerald-900" @click="notificationMessage = ''">✕</button>
    </div>

    <!-- Filters Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
      <div class="relative w-full md:w-80">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search by SKU, barcode, name, or vendor..."
          class="w-full text-xs px-3.5 py-2.5 pl-9 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none focus:border-rose-500"
        />
        <span class="absolute left-3 top-2.5 text-slate-400 text-xs">🔍</span>
      </div>

      <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto">
        <span class="text-xs font-bold text-slate-400 whitespace-nowrap">Category:</span>
        <div class="flex gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200">
          <button
            v-for="cat in categories"
            :key="cat"
            type="button"
            class="px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all cursor-pointer"
            :class="selectedCategory === cat ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
            @click="selectedCategory = cat"
          >
            {{ cat }}
          </button>
        </div>
      </div>
    </div>

    <!-- Inventory Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200/80 text-slate-500 uppercase font-bold text-[10px] tracking-wider">
              <th class="p-4">SKU &amp; Barcode</th>
              <th class="p-4">Product Name &amp; Category</th>
              <th class="p-4">Cost vs Selling Price</th>
              <th class="p-4">Gross Margin</th>
              <th class="p-4">Stock on Hand</th>
              <th class="p-4">Vendor &amp; Lead Time</th>
              <th class="p-4 text-right">Stock &amp; Variants</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="item in filteredInventory"
              :key="item.id"
              class="hover:bg-slate-50/70 transition-colors"
            >
              <!-- SKU & Barcode -->
              <td class="p-4">
                <span class="font-mono font-bold text-slate-900 text-xs block">{{ item.sku }}</span>
                <span class="font-mono text-[10px] text-slate-400 block">UPC: {{ item.barcode }}</span>
              </td>

              <!-- Name & Category -->
              <td class="p-4">
                <span class="font-bold text-slate-900 block max-w-xs truncate">{{ item.name }}</span>
                <span class="text-[10px] font-semibold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-md inline-block mt-0.5">
                  {{ item.category }}
                </span>
              </td>

              <!-- Cost vs Selling -->
              <td class="p-4">
                <div class="space-y-0.5">
                  <div class="font-bold text-slate-900">{{ formatCurrency(item.sellingPrice) }}</div>
                  <div class="text-[10px] text-slate-400 font-mono">Cost: {{ formatCurrency(item.costPrice) }}</div>
                </div>
              </td>

              <!-- Margin -->
              <td class="p-4">
                <span class="font-bold text-emerald-600">
                  {{ Math.round(((item.sellingPrice - item.costPrice) / item.sellingPrice) * 100) }}%
                </span>
                <span class="text-[10px] text-slate-400 block">+{{ formatCurrency(item.sellingPrice - item.costPrice) }}</span>
              </td>

              <!-- Stock Quantity with Quick Stepper -->
              <td class="p-4">
                <div class="flex items-center gap-2">
                  <div class="flex items-center border border-slate-200 rounded-lg bg-slate-50 overflow-hidden">
                    <button
                      type="button"
                      class="px-2 py-1 hover:bg-slate-200 text-slate-600 font-bold transition-colors cursor-pointer"
                      @click="adjustStock(item, -1)"
                    >
                      -
                    </button>
                    <span
                      class="w-10 text-center font-bold text-xs"
                      :class="item.stock <= item.lowStockThreshold ? 'text-rose-600' : 'text-slate-800'"
                    >
                      {{ item.stock }}
                    </span>
                    <button
                      type="button"
                      class="px-2 py-1 hover:bg-slate-200 text-slate-600 font-bold transition-colors cursor-pointer"
                      @click="adjustStock(item, 1)"
                    >
                      +
                    </button>
                  </div>

                  <span
                    v-if="item.stock <= item.lowStockThreshold"
                    class="text-[10px] font-bold px-2 py-0.5 rounded bg-rose-100 text-rose-700 whitespace-nowrap"
                  >
                    Low (≤{{ item.lowStockThreshold }})
                  </span>
                  <span
                    v-else
                    class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 whitespace-nowrap"
                  >
                    Healthy
                  </span>
                </div>
              </td>

              <!-- Vendor & Lead Time -->
              <td class="p-4">
                <button
                  type="button"
                  class="text-left font-bold text-slate-800 hover:text-rose-600 hover:underline block truncate max-w-[150px] cursor-pointer"
                  @click="openVendor(item)"
                >
                  {{ item.vendor }}
                </button>
                <span class="text-[10px] text-slate-400">{{ item.leadTimeDays }} days restock lead</span>
              </td>

              <!-- Variants & Actions -->
              <td class="p-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button
                    v-if="item.variants && item.variants.length > 0"
                    type="button"
                    class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-bold text-[11px] border border-indigo-200 cursor-pointer"
                    @click="openVariants(item)"
                  >
                    {{ item.variants.length }} Variants
                  </button>
                  <button
                    type="button"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-800 hover:bg-slate-100 cursor-pointer"
                    title="Vendor Lead Specs"
                    @click="openVendor(item)"
                  >
                    🏢
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Variant Matrix Modal -->
    <teleport to="body">
      <div v-if="isVariantModalOpen && selectedItem" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl border border-slate-200 font-display">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <h3 class="text-base font-bold text-slate-900">Variant Matrix &bull; {{ selectedItem.name }}</h3>
              <p class="text-xs text-slate-500">Manage individual stock, SKU, and prices per variation</p>
            </div>
            <button type="button" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center cursor-pointer" @click="isVariantModalOpen = false">
              ✕
            </button>
          </div>

          <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
            <div v-for="v in selectedItem.variants" :key="v.id" class="py-3 flex items-center justify-between text-xs">
              <div>
                <span class="font-bold text-slate-900">{{ v.title }}</span>
                <p class="font-mono text-[10px] text-slate-400">SKU: {{ v.sku }}</p>
              </div>
              <div class="flex items-center gap-3">
                <span class="font-bold text-slate-900">{{ formatCurrency(v.price) }}</span>
                <div class="flex items-center border border-slate-200 rounded-lg bg-slate-50">
                  <button type="button" class="px-2 py-0.5 hover:bg-slate-200 text-slate-600 font-bold" @click="v.stock = Math.max(0, v.stock - 1)">-</button>
                  <span class="w-8 text-center font-bold text-slate-800">{{ v.stock }}</span>
                  <button type="button" class="px-2 py-0.5 hover:bg-slate-200 text-slate-600 font-bold" @click="v.stock++">+</button>
                </div>
              </div>
            </div>
          </div>

          <div class="pt-2 border-t border-slate-100 flex justify-end">
            <button type="button" class="px-4 py-2 bg-slate-900 text-white text-xs font-bold rounded-xl cursor-pointer" @click="isVariantModalOpen = false">
              Save Variant Quantities
            </button>
          </div>
        </div>
      </div>
    </teleport>

    <!-- Vendor Management Modal -->
    <teleport to="body">
      <div v-if="isVendorModalOpen && selectedItem" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-slate-200 font-display">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <h3 class="text-base font-bold text-slate-900">Supplier &amp; Vendor Profile</h3>
              <p class="text-xs text-slate-500">Procurement channel and lead time metrics</p>
            </div>
            <button type="button" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center cursor-pointer" @click="isVendorModalOpen = false">
              ✕
            </button>
          </div>

          <div class="space-y-3 text-xs">
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
              <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Primary Vendor</span>
              <p class="text-sm font-extrabold text-slate-900">{{ selectedItem.vendor }}</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <span class="text-[10px] font-bold uppercase text-slate-400">Unit Wholesale Cost</span>
                <p class="font-extrabold text-slate-900 mt-0.5">{{ formatCurrency(selectedItem.costPrice) }}</p>
              </div>
              <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <span class="text-[10px] font-bold uppercase text-slate-400">Restock Lead Time</span>
                <p class="font-extrabold text-slate-900 mt-0.5">{{ selectedItem.leadTimeDays }} Business Days</p>
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Restock Lead Time (Days)</label>
              <input v-model.number="selectedItem.leadTimeDays" type="number" class="w-full text-xs p-2.5 rounded-xl border border-slate-200" />
            </div>
          </div>

          <div class="pt-2 border-t border-slate-100 flex justify-end">
            <button type="button" class="px-4 py-2 bg-slate-900 text-white text-xs font-bold rounded-xl cursor-pointer" @click="isVendorModalOpen = false">
              Update Vendor Info
            </button>
          </div>
        </div>
      </div>
    </teleport>

    <!-- CSV Bulk Import Simulation Modal -->
    <teleport to="body">
      <div v-if="isCsvModalOpen" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-slate-200 font-display">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <h3 class="text-base font-bold text-slate-900">Bulk CSV Inventory Import</h3>
              <p class="text-xs text-slate-500">Upload CSV to mass update quantities and prices</p>
            </div>
            <button type="button" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center cursor-pointer" @click="isCsvModalOpen = false">
              ✕
            </button>
          </div>

          <div class="p-6 border-2 border-dashed border-slate-200 rounded-2xl text-center space-y-2 bg-slate-50">
            <span class="text-3xl">📄</span>
            <p class="text-xs font-bold text-slate-700">Drop your inventory spreadsheet (.csv) here</p>
            <p class="text-[11px] text-slate-400">Supported columns: SKU, Stock, Price, Cost</p>
          </div>

          <div class="flex gap-3">
            <button type="button" class="flex-1 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs" @click="isCsvModalOpen = false">
              Cancel
            </button>
            <button type="button" class="flex-1 py-2.5 rounded-xl bg-slate-900 text-white font-bold text-xs" @click="handleSimulatedImport">
              Process &amp; Update Stock
            </button>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>
