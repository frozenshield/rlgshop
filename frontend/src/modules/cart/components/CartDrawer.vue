<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useCartComposable } from '../cart.composable'
import FreeShippingBar from './FreeShippingBar.vue'
import CartItemRow from './CartItemRow.vue'
import BaseButton from '@/shared/components/BaseButton.vue'
import { formatCurrency } from '@/shared/utils/currency.util'

const router = useRouter()
const {
  items,
  isDrawerOpen,
  subtotal,
  promoDiscount,
  standardShippingCost,
  grandTotal,
  promoInput,
  promoStatus,
  closeDrawer,
  updateQuantity,
  removeItem,
  clearCart,
  handleApplyPromo,
  handleRemovePromo,
} = useCartComposable()

const handleProceedToCheckout = () => {
  closeDrawer()
  router.push('/checkout')
}

const handleExploreToys = () => {
  closeDrawer()
  router.push('/catalog')
}
</script>

<template>
  <!-- Backdrop -->
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
        v-if="isDrawerOpen"
        class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-50 flex justify-end"
        @click="closeDrawer"
      >
        <!-- Slide-over Drawer Panel -->
        <div
          class="w-full max-w-md bg-white h-full shadow-2xl flex flex-col font-display transform transition-transform duration-300 ease-out"
          @click.stop
        >
          <!-- Drawer Header -->
          <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between bg-white">
            <div class="flex items-center gap-2">
              <span class="text-xl">🛒</span>
              <h3 class="text-base font-black text-slate-900">Your Cart</h3>
              <span class="bg-slate-100 text-slate-700 text-xs font-bold px-2 py-0.5 rounded-full border border-slate-200">
                {{ items.length }} items
              </span>
            </div>
            <button
              type="button"
              class="w-8 h-8 rounded-full hover:bg-slate-100 text-slate-500 flex items-center justify-center transition-colors cursor-pointer"
              aria-label="Close Cart"
              @click="closeDrawer"
            >
              ✕
            </button>
          </div>

          <!-- Free Shipping Milestone -->
          <div v-if="items.length > 0" class="p-4 bg-white border-b border-slate-100">
            <FreeShippingBar :subtotal="subtotal" />
          </div>

          <!-- Drawer Content: Items List OR Empty State -->
          <div class="flex-1 overflow-y-auto px-5 py-2">
            <!-- Empty state -->
            <div
              v-if="items.length === 0"
              class="h-full flex flex-col items-center justify-center text-center p-6 space-y-4"
            >
              <div class="w-20 h-20 rounded-3xl bg-slate-100 flex items-center justify-center text-4xl">
                🛍️
              </div>
              <div>
                <h4 class="text-lg font-bold text-slate-800">Your Cart is Empty</h4>
                <p class="text-xs text-slate-500 mt-1 max-w-xs">
                  Discover factory-sealed TCG booster boxes, Japanese Gunpla model kits, and scale figures in our catalog!
                </p>
              </div>
              <button
                type="button"
                class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl shadow-xs transition-all active:scale-95 cursor-pointer"
                @click="handleExploreToys"
              >
                Explore Products &rarr;
              </button>
            </div>

            <!-- Items List -->
            <div v-else class="divide-y divide-slate-100">
              <CartItemRow
                v-for="item in items"
                :key="item.toy.id"
                :item="item"
                @update-quantity="updateQuantity"
                @remove="removeItem"
              />
            </div>
          </div>

          <!-- Drawer Footer: Summary & Actions -->
          <div v-if="items.length > 0" class="p-5 border-t border-slate-200 bg-slate-50/80 space-y-3">
            <!-- Promo code input -->
            <div class="flex gap-2">
              <label for="promo-input" class="sr-only">Collector Promo Code</label>
              <input
                id="promo-input"
                v-model="promoInput"
                type="text"
                aria-label="Collector promo code"
                placeholder="Promo Code (e.g. HOBBY10)"
                class="flex-1 uppercase text-xs font-bold px-3 py-2 bg-white rounded-xl border border-slate-200 focus:outline-none focus:border-rose-500"
              />
              <button
                type="button"
                class="bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold px-3 py-2 rounded-xl transition-colors cursor-pointer"
                @click="handleApplyPromo"
              >
                Apply
              </button>
            </div>

            <!-- Promo feedback status -->
            <div
              v-if="promoStatus"
              :class="[
                'text-[11px] font-bold px-3 py-1.5 rounded-lg flex items-center justify-between',
                promoStatus.isError
                  ? 'bg-red-50 text-red-600 border border-red-200'
                  : 'bg-emerald-50 text-emerald-700 border border-emerald-200',
              ]"
            >
              <span>{{ promoStatus.message }}</span>
              <button
                v-if="!promoStatus.isError"
                class="underline text-[10px] text-slate-500 hover:text-slate-700 cursor-pointer"
                @click="handleRemovePromo"
              >
                Remove
              </button>
            </div>

            <!-- Price Breakdown -->
            <div class="space-y-1.5 text-xs text-slate-600 font-semibold pt-1">
              <div class="flex justify-between">
                <span>Subtotal</span>
                <span class="font-bold text-slate-800">{{ formatCurrency(subtotal) }}</span>
              </div>
              <div v-if="promoDiscount > 0" class="flex justify-between text-emerald-600">
                <span>Collector Discount</span>
                <span class="font-bold">-{{ formatCurrency(promoDiscount) }}</span>
              </div>
              <div class="flex justify-between">
                <span>Dispatch / Shipping</span>
                <span class="font-bold text-slate-800">
                  {{ standardShippingCost === 0 ? 'FREE' : formatCurrency(standardShippingCost) }}
                </span>
              </div>
              <div class="flex justify-between text-sm font-extrabold text-slate-900 pt-2 border-t border-slate-200">
                <span>Total</span>
                <span class="text-rose-600 font-extrabold text-base">{{ formatCurrency(grandTotal) }}</span>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="pt-2 space-y-2">
              <BaseButton
                variant="primary"
                size="lg"
                fullWidth
                @click="handleProceedToCheckout"
              >
                Proceed to Checkout &rarr;
              </BaseButton>

              <button
                type="button"
                class="w-full text-center text-xs font-semibold text-slate-400 hover:text-rose-600 py-1 transition-colors cursor-pointer"
                @click="clearCart"
              >
                Clear Cart
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>
