<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useCartStore } from "@/modules/cart/cart.store";
import { useCheckoutStore } from "./checkout.store";
import CheckoutForm from "./components/CheckoutForm.vue";
import OrderSummary from "./components/OrderSummary.vue";
import OrderSuccessModal from "./components/OrderSuccessModal.vue";
import BaseButton from "@/shared/components/BaseButton.vue";
import type { CheckoutFormData } from "@/shared/types/toy.types";

const router = useRouter();
const cartStore = useCartStore();
const checkoutStore = useCheckoutStore();

const currentDeliveryOption = ref<"standard" | "express" | "gift-wrapped">(
  "standard",
);

const handleDeliveryChange = (opt: "standard" | "express" | "gift-wrapped") => {
  currentDeliveryOption.value = opt;
};

const handleSubmitOrder = async (data: CheckoutFormData) => {
  await checkoutStore.placeOrder(data);
};
</script>

<template>
  <div class="min-h-screen py-10 font-display">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
      <!-- Title & Progress -->
      <div
        class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-amber-200/80 pb-5"
      >
        <div>
          <button
            type="button"
            class="text-xs font-bold text-slate-500 hover:text-red-600 flex items-center gap-1.5 mb-1 cursor-pointer transition-colors"
            @click="router.back()"
          >
            &larr; Back to Pokémon Vault
          </button>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">
            RLG Express Poké-Checkout
          </h1>
        </div>

        <div class="flex items-center gap-2 text-xs font-bold text-slate-500">
          <span class="text-emerald-600 flex items-center gap-1">
            <span>✓</span> Bag
          </span>
          <span class="text-slate-300">&gt;</span>
          <span
            class="text-red-600 bg-red-50 px-3 py-1 rounded-full border border-red-200"
          >
            Trainer Dispatch
          </span>
          <span class="text-slate-300">&gt;</span>
          <span class="text-slate-400">Caught!</span>
        </div>
      </div>

      <!-- Empty Cart Guard -->
      <div
        v-if="cartStore.items.length === 0 && !checkoutStore.isSuccessModalOpen"
        class="bg-white rounded-3xl p-12 text-center border border-amber-200/80 shadow-sm max-w-lg mx-auto space-y-4"
      >
        <div class="text-5xl">🎒</div>
        <h3 class="text-xl font-bold text-slate-800">
          Your Poké-Bag is Currently Empty
        </h3>
        <p class="text-xs text-slate-500 max-w-xs mx-auto">
          Please catch items from our catalog before proceeding to trainer
          checkout.
        </p>
        <BaseButton
          variant="primary"
          size="md"
          @click="router.push('/catalog')"
        >
          Browse Pokémon Catalog 🔴
        </BaseButton>
      </div>

      <!-- Main Checkout Grid -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Left: Multi-step Form (7 cols) -->
        <div class="lg:col-span-7">
          <CheckoutForm
            :is-submitting="checkoutStore.isSubmitting"
            @delivery-change="handleDeliveryChange"
            @submit-order="handleSubmitOrder"
          />
        </div>

        <!-- Right: Order Summary Sticky Card (5 cols) -->
        <div class="lg:col-span-5 sticky top-28">
          <OrderSummary :selected-delivery-option="currentDeliveryOption" />
        </div>
      </div>

      <!-- Success Celebration Modal -->
      <OrderSuccessModal
        :order="checkoutStore.lastPlacedOrder"
        :is-open="checkoutStore.isSuccessModalOpen"
        @close="checkoutStore.closeSuccessModal"
      />
    </div>
  </div>
</template>
