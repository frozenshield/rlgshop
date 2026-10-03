<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "@/modules/auth/auth.store";
import { useCheckoutStore } from "@/modules/checkout/checkout.store";
import { formatCurrency } from "@/shared/utils/currency.util";
import BaseBadge from "@/shared/components/BaseBadge.vue";
import BaseButton from "@/shared/components/BaseButton.vue";

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const checkoutStore = useCheckoutStore();

interface OrderItem {
  id?: number | string;
  product_name?: string;
  name?: string;
  sku?: string;
  price: number | string;
  quantity: number;
  subtotal?: number | string;
  image_url?: string;
  toy?: any;
}

interface OrderRecord {
  id: string | number;
  orderNumber: string;
  createdAt: string;
  status: string;
  statusLabel?: string;
  statusBadgeColor?: string;
  totalAmount: number;
  paymentMethod: string;
  paymentStatus?: string;
  customerName: string;
  customerEmail: string;
  customerPhone?: string;
  shippingAddress: string;
  city?: string;
  carrierName?: string;
  trackingNumber?: string;
  trackingUrl?: string;
  items: OrderItem[];
}

const orders = ref<OrderRecord[]>([]);
const isLoading = ref(true);
const searchQuery = ref("");
const selectedFilter = ref<
  "all" | "shipped" | "delivered" | "processing" | "completed"
>("all");
const selectedOrderForModal = ref<OrderRecord | null>(null);

const openAuthModal = () => {
  if (typeof window !== "undefined") {
    window.dispatchEvent(new CustomEvent("open-auth-modal"));
  }
};

const fetchOrders = async () => {
  isLoading.value = true;
  try {
    const headers: Record<string, string> = {};
    if (authStore.token) {
      headers["Authorization"] = `Bearer ${authStore.token}`;
    }

    const res = await fetch("/api/customer-orders", { headers });
    let dbOrders: OrderRecord[] = [];
    if (res.ok) {
      const json = await res.json();
      if (json.success && Array.isArray(json.data)) {
        dbOrders = json.data.map((o: any) => ({
          id: o.id,
          orderNumber: o.order_number,
          createdAt: o.created_at || o.order_date,
          status: o.status?.name || "processing",
          statusLabel: o.status?.label || "Processing",
          statusBadgeColor: o.status?.badge_color || "blue",
          totalAmount: parseFloat(o.total_amount) || 0,
          paymentMethod: o.payment_method || "Credit Card",
          paymentStatus: o.payment_status || "Paid",
          customerName:
            o.customer_profile?.name ||
            o.customer_profile?.user?.name ||
            "Collector",
          customerEmail:
            o.customer_profile?.user?.email ||
            authStore.currentUser?.email ||
            "collector@rlghobby.com",
          customerPhone:
            o.customer_profile?.phone ||
            o.customer_profile?.shipping_address?.phone ||
            undefined,
          shippingAddress:
            o.customer_profile?.shipping_address?.shipping_address ||
            o.customer_profile?.address_line1 ||
            "Metro Manila, Philippines",
          city:
            o.customer_profile?.shipping_address?.city ||
            o.customer_profile?.city ||
            "Metro Manila",
          carrierName:
            o.fulfillment?.carrier?.short_name ||
            o.fulfillment?.carrier?.name ||
            "J&T Express",
          trackingNumber: o.fulfillment?.tracking_number || undefined,
          trackingUrl:
            o.fulfillment?.carrier?.tracking_url_template &&
            o.fulfillment?.tracking_number
              ? o.fulfillment.carrier.tracking_url_template.replace(
                  "{tracking}",
                  o.fulfillment.tracking_number,
                )
              : undefined,
          items: (o.items || []).map((it: any) => ({
            id: it.id,
            product_name: it.product_name || "Collector Product",
            sku: it.sku || "TCG-ITEM",
            price: parseFloat(it.price) || 0,
            quantity: it.quantity || 1,
            image_url:
              it.image_url ||
              "https://images.unsplash.com/photo-1613771404784-3a5686aa2be3?w=600&auto=format&fit=crop&q=80",
          })),
        }));

        // Also merge local storage placed orders from current session
        const localOrders = checkoutStore.orderHistory.map((lo) => ({
          id: lo.orderId,
          orderNumber: lo.orderId,
          createdAt: lo.createdAt,
          status: "processing",
          statusLabel: "Processing",
          statusBadgeColor: "blue",
          totalAmount: lo.total,
          paymentMethod: lo.shippingDetails.paymentMethod.toUpperCase(),
          paymentStatus: "Paid",
          customerName:
            `${lo.shippingDetails.firstName} ${lo.shippingDetails.lastName}`.trim(),
          customerEmail: lo.shippingDetails.email,
          customerPhone: lo.shippingDetails.phone,
          shippingAddress: lo.shippingDetails.streetAddress,
          city: lo.shippingDetails.city,
          carrierName: "J&T Express (Standard)",
          trackingNumber: `PH-${Math.floor(100000000 + Math.random() * 900000000)}`,
          trackingUrl: "https://www.jtexpress.ph",
          items: lo.items.map((it) => ({
            product_name: it.toy.name,
            sku: it.toy.slug || "TCG-COLLECTIBLE",
            price: it.toy.price,
            quantity: it.quantity,
            image_url: it.toy.imageUrl,
          })),
        }));

        const existingNums = new Set(dbOrders.map((d) => d.orderNumber));
        const filteredLocals = localOrders.filter(
          (l) => !existingNums.has(l.orderNumber),
        );
        orders.value = [...filteredLocals, ...dbOrders];
      }
    }
  } catch (e) {
    console.error("Failed to load customer orders", e);
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  fetchOrders();

  // If query has order tracking search param
  if (route.query.q) {
    searchQuery.value = String(route.query.q);
  }
});

