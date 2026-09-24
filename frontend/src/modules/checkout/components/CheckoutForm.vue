<script setup lang="ts">
import { ref } from 'vue'
import { checkoutFormSchema } from '../checkout-form.schema'
import type { CheckoutFormData } from '@/shared/types/toy.types'
import BaseButton from '@/shared/components/BaseButton.vue'
import { formatCurrency } from '@/shared/utils/currency.util'
import * as yup from 'yup'

interface Props {
  isSubmitting: boolean
}

defineProps<Props>()

const emit = defineEmits<{
  (e: 'submit-order', data: CheckoutFormData): void
  (e: 'delivery-change', option: 'standard' | 'express' | 'gift-wrapped'): void
}>()

const form = ref<CheckoutFormData>({
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
  streetAddress: '',
  city: '',
  postalCode: '',
  notes: '',
  deliveryOption: 'standard',
  giftMessage: '',
  paymentMethod: 'card',
  cardNumber: '',
  cardExpiry: '',
  cardCvv: '',
})

const errors = ref<Record<string, string>>({})

const handleDeliverySelect = (opt: 'standard' | 'express' | 'gift-wrapped') => {
  form.value.deliveryOption = opt
  emit('delivery-change', opt)
}

const handleSubmit = async () => {
  errors.value = {}
  try {
    const validData = await checkoutFormSchema.validate(form.value, { abortEarly: false })
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    emit('submit-order', validData as any)
  } catch (err) {
    if (err instanceof yup.ValidationError) {
      err.inner.forEach((validationError) => {
        if (validationError.path) {
          errors.value[validationError.path] = validationError.message
        }
      })
    }
  }
}
</script>

