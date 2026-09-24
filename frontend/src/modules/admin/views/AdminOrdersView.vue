<script setup lang="ts">
import { ref, computed } from 'vue'
import { useAdminStore } from '../admin.store'
import type { AdminOrder, OrderStatus } from '../admin.types'
import { formatCurrency } from '@/shared/utils/currency.util'

const adminStore = useAdminStore()

const searchQuery = ref('')
const statusFilter = ref<'All' | OrderStatus>('All')
const selectedOrder = ref<AdminOrder | null>(null)

// Modals
const isFulfillmentModalOpen = ref(false)
const isRefundModalOpen = ref(false)
const isInvoiceModalOpen = ref(false)

// Fulfillment form
const trackingNumberInput = ref('')
const carrierInput = ref('J&T Express')

// Refund form
const refundAmountInput = ref(0)
const isFullRefund = ref(false)
const restockInventory = ref(true)

const filteredOrders = computed(() => {
  return adminStore.orders.filter((order) => {
    const matchesStatus = statusFilter.value === 'All' || order.status === statusFilter.value
    const matchesSearch =
      order.id.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      order.customerName.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      order.customerEmail.toLowerCase().includes(searchQuery.value.toLowerCase())
    return matchesStatus && matchesSearch
  })
})

const openFulfillment = (order: AdminOrder) => {
  selectedOrder.value = order
  trackingNumberInput.value = order.trackingNumber || `PH-${Math.floor(100000000 + Math.random() * 900000000)}`
  carrierInput.value = order.carrier || 'J&T Express'
  isFulfillmentModalOpen.value = true
}

const saveFulfillment = () => {
  if (selectedOrder.value && trackingNumberInput.value) {
    adminStore.attachTracking(selectedOrder.value.id, trackingNumberInput.value, carrierInput.value)
    adminStore.markPackingSlipPrinted(selectedOrder.value.id)
    isFulfillmentModalOpen.value = false
  }
}

const openRefund = (order: AdminOrder) => {
  selectedOrder.value = order
  refundAmountInput.value = order.total
  isFullRefund.value = true
  isRefundModalOpen.value = true
}

const confirmRefund = () => {
  if (selectedOrder.value) {
    adminStore.processRefund(selectedOrder.value.id, refundAmountInput.value, isFullRefund.value)
    isRefundModalOpen.value = false
  }
}

const openInvoice = (order: AdminOrder) => {
  selectedOrder.value = order
  isInvoiceModalOpen.value = true
}

const printDocument = () => {
  window.print()
}

