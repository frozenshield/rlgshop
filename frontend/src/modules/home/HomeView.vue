<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useCatalogStore } from "../catalog/catalog.store";
import HeroBanner from "./components/HeroBanner.vue";
import CategoryPills from "./components/CategoryPills.vue";
import PromotionalBanner from "./components/PromotionalBanner.vue";
import ToyCard from "../catalog/components/ToyCard.vue";
import ToyDetailModal from "../catalog/components/ToyDetailModal.vue";
import type { ToyProduct } from "@/shared/types/toy.types";

const emit = defineEmits<{
  (e: "open-advisor"): void;
}>();

const router = useRouter();
const catalogStore = useCatalogStore();

interface StoreReview {
  id: number;
  user_id: number;
  product_id: number;
  stars: number;
  message: string;
  image?: string | null;
  staff_reply?: string | null;
  user?: {
    name?: string;
    email?: string;
    customer_profile?: { segment?: string };
  };
  product?: { name?: string; image_url?: string };
  staff?: { name?: string };
  created_at?: string;
}

const liveReviews = ref<StoreReview[]>([]);
const isReviewsLoading = ref(false);

const fetchLiveReviews = async () => {
  isReviewsLoading.value = true;
  try {
    const res = await fetch("/api/customer-reviews");
    if (res.ok) {
      const json = await res.json();
      if (json.success && Array.isArray(json.data) && json.data.length > 0) {
        liveReviews.value = json.data;
      }
    }
  } catch (e) {
    console.warn("Could not fetch live reviews for home page", e);
  } finally {
    isReviewsLoading.value = false;
  }
};

const defaultReviews: StoreReview[] = [
  {
    id: 1,
    user_id: 3,
    product_id: 1,
    stars: 5,
    message:
      "The Scarlet & Violet 151 Elite Trainer Box and OP-05 booster box arrived in factory-sealed mint condition.",
    image: null,
    staff_reply:
      "Thank you Marcus! We take extra care in packaging all collectible booster boxes.",
    user: { name: "Marcus Tan", customer_profile: { segment: "VIP" } },
    product: { name: "Pokémon TCG 151" },
  },
  {
    id: 2,
    user_id: 4,
    product_id: 1,
    stars: 5,
    message:
      "Fast delivery with J&T Express and very accommodating customer support team. Cards are 100% authentic!",
    image: null,
    staff_reply: "Much appreciated Elena! Enjoy your collection.",
    user: { name: "Elena Reyes", customer_profile: { segment: "Regular" } },
    product: { name: "Japanese TCG Booster" },
  },
  {
    id: 3,
    user_id: 5,
    product_id: 1,
    stars: 4,
    message:
      "Great booster box selection. Looking forward to more Japanese expansions and singles restocks.",
    image: null,
    staff_reply: null,
    user: { name: "David Cruz", customer_profile: { segment: "Regular" } },
    product: { name: "Leafeon GX 012/066 RR" },
  },
];

const displayedReviews = computed<StoreReview[]>(() => {
  return liveReviews.value.length > 0
    ? liveReviews.value.slice(0, 3)
    : defaultReviews;
});

onMounted(() => {
  fetchLiveReviews();
});

// Layer 1: Best Selling Collectibles
const bestSellingToys = computed<ToyProduct[]>(() => {
  return catalogStore.toys.filter((t) => t.isBestSeller).slice(0, 4);
});

// Layer 2: Top Picks / Staff Favorites
const topPicksToys = computed<ToyProduct[]>(() => {
  const bestSellerIds = new Set(bestSellingToys.value.map((t) => t.id));
  const featuredDistinct = catalogStore.toys.filter(
    (t) => t.isFeatured && !bestSellerIds.has(t.id),
  );
  if (featuredDistinct.length >= 4) {
    return featuredDistinct.slice(0, 4);
  }
  return catalogStore.toys
    .filter(
      (t) => (t.isFeatured || t.rating >= 4.8) && !bestSellerIds.has(t.id),
    )
    .slice(0, 4);
});

