<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import type { AdminOrder, OrderStatus } from "../admin.types";
import { formatCurrency } from "@/shared/utils/currency.util";

const props = defineProps<{
  isOpen: boolean;
  order: AdminOrder | null;
}>();

const emit = defineEmits<{
  (e: "close"): void;
  (e: "print-invoice", order: AdminOrder): void;
  (e: "open-fulfillment", order: AdminOrder): void;
}>();

const isCopied = ref(false);

const copyOrderNumber = async () => {
  if (!props.order) return;
  try {
    await navigator.clipboard.writeText(props.order.id);
    isCopied.value = true;
    setTimeout(() => {
      isCopied.value = false;
    }, 2000);
  } catch (e) {
    console.error("Failed to copy order number", e);
  }
};

const handleKeyDown = (e: KeyboardEvent) => {
  if (e.key === "Escape" && props.isOpen) {
    emit("close");
  }
};

onMounted(() => {
  window.addEventListener("keydown", handleKeyDown);
});

onUnmounted(() => {
  window.removeEventListener("keydown", handleKeyDown);
});

const totalItemCount = computed(() => {
  if (!props.order || !props.order.items) return 0;
  return props.order.items.reduce((sum, item) => sum + (item.quantity || 1), 0);
});

const getStatusBadgeClass = (status: OrderStatus) => {
  switch (status) {
    case "Pending":
      return "bg-amber-100 text-amber-900 border-amber-300 ring-1 ring-amber-200";
    case "Processing":
      return "bg-blue-100 text-blue-900 border-blue-300 ring-1 ring-blue-200";
    case "Shipped":
      return "bg-indigo-100 text-indigo-900 border-indigo-300 ring-1 ring-indigo-200";
    case "Delivered":
      return "bg-emerald-100 text-emerald-900 border-emerald-300 ring-1 ring-emerald-200";
    case "Accepted":
      return "bg-teal-100 text-teal-900 border-teal-300 ring-1 ring-teal-200";
    case "Canceled":
      return "bg-rose-100 text-rose-900 border-rose-300 ring-1 ring-rose-200";
    case "Refunded":
      return "bg-fuchsia-100 text-fuchsia-900 border-fuchsia-300 ring-1 ring-fuchsia-200";
    case "Returned":
      return "bg-orange-100 text-orange-900 border-orange-300 ring-1 ring-orange-200";
    default:
      return "bg-slate-100 text-slate-800 border-slate-300";
  }
};

const getStatusIcon = (status: OrderStatus) => {
  switch (status) {
    case "Pending":
      return "⏳";
    case "Processing":
      return "⚙️";
    case "Shipped":
      return "🚚";
    case "Delivered":
      return "✅";
    case "Accepted":
      return "👍";
    case "Canceled":
      return "🚫";
    case "Refunded":
      return "💸";
    case "Returned":
      return "↩️";
    default:
      return "📦";
  }
};

const getCustomerInitials = (name: string) => {
  if (!name) return "CU";
  return name
    .split(" ")
    .map((part) => part[0])
    .slice(0, 2)
    .join("")
    .toUpperCase();
};
</script>

