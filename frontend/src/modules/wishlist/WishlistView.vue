<script setup lang="ts">
import { onMounted } from "vue";
import { useRouter } from "vue-router";
import { useWishlistComposable } from "./wishlist.composable";
import { formatCurrency, formatAgeGroup } from "@/shared/utils/currency.util";
import { formatCurrency } from "@/shared/utils/currency.util";
import BaseButton from "@/shared/components/BaseButton.vue";
import BaseRating from "@/shared/components/BaseRating.vue";
import BaseBadge from "@/shared/components/BaseBadge.vue";

const router = useRouter();
const {
  favoriteToys,
  count,
  isLoading,
  isSyncing,
  toggleFavorite,
  clearWishlist,
  moveAllToCart,
  addSingleToCart,
  fetchFavorites,
} = useWishlistComposable();

onMounted(() => {
  fetchFavorites();
});
</script>

<template>
  <div class="min-h-screen py-10 font-display">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
      <!-- Wishlist Header -->
      <div
        class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5"
      >

      <!-- ─── Page Header ──────────────────────────────────────────────────── -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-6">
        <div>
          <button
            type="button"
            class="text-xs font-bold text-slate-400 hover:text-white flex items-center gap-1.5 mb-1 cursor-pointer transition-colors"
            class="text-xs font-bold text-slate-400 hover:text-white flex items-center gap-1.5 mb-2 cursor-pointer transition-colors"
            @click="router.back()"
          >
            &larr; Back
            ← Back
          </button>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white">
              Collector Wishlist
            </h1>
            <div class="w-10 h-10 rounded-xl bg-rose-950/60 border border-rose-500/30 flex items-center justify-center text-xl">
              ❤️
            </div>
            <div>
              <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                My Favourites
              </h1>
              <p class="text-xs text-slate-400 mt-0.5">
                Your curated collector vault
              </p>
            </div>
            <span
              class="bg-indigo-950/70 text-indigo-300 text-xs font-bold px-3 py-1 rounded-full border border-indigo-500/30"
              v-if="count > 0"
              class="bg-rose-950/60 text-rose-300 text-xs font-black px-3 py-1 rounded-full border border-rose-500/30 ml-1"
            >
              {{ count }} saved items
              {{ count }} item{{ count !== 1 ? "s" : "" }}
            </span>
          </div>
        </div>

        <div v-if="count > 0" class="flex items-center gap-3">
        <div v-if="count > 0" class="flex items-center gap-3 flex-shrink-0">
          <button
            type="button"
            class="text-xs font-bold text-slate-400 hover:text-rose-400 cursor-pointer transition-colors"
            class="text-xs font-bold text-slate-400 hover:text-rose-400 cursor-pointer transition-colors px-3 py-2 rounded-lg hover:bg-rose-950/30 border border-transparent hover:border-rose-500/20"
            :disabled="isSyncing"
            @click="clearWishlist"
          >
            Clear All
          </button>
          <BaseButton variant="primary" size="md" @click="moveAllToCart">
            Move All to Cart 🛒
          <BaseButton
            variant="primary"
            size="md"
            :disabled="isSyncing"
            @click="moveAllToCart"
          >
            <span class="flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              Move All to Cart
            </span>
          </BaseButton>
        </div>
      </div>

      <!-- Empty State -->
      <div
        v-if="count === 0"
        class="bg-slate-900/90 rounded-3xl p-16 text-center border border-slate-800 shadow-xl max-w-lg mx-auto space-y-4 my-8"
      >
      <!-- ─── Loading Skeleton ─────────────────────────────────────────────── -->
      <div v-if="isLoading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <div
          class="w-20 h-20 rounded-2xl bg-indigo-950/60 text-indigo-400 flex items-center justify-center text-3xl mx-auto border border-indigo-500/30 shadow-inner"
          v-for="i in 4"
          :key="i"
          class="bg-slate-900/90 rounded-3xl p-4 border border-slate-800 animate-pulse"
        >
          🤍
          <div class="w-full aspect-square rounded-2xl bg-slate-800 mb-4" />
          <div class="h-3 bg-slate-800 rounded-full w-1/3 mb-3" />
          <div class="h-4 bg-slate-800 rounded-full w-4/5 mb-2" />
          <div class="h-4 bg-slate-800 rounded-full w-3/5 mb-4" />
          <div class="h-9 bg-slate-800 rounded-xl" />
        </div>
        <h3 class="text-xl font-bold text-white">Your Wishlist is Empty</h3>
        <p class="text-xs text-slate-400 max-w-sm mx-auto">
          Save your favorite TCG booster boxes, model kits, and scale figures
          here to track prices and availability!
        </p>
      </div>

      <!-- ─── Empty State ───────────────────────────────────────────────────── -->
      <div
        v-else-if="count === 0"
        class="flex flex-col items-center justify-center py-24 text-center space-y-6"
      >
        <div class="relative">
          <div class="w-28 h-28 rounded-3xl bg-slate-900 border border-slate-800 flex items-center justify-center text-5xl shadow-xl">
            🤍
          </div>
          <div class="absolute -top-1 -right-1 w-6 h-6 rounded-full bg-rose-600 border-2 border-[#090d16] flex items-center justify-center text-xs font-black text-white">
            0
          </div>
        </div>
        <div class="space-y-2">
          <h3 class="text-2xl font-extrabold text-white">Your Wishlist is Empty</h3>
          <p class="text-sm text-slate-400 max-w-sm mx-auto leading-relaxed">
            Heart any TCG booster box, gunpla kit, or anime figure
            to save it here and track availability!
          </p>
        </div>
        <BaseButton
          variant="primary"
          size="md"
          @click="router.push('/catalog')"
        >
          Browse Products 🔍
          <span class="flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            Browse Catalog
          </span>
        </BaseButton>
      </div>

      <!-- Wishlist Toys Grid -->
      <!-- ─── Favourites Grid ───────────────────────────────────────────────── -->
      <div
        v-else
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"
      >
        <div
          v-for="toy in favoriteToys"
          :key="toy.id"
          class="bg-slate-900/90 rounded-3xl p-4 border border-slate-800 hover:border-indigo-500/50 shadow-lg shadow-black/30 hover:shadow-indigo-500/10 transition-all flex flex-col justify-between"
          class="group relative bg-slate-900 rounded-3xl border border-slate-800 hover:border-rose-500/40 shadow-lg shadow-black/30 hover:shadow-rose-500/10 transition-all duration-300 flex flex-col overflow-hidden"
        >
          <div
            class="relative w-full aspect-square rounded-2xl overflow-hidden bg-slate-950 mb-3 border border-slate-800/80 flex items-center justify-center p-2"
          >
          <!-- Product Image -->
          <div class="relative w-full aspect-square bg-slate-950 border-b border-slate-800/80 flex items-center justify-center p-3 overflow-hidden">
            <img
              :src="toy.imageUrl"
              :alt="toy.name"
              class="max-w-full max-h-full object-contain"
              :class="{ 'opacity-55 grayscale-[35%]': toy.stock <= 0 }"
              class="max-w-full max-h-full object-contain transition-transform duration-500 group-hover:scale-105"
              :class="{ 'opacity-50 grayscale': toy.stock <= 0 }"
            />
            <span

            <!-- Sold Out Badge -->
            <div
              v-if="toy.stock <= 0"
              class="absolute top-2.5 left-2.5 inline-flex items-center text-[10px] font-black px-2 py-0.5 rounded-md bg-rose-600 text-white uppercase tracking-wider shadow-md shadow-rose-600/40"
              class="absolute inset-0 flex items-center justify-center bg-black/40 backdrop-blur-[2px]"
            >
              🚫 Sold Out
              <span class="bg-rose-600/90 text-white text-xs font-black px-3 py-1.5 rounded-lg uppercase tracking-widest shadow-lg">
                Sold Out
              </span>
            </div>

            <!-- In Stock Badge -->
            <span
              v-else-if="toy.stock <= 5"
              class="absolute top-2.5 left-2.5 inline-flex items-center text-[10px] font-black px-2 py-0.5 rounded-md bg-amber-600/90 text-white uppercase tracking-wider shadow-md"
            >
              Only {{ toy.stock }} left!
            </span>

            <!-- Remove from Wishlist -->
            <button
              type="button"
              class="absolute top-2.5 right-2.5 w-8 h-8 rounded-full bg-slate-900/80 backdrop-blur-sm text-slate-300 hover:text-rose-400 border border-slate-700 flex items-center justify-center shadow-xs cursor-pointer hover:scale-110 transition-transform"
              title="Remove"
              class="absolute top-2.5 right-2.5 w-8 h-8 rounded-full bg-slate-900/80 backdrop-blur-sm text-rose-400 border border-rose-500/30 flex items-center justify-center shadow-sm cursor-pointer hover:bg-rose-600 hover:border-rose-600 hover:text-white hover:scale-110 transition-all duration-200"
              title="Remove from favourites"
              :disabled="isSyncing"
              @click="toggleFavorite(toy.id)"
            >
              ✕
              <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                  d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"
                  clip-rule="evenodd" />
              </svg>
            </button>
          </div>

          <div class="space-y-2 flex-1 flex flex-col justify-between">
            <div>
              <div
                class="flex items-center justify-between text-[11px] font-bold text-slate-400"
              >
                <span
                  class="text-indigo-400 font-extrabold uppercase tracking-wide"
                  >{{ toy.brand }}</span
                >
                <BaseBadge variant="secondary" size="sm">
                  {{ formatAgeGroup(toy.ageGroup) }}
                </BaseBadge>
              </div>
          <!-- Product Info -->
          <div class="flex-1 flex flex-col p-4 space-y-3">
            <!-- Brand & Category -->
            <div class="flex items-center justify-between gap-2">
              <span class="text-[11px] font-black text-rose-400 uppercase tracking-widest truncate">
                {{ toy.brand }}
              </span>
              <span class="text-[10px] font-bold text-slate-500 bg-slate-800 px-2 py-0.5 rounded-full capitalize shrink-0">
                {{ toy.category.replace('-', ' ') }}
              </span>
            </div>

              <h4 class="text-sm font-bold text-white line-clamp-2 mt-1">
                {{ toy.name }}
              </h4>
            <!-- Name -->
            <h4 class="text-sm font-bold text-white line-clamp-2 leading-snug flex-1">
              {{ toy.name }}
            </h4>

              <div class="mt-2">
                <BaseRating
                  :rating="toy.rating"
                  :review-count="toy.reviewCount"
                  size="sm"
                />
              </div>
            </div>

            <div
              class="pt-3 border-t border-slate-800 flex items-center justify-between gap-2 mt-3"
            >
              <div>
            <!-- Price Row -->
            <div class="pt-2 border-t border-slate-800 flex items-center justify-between gap-2">
              <div class="flex items-baseline gap-1.5">
                <span class="text-base font-extrabold text-white font-mono">
                  {{ formatCurrency(toy.price) }}
                </span>
                <span
                  v-if="toy.originalPrice"
                  class="text-xs text-slate-500 line-through ml-1.5 font-mono"
                  v-if="toy.originalPrice && toy.originalPrice > toy.price"
                  class="text-xs text-slate-500 line-through font-mono"
                >
                  {{ formatCurrency(toy.originalPrice) }}
                </span>
              </div>

              <BaseButton
                :variant="toy.stock > 0 ? 'primary' : 'secondary'"
                size="sm"
                :disabled="toy.stock <= 0"
                class="disabled:opacity-50 disabled:cursor-not-allowed"
              <button
                v-if="toy.stock > 0"
                type="button"
                class="flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold px-3 py-1.5 rounded-xl transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed shrink-0"
                :disabled="isSyncing"
                @click="addSingleToCart(toy.id)"
              >
                {{ toy.stock > 0 ? "Add to Cart" : "Sold Out" }}
              </BaseButton>
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Add to Cart
              </button>

              <span
                v-else
                class="text-[11px] font-black text-rose-400 bg-rose-950/50 border border-rose-500/20 px-3 py-1.5 rounded-xl shrink-0"
              >
                Sold Out
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- ─── Syncing Overlay ───────────────────────────────────────────────── -->
      <Transition name="fade">
        <div
          v-if="isSyncing"
          class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-slate-800 border border-slate-700 text-white text-sm font-bold px-5 py-2.5 rounded-full shadow-xl flex items-center gap-2"
        >
          <svg class="w-4 h-4 animate-spin text-rose-400" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
          </svg>
          Syncing favourites…
        </div>
      </Transition>

    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(8px);
}
</style>
