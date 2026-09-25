<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useAdminStore } from "../admin.store";
import { formatCurrency } from "@/shared/utils/currency.util";

const adminStore = useAdminStore();

// Tabs
const activeTab = ref<"promos" | "upsells" | "abandoned" | "seo">("promos");

// New Promo Code Form Modal
const isCreatePromoOpen = ref(false);
const newCode = ref("");
const newType = ref<"percentage" | "fixed" | "shipping">("percentage");
const newValue = ref(15);
const newLimit = ref(200);
const newExpiry = ref("2026-12-31");

const fetchDbPromos = async () => {
  try {
    const res = await fetch("/api/promo-codes");
    if (res.ok) {
      const json = await res.json();
      if (json.success && Array.isArray(json.data) && json.data.length > 0) {
        adminStore.promoCodes = json.data.map((p: any) => ({
          id: String(p.id),
          code: p.code,
          type: p.type,
          value: Number(p.value),
          usageCount: Number(p.usage_count) || 0,
          usageLimit: p.usage_limit ? Number(p.usage_limit) : 500,
          expiryDate: p.expiry_date
            ? String(p.expiry_date).slice(0, 10)
            : "2026-12-31",
          isActive: Boolean(p.is_active),
        }));
      }
    }
  } catch (e) {
    console.error("Failed to load promo codes from database", e);
  }
};

onMounted(() => {
  fetchDbPromos();
});

const handleCreatePromo = async () => {
  if (newCode.value.trim()) {
    const code = newCode.value.toUpperCase().trim();
    try {
      const res = await fetch("/api/promo-codes", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({
          code,
          type: newType.value,
          value: newValue.value,
          usage_limit: newLimit.value,
          expiry_date: newExpiry.value,
          is_active: true,
        }),
      });

      if (res.ok) {
        const json = await res.json();
        const p = json.data;
        adminStore.promoCodes.unshift({
          id: String(p.id),
          code: p.code,
          type: p.type,
          value: Number(p.value),
          usageCount: Number(p.usage_count) || 0,
          usageLimit: p.usage_limit ? Number(p.usage_limit) : newLimit.value,
          expiryDate: p.expiry_date
            ? String(p.expiry_date).slice(0, 10)
            : newExpiry.value,
          isActive: Boolean(p.is_active),
        });
        isCreatePromoOpen.value = false;
        newCode.value = "";
        return;
      }
    } catch (e) {
      console.error("Failed to create promo code in database", e);
    }

    adminStore.addPromoCode({
      code,
      type: newType.value,
      value: newValue.value,
      usageLimit: newLimit.value,
      expiryDate: newExpiry.value,
      isActive: true,
    });
    isCreatePromoOpen.value = false;
    newCode.value = "";
  }
};

const handleTogglePromo = async (promo: any) => {
  promo.isActive = !promo.isActive;
  try {
    await fetch(`/api/promo-codes/${promo.id}/toggle`, {
      method: "POST",
      headers: { Accept: "application/json" },
    });
  } catch (e) {
    console.error("Failed to toggle promo in database", e);
  }
};

// Upsell pairings
const upsellPairs = ref([
  {
    primaryProduct: "One Piece Card Game: Awakening of the New Era (OP-05)",
    recommendations: [
      "KMC Hyper Matte Sleeves (Black)",
      "Acrylic Magnetic Booster Box Case",
    ],
    conversionLift: "+22.4%",
  },
  {
    primaryProduct: "Pokémon TCG: Scarlet & Violet 151 Elite Trainer Box",
    recommendations: [
      "Ultra Pro 9-Pocket Binder (Charizard)",
      "Pro-Fit Exact Inner Sleeves",
    ],
    conversionLift: "+18.1%",
  },
  {
    primaryProduct: "RG 1/144 RX-78-2 Gundam Ver.2.0 Gunpla",
    recommendations: [
      "Bandai Spirits Action Base 5",
      "Gundam Marker Fine Tip Lining Pen",
    ],
    conversionLift: "+31.0%",
  },
]);

