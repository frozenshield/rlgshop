<script setup lang="ts">
import { ref, computed } from 'vue'
import type { ToyProduct } from '@/shared/types/toy.types'
import { TCG_SERIES_DATA } from '@/shared/constants/categories.data'
import { formatCurrency, formatAgeGroup } from '@/shared/utils/currency.util'
import { useCartStore } from '@/modules/cart/cart.store'
import { useWishlistStore } from '@/modules/wishlist/wishlist.store'
import BaseBadge from '@/shared/components/BaseBadge.vue'
import BaseRating from '@/shared/components/BaseRating.vue'
import BaseButton from '@/shared/components/BaseButton.vue'

interface Props {
  toy: ToyProduct | null
  isOpen: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

const cartStore = useCartStore()
const wishlistStore = useWishlistStore()

const quantity = ref(1)
const activeImage = ref('')

const tcgInfo = computed(() => {
  if (props.toy?.category === 'tcg' && props.toy.tcgSeries) {
    return TCG_SERIES_DATA.find((s) => s.id === props.toy.tcgSeries)
  }
  return null
})

const currentImage = computed(() => {
  if (activeImage.value) return activeImage.value
  return props.toy?.imageUrl || ''
})

const incrementQty = () => {
  if (props.toy && quantity.value < props.toy.stock) {
    quantity.value++
  }
}

const decrementQty = () => {
  if (quantity.value > 1) {
    quantity.value--
  }
}

const handleAddToCart = () => {
  if (props.toy) {
    cartStore.addItem(props.toy, quantity.value)
    emit('close')
  }
}

const handleToggleWishlist = () => {
  if (props.toy) {
    wishlistStore.toggleFavorite(props.toy.id)
  }
}
</script>

<template>
  <teleport to="body">
    <transition
      enter-active-class="transition-opacity duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen && toy"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 overflow-y-auto font-display"
        @click="emit('close')"
      >
        <div
          class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl relative border border-slate-200 transform transition-all my-8 max-h-[90vh] overflow-y-auto"
          @click.stop
        >
          <!-- Close Button -->
          <button
            type="button"
            class="absolute top-4 right-4 w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors cursor-pointer z-10"
            @click="emit('close')"
          >
            ✕
          </button>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
            <!-- Left: Toy Image & Gallery -->
            <div class="space-y-3">
              <div class="aspect-square rounded-2xl overflow-hidden bg-slate-100/70 border border-slate-200 relative">
                <img
                  :src="currentImage"
                  :alt="toy.name"
                  class="w-full h-full object-cover"
                />
                <div class="absolute top-3 left-3 flex flex-col gap-1">
                  <span v-if="toy.isBestSeller" class="text-[10px] font-bold px-2.5 py-1 rounded-md bg-amber-500 text-slate-950 uppercase tracking-wider shadow-2xs">
                    🔥 Best Seller
                  </span>
                  <span v-if="toy.discountPercent" class="text-[10px] font-bold px-2.5 py-1 rounded-md bg-rose-600 text-white uppercase tracking-wider shadow-2xs">
                    -{{ toy.discountPercent }}% OFF
                  </span>
                </div>
              </div>

              <!-- Gallery thumbs if available -->
              <div v-if="toy.galleryImages && toy.galleryImages.length > 1" class="flex gap-2">
                <img
                  v-for="(img, idx) in toy.galleryImages"
                  :key="idx"
                  :src="img"
                  class="w-14 h-14 rounded-xl object-cover border-2 cursor-pointer transition-all"
                  :class="currentImage === img ? 'border-rose-600 scale-105' : 'border-slate-200 opacity-70 hover:opacity-100'"
                  @click="activeImage = img"
                />
              </div>
            </div>

