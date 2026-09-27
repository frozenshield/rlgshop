<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import type { ToyProduct } from "@/shared/types/toy.types";
import { TCG_SERIES_DATA } from "@/shared/constants/categories.data";
import { formatCurrency, formatAgeGroup } from "@/shared/utils/currency.util";
import { formatProductDescription } from "@/shared/utils/descriptionFormatter";
import { useCartStore } from "@/modules/cart/cart.store";
import { useWishlistStore } from "@/modules/wishlist/wishlist.store";
import { useAuthStore } from "@/modules/auth/auth.store";
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
const authStore = useAuthStore();

const quantity = ref(1);
const activeImage = ref("");

// Reviews State (customer_review API)
interface CustomerReviewItem {
  id: number;
  user_id: number;
  product_id: number;
  stars: number;
  message: string;
  image?: string | null;
  staff_reply?: string | null;
  staff?: { name?: string };
  user?: {
    name?: string;
    email?: string;
    customer_profile?: { segment?: string };
  };
  created_at?: string;
}

const productReviews = ref<CustomerReviewItem[]>([]);
const isLoadingReviews = ref(false);
const isWritingReview = ref(false);
const isSubmittingReview = ref(false);
const reviewSuccessMessage = ref("");
const reviewErrorMessage = ref("");

const reviewForm = ref({
  stars: 5,
  message: "",
  image: "",
});

const fetchReviews = async () => {
  if (!props.toy) return;
  isLoadingReviews.value = true;
  try {
    const res = await fetch("/api/customer-reviews");
    if (res.ok) {
      const json = await res.json();
      if (json.success && Array.isArray(json.data)) {
        // Find reviews for this product id, or matching product name, or display authentic verified reviews
        const numericId = parseInt(String(props.toy.id).replace(/\D/g, ""));
        const matching = json.data.filter((r: any) => {
          if (!isNaN(numericId) && r.product_id === numericId) return true;
          if (
            r.product?.name &&
            props.toy?.name &&
            r.product.name.toLowerCase() === props.toy.name.toLowerCase()
          )
            return true;
          return false;
        });

        // If specific product has no reviews yet, show real platform verified reviews from customer_review
        productReviews.value =
          matching.length > 0 ? matching : json.data.slice(0, 3);
      }
    }
  } catch (e) {
    console.warn("Could not load reviews for product", e);
  } finally {
    isLoadingReviews.value = false;
  }
};

watch(
  () => props.isOpen,
  (val) => {
    if (val && props.toy) {
      quantity.value = 1;
      activeImage.value = props.toy.imageUrl || "";
      isWritingReview.value = false;
      reviewSuccessMessage.value = "";
      reviewErrorMessage.value = "";
      fetchReviews();
    }
  },
);

const handleWriteReviewSubmit = async () => {
  if (!reviewForm.value.message.trim()) {
    reviewErrorMessage.value = "Please write a review message.";
    return;
  }

  isSubmittingReview.value = true;
  reviewErrorMessage.value = "";
  reviewSuccessMessage.value = "";

  try {
    const numericId = parseInt(String(props.toy?.id).replace(/\D/g, "")) || 1;
    const res = await fetch("/api/customer-reviews", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        product_id: numericId,
        stars: reviewForm.value.stars,
        message: reviewForm.value.message.trim(),
        image: reviewForm.value.image.trim() || null,
        user_id: authStore.currentUser?.id
          ? parseInt(String(authStore.currentUser.id).replace(/\D/g, ""))
          : undefined,
      }),
    });

    if (res.ok) {
      const json = await res.json();
      reviewSuccessMessage.value =
        "Thank you! Your verified collector review has been published.";
      reviewForm.value.message = "";
      reviewForm.value.image = "";
      isWritingReview.value = false;
      if (json.data) {
        productReviews.value.unshift(json.data);
      } else {
        await fetchReviews();
      }
    } else {
      const err = await res.json();
      reviewErrorMessage.value =
        err.message || "Failed to submit review. Please try again.";
    }
  } catch (e: any) {
    reviewErrorMessage.value = "Network error submitting review.";
  } finally {
    isSubmittingReview.value = false;
  }
};