<template>
  <!-- Backdrop Transition -->
  <Transition
    enter-active-class="transition duration-200 ease-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition duration-150 ease-in"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="isOpen && order"
      class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-5 md:p-6"
      @click.self="emit('close')"
    >
      <!-- Modal Container -->
      <div
        class="bg-white rounded-3xl shadow-2xl border border-slate-200/80 max-w-4xl w-full overflow-hidden flex flex-col max-h-[92vh] animate-in fade-in zoom-in-95 duration-200"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-order-title"
      >
        <!-- Top Gradient Accent Strip -->
        <div
          class="h-2 w-full bg-gradient-to-r from-rose-500 via-indigo-600 to-amber-400"
        ></div>

        <!-- Header -->
        <div
          class="px-6 py-5 border-b border-slate-100 flex items-start justify-between gap-4 bg-slate-50/70"
        >
          <div class="space-y-1.5">
            <div class="flex items-center gap-2.5 flex-wrap">
              <span
                class="inline-flex items-center gap-1.5 text-xs font-black uppercase tracking-wider px-2.5 py-1 rounded-lg bg-slate-900 text-white shadow-xs"
              >
                <span>📦</span>
                <span>Customer Order</span>
              </span>

              <!-- Order Number with Copy action -->
              <button
                type="button"
                class="inline-flex items-center gap-1.5 font-mono font-black text-slate-900 text-sm px-2.5 py-1 rounded-lg bg-white border border-slate-200 shadow-2xs hover:border-slate-400 transition-colors cursor-pointer"
                title="Click to copy order number"
                @click="copyOrderNumber"
              >
                <span>{{ order.id }}</span>
                <span class="text-xs text-slate-400 hover:text-slate-600">
                  {{ isCopied ? "✓ Copied!" : "📋" }}
                </span>
              </button>

              <!-- Order Status Badge -->
              <span
                class="inline-flex items-center gap-1 text-xs font-extrabold px-3 py-1 rounded-full border shadow-2xs"
                :class="getStatusBadgeClass(order.status)"
              >
                <span>{{ getStatusIcon(order.status) }}</span>
                <span>{{ order.statusLabel || order.status }}</span>
              </span>

              <!-- Payment Status Badge -->
              <span
                class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full"
                :class="
                  order.paymentStatus === 'Paid'
                    ? 'bg-emerald-100 text-emerald-800 border border-emerald-200'
                    : order.paymentStatus === 'Refunded'
                      ? 'bg-rose-100 text-rose-800 border border-rose-200'
                      : 'bg-amber-100 text-amber-800 border border-amber-200'
                "
              >
                <span>{{ order.paymentStatus === "Paid" ? "●" : "○" }}</span>
                <span
                  >{{ order.paymentStatus || "Paid" }} via
                  {{ order.paymentMethod }}</span
                >
              </span>
            </div>

            <!-- Date & Database ID Sub-info -->
            <p class="text-xs text-slate-500 flex items-center gap-2">
              <span
                >📅 Ordered on
                <strong class="text-slate-700">{{
                  order.createdAt
                }}</strong></span
              >
              <span v-if="order.backendId" class="text-slate-300">&bull;</span>
              <span
                v-if="order.backendId"
                class="font-mono text-[11px] text-slate-400"
              >
                DB Record: #{{ order.backendId }} (customer_orders)
              </span>
            </p>
          </div>

          <!-- Close Modal Button -->
          <button
            type="button"
            class="w-9 h-9 rounded-2xl bg-white hover:bg-slate-200 border border-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center font-bold text-sm cursor-pointer transition-colors shadow-2xs flex-shrink-0"
            title="Close dialog (Esc)"
            @click="emit('close')"
          >
            ✕
          </button>
        </div>

        <!-- Scrollable Modal Body -->
        <div class="p-6 overflow-y-auto space-y-6 flex-1 bg-white">
          <!-- 1. Customer & Shipping Destination Cards -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
            <!-- Customer Profile -->
            <div
              class="p-4 rounded-2xl bg-slate-50/90 border border-slate-200/80 space-y-2.5"
            >
              <div
                class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-wider"
              >
                <span>👤</span>
                <span>Customer Profile</span>
              </div>
              <div class="flex items-center gap-3">
                <div
                  class="w-10 h-10 rounded-full bg-gradient-to-tr from-rose-600 to-indigo-600 text-white font-extrabold flex items-center justify-center text-sm shadow-xs flex-shrink-0"
                >
                  {{ getCustomerInitials(order.customerName) }}
                </div>
                <div class="min-w-0">
                  <h4 class="text-xs font-bold text-slate-900 truncate">
                    {{ order.customerName }}
                  </h4>
                  <span
                    class="inline-block text-[10px] font-semibold px-1.5 py-0.5 rounded bg-slate-200 text-slate-700"
                  >
                    {{ order.customerProfile?.segment || "Store Buyer" }}
                  </span>
                </div>
              </div>
              <div
                class="space-y-1 text-xs text-slate-600 pt-1 border-t border-slate-200/60 font-medium"
              >
                <p
                  v-if="order.customerEmail"
                  class="truncate flex items-center gap-1.5 text-slate-500"
                >
                  <span>✉️</span>
                  <a
                    :href="`mailto:${order.customerEmail}`"
                    class="hover:text-rose-600 hover:underline"
                  >
                    {{ order.customerEmail }}
                  </a>
                </p>
                <p
                  v-if="order.customerPhone"
                  class="truncate flex items-center gap-1.5 text-slate-500"
                >
                  <span>📞</span>
                  <span>{{ order.customerPhone }}</span>
                </p>
              </div>
            </div>

            <!-- Shipping Destination -->
            <div
              class="p-4 rounded-2xl bg-slate-50/90 border border-slate-200/80 space-y-2"
            >
              <div
                class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-wider"
              >
                <span>📍</span>
                <span>Shipping Destination</span>
              </div>
              <div class="text-xs space-y-1">
                <p class="font-bold text-slate-900 leading-snug">
                  {{ order.shippingAddress || "No street address provided" }}
                </p>
                <p class="text-slate-500 font-medium">
                  {{ order.city
                  }}<span v-if="order.postalCode"
                    >, {{ order.postalCode }}</span
                  >
                </p>
                <p class="text-[11px] text-slate-400 font-semibold">
                  🇵🇭 Philippines Delivery
                </p>
              </div>
            </div>

            <!-- Logistics & Fulfillment -->
            <div
              class="p-4 rounded-2xl bg-slate-50/90 border border-slate-200/80 space-y-2"
            >
              <div class="flex items-center justify-between">
                <div
                  class="flex items-center gap-1.5 text-xs font-bold text-slate-500 uppercase tracking-wider"
                >
                  <span>🚚</span>
                  <span>Fulfillment</span>
                </div>
                <span
                  class="text-[10px] font-bold px-2 py-0.5 rounded-md"
                  :class="
                    order.packingSlipPrinted
                      ? 'bg-emerald-100 text-emerald-800'
                      : 'bg-slate-200 text-slate-700'
                  "
                >
                  {{
                    order.packingSlipPrinted ? "Slip Printed ✓" : "Unprinted"
                  }}
                </span>
              </div>
              <div class="text-xs space-y-1.5">
                <div>
                  <span class="text-[11px] text-slate-400 block font-medium"
                    >Carrier</span
                  >
                  <span class="font-bold text-slate-900">
                    {{ order.carrier || "Standard Hobby Courier" }}
                  </span>
                </div>
                <div>
                  <span class="text-[11px] text-slate-400 block font-medium"
                    >Tracking Number</span
                  >
                  <span
                    v-if="order.trackingNumber"
                    class="font-mono text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100 inline-block"
                  >
                    {{ order.trackingNumber }}
                  </span>
                  <span v-else class="text-slate-400 italic text-[11px]">
                    Not dispatched yet
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- 2. Ordered Items List (Based on customer_order_items table) -->
          <div class="space-y-3">
            <div
              class="flex items-center justify-between border-b border-slate-100 pb-2"
            >
              <div class="flex items-center gap-2">
                <h3
                  class="text-sm font-extrabold text-slate-900 uppercase tracking-wider flex items-center gap-1.5"
                >
                  <span>🛍️</span>
                  <span>Items Ordered</span>
                </h3>
                <span
                  class="text-xs font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 border border-rose-200"
                >
                  {{ order.items.length }} unique line item{{
                    order.items.length === 1 ? "" : "s"
                  }}
                  ({{ totalItemCount }} total units)
                </span>
              </div>
              <span class="text-xs text-slate-400 font-mono">
                table: customer_order_items
              </span>
            </div>

            <!-- Items Table / Cards -->
            <div
              class="divide-y divide-slate-100 border border-slate-200/80 rounded-2xl overflow-hidden bg-white shadow-xs"
            >
              <div
                v-for="(item, index) in order.items"
                :key="item.id || index"
                class="p-4 hover:bg-slate-50/80 transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-4"
              >
                <!-- Left: Thumbnail & Product Specs -->
                <div class="flex items-center gap-3.5 min-w-0">
                  <!-- Product Image (Uncropped object-contain) -->
                  <div
                    class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-center p-1.5 flex-shrink-0 relative overflow-hidden shadow-2xs"
                  >
                    <img
                      :src="item.imageUrl"
                      :alt="item.name"
                      class="max-w-full max-h-full object-contain"
                      loading="lazy"
                    />
                    <span
                      class="absolute bottom-1 right-1 bg-slate-900/90 text-[9px] font-bold text-white px-1.5 py-0.2 rounded border border-slate-700"
                    >
                      ×{{ item.quantity }}
                    </span>
                  </div>

                  <!-- Details -->
                  <div class="min-w-0 space-y-1">
                    <h4
                      class="text-xs sm:text-sm font-bold text-slate-900 leading-snug line-clamp-2"
                    >
                      {{ item.name }}
                    </h4>

                    <!-- SKU & Metadata Badges -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                      <span
                        v-if="item.sku"
                        class="font-mono text-[10px] font-extrabold px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 border border-slate-200"
                      >
                        SKU: {{ item.sku }}
                      </span>
                      <span
                        v-if="item.category"
                        class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100"
                      >
                        {{ item.category }}
                      </span>
                      <span
                        v-if="item.brand"
                        class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-100"
                      >
                        {{ item.brand }}
                      </span>
                      <span
                        v-if="item.condition"
                        class="text-[10px] font-medium px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-100"
                      >
                        {{ item.condition }}
                      </span>
                    </div>

                    <p class="text-xs text-slate-500 font-medium">
                      Unit Price:
                      <span class="font-bold text-slate-800">{{
                        formatCurrency(item.price)
                      }}</span>
                    </p>
                  </div>
                </div>

                <!-- Right: Quantity, Calculation & Subtotal -->
                <div
                  class="flex sm:flex-col items-center sm:items-end justify-between sm:justify-center border-t sm:border-t-0 pt-2 sm:pt-0 border-slate-100 flex-shrink-0"
                >
                  <div class="text-[11px] text-slate-400 font-mono">
                    {{ formatCurrency(item.price) }} &times; {{ item.quantity }}
                  </div>
                  <div class="text-sm sm:text-base font-black text-slate-900">
                    {{
                      formatCurrency(
                        item.subtotal || item.price * item.quantity,
                      )
                    }}
                  </div>
                  <span
                    class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100 mt-0.5"
                  >
                    Confirmed
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- 3. Notes & Financial Calculation Breakdown -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
            <!-- Order Notes Card -->
            <div
              class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200/80 space-y-1.5"
            >
              <div
                class="flex items-center gap-1.5 text-xs font-bold text-amber-900 uppercase tracking-wider"
              >
                <span>📝</span>
                <span>Order Instructions &amp; Notes</span>
              </div>
              <p class="text-xs text-amber-800 italic leading-relaxed">
                {{
                  order.notes ||
                  "No special handling instructions provided for this order."
                }}
              </p>
              <div class="text-[11px] text-amber-700/80 pt-1 font-medium">
                Invoice Reference:
                <strong class="font-mono">{{ order.invoiceId }}</strong>
              </div>
            </div>

            <!-- Financial Calculation Summary -->
            <div
              class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2"
            >
              <div
                class="flex items-center justify-between text-xs text-slate-600"
              >
                <span>Items Subtotal:</span>
                <span class="font-mono font-bold text-slate-800">
                  {{
                    formatCurrency(
                      order.items.reduce(
                        (s, i) => s + (i.subtotal || i.price * i.quantity),
                        0,
                      ),
                    )
                  }}
                </span>
              </div>

              <div
                class="flex items-center justify-between text-xs text-slate-600"
              >
                <span class="flex items-center gap-1">
                  <span>Shipping &amp; Handling:</span>
                  <span
                    class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-1.5 py-0.2 rounded"
                    >Hobby Express</span
                  >
                </span>
                <span class="font-mono font-bold text-emerald-600"
                  >₱0.00 (FREE)</span
                >
              </div>

              <div
                v-if="order.refundStatus && order.refundStatus !== 'None'"
                class="flex items-center justify-between text-xs text-rose-600 font-bold"
              >
                <span>Refunded Amount ({{ order.refundStatus }}):</span>
                <span class="font-mono"
                  >-{{ formatCurrency(order.refundAmount || 0) }}</span
                >
              </div>

              <div
                class="pt-2 border-t border-slate-200 flex items-baseline justify-between"
              >
                <div>
                  <span
                    class="text-xs font-black uppercase tracking-wider text-slate-900 block"
                  >
                    Grand Total
                  </span>
                  <span class="text-[10px] text-slate-400">
                    Paid via {{ order.paymentMethod }}
                  </span>
                </div>
                <div
                  class="text-xl sm:text-2xl font-black text-rose-600 tracking-tight font-display"
                >
                  {{ formatCurrency(order.total) }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer Actions -->
        <div
          class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3"
        >
          <div class="text-xs text-slate-400 hidden sm:block">
            <span
              >Press
              <kbd
                class="px-1.5 py-0.5 rounded bg-white border border-slate-300 font-mono text-[10px]"
                >Esc</kbd
              >
              or click outside to dismiss</span
            >
          </div>

          <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
            <!-- Print Invoice / Packing Slip -->
            <button
              type="button"
              class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-800 text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs"
              @click="emit('print-invoice', order)"
            >
              <span>📄</span>
              <span>Print Invoice / Slip</span>
            </button>

            <!-- Dispatch / Tracking (if Pending/Processing) -->
            <button
              v-if="order.status === 'Pending' || order.status === 'Processing'"
              type="button"
              class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-sm shadow-indigo-600/25"
              @click="emit('open-fulfillment', order)"
            >
              <span>🚚</span>
              <span>Manage Fulfillment</span>
            </button>

            <!-- Done / Close -->
            <button
              type="button"
              class="flex-1 sm:flex-none px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition-all cursor-pointer shadow-sm"
              @click="emit('close')"
            >
              Done
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>