            <!-- Right: Product Info & Actions -->
            <div class="space-y-4">
              <div>
                <div class="flex items-center gap-2 mb-1 flex-wrap">
                  <span
                    v-if="tcgInfo"
                    class="inline-flex items-center gap-1 font-bold text-xs px-2.5 py-0.5 rounded-md border shadow-2xs"
                    :class="tcgInfo.bgClass"
                  >
                    <span>{{ tcgInfo.icon }}</span>
                    <span>{{ tcgInfo.name }}</span>
                  </span>
                  <span v-else class="text-xs font-bold text-rose-600 uppercase tracking-wider">
                    {{ toy.brand }}
                  </span>
                  <BaseBadge variant="secondary" size="sm">
                    {{ formatAgeGroup(toy.ageGroup) }}
                  </BaseBadge>
                </div>

                <h2 class="text-xl sm:text-2xl font-black text-slate-900 leading-tight">
                  {{ toy.name }}
                </h2>

                <div class="mt-2">
                  <BaseRating :rating="toy.rating" :review-count="toy.reviewCount" size="md" />
                </div>
              </div>

              <!-- Price Box -->
              <div class="flex items-baseline gap-3 p-3.5 rounded-2xl bg-slate-50 border border-slate-200">
                <span class="text-2xl font-black text-slate-900">
                  {{ formatCurrency(toy.price) }}
                </span>
                <span
                  v-if="toy.originalPrice"
                  class="text-sm text-slate-400 line-through"
                >
                  {{ formatCurrency(toy.originalPrice) }}
                </span>
                <span v-if="toy.stock > 0" class="text-xs font-bold text-emerald-600 ml-auto">
                  ✓ In Stock ({{ toy.stock }} available)
                </span>
              </div>

              <!-- Description -->
              <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-normal">
                {{ toy.description }}
              </p>

              <!-- Key Features List -->
              <div v-if="toy.features && toy.features.length > 0">
                <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                  <span>✨</span>
                  <span>Collector Specifications:</span>
                </h4>
                <ul class="text-xs text-slate-600 space-y-1">
                  <li v-for="(feat, i) in toy.features" :key="i" class="flex items-center gap-2">
                    <span class="text-emerald-500 font-bold">✓</span>
                    <span>{{ feat }}</span>
                  </li>
                </ul>
              </div>

              <!-- Safety Warning if any -->
              <div
                v-if="toy.safetyWarning"
                class="p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-[11px] text-amber-800 font-semibold flex items-center gap-2"
              >
                <span>⚠️</span>
                <span>{{ toy.safetyWarning }}</span>
              </div>

              <!-- Quantity Selector & Add to Cart Action -->
              <div class="pt-2 flex items-center gap-3">
                <div class="flex items-center border border-slate-200 rounded-2xl p-1 bg-slate-50">
                  <button
                    type="button"
                    class="w-8 h-8 rounded-xl bg-white shadow-xs font-bold text-slate-700 hover:bg-slate-100 flex items-center justify-center cursor-pointer transition-colors"
                    @click="decrementQty"
                  >
                    -
                  </button>
                  <span class="w-10 text-center font-bold text-sm text-slate-800">
                    {{ quantity }}
                  </span>
                  <button
                    type="button"
                    class="w-8 h-8 rounded-xl bg-white shadow-xs font-bold text-slate-700 hover:bg-slate-100 flex items-center justify-center cursor-pointer transition-colors"
                    @click="incrementQty"
                  >
                    +
                  </button>
                </div>

                <button
                  type="button"
                  class="flex-1 py-3 px-6 bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm rounded-2xl shadow-sm transition-all active:scale-95 cursor-pointer flex items-center justify-center gap-2"
                  @click="handleAddToCart"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                  </svg>
                  <span>Add to Cart</span>
                </button>

                <button
                  type="button"
                  class="w-11 h-11 rounded-2xl border border-slate-200 flex items-center justify-center hover:bg-rose-50 hover:text-rose-600 transition-colors cursor-pointer"
                  :title="wishlistStore.isFavorite(toy.id) ? 'Remove from Wishlist' : 'Add to Wishlist'"
                  @click="handleToggleWishlist"
                >
                  <svg
                    class="w-5 h-5 transition-colors"
                    :class="wishlistStore.isFavorite(toy.id) ? 'text-rose-600 fill-rose-600' : 'text-slate-400'"
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
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>
