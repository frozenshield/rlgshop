<script setup lang="ts">
import { computed } from "vue";
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
          <div
            class="bg-slate-950/80 rounded-2xl p-5 border border-slate-800 shadow-md space-y-3"
          >
            <div class="flex text-amber-400 text-sm">⭐⭐⭐⭐⭐</div>
            <p class="text-xs text-slate-300 leading-relaxed font-medium">
              "The Scarlet &amp; Violet 151 Elite Trainer Box and OP-05 booster
              box arrived in factory-sealed mint condition. The double-boxed
              packaging protected the corners perfectly!"
            </p>
            <div class="flex items-center gap-2.5 pt-1">
              <span
                class="w-8 h-8 rounded-full bg-indigo-950 text-indigo-300 border border-indigo-500/30 font-bold text-xs flex items-center justify-center"
                >MT</span
              >
              <div>
                <h5 class="text-xs font-bold text-white">Marcus T.</h5>
                <p class="text-[10px] text-slate-400">TCG Sealed Collector</p>
              </div>
            </div>
          </div>

          <div
            class="bg-slate-950/80 rounded-2xl p-5 border border-slate-800 shadow-md space-y-3"
          >
            <div class="flex text-amber-400 text-sm">⭐⭐⭐⭐⭐</div>
            <p class="text-xs text-slate-300 leading-relaxed font-medium">
              "Got the Luffy Gear 5 figure and Gunpla kits. 100% authentic
              licensed goods with pristine runners and flawless paint
              applications. Best hobby shop online!"
            </p>
            <div class="flex items-center gap-2.5 pt-1">
              <span
                class="w-8 h-8 rounded-full bg-indigo-950 text-indigo-300 border border-indigo-500/30 font-bold text-xs flex items-center justify-center"
                >ER</span
              >
              <div>
                <h5 class="text-xs font-bold text-white">Elena R.</h5>
                <p class="text-[10px] text-slate-400">
                  Gunpla &amp; Figure Hobbyist
                </p>
              </div>
            </div>
          </div>

          <div
            class="bg-slate-950/80 rounded-2xl p-5 border border-slate-800 shadow-md space-y-3"
          >
            <div class="flex text-amber-400 text-sm">⭐⭐⭐⭐⭐</div>
            <p class="text-xs text-slate-300 leading-relaxed font-medium">
              "The Hobby Matcher quiz recommended the ideal deck sleeves and
              Charizard collection box for a tournament gift. Fast delivery and
              stellar customer service!"
            </p>
            <div class="flex items-center gap-2.5 pt-1">
              <span
                class="w-8 h-8 rounded-full bg-indigo-950 text-indigo-300 border border-indigo-500/30 font-bold text-xs flex items-center justify-center"
                >DC</span
              >
              <div>
                <h5 class="text-xs font-bold text-white">David C.</h5>
                <p class="text-[10px] text-slate-400">
                  Competitive Card Player
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