const averageRating = computed(() => {
  if (productReviews.value.length === 0)
    return (props.toy?.rating || 5.0).toFixed(1);
  const sum = productReviews.value.reduce((acc, r) => acc + Number(r.stars), 0);
  return (sum / productReviews.value.length).toFixed(1);
});

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
                    ★ {{ averageRating }}
                  </span>
                  <span class="text-xs text-slate-400">
                    ({{ productReviews.length }} Verified Collector Reviews)
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

          <!-- Bottom Section: Customer Reviews & Ratings (customer_review API) -->
          <div class="pt-6 border-t border-slate-800 space-y-6">
            <div class="flex items-center justify-between flex-wrap gap-4">
              <div>
                <div class="flex items-center gap-2">
                  <span class="text-xl">⭐</span>
                  <h3 class="text-lg font-black text-white">
                    Collector Reviews &amp; Ratings
                  </h3>
                  <span
                    class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-400/20 text-amber-300 border border-amber-400/30"
                  >
                    {{ averageRating }} ★
                  </span>
                </div>
                <p class="text-xs text-slate-400 mt-0.5">
                  Verified buyers sharing packaging condition, card pulls, and
                  authenticity.
                </p>
              </div>

              <button
                type="button"
                class="px-4 py-2 rounded-xl text-xs font-bold border transition-colors cursor-pointer flex items-center gap-1.5"
                :class="
                  isWritingReview
                    ? 'bg-slate-800 text-slate-300 border-slate-700'
                    : 'bg-indigo-600 hover:bg-indigo-500 text-white border-indigo-500 shadow-md shadow-indigo-600/20'
                "
                @click="isWritingReview = !isWritingReview"
              >
                <span>{{
                  isWritingReview ? "Cancel Review" : "✍️ Write a Review"
                }}</span>
              </button>
            </div>

            <!-- Success Alert -->
            <div
              v-if="reviewSuccessMessage"
              class="p-3 rounded-xl bg-emerald-950/70 border border-emerald-500/40 text-emerald-300 text-xs font-semibold flex items-center gap-2"
            >
              <span>✓</span>
              <span>{{ reviewSuccessMessage }}</span>
            </div>

            <!-- Write a Review Form Drawer -->
            <transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="opacity-0 -translate-y-2"
              enter-to-class="opacity-100 translate-y-0"
              leave-active-class="transition duration-150 ease-in"
              leave-from-class="opacity-100"
              leave-to-class="opacity-0 -translate-y-2"
            >
              <div
                v-if="isWritingReview"
                class="p-5 rounded-2xl bg-slate-950 border border-slate-800 space-y-4"
              >
                <div class="flex items-center justify-between">
                  <h4
                    class="text-xs font-extrabold uppercase tracking-wider text-slate-200"
                  >
                    Write Verified Product Review
                  </h4>
                  <!-- Star Rating Picker -->
                  <div class="flex items-center gap-1">
                    <button
                      v-for="s in 5"
                      :key="s"
                      type="button"
                      class="text-lg cursor-pointer transition-transform hover:scale-125 p-0.5"
                      :class="
                        s <= reviewForm.stars
                          ? 'text-amber-400'
                          : 'text-slate-700'
                      "
                      @click="reviewForm.stars = s"
                    >
                      ★
                    </button>
                    <span class="text-xs font-bold text-amber-300 ml-1">
                      ({{ reviewForm.stars }} Stars)
                    </span>
                  </div>
                </div>

                <div class="space-y-2">
                  <textarea
                    v-model="reviewForm.message"
                    rows="3"
                    placeholder="Share your experience (e.g. mint condition box, fast dispatch, card pulls, authentic seal)..."
                    class="w-full p-3 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-amber-400 placeholder-slate-500"
                  ></textarea>

                  <input
                    v-model="reviewForm.image"
                    type="text"
                    placeholder="Photo attachment URL (optional, e.g. https://... image of your pulled cards)"
                    class="w-full p-2.5 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-amber-400 placeholder-slate-500"
                  />
                </div>

                <div
                  v-if="reviewErrorMessage"
                  class="text-xs text-rose-400 font-bold"
                >
                  {{ reviewErrorMessage }}
                </div>

                <div class="flex justify-end gap-2">
                  <button
                    type="button"
                    class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 hover:text-white bg-slate-900 cursor-pointer"
                    @click="isWritingReview = false"
                  >
                    Cancel
                  </button>
                  <button
                    type="button"
                    class="px-5 py-2 rounded-xl text-xs font-bold bg-amber-400 hover:bg-amber-300 text-slate-950 font-black cursor-pointer shadow-md disabled:opacity-50"
                    :disabled="isSubmittingReview"
                    @click="handleWriteReviewSubmit"
                  >
                    {{ isSubmittingReview ? "Publishing..." : "Submit Review" }}
                  </button>
                </div>
              </div>
            </transition>

            <!-- Reviews List Cards -->
            <div
              v-if="isLoadingReviews"
              class="py-8 text-center text-xs text-slate-400"
            >
              Loading verified reviews...
            </div>

            <div
              v-else-if="productReviews.length === 0"
              class="py-6 text-center text-xs text-slate-400"
            >
              No customer reviews yet. Be the first collector to review this
              item!
            </div>

            <div v-else class="space-y-4">
              <div
                v-for="rev in productReviews"
                :key="rev.id"
                class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-3"
              >
                <!-- Reviewer Header -->
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span
                      class="w-7 h-7 rounded-full bg-indigo-950 text-indigo-300 border border-indigo-500/30 font-bold text-xs flex items-center justify-center"
                    >
                      {{
                        rev.user?.name
                          ? rev.user.name.charAt(0).toUpperCase()
                          : "C"
                      }}
                    </span>
                    <div>
                      <p class="text-xs font-bold text-white">
                        {{ rev.user?.name || "Verified Collector" }}
                      </p>
                      <p class="text-[10px] text-slate-400">
                        {{ formatDate(rev.created_at) }}
                      </p>
                    </div>
                  </div>

                  <div class="flex items-center gap-1.5">
                    <div class="text-amber-400 text-xs">
                      {{ "★".repeat(rev.stars) }}{{ "☆".repeat(5 - rev.stars) }}
                    </div>
                    <span
                      class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20"
                    >
                      Verified
                    </span>
                  </div>
                </div>

                <!-- Review Content -->
                <p class="text-xs text-slate-300 leading-relaxed font-normal">
                  "{{ rev.message }}"
                </p>

                <!-- Attached Photo -->
                <div v-if="rev.image" class="pt-1">
                  <img
                    :src="rev.image"
                    alt="Customer photo"
                    class="w-16 h-16 rounded-xl object-contain bg-slate-900 border border-slate-700 p-1 cursor-pointer hover:opacity-90"
                  />
                </div>

                <!-- Staff Reply Box if staff replied -->
                <div
                  v-if="rev.staff_reply"
                  class="p-3 rounded-xl bg-indigo-950/50 border border-indigo-500/30 text-xs space-y-1"
                >
                  <div
                    class="flex items-center gap-1 text-[10px] font-extrabold uppercase tracking-wider text-indigo-300"
                  >
                    <span>💬</span>
                    <span
                      >Staff Reply
                      {{ rev.staff?.name ? `(${rev.staff.name})` : "" }}:</span
                    >
                  </div>
                  <p class="text-slate-300 text-[11px] italic">
                    {{ rev.staff_reply }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>
