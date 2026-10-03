<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useAdminStore } from "../admin.store";
import type { AdminOrder, OrderStatus } from "../admin.types";
import { formatCurrency } from "@/shared/utils/currency.util";
import OrderItemsDetailModal from "../components/OrderItemsDetailModal.vue";

const adminStore = useAdminStore();

onMounted(async () => {
  await Promise.all([
    adminStore.fetchOrders(),
    adminStore.fetchShippingCarriers(),
    adminStore.fetchOrderStatuses(),
  ]);
});

const searchQuery = ref("");
const statusFilter = ref<"All" | OrderStatus>("All");
const selectedOrder = ref<AdminOrder | null>(null);

// Modals
const isFulfillmentModalOpen = ref(false);
const isRefundModalOpen = ref(false);
const isInvoiceModalOpen = ref(false);
const isItemsDetailModalOpen = ref(false);

const openItemsDetail = (order: AdminOrder) => {
  selectedOrder.value = order;
  isItemsDetailModalOpen.value = true;
};

// Fulfillment form
const trackingNumberInput = ref("");
const carrierInput = ref("J&T Express Philippines");

// Refund form
const refundAmountInput = ref(0);
const isFullRefund = ref(false);
const restockInventory = ref(true);

const filteredOrders = computed(() => {
  return adminStore.orders.filter((order) => {
    const matchesStatus =
      statusFilter.value === "All" || order.status === statusFilter.value;
    const matchesSearch =
      order.id.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      order.customerName
        .toLowerCase()
        .includes(searchQuery.value.toLowerCase()) ||
      order.customerEmail
        .toLowerCase()
        .includes(searchQuery.value.toLowerCase());
    return matchesStatus && matchesSearch;
  });
});

const openFulfillment = (order: AdminOrder) => {
  selectedOrder.value = order;
  trackingNumberInput.value =
    order.trackingNumber ||
    `PH-${Math.floor(100000000 + Math.random() * 900000000)}`;
  carrierInput.value =
    order.carrier ||
    adminStore.shippingCarriers[0]?.name ||
    "J&T Express Philippines";
  isFulfillmentModalOpen.value = true;
};

const saveFulfillment = () => {
  if (selectedOrder.value && trackingNumberInput.value) {
    adminStore.attachTracking(
      selectedOrder.value.id,
      trackingNumberInput.value,
      carrierInput.value,
    );
    adminStore.markPackingSlipPrinted(selectedOrder.value.id);
    isFulfillmentModalOpen.value = false;
  }
};

const openRefund = (order: AdminOrder) => {
  selectedOrder.value = order;
  refundAmountInput.value = order.total;
  isFullRefund.value = true;
  isRefundModalOpen.value = true;
};

const confirmRefund = () => {
  if (selectedOrder.value) {
    adminStore.processRefund(
      selectedOrder.value.id,
      refundAmountInput.value,
      isFullRefund.value,
    );
    isRefundModalOpen.value = false;
  }
};

const openInvoice = (order: AdminOrder) => {
  selectedOrder.value = order;
  isInvoiceModalOpen.value = true;
};

const printDocument = () => {
  if (selectedOrder.value) {
    adminStore.markPackingSlipPrinted(selectedOrder.value.id);
  }
  document.body.classList.add("printing-invoice");
  window.print();
  setTimeout(() => {
    document.body.classList.remove("printing-invoice");
  }, 1000);
};

if (typeof window !== "undefined") {
  window.addEventListener("afterprint", () => {
    document.body.classList.remove("printing-invoice");
  });
}

