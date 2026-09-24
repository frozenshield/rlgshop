<script setup lang="ts">
import { ref } from 'vue'
import { useAdminStore } from '../admin.store'

const adminStore = useAdminStore()

const activeTab = ref<'staff' | 'payments' | 'shipping' | 'localization'>('staff')
const savedFeedback = ref('')

const isAddStaffOpen = ref(false)
const newStaffName = ref('')
const newStaffEmail = ref('')
const newStaffRole = ref<'Super Admin' | 'Store Manager' | 'Fulfillment Staff'>('Fulfillment Staff')

const addStaff = () => {
  if (newStaffName.value.trim() && newStaffEmail.value.trim()) {
    adminStore.staffMembers.push({
      id: 'STF-' + (adminStore.staffMembers.length + 1),
      name: newStaffName.value,
      email: newStaffEmail.value,
      role: newStaffRole.value,
      permissions:
        newStaffRole.value === 'Super Admin'
          ? ['All Modules', 'Financial Refunds', 'Staff Management']
          : newStaffRole.value === 'Store Manager'
            ? ['Order Management', 'Inventory Control', 'Promotions']
            : ['View Orders', 'Print Packing Slips'],
      isActive: true,
    })
    isAddStaffOpen.value = false
    newStaffName.value = ''
    newStaffEmail.value = ''
    showFeedback('New staff member added with role permissions.')
  }
}

