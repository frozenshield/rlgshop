<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useAdminStore } from "../admin.store";
import type { AdminOrder } from "../admin.types";
import { formatCurrency } from "@/shared/utils/currency.util";
import OrderItemsDetailModal from "../components/OrderItemsDetailModal.vue";

const router = useRouter();
const adminStore = useAdminStore();

const selectedOrder = ref<AdminOrder | null>(null);
const isItemsDetailModalOpen = ref(false);

const openItemsDetail = (order: AdminOrder) => {
  selectedOrder.value = order;
  isItemsDetailModalOpen.value = true;
};

onMounted(async () => {
  if (adminStore.orders.length === 0) {
    await adminStore.fetchOrders();
  }
});
</script>

<template>
  <div class="space-y-6">
    <!-- Top Welcome & Timeframe Selector -->
    <div
      class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs"
    >
      <div>
        <div
          class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-50 text-rose-600 border border-rose-100 mb-1"
        >
          <span
            class="w-1.5 h-1.5 rounded-full bg-rose-600 animate-ping"
          ></span>
          Live Store Telemetry
        </div>
        <h1
          class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight"
        >
          Executive Command Center
        </h1>
        <p class="text-xs text-slate-500">
          Real-time overview of revenue, operations, fulfillment, and customer
          traffic.
        </p>
      </div>

      <!-- Timeframe Toggle -->
      <div
        class="flex items-center gap-1 p-1 bg-slate-100 rounded-xl border border-slate-200 self-start sm:self-auto"
      >
        <button
          type="button"
          class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer"
          :class="
            adminStore.dashboardTimeframe === 'today'
              ? 'bg-white text-slate-900 shadow-xs'
              : 'text-slate-500 hover:text-slate-800'
          "
          @click="adminStore.dashboardTimeframe = 'today'"
        >
          Today
        </button>
        <button
          type="button"
          class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer"
          :class="
            adminStore.dashboardTimeframe === 'week'
              ? 'bg-white text-slate-900 shadow-xs'
              : 'text-slate-500 hover:text-slate-800'
          "
          @click="adminStore.dashboardTimeframe = 'week'"
        >
          This Week
        </button>
        <button
          type="button"
          class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer"
          :class="
            adminStore.dashboardTimeframe === 'month'
              ? 'bg-white text-slate-900 shadow-xs'
              : 'text-slate-500 hover:text-slate-800'
          "
          @click="adminStore.dashboardTimeframe = 'month'"
        >
          This Month
        </button>
      </div>
    </div>

    <!-- At-a-Glance Core Metrics (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
      <!-- Total Revenue -->
      <div
        class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3"
      >
        <div class="flex items-center justify-between text-slate-500">
          <span class="text-xs font-bold uppercase tracking-wider"
            >Total Revenue</span
          >
          <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600 text-sm"
            >💰</span
          >
        </div>
        <div class="space-y-0.5">
          <div class="text-2xl font-black text-slate-900">
            {{ formatCurrency(adminStore.metrics.totalRevenue) }}
          </div>
          <p
            class="text-[11px] text-emerald-600 font-bold flex items-center gap-1"
          >
            <span>&uarr; +18.4%</span>
            <span class="text-slate-400 font-normal">vs previous period</span>
          </p>
        </div>
      </div>

      <!-- Orders Count -->
      <div
        class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3"
      >
        <div class="flex items-center justify-between text-slate-500">
          <span class="text-xs font-bold uppercase tracking-wider"
            >Total Orders</span
          >
          <span class="p-2 rounded-xl bg-indigo-50 text-indigo-600 text-sm"
            >📦</span
          >
        </div>
        <div class="space-y-0.5">
          <div class="text-2xl font-black text-slate-900">
            {{ adminStore.metrics.totalOrdersCount }} Orders
          </div>
          <p
            class="text-[11px] text-indigo-600 font-bold flex items-center gap-1"
          >
            <span>&uarr; +12.1%</span>
            <span class="text-slate-400 font-normal">order velocity</span>
          </p>
        </div>
      </div>

      <!-- Average Order Value (AOV) -->
      <div
        class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3"
      >
        <div class="flex items-center justify-between text-slate-500">
          <span class="text-xs font-bold uppercase tracking-wider"
            >Avg Order Value (AOV)</span
          >
          <span class="p-2 rounded-xl bg-rose-50 text-rose-600 text-sm"
            >🏷️</span
          >
        </div>
        <div class="space-y-0.5">
          <div class="text-2xl font-black text-slate-900">
            {{ formatCurrency(adminStore.metrics.averageOrderValue) }}
          </div>
          <p
            class="text-[11px] text-rose-600 font-bold flex items-center gap-1"
          >
            <span>&uarr; +₱420</span>
            <span class="text-slate-400 font-normal">bundle lift</span>
          </p>
        </div>
      </div>

      <!-- Active Visitors -->
      <div
        class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3"
      >
        <div class="flex items-center justify-between text-slate-500">
          <span class="text-xs font-bold uppercase tracking-wider"
            >Active Visitors</span
          >
          <span class="p-2 rounded-xl bg-amber-50 text-amber-600 text-sm"
            >👥</span
          >
        </div>
        <div class="space-y-0.5">
          <div class="text-2xl font-black text-slate-900">
            {{ adminStore.metrics.activeVisitorsToday.toLocaleString() }}
          </div>
          <p
            class="text-[11px] text-emerald-600 font-bold flex items-center gap-1"
          >
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span>124 live on checkout</span>
          </p>
        </div>
      </div>
    </div>

    <!-- Action Items & Priority Alerts Banner (3 Alerts) -->
    <div class="space-y-3">
      <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">
        Action Items &amp; Operational Alerts
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Pending Orders Alert -->
        <div
          class="p-4 rounded-2xl bg-amber-50 border border-amber-200/80 flex items-start gap-3.5 hover:shadow-sm transition-all cursor-pointer"
          @click="router.push('/admin/orders')"
        >
          <div
            class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg flex-shrink-0"
          >
            ⏳
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between">
              <h3 class="text-xs font-bold text-amber-900">Pending Orders</h3>
              <span
                class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-amber-200 text-amber-900"
              >
                {{ adminStore.metrics.pendingOrdersCount }} Needs Review
              </span>
            </div>
            <p class="text-[11px] text-amber-700 mt-0.5 leading-snug">
              {{ adminStore.metrics.pendingOrdersCount }} order(s) awaiting
              packing verification or COD confirmation.
            </p>
          </div>
        </div>

        <!-- Low Stock Warning -->
        <div
          class="p-4 rounded-2xl bg-rose-50 border border-rose-200/80 flex items-start gap-3.5 hover:shadow-sm transition-all cursor-pointer"
          @click="router.push('/admin/inventory')"
        >
          <div
            class="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center text-lg flex-shrink-0"
          >
            ⚠️
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between">
              <h3 class="text-xs font-bold text-rose-900">
                Low Stock Warnings
              </h3>
              <span
                class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-rose-200 text-rose-900"
              >
                {{ adminStore.metrics.lowStockCount }} SKUs Critical
              </span>
            </div>
            <p class="text-[11px] text-rose-700 mt-0.5 leading-snug">
              Key Gunpla kits and TCG 151 ETBs are below safe replenishment
              threshold.
            </p>
          </div>
        </div>

        <!-- Unread Messages -->
        <div
          class="p-4 rounded-2xl bg-indigo-50 border border-indigo-200/80 flex items-start gap-3.5 hover:shadow-sm transition-all cursor-pointer"
          @click="router.push('/admin/customers')"
        >
          <div
            class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-lg flex-shrink-0"
          >
            💬
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between">
              <h3 class="text-xs font-bold text-indigo-900">
                Customer Inquiries
              </h3>
              <span
                class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-indigo-200 text-indigo-900"
              >
                {{ adminStore.metrics.unreadInquiriesCount }} Unread
              </span>
            </div>
            <p class="text-[11px] text-indigo-700 mt-0.5 leading-snug">
              Pre-order questions and tracking inquiries awaiting response in
              CRM inbox.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Top Performers (Best Sellers + Traffic Referrers) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- Best Selling Products (7 cols) -->
      <div
        class="lg:col-span-7 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4"
      >
        <div class="flex items-center justify-between">
          <div>
            <h3
              class="text-sm font-bold text-slate-900 uppercase tracking-wider"
            >
              Top Performing Products
            </h3>
            <p class="text-xs text-slate-500">
              Highest grossing items by units sold and revenue
            </p>
          </div>
          <router-link
            to="/admin/products"
            class="text-xs font-bold text-rose-600 hover:underline"
          >
            Manage Catalog &rarr;
          </router-link>
        </div>

        <div class="divide-y divide-slate-100">
          <div
            v-for="(prod, idx) in adminStore.topProducts"
            :key="idx"
            class="py-3 flex items-center justify-between gap-4"
          >
            <div class="flex items-center gap-3 min-w-0">
              <span
                class="w-6 h-6 rounded-lg bg-slate-100 text-slate-700 font-mono text-xs font-bold flex items-center justify-center"
              >
                #{{ idx + 1 }}
              </span>
              <div class="min-w-0">
                <h4 class="text-xs font-bold text-slate-800 truncate">
                  {{ prod.name }}
                </h4>
                <p class="text-[11px] text-slate-400">
                  {{ prod.unitsSold }} units dispatched &bull;
                  {{ prod.share }} store share
                </p>
              </div>
            </div>
            <div class="text-right flex-shrink-0">
              <div class="text-xs font-black text-slate-900">
                {{ formatCurrency(prod.revenue) }}
              </div>
              <span class="text-[10px] text-emerald-600 font-bold"
                >Trending High</span
              >
            </div>
          </div>
        </div>
      </div>

      <!-- Top Traffic Referrers (5 cols) -->
      <div
        class="lg:col-span-5 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4"
      >
        <div>
          <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
            Top Acquisition Referrers
          </h3>
          <p class="text-xs text-slate-500">
            Where buyers are discovering your shop
          </p>
        </div>

        <div class="space-y-3">
          <div
            v-for="(ref, idx) in adminStore.topReferrers"
            :key="idx"
            class="p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-1.5"
          >
            <div
              class="flex items-center justify-between text-xs font-bold text-slate-800"
            >
              <span class="truncate max-w-[200px]">{{ ref.source }}</span>
              <span class="text-rose-600 font-mono text-[11px]"
                >{{ ref.conversionRate }} CVR</span
              >
            </div>
            <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
              <div
                class="bg-rose-600 h-full rounded-full"
                :style="{ width: `${80 - idx * 18}%` }"
              ></div>
            </div>
            <div
              class="flex justify-between text-[10px] text-slate-400 font-medium"
            >
              <span>{{ ref.visitors.toLocaleString() }} visits</span>
              <span>Primary Channel</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Customer Orders Section with Clickable Items Ordered Modal -->
    <div
      class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4"
    >
      <div
        class="flex flex-col sm:flex-row sm:items-center justify-between gap-2"
      >
        <div>
          <div
            class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-100 mb-1"
          >
            <span>📦</span>
            <span>Live Transactions</span>
          </div>
          <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
            Recent Customer Orders
          </h3>
          <p class="text-xs text-slate-500">
            Click on any order's
            <span class="font-bold text-slate-700">Items Ordered</span> column
            to open the full item breakdown modal.
          </p>
        </div>
        <router-link
          to="/admin/orders"
          class="text-xs font-bold text-rose-600 hover:text-rose-700 hover:underline flex items-center gap-1 self-start sm:self-auto"
        >
          <span>All Orders ({{ adminStore.orders.length }})</span>
          <span>&rarr;</span>
        </router-link>
      </div>

      <div class="overflow-x-auto rounded-xl border border-slate-100">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr
              class="bg-slate-50/80 border-b border-slate-200/80 text-slate-500 uppercase font-bold text-[10px] tracking-wider"
            >
              <th class="p-3.5">Order ID &amp; Date</th>
              <th class="p-3.5">Customer &amp; City</th>
              <th class="p-3.5">Items Ordered</th>
              <th class="p-3.5">Total &amp; Payment</th>
              <th class="p-3.5">Status</th>
              <th class="p-3.5 text-right">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="order in adminStore.orders.slice(0, 5)"
              :key="order.id"
              class="hover:bg-slate-50/70 transition-colors"
            >
              <!-- Order & Date -->
              <td class="p-3.5">
                <span
                  class="font-mono font-bold text-slate-900 text-xs block"
                  >{{ order.id }}</span
                >
                <span class="text-[11px] text-slate-400">{{
                  order.createdAt
                }}</span>
              </td>

              <!-- Customer -->
              <td class="p-3.5">
                <span
                  class="font-bold text-slate-800 block truncate max-w-[140px]"
                  >{{ order.customerName }}</span
                >
                <span
                  class="text-[11px] text-slate-400 block truncate max-w-[140px]"
                  >{{ order.city }}</span
                >
              </td>

              <!-- Items Ordered (Clickable Modal Link) -->
              <td class="p-3.5">
                <button
                  type="button"
                  class="group flex items-center gap-2.5 p-1.5 -m-1.5 rounded-xl hover:bg-rose-50/80 border border-transparent hover:border-rose-200 transition-all text-left cursor-pointer focus:outline-none focus:ring-2 focus:ring-rose-500/30"
                  title="Click to view detailed items order modal"
                  @click="openItemsDetail(order)"
                >
                  <div
                    class="flex items-center -space-x-2 overflow-hidden py-0.5"
                  >
                    <div
                      v-for="item in order.items.slice(0, 3)"
                      :key="item.id"
                      class="w-8 h-8 rounded-lg bg-slate-950 border-2 border-white shadow-2xs flex items-center justify-center p-0.5 relative overflow-hidden group-hover:scale-105 transition-transform"
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
                      class="w-8 h-8 rounded-lg bg-slate-800 text-white font-bold text-[9px] border-2 border-white shadow-2xs flex items-center justify-center"
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
              <td class="p-3.5">
                <span class="font-extrabold text-slate-900 block">{{
                  formatCurrency(order.total)
                }}</span>
                <span class="text-[10px] font-bold text-slate-400 uppercase">{{
                  order.paymentMethod
                }}</span>
              </td>

              <!-- Status -->
              <td class="p-3.5">
                <span
                  class="inline-flex items-center gap-1 text-[11px] font-extrabold px-2.5 py-0.5 rounded-full border shadow-2xs"
                  :class="{
                    'bg-amber-50 text-amber-800 border-amber-200':
                      order.status === 'Pending',
                    'bg-blue-50 text-blue-800 border-blue-200':
                      order.status === 'Processing',
                    'bg-indigo-50 text-indigo-800 border-indigo-200':
                      order.status === 'Shipped',
                    'bg-emerald-50 text-emerald-800 border-emerald-200':
                      order.status === 'Delivered',
                    'bg-rose-50 text-rose-800 border-rose-200':
                      order.status === 'Canceled',
                    'bg-fuchsia-50 text-fuchsia-800 border-fuchsia-200':
                      order.status === 'Refunded',
                  }"
                >
                  {{ order.status }}
                </span>
              </td>

              <!-- Action -->
              <td class="p-3.5 text-right">
                <button
                  type="button"
                  class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all cursor-pointer inline-flex items-center gap-1"
                  title="Inspect Order Items"
                  @click="openItemsDetail(order)"
                >
                  <span>👁️</span>
                  <span>Inspect</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Beautiful Order Items Detail Modal (Based on customer_orders & customer_order_items) -->
    <OrderItemsDetailModal
      :is-open="isItemsDetailModalOpen"
      :order="selectedOrder"
      @close="isItemsDetailModalOpen = false"
      @print-invoice="router.push('/admin/orders')"
      @open-fulfillment="router.push('/admin/orders')"
    />
  </div>
</template>