<template>
  <form @submit.prevent="handleSubmit" class="space-y-6 font-display">
    <!-- Step 1: Contact & Shipping Address -->
    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-amber-200/80 shadow-sm space-y-4">
      <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
        <span class="w-7 h-7 rounded-full bg-red-600 text-white text-xs font-bold flex items-center justify-center">1</span>
        <h3 class="text-base font-extrabold text-slate-800">Trainer Shipping &amp; Contact Info</h3>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Trainer First Name *</label>
          <input
            v-model="form.firstName"
            type="text"
            placeholder="e.g. Ash"
            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-200"
          />
          <p v-if="errors.firstName" class="text-[11px] text-red-500 font-semibold mt-1">{{ errors.firstName }}</p>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Trainer Last Name *</label>
          <input
            v-model="form.lastName"
            type="text"
            placeholder="e.g. Ketchum"
            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-200"
          />
          <p v-if="errors.lastName" class="text-[11px] text-red-500 font-semibold mt-1">{{ errors.lastName }}</p>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Trainer Email *</label>
          <input
            v-model="form.email"
            type="email"
            placeholder="trainer@pokemoncenter.com"
            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-200"
          />
          <p v-if="errors.email" class="text-[11px] text-red-500 font-semibold mt-1">{{ errors.email }}</p>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Phone Number *</label>
          <input
            v-model="form.phone"
            type="tel"
            placeholder="+1 (555) 019-2834"
            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-200"
          />
          <p v-if="errors.phone" class="text-[11px] text-red-500 font-semibold mt-1">{{ errors.phone }}</p>
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Street Address *</label>
        <input
          v-model="form.streetAddress"
          type="text"
          placeholder="Pallet Town St, Apt 25"
          class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-200"
        />
        <p v-if="errors.streetAddress" class="text-[11px] text-red-500 font-semibold mt-1">{{ errors.streetAddress }}</p>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">City / Region *</label>
          <input
            v-model="form.city"
            type="text"
            placeholder="Kanto / City"
            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-red-500"
          />
          <p v-if="errors.city" class="text-[11px] text-red-500 font-semibold mt-1">{{ errors.city }}</p>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Postal Code *</label>
          <input
            v-model="form.postalCode"
            type="text"
            placeholder="ZIP / Postal"
            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-red-500"
          />
          <p v-if="errors.postalCode" class="text-[11px] text-red-500 font-semibold mt-1">{{ errors.postalCode }}</p>
        </div>
      </div>
    </div>

    <!-- Step 2: Delivery Speed & Gift Options -->
    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-amber-200/80 shadow-sm space-y-4">
      <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
        <span class="w-7 h-7 rounded-full bg-amber-400 text-slate-900 text-xs font-bold flex items-center justify-center">2</span>
        <h3 class="text-base font-extrabold text-slate-800">Dispatch Speed &amp; Premier Ball Packaging</h3>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <!-- Standard -->
        <div
          class="p-4 rounded-2xl border-2 cursor-pointer transition-all flex flex-col justify-between"
          :class="form.deliveryOption === 'standard' ? 'border-red-600 bg-red-50/50' : 'border-slate-200 hover:border-slate-300'"
          @click="handleDeliverySelect('standard')"
        >
          <div>
            <div class="flex items-center justify-between mb-1">
              <span class="text-lg">🚚</span>
              <span class="text-xs font-extrabold text-slate-900">Standard Delivery</span>
            </div>
            <p class="text-[11px] text-slate-500">3-5 business days</p>
          </div>
          <span class="text-xs font-bold text-slate-800 mt-2">FREE / {{ formatCurrency(5.99) }}</span>
        </div>

        <!-- Express -->
        <div
          class="p-4 rounded-2xl border-2 cursor-pointer transition-all flex flex-col justify-between"
          :class="form.deliveryOption === 'express' ? 'border-red-600 bg-red-50/50' : 'border-slate-200 hover:border-slate-300'"
          @click="handleDeliverySelect('express')"
        >
          <div>
            <div class="flex items-center justify-between mb-1">
              <span class="text-lg">🕊️</span>
              <span class="text-xs font-extrabold text-slate-900">Pidgey Express</span>
            </div>
            <p class="text-[11px] text-slate-500">1-2 days priority air dispatch</p>
          </div>
          <span class="text-xs font-bold text-slate-800 mt-2">{{ formatCurrency(12.99) }}</span>
        </div>

        <!-- Gift Wrapped -->
        <div
          class="p-4 rounded-2xl border-2 cursor-pointer transition-all flex flex-col justify-between"
          :class="form.deliveryOption === 'gift-wrapped' ? 'border-red-600 bg-red-50/50' : 'border-slate-200 hover:border-slate-300'"
          @click="handleDeliverySelect('gift-wrapped')"
        >
          <div>
            <div class="flex items-center justify-between mb-1">
              <span class="text-lg">🎁</span>
              <span class="text-xs font-extrabold text-slate-900">Premier Ball Wrap</span>
            </div>
            <p class="text-[11px] text-slate-500">Ribbon + Pikachu card note</p>
          </div>
          <span class="text-xs font-bold text-slate-800 mt-2">{{ formatCurrency(7.99) }}</span>
        </div>
      </div>

      <!-- Gift Message Field (shown if gift-wrapped selected) -->
      <div v-if="form.deliveryOption === 'gift-wrapped'" class="pt-2 animate-fade-in">
        <label class="block text-xs font-bold text-slate-700 mb-1">
          💌 Your Trainer Birthday / Gift Card Note:
        </label>
        <textarea
          v-model="form.giftMessage"
          rows="2"
          placeholder="Happy 8th Birthday Leo! May your Pokémon journey be filled with legendary battles! From Dad"
          class="w-full text-xs p-3 rounded-xl border border-red-300 bg-red-50/30 focus:outline-none focus:border-red-500"
        ></textarea>
      </div>
    </div>

    <!-- Step 3: Payment Method -->
    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-amber-200/80 shadow-sm space-y-4">
      <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
        <span class="w-7 h-7 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center">3</span>
        <h3 class="text-base font-extrabold text-slate-800">Secure Payment</h3>
      </div>

      <div class="grid grid-cols-3 gap-3">
        <label
          class="p-3 rounded-2xl border-2 cursor-pointer text-center flex flex-col items-center gap-1.5 transition-all"
          :class="form.paymentMethod === 'card' ? 'border-red-600 bg-red-50/50' : 'border-slate-200'"
        >
          <input v-model="form.paymentMethod" type="radio" value="card" class="sr-only" />
          <span class="text-xl">💳</span>
          <span class="text-xs font-bold text-slate-800">Credit / Debit</span>
        </label>

        <label
          class="p-3 rounded-2xl border-2 cursor-pointer text-center flex flex-col items-center gap-1.5 transition-all"
          :class="form.paymentMethod === 'wallet' ? 'border-red-600 bg-red-50/50' : 'border-slate-200'"
        >
          <input v-model="form.paymentMethod" type="radio" value="wallet" class="sr-only" />
          <span class="text-xl">📱</span>
          <span class="text-xs font-bold text-slate-800">PayPal / GPay</span>
        </label>

        <label
          class="p-3 rounded-2xl border-2 cursor-pointer text-center flex flex-col items-center gap-1.5 transition-all"
          :class="form.paymentMethod === 'cod' ? 'border-red-600 bg-red-50/50' : 'border-slate-200'"
        >
          <input v-model="form.paymentMethod" type="radio" value="cod" class="sr-only" />
          <span class="text-xl">💵</span>
          <span class="text-xs font-bold text-slate-800">Cash on Delivery</span>
        </label>
      </div>

      <!-- Card Fields Simulation -->
      <div v-if="form.paymentMethod === 'card'" class="space-y-3 pt-2">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Card Number *</label>
          <input
            v-model="form.cardNumber"
            type="text"
            placeholder="4532 0000 0000 0000"
            maxlength="19"
            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-red-500"
          />
          <p v-if="errors.cardNumber" class="text-[11px] text-red-500 font-semibold mt-1">{{ errors.cardNumber }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Expiry Date *</label>
            <input
              v-model="form.cardExpiry"
              type="text"
              placeholder="MM/YY"
              maxlength="5"
              class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-red-500"
            />
            <p v-if="errors.cardExpiry" class="text-[11px] text-red-500 font-semibold mt-1">{{ errors.cardExpiry }}</p>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">CVV Security Code *</label>
            <input
              v-model="form.cardCvv"
              type="password"
              placeholder="123"
              maxlength="4"
              class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-red-500"
            />
            <p v-if="errors.cardCvv" class="text-[11px] text-red-500 font-semibold mt-1">{{ errors.cardCvv }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Place Order Button -->
    <BaseButton
      type="submit"
      variant="primary"
      size="lg"
      fullWidth
      :loading="isSubmitting"
    >
      Complete Order &amp; Catch 'Em All! 🔴
    </BaseButton>
  </form>
</template>
