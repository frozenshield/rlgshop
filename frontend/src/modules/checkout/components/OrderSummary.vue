<script setup lang="ts">
import { computed } from 'vue'
import { useCartStore } from '@/modules/cart/cart.store'
import { formatCurrency } from '@/shared/utils/currency.util'

interface Props {
  selectedDeliveryOption: 'standard' | 'express' | 'gift-wrapped'
}

const props = defineProps<Props>()

const cartStore = useCartStore()

const deliveryFee = computed(() => {
  switch (props.selectedDeliveryOption) {
    case 'express':
      return 12.99
    case 'gift-wrapped':
      return 7.99
    case 'standard':
    default:
      return cartStore.standardShippingCost
  }
})

const orderTotal = computed(() => {
  return Math.max(0, cartStore.subtotal - cartStore.promoDiscount + deliveryFee.value)
})
</script>

<template>
  <div class="bg-white rounded-3xl p-6 border border-amber-200/80 shadow-sm space-y-4 font-display">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
      <h3 class="text-base font-extrabold text-slate-800">Poké-Bag Summary</h3>
      <span class="text-xs font-bold text-red-600 bg-red-50 px-2.5 py-1 rounded-full border border-red-200">
        {{ cartStore.totalItemCount }} items
      </span>
    </div>

    <!-- Items Mini-List -->
    <div class="space-y-3 max-h-56 overflow-y-auto pr-1 divide-y divide-slate-50">
      <div
        v-for="item in cartStore.items"
        :key="item.toy.id"
        class="flex items-center gap-3 pt-2"
      >
        <img
          :src="item.toy.imageUrl"
          :alt="item.toy.name"
          class="w-12 h-12 rounded-xl object-cover bg-amber-50"
        />
        <div class="flex-1 min-w-0">
          <p class="text-xs font-bold text-slate-800 truncate">{{ item.toy.name }}</p>
          <p class="text-[11px] text-slate-400 font-semibold">Qty: {{ item.quantity }}</p>
        </div>
        <span class="text-xs font-bold text-slate-800">
          {{ formatCurrency(item.toy.price * item.quantity) }}
        </span>
      </div>
    </div>

    <!-- Breakdown -->
    <div class="space-y-2 pt-3 border-t border-slate-100 text-xs text-slate-600 font-medium">
      <div class="flex justify-between">
        <span>Items Subtotal</span>
        <span class="font-bold text-slate-800">{{ formatCurrency(cartStore.subtotal) }}</span>
      </div>

      <div v-if="cartStore.promoDiscount > 0" class="flex justify-between text-emerald-600 font-bold">
        <span>Trainer Promo ({{ cartStore.appliedPromo }})</span>
        <span>-{{ formatCurrency(cartStore.promoDiscount) }}</span>
      </div>

      <div class="flex justify-between">
        <span>Delivery Option</span>
        <span class="font-bold text-slate-800">
          {{ deliveryFee === 0 ? 'FREE' : formatCurrency(deliveryFee) }}
        </span>
      </div>

      <div class="flex justify-between text-base font-extrabold text-slate-900 pt-3 border-t border-slate-200">
        <span>Final Total</span>
        <span class="text-red-600 font-extrabold text-lg">{{ formatCurrency(orderTotal) }}</span>
      </div>
    </div>

    <div class="p-3 bg-amber-50/80 rounded-2xl border border-amber-200/70 text-[11px] text-amber-900 flex items-center gap-2">
      <span class="text-sm">🛡️</span>
      <span>100% Genuine Pokémon Center licensed goods. Guaranteed authentic cards and figures.</span>
    </div>
  </div>
</template>
