<script setup lang="ts">
import { computed } from 'vue'
import type { ToyProduct } from '@/shared/types/toy.types'
import { TCG_SERIES_DATA } from '@/shared/constants/categories.data'
import { formatCurrency, formatAgeGroup } from '@/shared/utils/currency.util'
import { useCartStore } from '@/modules/cart/cart.store'
import { useWishlistStore } from '@/modules/wishlist/wishlist.store'
import BaseBadge from '@/shared/components/BaseBadge.vue'
import BaseRating from '@/shared/components/BaseRating.vue'
import BaseButton from '@/shared/components/BaseButton.vue'

interface Props {
  toy: ToyProduct
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'quick-view', toy: ToyProduct): void
}>()

const cartStore = useCartStore()
const wishlistStore = useWishlistStore()

const tcgInfo = computed(() => {
  if (props.toy.category === 'tcg' && props.toy.tcgSeries) {
    return TCG_SERIES_DATA.find((s) => s.id === props.toy.tcgSeries)
  }
  return null
})

const handleAddToCart = () => {
  cartStore.addItem(props.toy, 1)
}

const handleToggleWishlist = () => {
  wishlistStore.toggleFavorite(props.toy.id)
}
</script>

<template>
  <div
    class="bg-white rounded-3xl p-3.5 sm:p-4 border border-red-100/90 poke-card-shadow poke-card-hover flex flex-col justify-between relative group font-display"
  >
    <!-- Top Image Container -->
    <div class="relative w-full aspect-square rounded-2xl overflow-hidden bg-amber-50/50 mb-3.5">
      <img
        :src="toy.imageUrl"
        :alt="toy.name"
        class="w-full h-full object-cover group-hover:scale-108 transition-transform duration-500 ease-out"
        loading="lazy"
      />

      <!-- Floating Badges Top Left -->
      <div class="absolute top-2.5 left-2.5 flex flex-col gap-1 z-10">
        <BaseBadge v-if="toy.isBestSeller" variant="warning" size="sm">
          🔥 Top Catch
        </BaseBadge>
        <BaseBadge v-else-if="toy.isNewArrival" variant="mint" size="sm">
          ✨ New Drop
        </BaseBadge>
        <BaseBadge v-if="toy.discountPercent" variant="primary" size="sm">
          -{{ toy.discountPercent }}%
        </BaseBadge>
      </div>

      <!-- Floating Wishlist Heart Top Right -->
      <button
        type="button"
        class="absolute top-2.5 right-2.5 w-9 h-9 rounded-full bg-white/90 backdrop-blur-xs flex items-center justify-center text-slate-400 hover:text-red-600 hover:scale-110 shadow-sm transition-all z-10 cursor-pointer"
        :title="wishlistStore.isFavorite(toy.id) ? 'Remove from Wishlist' : 'Add to Trainer Wishlist'"
        @click.stop="handleToggleWishlist"
      >
        <svg
          class="w-5 h-5 transition-colors"
          :class="wishlistStore.isFavorite(toy.id) ? 'text-red-600 fill-red-600' : ''"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
          />
        </svg>
      </button>

      <!-- Quick View Overlay on Desktop Hover -->
      <div
        class="absolute inset-x-0 bottom-3 flex justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200 px-4 z-10 pointer-events-none group-hover:pointer-events-auto"
      >
        <button
          type="button"
          class="bg-slate-900/85 hover:bg-slate-900 backdrop-blur-xs text-white text-xs font-bold py-2 px-4 rounded-xl shadow-lg transition-transform active:scale-95 cursor-pointer flex items-center gap-1.5"
          @click.stop="emit('quick-view', toy)"
        >
          <span>🔍</span>
          <span>Pokédex View</span>
        </button>
      </div>
    </div>

    <!-- Product Info Details -->
    <div class="space-y-2 flex-1 flex flex-col justify-between">
      <div>
        <div class="flex items-center justify-between gap-1 text-[11px] font-bold text-slate-400">
          <span
            v-if="tcgInfo"
            class="inline-flex items-center gap-1 font-extrabold text-[10px] px-2 py-0.5 rounded-full border shadow-2xs"
            :class="tcgInfo.bgClass"
          >
            <span>{{ tcgInfo.icon }}</span>
            <span>{{ tcgInfo.shortName }} TCG</span>
          </span>
          <span v-else class="text-red-600 uppercase tracking-wider font-extrabold truncate">
            {{ toy.pokemonType || toy.brand }}
          </span>
          <span class="bg-amber-100/70 text-amber-900 px-2 py-0.5 rounded-md font-semibold whitespace-nowrap">
            {{ formatAgeGroup(toy.ageGroup) }}
          </span>
        </div>

        <h3
          class="text-sm sm:text-base font-bold text-slate-800 line-clamp-2 mt-1 group-hover:text-red-600 transition-colors cursor-pointer"
          @click="emit('quick-view', toy)"
        >
          {{ toy.name }}
        </h3>

        <!-- Rating Stars -->
        <div class="mt-1.5">
          <BaseRating :rating="toy.rating" :review-count="toy.reviewCount" size="sm" />
        </div>
      </div>

      <!-- Price & Catch Action -->
      <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2 mt-auto">
        <div class="flex flex-col">
          <span class="text-base sm:text-lg font-extrabold text-slate-900 leading-none">
            {{ formatCurrency(toy.price) }}
          </span>
          <span
            v-if="toy.originalPrice"
            class="text-[11px] text-slate-400 line-through mt-0.5"
          >
            {{ formatCurrency(toy.originalPrice) }}
          </span>
        </div>

        <BaseButton
          variant="primary"
          size="sm"
          class="bounce-on-hover"
          @click="handleAddToCart"
        >
          <template #prefix>
            <span class="text-xs">🔴</span>
          </template>
          Catch
        </BaseButton>
      </div>
    </div>
  </div>
</template>
