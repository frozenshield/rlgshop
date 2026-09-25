<script setup lang="ts">
import { ref, computed } from "vue";
import type { ToyProduct } from "@/shared/types/toy.types";
import { TCG_SERIES_DATA } from "@/shared/constants/categories.data";
import { formatCurrency, formatAgeGroup } from "@/shared/utils/currency.util";
import { useCartStore } from "@/modules/cart/cart.store";
import { useWishlistStore } from "@/modules/wishlist/wishlist.store";
import BaseBadge from "@/shared/components/BaseBadge.vue";
import BaseRating from "@/shared/components/BaseRating.vue";
import BaseButton from "@/shared/components/BaseButton.vue";

interface Props {
  toy: ToyProduct | null;
  isOpen: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  (e: "close"): void;
}>();

const cartStore = useCartStore();
const wishlistStore = useWishlistStore();

const quantity = ref(1);
const activeImage = ref("");

const tcgInfo = computed(() => {
  if (props.toy?.category === "tcg" && props.toy.tcgSeries) {
    return TCG_SERIES_DATA.find((s) => s.id === props.toy?.tcgSeries);
  }
  return null;
});

const currentImage = computed(() => {
  if (activeImage.value) return activeImage.value;
  return props.toy?.imageUrl || "";
});

const incrementQty = () => {
  if (props.toy && quantity.value < props.toy.stock) {
    quantity.value++;
  }
};

const decrementQty = () => {
  if (quantity.value > 1) {
    quantity.value--;
  }
};

const handleAddToCart = () => {
  if (props.toy) {
    cartStore.addItem(props.toy, quantity.value);
    emit("close");
  }
};

