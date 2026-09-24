<script setup lang="ts">
import { useRouter } from 'vue-router'
import type { PlacedOrder } from '@/shared/types/toy.types'
import { formatCurrency } from '@/shared/utils/currency.util'
import BaseButton from '@/shared/components/BaseButton.vue'

interface Props {
  order: PlacedOrder | null
  isOpen: boolean
}

defineProps<Props>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

const router = useRouter()

const handleContinueShopping = () => {
  emit('close')
  router.push('/catalog')
}
</script>

<template>
  <teleport to="body">
    <transition
      enter-active-class="transition-opacity duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen && order"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto"
      >
        <div
          class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl relative border border-slate-200 font-display text-center space-y-5 animate-scale-up"
        >
          <!-- Confetti / Success Header -->
          <div class="w-18 h-18 rounded-2xl bg-emerald-500 text-white flex items-center justify-center mx-auto text-3xl shadow-lg shadow-emerald-500/20">
            ✓
          </div>

          <div>
            <span class="text-xs font-bold text-emerald-700 uppercase tracking-widest bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
              Order Confirmed &amp; In Fulfillment
            </span>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">
              Thank You for Your Order!
            </h2>
            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
              We received your order, <span class="font-bold text-slate-700">{{ order.shippingDetails.firstName }}</span>! Our fulfillment team is preparing your collector items with reinforced packaging and tracking.
            </p>
          </div>

          <!-- Order Summary Card -->
          <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200 text-left space-y-2.5 text-xs">
            <div class="flex justify-between items-center pb-2 border-b border-slate-200/70 font-bold">
              <span class="text-slate-500">Order Reference:</span>
              <span class="text-rose-600 font-mono text-sm">{{ order.orderId }}</span>
            </div>

            <div class="flex justify-between items-center">
              <span class="text-slate-500">Est. Dispatch Date:</span>
              <span class="font-bold text-slate-800">{{ order.estimatedDeliveryDate }}</span>
            </div>

            <div class="flex justify-between items-center">
              <span class="text-slate-500">Ship To:</span>
              <span class="font-bold text-slate-800 truncate max-w-[200px]">
                {{ order.shippingDetails.firstName }} {{ order.shippingDetails.lastName }}
              </span>
            </div>

            <div class="flex justify-between items-center pt-2 border-t border-slate-200/70 font-extrabold text-sm text-slate-900">
              <span>Total Paid:</span>
              <span class="text-rose-600">{{ formatCurrency(order.total) }}</span>
            </div>
          </div>

          <!-- Items Ordered Mini-Preview -->
          <div class="flex items-center justify-center gap-2 overflow-hidden py-1">
            <img
              v-for="item in order.items.slice(0, 4)"
              :key="item.toy.id"
              :src="item.toy.imageUrl"
              :alt="item.toy.name"
              class="w-12 h-12 rounded-xl object-cover border border-slate-200 shadow-xs"
              :title="item.toy.name"
            />
            <span
              v-if="order.items.length > 4"
              class="w-12 h-12 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold flex items-center justify-center border border-slate-200"
            >
              +{{ order.items.length - 4 }}
            </span>
          </div>

          <!-- Action Buttons -->
          <div class="pt-2 flex flex-col sm:flex-row gap-3">
            <BaseButton
              variant="outline"
              size="md"
              fullWidth
              @click="emit('close')"
            >
              Close Receipt
            </BaseButton>
            <BaseButton
              variant="primary"
              size="md"
              fullWidth
              @click="handleContinueShopping"
            >
              Explore More Collectibles 🚀
            </BaseButton>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>
