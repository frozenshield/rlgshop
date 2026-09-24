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
    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-4">
      <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
        <span class="w-7 h-7 rounded-full bg-slate-900 text-white text-xs font-bold flex items-center justify-center">1</span>
        <h3 class="text-base font-extrabold text-slate-900">Shipping &amp; Contact Details</h3>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">First Name *</label>
          <input
            v-model="form.firstName"
            type="text"
            placeholder="e.g. Ren"
            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-200"
          />
          <p v-if="errors.firstName" class="text-[11px] text-red-500 font-semibold mt-1">{{ errors.firstName }}</p>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Last Name *</label>
          <input
            v-model="form.lastName"
            type="text"
            placeholder="e.g. Santos"
            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-200"
          />
          <p v-if="errors.lastName" class="text-[11px] text-red-500 font-semibold mt-1">{{ errors.lastName }}</p>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Email Address *</label>
          <input
            v-model="form.email"
            type="email"
            placeholder="collector@example.com"
            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-200"
          />
          <p v-if="errors.email" class="text-[11px] text-red-500 font-semibold mt-1">{{ errors.email }}</p>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Phone Number *</label>
          <input
            v-model="form.phone"
            type="tel"
            placeholder="+63 917 123 4567"
            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-200"
          />
          <p v-if="errors.phone" class="text-[11px] text-red-500 font-semibold mt-1">{{ errors.phone }}</p>
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Street Address *</label>
        <input
          v-model="form.streetAddress"
          type="text"
          placeholder="123 Collector Blvd, Unit 4B"
          class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-200"
        />
        <p v-if="errors.streetAddress" class="text-[11px] text-red-500 font-semibold mt-1">{{ errors.streetAddress }}</p>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">City / Region *</label>
          <input
            v-model="form.city"
            type="text"
            placeholder="Quezon City, Metro Manila"
            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900"
          />
          <p v-if="errors.city" class="text-[11px] text-red-500 font-semibold mt-1">{{ errors.city }}</p>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Postal Code *</label>
          <input
            v-model="form.postalCode"
            type="text"
            placeholder="1100"
            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900"
          />
          <p v-if="errors.postalCode" class="text-[11px] text-red-500 font-semibold mt-1">{{ errors.postalCode }}</p>
        </div>
      </div>
    </div>

    <!-- Step 2: Delivery Speed & Gift Options -->
    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-4">
      <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
        <span class="w-7 h-7 rounded-full bg-slate-900 text-white text-xs font-bold flex items-center justify-center">2</span>
        <h3 class="text-base font-extrabold text-slate-900">Shipping Method &amp; Packaging</h3>
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
          :class="form.deliveryOption === 'express' ? 'border-rose-600 bg-rose-50/50' : 'border-slate-200 hover:border-slate-300'"
          @click="handleDeliverySelect('express')"
        >
          <div>
            <div class="flex items-center justify-between mb-1">
              <span class="text-lg">⚡</span>
              <span class="text-xs font-extrabold text-slate-900">Priority Express Dispatch</span>
            </div>
            <p class="text-[11px] text-slate-500">1-2 business days with insured tracking</p>
          </div>
          <span class="text-xs font-bold text-slate-800 mt-2">{{ formatCurrency(12.99) }}</span>
        </div>

        <!-- Gift Wrapped -->
        <div
          class="p-4 rounded-2xl border-2 cursor-pointer transition-all flex flex-col justify-between"
          :class="form.deliveryOption === 'gift-wrapped' ? 'border-rose-600 bg-rose-50/50' : 'border-slate-200 hover:border-slate-300'"
          @click="handleDeliverySelect('gift-wrapped')"
        >
          <div>
            <div class="flex items-center justify-between mb-1">
              <span class="text-lg">🎁</span>
              <span class="text-xs font-extrabold text-slate-900">Collector Box &amp; Wrap</span>
            </div>
            <p class="text-[11px] text-slate-500">Heavy bubble armor + gift ribbon &amp; note</p>
          </div>
          <span class="text-xs font-bold text-slate-800 mt-2">{{ formatCurrency(7.99) }}</span>
        </div>
      </div>

      <!-- Gift Message Field (shown if gift-wrapped selected) -->
      <div v-if="form.deliveryOption === 'gift-wrapped'" class="pt-2 animate-fade-in">
        <label class="block text-xs font-bold text-slate-700 mb-1">
          💌 Collector Gift Message or Packing Note:
        </label>
        <textarea
          v-model="form.giftMessage"
          rows="2"
          placeholder="Happy Birthday! Enjoy the new booster boxes and model kit! From Alex"
          class="w-full text-xs p-3 rounded-xl border border-slate-300 bg-slate-50/40 focus:outline-none focus:border-slate-900"
        ></textarea>
      </div>
    </div>

    <!-- Step 3: Payment Method -->
    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-4">
      <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
        <span class="w-7 h-7 rounded-full bg-slate-900 text-white text-xs font-bold flex items-center justify-center">3</span>
        <h3 class="text-base font-extrabold text-slate-900">Secure Payment</h3>
      </div>

      <div class="grid grid-cols-3 gap-3">
        <label
          class="p-3 rounded-2xl border-2 cursor-pointer text-center flex flex-col items-center gap-1.5 transition-all"
          :class="form.paymentMethod === 'card' ? 'border-rose-600 bg-rose-50/50' : 'border-slate-200'"
        >
          <input v-model="form.paymentMethod" type="radio" value="card" class="sr-only" />
          <span class="text-xl">💳</span>
          <span class="text-xs font-bold text-slate-800">Credit / Debit</span>
        </label>

        <label
          class="p-3 rounded-2xl border-2 cursor-pointer text-center flex flex-col items-center gap-1.5 transition-all"
          :class="form.paymentMethod === 'wallet' ? 'border-rose-600 bg-rose-50/50' : 'border-slate-200'"
        >
          <input v-model="form.paymentMethod" type="radio" value="wallet" class="sr-only" />
          <span class="text-xl">📱</span>
          <span class="text-xs font-bold text-slate-800">GCash / Maya</span>
        </label>

        <label
          class="p-3 rounded-2xl border-2 cursor-pointer text-center flex flex-col items-center gap-1.5 transition-all"
          :class="form.paymentMethod === 'cod' ? 'border-rose-600 bg-rose-50/50' : 'border-slate-200'"
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
            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900"
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
              class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900"
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
              class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900"
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
      Place Order &amp; Confirm Dispatch 📦
    </BaseButton>
  </form>
</template>