watch(
  () => authStore.token,
  () => {
    fetchOrders();
  },
);

const filteredOrders = computed(() => {
  return orders.value.filter((o) => {
    // Status filter
    if (selectedFilter.value !== "all") {
      const st = (o.status || "").toLowerCase();
      if (
        selectedFilter.value === "shipped" &&
        !st.includes("ship") &&
        !st.includes("transit")
      )
        return false;
      if (selectedFilter.value === "delivered" && !st.includes("deliver"))
        return false;
      if (
        selectedFilter.value === "completed" &&
        !st.includes("complete") &&
        !st.includes("accept")
      )
        return false;
      if (
        selectedFilter.value === "processing" &&
        !st.includes("process") &&
        !st.includes("pend")
      )
        return false;
    }

    // Search query
    if (searchQuery.value.trim()) {
      const q = searchQuery.value.toLowerCase().trim();
      const matchNum = o.orderNumber.toLowerCase().includes(q);
      const matchTrack = o.trackingNumber
        ? o.trackingNumber.toLowerCase().includes(q)
        : false;
      const matchName = o.customerName.toLowerCase().includes(q);
      const matchEmail = o.customerEmail.toLowerCase().includes(q);
      const matchItems = o.items.some((it) =>
        (it.product_name || "").toLowerCase().includes(q),
      );
      return matchNum || matchTrack || matchName || matchEmail || matchItems;
    }

    return true;
  });
});

const getStatusBadgeClass = (status: string) => {
  const s = (status || "").toLowerCase();
  if (s.includes("complete") || s.includes("accept")) {
    return "bg-emerald-500/20 text-emerald-400 border border-emerald-500/40";
  }
  if (s.includes("deliver")) {
    return "bg-teal-500/20 text-teal-400 border border-teal-500/40";
  }
  if (s.includes("transit")) {
    return "bg-indigo-500/20 text-indigo-400 border border-indigo-500/40";
  }
  if (s.includes("ship")) {
    return "bg-purple-500/20 text-purple-400 border border-purple-500/40";
  }
  if (s.includes("process")) {
    return "bg-blue-500/20 text-blue-400 border border-blue-500/40";
  }
  if (s.includes("cancel")) {
    return "bg-rose-500/20 text-rose-400 border border-rose-500/40";
  }
  if (s.includes("refund")) {
    return "bg-pink-500/20 text-pink-400 border border-pink-500/40";
  }
  return "bg-amber-500/20 text-amber-400 border border-amber-500/40";
};

export interface TimelineStep {
  step: number;
  key: string;
  title: string;
  subtitle: string;
  icon: string;
  isCompleted: boolean;
  isCurrent: boolean;
  isPendingPayment?: boolean;
}

