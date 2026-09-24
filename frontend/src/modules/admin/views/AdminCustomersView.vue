<script setup lang="ts">
import { ref, computed } from 'vue'
import { useAdminStore } from '../admin.store'
import type { CustomerProfile, CustomerInquiry } from '../admin.types'
import { formatCurrency } from '@/shared/utils/currency.util'

const adminStore = useAdminStore()

const activeTab = ref<'profiles' | 'inbox'>('profiles')
const searchQuery = ref('')
const selectedSegment = ref<'All' | 'VIP' | 'Regular' | 'Wholesale' | 'Inactive'>('All')

// Profile Details Modal
const selectedCustomer = ref<CustomerProfile | null>(null)
const isProfileModalOpen = ref(false)

// Inbox Reply Modal
const selectedInquiry = ref<CustomerInquiry | null>(null)
const replyText = ref('')
const isReplyModalOpen = ref(false)

const filteredCustomers = computed(() => {
  return adminStore.customers.filter((c) => {
    const matchesSegment = selectedSegment.value === 'All' || c.segment === selectedSegment.value
    const matchesSearch =
      c.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      c.email.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      c.city.toLowerCase().includes(searchQuery.value.toLowerCase())
    return matchesSegment && matchesSearch
  })
})

const openProfile = (c: CustomerProfile) => {
  selectedCustomer.value = c
  isProfileModalOpen.value = true
}

const openReply = (inq: CustomerInquiry) => {
  selectedInquiry.value = inq
  replyText.value = `Hi ${inq.customerName},\n\nThank you for reaching out to RLG Hobby Shop support regarding "${inq.subject}". `
  isReplyModalOpen.value = true
}

const sendReply = () => {
  if (selectedInquiry.value) {
    adminStore.markInquiryStatus(selectedInquiry.value.id, 'resolved')
    isReplyModalOpen.value = false
  }
}

