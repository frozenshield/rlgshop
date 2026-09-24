<script setup lang="ts">
import { ref } from 'vue'
import { useAdminStore } from '../admin.store'
import { formatCurrency } from '@/shared/utils/currency.util'

const adminStore = useAdminStore()

const period = ref<'7d' | '30d' | '90d' | 'year'>('30d')

// Dead Stock vs High Velocity items
const deadStockItems = ref([
  { sku: 'SUP-DICE-SET', name: 'Polyhedral Dice 7-Piece Set (Green Marbled)', daysInStock: 142, unitsSold30d: 0, stock: 32, tiedUpCapital: 6400 },
  { sku: 'PLSH-PKM-PIK-OLD', name: 'Vintage 2021 Edition Plush (Discontinued)', daysInStock: 110, unitsSold30d: 1, stock: 15, tiedUpCapital: 12000 },
  { sku: 'DM-DEC-STARTER-OLD', name: 'Duel Masters Season 1 Trial Deck (Japanese)', daysInStock: 95, unitsSold30d: 0, stock: 18, tiedUpCapital: 9900 },
])

const highVelocityItems = ref([
  { sku: 'TCG-OP-05-BOX', name: 'One Piece Card Game OP-05 Awakening Box', daysToStockout: 4, velocityPerDay: 3.2, stock: 12, runRate: 'Critical' },
  { sku: 'TCG-PKM-151-ETB', name: 'Pokémon TCG 151 Elite Trainer Box', daysToStockout: 2, velocityPerDay: 2.8, stock: 6, runRate: 'Critical' },
  { sku: 'FIG-GNDM-RG-RX78', name: 'RG 1/144 RX-78-2 Gundam Ver.2.0 Model Kit', daysToStockout: 3, velocityPerDay: 1.5, stock: 4, runRate: 'High' },
  { sku: 'SUP-SLV-KMC-MAT', name: 'KMC Hyper Matte Tournament Sleeves (80ct)', daysToStockout: 14, velocityPerDay: 3.1, stock: 45, runRate: 'Moderate' },
])