const getStatusBadge = (status: OrderStatus) => {
  switch (status) {
    case "Pending":
      return "bg-amber-100 text-amber-800 border-amber-200";
    case "Processing":
      return "bg-blue-100 text-blue-800 border-blue-200";
    case "Shipped":
      return "bg-purple-100 text-purple-800 border-purple-200";
    case "In Transit":
      return "bg-indigo-100 text-indigo-800 border-indigo-200";
    case "Delivered":
      return "bg-emerald-100 text-emerald-800 border-emerald-200";
    case "Completed":
      return "bg-teal-100 text-teal-800 border-teal-200";
    case "Accepted":
      return "bg-indigo-100 text-indigo-800 border-indigo-200";
    case "Canceled":
      return "bg-rose-100 text-rose-800 border-rose-200";
    case "Refunded":
      return "bg-pink-100 text-pink-800 border-pink-200";
    case "Returned":
      return "bg-orange-100 text-orange-800 border-orange-200";
    default:
      return "bg-slate-100 text-slate-800";
  }
};

// Pending Status Confirmation State
const pendingStatusChanges = ref<Record<string, OrderStatus>>({});
const updatingStatusOrderId = ref<string | null>(null);

const onStatusSelectChange = (
  orderId: string,
  currentStatus: OrderStatus,
  newStatus: OrderStatus,
) => {
  if (newStatus === currentStatus) {
    delete pendingStatusChanges.value[orderId];
  } else {
    pendingStatusChanges.value[orderId] = newStatus;
  }
};

const confirmStatusChange = async (orderId: string) => {
  const newStatus = pendingStatusChanges.value[orderId];
  if (!newStatus) return;
  updatingStatusOrderId.value = orderId;
  try {
    await adminStore.updateOrderStatus(orderId, newStatus);
    delete pendingStatusChanges.value[orderId];
  } finally {
    updatingStatusOrderId.value = null;
  }
};