const getStepProgressIndex = (order: OrderRecord): number => {
  const s = (order.status || "").toLowerCase().trim();
  const p = (order.paymentStatus || "").toLowerCase().trim();
  const isPaid =
    p === "paid" || p === "success" || p === "completed";

  // Step 7: Completed (Accepted / Completed)
  if (
    s === "completed" ||
    s === "accepted" ||
    s.includes("complete") ||
    s.includes("accept")
  ) {
    return 7;
  }
  // Step 6: Delivered
  if (s === "delivered" || s.includes("deliver")) {
    return 6;
  }
  // Step 5: In Transit
  if (
    s === "in_transit" ||
    s === "in-transit" ||
    s.includes("transit")
  ) {
    return 5;
  }
  // Step 4: Shipped
  if (s === "shipped" || s.includes("ship")) {
    // If tracking number exists and dispatched, advance into In Transit
    return order.trackingNumber ? 5 : 4;
  }
  // Step 3: Processing
  if (s === "processing" || s.includes("process") || s.includes("pack")) {
    return 3;
  }
  // Step 2: Payment Confirmed / Paid
  if (isPaid) {
    return 2;
  }
  // Step 1: Order Placed (Payment Pending)
  return 1;
};

const getProgressLineWidth = (order: OrderRecord): string => {
  const current = getStepProgressIndex(order);
  const ratio = Math.max(0, Math.min(6, current - 1)) / 6;
  return `${ratio * 85.72}%`;
};

const getTimelineSteps = (order: OrderRecord): TimelineStep[] => {
  const current = getStepProgressIndex(order);
  const p = (order.paymentStatus || "").toLowerCase().trim();
  const isPaid =
    p === "paid" || p === "success" || p === "completed";
  const isCod =
    order.paymentMethod?.toLowerCase().includes("cod") ||
    order.paymentMethod?.toLowerCase().includes("cash");

  return [
    {
      step: 1,
      key: "order-placed",
      title: "Order Placed",
      subtitle: "Verified",
      icon: "✓",
      isCompleted: current >= 1,
      isCurrent: current === 1,
    },
    {
      step: 2,
      key: "payment",
      title: isPaid ? "Payment Success" : "Payment Pending",
      subtitle: isPaid
        ? `${order.paymentMethod || "Online"} Paid`
        : isCod
          ? "Due on Delivery"
          : "Awaiting Verification",
      icon: isPaid ? "✓" : current === 1 ? "⏳" : "💳",
      isCompleted: isPaid,
      isCurrent: !isPaid && current <= 2,
      isPendingPayment: !isPaid,
    },
    {
      step: 3,
      key: "processing",
      title: "Processing",
      subtitle: current >= 3 ? "Pick & Pack" : "Pending",
      icon: current > 3 ? "✓" : current === 3 ? "📦" : "3",
      isCompleted: current > 3,
      isCurrent: current === 3,
    },
    {
      step: 4,
      key: "shipped",
      title: "Shipped",
      subtitle:
        current >= 4
          ? order.carrierName
            ? order.carrierName.split(" ")[0]
            : "Dispatched"
          : "Carrier Dispatch",
      icon: current > 4 ? "✓" : current === 4 ? "🏷️" : "4",
      isCompleted: current > 4,
      isCurrent: current === 4,
    },
    {
      step: 5,
      key: "in-transit",
      title: "In Transit",
      subtitle:
        current >= 5
          ? order.trackingNumber
            ? "On the Road"
            : "In Transit"
          : "Logistics Hub",
      icon: current > 5 ? "✓" : current === 5 ? "🚚" : "5",
      isCompleted: current > 5,
      isCurrent: current === 5,
    },
    {
      step: 6,
      key: "delivered",
      title: "Delivered",
      subtitle: current >= 6 ? "Package Arrived" : "Collector Handed",
      icon: current > 6 ? "✓" : current === 6 ? "🏠" : "6",
      isCompleted: current >= 6,
      isCurrent: current === 6,
    },
    {
      step: 7,
      key: "completed",
      title: "Completed",
      subtitle: current >= 7 ? "Order Finalized" : "Final Step",
      icon: current >= 7 ? "★" : "7",
      isCompleted: current >= 7,
      isCurrent: current === 7,
    },
  ];
};

const formatDate = (dateStr: string) => {
  try {
    return new Date(dateStr).toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  } catch {
    return dateStr;
  }
};
</script>

