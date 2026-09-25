<script setup lang="ts">
import { computed } from "vue";
import type { ToyProduct } from "@/shared/types/toy.types";
import { TCG_SERIES_DATA } from "@/shared/constants/categories.data";
import { formatCurrency, formatAgeGroup } from "@/shared/utils/currency.util";
import { useCartStore } from "@/modules/cart/cart.store";
import { useWishlistStore } from "@/modules/wishlist/wishlist.store";
import BaseBadge from "@/shared/components/BaseBadge.vue";
import BaseRating from "@/shared/components/BaseRating.vue";
import BaseButton from "@/shared/components/BaseButton.vue";

interface Props {
  toy: ToyProduct;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  (e: "quick-view", toy: ToyProduct): void;
}>();

const cartStore = useCartStore();
const wishlistStore = useWishlistStore();

const tcgInfo = computed(() => {
  if (props.toy.category === "tcg" && props.toy.tcgSeries) {
    return TCG_SERIES_DATA.find((s) => s.id === props.toy.tcgSeries);
  }
  return null;
});

const handleAddToCart = () => {
  cartStore.addItem(props.toy, 1);
};

const handleToggleWishlist = () => {
  wishlistStore.toggleFavorite(props.toy.id);
};
</script>

<template>
  <div
    class="bg-slate-900/90 rounded-2xl p-4 border border-slate-800 hover:border-indigo-500/50 hobby-card-shadow hobby-card-hover flex flex-col justify-between relative group font-display backdrop-blur-xs transition-all duration-300"
  >
    <!-- Top Image Container -->
    <div
      class="relative w-full aspect-square rounded-xl overflow-hidden bg-slate-950/80 border border-slate-800/80 mb-3.5"
    >
      <img
        :src="toy.imageUrl"
        :alt="toy.name"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out"
        loading="lazy"
      />

      <!-- Floating Badges Top Left -->
      <div class="absolute top-2.5 left-2.5 flex flex-col gap-1 z-10">
        <span
          v-if="toy.isBestSeller"
          class="inline-flex items-center text-[10px] font-black px-2 py-0.5 rounded-md bg-amber-400 text-slate-950 uppercase tracking-wider shadow-md shadow-amber-400/30"
        >
          🔥 Best Seller
        </span>
        <span
          v-else-if="toy.isNewArrival"
          class="inline-flex items-center text-[10px] font-bold px-2 py-0.5 rounded-md bg-indigo-600 text-white uppercase tracking-wider shadow-sm shadow-indigo-600/30"
        >
          ✨ New
        </span>
        <span
          v-if="toy.discountPercent"
          class="inline-flex items-center text-[10px] font-bold px-2 py-0.5 rounded-md bg-rose-600 text-white uppercase tracking-wider shadow-sm"
        >
          -{{ toy.discountPercent }}%
        </span>
      </div>

      <!-- Floating Wishlist Heart Top Right -->
      <button
        type="button"
        class="absolute top-2.5 right-2.5 w-8 h-8 rounded-full bg-slate-900/90 backdrop-blur-md flex items-center justify-center text-slate-400 hover:text-amber-400 hover:scale-110 shadow-md transition-all z-10 cursor-pointer border border-slate-700"
        :title="
          wishlistStore.isFavorite(toy.id)
            ? 'Remove from Wishlist'
            : 'Add to Wishlist'
        "
        @click.stop="handleToggleWishlist"
      >
        <svg
          class="w-4 h-4 transition-colors"
          :class="
            wishlistStore.isFavorite(toy.id)
              ? 'text-amber-400 fill-amber-400'
              : ''
          "
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
          class="bg-slate-900/95 hover:bg-slate-800 border border-slate-700 backdrop-blur-md text-slate-200 text-xs font-bold py-2 px-3.5 rounded-xl shadow-lg transition-transform active:scale-95 cursor-pointer flex items-center gap-1.5"
          @click.stop="emit('quick-view', toy)"
        >
          <span>🔍</span>
          <span>Quick View</span>
        </button>
      </div>
    </div>

    <!-- Product Info Details -->
    <div class="space-y-2 flex-1 flex flex-col justify-between">
      <div>
        <div
          class="flex items-center justify-between gap-1 text-[11px] font-semibold text-slate-400"
        >
          <span
            v-if="tcgInfo"
            class="inline-flex items-center gap-1 font-bold text-[10px] px-2 py-0.5 rounded-md border border-indigo-500/30 bg-indigo-950/60 text-indigo-300 shadow-2xs"
          >
            <span>{{ tcgInfo.icon }}</span>
            <span>{{ tcgInfo.shortName }}</span>
          </span>
          <span
            v-else
            class="text-indigo-400 uppercase tracking-wider font-bold truncate text-[10px]"
          >
            {{ toy.brand }}
          </span>
          <div class="flex items-center gap-1.5 shrink-0">
            <span
              v-if="toy.condition"
              class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-slate-800 text-amber-300 border border-slate-700"
            >
              {{ toy.condition }}
            </span>
            <span class="text-slate-400 text-[10px] font-medium">
              {{ toy.stock > 0 ? "In Stock" : "Out of Stock" }}
            </span>
          </div>
        </div>

        <h3
          class="text-sm font-bold text-white line-clamp-2 mt-1 group-hover:text-amber-400 transition-colors cursor-pointer"
          @click="emit('quick-view', toy)"
        >
          {{ toy.name }}
        </h3>

        <!-- Rating Stars -->
        <div class="mt-1.5">
          <BaseRating
            :rating="toy.rating"
            :review-count="toy.reviewCount"
            size="sm"
          />
        </div>
      </div>

      <!-- Price & Add Action (10% High-Contrast Accent Button) -->
      <div
        class="pt-3 border-t border-slate-800 flex items-center justify-between gap-2 mt-auto"
      >
        <div class="flex flex-col">
          <span class="text-base font-black text-white leading-none font-mono">
            {{ formatCurrency(toy.price) }}
          </span>
          <span
            v-if="toy.originalPrice"
            class="text-[11px] text-slate-500 line-through mt-0.5 font-mono"
          >
            {{ formatCurrency(toy.originalPrice) }}
          </span>
        </div>

        <!-- 10% High Contrast Accent: Electric Amber Add to Cart Button -->
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-black rounded-xl shadow-md shadow-amber-400/25 border border-amber-300 active:scale-95 transition-all cursor-pointer"
          @click="handleAddToCart"
        >
          <svg
            class="w-3.5 h-3.5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2.5"
              d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
            />
          </svg>
          <span>Add</span>
        </button>
      </div>
    </div>
  </div>
</template>
