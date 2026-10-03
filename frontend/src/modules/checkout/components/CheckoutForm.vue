<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { checkoutFormSchema } from "../checkout-form.schema";
import type { CheckoutFormData } from "@/shared/types/toy.types";
import BaseButton from "@/shared/components/BaseButton.vue";
import { formatCurrency } from "@/shared/utils/currency.util";
import { useAuthStore } from "@/modules/auth/auth.store";
import { useCartStore } from "@/modules/cart/cart.store";
import * as yup from "yup";

interface Props {
  isSubmitting: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  (e: "submit-order", data: CheckoutFormData): void;
  (e: "delivery-change", option: "standard" | "express" | "gift-wrapped"): void;
}>();

const authStore = useAuthStore();
const cartStore = useCartStore();

interface PaymentMerchant {
  id: number;
  name: string;
  code: string;
  account_name: string;
  account_number: string;
  qr_image_url: string;
  instructions: string;
}

// 4 Static QR Merchants: GoTyme, GCash, MariBank, PayMaya
const paymentMerchants = ref<PaymentMerchant[]>([
  {
    id: 1,
    name: "GoTyme Bank",
    code: "gotyme",
    account_name: "RUSSEL LUIS GEMENTIZA",
    account_number: "•••••••• 5860",
    qr_image_url: "/images/qr/gotyme-qr.png",
    instructions: "Scan with your GoTyme or any InstaPay app. Input the exact total and save transaction screenshot.",
  },
  {
    id: 2,
    name: "GCash",
    code: "gcash",
    account_name: "RU***L LU*S G.",
    account_number: "+63 956 997 ****",
    qr_image_url: "/images/qr/gcash-qr.jpg",
    instructions: "Scan via GCash app. Transfer fees may apply. Save transaction receipt to confirm payment.",
  },
  {
    id: 3,
    name: "MariBank",
    code: "maribank",
    account_name: "RUSSEL LUIS GEMENTIZA",
    account_number: "MariBank(****4301)",
    qr_image_url: "/images/qr/maribank-qr.png",
    instructions: "Scan via MariBank or any InstaPay e-wallet. Save transfer confirmation.",
  },
  {
    id: 4,
    name: "PayMaya",
    code: "paymaya",
    account_name: "Russel Luis Gementiza",
    account_number: "+63 *** *** 0813 (@russelluis)",
    qr_image_url: "/images/qr/maya-qr.jpg",
    instructions: "Scan using Maya app. Transfer fees may apply. Save confirmation receipt.",
  },
]);

const selectedMerchantCode = ref<string>("gotyme");

const selectedMerchant = computed(() => {
  return (
    paymentMerchants.value.find((m) => m.code === selectedMerchantCode.value) ||
    paymentMerchants.value[0]
  );
});

const form = ref<CheckoutFormData>({
  firstName: "",
  lastName: "",
  email: "",
  phone: "",
  streetAddress: "",
  city: "",
  postalCode: "",
  notes: "",
  deliveryOption: "standard",
  giftMessage: "",
  paymentMethod: "qr",
  qrMerchantCode: "gotyme",
  cardNumber: "",
  cardExpiry: "",
  cardCvv: "",
});

const errors = ref<Record<string, string>>({});

// Compute estimated order total for display in QR instruction banner
const deliveryFee = computed(() => {
  switch (form.value.deliveryOption) {
    case "express":
      return 12.99;
    case "gift-wrapped":
      return 7.99;
    case "standard":
    default:
      return cartStore.standardShippingCost;
  }
});

const estimatedTotal = computed(() => {
  const taxableBase = Math.max(0, cartStore.subtotal - cartStore.promoDiscount);
  const vat = (taxableBase * cartStore.vatPercentage) / 100;
  return Math.max(0, taxableBase + deliveryFee.value + vat);
});

// Auto-fill shipping and contact details from the authenticated user's profile
const populateFromAuth = () => {
  const u = authStore.currentUser;
  if (!u) return;

  const fullName = (u.name || "").trim();
  const spaceIndex = fullName.indexOf(" ");
  const firstName = spaceIndex !== -1 ? fullName.substring(0, spaceIndex) : fullName;
  const lastName = spaceIndex !== -1 ? fullName.substring(spaceIndex + 1) : "";

  if (!form.value.firstName && firstName) form.value.firstName = firstName;
  if (!form.value.lastName && lastName) form.value.lastName = lastName;
  if (!form.value.email && u.email) form.value.email = u.email;
  if (!form.value.phone && u.phone) form.value.phone = u.phone;
  if (!form.value.streetAddress && u.address_line1) form.value.streetAddress = u.address_line1;
  if (!form.value.city && u.city) form.value.city = u.city;
  if (!form.value.postalCode && u.postal_code) form.value.postalCode = u.postal_code;
};