<template>
  <div class="min-h-screen py-10 font-display text-slate-100">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
      <!-- Page Header -->
      <div
        class="bg-gradient-to-r from-slate-900 via-slate-900 to-indigo-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-2xl relative overflow-hidden"
      >
        <!-- Background decorative badge -->
        <div
          class="absolute -right-8 -bottom-8 opacity-10 text-9xl pointer-events-none select-none"
        >
          📦
        </div>

        <div class="relative z-10 space-y-3">
          <div class="flex items-center gap-2">
            <span
              class="px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-400 text-slate-950 uppercase tracking-wider"
            >
              Live Fulfillment Tracking
            </span>
            <span
              class="text-xs text-emerald-400 font-bold flex items-center gap-1"
            >
              <span
                class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"
              ></span>
              Real-time Carrier Sync
            </span>
          </div>

          <h1 class="text-2xl sm:text-4xl font-black text-white tracking-tight">
            My Collector Orders &amp; Package Tracking
          </h1>

          <p
            class="text-xs sm:text-sm text-slate-400 max-w-2xl leading-relaxed"
          >
            Track your authentic Japanese Pokémon booster boxes, serialized
            cards, and collector scale figures directly from warehouse packaging
            to your doorstep.
          </p>
        </div>
      </div>

      <!-- Auth Status / Guest Scoped Notice -->
      <div
        v-if="!authStore.isAuthenticated"
        class="p-4 rounded-2xl bg-indigo-950/40 border border-indigo-500/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs"
      >
        <div class="flex items-center gap-3">
          <span class="text-2xl">🔒</span>
          <div>
            <p class="font-bold text-white">
              Sign In for Scoped Order Tracking
            </p>
            <p class="text-slate-400 text-[11px]">
              You are currently browsing as a guest. Log in to sync and track
              all your authenticated collector orders.
            </p>
          </div>
        </div>
        <button
          type="button"
          class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-xs transition-colors shrink-0 cursor-pointer shadow-md"
          @click="openAuthModal"
        >
          Sign In / Register
        </button>
      </div>

      <div
        v-else
        class="p-3 rounded-2xl bg-slate-900/60 border border-slate-800 flex items-center justify-between text-xs text-slate-300"
      >
        <span class="flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
          <span
            >Orders scoped to:
            <strong class="text-white">{{
              authStore.currentUser?.name || "Verified Collector"
            }}</strong>
            ({{ authStore.currentUser?.email }})</span
          >
        </span>
        <span
          class="text-[11px] text-emerald-400 font-bold bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/30"
        >
          Encrypted &amp; Scoped
        </span>
      </div>

      <!-- Search & Status Filter Bar -->
      <div
        class="bg-slate-900/90 backdrop-blur-md p-4 rounded-2xl border border-slate-800 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4"
      >
        <!-- Search input -->
        <div class="relative flex-1 max-w-lg">
          <span
            class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-sm"
          >
            🔍
          </span>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by Order # (e.g. ORD-8921), tracking number, or item name..."
            class="w-full pl-10 pr-4 py-2.5 bg-slate-950 text-white text-xs rounded-xl border border-slate-700/80 focus:border-amber-400 focus:outline-none placeholder-slate-500 font-medium"
          />
          <button
            v-if="searchQuery"
            type="button"
            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-500 hover:text-white text-xs cursor-pointer"
            @click="searchQuery = ''"
          >
            ✕
          </button>
        </div>

        <!-- Filter Tabs -->
        <div
          class="flex items-center gap-1.5 overflow-x-auto p-1 bg-slate-950 rounded-xl border border-slate-800"
        >
          <button
            type="button"
            class="px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-colors cursor-pointer"
            :class="
              selectedFilter === 'all'
                ? 'bg-amber-400 text-slate-950'
                : 'text-slate-400 hover:text-white'
            "
            @click="selectedFilter = 'all'"
          >
            All Orders ({{ orders.length }})
          </button>
          <button
            type="button"
            class="px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-colors cursor-pointer"
            :class="
              selectedFilter === 'shipped'
                ? 'bg-amber-400 text-slate-950'
                : 'text-slate-400 hover:text-white'
            "
            @click="selectedFilter = 'shipped'"
          >
            🚚 In Transit / Shipped
          </button>
          <button
            type="button"
            class="px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-colors cursor-pointer"
            :class="
              selectedFilter === 'processing'
                ? 'bg-amber-400 text-slate-950'
                : 'text-slate-400 hover:text-white'
            "
            @click="selectedFilter = 'processing'"
          >
            ⚙️ Processing
          </button>
          <button
            type="button"
            class="px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-colors cursor-pointer"
            :class="
              selectedFilter === 'delivered'
                ? 'bg-amber-400 text-slate-950'
                : 'text-slate-400 hover:text-white'
            "
            @click="selectedFilter = 'delivered'"
          >
            ✓ Delivered
          </button>
          <button
            type="button"
            class="px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-colors cursor-pointer"
            :class="
              selectedFilter === 'completed'
                ? 'bg-amber-400 text-slate-950'
                : 'text-slate-400 hover:text-white'
            "
            @click="selectedFilter = 'completed'"
          >
            ★ Completed
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="py-16 text-center space-y-3">
        <div
          class="w-10 h-10 border-3 border-amber-400 border-t-transparent rounded-full animate-spin mx-auto"
        ></div>
        <p class="text-xs text-slate-400 font-bold">
          Synchronizing orders from warehouse database...
        </p>
      </div>

      <!-- Empty State -->
      <div
        v-else-if="filteredOrders.length === 0"
        class="bg-slate-900 rounded-3xl p-12 text-center border border-slate-800 space-y-4 max-w-md mx-auto"
      >
        <div class="text-5xl">📦</div>
        <h3 class="text-lg font-bold text-white">No Orders Found</h3>
        <p class="text-xs text-slate-400 max-w-xs mx-auto">
          {{
            searchQuery
              ? `No order records matched "${searchQuery}". Check the tracking code or order number.`
              : "You don't have any placed orders yet. Explore our collector store!"
          }}
        </p>
        <button
          type="button"
          class="px-5 py-2.5 bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-black rounded-xl shadow-lg transition-all cursor-pointer"
          @click="router.push('/catalog')"
        >
          Browse Pokémon Catalog &rarr;
        </button>
      </div>

      <!-- Orders List Cards -->
      <div v-else class="space-y-6">
        <div
          v-for="order in filteredOrders"
          :key="order.id"
          class="bg-slate-900 rounded-3xl border border-slate-800 shadow-xl overflow-hidden transition-all hover:border-slate-700"
        >
          <!-- Order Card Header -->
          <div
            class="p-5 sm:p-6 bg-slate-950/70 border-b border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
          >
            <div class="space-y-1">
              <div class="flex items-center gap-2.5 flex-wrap">
                <span class="font-mono text-base font-black text-amber-400">
                  {{ order.orderNumber }}
                </span>
                <span
                  class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider"
                  :class="getStatusBadgeClass(order.status)"
                >
                  {{ order.statusLabel }}
                </span>
                <span class="text-xs text-slate-400 font-medium">
                  Placed on {{ formatDate(order.createdAt) }}
                </span>
              </div>
              <p class="text-xs text-slate-400">
                Payment:
                <span class="text-slate-200 font-bold">{{
                  order.paymentMethod
                }}</span>
                &bull; Status:
                <span
                  :class="
                    order.paymentStatus === 'Paid' || order.paymentStatus === 'Success'
                      ? 'text-emerald-400 font-semibold'
                      : 'text-amber-400 font-semibold'
                  "
                >{{ order.paymentStatus }}</span>
              </p>
            </div>

            <!-- Carrier & Tracking Quick Pill -->
            <div class="flex items-center gap-2 sm:self-center">
              <div
                v-if="order.trackingNumber"
                class="px-3 py-1.5 rounded-xl bg-slate-900 border border-indigo-500/30 text-xs text-indigo-300 font-mono font-bold flex items-center gap-2"
              >
                <span>🚚 {{ order.carrierName }}:</span>
                <span class="text-amber-300">{{ order.trackingNumber }}</span>
              </div>
              <a
                v-if="order.trackingUrl"
                :href="order.trackingUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="px-3 py-1.5 rounded-xl bg-indigo-950/80 hover:bg-indigo-900 text-indigo-300 border border-indigo-500/40 text-xs font-bold transition-colors flex items-center gap-1"
                title="Open carrier tracking page"
              >
                <span>Track</span>
                <span>↗</span>
              </a>
            </div>
          </div>

          <!-- Live Progress Stepper Graphic (7-Step Order Locator) -->
          <div class="p-6 bg-slate-900/60 border-b border-slate-800/80">
            <div class="flex items-center justify-between mb-4">
              <h4
                class="text-[11px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5"
              >
                <span>📍</span>
                <span>Shipment Journey Locator</span>
              </h4>
              <div class="flex items-center gap-2">
                <span class="text-[10px] text-slate-400"
                  >Current Stage:
                  <strong class="text-amber-400 font-bold uppercase">{{
                    order.statusLabel || order.status
                  }}</strong></span
                >
                <span
                  class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 border border-slate-700"
                  >Step {{ getStepProgressIndex(order) }} / 7</span
                >
              </div>
            </div>
            <div class="overflow-x-auto pb-2 -mx-2 px-2 custom-scrollbar">
              <div
                class="min-w-[620px] sm:min-w-0 grid grid-cols-7 gap-1 text-center relative py-1"
              >
                <!-- Track background line -->
                <div
                  class="absolute top-4 left-[7.14%] right-[7.14%] h-1 bg-slate-800 -z-0 rounded-full"
                ></div>
                <!-- Active gradient progress line -->
                <div
                  class="absolute top-4 left-[7.14%] h-1 bg-gradient-to-r from-amber-400 via-indigo-500 to-emerald-400 -z-0 transition-all duration-700 rounded-full"
                  :style="{ width: getProgressLineWidth(order) }"
                ></div>

                <!-- 7 Journey Steps: Order Placed -> Payment (Pending/Success) -> Processing -> Shipped -> In Transit -> Delivered -> Completed -->
                <div
                  v-for="step in getTimelineSteps(order)"
                  :key="step.key"
                  class="flex flex-col items-center relative z-10 space-y-1.5 px-0.5"
                >
                  <div
                    class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300"
                    :class="[
                      step.isCompleted
                        ? 'bg-amber-400 text-slate-950 font-black ring-4 ring-amber-400/20 shadow-md'
                        : step.isCurrent
                          ? (step.isPendingPayment
                              ? 'bg-amber-500/20 text-amber-300 border-2 border-amber-400 ring-4 ring-amber-400/20 animate-pulse'
                              : 'bg-indigo-600 text-white font-black ring-4 ring-indigo-500/30 animate-pulse shadow-md')
                          : 'bg-slate-800 text-slate-500 border border-slate-700/60'
                    ]"
                  >
                    <span>{{ step.icon }}</span>
                  </div>
                  <span
                    class="text-[10px] sm:text-[11px] font-bold leading-tight"
                    :class="[
                      step.isCompleted
                        ? 'text-white'
                        : step.isCurrent
                          ? (step.isPendingPayment ? 'text-amber-400' : 'text-indigo-300 font-extrabold')
                          : 'text-slate-500'
                    ]"
                  >
                    {{ step.title }}
                  </span>
                  <span
                    class="text-[9px] sm:text-[10px] text-slate-400 leading-tight hidden sm:block truncate max-w-full"
                    :title="step.subtitle"
                  >
                    {{ step.subtitle }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Items Ordered Grid -->
          <div class="p-6 space-y-3">
            <h5 class="text-xs font-bold text-slate-300">
              Items Included in Parcel ({{ order.items.length }})
            </h5>
            <div class="divide-y divide-slate-800/80">
              <div
                v-for="(item, idx) in order.items"
                :key="idx"
                class="py-3 flex items-center gap-3.5"
              >
                <img
                  :src="item.image_url"
                  :alt="item.product_name"
                  class="w-14 h-14 rounded-xl object-contain bg-slate-950 border border-slate-800 p-1 flex-shrink-0"
                />
                <div class="flex-1 min-w-0">
                  <h6 class="text-xs sm:text-sm font-bold text-white truncate">
                    {{ item.product_name }}
                  </h6>
                  <p class="text-[11px] text-slate-400 font-mono">
                    SKU: {{ item.sku }} &bull; Qty: {{ item.quantity }}
                  </p>
                </div>
                <div class="text-right flex-shrink-0 font-mono">
                  <p class="text-xs sm:text-sm font-bold text-amber-400">
                    {{ formatCurrency(Number(item.price) * item.quantity) }}
                  </p>
                  <p
                    v-if="item.quantity > 1"
                    class="text-[10px] text-slate-500"
                  >
                    {{ formatCurrency(Number(item.price)) }} each
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Order Footer: Recipient & Total -->
          <div
            class="p-5 sm:p-6 bg-slate-950/90 border-t border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs"
          >
            <div class="space-y-0.5 text-slate-400">
              <p>
                <span class="text-slate-200 font-bold">Recipient:</span>
                {{ order.customerName }}
                {{ order.customerPhone ? `(${order.customerPhone})` : "" }}
              </p>
              <p class="text-[11px]">
                <span class="text-slate-200 font-bold">Destination:</span>
                {{ order.shippingAddress }}, {{ order.city }}
              </p>
            </div>

            <div class="flex items-center gap-4 sm:self-center">
              <div class="text-right">
                <span
                  class="text-[10px] text-slate-400 uppercase tracking-wider block font-bold"
                  >Total Paid</span
                >
                <span class="text-lg font-black text-amber-400 font-mono">
                  {{ formatCurrency(order.totalAmount) }}
                </span>
              </div>

              <button
                type="button"
                class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-xl border border-slate-700 transition-colors cursor-pointer"
                @click="selectedOrderForModal = order"
              >
                Receipt
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Order Receipt Modal -->
    <teleport to="body">
      <div
        v-if="selectedOrderForModal"
        class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 flex items-center justify-center p-4 font-display"
        @click="selectedOrderForModal = null"
      >
        <div
          class="bg-slate-900 border border-slate-800 rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-5 text-slate-100 shadow-2xl relative"
          @click.stop
        >
          <button
            type="button"
            class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center text-xs cursor-pointer"
            @click="selectedOrderForModal = null"
          >
            ✕
          </button>

          <div class="flex items-center gap-3">
            <span class="text-2xl">🧾</span>
            <div>
              <h3 class="text-base font-black text-white">
                Collector Official Invoice
              </h3>
              <p class="text-xs text-slate-400 font-mono">
                Order {{ selectedOrderForModal.orderNumber }}
              </p>
            </div>
          </div>

          <div
            class="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-2 text-xs"
          >
            <div class="flex justify-between">
              <span class="text-slate-400">Order Date:</span>
              <span class="font-bold text-white">{{
                formatDate(selectedOrderForModal.createdAt)
              }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-400">Payment:</span>
              <span class="font-bold text-white"
                >{{ selectedOrderForModal.paymentMethod }} ({{
                  selectedOrderForModal.paymentStatus
                }})</span
              >
            </div>
            <div class="flex justify-between">
              <span class="text-slate-400">Carrier:</span>
              <span class="font-bold text-white">{{
                selectedOrderForModal.carrierName
              }}</span>
            </div>
            <div
              v-if="selectedOrderForModal.trackingNumber"
              class="flex justify-between"
            >
              <span class="text-slate-400">Tracking Number:</span>
              <span class="font-mono font-bold text-amber-300">{{
                selectedOrderForModal.trackingNumber
              }}</span>
            </div>
          </div>

          <div class="space-y-2">
            <h4 class="text-xs font-bold text-slate-300">
              Items ({{ selectedOrderForModal.items.length }})
            </h4>
            <div
              class="max-h-48 overflow-y-auto divide-y divide-slate-800/80 pr-1 text-xs"
            >
              <div
                v-for="(it, i) in selectedOrderForModal.items"
                :key="i"
                class="py-2 flex justify-between items-center"
              >
                <div class="min-w-0 flex-1 pr-2">
                  <p class="font-bold text-white truncate">
                    {{ it.product_name }}
                  </p>
                  <p class="text-[10px] text-slate-400">
                    Qty: {{ it.quantity }} &times;
                    {{ formatCurrency(Number(it.price)) }}
                  </p>
                </div>
                <span class="font-mono font-bold text-amber-400">
                  {{ formatCurrency(Number(it.price) * it.quantity) }}
                </span>
              </div>
            </div>
          </div>

          <div
            class="pt-3 border-t border-slate-800 flex justify-between items-baseline"
          >
            <span class="text-sm font-bold text-white">Total Amount</span>
            <span class="text-xl font-black text-amber-400 font-mono">
              {{ formatCurrency(selectedOrderForModal.totalAmount) }}
            </span>
          </div>

          <button
            type="button"
            class="w-full py-3 bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs rounded-xl shadow-lg cursor-pointer transition-colors"
            @click="selectedOrderForModal = null"
          >
            Close Receipt
          </button>
        </div>
      </div>
    </teleport>
  </div>
</template>