// Global Storefront SEO Controls
const globalMetaTitle = ref(
  "RLG Hobby Shop | TCG, Model Kits, Figures & Collectibles Philippines",
);
const globalMetaDescription = ref(
  "Shop authentic factory-sealed Pokémon, One Piece, Hololive TCG booster boxes, Japanese Bandai Gunpla model kits, and scale anime figures with secure nationwide shipping.",
);
const homeSlug = ref("rlghobby.ph");
const isSeoSaved = ref(false);

const saveSeo = () => {
  isSeoSaved.value = true;
  setTimeout(() => {
    isSeoSaved.value = false;
  }, 3000);
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
          Marketing &amp; Promotions Engine
        </h1>
        <p class="text-xs text-slate-500">
          Discount codes, cross-sell/upsell matrices, abandoned cart recovery,
          and search engine optimization.
        </p>
      </div>

      <!-- Tab Buttons -->
      <div
        class="flex items-center gap-1 p-1 bg-slate-100 rounded-xl border border-slate-200 overflow-x-auto"
      >
        <button
          type="button"
          class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer whitespace-nowrap"
          :class="
            activeTab === 'promos'
              ? 'bg-white text-slate-900 shadow-xs'
              : 'text-slate-500 hover:text-slate-800'
          "
          @click="activeTab = 'promos'"
        >
          🏷️ Discount Codes
        </button>
        <button
          type="button"
          class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer whitespace-nowrap"
          :class="
            activeTab === 'upsells'
              ? 'bg-white text-slate-900 shadow-xs'
              : 'text-slate-500 hover:text-slate-800'
          "
          @click="activeTab = 'upsells'"
        >
          🔄 Upsell &amp; Bundles
        </button>
        <button
          type="button"
          class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer whitespace-nowrap"
          :class="
            activeTab === 'abandoned'
              ? 'bg-white text-slate-900 shadow-xs'
              : 'text-slate-500 hover:text-slate-800'
          "
          @click="activeTab = 'abandoned'"
        >
          🛒 Abandoned Carts
        </button>
        <button
          type="button"
          class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer whitespace-nowrap"
          :class="
            activeTab === 'seo'
              ? 'bg-white text-slate-900 shadow-xs'
              : 'text-slate-500 hover:text-slate-800'
          "
          @click="activeTab = 'seo'"
        >
          🔍 SEO Metadata
        </button>
      </div>
    </div>

    <!-- TAB 1: Discount Engine -->
    <div v-if="activeTab === 'promos'" class="space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
            Active Promotional Coupons
          </h2>
          <p class="text-xs text-slate-500">
            Configure percentage markdowns, fixed discounts, and free shipping
            triggers
          </p>
        </div>
        <button
          type="button"
          class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold shadow-xs cursor-pointer"
          @click="isCreatePromoOpen = true"
        >
          + Create Promo Code
        </button>
      </div>

      <div
        class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden"
      >
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr
                class="bg-slate-50 border-b border-slate-200/80 text-slate-500 uppercase font-bold text-[10px] tracking-wider"
              >
                <th class="p-4">Coupon Code</th>
                <th class="p-4">Discount Type &amp; Value</th>
                <th class="p-4">Redemptions</th>
                <th class="p-4">Usage Limit</th>
                <th class="p-4">Expiration Date</th>
                <th class="p-4">Status</th>
                <th class="p-4 text-right">Toggle Active</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="promo in adminStore.promoCodes"
                :key="promo.id"
                class="hover:bg-slate-50/70 transition-colors"
              >
                <td class="p-4 font-mono font-black text-rose-600 text-xs">
                  {{ promo.code }}
                </td>
                <td class="p-4 font-bold text-slate-800">
                  <span v-if="promo.type === 'percentage'"
                    >{{ promo.value }}% OFF</span
                  >
                  <span v-else-if="promo.type === 'fixed'"
                    >₱{{ promo.value }} Fixed Reduction</span
                  >
                  <span v-else>Free Shipping</span>
                </td>
                <td class="p-4 font-bold text-slate-700">
                  {{ promo.usageCount }} times used
                </td>
                <td class="p-4 text-slate-500">
                  {{ promo.usageLimit }} maximum
                </td>
                <td class="p-4 text-slate-500 font-mono">
                  {{ promo.expiryDate }}
                </td>
                <td class="p-4">
                  <span
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                    :class="
                      promo.isActive
                        ? 'bg-emerald-100 text-emerald-800'
                        : 'bg-slate-100 text-slate-600'
                    "
                  >
                    {{ promo.isActive ? "Active" : "Disabled" }}
                  </span>
                </td>
                <td class="p-4 text-right">
                  <button
                    type="button"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold border transition-colors cursor-pointer"
                    :class="
                      promo.isActive
                        ? 'border-rose-200 text-rose-600 hover:bg-rose-50'
                        : 'border-emerald-200 text-emerald-600 hover:bg-emerald-50'
                    "
                    @click="handleTogglePromo(promo)"
                  >
                    {{ promo.isActive ? "Pause" : "Activate" }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 2: Upsell & Frequently Bought Together -->
    <div v-else-if="activeTab === 'upsells'" class="space-y-4">
      <div
        class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4"
      >
        <div>
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
            Frequently Bought Together Pairings
          </h2>
          <p class="text-xs text-slate-500">
            Automatic bundle recommendations shown on product pages to increase
            basket size
          </p>
        </div>

        <div class="divide-y divide-slate-100">
          <div
            v-for="(pair, idx) in upsellPairs"
            :key="idx"
            class="py-4 flex flex-col md:flex-row md:items-center justify-between gap-4"
          >
            <div class="space-y-1">
              <span class="text-xs font-extrabold text-slate-900 block"
                >Primary: {{ pair.primaryProduct }}</span
              >
              <div class="flex items-center gap-2 flex-wrap">
                <span class="text-[11px] text-slate-400"
                  >Paired Recommendations:</span
                >
                <span
                  v-for="(rec, rIdx) in pair.recommendations"
                  :key="rIdx"
                  class="px-2.5 py-0.5 rounded-md bg-indigo-50 text-indigo-700 font-semibold text-[11px] border border-indigo-200"
                >
                  + {{ rec }}
                </span>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <span
                class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200"
              >
                {{ pair.conversionLift }} Basket Lift
              </span>
              <button
                type="button"
                class="text-xs font-bold text-slate-500 hover:text-slate-800"
              >
                Edit Pair &rarr;
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 3: Abandoned Cart Recovery -->
    <div v-else-if="activeTab === 'abandoned'" class="space-y-4">
      <div
        class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden"
      >
        <div class="p-6 border-b border-slate-100">
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
            Incomplete Checkouts &amp; Cart Recovery
          </h2>
          <p class="text-xs text-slate-500">
            Trigger personalized reminder emails with one-click recover discount
            tokens
          </p>
        </div>

        <div class="divide-y divide-slate-100">
          <div
            v-for="cart in adminStore.abandonedCarts"
            :key="cart.id"
            class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4"
          >
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="font-bold text-slate-900 text-xs">{{
                  cart.customerName
                }}</span>
                <span class="text-[11px] text-slate-400 font-mono"
                  >({{ cart.customerEmail }})</span
                >
              </div>
              <p class="text-xs text-slate-600">
                {{ cart.itemsCount }} item(s) left in cart &bull; Value:
                <span class="font-bold text-slate-900">{{
                  formatCurrency(cart.totalValue)
                }}</span>
              </p>
              <span class="text-[11px] text-slate-400"
                >Last activity: {{ cart.lastActive }}</span
              >
            </div>

            <div class="flex items-center gap-3">
              <button
                v-if="!cart.reminderSent"
                type="button"
                class="px-3.5 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-2xs cursor-pointer flex items-center gap-1.5"
                @click="adminStore.sendAbandonedCartReminder(cart.id)"
              >
                <span>💌</span>
                <span>Send 10% Recovery Email</span>
              </button>
              <span
                v-else
                class="text-xs font-bold text-emerald-600 bg-emerald-50 px-3 py-1 rounded-xl border border-emerald-200"
              >
                ✓ Reminder Email Dispatched
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 4: Search Engine Optimization (SEO) Controls -->
    <div v-else class="space-y-4">
      <div
        class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6"
      >
        <div>
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
            Search Engine Metadata (SEO)
          </h2>
          <p class="text-xs text-slate-500">
            Configure how search engines like Google and Bing crawl and display
            your store
          </p>
        </div>

        <div class="space-y-4 max-w-2xl">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1"
              >Global Meta Title (Max 60 chars)</label
            >
            <input
              v-model="globalMetaTitle"
              type="text"
              maxlength="70"
              class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900"
            />
            <span class="text-[10px] text-slate-400 mt-0.5 block"
              >{{ globalMetaTitle.length }} / 60 optimal chars</span
            >
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1"
              >Global Meta Description (Max 160 chars)</label
            >
            <textarea
              v-model="globalMetaDescription"
              rows="3"
              maxlength="170"
              class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900"
            ></textarea>
            <span class="text-[10px] text-slate-400 mt-0.5 block"
              >{{ globalMetaDescription.length }} / 160 optimal chars</span
            >
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1"
              >Canonical Storefront Handle / Domain</label
            >
            <input
              v-model="homeSlug"
              type="text"
              class="w-full text-xs font-mono p-3 rounded-xl border border-slate-200 bg-slate-50"
            />
          </div>

          <!-- Google SERP Live Snippet Preview -->
          <div
            class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-1"
          >
            <span
              class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1"
              >Google SERP Live Preview:</span
            >
            <div class="text-[11px] text-slate-500 font-mono">
              https://{{ homeSlug }}
            </div>
            <h4
              class="text-sm font-bold text-blue-700 hover:underline cursor-pointer leading-snug"
            >
              {{ globalMetaTitle }}
            </h4>
            <p class="text-xs text-slate-600 leading-relaxed">
              {{ globalMetaDescription }}
            </p>
          </div>

          <div class="flex items-center gap-3 pt-2">
            <button
              type="button"
              class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs cursor-pointer shadow-xs"
              @click="saveSeo"
            >
              Save SEO Metadata
            </button>
            <span
              v-if="isSeoSaved"
              class="text-xs font-bold text-emerald-600 animate-fade-in"
            >
              ✓ Meta tags updated across all routes
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Promo Modal -->
    <teleport to="body">
      <div
        v-if="isCreatePromoOpen"
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
                Create New Coupon Engine Rule
              </h3>
              <p class="text-xs text-slate-500">
                Set discount code, percentage, and usage limits
              </p>
            </div>
            <button
              type="button"
              class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center cursor-pointer"
              @click="isCreatePromoOpen = false"
            >
              ✕
            </button>
          </div>

          <div class="space-y-3 text-xs">
            <div>
              <label class="block font-bold text-slate-700 mb-1"
                >Promo Code (Uppercase)</label
              >
              <input
                v-model="newCode"
                type="text"
                placeholder="e.g. FLASH25"
                class="w-full p-2.5 rounded-xl border border-slate-300 uppercase font-mono font-bold"
              />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-bold text-slate-700 mb-1"
                  >Discount Type</label
                >
                <select
                  v-model="newType"
                  class="w-full p-2.5 rounded-xl border border-slate-300 bg-white"
                >
                  <option value="percentage">Percentage (%)</option>
                  <option value="fixed">Fixed Cash (₱)</option>
                  <option value="shipping">Free Shipping</option>
                </select>
              </div>
              <div>
                <label class="block font-bold text-slate-700 mb-1"
                  >Value Amount</label
                >
                <input
                  v-model.number="newValue"
                  type="number"
                  class="w-full p-2.5 rounded-xl border border-slate-300 font-bold"
                />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-bold text-slate-700 mb-1"
                  >Total Usage Limit</label
                >
                <input
                  v-model.number="newLimit"
                  type="number"
                  class="w-full p-2.5 rounded-xl border border-slate-300"
                />
              </div>
              <div>
                <label class="block font-bold text-slate-700 mb-1"
                  >Expiration Date</label
                >
                <input
                  v-model="newExpiry"
                  type="date"
                  class="w-full p-2.5 rounded-xl border border-slate-300"
                />
              </div>
            </div>
          </div>

          <div class="flex gap-3 pt-3">
            <button
              type="button"
              class="flex-1 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs"
              @click="isCreatePromoOpen = false"
            >
              Cancel
            </button>
            <button
              type="button"
              class="flex-1 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs"
              @click="handleCreatePromo"
            >
              Publish Coupon
            </button>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>