const handleDeliverySelect = (opt: "standard" | "express" | "gift-wrapped") => {
  form.value.deliveryOption = opt;
  emit("delivery-change", opt);
};

const handleSelectMerchant = (code: string) => {
  selectedMerchantCode.value = code;
  form.value.qrMerchantCode = code;
};

const handleSubmit = async () => {
  errors.value = {};
  try {
    form.value.qrMerchantCode = selectedMerchantCode.value;
    const validData = await checkoutFormSchema.validate(form.value, {
      abortEarly: false,
    });
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    emit("submit-order", validData as any);
  } catch (err) {
    if (err instanceof yup.ValidationError) {
      err.inner.forEach((validationError) => {
        if (validationError.path) {
          errors.value[validationError.path] = validationError.message;
        }
      });
    }
  }
};

onMounted(async () => {
  populateFromAuth();

  // Fetch active merchants from backend
  try {
    const res = await fetch("/api/ref-payment-merchants");
    if (res.ok) {
      const data = await res.json();
      if (Array.isArray(data) && data.length > 0) {
        paymentMerchants.value = data;
      }
    }
  } catch (e) {
    // Keep local fallback list
  }
});
</script>

<template>
  <form @submit.prevent="handleSubmit" class="space-y-6 font-display">
    <!-- Step 1: Contact & Shipping Address -->
    <div
      class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-4"
    >
      <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-slate-100 gap-2">
        <div class="flex items-center gap-2">
          <span
            class="w-7 h-7 rounded-full bg-slate-900 text-white text-xs font-bold flex items-center justify-center"
            >1</span
          >
          <h3 class="text-base font-extrabold text-slate-900">
            Shipping &amp; Contact Details
          </h3>
        </div>

        <!-- Authenticated Profile Autofill Badge -->
        <span
          v-if="authStore.isAuthenticated"
          class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200/70"
        >
          <span>✓</span>
          <span>Autofilled from your Profile</span>
        </span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1"
            >First Name *</label
          >
          <input
            v-model="form.firstName"
            type="text"
            placeholder="e.g. Ren"
            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-200"
          />
          <p
            v-if="errors.firstName"
            class="text-[11px] text-red-500 font-semibold mt-1"
          >
            {{ errors.firstName }}
          </p>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1"
            >Last Name *</label
          >
          <input
            v-model="form.lastName"
            type="text"
            placeholder="e.g. Santos"
            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-200"
          />
          <p
            v-if="errors.lastName"
            class="text-[11px] text-red-500 font-semibold mt-1"
          >
            {{ errors.lastName }}
          </p>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1"
            >Email Address *</label
          >
          <input
            v-model="form.email"
            type="email"
            placeholder="collector@example.com"
            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-200"
          />
          <p
            v-if="errors.email"
            class="text-[11px] text-red-500 font-semibold mt-1"
          >
            {{ errors.email }}
          </p>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1"
            >Phone Number *</label
          >
          <input
            v-model="form.phone"
            type="tel"
            placeholder="+63 917 123 4567"
            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-200"
          />
          <p
            v-if="errors.phone"
            class="text-[11px] text-red-500 font-semibold mt-1"
          >
            {{ errors.phone }}
          </p>
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1"
          >Street Address *</label
        >
        <input
          v-model="form.streetAddress"
          type="text"
          placeholder="123 Collector Blvd, Unit 4B"
          class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-200"
        />
        <p
          v-if="errors.streetAddress"
          class="text-[11px] text-red-500 font-semibold mt-1"
        >
          {{ errors.streetAddress }}
        </p>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1"
            >City / Region *</label
          >
          <input
            v-model="form.city"
            type="text"
            placeholder="Quezon City, Metro Manila"
            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900"
          />
          <p
            v-if="errors.city"
            class="text-[11px] text-red-500 font-semibold mt-1"
          >
            {{ errors.city }}
          </p>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1"
            >Postal Code *</label
          >
          <input
            v-model="form.postalCode"
            type="text"
            placeholder="1100"
            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900"
          />
          <p
            v-if="errors.postalCode"
            class="text-[11px] text-red-500 font-semibold mt-1"
          >
            {{ errors.postalCode }}
          </p>
        </div>
      </div>
    </div>

    <!-- Step 2: Delivery Speed & Gift Options -->
    <div
      class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-4"
    >
      <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
        <span
          class="w-7 h-7 rounded-full bg-slate-900 text-white text-xs font-bold flex items-center justify-center"
          >2</span
        >
        <h3 class="text-base font-extrabold text-slate-900">
          Shipping Method &amp; Packaging
        </h3>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <!-- Standard -->
        <div
          class="p-4 rounded-2xl border-2 cursor-pointer transition-all flex flex-col justify-between"
          :class="
            form.deliveryOption === 'standard'
              ? 'border-red-600 bg-red-50/50'
              : 'border-slate-200 hover:border-slate-300'
          "
          @click="handleDeliverySelect('standard')"
        >
          <div>
            <div class="flex items-center justify-between mb-1">
              <span class="text-lg">🚚</span>
              <span class="text-xs font-extrabold text-slate-900"
                >Standard Delivery</span
              >
            </div>
            <p class="text-[11px] text-slate-500">3-5 business days</p>
          </div>
          <span class="text-xs font-bold text-slate-800 mt-2"
            >FREE / {{ formatCurrency(5.99) }}</span
          >
        </div>

        <!-- Express -->
        <div
          class="p-4 rounded-2xl border-2 cursor-pointer transition-all flex flex-col justify-between"
          :class="
            form.deliveryOption === 'express'
              ? 'border-rose-600 bg-rose-50/50'
              : 'border-slate-200 hover:border-slate-300'
          "
          @click="handleDeliverySelect('express')"
        >
          <div>
            <div class="flex items-center justify-between mb-1">
              <span class="text-lg">⚡</span>
              <span class="text-xs font-extrabold text-slate-900"
                >Priority Express Dispatch</span
              >
            </div>
            <p class="text-[11px] text-slate-500">
              1-2 business days with insured tracking
            </p>
          </div>
          <span class="text-xs font-bold text-slate-800 mt-2">{{
            formatCurrency(12.99)
          }}</span>
        </div>

        <!-- Gift Wrapped -->
        <div
          class="p-4 rounded-2xl border-2 cursor-pointer transition-all flex flex-col justify-between"
          :class="
            form.deliveryOption === 'gift-wrapped'
              ? 'border-rose-600 bg-rose-50/50'
              : 'border-slate-200 hover:border-slate-300'
          "
          @click="handleDeliverySelect('gift-wrapped')"
        >
          <div>
            <div class="flex items-center justify-between mb-1">
              <span class="text-lg">🎁</span>
              <span class="text-xs font-extrabold text-slate-900"
                >Collector Box &amp; Wrap</span
              >
            </div>
            <p class="text-[11px] text-slate-500">
              Heavy bubble armor + gift ribbon &amp; note
            </p>
          </div>
          <span class="text-xs font-bold text-slate-800 mt-2">{{
            formatCurrency(7.99)
          }}</span>
        </div>
      </div>

      <!-- Gift Message Field (shown if gift-wrapped selected) -->
      <div
        v-if="form.deliveryOption === 'gift-wrapped'"
        class="pt-2 animate-fade-in"
      >
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

    <!-- Step 3: Secure Payment -->
    <div
      class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-5"
    >
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <div class="flex items-center gap-2">
          <span
            class="w-7 h-7 rounded-full bg-slate-900 text-white text-xs font-bold flex items-center justify-center"
            >3</span
          >
          <h3 class="text-base font-extrabold text-slate-900">Secure Payment</h3>
        </div>

        <span
          v-if="form.paymentMethod === 'qr'"
          class="text-[10px] font-extrabold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full"
        >
          Option 1: 0% Fee Static QR
        </span>
      </div>

      <!-- Payment Method Radios (Static QR Ph, Credit/Debit, Cash on Delivery) -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <!-- Static QR Code (Preferred / 0% Fee) -->
        <label
          class="p-4 rounded-2xl border-2 cursor-pointer text-center flex flex-col items-center justify-between gap-1.5 transition-all"
          :class="
            form.paymentMethod === 'qr'
              ? 'border-indigo-600 bg-indigo-50/50 shadow-xs'
              : 'border-slate-200 hover:border-slate-300'
          "
        >
          <input
            v-model="form.paymentMethod"
            type="radio"
            value="qr"
            class="sr-only"
          />
          <div class="text-2xl">📱</div>
          <div>
            <span class="text-xs font-extrabold text-slate-900 block">QR (GoTyme / E-Wallets)</span>
            <span class="text-[10px] text-emerald-600 font-bold">0% Transaction Fee</span>
          </div>
          <span class="text-[9px] text-slate-400 font-semibold">GoTyme &bull; GCash &bull; MariBank &bull; Maya</span>
        </label>

        <!-- Credit / Debit -->
        <label
          class="p-4 rounded-2xl border-2 cursor-pointer text-center flex flex-col items-center justify-between gap-1.5 transition-all"
          :class="
            form.paymentMethod === 'card'
              ? 'border-rose-600 bg-rose-50/50 shadow-xs'
              : 'border-slate-200 hover:border-slate-300'
          "
        >
          <input
            v-model="form.paymentMethod"
            type="radio"
            value="card"
            class="sr-only"
          />
          <div class="text-2xl">💳</div>
          <div>
            <span class="text-xs font-extrabold text-slate-900 block">Credit / Debit</span>
            <span class="text-[10px] text-slate-400 font-semibold">Visa / MasterCard</span>
          </div>
          <span class="text-[9px] text-slate-400">3D Secure Verified</span>
        </label>

        <!-- Cash on Delivery -->
        <label
          class="p-4 rounded-2xl border-2 cursor-pointer text-center flex flex-col items-center justify-between gap-1.5 transition-all"
          :class="
            form.paymentMethod === 'cod'
              ? 'border-rose-600 bg-rose-50/50 shadow-xs'
              : 'border-slate-200 hover:border-slate-300'
          "
        >
          <input
            v-model="form.paymentMethod"
            type="radio"
            value="cod"
            class="sr-only"
          />
          <div class="text-2xl">💵</div>
          <div>
            <span class="text-xs font-extrabold text-slate-900 block">Cash on Delivery</span>
            <span class="text-[10px] text-slate-400 font-semibold">Nationwide Delivery</span>
          </div>
          <span class="text-[9px] text-slate-400">Pay upon doorstep receipt</span>
        </label>
      </div>

      <!-- ─── SUB-SECTION: Static QR Merchant Selection & Live QR Display ─────────────────────── -->
      <div v-if="form.paymentMethod === 'qr'" class="space-y-4 pt-1 animate-fade-in">
        <div>
          <label class="block text-xs font-extrabold text-slate-800 mb-1.5">
            Select Your Receiving Wallet / Bank:
          </label>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            <button
              v-for="merch in paymentMerchants"
              :key="merch.code"
              type="button"
              class="px-3 py-2.5 rounded-xl border-2 text-xs font-extrabold transition-all cursor-pointer flex flex-col items-center justify-center gap-0.5 text-center"
              :class="
                selectedMerchantCode === merch.code
                  ? 'border-indigo-600 bg-indigo-600 text-white shadow-xs'
                  : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300'
              "
              @click="handleSelectMerchant(merch.code)"
            >
              <span>{{ merch.name }}</span>
              <span
                class="text-[9px] font-medium"
                :class="selectedMerchantCode === merch.code ? 'text-indigo-100' : 'text-slate-400'"
              >
                {{ merch.code === 'gotyme' ? 'No transfer fee' : 'InstaPay QR' }}
              </span>
            </button>
          </div>
        </div>

        <!-- Static QR Code Display Box with Explicit User Instruction -->
        <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200 space-y-4">
          <!-- Exact Instruction Banner Required by User -->
          <div class="bg-indigo-50/80 border border-indigo-200 text-indigo-900 p-3.5 rounded-xl text-xs font-semibold leading-relaxed">
            <div class="flex items-center gap-1.5 font-extrabold text-indigo-950 mb-0.5">
              <span>📌 Payment Instruction:</span>
            </div>
            Scan this <strong>{{ selectedMerchant.name }}</strong> QR, input the exact total of
            <strong class="text-rose-600 font-mono text-sm underline">{{ formatCurrency(estimatedTotal) }}</strong>,
            and reply to your confirmation email with the payment screenshot.
          </div>

          <!-- QR Image & Receiving Account Box -->
          <div class="flex flex-col sm:flex-row items-center gap-5 bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
            <div class="bg-white p-2 rounded-xl border border-slate-200 shadow-xs shrink-0 text-center">
              <img
                :src="selectedMerchant.qr_image_url"
                :alt="`${selectedMerchant.name} QR Code`"
                class="w-44 h-auto rounded-lg mx-auto object-contain"
              />
              <span class="text-[10px] font-bold text-slate-400 block mt-1">
                Scan via {{ selectedMerchant.name }}
              </span>
            </div>

            <div class="space-y-2 text-xs flex-1 text-center sm:text-left">
              <div>
                <span class="text-[10px] uppercase font-bold text-slate-400">Receiving Merchant</span>
                <p class="font-black text-slate-900 text-sm">{{ selectedMerchant.name }}</p>
              </div>

              <div v-if="selectedMerchant.account_name">
                <span class="text-[10px] uppercase font-bold text-slate-400">Account Name</span>
                <p class="font-extrabold text-slate-800">{{ selectedMerchant.account_name }}</p>
              </div>

              <div v-if="selectedMerchant.account_number">
                <span class="text-[10px] uppercase font-bold text-slate-400">Account / ID Number</span>
                <p class="font-mono font-bold text-indigo-700">{{ selectedMerchant.account_number }}</p>
              </div>

              <div class="pt-1">
                <span class="text-[10px] uppercase font-bold text-slate-400">Payable Amount</span>
                <p class="text-lg font-black text-rose-600 font-mono">{{ formatCurrency(estimatedTotal) }}</p>
              </div>
            </div>
          </div>

          <!-- Explanation of Zero-Cost Manual Verification Flow -->
          <div class="text-[11px] text-slate-500 space-y-1 bg-white/60 p-3 rounded-xl border border-slate-200/60">
            <div class="font-bold text-slate-700 flex items-center gap-1.5">
              <span>💡 Zero-Cost Static QR Flow:</span>
            </div>
            <p>
              1. When you click <strong>Place Order</strong>, your order is saved as <strong>Pending</strong>.
            </p>
            <p>
              2. Our Laravel email engine will automatically dispatch an email attaching this static QR code image.
            </p>
            <p>
              3. You transfer the exact amount and reply with your receipt screenshot. We will verify and mark your order as <strong>Paid</strong>!
            </p>
          </div>
        </div>
      </div>

      <!-- Card Fields Simulation -->
      <div v-else-if="form.paymentMethod === 'card'" class="space-y-3 pt-2 animate-fade-in">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1"
            >Card Number *</label
          >
          <input
            v-model="form.cardNumber"
            type="text"
            placeholder="4532 0000 0000 0000"
            maxlength="19"
            class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900"
          />
          <p
            v-if="errors.cardNumber"
            class="text-[11px] text-red-500 font-semibold mt-1"
          >
            {{ errors.cardNumber }}
          </p>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1"
              >Expiry Date *</label
            >
            <input
              v-model="form.cardExpiry"
              type="text"
              placeholder="MM/YY"
              maxlength="5"
              class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900"
            />
            <p
              v-if="errors.cardExpiry"
              class="text-[11px] text-red-500 font-semibold mt-1"
            >
              {{ errors.cardExpiry }}
            </p>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1"
              >CVV Security Code *</label
            >
            <input
              v-model="form.cardCvv"
              type="password"
              placeholder="123"
              maxlength="4"
              class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900"
            />
            <p
              v-if="errors.cardCvv"
              class="text-[11px] text-red-500 font-semibold mt-1"
            >
              {{ errors.cardCvv }}
            </p>
          </div>
        </div>
      </div>

      <!-- Cash on Delivery Notice -->
      <div v-else-if="form.paymentMethod === 'cod'" class="p-4 bg-amber-50 rounded-2xl border border-amber-200 text-xs text-amber-900 space-y-1 animate-fade-in">
        <span class="font-extrabold flex items-center gap-1">📦 Cash on Delivery Confirmed</span>
        <p class="text-[11px] text-amber-800">
          Please prepare the exact amount of <strong>{{ formatCurrency(estimatedTotal) }}</strong> in cash upon courier package arrival.
        </p>
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
      {{ form.paymentMethod === 'qr' ? 'Place Order & Receive QR Instructions 📲' : 'Place Order & Confirm Dispatch 📦' }}
    </BaseButton>
  </form>
</template>