// Layer 3: New Releases & Fresh Arrivals
const newReleasesToys = computed<ToyProduct[]>(() => {
  const newArrivals = catalogStore.toys.filter((t) => t.isNewArrival);
  if (newArrivals.length >= 4) {
    return newArrivals.slice(0, 4);
  }
  return catalogStore.toys.slice().reverse().slice(0, 4);
});

// Layer 4: TCG Cards Spotlight
const tcgToys = computed<ToyProduct[]>(() => {
  return catalogStore.toys.filter((t) => t.category === "tcg").slice(0, 4);
});

// Layer 5: Gunpla & Anime Scale Figures
const gunplaAndFigures = computed<ToyProduct[]>(() => {
  return catalogStore.toys
    .filter((t) => t.category === "gunpla" || t.category === "anime-figures")
    .slice(0, 4);
});

const handleQuickView = (toy: ToyProduct) => {
  catalogStore.openDetailModal(toy);
};

const goToCatalog = (
  filterType?: "bestseller" | "toppicks" | "newest" | "tcg" | "gunpla",
) => {
  catalogStore.resetFilters();
  if (filterType === "newest") {
    catalogStore.sortBy = "newest";
  } else if (filterType === "toppicks") {
    catalogStore.sortBy = "rating";
  } else if (filterType === "tcg") {
    catalogStore.setCategory("tcg");
  } else if (filterType === "gunpla") {
    catalogStore.setCategory("gunpla");
  }
  router.push("/catalog");
};
</script>