const cancelStatusChange = (orderId: string) => {
  delete pendingStatusChanges.value[orderId];
};
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div
      class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs"
    >
      <div>
        <h1
          class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight"
        >
          Order Lifecycle &amp; Fulfillment
        </h1>
        <p class="text-xs text-slate-500">
          Track statuses, print packing slips, attach carrier tracking, issue
          refunds, and generate PDF invoices.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <span
          class="text-xs font-bold text-slate-500 bg-slate-100 px-3 py-1.5 rounded-xl border border-slate-200"
        >
          Total: {{ adminStore.orders.length }} Orders
        </span>
      </div>
    </div>

    <!-- Filters & Search Bar -->
    <div
      class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4"
    >
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
      <div
        class="flex items-center gap-1 overflow-x-auto w-full md:w-auto p-1 bg-slate-100 rounded-xl border border-slate-200"
      >
        <button
          v-for="st in [
            'All',
            'Pending',
            'Processing',
            'Shipped',
            'In Transit',
            'Delivered',
            'Completed',
            'Accepted',
            'Canceled',
            'Refunded',
            'Returned',
          ] as const"
          :key="st"
          type="button"
          class="px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all cursor-pointer"
          :class="
            statusFilter === st
              ? 'bg-white text-slate-900 shadow-xs'
              : 'text-slate-500 hover:text-slate-800'
          "
          @click="statusFilter = st"
        >
          {{ st }}
        </button>
      </div>
    </div>

    <!-- Orders Table -->
    <div
      class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden"
    >
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr
              class="bg-slate-50 border-b border-slate-200/80 text-slate-500 uppercase font-bold text-[10px] tracking-wider"
            >
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
                <span class="font-mono font-bold text-slate-900 text-xs">{{
                  order.id
                }}</span>
                <p class="text-[11px] text-slate-400 mt-0.5">
                  {{ order.createdAt }}
                </p>
              </td>

              <!-- Customer -->
              <td class="p-4">
                <span class="font-bold text-slate-900 block">{{
                  order.customerName
                }}</span>
                <span class="text-[11px] text-slate-500 block"
                  >{{ order.city }} &bull; {{ order.customerPhone }}</span
                >
              </td>

              <!-- Items -->
              <td class="p-4">
                <button
                  type="button"
                  class="group flex items-center gap-2.5 p-2 -m-2 rounded-2xl hover:bg-slate-100/90 border border-transparent hover:border-slate-200 transition-all text-left cursor-pointer focus:outline-none focus:ring-2 focus:ring-rose-500/30"
                  title="Click to view detailed items order modal"
                  @click="openItemsDetail(order)"
                >
                  <div
                    class="flex items-center -space-x-2 overflow-hidden py-0.5"
                  >
                    <div
                      v-for="item in order.items.slice(0, 3)"
                      :key="item.id"
                      class="w-9 h-9 rounded-xl bg-slate-950 border-2 border-white shadow-2xs flex items-center justify-center p-1 relative overflow-hidden group-hover:scale-105 transition-transform"
                    >
                      <img
                        :src="item.imageUrl"
                        :alt="item.name"
                        class="max-w-full max-h-full object-contain"
                        :title="`${item.name} (x${item.quantity})`"
                      />
                    </div>
                    <div
                      v-if="order.items.length > 3"
                      class="w-9 h-9 rounded-xl bg-slate-800 text-white font-bold text-[10px] border-2 border-white shadow-2xs flex items-center justify-center"
                    >
                      +{{ order.items.length - 3 }}
                    </div>
                  </div>

                  <div class="flex flex-col min-w-0">
                    <span
                      class="text-xs font-bold text-slate-900 group-hover:text-rose-600 transition-colors flex items-center gap-1"
                    >
                      <span
                        >{{
                          order.items.reduce((s, i) => s + i.quantity, 0)
                        }}
                        item(s)</span
                      >
                      <span
                        class="text-[10px] opacity-0 group-hover:opacity-100 transition-opacity"
                        >🔍</span
                      >
                    </span>
                    <span
                      class="text-[10px] font-semibold text-rose-500 group-hover:underline"
                    >
                      View items &rarr;
                    </span>
                  </div>
                </button>
              </td>

              <!-- Total & Payment -->
              <td class="p-4">
                <span class="font-extrabold text-slate-900 block">{{
                  formatCurrency(order.total)
                }}</span>
                <span class="text-[10px] font-bold text-slate-500 uppercase">{{
                  order.paymentMethod
                }}</span>
                <span
                  v-if="order.refundStatus && order.refundStatus !== 'None'"
                  class="block text-[10px] text-rose-600 font-bold"
                >
                  Refunded {{ formatCurrency(order.refundAmount || 0) }}
                </span>
              </td>

              <!-- Status Dropdown Selector with Check & Ex Confirmation -->
              <td class="p-4 whitespace-nowrap">
                <div class="flex items-center gap-1.5">
                  <select
                    :value="pendingStatusChanges[order.id] || order.status"
                    class="text-xs font-bold px-2.5 py-1 rounded-full border cursor-pointer focus:outline-none transition-all"
                    :class="[
                      getStatusBadge(pendingStatusChanges[order.id] || order.status),
                      pendingStatusChanges[order.id] ? 'ring-2 ring-amber-400 border-amber-400 shadow-xs' : ''
                    ]"
                    @change="
                      (e) =>
                        onStatusSelectChange(
                          order.id,
                          order.status,
                          (e.target as HTMLSelectElement).value as OrderStatus,
                        )
                    "
                  >
                    <option value="Pending">⏳ Pending</option>
                    <option value="Processing">⚙️ Processing</option>
                    <option value="Shipped">📦 Shipped</option>
                    <option value="In Transit">🚚 In Transit</option>
                    <option value="Delivered">✓ Delivered</option>
                    <option value="Completed">★ Completed</option>
                    <option value="Accepted">🤝 Accepted</option>
                    <option value="Canceled">✕ Canceled</option>
                    <option value="Refunded">💸 Refunded</option>
                    <option value="Returned">↩️ Returned</option>
                  </select>

                  <!-- Confirmation Controls (Check & Ex) -->
                  <div
                    v-if="pendingStatusChanges[order.id]"
                    class="flex items-center gap-1 animate-fadeIn shrink-0"
                  >
                    <button
                      type="button"
                      title="Confirm status change"
                      class="w-6 h-6 rounded-full bg-emerald-500 hover:bg-emerald-600 active:scale-95 text-white flex items-center justify-center text-xs font-black shadow-sm transition-all cursor-pointer disabled:opacity-50"
                      :disabled="updatingStatusOrderId === order.id"
                      @click="confirmStatusChange(order.id)"
                    >
                      <span
                        v-if="updatingStatusOrderId === order.id"
                        class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"
                      ></span>
                      <span v-else>✓</span>
                    </button>
                    <button
                      type="button"
                      title="Cancel and revert"
                      class="w-6 h-6 rounded-full bg-rose-500 hover:bg-rose-600 active:scale-95 text-white flex items-center justify-center text-[11px] font-black shadow-sm transition-all cursor-pointer disabled:opacity-50"
                      :disabled="updatingStatusOrderId === order.id"
                      @click="cancelStatusChange(order.id)"
                    >
                      ✕
                    </button>
                  </div>
                </div>
              </td>

              <!-- Fulfillment / Tracking -->
              <td class="p-4">
                <div v-if="order.trackingNumber" class="space-y-0.5">
                  <span class="text-[10px] font-bold text-slate-400 block">{{
                    order.carrier
                  }}</span>
                  <span
                    class="font-mono text-xs font-semibold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200 inline-block"
                  >
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
                    class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 transition-colors cursor-pointer"
                    title="View Items Ordered Details (Modal)"
                    @click="openItemsDetail(order)"
                  >
                    👁️
                  </button>
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
      <div
        v-if="isFulfillmentModalOpen && selectedOrder"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4"
      >
        <div
          class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-5 shadow-2xl border border-slate-200 font-display"
        >
          <div
            class="flex items-center justify-between pb-3 border-b border-slate-100"
          >
            <div>
              <h3 class="text-base font-bold text-slate-900">
                Fulfillment Tools &bull; {{ selectedOrder.id }}
              </h3>
              <p class="text-xs text-slate-500">
                Generate carrier shipping label and print packing slip
              </p>
            </div>
            <button
              type="button"
              class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center cursor-pointer"
              @click="isFulfillmentModalOpen = false"
            >
              ✕
            </button>
          </div>

          <!-- Carrier & Tracking inputs -->
          <div class="space-y-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1"
                >Select Shipping Carrier</label
              >
              <select
                v-model="carrierInput"
                class="w-full text-xs p-2.5 rounded-xl border border-slate-200 bg-white"
              >
                <template
                  v-if="
                    adminStore.shippingCarriers &&
                    adminStore.shippingCarriers.length > 0
                  "
                >
                  <option
                    v-for="c in adminStore.shippingCarriers"
                    :key="c.id"
                    :value="c.name"
                  >
                    {{ c.name }}
                  </option>
                </template>
                <template v-else>
                  <option value="J&T Express Philippines">
                    J&T Express Philippines
                  </option>
                  <option value="LBC Express (Door to Door)">
                    LBC Express (Door to Door)
                  </option>
                  <option value="Ninja Van Standard">Ninja Van Standard</option>
                  <option value="Flash Express Priority">
                    Flash Express Priority
                  </option>
                  <option value="DHL Express International">
                    DHL Express International
                  </option>
                </template>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1"
                >Carrier Tracking Number</label
              >
              <input
                v-model="trackingNumberInput"
                type="text"
                placeholder="e.g. PH-JT-98234812"
                class="w-full text-xs font-mono p-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900"
              />
            </div>

            <!-- Simulated Barcode Shipping Label Preview -->
            <div
              class="p-4 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 space-y-2 text-center"
            >
              <div
                class="flex items-center justify-between text-[11px] font-bold text-slate-600"
              >
                <span>{{ carrierInput }}</span>
                <span>AIR COURIER WAYBILL</span>
              </div>
              <div
                class="font-mono text-2xl tracking-widest text-slate-900 py-2 select-none"
              >
                ||| | |||| | ||| |||| | ||
              </div>
              <p class="font-mono text-xs font-bold text-slate-800">
                {{ trackingNumberInput }}
              </p>
              <div
                class="text-[10px] text-slate-500 text-left pt-2 border-t border-slate-200"
              >
                <p>
                  <span class="font-bold">Ship To:</span>
                  {{ selectedOrder.customerName }}
                </p>
                <p>
                  <span class="font-bold">Address:</span>
                  {{ selectedOrder.shippingAddress }},
                  {{ selectedOrder.city }} ({{ selectedOrder.postalCode }})
                </p>
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
      <div
        v-if="isRefundModalOpen && selectedOrder"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4"
      >
        <div
          class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-slate-200 font-display"
        >
          <div
            class="flex items-center justify-between pb-3 border-b border-slate-100"
          >
            <div>
              <h3 class="text-base font-bold text-slate-900">
                Process Return / Refund
              </h3>
              <p class="text-xs text-slate-500">
                Order {{ selectedOrder.id }} &bull; Paid
                {{ formatCurrency(selectedOrder.total) }}
              </p>
            </div>
            <button
              type="button"
              class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center cursor-pointer"
              @click="isRefundModalOpen = false"
            >
              ✕
            </button>
          </div>

          <div class="space-y-3">
            <div class="flex gap-4 text-xs font-bold">
              <label class="flex items-center gap-1.5 cursor-pointer">
                <input
                  v-model="isFullRefund"
                  type="radio"
                  :value="true"
                  class="accent-rose-600"
                />
                <span>Full Refund</span>
              </label>
              <label class="flex items-center gap-1.5 cursor-pointer">
                <input
                  v-model="isFullRefund"
                  type="radio"
                  :value="false"
                  class="accent-rose-600"
                />
                <span>Partial Refund</span>
              </label>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1"
                >Refund Amount (PHP)</label
              >
              <input
                v-model.number="refundAmountInput"
                type="number"
                :max="selectedOrder.total"
                class="w-full text-xs p-2.5 rounded-xl border border-slate-200 font-bold focus:outline-none focus:border-slate-900"
              />
            </div>

            <label
              class="flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer pt-1"
            >
              <input
                v-model="restockInventory"
                type="checkbox"
                class="accent-rose-600 rounded"
              />
              <span
                >Automatically return items back into available inventory</span
              >
            </label>
          </div>

          <div class="flex gap-3 pt-3">
            <button
              type="button"
              class="flex-1 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs"
              @click="isRefundModalOpen = false"
            >
              Cancel
            </button>
            <button
              type="button"
              class="flex-1 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs"
              @click="confirmRefund"
            >
              Confirm &amp; Issue Refund
            </button>
          </div>
        </div>
      </div>
    </teleport>

    <!-- 3. Printable PDF Invoice Modal -->
    <teleport to="body">
      <div
        v-if="isInvoiceModalOpen && selectedOrder"
        class="invoice-modal-overlay fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 print:p-0 print:static print:bg-white print:backdrop-blur-none print:z-auto print:block print:inset-auto"
      >
        <div
          id="printable-invoice"
          class="invoice-modal-card bg-white rounded-3xl max-w-xl w-full p-8 space-y-6 shadow-2xl border border-slate-200 font-display max-h-[90vh] overflow-y-auto print:max-w-none print:w-full print:rounded-none print:shadow-none print:border-none print:p-0 print:max-h-none print:overflow-visible print:space-y-6"
        >
          <!-- Invoice Header -->
          <div
            class="flex items-start justify-between pb-4 border-b-2 border-slate-800 print:border-slate-800"
          >
            <div class="flex items-center gap-3">
              <img
                src="/logo.png"
                alt="RLG Online Shop Logo"
                class="h-12 w-auto object-contain print:h-14 drop-shadow-sm"
              />
              <div>
                <h2 class="text-xl font-black text-slate-900 tracking-tight">
                  RLG <span class="text-rose-600">ONLINE SHOP</span>
                </h2>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                  Official Commercial Tax Invoice
                </p>
                <p class="text-[10px] text-slate-400 print:text-slate-500">
                  Authentic Japanese Imports Vault &bull; Metro Manila, Philippines
                </p>
              </div>
            </div>
            <div class="text-right">
              <span
                class="inline-block text-[10px] font-black uppercase px-2.5 py-0.5 rounded bg-slate-900 text-white print:bg-slate-900 print:text-white mb-1"
              >
                Tax Invoice
              </span>
              <p class="font-mono text-base font-extrabold text-slate-900 tracking-tight">
                {{ selectedOrder.invoiceId || 'INV-2026-001' }}
              </p>
              <p class="text-xs text-slate-500 font-medium">
                {{ selectedOrder.createdAt }}
              </p>
              <p class="text-[11px] text-slate-400 font-mono">
                Order Ref: #{{ selectedOrder.id }}
              </p>
            </div>
          </div>

          <!-- Billed / Shipped & Payment Grid -->
          <div
            class="grid grid-cols-2 gap-6 text-xs text-slate-700 bg-slate-50/80 p-4 rounded-2xl border border-slate-200/80 print:bg-slate-50 print:border-slate-300"
          >
            <div>
              <h4 class="font-black text-slate-900 uppercase tracking-wider text-[10px] mb-1.5 flex items-center gap-1.5">
                <span>📦</span>
                <span>Billed &amp; Shipped To:</span>
              </h4>
              <p class="font-bold text-sm text-slate-900">
                {{ selectedOrder.customerName || 'Valued Collector' }}
              </p>
              <p class="text-slate-600 leading-relaxed mt-0.5">
                {{ selectedOrder.shippingAddress || 'Registered Shipping Address' }}
              </p>
              <p class="text-slate-600 font-medium">
                {{ selectedOrder.city ? `${selectedOrder.city}, ` : '' }}{{ selectedOrder.postalCode || 'Philippines' }}
              </p>
              <p class="text-slate-500 font-mono text-[11px] mt-1">
                {{ selectedOrder.customerEmail }}
              </p>
            </div>

            <div class="text-right flex flex-col justify-between">
              <div>
                <h4 class="font-black text-slate-900 uppercase tracking-wider text-[10px] mb-1.5">
                  Payment Information:
                </h4>
                <p class="text-slate-700 font-semibold">
                  <span class="text-slate-500 font-normal">Method:</span>
                  {{ selectedOrder.paymentMethod }}
                </p>
                <div class="mt-1 flex items-center justify-end gap-1.5">
                  <span
                    class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 print:bg-emerald-100 print:text-emerald-900"
                  >
                    ✓ Paid &bull; Verified
                  </span>
                </div>
              </div>
              <p class="text-[11px] text-slate-500 font-mono mt-2">
                Currency: <strong>Philippine Peso (PHP ₱)</strong>
              </p>
            </div>
          </div>

          <!-- Items Table -->
          <div class="overflow-hidden rounded-xl border border-slate-200 print:border-slate-300">
            <table class="w-full text-xs text-left border-collapse">
              <thead>
                <tr
                  class="bg-slate-100 text-slate-800 font-black uppercase text-[10px] tracking-wider border-b border-slate-200 print:bg-slate-100"
                >
                  <th class="p-3">Item Description</th>
                  <th class="p-3 text-center">Qty</th>
                  <th class="p-3 text-right">Unit Price</th>
                  <th class="p-3 text-right">Subtotal</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 print:divide-slate-200">
                <tr v-for="item in selectedOrder.items" :key="item.id" class="hover:bg-slate-50/50">
                  <td class="p-3">
                    <p class="font-bold text-slate-900 text-xs">{{ item.name }}</p>
                    <p class="text-[10px] text-slate-500 font-mono">
                      SKU: {{ item.sku }}
                    </p>
                  </td>
                  <td class="p-3 text-center font-bold text-slate-800">
                    {{ item.quantity }}
                  </td>
                  <td class="p-3 text-right font-mono text-slate-700">
                    {{ formatCurrency(item.price) }}
                  </td>
                  <td class="p-3 text-right font-mono font-bold text-slate-900">
                    {{ formatCurrency(item.price * item.quantity) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Totals -->
          <div class="pt-2 space-y-2 text-xs text-slate-600">
            <div class="flex justify-between py-1 border-b border-slate-100 print:border-slate-200">
              <span class="text-slate-500">Items Subtotal</span>
              <span class="font-mono font-bold text-slate-800">
                {{ formatCurrency(selectedOrder.total) }}
              </span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-100 print:border-slate-200 text-[11px]">
              <span class="text-slate-500">Shipping Armor &amp; Logistics (Courier Delivery)</span>
              <span class="font-bold text-emerald-600 uppercase tracking-wide">Included / Free</span>
            </div>
            <div
              class="flex justify-between font-black text-lg text-slate-900 pt-2 border-t-2 border-slate-800 print:border-slate-800"
            >
              <span>Grand Total</span>
              <span class="text-rose-600 font-mono font-black text-xl">
                {{ formatCurrency(selectedOrder.total) }}
              </span>
            </div>
          </div>

          <!-- Official Document Footer Notice -->
          <div
            class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-[10px] text-slate-400 print:text-slate-600"
          >
            <div class="space-y-0.5">
              <p class="font-bold text-slate-700">
                Authenticity Guarantee: 100% Genuine Japanese Collector Imports
              </p>
              <p>
                Thank you for your order with RLG Online Shop! For inquiries or support, contact support@rlghobby.com.
              </p>
            </div>
            <div class="text-right font-mono text-[9px] text-slate-400 shrink-0">
              <div class="tracking-widest font-bold">||||| | |||| ||| | ||||| ||</div>
              <span>{{ selectedOrder.invoiceId || 'INV-2026-001' }}</span>
            </div>
          </div>

          <!-- Action Buttons (Hidden when printing) -->
          <div class="flex gap-3 pt-3 border-t border-slate-100 print:hidden">
            <button
              type="button"
              class="flex-1 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors cursor-pointer"
              @click="isInvoiceModalOpen = false"
            >
              Close
            </button>
            <button
              type="button"
              class="flex-1 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center justify-center gap-1.5 transition-colors cursor-pointer shadow-sm"
              @click="printDocument"
            >
              <span>🖨️ Download / Print PDF</span>
            </button>
          </div>
        </div>
      </div>
    </teleport>

    <!-- Beautiful Order Items Detail Modal (Based on customer_orders & customer_order_items) -->
    <OrderItemsDetailModal
      :is-open="isItemsDetailModalOpen"
      :order="selectedOrder"
      @close="isItemsDetailModalOpen = false"
      @print-invoice="openInvoice"
      @open-fulfillment="openFulfillment"
    />
  </div>
</template>

<style>
@media print {
  @page {
    size: A4 portrait;
    margin: 12mm 15mm;
  }

  /* Force background colors and crisp borders */
  * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  body {
    background-color: #ffffff !important;
    color: #0f172a !important;
    margin: 0 !important;
    padding: 0 !important;
  }

  /* When printing invoice, hide root dashboard application */
  body.printing-invoice #app,
  body:has(#printable-invoice) #app {
    display: none !important;
  }

  /* Invoice modal overlay becomes a static clean page container */
  .invoice-modal-overlay {
    position: static !important;
    background: transparent !important;
    backdrop-filter: none !important;
    padding: 0 !important;
    margin: 0 !important;
    display: block !important;
    width: 100% !important;
    height: auto !important;
  }

  /* Invoice card expands to full A4 page width cleanly without card borders or shadows */
  .invoice-modal-card {
    max-width: 100% !important;
    width: 100% !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    border: none !important;
    padding: 0 !important;
    margin: 0 !important;
    max-height: none !important;
    overflow: visible !important;
  }
}
</style>
