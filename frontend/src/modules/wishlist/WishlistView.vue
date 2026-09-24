<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useWishlistComposable } from './wishlist.composable'
import { formatCurrency, formatAgeGroup } from '@/shared/utils/currency.util'
import BaseButton from '@/shared/components/BaseButton.vue'
import BaseRating from '@/shared/components/BaseRating.vue'
import BaseBadge from '@/shared/components/BaseBadge.vue'

const router = useRouter()
const {
  favoriteToys,
  count,
  toggleFavorite,
  clearWishlist,
  moveAllToCart,
  addSingleToCart,
} = useWishlistComposable()
</script>

<template>
  <div class="min-h-screen py-10 font-display">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
      <!-- Wishlist Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
          <button
            type="button"
            class="text-xs font-bold text-slate-500 hover:text-slate-900 flex items-center gap-1.5 mb-1 cursor-pointer transition-colors"
            @click="router.back()"
          >
            &larr; Back
          </button>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">
              Collector Wishlist
            </h1>
            <span class="bg-rose-100 text-rose-700 text-xs font-bold px-3 py-1 rounded-full border border-rose-200">
              {{ count }} saved items
            </span>
          </div>
        </div>

        <div v-if="count > 0" class="flex items-center gap-3">
          <button
            type="button"
            class="text-xs font-bold text-slate-400 hover:text-rose-600 cursor-pointer"
            @click="clearWishlist"
          >
            Clear All
          </button>
          <BaseButton variant="primary" size="md" @click="moveAllToCart">
            Move All to Cart 🛒
          </BaseButton>
        </div>
      </div>

      <!-- Empty State -->
      <div
        v-if="count === 0"
        class="bg-white rounded-3xl p-16 text-center border border-slate-200 shadow-sm max-w-lg mx-auto space-y-4 my-8"
      >
        <div class="w-20 h-20 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center text-3xl mx-auto border border-rose-100">
          🤍
        </div>
        <h3 class="text-xl font-bold text-slate-900">Your Wishlist is Empty</h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto">
          Save your favorite TCG booster boxes, model kits, and scale figures here to track prices and availability!
        </p>
        <BaseButton variant="primary" size="md" @click="router.push('/catalog')">
          Browse Products 🔍
        </BaseButton>
      </div>

      <!-- Wishlist Toys Grid -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <div
          v-for="toy in favoriteToys"
          :key="toy.id"
          class="bg-white rounded-3xl p-4 border border-slate-200 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between"
        >
          <div class="relative w-full aspect-square rounded-2xl overflow-hidden bg-slate-50 mb-3">
            <img :src="toy.imageUrl" :alt="toy.name" class="w-full h-full object-cover" />
            <button
              type="button"
              class="absolute top-2.5 right-2.5 w-8 h-8 rounded-full bg-white/90 text-slate-400 hover:text-rose-600 flex items-center justify-center shadow-xs cursor-pointer hover:scale-110 transition-transform"
              title="Remove"
              @click="toggleFavorite(toy.id)"
            >
              ✕
            </button>
          </div>

          <div class="space-y-2 flex-1 flex flex-col justify-between">
            <div>
              <div class="flex items-center justify-between text-[11px] font-bold text-slate-400">
                <span class="text-rose-600 uppercase">{{ toy.brand }}</span>
                <BaseBadge variant="secondary" size="sm">
                  {{ formatAgeGroup(toy.ageGroup) }}
                </BaseBadge>
              </div>

              <h4 class="text-sm font-bold text-slate-800 line-clamp-2 mt-1">
                {{ toy.name }}
              </h4>

              <div class="mt-2">
                <BaseRating :rating="toy.rating" :review-count="toy.reviewCount" size="sm" />
              </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2 mt-3">
              <div>
                <span class="text-base font-extrabold text-slate-900">
                  {{ formatCurrency(toy.price) }}
                </span>
                <span v-if="toy.originalPrice" class="text-xs text-slate-400 line-through ml-1.5">
                  {{ formatCurrency(toy.originalPrice) }}
                </span>
              </div>

              <BaseButton variant="primary" size="sm" @click="addSingleToCart(toy)">
                Add to Cart
              </BaseButton>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