const getSegmentBadge = (segment: string) => {
  switch (segment) {
    case 'VIP':
      return 'bg-amber-100 text-amber-800 border-amber-200'
    case 'Wholesale':
      return 'bg-purple-100 text-purple-800 border-purple-200'
    case 'Regular':
      return 'bg-blue-100 text-blue-800 border-blue-200'
    case 'Inactive':
      return 'bg-slate-100 text-slate-600 border-slate-200'
    default:
      return 'bg-slate-100 text-slate-700'
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Customer Relationship Management (CRM)</h1>
        <p class="text-xs text-slate-500">Collector profiles, purchase history LTV, behavioral segmentation, and centralized inquiry inbox.</p>
      </div>

      <!-- Tab Switcher -->
      <div class="flex gap-1 p-1 bg-slate-100 rounded-xl border border-slate-200">
        <button
          type="button"
          class="px-4 py-2 rounded-lg text-xs font-bold transition-all cursor-pointer"
          :class="activeTab === 'profiles' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
          @click="activeTab = 'profiles'"
        >
          👥 Customer Profiles ({{ adminStore.customers.length }})
        </button>
        <button
          type="button"
          class="px-4 py-2 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5"
          :class="activeTab === 'inbox' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
          @click="activeTab = 'inbox'"
        >
          <span>📥 Support Inbox</span>
          <span
            v-if="adminStore.metrics.unreadInquiriesCount > 0"
            class="px-1.5 py-0.2 rounded-full bg-rose-600 text-white text-[10px] font-extrabold"
          >
            {{ adminStore.metrics.unreadInquiriesCount }}
          </span>
        </button>
      </div>
    </div>

    <!-- TAB 1: Customer Profiles & Segmentation -->
    <div v-if="activeTab === 'profiles'" class="space-y-4">
      <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="relative w-full md:w-80">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search collectors by name, email, or city..."
            class="w-full text-xs px-3.5 py-2.5 pl-9 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none focus:border-rose-500"
          />
          <span class="absolute left-3 top-2.5 text-slate-400 text-xs">🔍</span>
        </div>

        <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200 overflow-x-auto w-full md:w-auto">
          <button
            v-for="seg in (['All', 'VIP', 'Regular', 'Wholesale', 'Inactive'] as const)"
            :key="seg"
            type="button"
            class="px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all cursor-pointer"
            :class="selectedSegment === seg ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
            @click="selectedSegment = seg"
          >
            {{ seg }}
          </button>
        </div>
      </div>

      <!-- Customers Table -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200/80 text-slate-500 uppercase font-bold text-[10px] tracking-wider">
                <th class="p-4">Customer Name</th>
                <th class="p-4">Contact &amp; Location</th>
                <th class="p-4">Segment Rank</th>
                <th class="p-4">Lifetime Value (LTV)</th>
                <th class="p-4">Total Orders</th>
                <th class="p-4">Last Active</th>
                <th class="p-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="c in filteredCustomers" :key="c.id" class="hover:bg-slate-50/70 transition-colors">
                <td class="p-4">
                  <span class="font-bold text-slate-900 text-xs block">{{ c.name }}</span>
                  <span class="font-mono text-[10px] text-slate-400">{{ c.id }}</span>
                </td>
                <td class="p-4">
                  <span class="text-slate-800 block">{{ c.email }}</span>
                  <span class="text-[11px] text-slate-400 block">{{ c.phone }} &bull; {{ c.city }}</span>
                </td>
                <td class="p-4">
                  <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border" :class="getSegmentBadge(c.segment)">
                    {{ c.segment }}
                  </span>
                </td>
                <td class="p-4">
                  <span class="font-extrabold text-slate-900">{{ formatCurrency(c.lifetimeValue) }}</span>
                </td>
                <td class="p-4 font-bold text-slate-700">
                  {{ c.totalOrders }} order(s)
                </td>
                <td class="p-4 text-slate-500">
                  {{ c.lastOrderDate }}
                </td>
                <td class="p-4 text-right">
                  <button
                    type="button"
                    class="px-3 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold cursor-pointer"
                    @click="openProfile(c)"
                  >
                    View CRM Card
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 2: Support & Messaging Inbox -->
    <div v-else class="space-y-4">
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
        <div
          v-for="inq in adminStore.inquiries"
          :key="inq.id"
          class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-slate-50/50 transition-colors"
        >
          <div class="space-y-1.5 flex-1">
            <div class="flex items-center gap-2">
              <span
                class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                :class="inq.type === 'dispute' ? 'bg-rose-100 text-rose-800' : inq.type === 'review' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800'"
              >
                {{ inq.type }}
              </span>
              <span class="text-xs font-extrabold text-slate-900">{{ inq.subject }}</span>
              <span v-if="inq.status === 'unread'" class="w-2 h-2 rounded-full bg-rose-600" title="Unread"></span>
            </div>
            <p class="text-xs text-slate-600 line-clamp-2">"{{ inq.message }}"</p>
            <div class="flex items-center gap-3 text-[11px] text-slate-400">
              <span class="font-bold text-slate-700">{{ inq.customerName }} ({{ inq.email }})</span>
              <span>&bull;</span>
              <span>{{ inq.date }}</span>
            </div>
          </div>

          <div class="flex items-center gap-2 self-start md:self-auto">
            <button
              v-if="inq.status !== 'resolved'"
              type="button"
              class="px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs cursor-pointer shadow-2xs"
              @click="openReply(inq)"
            >
              Reply &amp; Resolve
            </button>
            <span v-else class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200">
              ✓ Resolved
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Customer CRM Detail Modal -->
    <teleport to="body">
      <div v-if="isProfileModalOpen && selectedCustomer" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-slate-200 font-display">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <h3 class="text-base font-bold text-slate-900">{{ selectedCustomer.name }}</h3>
              <p class="text-xs text-slate-500">{{ selectedCustomer.email }}</p>
            </div>
            <button type="button" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center cursor-pointer" @click="isProfileModalOpen = false">
              ✕
            </button>
          </div>

          <div class="space-y-3 text-xs">
            <div class="grid grid-cols-2 gap-3">
              <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <span class="text-[10px] font-bold text-slate-400 uppercase">Lifetime Value (LTV)</span>
                <p class="text-base font-black text-slate-900 mt-0.5">{{ formatCurrency(selectedCustomer.lifetimeValue) }}</p>
              </div>
              <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <span class="text-[10px] font-bold text-slate-400 uppercase">Orders Completed</span>
                <p class="text-base font-black text-slate-900 mt-0.5">{{ selectedCustomer.totalOrders }}</p>
              </div>
            </div>

            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
              <span class="text-[10px] font-bold text-slate-400 uppercase">Collector Behavioral Segment</span>
              <div class="pt-1">
                <select v-model="selectedCustomer.segment" class="text-xs font-bold p-1.5 rounded-lg border border-slate-300 bg-white">
                  <option value="VIP">👑 VIP Collector</option>
                  <option value="Regular">Regular Buyer</option>
                  <option value="Wholesale">💼 Wholesale Partner</option>
                  <option value="Inactive">Inactive</option>
                </select>
              </div>
            </div>

            <div v-if="selectedCustomer.notes" class="p-3 bg-amber-50 rounded-xl border border-amber-200/80">
              <span class="text-[10px] font-bold text-amber-800 uppercase">Collector Internal Notes</span>
              <p class="text-amber-900 mt-0.5">{{ selectedCustomer.notes }}</p>
            </div>
          </div>

          <div class="pt-2 border-t border-slate-100 flex justify-end">
            <button type="button" class="px-4 py-2 bg-slate-900 text-white text-xs font-bold rounded-xl cursor-pointer" @click="isProfileModalOpen = false">
              Save Profile
            </button>
          </div>
        </div>
      </div>
    </teleport>

    <!-- Reply to Inquiry Modal -->
    <teleport to="body">
      <div v-if="isReplyModalOpen && selectedInquiry" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl border border-slate-200 font-display">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <h3 class="text-base font-bold text-slate-900">Reply to {{ selectedInquiry.customerName }}</h3>
              <p class="text-xs text-slate-500">Subject: {{ selectedInquiry.subject }}</p>
            </div>
            <button type="button" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center cursor-pointer" @click="isReplyModalOpen = false">
              ✕
            </button>
          </div>

          <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-600">
            <span class="font-bold text-slate-800">Original Inquiry:</span>
            <p class="mt-1">"{{ selectedInquiry.message }}"</p>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Official Support Response</label>
            <textarea
              v-model="replyText"
              rows="5"
              class="w-full text-xs p-3 rounded-xl border border-slate-300 focus:outline-none focus:border-slate-900"
            ></textarea>
          </div>

          <div class="flex justify-between items-center pt-2">
            <span class="text-[11px] text-slate-400">Will send email response to {{ selectedInquiry.email }}</span>
            <button type="button" class="px-4 py-2 bg-slate-900 text-white font-bold text-xs rounded-xl cursor-pointer" @click="sendReply">
              Send &amp; Resolve Ticket
            </button>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>