// Monthly sales breakdown graph simulation data
const monthlySalesBars = [
  { month: 'Apr', gross: 240000, net: 214000, height: '45%' },
  { month: 'May', gross: 290000, net: 258000, height: '55%' },
  { month: 'Jun', gross: 340000, net: 303000, height: '65%' },
  { month: 'Jul', gross: 420000, net: 375000, height: '80%' },
  { month: 'Aug', gross: 480000, net: 428000, height: '90%' },
  { month: 'Sep', gross: 540000, net: 482000, height: '100%' },
]
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Analytics &amp; Executive Reporting</h1>
        <p class="text-xs text-slate-500">Gross vs net sales breakdowns, tax compliance, inventory velocity, and buyer acquisition channels.</p>
      </div>

      <div class="flex items-center gap-1 p-1 bg-slate-100 rounded-xl border border-slate-200">
        <button
          v-for="p in (['7d', '30d', '90d', 'year'] as const)"
          :key="p"
          type="button"
          class="px-3 py-1.5 rounded-lg text-xs font-bold uppercase transition-all cursor-pointer"
          :class="period === p ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500'"
          @click="period = p"
        >
          {{ p }}
        </button>
      </div>
    </div>

    <!-- 1. Financial Breakdown Cards (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Gross Sales</span>
        <div class="text-2xl font-black text-slate-900">{{ formatCurrency(adminStore.metrics.grossSales) }}</div>
        <p class="text-[11px] text-emerald-600 font-bold">+24.8% vs last quarter</p>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Net Store Sales</span>
        <div class="text-2xl font-black text-slate-900">{{ formatCurrency(adminStore.metrics.netSales) }}</div>
        <p class="text-[11px] text-slate-400">After refunds &amp; discounts</p>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">VAT Collected (12%)</span>
        <div class="text-2xl font-black text-indigo-700">{{ formatCurrency(adminStore.metrics.totalTax) }}</div>
        <p class="text-[11px] text-slate-400">BIR Remittance balance</p>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Est. Logistics Expense</span>
        <div class="text-2xl font-black text-slate-900">{{ formatCurrency(14250) }}</div>
        <p class="text-[11px] text-slate-400">Courier billable waybills</p>
      </div>
    </div>

    <!-- 2. Revenue Trend Histogram -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Gross vs Net Sales Trajectory</h3>
          <p class="text-xs text-slate-500">6-Month historical performance (April - September 2026)</p>
        </div>
        <div class="flex items-center gap-4 text-xs font-bold">
          <div class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded bg-slate-900"></span>
            <span>Gross Sales</span>
          </div>
          <div class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded bg-rose-500"></span>
            <span>Net Sales</span>
          </div>
        </div>
      </div>

      <!-- Histogram Bars -->
      <div class="h-56 flex items-end justify-between gap-4 pt-6 px-4 border-b border-slate-100">
        <div
          v-for="bar in monthlySalesBars"
          :key="bar.month"
          class="flex-1 flex flex-col items-center gap-2 h-full justify-end group cursor-pointer"
        >
          <div class="text-[11px] font-mono font-bold text-slate-700 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
            {{ formatCurrency(bar.gross) }}
          </div>
          <div class="w-full max-w-[48px] flex gap-1 items-end h-full justify-center">
            <div class="w-1/2 bg-slate-900 rounded-t-md transition-all group-hover:bg-slate-700" :style="{ height: bar.height }"></div>
            <div class="w-1/2 bg-rose-500 rounded-t-md transition-all group-hover:bg-rose-600" :style="{ height: `calc(${bar.height} * 0.88)` }"></div>
          </div>
          <span class="text-xs font-bold text-slate-500 mt-2">{{ bar.month }}</span>
        </div>
      </div>
    </div>

    <!-- 3. Inventory Reports (High Velocity vs Dead Stock) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- High Velocity / Fast Depletion -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">High Velocity Items (Risk of Stockout)</h3>
            <p class="text-xs text-slate-500">Fastest selling SKUs projected to stock out within 14 days</p>
          </div>
          <span class="text-xs font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded">Urgent Restock</span>
        </div>

        <div class="divide-y divide-slate-100">
          <div v-for="item in highVelocityItems" :key="item.sku" class="py-3 flex items-center justify-between text-xs">
            <div>
              <span class="font-bold text-slate-900 block truncate max-w-xs">{{ item.name }}</span>
              <p class="text-[10px] text-slate-400 font-mono">{{ item.sku }} &bull; Velocity: {{ item.velocityPerDay }} units/day</p>
            </div>
            <div class="text-right">
              <span class="font-bold text-rose-600 block">{{ item.daysToStockout }} days of stock left</span>
              <span class="text-[10px] text-slate-500">Only {{ item.stock }} units remaining</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Dead Stock Analysis -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Dead Stock &amp; Stagnant Inventory</h3>
            <p class="text-xs text-slate-500">Products with 0 units moved in the last 30 days</p>
          </div>
          <span class="text-xs font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded">Tied Capital</span>
        </div>

        <div class="divide-y divide-slate-100">
          <div v-for="item in deadStockItems" :key="item.sku" class="py-3 flex items-center justify-between text-xs">
            <div>
              <span class="font-bold text-slate-900 block truncate max-w-xs">{{ item.name }}</span>
              <p class="text-[10px] text-slate-400 font-mono">{{ item.sku }} &bull; In storage for {{ item.daysInStock }} days</p>
            </div>
            <div class="text-right">
              <span class="font-bold text-slate-900 block">{{ formatCurrency(item.tiedUpCapital) }}</span>
              <span class="text-[10px] text-rose-600 font-semibold">{{ item.stock }} idle units</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
