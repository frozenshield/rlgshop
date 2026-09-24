<script setup lang="ts">
import { useCatalogStore } from '../catalog.store'
import { CATEGORIES_DATA, TCG_SERIES_DATA } from '@/shared/constants/categories.data'
import type { AgeGroup, ToyCategory, TcgSubCategory } from '@/shared/types/toy.types'
import { formatCurrency } from '@/shared/utils/currency.util'

interface Props {
  hasActiveFilters: boolean
  totalCount: number
}

defineProps<Props>()

const store = useCatalogStore()

const ageGroups: { id: AgeGroup | 'all'; label: string }[] = [
  { id: 'all', label: 'All Ranks' },
  { id: '0-2', label: '0-2 Yrs (Baby)' },
  { id: '3-5', label: '3-5 Yrs (Rookie)' },
  { id: '6-8', label: '6-8 Yrs (Junior)' },
  { id: '9-12', label: '9-12 Yrs (Ace)' },
  { id: '12+', label: '12+ Yrs (Master)' },
]

const handleCategoryClick = (catId: ToyCategory | 'all') => {
  store.setCategory(catId)
}

const handleTcgSeriesClick = (seriesId: TcgSubCategory | 'all') => {
  store.setTcgSeries(seriesId)
}

const handleAgeClick = (age: AgeGroup | 'all') => {
  store.setAgeGroup(age)
}
</script>