const getStatusBadge = (status: OrderStatus) => {
  switch (status) {
    case 'Pending':
      return 'bg-amber-100 text-amber-800 border-amber-200'
    case 'Processing':
      return 'bg-blue-100 text-blue-800 border-blue-200'
    case 'Shipped':
      return 'bg-purple-100 text-purple-800 border-purple-200'
    case 'Delivered':
      return 'bg-emerald-100 text-emerald-800 border-emerald-200'
    case 'Canceled':
      return 'bg-rose-100 text-rose-800 border-rose-200'
    default:
      return 'bg-slate-100 text-slate-800'
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Order Lifecycle &amp; Fulfillment</h1>
        <p class="text-xs text-slate-500">Track statuses, print packing slips, attach carrier tracking, issue refunds, and generate PDF invoices.</p>
      </div>

      <div class="flex items-center gap-2">
        <span class="text-xs font-bold text-slate-500 bg-slate-100 px-3 py-1.5 rounded-xl border border-slate-200">
          Total: {{ adminStore.orders.length }} Orders
        </span>
      </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
      <!-- Search Input -->
      <div class="relative w-full md:w-80">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search by order ID, customer name, email..."
          class="w-full text-xs px-3.5 py-2.5 pl-9 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none focus:border-rose-500"
        />
        <span class="absolute left-3 top-2.5 text-slate-400 text-xs">🔍</span>
      </div>

      <!-- Status Filter Tabs -->
      <div class="flex items-center gap-1 overflow-x-auto w-full md:w-auto p-1 bg-slate-100 rounded-xl border border-slate-200">
        <button
          v-for="st in (['All', 'Pending', 'Processing', 'Shipped', 'Delivered', 'Canceled'] as const)"
          :key="st"
          type="button"
          class="px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all cursor-pointer"
          :class="statusFilter === st ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
          @click="statusFilter = st"
        >
          {{ st }}
        </button>
      </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200/80 text-slate-500 uppercase font-bold text-[10px] tracking-wider">
              <th class="p-4">Order ID &amp; Date</th>
              <th class="p-4">Customer &amp; Address</th>
              <th class="p-4">Items Ordered</th>
              <th class="p-4">Total &amp; Payment</th>
              <th class="p-4">Status</th>
              <th class="p-4">Fulfillment / Tracking</th>
              <th class="p-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="order in filteredOrders"
              :key="order.id"
              class="hover:bg-slate-50/70 transition-colors"
            >
              <!-- ID & Date -->
              <td class="p-4">
                <span class="font-mono font-bold text-slate-900 text-xs">{{ order.id }}</span>
                <p class="text-[11px] text-slate-400 mt-0.5">{{ order.createdAt }}</p>
              </td>

              <!-- Customer -->
              <td class="p-4">
                <span class="font-bold text-slate-900 block">{{ order.customerName }}</span>
                <span class="text-[11px] text-slate-500 block">{{ order.city }} &bull; {{ order.customerPhone }}</span>
              </td>

              <!-- Items -->
              <td class="p-4">
                <div class="flex items-center gap-1.5">
                  <img
                    v-for="item in order.items"
                    :key="item.id"
                    :src="item.imageUrl"
                    :alt="item.name"
                    class="w-8 h-8 rounded-lg object-cover border border-slate-200 shadow-2xs"
                    :title="`${item.name} (x${item.quantity})`"
                  />
                  <span class="text-[11px] font-semibold text-slate-600 ml-1">
                    {{ order.items.reduce((s, i) => s + i.quantity, 0) }} item(s)
                  </span>
                </div>
              </td>

              <!-- Total & Payment -->
              <td class="p-4">
                <span class="font-extrabold text-slate-900 block">{{ formatCurrency(order.total) }}</span>
                <span class="text-[10px] font-bold text-slate-500 uppercase">{{ order.paymentMethod }}</span>
                <span v-if="order.refundStatus && order.refundStatus !== 'None'" class="block text-[10px] text-rose-600 font-bold">
                  Refunded {{ formatCurrency(order.refundAmount || 0) }}
                </span>
              </td>

              <!-- Status Dropdown Selector -->
              <td class="p-4">
                <select
                  :value="order.status"
                  class="text-xs font-bold px-2.5 py-1 rounded-full border cursor-pointer focus:outline-none"
                  :class="getStatusBadge(order.status)"
                  @change="(e) => adminStore.updateOrderStatus(order.id, (e.target as HTMLSelectElement).value as OrderStatus)"
                >
                  <option value="Pending">⏳ Pending</option>
                  <option value="Processing">⚙️ Processing</option>
                  <option value="Shipped">🚚 Shipped</option>
                  <option value="Delivered">✓ Delivered</option>
                  <option value="Canceled">✕ Canceled</option>
                </select>
              </td>

              <!-- Fulfillment / Tracking -->
              <td class="p-4">
                <div v-if="order.trackingNumber" class="space-y-0.5">
                  <span class="text-[10px] font-bold text-slate-400 block">{{ order.carrier }}</span>
                  <span class="font-mono text-xs font-semibold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200 inline-block">
                    {{ order.trackingNumber }}
                  </span>
                </div>
                <button
                  v-else
                  type="button"
                  class="text-[11px] font-bold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-2.5 py-1 rounded-lg border border-rose-200 cursor-pointer"
                  @click="openFulfillment(order)"
                >
                  + Generate Label &amp; Track
                </button>
              </td>

              <!-- Actions -->
              <td class="p-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    type="button"
                    class="p-1.5 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors cursor-pointer"
                    title="Print Packing Slip & Label"
                    @click="openFulfillment(order)"
                  >
                    🏷️
                  </button>
                  <button
                    type="button"
                    class="p-1.5 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors cursor-pointer"
                    title="View Invoice Receipt"
                    @click="openInvoice(order)"
                  >
                    🧾
                  </button>
                  <button
                    type="button"
                    class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                    title="Process Refund"
                    @click="openRefund(order)"
                  >
                    ↩️
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 1. Fulfillment & Shipping Label Modal -->
    <teleport to="body">
      <div v-if="isFulfillmentModalOpen && selectedOrder" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-5 shadow-2xl border border-slate-200 font-display">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <h3 class="text-base font-bold text-slate-900">Fulfillment Tools &bull; {{ selectedOrder.id }}</h3>
              <p class="text-xs text-slate-500">Generate carrier shipping label and print packing slip</p>
            </div>
            <button type="button" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center cursor-pointer" @click="isFulfillmentModalOpen = false">
              ✕
            </button>
          </div>

          <!-- Carrier & Tracking inputs -->
          <div class="space-y-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Select Shipping Carrier</label>
              <select v-model="carrierInput" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 bg-white">
                <option value="J&T Express">J&T Express Philippines</option>
                <option value="LBC Express">LBC Express (Door to Door)</option>
                <option value="Ninja Van">Ninja Van Standard</option>
                <option value="Flash Express">Flash Express Priority</option>
                <option value="DHL Express">DHL Express International</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Carrier Tracking Number</label>
              <input
                v-model="trackingNumberInput"
                type="text"
                placeholder="e.g. PH-JT-98234812"
                class="w-full text-xs font-mono p-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900"
              />
            </div>

            <!-- Simulated Barcode Shipping Label Preview -->
            <div class="p-4 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 space-y-2 text-center">
              <div class="flex items-center justify-between text-[11px] font-bold text-slate-600">
                <span>{{ carrierInput }}</span>
                <span>AIR COURIER WAYBILL</span>
              </div>
              <div class="font-mono text-2xl tracking-widest text-slate-900 py-2 select-none">
                ||| | |||| | ||| |||| | ||
              </div>
              <p class="font-mono text-xs font-bold text-slate-800">{{ trackingNumberInput }}</p>
              <div class="text-[10px] text-slate-500 text-left pt-2 border-t border-slate-200">
                <p><span class="font-bold">Ship To:</span> {{ selectedOrder.customerName }}</p>
                <p><span class="font-bold">Address:</span> {{ selectedOrder.shippingAddress }}, {{ selectedOrder.city }} ({{ selectedOrder.postalCode }})</p>
              </div>
            </div>
          </div>

          <div class="flex gap-3 pt-2">
            <button
              type="button"
              class="flex-1 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs cursor-pointer flex items-center justify-center gap-1.5"
              @click="printDocument"
            >
              <span>🖨️ Print Packing Slip</span>
            </button>
            <button
              type="button"
              class="flex-1 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs cursor-pointer"
              @click="saveFulfillment"
            >
              Attach &amp; Mark Shipped
            </button>
          </div>
        </div>
      </div>
    </teleport>

    <!-- 2. Refund & Return Modal -->
    <teleport to="body">
      <div v-if="isRefundModalOpen && selectedOrder" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-slate-200 font-display">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <h3 class="text-base font-bold text-slate-900">Process Return / Refund</h3>
              <p class="text-xs text-slate-500">Order {{ selectedOrder.id }} &bull; Paid {{ formatCurrency(selectedOrder.total) }}</p>
            </div>
            <button type="button" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center cursor-pointer" @click="isRefundModalOpen = false">
              ✕
            </button>
          </div>

          <div class="space-y-3">
            <div class="flex gap-4 text-xs font-bold">
              <label class="flex items-center gap-1.5 cursor-pointer">
                <input v-model="isFullRefund" type="radio" :value="true" class="accent-rose-600" />
                <span>Full Refund</span>
              </label>
              <label class="flex items-center gap-1.5 cursor-pointer">
                <input v-model="isFullRefund" type="radio" :value="false" class="accent-rose-600" />
                <span>Partial Refund</span>
              </label>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Refund Amount (PHP)</label>
              <input
                v-model.number="refundAmountInput"
                type="number"
                :max="selectedOrder.total"
                class="w-full text-xs p-2.5 rounded-xl border border-slate-200 font-bold focus:outline-none focus:border-slate-900"
              />
            </div>

            <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer pt-1">
              <input v-model="restockInventory" type="checkbox" class="accent-rose-600 rounded" />
              <span>Automatically return items back into available inventory</span>
            </label>
          </div>

          <div class="flex gap-3 pt-3">
            <button type="button" class="flex-1 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs" @click="isRefundModalOpen = false">
              Cancel
            </button>
            <button type="button" class="flex-1 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs" @click="confirmRefund">
              Confirm &amp; Issue Refund
            </button>
          </div>
        </div>
      </div>
    </teleport>

    <!-- 3. Printable PDF Invoice Modal -->
    <teleport to="body">
      <div v-if="isInvoiceModalOpen && selectedOrder" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-xl w-full p-8 space-y-6 shadow-2xl border border-slate-200 font-display max-h-[90vh] overflow-y-auto">
          <div class="flex items-start justify-between pb-4 border-b border-slate-200">
            <div>
              <span class="text-xl font-black text-slate-900 tracking-tight">RLG <span class="text-rose-600">HOBBY</span></span>
              <p class="text-[11px] text-slate-400">Official Commercial Tax Invoice</p>
            </div>
            <div class="text-right">
              <p class="font-mono text-sm font-extrabold text-slate-900">{{ selectedOrder.invoiceId }}</p>
              <p class="text-[11px] text-slate-500">{{ selectedOrder.createdAt }}</p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4 text-xs text-slate-600">
            <div>
              <h4 class="font-bold text-slate-900 mb-1">Billed &amp; Shipped To:</h4>
              <p class="font-bold text-slate-800">{{ selectedOrder.customerName }}</p>
              <p>{{ selectedOrder.shippingAddress }}</p>
              <p>{{ selectedOrder.city }}, {{ selectedOrder.postalCode }}</p>
              <p>{{ selectedOrder.customerEmail }}</p>
            </div>
            <div class="text-right">
              <h4 class="font-bold text-slate-900 mb-1">Payment Information:</h4>
              <p><span class="font-bold">Method:</span> {{ selectedOrder.paymentMethod }}</p>
              <p><span class="font-bold">Status:</span> Paid &bull; Verified</p>
              <p><span class="font-bold">Currency:</span> PHP (₱)</p>
            </div>
          </div>

          <!-- Items Table -->
          <table class="w-full text-xs text-left">
            <thead>
              <tr class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] border-b border-slate-200">
                <th class="p-2">Item Description</th>
                <th class="p-2 text-center">Qty</th>
                <th class="p-2 text-right">Price</th>
                <th class="p-2 text-right">Subtotal</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in selectedOrder.items" :key="item.id">
                <td class="p-2">
                  <p class="font-bold text-slate-800">{{ item.name }}</p>
                  <p class="text-[10px] text-slate-400 font-mono">SKU: {{ item.sku }}</p>
                </td>
                <td class="p-2 text-center font-bold">{{ item.quantity }}</td>
                <td class="p-2 text-right">{{ formatCurrency(item.price) }}</td>
                <td class="p-2 text-right font-bold">{{ formatCurrency(item.price * item.quantity) }}</td>
              </tr>
            </tbody>
          </table>

          <!-- Totals -->
          <div class="pt-3 border-t border-slate-200 space-y-1 text-xs text-slate-600 text-right">
            <div class="flex justify-between font-extrabold text-base text-slate-900 pt-2 border-t border-slate-200">
              <span>Grand Total</span>
              <span class="text-rose-600">{{ formatCurrency(selectedOrder.total) }}</span>
            </div>
          </div>

          <div class="flex gap-3 pt-3 border-t border-slate-100">
            <button type="button" class="flex-1 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs" @click="isInvoiceModalOpen = false">
              Close
            </button>
            <button type="button" class="flex-1 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center justify-center gap-1.5" @click="printDocument">
              <span>🖨️ Download / Print PDF</span>
            </button>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>