<template>
  <div
    class="min-h-screen py-6 sm:py-8 space-y-12 sm:space-y-16 font-display text-slate-100"
  >
    <div
      class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12 sm:space-y-16"
    >
      <!-- 1. Hero Banner -->
      <HeroBanner @open-advisor="emit('open-advisor')" />

      <!-- 2. Category Explorer Pills (TCG, Gunpla, Anime Figures, Anime Merch) -->
      <CategoryPills />

      <!-- 3. LAYER: Best Selling Collectibles -->
      <section class="space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <div class="flex items-center gap-2.5">
              <span class="text-xl">🔥</span>
              <h2
                class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight"
              >
                Best Selling
              </h2>
              <span
                class="hidden sm:inline-flex text-[11px] font-extrabold uppercase px-2.5 py-0.5 rounded-full bg-amber-400/10 text-amber-400 border border-amber-400/30"
              >
                High Demand
              </span>
            </div>
            <p class="text-xs text-slate-400 font-medium mt-1">
              Top-selling collector favorites, sealed boxes, and fan-voted
              grails
            </p>
          </div>

          <button
            type="button"
            class="text-xs font-bold text-amber-400 hover:text-amber-300 flex items-center gap-1.5 cursor-pointer transition-colors"
            @click="goToCatalog('bestseller')"
          >
            <span>See All Best Sellers</span>
            <span>&rarr;</span>
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <ToyCard
            v-for="toy in bestSellingToys"
            :key="toy.id"
            :toy="toy"
            @quick-view="handleQuickView"
          />
        </div>
      </section>

      <!-- 4. LAYER: Top Picks & Staff Favorites -->
      <section class="space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <div class="flex items-center gap-2.5">
              <span class="text-xl">✨</span>
              <h2
                class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight"
              >
                Top Picks
              </h2>
              <span
                class="hidden sm:inline-flex text-[11px] font-extrabold uppercase px-2.5 py-0.5 rounded-full bg-indigo-950/70 text-indigo-300 border border-indigo-500/30"
              >
                Staff Curated
              </span>
            </div>
            <p class="text-xs text-slate-400 font-medium mt-1">
              Hand-picked gems, premium collectibles, and essential hobbyist
              grails
            </p>
          </div>

          <button
            type="button"
            class="text-xs font-bold text-indigo-400 hover:text-indigo-300 flex items-center gap-1.5 cursor-pointer transition-colors"
            @click="goToCatalog('toppicks')"
          >
            <span>Explore Top Picks</span>
            <span>&rarr;</span>
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <ToyCard
            v-for="toy in topPicksToys"
            :key="toy.id"
            :toy="toy"
            @quick-view="handleQuickView"
          />
        </div>
      </section>

      <!-- 5. LAYER: Flash Sale / Collector Promo Banner -->
      <PromotionalBanner />

      <!-- 6. LAYER: New Releases & Fresh Arrivals -->
      <section class="space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <div class="flex items-center gap-2.5">
              <span class="text-xl">🚀</span>
              <h2
                class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight"
              >
                New Release
              </h2>
              <span
                class="hidden sm:inline-flex text-[11px] font-extrabold uppercase px-2.5 py-0.5 rounded-full bg-emerald-950/70 text-emerald-300 border border-emerald-500/30"
              >
                Just Landed
              </span>
            </div>
            <p class="text-xs text-slate-400 font-medium mt-1">
              Fresh shipments from Japan &amp; official distributors — latest
              card sets &amp; first-run model kits
            </p>
          </div>

          <button
            type="button"
            class="text-xs font-bold text-emerald-400 hover:text-emerald-300 flex items-center gap-1.5 cursor-pointer transition-colors"
            @click="goToCatalog('newest')"
          >
            <span>View All New Releases</span>
            <span>&rarr;</span>
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <ToyCard
            v-for="toy in newReleasesToys"
            :key="toy.id"
            :toy="toy"
            @quick-view="handleQuickView"
          />
        </div>
      </section>

      <!-- 7. LAYER: TCG & Trading Cards Spotlight -->
      <section class="space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <div class="flex items-center gap-2.5">
              <span class="text-xl">🃏</span>
              <h2
                class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight"
              >
                Trading Card Games (TCG) Spotlight
              </h2>
              <span
                class="hidden sm:inline-flex text-[11px] font-extrabold uppercase px-2.5 py-0.5 rounded-full bg-indigo-950/70 text-indigo-300 border border-indigo-500/30"
              >
                Factory Sealed
              </span>
            </div>
            <p class="text-xs text-slate-400 font-medium mt-1">
              Factory-sealed Pokémon, One Piece, Yu-Gi-Oh! and Weiß Schwarz
              booster boxes &amp; ETBs
            </p>
          </div>

          <button
            type="button"
            class="text-xs font-bold text-indigo-400 hover:text-indigo-300 flex items-center gap-1.5 cursor-pointer transition-colors"
            @click="goToCatalog('tcg')"
          >
            <span>Explore All TCG</span>
            <span>&rarr;</span>
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <ToyCard
            v-for="toy in tcgToys"
            :key="toy.id"
            :toy="toy"
            @quick-view="handleQuickView"
          />
        </div>
      </section>

      <!-- 8. LAYER: Gunpla & Japanese Scale Figures -->
      <section class="space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <div class="flex items-center gap-2.5">
              <span class="text-xl">🤖</span>
              <h2
                class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight"
              >
                Gunpla Model Kits &amp; Scale Figures
              </h2>
              <span
                class="hidden sm:inline-flex text-[11px] font-extrabold uppercase px-2.5 py-0.5 rounded-full bg-indigo-950/70 text-indigo-300 border border-indigo-500/30"
              >
                Authentic Bandai &amp; GSC
              </span>
            </div>
            <p class="text-xs text-slate-400 font-medium mt-1">
              Authentic Bandai Spirits RG, MG, HG Gundams &amp; Good Smile
              Company scale statues
            </p>
          </div>

          <button
            type="button"
            class="text-xs font-bold text-amber-400 hover:text-amber-300 flex items-center gap-1.5 cursor-pointer transition-colors"
            @click="goToCatalog('gunpla')"
          >
            <span>Explore Gunpla &amp; Figures</span>
            <span>&rarr;</span>
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <ToyCard
            v-for="toy in gunplaAndFigures"
            :key="toy.id"
            :toy="toy"
            @quick-view="handleQuickView"
          />
        </div>
      </section>

      <!-- 7. LAYER: Verified Collector Reviews Section -->
      <section
        class="bg-slate-900/60 rounded-3xl p-8 sm:p-12 border border-indigo-500/20 shadow-xl space-y-6 text-center backdrop-blur-md"
      >
        <div class="max-w-xl mx-auto space-y-1">
          <span
            class="text-xs font-bold uppercase tracking-wider text-indigo-300 bg-indigo-950/80 px-3 py-1 rounded-full border border-indigo-500/30"
          >
            Verified Hobbyist Reviews
          </span>
          <h3 class="text-2xl sm:text-3xl font-extrabold text-white mt-2">
            Trusted by 10,000+ Collectors &amp; Builders
          </h3>
          <p class="text-xs text-slate-400">
            Read authentic reviews from card players, model kit builders, and
            anime enthusiasts
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-left">
          <!-- Real Reviews from customer_review DB Table -->
          <div
            v-for="(rev, idx) in displayedReviews"
            :key="rev.id"
            class="bg-slate-950/80 rounded-2xl p-5 border border-slate-800 shadow-md space-y-3 flex flex-col justify-between"
          >
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <div class="flex text-amber-400 text-sm tracking-wider">
                  {{ "★".repeat(rev.stars) }}{{ "☆".repeat(5 - rev.stars) }}
                </div>
                <span
                  class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20"
                >
                  Verified Buyer
                </span>
              </div>

              <!-- Product Reviewed Pill -->
              <p
                v-if="rev.product?.name"
                class="text-[11px] font-bold text-indigo-300 truncate"
              >
                Item: {{ rev.product.name }}
              </p>

              <!-- Review Message -->
              <p class="text-xs text-slate-300 leading-relaxed font-medium">
                "{{ rev.message }}"
              </p>

              <!-- Photo Attachment if available -->
              <div v-if="rev.image" class="pt-1">
                <img
                  :src="rev.image"
                  alt="Customer Review Photo"
                  class="w-16 h-16 rounded-xl object-contain bg-slate-900 border border-slate-700 p-1"
                />
              </div>

              <!-- Staff Reply Badge & Text -->
              <div
                v-if="rev.staff_reply"
                class="mt-2 p-2.5 rounded-xl bg-indigo-950/50 border border-indigo-500/30 text-[11px] space-y-1"
              >
                <div
                  class="font-extrabold text-[10px] text-indigo-300 uppercase tracking-wider flex items-center gap-1"
                >
                  <span>💬</span>
                  <span>Staff Response:</span>
                </div>
                <p class="text-slate-300 text-[11px] italic">
                  "{{ rev.staff_reply }}"
                </p>
              </div>
            </div>

            <!-- Customer Avatar & Name -->
            <div
              class="flex items-center gap-2.5 pt-2 border-t border-slate-800/80 mt-auto"
            >
              <span
                class="w-8 h-8 rounded-full bg-indigo-950 text-indigo-300 border border-indigo-500/30 font-bold text-xs flex items-center justify-center flex-shrink-0"
              >
                {{
                  rev.user?.name
                    ? rev.user.name.slice(0, 2).toUpperCase()
                    : "CO"
                }}
              </span>
              <div class="min-w-0">
                <h5 class="text-xs font-bold text-white truncate">
                  {{ rev.user?.name || "Collector" }}
                </h5>
                <p class="text-[10px] text-slate-400">
                  {{
                    rev.user?.customer_profile?.segment
                      ? `${rev.user.customer_profile.segment} Member`
                      : "Verified Collector"
                  }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Quick View Modal -->
      <ToyDetailModal
        :toy="catalogStore.selectedToyForModal"
        :is-open="catalogStore.isDetailModalOpen"
        @close="catalogStore.closeDetailModal"
      />
    </div>
  </div>
</template>
