<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import type { ToyProduct } from "@/shared/types/toy.types";
import { TCG_SERIES_DATA } from "@/shared/constants/categories.data";
import { formatCurrency, formatAgeGroup } from "@/shared/utils/currency.util";
import { formatProductDescription } from "@/shared/utils/descriptionFormatter";
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

watch(
  () => props.isOpen,
  (val) => {
    if (val && props.toy) {
      quantity.value = props.toy.stock > 0 ? 1 : 0;
      activeImage.value = props.toy.imageUrl || "";
    }
  },
);

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

const formattedDescription = computed(() => {
  return formatProductDescription(props.toy?.description);
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
  if (props.toy && props.toy.stock > 0) {
    cartStore.addItem(props.toy, quantity.value);
    emit("close");
  }
};

const handleToggleWishlist = () => {
  if (props.toy) {
    wishlistStore.toggleFavorite(props.toy.id);
  }
};

const formatDate = (dateStr?: string) => {
  if (!dateStr) return "Verified Buyer";
  try {
    return new Date(dateStr).toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
    });
  } catch {
    return "Recent";
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
          class="bg-slate-900 rounded-3xl max-w-3xl w-full p-6 sm:p-8 shadow-2xl relative border border-slate-800 transform transition-all my-8 max-h-[92vh] overflow-y-auto text-slate-100 space-y-8"
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

          <!-- Top Grid: Product Details & Purchase Actions -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
            <!-- Left: Toy Image & Gallery -->
            <div class="space-y-3">
              <div
                class="aspect-square rounded-2xl overflow-hidden bg-slate-950 border border-slate-800 relative flex items-center justify-center p-3"
              >
                <img
                  :src="currentImage"
                  :alt="toy.name"
                  class="max-w-full max-h-full object-contain"
                  :class="{ 'opacity-55 grayscale-[35%]': toy.stock <= 0 }"
                />
                <div class="absolute top-3 left-3 flex flex-col gap-1">
                  <span
                    v-if="toy.stock <= 0"
                    class="text-[10px] font-black px-2.5 py-1 rounded-md bg-rose-600 text-white uppercase tracking-wider shadow-md shadow-rose-600/40"
                  >
                    🚫 Sold Out
                  </span>
                  <span
                    v-else-if="toy.isBestSeller"
                    class="text-[10px] font-black px-2.5 py-1 rounded-md bg-amber-400 text-slate-950 uppercase tracking-wider shadow-md shadow-amber-400/25"
                  >
                    🔥 Best Seller
                  </span>
                  <span
                    v-if="toy.discountPercent && toy.stock > 0"
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
                  class="w-14 h-14 rounded-xl object-contain p-1 border-2 cursor-pointer transition-all bg-slate-950"
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

                <div class="mt-2 flex items-center gap-2">
                  <span class="text-amber-400 text-sm font-black">
                    ★ {{ toy.rating ? toy.rating.toFixed(1) : "5.0" }}
                  </span>
                  <span class="text-xs text-slate-400">
                    ({{ toy.reviewCount || 0 }} Reviews)
                  </span>
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
                <span
                  v-else
                  class="text-xs font-bold text-rose-400 ml-auto bg-rose-950/70 px-2.5 py-1 rounded-md border border-rose-800"
                >
                  🚫 Sold Out
                </span>
              </div>

              <!-- Description with formatted HTML and icons -->
              <div
                class="text-xs sm:text-sm text-slate-300 leading-relaxed font-normal space-y-1"
                v-html="formattedDescription"
              ></div>

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

              <!-- Quantity Selector & Add to Cart Action -->
              <div class="pt-2 flex items-center gap-3">
                <div
                  v-if="toy.stock > 0"
                  class="flex items-center border border-slate-700 rounded-2xl p-1 bg-slate-950"
                >
                  <button
                    type="button"
                    class="w-8 h-8 rounded-xl bg-slate-800 shadow-xs font-bold text-slate-200 hover:bg-slate-700 flex items-center justify-center cursor-pointer transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                    :disabled="quantity <= 1"
                    @click="decrementQty"
                  >
                    -
                  </button>
                  <span class="w-10 text-center font-bold text-sm text-white">
                    {{ quantity }}
                  </span>
                  <button
                    type="button"
                    class="w-8 h-8 rounded-xl bg-slate-800 shadow-xs font-bold text-slate-200 hover:bg-slate-700 flex items-center justify-center cursor-pointer transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                    :disabled="quantity >= toy.stock"
                    @click="incrementQty"
                  >
                    +
                  </button>
                </div>

                <button
                  type="button"
                  class="flex-1 py-3 px-6 font-black text-sm rounded-2xl shadow-lg transition-all flex items-center justify-center gap-2"
                  :class="
                    toy.stock > 0
                      ? 'bg-amber-400 hover:bg-amber-300 text-slate-950 shadow-amber-400/25 border border-amber-300 active:scale-95 cursor-pointer'
                      : 'bg-slate-800 text-slate-500 border border-slate-700/60 cursor-not-allowed opacity-60 shadow-none'
                  "
                  :disabled="toy.stock <= 0"
                  @click="handleAddToCart"
                >
                  <svg
                    v-if="toy.stock > 0"
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
                  <span>{{ toy.stock > 0 ? "Add to Cart" : "Sold Out" }}</span>
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