<template>
  <div class="bg-white rounded-3xl p-5 border border-amber-200/70 shadow-sm space-y-4 font-display">
    <!-- Row 1: Main Categories Chips -->
    <div>
      <div class="flex items-center justify-between mb-2">
        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-400">
          Product Categories
        </label>
        <span class="text-xs font-bold text-slate-400">
          {{ totalCount }} items found
        </span>
      </div>
      <div class="flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar">
        <button
          type="button"
          :class="[
            'px-4 py-2.5 rounded-2xl text-xs font-bold transition-all whitespace-nowrap cursor-pointer flex items-center gap-2',
            store.selectedCategory === 'all'
              ? 'bg-red-600 text-white shadow-md shadow-red-200'
              : 'bg-slate-100 text-slate-700 hover:bg-slate-200',
          ]"
          @click="handleCategoryClick('all')"
        >
          <span>🌟</span>
          <span>All Products</span>
        </button>

        <button
          v-for="cat in CATEGORIES_DATA"
          :key="cat.id"
          type="button"
          :class="[
            'px-4 py-2.5 rounded-2xl text-xs font-bold transition-all whitespace-nowrap cursor-pointer flex items-center gap-2',
            store.selectedCategory === cat.id
              ? 'bg-red-600 text-white shadow-md shadow-red-200'
              : 'bg-slate-100 text-slate-700 hover:bg-slate-200',
          ]"
          @click="handleCategoryClick(cat.id)"
        >
          <span>{{ cat.icon }}</span>
          <span>{{ cat.name }}</span>
        </button>
      </div>
    </div>

    <!-- Row 1.5: TCG Subcategories (Shown when TCG is selected or whenever trainer wants TCG series) -->
    <div
      v-if="store.selectedCategory === 'tcg' || store.selectedTcgSeries !== 'all'"
      class="bg-blue-50/70 p-3.5 rounded-2xl border border-blue-200/80 animate-fade-in space-y-2"
    >
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-1.5 text-xs font-extrabold text-blue-900 uppercase tracking-wider">
          <span>🃏</span>
          <span>TCG Franchises &amp; Card Series:</span>
        </div>
        <button
          v-if="store.selectedTcgSeries !== 'all'"
          type="button"
          class="text-[11px] font-bold text-blue-600 hover:text-blue-800 underline cursor-pointer"
          @click="handleTcgSeriesClick('all')"
        >
          Show All TCG Cards
        </button>
      </div>

      <div class="flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar">
        <button
          type="button"
          :class="[
            'px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap cursor-pointer flex items-center gap-1.5',
            store.selectedTcgSeries === 'all'
              ? 'bg-blue-600 text-white shadow-sm'
              : 'bg-white text-blue-900 hover:bg-blue-100/70 border border-blue-200',
          ]"
          @click="handleTcgSeriesClick('all')"
        >
          <span>🃏</span>
          <span>All TCG Games</span>
        </button>

        <button
          v-for="series in TCG_SERIES_DATA"
          :key="series.id"
          type="button"
          :class="[
            'px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap cursor-pointer flex items-center gap-1.5',
            store.selectedTcgSeries === series.id
              ? 'bg-blue-600 text-white shadow-sm'
              : 'bg-white text-slate-700 hover:bg-blue-100/70 border border-blue-200',
          ]"
          @click="handleTcgSeriesClick(series.id)"
        >
          <span>{{ series.icon }}</span>
          <span>{{ series.name }}</span>
        </button>
      </div>
    </div>

    <!-- Row 2: Age Group, Price Slider, Sort & Quick Toggles -->
    <div class="pt-3 border-t border-slate-100 grid grid-cols-1 md:grid-cols-4 gap-5 items-center">
      <!-- Age Group Filter -->
      <div>
        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-400 mb-2">
          Target Age / Rank
        </label>
        <div class="flex flex-wrap gap-1.5">
          <button
            v-for="age in ageGroups"
            :key="age.id"
            type="button"
            :class="[
              'px-2.5 py-1 rounded-xl text-xs font-bold transition-all cursor-pointer',
              store.selectedAgeGroup === age.id
                ? 'bg-amber-400 text-slate-900 font-extrabold shadow-sm'
                : 'bg-amber-50/80 text-amber-900 hover:bg-amber-100',
            ]"
            @click="handleAgeClick(age.id)"
          >
            {{ age.label }}
          </button>
        </div>
      </div>

      <!-- Max Price Slider -->
      <div>
        <div class="flex items-center justify-between text-xs font-extrabold text-slate-400 mb-1.5">
          <span class="uppercase tracking-wider">Max Budget</span>
          <span class="text-red-600 font-bold text-sm">{{ formatCurrency(store.maxPriceFilter) }}</span>
        </div>
        <input
          v-model.number="store.maxPriceFilter"
          type="range"
          min="20"
          max="120"
          step="5"
          class="w-full accent-red-600 cursor-pointer"
        />
        <div class="flex justify-between text-[10px] text-slate-400 font-semibold">
          <span>{{ formatCurrency(20) }}</span>
          <span>{{ formatCurrency(120) }}</span>
        </div>
      </div>

      <!-- Sort By Dropdown -->
      <div>
        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-400 mb-2">
          Sort Items
        </label>
        <select
          v-model="store.sortBy"
          class="w-full bg-slate-100 text-xs font-bold text-slate-700 rounded-xl px-3 py-2 border border-slate-200 focus:outline-none focus:border-red-500 cursor-pointer"
        >
          <option value="featured">⚡ Featured &amp; Hot Drops</option>
          <option value="price-asc">💵 Price: Low to High</option>
          <option value="price-desc">💎 Rare &amp; Collector (High to Low)</option>
          <option value="rating">⭐ Highest Rated</option>
          <option value="newest">🆕 New Arrivals</option>
        </select>
      </div>

      <!-- Quick Toggles & Reset -->
      <div class="flex flex-col justify-end space-y-2">
        <div class="flex items-center justify-between gap-3 text-xs font-bold text-slate-600">
          <label class="inline-flex items-center gap-1.5 cursor-pointer">
            <input
              v-model="store.onlyDiscounted"
              type="checkbox"
              class="rounded text-red-600 focus:ring-red-500 accent-red-600"
            />
            <span>On Sale Deals</span>
          </label>

          <label class="inline-flex items-center gap-1.5 cursor-pointer">
            <input
              v-model="store.onlyInStock"
              type="checkbox"
              class="rounded text-red-600 focus:ring-red-500 accent-red-600"
            />
            <span>In Stock Only</span>
          </label>
        </div>

        <button
          v-if="hasActiveFilters"
          type="button"
          class="text-xs font-bold text-red-600 hover:text-red-700 underline text-right cursor-pointer"
          @click="store.resetFilters"
        >
          Reset All Filters
        </button>
      </div>
    </div>
  </div>
</template>