const handleToggleWishlist = () => {
  if (props.toy) {
    wishlistStore.toggleFavorite(props.toy.id);
  }
};
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
        class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 flex items-center justify-center p-4 overflow-y-auto font-display"
        @click="emit('close')"
      >
        <div
          class="bg-slate-900 rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl relative border border-slate-800 transform transition-all my-8 max-h-[90vh] overflow-y-auto text-slate-100"
          @click.stop
        >
          <!-- Close Button -->
          <button
            type="button"
            class="absolute top-4 right-4 w-9 h-9 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition-colors cursor-pointer z-10 border border-slate-700"
            @click="emit('close')"
          >
            ✕
          </button>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
            <!-- Left: Toy Image & Gallery -->
            <div class="space-y-3">
              <div
                class="aspect-square rounded-2xl overflow-hidden bg-slate-950 border border-slate-800 relative"
              >
                <img
                  :src="currentImage"
                  :alt="toy.name"
                  class="w-full h-full object-cover"
                />
                <div class="absolute top-3 left-3 flex flex-col gap-1">
                  <span
                    v-if="toy.isBestSeller"
                    class="text-[10px] font-black px-2.5 py-1 rounded-md bg-amber-400 text-slate-950 uppercase tracking-wider shadow-md shadow-amber-400/25"
                  >
                    🔥 Best Seller
                  </span>
                  <span
                    v-if="toy.discountPercent"
                    class="text-[10px] font-bold px-2.5 py-1 rounded-md bg-rose-600 text-white uppercase tracking-wider shadow-sm"
                  >
                    -{{ toy.discountPercent }}% OFF
                  </span>
                </div>
              </div>

              <!-- Gallery thumbs if available -->
              <div
                v-if="toy.galleryImages && toy.galleryImages.length > 1"
                class="flex gap-2"
              >
                <img
                  v-for="(img, idx) in toy.galleryImages"
                  :key="idx"
                  :src="img"
                  class="w-14 h-14 rounded-xl object-cover border-2 cursor-pointer transition-all bg-slate-950"
                  :class="
                    currentImage === img
                      ? 'border-amber-400 scale-105'
                      : 'border-slate-800 opacity-70 hover:opacity-100'
                  "
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
                    class="inline-flex items-center gap-1 font-bold text-xs px-2.5 py-0.5 rounded-md border border-indigo-500/30 bg-indigo-950/80 text-indigo-300 shadow-2xs"
                  >
                    <span>{{ tcgInfo.icon }}</span>
                    <span>{{ tcgInfo.name }}</span>
                  </span>
                  <span
                    v-else
                    class="text-xs font-bold text-indigo-400 uppercase tracking-wider"
                  >
                    {{ toy.brand }}
                  </span>
                  <BaseBadge variant="secondary" size="sm">
                    {{ formatAgeGroup(toy.ageGroup) }}
                  </BaseBadge>
                  <span
                    v-if="toy.condition"
                    class="inline-flex items-center gap-1 font-bold text-xs px-2 py-0.5 rounded-md bg-slate-800 text-amber-300 border border-slate-700 shadow-2xs"
                  >
                    <span>🏷️</span>
                    <span>{{ toy.condition }}</span>
                  </span>
                </div>

                <h2
                  class="text-xl sm:text-2xl font-black text-white leading-tight"
                >
                  {{ toy.name }}
                </h2>

                <div class="mt-2">
                  <BaseRating
                    :rating="toy.rating"
                    :review-count="toy.reviewCount"
                    size="md"
                  />
                </div>
              </div>

              <!-- Price Box -->
              <div
                class="flex items-baseline gap-3 p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800"
              >
                <span class="text-2xl font-black text-white font-mono">
                  {{ formatCurrency(toy.price) }}
                </span>
                <span
                  v-if="toy.originalPrice"
                  class="text-sm text-slate-500 line-through font-mono"
                >
                  {{ formatCurrency(toy.originalPrice) }}
                </span>
                <span
                  v-if="toy.stock > 0"
                  class="text-xs font-bold text-emerald-400 ml-auto"
                >
                  ✓ In Stock ({{ toy.stock }} available)
                </span>
              </div>

              <!-- Description -->
              <p
                class="text-xs sm:text-sm text-slate-300 leading-relaxed font-normal"
              >
                {{ toy.description }}
              </p>

              <!-- Key Features List -->
              <div v-if="toy.features && toy.features.length > 0">
                <h4
                  class="text-xs font-bold text-slate-200 uppercase tracking-wider mb-1.5 flex items-center gap-1.5"
                >
                  <span>✨</span>
                  <span>Collector Specifications:</span>
                </h4>
                <ul class="text-xs text-slate-400 space-y-1">
                  <li
                    v-for="(feat, i) in toy.features"
                    :key="i"
                    class="flex items-center gap-2"
                  >
                    <span class="text-indigo-400 font-bold">✓</span>
                    <span>{{ feat }}</span>
                  </li>
                </ul>
              </div>

              <!-- Safety Warning if any -->
              <div
                v-if="toy.safetyWarning"
                class="p-2.5 rounded-xl bg-slate-950 border border-amber-500/30 text-[11px] text-amber-300 font-semibold flex items-center gap-2"
              >
                <span>⚠️</span>
                <span>{{ toy.safetyWarning }}</span>
              </div>

              <!-- Quantity Selector & Add to Cart Action (10% High-Contrast Accent) -->
              <div class="pt-2 flex items-center gap-3">
                <div
                  class="flex items-center border border-slate-700 rounded-2xl p-1 bg-slate-950"
                >
                  <button
                    type="button"
                    class="w-8 h-8 rounded-xl bg-slate-800 shadow-xs font-bold text-slate-200 hover:bg-slate-700 flex items-center justify-center cursor-pointer transition-colors"
                    @click="decrementQty"
                  >
                    -
                  </button>
                  <span class="w-10 text-center font-bold text-sm text-white">
                    {{ quantity }}
                  </span>
                  <button
                    type="button"
                    class="w-8 h-8 rounded-xl bg-slate-800 shadow-xs font-bold text-slate-200 hover:bg-slate-700 flex items-center justify-center cursor-pointer transition-colors"
                    @click="incrementQty"
                  >
                    +
                  </button>
                </div>

                <!-- 10% High-Contrast Accent Button -->
                <button
                  type="button"
                  class="flex-1 py-3 px-6 bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-sm rounded-2xl shadow-lg shadow-amber-400/25 border border-amber-300 transition-all active:scale-95 cursor-pointer flex items-center justify-center gap-2"
                  @click="handleAddToCart"
                >
                  <svg
                    class="w-4 h-4"
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
                  <span>Add to Cart</span>
                </button>

                <button
                  type="button"
                  class="w-11 h-11 rounded-2xl border border-slate-700 bg-slate-950 flex items-center justify-center hover:bg-slate-800 text-slate-400 hover:text-amber-400 transition-colors cursor-pointer"
                  :title="
                    wishlistStore.isFavorite(toy.id)
                      ? 'Remove from Wishlist'
                      : 'Add to Wishlist'
                  "
                  @click="handleToggleWishlist"
                >
                  <svg
                    class="w-5 h-5 transition-colors"
                    :class="
                      wishlistStore.isFavorite(toy.id)
                        ? 'text-amber-400 fill-amber-400'
                        : 'text-slate-400'
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
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>