const showFeedback = (msg: string) => {
  savedFeedback.value = msg
  setTimeout(() => {
    savedFeedback.value = ''
  }, 3500)
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Settings &amp; Store Configuration</h1>
        <p class="text-xs text-slate-500">Staff RBAC permissions, payment gateway APIs, logistics tax zones, and currency localization.</p>
      </div>

      <!-- Navigation Tabs -->
      <div class="flex items-center gap-1 p-1 bg-slate-100 rounded-xl border border-slate-200 overflow-x-auto">
        <button
          type="button"
          class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer whitespace-nowrap"
          :class="activeTab === 'staff' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
          @click="activeTab = 'staff'"
        >
          👥 Staff &amp; RBAC
        </button>
        <button
          type="button"
          class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer whitespace-nowrap"
          :class="activeTab === 'payments' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
          @click="activeTab = 'payments'"
        >
          💳 Payment Gateways
        </button>
        <button
          type="button"
          class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer whitespace-nowrap"
          :class="activeTab === 'shipping' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
          @click="activeTab = 'shipping'"
        >
          🚚 Shipping &amp; Taxes
        </button>
        <button
          type="button"
          class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer whitespace-nowrap"
          :class="activeTab === 'localization' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
          @click="activeTab = 'localization'"
        >
          🌐 Localization
        </button>
      </div>
    </div>

    <!-- Alert -->
    <div v-if="savedFeedback" class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-xl animate-fade-in">
      ✓ {{ savedFeedback }}
    </div>

    <!-- TAB 1: Staff & Role Based Access Control (RBAC) -->
    <div v-if="activeTab === 'staff'" class="space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Staff Role-Based Permissions</h2>
          <p class="text-xs text-slate-500">Configure access levels for administrators, inventory managers, and pack room fulfillment crews</p>
        </div>
        <button
          type="button"
          class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold shadow-xs cursor-pointer"
          @click="isAddStaffOpen = true"
        >
          + Add Staff Member
        </button>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200/80 text-slate-500 uppercase font-bold text-[10px] tracking-wider">
                <th class="p-4">Staff Member</th>
                <th class="p-4">Assigned Role</th>
                <th class="p-4">Active Permissions Granted</th>
                <th class="p-4">Account Status</th>
                <th class="p-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="staff in adminStore.staffMembers" :key="staff.id" class="hover:bg-slate-50/70 transition-colors">
                <td class="p-4">
                  <span class="font-bold text-slate-900 block">{{ staff.name }}</span>
                  <span class="text-[11px] text-slate-400 block">{{ staff.email }}</span>
                </td>
                <td class="p-4">
                  <span
                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border"
                    :class="staff.role === 'Super Admin' ? 'bg-rose-50 text-rose-700 border-rose-200' : staff.role === 'Store Manager' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-slate-100 text-slate-700 border-slate-200'"
                  >
                    {{ staff.role }}
                  </span>
                </td>
                <td class="p-4">
                  <div class="flex flex-wrap gap-1">
                    <span
                      v-for="(perm, i) in staff.permissions"
                      :key="i"
                      class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-medium"
                    >
                      {{ perm }}
                    </span>
                  </div>
                </td>
                <td class="p-4">
                  <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">
                    Active
                  </span>
                </td>
                <td class="p-4 text-right">
                  <button type="button" class="text-xs font-bold text-slate-500 hover:text-slate-800">
                    Edit Permissions
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 2: Payment Gateways -->
    <div v-else-if="activeTab === 'payments'" class="space-y-4">
      <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6">
        <div>
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Payment Processor Gateways</h2>
          <p class="text-xs text-slate-500">Enable or disable localized Philippine and international payment methods</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- GCash -->
          <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50 flex items-center justify-between">
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="text-xl">📱</span>
                <h4 class="font-bold text-slate-900 text-sm">GCash QR &amp; Wallet</h4>
              </div>
              <p class="text-xs text-slate-500">Direct mobile checkout for Philippine collectors</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="adminStore.settings.gateways.gcash" type="checkbox" class="sr-only peer" />
              <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-600"></div>
            </label>
          </div>

          <!-- Maya -->
          <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50 flex items-center justify-between">
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="text-xl">💳</span>
                <h4 class="font-bold text-slate-900 text-sm">Maya (PayMaya) Checkout</h4>
              </div>
              <p class="text-xs text-slate-500">Digital wallet and direct credit/debit card gateway</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="adminStore.settings.gateways.maya" type="checkbox" class="sr-only peer" />
              <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-600"></div>
            </label>
          </div>

          <!-- Stripe -->
          <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50 flex items-center justify-between">
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="text-xl">🌐</span>
                <h4 class="font-bold text-slate-900 text-sm">Stripe International</h4>
              </div>
              <p class="text-xs text-slate-500">Visa, Mastercard, AMEX, and Apple Pay</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="adminStore.settings.gateways.stripe" type="checkbox" class="sr-only peer" />
              <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-600"></div>
            </label>
          </div>

          <!-- COD -->
          <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50 flex items-center justify-between">
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="text-xl">💵</span>
                <h4 class="font-bold text-slate-900 text-sm">Cash on Delivery (COD)</h4>
              </div>
              <p class="text-xs text-slate-500">Pay directly to accredited logistics courier</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="adminStore.settings.gateways.cod" type="checkbox" class="sr-only peer" />
              <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-600"></div>
            </label>
          </div>
        </div>

        <div class="flex justify-end pt-2 border-t border-slate-100">
          <button
            type="button"
            class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-xs cursor-pointer"
            @click="showFeedback('Payment gateway configurations updated!')"
          >
            Save Gateway Settings
          </button>
        </div>
      </div>
    </div>

    <!-- TAB 3: Shipping & Tax Zones -->
    <div v-else-if="activeTab === 'shipping'" class="space-y-4">
      <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6">
        <div>
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Shipping Rates &amp; Value-Added Tax (VAT)</h2>
          <p class="text-xs text-slate-500">Configure logistics rate cards, free delivery threshold, and tax calculations</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Standard Nationwide Shipping (PHP)</label>
            <input v-model.number="adminStore.settings.flatShippingRate" type="number" class="w-full p-2.5 rounded-xl border border-slate-200 font-bold" />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Free Shipping Order Threshold (PHP)</label>
            <input v-model.number="adminStore.settings.freeShippingThreshold" type="number" class="w-full p-2.5 rounded-xl border border-slate-200 font-bold" />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Philippine VAT Percentage (%)</label>
            <input v-model.number="adminStore.settings.taxRatePercent" type="number" class="w-full p-2.5 rounded-xl border border-slate-200 font-bold" />
          </div>
        </div>

        <div class="flex justify-end pt-2 border-t border-slate-100">
          <button
            type="button"
            class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-xs cursor-pointer"
            @click="showFeedback('Logistics and tax calculations successfully updated!')"
          >
            Save Logistics &amp; Tax Rules
          </button>
        </div>
      </div>
    </div>

    <!-- TAB 4: Store Localization -->
    <div v-else class="space-y-4">
      <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6">
        <div>
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Store Localization &amp; Formats</h2>
          <p class="text-xs text-slate-500">Manage base transaction currency, default time zones, and date conventions</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Store Legal Name</label>
            <input v-model="adminStore.settings.storeName" type="text" class="w-full p-2.5 rounded-xl border border-slate-200 font-bold" />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Default Store Currency</label>
            <select v-model="adminStore.settings.currency" class="w-full p-2.5 rounded-xl border border-slate-200 bg-white font-bold">
              <option value="PHP">Philippine Peso (PHP ₱)</option>
              <option value="USD">US Dollar (USD $)</option>
              <option value="JPY">Japanese Yen (JPY ¥)</option>
            </select>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Timezone</label>
            <input type="text" value="Asia/Manila (GMT+8)" disabled class="w-full p-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-500 font-mono" />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Weight Unit</label>
            <input type="text" value="Kilograms (kg) &bull; Grams (g)" disabled class="w-full p-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-500 font-mono" />
          </div>
        </div>

        <div class="flex justify-end pt-2 border-t border-slate-100">
          <button
            type="button"
            class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-xs cursor-pointer"
            @click="showFeedback('Store localization parameters saved!')"
          >
            Save Localization Settings
          </button>
        </div>
      </div>
    </div>

    <!-- Add Staff Modal -->
    <teleport to="body">
      <div v-if="isAddStaffOpen" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-slate-200 font-display">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <h3 class="text-base font-bold text-slate-900">Add Staff Account</h3>
              <p class="text-xs text-slate-500">Assign role and permissions</p>
            </div>
            <button type="button" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center cursor-pointer" @click="isAddStaffOpen = false">✕</button>
          </div>

          <div class="space-y-3 text-xs">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Full Name</label>
              <input v-model="newStaffName" type="text" placeholder="e.g. Gabriel Santos" class="w-full p-2.5 rounded-xl border border-slate-300" />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Work Email</label>
              <input v-model="newStaffEmail" type="email" placeholder="staff@rlghobby.com" class="w-full p-2.5 rounded-xl border border-slate-300" />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Role Designation</label>
              <select v-model="newStaffRole" class="w-full p-2.5 rounded-xl border border-slate-300 bg-white">
                <option value="Super Admin">Super Admin (Unrestricted)</option>
                <option value="Store Manager">Store Manager (Catalog & Operations)</option>
                <option value="Fulfillment Staff">Fulfillment Staff (Packing & Shipping only)</option>
              </select>
            </div>
          </div>

          <div class="flex gap-3 pt-3">
            <button type="button" class="flex-1 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs" @click="isAddStaffOpen = false">Cancel</button>
            <button type="button" class="flex-1 py-2.5 rounded-xl bg-slate-900 text-white font-bold text-xs" @click="addStaff">Create Staff Access</button>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>
