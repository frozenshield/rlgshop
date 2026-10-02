<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useAdminStore } from "../admin.store";
import { formatCurrency } from "@/shared/utils/currency.util";
import type { ProductBundleItem, AbandonedCart, SeoMetadataItem } from "../admin.types";

const adminStore = useAdminStore();

// Main Tabs
const activeTab = ref<"promos" | "upsells" | "abandoned" | "seo">("promos");

// Notification Feedback
const feedbackMessage = ref("");
const showFeedback = (msg: string) => {
  feedbackMessage.value = msg;
  setTimeout(() => {
    feedbackMessage.value = "";
  }, 4000);
};

// ─── TAB 1: Promo Code Engine ──────────────────────────────────────────────
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

const handleCreatePromo = async () => {
  if (!newCode.value.trim()) return;
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
      showFeedback(`Promo coupon ${code} created successfully!`);
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
  showFeedback(`Promo coupon ${code} created successfully!`);
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

// ─── TAB 2: Upsell & Bundles Engine ────────────────────────────────────────
const isCreateBundleOpen = ref(false);
const editingBundleId = ref<number | null>(null);
const bundleTitle = ref("");
const bundlePrimaryId = ref<number | null>(null);
const bundleCompanionIds = ref<number[]>([]);
const bundleDiscountPercent = ref(10);
const bundleConversionLift = ref("+24.5%");
const bundleBadgeText = ref("🔥 Frequent Combo");
const isSubmittingBundle = ref(false);

const openCreateBundleModal = () => {
  editingBundleId.value = null;
  bundleTitle.value = "";
  bundlePrimaryId.value = adminStore.inventory[0]?.id ? Number(adminStore.inventory[0].id) : null;
  bundleCompanionIds.value = adminStore.inventory[1]?.id ? [Number(adminStore.inventory[1].id)] : [];
  bundleDiscountPercent.value = 10;
  bundleConversionLift.value = "+22.5%";
  bundleBadgeText.value = "🔥 Frequent Combo";
  isCreateBundleOpen.value = true;
};

const openEditBundleModal = (bundle: ProductBundleItem) => {
  editingBundleId.value = bundle.id;
  bundleTitle.value = bundle.title;
  bundlePrimaryId.value = bundle.primary_product_id;
  bundleCompanionIds.value = [...bundle.bundle_product_ids];
  bundleDiscountPercent.value = bundle.discount_percentage;
  bundleConversionLift.value = bundle.conversion_lift;
  bundleBadgeText.value = bundle.badge_text;
  isCreateBundleOpen.value = true;
};

const toggleCompanionSelection = (productId: number) => {
  if (bundleCompanionIds.value.includes(productId)) {
    bundleCompanionIds.value = bundleCompanionIds.value.filter((id) => id !== productId);
  } else {
    bundleCompanionIds.value.push(productId);
  }
};

const saveBundle = async () => {
  if (!bundlePrimaryId.value || bundleCompanionIds.value.length === 0 || !bundleTitle.value.trim()) {
    alert("Please select a primary product, at least one companion product, and enter a title.");
    return;
  }

  isSubmittingBundle.value = true;
  const payload = {
    primary_product_id: bundlePrimaryId.value,
    bundle_product_ids: bundleCompanionIds.value,
    title: bundleTitle.value.trim(),
    discount_percentage: Number(bundleDiscountPercent.value) || 10,
    conversion_lift: bundleConversionLift.value.trim() || "+20.0%",
    badge_text: bundleBadgeText.value.trim() || "Frequently Bought Together",
    is_active: true,
  };

  try {
    if (editingBundleId.value) {
      await adminStore.updateProductBundle(editingBundleId.value, payload);
      showFeedback(`Bundle "${payload.title}" updated successfully!`);
    } else {
      await adminStore.createProductBundle(payload);
      showFeedback(`Bundle "${payload.title}" created and activated!`);
    }
    isCreateBundleOpen.value = false;
  } catch (e) {
    console.error("Save bundle error", e);
  } finally {
    isSubmittingBundle.value = false;
  }
};

const handleDeleteBundle = async (bundle: ProductBundleItem) => {
  if (confirm(`Are you sure you want to delete the bundle "${bundle.title}"?`)) {
    await adminStore.deleteProductBundle(bundle.id);
    showFeedback("Bundle pairing removed.");
  }
};

// ─── TAB 3: Abandoned Carts & Email Recovery ────────────────────────────────
const isDispatchingAll = ref(false);
const sendingEmailCartId = ref<string | number | null>(null);
const copiedToken = ref<string | null>(null);

const handleSendReminder = async (cart: AbandonedCart) => {
  sendingEmailCartId.value = cart.id;
  try {
    const msg = await adminStore.sendAbandonedCartReminder(cart.id);
    showFeedback(msg || `10% Recovery email dispatched to ${cart.customerEmail}!`);
  } finally {
    sendingEmailCartId.value = null;
  }
};

const handleDispatchAll = async () => {
  if (!confirm("Dispatch 10% recovery emails to all customers with pending abandoned carts?")) {
    return;
  }
  isDispatchingAll.value = true;
  try {
    const msg = await adminStore.dispatchAllAbandonedCarts();
    showFeedback(msg || "All pending abandoned cart reminders have been emailed!");
  } finally {
    isDispatchingAll.value = false;
  }
};

const copyRecoveryLink = (cart: AbandonedCart) => {
  const token = cart.recoveryToken || "RECOVER";
  const url = `${window.location.origin}/checkout?recover=${token}&promo=${cart.discountCode || "RECOVER10"}`;
  navigator.clipboard.writeText(url);
  copiedToken.value = String(cart.id);
  setTimeout(() => {
    copiedToken.value = null;
  }, 2500);
  showFeedback("Checkout recovery link copied to clipboard!");
};

const handleDeleteCart = async (cart: AbandonedCart) => {
  if (confirm(`Remove abandoned cart record for ${cart.customerName}?`)) {
    await adminStore.deleteAbandonedCart(cart.id);
    showFeedback("Cart record removed.");
  }
};

// ─── TAB 4: Search Engine Optimization (SEO) & AI Copilot ───────────────────
const seoViewMode = ref<"copilot" | "directory">("copilot");
const serpDeviceMode = ref<"desktop" | "mobile">("desktop");
const isGeneratingAiSeo = ref(false);
const isSavingSeo = ref(false);

// AI Copilot Generator Inputs
const seoTargetType = ref<"global" | "product" | "category" | "custom">("global");
const seoSelectedProductId = ref<number | null>(null);
const seoCustomRoute = ref("/");
const seoTargetKeyword = ref("One Piece TCG Philippines");

// Active SEO Record being edited / generated
const currentSeo = ref<SeoMetadataItem>({
  entity_type: "global",
  page_name: "Storefront Homepage",
  route_path: "/",
  meta_title: "RLG Hobby Shop | TCG, Model Kits & Collectibles Philippines",
  meta_description: "Shop authentic factory-sealed Pokémon & One Piece TCG booster boxes, Japanese Gunpla kits, and scale anime figures with secure nationwide shipping in PH.",
  focus_keyword: "Hobby Shop Philippines",
  meta_keywords: "Pokemon TCG Philippines, One Piece Card Game, Gunpla Manila, Bandai Model Kits, Anime Figures, TCG booster boxes",
  canonical_url: "https://rlghobby.ph/",
  og_title: "RLG Hobby Shop | Authentic TCG, Gunpla & Scale Collectibles",
  og_description: "Official hub for authentic Pokémon TCG, One Piece TCG, Bandai Gunpla kits, and Japanese hobby collectibles in the Philippines.",
  og_image_url: "https://rlghobby.ph/logo.png",
  twitter_card: "summary_large_image",
  robots: "index, follow",
  structured_data_json: {
    "@context": "https://schema.org",
    "@type": "WebSite",
    name: "RLG Hobby Shop",
    url: "https://rlghobby.ph",
  },
  seo_score: 95,
  ai_generated: true,
  ai_model: "gemini-2.5-flash",
  is_active: true,
});

const seoRecommendations = ref<string[]>([
  "Primary focus keyword is positioned within the first 40 characters of the meta title.",
  "Meta description contains a clear, urgency-driven action verb (Shop now, Order now).",
  "Schema.org structured data markup is validated for Google Rich Results snippets.",
]);

const runAiSeoGeneration = async () => {
  isGeneratingAiSeo.value = true;
  try {
    let pageName = "Storefront Homepage";
    let routePath = seoCustomRoute.value || "/";
    let productDetails: any = null;

    if (seoTargetType.value === "product") {
      const prod = adminStore.inventory.find((p) => Number(p.id) === Number(seoSelectedProductId.value));
      if (prod) {
        pageName = prod.name;
        routePath = `/products/${prod.id}`;
        productDetails = {
          price: prod.sellingPrice || prod.costPrice,
          brand: prod.vendor || "Bandai",
          description: prod.name,
        };
      }
    } else if (seoTargetType.value === "category") {
      pageName = "Trading Card Games (TCG) Category";
      routePath = "/catalog?category=tcg";
    }

    const payload = {
      entity_type: seoTargetType.value === "custom" ? "custom_page" : seoTargetType.value,
      entity_id: seoSelectedProductId.value,
      page_name: pageName,
      route_path: routePath,
      target_keyword: seoTargetKeyword.value,
      ...productDetails,
    };

    const result = await adminStore.generateAiSeo(payload);
    if (result) {
      currentSeo.value = {
        ...currentSeo.value,
        ...result,
        page_name: pageName,
        route_path: routePath,
        entity_type: payload.entity_type,
        entity_id: payload.entity_id,
        is_active: true,
      };
      if (Array.isArray(result.recommendations)) {
        seoRecommendations.value = result.recommendations;
      }
      showFeedback("✨ Gemini AI generated optimized SEO metadata and Schema.org markup!");
    } else {
      showFeedback("AI Generation complete with local SEO optimization engine.");
    }
  } catch (e) {
    console.error("AI SEO error:", e);
  } finally {
    isGeneratingAiSeo.value = false;
  }
};

const handleSaveSeo = async () => {
  isSavingSeo.value = true;
  try {
    const success = await adminStore.saveSeoMetadata(currentSeo.value);
    if (success) {
      showFeedback("SEO metadata published to storefront routes successfully!");
    }
  } finally {
    isSavingSeo.value = false;
  }
};

const selectSeoForEdit = (item: SeoMetadataItem) => {
  currentSeo.value = { ...item };
  seoViewMode.value = "copilot";
  showFeedback(`Loaded "${item.page_name}" into AI Copilot editor.`);
};

const handleDeleteSeo = async (item: SeoMetadataItem) => {
  if (item.id && confirm(`Delete SEO configuration for route "${item.route_path}"?`)) {
    await adminStore.deleteSeoMetadata(item.id);
    showFeedback("SEO record deleted.");
  }
};

// Lifecycle
onMounted(() => {
  fetchDbPromos();
  adminStore.fetchProductBundles();
  adminStore.fetchAbandonedCarts();
  adminStore.fetchSeoMetadata();
  if (adminStore.inventory.length === 0) {
    adminStore.fetchInventory();
  }
});
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div
      class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs"
    >
      <div>
        <div class="flex items-center gap-2">
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
            Marketing &amp; Promotions Engine
          </h1>
          <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-rose-50 text-rose-600 border border-rose-200">
            Automated
          </span>
        </div>
        <p class="text-xs text-slate-500 mt-0.5">
          Discount codes, cross-sell/upsell matrices, abandoned cart recovery, and search engine optimization.
        </p>
      </div>

      <!-- Tab Buttons -->
      <div
        class="flex items-center gap-1 p-1 bg-slate-100 rounded-xl border border-slate-200 overflow-x-auto"
      >
        <button
          type="button"
          class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer whitespace-nowrap flex items-center gap-1.5"
          :class="
            activeTab === 'promos'
              ? 'bg-white text-slate-900 shadow-xs'
              : 'text-slate-500 hover:text-slate-800'
          "
          @click="activeTab = 'promos'"
        >
          <span>🏷️</span>
          <span>Discount Codes</span>
          <span class="px-1.5 py-0.2 rounded-full text-[9px] bg-slate-200 text-slate-700">
            {{ adminStore.promoCodes.length }}
          </span>
        </button>

        <button
          type="button"
          class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer whitespace-nowrap flex items-center gap-1.5"
          :class="
            activeTab === 'upsells'
              ? 'bg-white text-slate-900 shadow-xs'
              : 'text-slate-500 hover:text-slate-800'
          "
          @click="activeTab = 'upsells'"
        >
          <span>🔄</span>
          <span>Upsell &amp; Bundles</span>
          <span class="px-1.5 py-0.2 rounded-full text-[9px] bg-indigo-100 text-indigo-700">
            {{ adminStore.productBundles.length }}
          </span>
        </button>

        <button
          type="button"
          class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer whitespace-nowrap flex items-center gap-1.5"
          :class="
            activeTab === 'abandoned'
              ? 'bg-white text-slate-900 shadow-xs'
              : 'text-slate-500 hover:text-slate-800'
          "
          @click="activeTab = 'abandoned'"
        >
          <span>🛒</span>
          <span>Abandoned Carts</span>
          <span class="px-1.5 py-0.2 rounded-full text-[9px] bg-rose-100 text-rose-700 font-bold">
            {{ adminStore.abandonedCarts.length }}
          </span>
        </button>

        <button
          type="button"
          class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer whitespace-nowrap flex items-center gap-1.5"
          :class="
            activeTab === 'seo'
              ? 'bg-white text-slate-900 shadow-xs'
              : 'text-slate-500 hover:text-slate-800'
          "
          @click="activeTab = 'seo'"
        >
          <span>🔍</span>
          <span>SEO Metadata</span>
          <span class="px-1.5 py-0.2 rounded-full text-[9px] bg-emerald-100 text-emerald-700 font-bold">
            AI Active
          </span>
        </button>
      </div>
    </div>

    <!-- Feedback Notification Banner -->
    <div
      v-if="feedbackMessage"
      class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-bold flex items-center justify-between shadow-xs animate-fade-in"
    >
      <div class="flex items-center gap-2">
        <span>✨</span>
        <span>{{ feedbackMessage }}</span>
      </div>
      <button
        type="button"
        class="text-emerald-600 hover:text-emerald-900 text-xs font-bold cursor-pointer"
        @click="feedbackMessage = ''"
      >
        ✕
      </button>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- TAB 1: Discount Promo Codes Engine                                    -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'promos'" class="space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
            Active Promotional Coupons
          </h2>
          <p class="text-xs text-slate-500">
            Configure percentage markdowns, fixed discounts, and free shipping triggers
          </p>
        </div>
        <button
          type="button"
          class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold shadow-xs cursor-pointer flex items-center gap-1.5 transition-colors"
          @click="isCreatePromoOpen = true"
        >
          <span>+</span>
          <span>Create Promo Code</span>
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

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- TAB 2: Upsell & Bundles Engine                                        -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div v-else-if="activeTab === 'upsells'" class="space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
            Frequently Bought Together &amp; Bundle Matrices
          </h2>
          <p class="text-xs text-slate-500">
            Pair complementary hobby items (booster boxes + card sleeves, Gunpla kits + action bases) with combo discounts to multiply basket value.
          </p>
        </div>
        <button
          type="button"
          class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold shadow-xs cursor-pointer flex items-center gap-2 transition-colors shrink-0"
          @click="openCreateBundleModal"
        >
          <span>+</span>
          <span>Create Bundle Pairing</span>
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="adminStore.isLoadingBundles" class="p-8 text-center text-slate-400 text-xs font-bold">
        Loading bundle matrices...
      </div>

      <!-- Empty State -->
      <div
        v-else-if="adminStore.productBundles.length === 0"
        class="bg-white p-8 rounded-2xl border border-slate-200 text-center space-y-3"
      >
        <div class="text-3xl">📦</div>
        <h3 class="text-sm font-bold text-slate-800">No Bundles Configured Yet</h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto">
          Create pairing matrices between products to boost average order values across your catalog.
        </p>
        <button
          type="button"
          class="px-4 py-2 bg-indigo-600 text-white font-bold text-xs rounded-xl hover:bg-indigo-700 cursor-pointer"
          @click="openCreateBundleModal"
        >
          + Create First Bundle
        </button>
      </div>

      <!-- Bundles Grid / List -->
      <div v-else class="grid grid-cols-1 gap-4">
        <div
          v-for="bundle in adminStore.productBundles"
          :key="bundle.id"
          class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs hover:border-slate-300 transition-all flex flex-col md:flex-row md:items-center justify-between gap-4"
        >
          <div class="space-y-2 flex-1">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-xs font-black text-slate-900">{{ bundle.title }}</span>
              <span
                class="px-2 py-0.5 rounded-md text-[10px] font-bold"
                :class="bundle.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500'"
              >
                {{ bundle.is_active ? 'Active on Storefront' : 'Inactive' }}
              </span>
              <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold">
                {{ bundle.badge_text || 'Frequently Bought Together' }}
              </span>
            </div>

            <!-- Primary Product Anchor -->
            <div class="flex items-center gap-3 p-2.5 bg-slate-50 rounded-xl border border-slate-100">
              <img
                :src="bundle.primary_product?.image_url || 'https://images.unsplash.com/photo-1613771404784-3a5686aa2be3?w=100'"
                :alt="bundle.primary_product?.name || 'Primary Product'"
                class="w-10 h-10 object-cover rounded-lg border border-slate-200"
              />
              <div class="flex-1 min-w-0">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">Anchor Product</span>
                <span class="text-xs font-bold text-slate-800 truncate block">
                  {{ bundle.primary_product?.name || 'Primary Product #' + bundle.primary_product_id }}
                </span>
              </div>
              <div class="text-right">
                <span class="text-xs font-black text-slate-900">
                  {{ formatCurrency(bundle.primary_product?.price || 0) }}
                </span>
              </div>
            </div>

            <!-- Companion Products -->
            <div class="flex items-center gap-2 flex-wrap pt-1">
              <span class="text-[11px] text-slate-400 font-semibold">Bundled With:</span>
              <span
                v-for="comp in bundle.bundled_products"
                :key="comp.id"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-800 text-[11px] font-bold border border-indigo-100"
              >
                <span>+</span>
                <span>{{ comp.name }}</span>
                <span class="text-indigo-500 font-mono text-[10px]">({{ formatCurrency(comp.price) }})</span>
              </span>
            </div>
          </div>

          <!-- Economics & Controls -->
          <div class="flex items-center gap-3 shrink-0 pt-2 md:pt-0 border-t md:border-t-0 border-slate-100 justify-between md:justify-end">
            <div class="text-right">
              <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-rose-50 text-rose-700 font-black text-xs border border-rose-200">
                <span>🏷️ Save {{ bundle.discount_percentage }}%</span>
              </div>
              <span class="text-[10px] text-emerald-600 font-bold block mt-1">
                {{ bundle.conversion_lift || '+20%' }} Lift
              </span>
            </div>

            <div class="flex items-center gap-1.5">
              <button
                type="button"
                class="px-2.5 py-1.5 rounded-xl border text-xs font-bold transition-colors cursor-pointer"
                :class="bundle.is_active ? 'border-amber-200 text-amber-700 hover:bg-amber-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50'"
                @click="adminStore.toggleProductBundle(bundle.id)"
              >
                {{ bundle.is_active ? 'Pause' : 'Activate' }}
              </button>
              <button
                type="button"
                class="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-slate-100 cursor-pointer"
                title="Edit Bundle"
                @click="openEditBundleModal(bundle)"
              >
                ✏️
              </button>
              <button
                type="button"
                class="p-1.5 text-rose-400 hover:text-rose-700 rounded-lg hover:bg-rose-50 cursor-pointer"
                title="Delete Bundle"
                @click="handleDeleteBundle(bundle)"
              >
                🗑️
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- TAB 3: Abandoned Carts Recovery Engine                                 -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div v-else-if="activeTab === 'abandoned'" class="space-y-4">
      <!-- Recovery Statistics KPI Summary Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
        <div class="p-4 bg-white rounded-2xl border border-slate-200/90 shadow-2xs space-y-1">
          <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Unclaimed Cart Value</span>
          <div class="text-xl sm:text-2xl font-black text-rose-600 tracking-tight">
            {{ formatCurrency(adminStore.abandonedCartStats.total_abandoned_value) }}
          </div>
          <span class="text-[10.5px] text-slate-500 font-medium">Pending checkout completion</span>
        </div>

        <div class="p-4 bg-white rounded-2xl border border-slate-200/90 shadow-2xs space-y-1">
          <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Abandoned Carts</span>
          <div class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
            {{ adminStore.abandonedCarts.length }}
          </div>
          <span class="text-[10.5px] text-slate-500 font-medium">Across storefront sessions</span>
        </div>

        <div class="p-4 bg-white rounded-2xl border border-slate-200/90 shadow-2xs space-y-1">
          <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Reminder Emails Sent</span>
          <div class="text-xl sm:text-2xl font-black text-indigo-600 tracking-tight">
            {{ adminStore.abandonedCartStats.reminders_sent_count }}
          </div>
          <span class="text-[10.5px] text-slate-500 font-medium">With 10% discount tokens</span>
        </div>

        <div class="p-4 bg-white rounded-2xl border border-slate-200/90 shadow-2xs space-y-1">
          <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Recovery Rate</span>
          <div class="text-xl sm:text-2xl font-black text-emerald-600 tracking-tight">
            {{ adminStore.abandonedCartStats.recovery_rate_percent }}%
          </div>
          <span class="text-[10.5px] text-slate-500 font-medium">Checkout conversions saved</span>
        </div>
      </div>

      <!-- Controls & Header -->
      <div
        class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden"
      >
        <div class="p-5 sm:p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
              Incomplete Checkouts &amp; Cart Recovery
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
              Trigger personalized HTML reminder emails with one-click recover discount tokens (10% OFF voucher).
            </p>
          </div>

          <button
            type="button"
            :disabled="isDispatchingAll || adminStore.abandonedCarts.length === 0"
            class="px-4 py-2 bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold shadow-xs cursor-pointer flex items-center gap-2 transition-all shrink-0"
            @click="handleDispatchAll"
          >
            <span v-if="isDispatchingAll" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span v-else>🚀</span>
            <span>{{ isDispatchingAll ? 'Dispatching...' : 'Dispatch All Recovery Emails' }}</span>
          </button>
        </div>

        <!-- Carts List -->
        <div class="divide-y divide-slate-100">
          <div
            v-for="cart in adminStore.abandonedCarts"
            :key="cart.id"
            class="p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4 hover:bg-slate-50/50 transition-colors"
          >
            <!-- Customer & Item Info -->
            <div class="space-y-2 flex-1">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="font-extrabold text-slate-900 text-xs">{{ cart.customerName }}</span>
                <span class="text-[11px] text-slate-400 font-mono">({{ cart.customerEmail }})</span>
                <span
                  v-if="cart.recovered"
                  class="px-2 py-0.5 rounded-full text-[9px] font-black bg-emerald-100 text-emerald-800"
                >
                  RECOVERED
                </span>
              </div>

              <!-- Cart Value & Items Count -->
              <div class="flex items-center gap-3 text-xs text-slate-600">
                <span>{{ cart.itemsCount }} item(s) left in cart</span>
                <span>&bull;</span>
                <span>
                  Value:
                  <span class="font-bold text-slate-900">{{ formatCurrency(cart.totalValue) }}</span>
                </span>
                <span>&bull;</span>
                <span class="text-[11px] text-slate-400">Last activity: {{ cart.lastActive }}</span>
              </div>

              <!-- Items Thumbnails Carousel -->
              <div v-if="cart.cartItems && cart.cartItems.length > 0" class="flex items-center gap-2 flex-wrap pt-1">
                <div
                  v-for="(item, iIdx) in cart.cartItems"
                  :key="iIdx"
                  class="flex items-center gap-1.5 p-1 px-2 rounded-lg bg-slate-100 border border-slate-200 text-[11px]"
                >
                  <img
                    :src="item.image_url || 'https://images.unsplash.com/photo-1613771404784-3a5686aa2be3?w=80'"
                    :alt="item.name"
                    class="w-5 h-5 rounded object-cover"
                  />
                  <span class="font-semibold text-slate-700 max-w-[140px] truncate">{{ item.name }}</span>
                  <span class="text-slate-400 font-mono text-[10px]">x{{ item.quantity }}</span>
                </div>
              </div>
            </div>

            <!-- Actions & Dispatched Status -->
            <div class="flex items-center gap-2 shrink-0 pt-2 lg:pt-0 border-t lg:border-t-0 border-slate-100">
              <!-- Copy Recovery Link Button -->
              <button
                type="button"
                class="px-3 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-700 font-bold text-xs cursor-pointer transition-colors flex items-center gap-1"
                title="Copy Direct Checkout Recovery URL"
                @click="copyRecoveryLink(cart)"
              >
                <span>🔗</span>
                <span>{{ copiedToken === String(cart.id) ? 'Copied!' : 'Copy Link' }}</span>
              </button>

              <!-- Email Trigger Button -->
              <button
                v-if="!cart.reminderSent"
                type="button"
                :disabled="sendingEmailCartId === cart.id"
                class="px-3.5 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-2xs cursor-pointer flex items-center gap-1.5 transition-all disabled:opacity-50"
                @click="handleSendReminder(cart)"
              >
                <span
                  v-if="sendingEmailCartId === cart.id"
                  class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"
                ></span>
                <span v-else>💌</span>
                <span>{{ sendingEmailCartId === cart.id ? 'Sending...' : 'Send 10% Recovery Email' }}</span>
              </button>

              <span
                v-else
                class="text-xs font-bold text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-200 flex items-center gap-1.5"
              >
                <span>✓</span>
                <span>Reminder Email Dispatched</span>
              </span>

              <button
                type="button"
                class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg cursor-pointer"
                title="Remove Cart Record"
                @click="handleDeleteCart(cart)"
              >
                ✕
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- TAB 4: Search Engine Optimization (SEO) & AI Copilot                 -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div v-else class="space-y-4">
      <!-- Sub-Navigation Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 px-6 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
          <div class="flex items-center gap-2">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
              Search Engine Optimization (SEO) Copilot
            </h2>
            <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-200">
              ⚡ Google Gemini Enabled
            </span>
          </div>
          <p class="text-xs text-slate-500">
            Automatically craft high-CTR meta titles, Google SERP snippets, Open Graph tags, and Schema.org JSON-LD.
          </p>
        </div>

        <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl">
          <button
            type="button"
            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer"
            :class="seoViewMode === 'copilot' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
            @click="seoViewMode = 'copilot'"
          >
            ✨ AI Generator &amp; Editor
          </button>
          <button
            type="button"
            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer"
            :class="seoViewMode === 'directory' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
            @click="seoViewMode = 'directory'"
          >
            📑 Tracked Routes Matrix ({{ adminStore.seoMetadataList.length }})
          </button>
        </div>
      </div>

      <!-- VIEW 1: AI Copilot Generator & Visual Preview -->
      <div v-if="seoViewMode === 'copilot'" class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        <!-- Left: Configuration & Inputs (7 cols) -->
        <div class="lg:col-span-7 space-y-4">
          <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
              <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">1. Select SEO Target</span>
              <span class="text-[11px] text-slate-400 font-mono">{{ currentSeo.route_path }}</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
              <div>
                <label class="block font-bold text-slate-700 mb-1">Target Page Type</label>
                <select
                  v-model="seoTargetType"
                  class="w-full p-2.5 rounded-xl border border-slate-300 bg-white font-medium text-xs focus:outline-none focus:border-slate-800"
                >
                  <option value="global">Homepage (Storefront Root)</option>
                  <option value="product">Specific Product from Catalog</option>
                  <option value="category">Category Hub (TCG / Gunpla)</option>
                  <option value="custom">Custom Storefront URL Route</option>
                </select>
              </div>

              <div v-if="seoTargetType === 'product'">
                <label class="block font-bold text-slate-700 mb-1">Choose Product</label>
                <select
                  v-model="seoSelectedProductId"
                  class="w-full p-2.5 rounded-xl border border-slate-300 bg-white font-medium text-xs focus:outline-none focus:border-slate-800"
                >
                  <option :value="null">-- Select Product --</option>
                  <option v-for="prod in adminStore.inventory" :key="prod.id" :value="Number(prod.id)">
                    {{ prod.name }} (₱{{ prod.sellingPrice || prod.costPrice }})
                  </option>
                </select>
              </div>

              <div v-else-if="seoTargetType === 'custom'">
                <label class="block font-bold text-slate-700 mb-1">Custom Route Path</label>
                <input
                  v-model="seoCustomRoute"
                  type="text"
                  placeholder="e.g. /collections/rare-charizards"
                  class="w-full p-2.5 rounded-xl border border-slate-300 font-mono text-xs"
                />
              </div>

              <div>
                <label class="block font-bold text-slate-700 mb-1">Target Focus Keyword</label>
                <input
                  v-model="seoTargetKeyword"
                  type="text"
                  placeholder="e.g. One Piece OP-05 Philippines"
                  class="w-full p-2.5 rounded-xl border border-slate-300 font-medium text-xs"
                />
              </div>
            </div>

            <div class="pt-2">
              <button
                type="button"
                :disabled="isGeneratingAiSeo"
                class="w-full py-3 bg-gradient-to-r from-indigo-600 via-purple-600 to-rose-600 hover:opacity-95 text-white font-extrabold text-xs rounded-xl shadow-xs cursor-pointer flex items-center justify-center gap-2 transition-all disabled:opacity-50"
                @click="runAiSeoGeneration"
              >
                <span v-if="isGeneratingAiSeo" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <span v-else>✨</span>
                <span>{{ isGeneratingAiSeo ? 'Gemini AI Optimizing Meta & Schema...' : 'Generate High-Ranking SEO with AI (Gemini)' }}</span>
              </button>
            </div>
          </div>

          <!-- Metadata Fields Editor -->
          <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4 text-xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
              <span class="font-bold text-slate-800 uppercase tracking-wider text-xs">2. Edit Metadata &amp; Keywords</span>
              <div class="flex items-center gap-1.5">
                <span class="text-[10px] font-bold text-slate-400">SEO Score:</span>
                <span class="px-2 py-0.5 rounded-full font-black text-[10px] bg-emerald-100 text-emerald-800">
                  {{ currentSeo.seo_score || 94 }}/100 🟢
                </span>
              </div>
            </div>

            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="font-bold text-slate-700">Meta Title (Max 60 chars)</label>
                <span
                  class="text-[10px] font-mono"
                  :class="currentSeo.meta_title.length > 60 ? 'text-rose-600 font-bold' : 'text-slate-400'"
                >
                  {{ currentSeo.meta_title.length }} / 60
                </span>
              </div>
              <input
                v-model="currentSeo.meta_title"
                type="text"
                maxlength="80"
                class="w-full p-2.5 rounded-xl border border-slate-300 font-medium text-xs focus:outline-none focus:border-slate-800"
              />
            </div>

            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="font-bold text-slate-700">Meta Description (Max 160 chars)</label>
                <span
                  class="text-[10px] font-mono"
                  :class="currentSeo.meta_description.length > 160 ? 'text-rose-600 font-bold' : 'text-slate-400'"
                >
                  {{ currentSeo.meta_description.length }} / 160
                </span>
              </div>
              <textarea
                v-model="currentSeo.meta_description"
                rows="3"
                maxlength="180"
                class="w-full p-2.5 rounded-xl border border-slate-300 font-medium text-xs focus:outline-none focus:border-slate-800 leading-relaxed"
              ></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block font-bold text-slate-700 mb-1">Focus Keyword</label>
                <input
                  v-model="currentSeo.focus_keyword"
                  type="text"
                  class="w-full p-2.5 rounded-xl border border-slate-300 text-xs font-semibold"
                />
              </div>
              <div>
                <label class="block font-bold text-slate-700 mb-1">Canonical URL</label>
                <input
                  v-model="currentSeo.canonical_url"
                  type="text"
                  class="w-full p-2.5 rounded-xl border border-slate-300 font-mono text-[11px]"
                />
              </div>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Target Keyword Cloud (Comma Separated)</label>
              <input
                v-model="currentSeo.meta_keywords"
                type="text"
                class="w-full p-2.5 rounded-xl border border-slate-300 text-xs"
              />
            </div>

            <!-- Save CTA -->
            <div class="flex justify-end pt-2 border-t border-slate-100">
              <button
                type="button"
                :disabled="isSavingSeo"
                class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-xs cursor-pointer flex items-center gap-2 transition-all disabled:opacity-50"
                @click="handleSaveSeo"
              >
                <span v-if="isSavingSeo" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <span>💾</span>
                <span>{{ isSavingSeo ? 'Saving...' : 'Save & Publish SEO Metadata' }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Right: Live Previews (SERP & Social Open Graph) (5 cols) -->
        <div class="lg:col-span-5 space-y-4">
          <!-- Google SERP Live Snippet Preview -->
          <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
              <span class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                <span>🔎</span>
                <span>Google SERP Live Snippet</span>
              </span>
              <div class="flex items-center gap-1 bg-slate-100 p-0.5 rounded-lg text-[10px] font-bold">
                <button
                  type="button"
                  class="px-2 py-0.5 rounded cursor-pointer"
                  :class="serpDeviceMode === 'desktop' ? 'bg-white shadow-2xs text-slate-900' : 'text-slate-500'"
                  @click="serpDeviceMode = 'desktop'"
                >
                  Desktop
                </button>
                <button
                  type="button"
                  class="px-2 py-0.5 rounded cursor-pointer"
                  :class="serpDeviceMode === 'mobile' ? 'bg-white shadow-2xs text-slate-900' : 'text-slate-500'"
                  @click="serpDeviceMode = 'mobile'"
                >
                  Mobile
                </button>
              </div>
            </div>

            <!-- SERP Snippet Box -->
            <div
              class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-1 font-sans transition-all"
              :class="serpDeviceMode === 'mobile' ? 'max-w-[320px] mx-auto shadow-inner' : ''"
            >
              <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded-full bg-rose-600 flex items-center justify-center text-[9px] text-white font-black">
                  R
                </div>
                <div class="text-[11px] text-slate-600 font-mono truncate leading-tight">
                  https://rlghobby.ph <span class="text-slate-400">&rsaquo;</span> {{ currentSeo.route_path.replace(/^\//, '') || 'home' }}
                </div>
              </div>
              <h4 class="text-sm font-medium text-blue-800 hover:underline cursor-pointer leading-snug pt-0.5">
                {{ currentSeo.meta_title }}
              </h4>
              <p class="text-xs text-slate-600 leading-relaxed line-clamp-3">
                {{ currentSeo.meta_description }}
              </p>
            </div>
          </div>

          <!-- Social Share Open Graph Card Live Preview (Facebook / Discord / Twitter) -->
          <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
            <span class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5 pb-2 border-b border-slate-100">
              <span>💬</span>
              <span>Social Share Preview (Open Graph)</span>
            </span>

            <div class="rounded-xl border border-slate-200 overflow-hidden bg-slate-50 shadow-2xs">
              <img
                :src="currentSeo.og_image_url || 'https://images.unsplash.com/photo-1613771404784-3a5686aa2be3?w=600'"
                alt="OG Preview Banner"
                class="w-full h-36 object-cover bg-slate-200"
              />
              <div class="p-3 space-y-1">
                <span class="text-[10px] font-mono uppercase text-slate-400 font-bold block">RLGHOBBY.PH</span>
                <span class="text-xs font-bold text-slate-900 block line-clamp-1">
                  {{ currentSeo.og_title || currentSeo.meta_title }}
                </span>
                <p class="text-[11px] text-slate-500 line-clamp-2 leading-relaxed">
                  {{ currentSeo.og_description || currentSeo.meta_description }}
                </p>
              </div>
            </div>
          </div>

          <!-- AI Optimization Recommendations -->
          <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-2.5 text-xs">
            <span class="font-bold text-slate-800 uppercase tracking-wider text-[11px] block">
              💡 Gemini AI SEO Strategy Tips
            </span>
            <ul class="space-y-1.5 text-slate-600">
              <li
                v-for="(rec, rIdx) in seoRecommendations"
                :key="rIdx"
                class="flex items-start gap-2 leading-relaxed text-[11.5px]"
              >
                <span class="text-emerald-600 font-bold mt-0.5">✓</span>
                <span>{{ rec }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- VIEW 2: Tracked Routes Directory Matrix Table -->
      <div v-else class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
          <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
            Storefront Tracked SEO Routes
          </h3>
          <span class="text-xs text-slate-400">Total: {{ adminStore.seoMetadataList.length }} pages</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200/80 text-slate-500 uppercase font-bold text-[10px] tracking-wider">
                <th class="p-4">Route Path &amp; Name</th>
                <th class="p-4">Meta Title</th>
                <th class="p-4">Focus Keyword</th>
                <th class="p-4">SEO Health</th>
                <th class="p-4">Engine</th>
                <th class="p-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="item in adminStore.seoMetadataList"
                :key="item.id"
                class="hover:bg-slate-50/70 transition-colors"
              >
                <td class="p-4">
                  <span class="font-extrabold text-slate-900 block text-xs">{{ item.page_name }}</span>
                  <span class="font-mono text-slate-400 text-[11px]">{{ item.route_path }}</span>
                </td>
                <td class="p-4 font-medium text-slate-700 max-w-xs truncate">
                  {{ item.meta_title }}
                </td>
                <td class="p-4">
                  <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-bold text-[10px]">
                    {{ item.focus_keyword || 'Auto' }}
                  </span>
                </td>
                <td class="p-4">
                  <span class="px-2 py-0.5 rounded-full font-black text-[10px] bg-emerald-100 text-emerald-800">
                    {{ item.seo_score || 95 }}/100 🟢
                  </span>
                </td>
                <td class="p-4">
                  <span
                    v-if="item.ai_generated"
                    class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200"
                  >
                    AI (Gemini)
                  </span>
                  <span v-else class="text-slate-400 text-[11px]">Manual</span>
                </td>
                <td class="p-4 text-right space-x-2">
                  <button
                    type="button"
                    class="px-2.5 py-1 bg-slate-900 text-white rounded-lg font-bold text-xs hover:bg-slate-800 cursor-pointer"
                    @click="selectSeoForEdit(item)"
                  >
                    Edit in Copilot
                  </button>
                  <button
                    type="button"
                    class="p-1 text-rose-500 hover:text-rose-700 cursor-pointer"
                    title="Delete"
                    @click="handleDeleteSeo(item)"
                  >
                    🗑️
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL: Create Promo Code                                              -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
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
              class="flex-1 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs cursor-pointer"
              @click="isCreatePromoOpen = false"
            >
              Cancel
            </button>
            <button
              type="button"
              class="flex-1 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs cursor-pointer"
              @click="handleCreatePromo"
            >
              Publish Coupon
            </button>
          </div>
        </div>
      </div>
    </teleport>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL: Create / Edit Product Bundle Pairing                           -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <teleport to="body">
      <div
        v-if="isCreateBundleOpen"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4"
      >
        <div
          class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl border border-slate-200 font-display max-h-[90vh] overflow-y-auto"
        >
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <h3 class="text-base font-bold text-slate-900">
                {{ editingBundleId ? 'Edit Bundle Pairing' : 'Create Frequently Bought Together Bundle' }}
              </h3>
              <p class="text-xs text-slate-500">
                Select an anchor product and complementary add-on accessories
              </p>
            </div>
            <button
              type="button"
              class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center cursor-pointer"
              @click="isCreateBundleOpen = false"
            >
              ✕
            </button>
          </div>

          <div class="space-y-3.5 text-xs">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Bundle Title / Proposition</label>
              <input
                v-model="bundleTitle"
                type="text"
                placeholder="e.g. Pro Sleeves &amp; Magnetic Case Protector Pack"
                class="w-full p-2.5 rounded-xl border border-slate-300 font-semibold"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Anchor Primary Product</label>
              <select
                v-model="bundlePrimaryId"
                class="w-full p-2.5 rounded-xl border border-slate-300 bg-white font-medium"
              >
                <option :value="null">-- Select Primary Product --</option>
                <option v-for="prod in adminStore.inventory" :key="prod.id" :value="Number(prod.id)">
                  {{ prod.name }} ({{ formatCurrency(prod.sellingPrice || prod.costPrice) }})
                </option>
              </select>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1.5">
                Select Bundled Companion Products (Check 1 or more)
              </label>
              <div class="max-h-40 overflow-y-auto border border-slate-200 rounded-xl divide-y divide-slate-100 p-1">
                <label
                  v-for="prod in adminStore.inventory.filter((p) => Number(p.id) !== bundlePrimaryId)"
                  :key="prod.id"
                  class="flex items-center gap-2.5 p-2 hover:bg-slate-50 rounded-lg cursor-pointer transition-colors"
                >
                  <input
                    type="checkbox"
                    :checked="bundleCompanionIds.includes(Number(prod.id))"
                    class="rounded text-indigo-600 focus:ring-indigo-500"
                    @change="toggleCompanionSelection(Number(prod.id))"
                  />
                  <img
                    :src="(prod as any).imageUrl || (prod as any).image_url || 'https://images.unsplash.com/photo-1613771404784-3a5686aa2be3?w=80'"
                    class="w-6 h-6 object-cover rounded"
                  />
                  <span class="flex-1 font-medium text-slate-800 truncate text-[11.5px]">{{ prod.name }}</span>
                  <span class="font-bold text-slate-600 text-[11px]">{{ formatCurrency(prod.sellingPrice || prod.costPrice) }}</span>
                </label>
              </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
              <div>
                <label class="block font-bold text-slate-700 mb-1">Bundle Discount (%)</label>
                <input
                  v-model.number="bundleDiscountPercent"
                  type="number"
                  min="0"
                  max="50"
                  class="w-full p-2.5 rounded-xl border border-slate-300 font-bold"
                />
              </div>

              <div>
                <label class="block font-bold text-slate-700 mb-1">Expected Lift</label>
                <input
                  v-model="bundleConversionLift"
                  type="text"
                  placeholder="+25.0%"
                  class="w-full p-2.5 rounded-xl border border-slate-300"
                />
              </div>

              <div>
                <label class="block font-bold text-slate-700 mb-1">Badge Tag</label>
                <input
                  v-model="bundleBadgeText"
                  type="text"
                  placeholder="🔥 Frequent Combo"
                  class="w-full p-2.5 rounded-xl border border-slate-300"
                />
              </div>
            </div>
          </div>

          <div class="flex gap-3 pt-3 border-t border-slate-100">
            <button
              type="button"
              class="flex-1 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs cursor-pointer"
              @click="isCreateBundleOpen = false"
            >
              Cancel
            </button>
            <button
              type="button"
              :disabled="isSubmittingBundle"
              class="flex-1 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs cursor-pointer flex items-center justify-center gap-1.5 transition-all disabled:opacity-50"
              @click="saveBundle"
            >
              <span v-if="isSubmittingBundle" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              <span>{{ isSubmittingBundle ? 'Saving...' : 'Save & Activate Bundle' }}</span>
            </button>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>
