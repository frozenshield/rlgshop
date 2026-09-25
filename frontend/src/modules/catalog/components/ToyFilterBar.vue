<script setup lang="ts">
import { useCatalogStore } from "../catalog.store";
import {
  CATEGORIES_DATA,
  TCG_SERIES_DATA,
} from "@/shared/constants/categories.data";
import type {
  AgeGroup,
  ToyCategory,
  TcgSubCategory,
} from "@/shared/types/toy.types";
import { formatCurrency } from "@/shared/utils/currency.util";

interface Props {
  hasActiveFilters: boolean;
  totalCount: number;
}

defineProps<Props>();

const store = useCatalogStore();

const ageGroups: { id: AgeGroup | "all"; label: string }[] = [
  { id: "all", label: "All Ranks" },
  { id: "0-2", label: "0-2 Yrs (Baby)" },
  { id: "3-5", label: "3-5 Yrs (Rookie)" },
  { id: "6-8", label: "6-8 Yrs (Junior)" },
  { id: "9-12", label: "9-12 Yrs (Ace)" },
  { id: "12+", label: "12+ Yrs (Master)" },
];

const handleCategoryClick = (catId: ToyCategory | "all") => {
  store.setCategory(catId);
};

const handleTcgSeriesClick = (seriesId: TcgSubCategory | "all") => {
  store.setTcgSeries(seriesId);
};

const handleAgeClick = (age: AgeGroup | "all") => {
  store.setAgeGroup(age);
};
</script>

<template>
  <div
    class="bg-slate-900/90 rounded-3xl p-5 border border-slate-800 shadow-xl space-y-4 font-display backdrop-blur-md text-slate-200"
  >
    <!-- Row 1: Main Categories Chips -->
    <div>
      <div class="flex items-center justify-between mb-2">
        <label
          class="block text-xs font-bold uppercase tracking-wider text-slate-400"
        >
          Product Categories
        </label>
        <span class="text-xs font-semibold text-slate-400">
          {{ totalCount }} items found
        </span>
      </div>
      <div class="flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar">
        <button
          type="button"
          :class="[
            'px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap cursor-pointer flex items-center gap-2',
            store.selectedCategory === 'all'
              ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30'
              : 'bg-slate-950 text-slate-300 hover:bg-indigo-950/60 hover:text-white border border-slate-800',
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
            'px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap cursor-pointer flex items-center gap-2',
            store.selectedCategory === cat.id
              ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30'
              : 'bg-slate-950 text-slate-300 hover:bg-indigo-950/60 hover:text-white border border-slate-800',
          ]"
          @click="handleCategoryClick(cat.id)"
        >
          <span>{{ cat.icon }}</span>
          <span>{{ cat.name }}</span>
        </button>
      </div>
    </div>

    <!-- Row 1.5: TCG Subcategories -->
    <div
      v-if="
        store.selectedCategory === 'tcg' || store.selectedTcgSeries !== 'all'
      "
      class="bg-indigo-950/40 p-3.5 rounded-2xl border border-indigo-500/30 animate-fade-in space-y-2"
    >
      <div class="flex items-center justify-between">
        <div
          class="flex items-center gap-1.5 text-xs font-extrabold text-indigo-300 uppercase tracking-wider"
        >
          <span>🃏</span>
          <span>TCG Franchises &amp; Card Series:</span>
        </div>
        <button
          v-if="store.selectedTcgSeries !== 'all'"
          type="button"
          class="text-[11px] font-bold text-amber-400 hover:text-amber-300 underline cursor-pointer"
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
              ? 'bg-indigo-600 text-white shadow-sm'
              : 'bg-slate-950 text-indigo-300 hover:bg-slate-900 border border-indigo-500/30',
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
              ? 'bg-indigo-600 text-white shadow-sm'
              : 'bg-slate-950 text-slate-300 hover:bg-slate-900 border border-slate-800',
          ]"
          @click="handleTcgSeriesClick(series.id)"
        >
          <span>{{ series.icon }}</span>
          <span>{{ series.name }}</span>
        </button>
      </div>
    </div>

    <!-- Row 2: Price Slider, Sort & Quick Toggles (3-column layout without Rank/Grade) -->
    <div
      class="pt-3 border-t border-slate-800 grid grid-cols-1 md:grid-cols-3 gap-5 items-center"
    >
      <!-- Max Price Slider -->
      <div>
        <div
          class="flex items-center justify-between text-xs font-bold text-slate-400 mb-1.5"
        >
          <span class="uppercase tracking-wider">Max Budget</span>
          <span class="text-amber-400 font-bold text-sm font-mono">{{
            formatCurrency(store.maxPriceFilter)
          }}</span>
        </div>
        <input
          v-model.number="store.maxPriceFilter"
          type="range"
          min="500"
          max="15000"
          step="250"
          class="w-full accent-amber-400 cursor-pointer"
        />
        <div
          class="flex justify-between text-[10px] text-slate-500 font-semibold font-mono"
        >
          <span>{{ formatCurrency(500) }}</span>
          <span>{{ formatCurrency(15000) }}</span>
        </div>
      </div>

      <!-- Sort By Dropdown -->
      <div>
        <label
          class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2"
        >
          Sort Products
        </label>
        <select
          v-model="store.sortBy"
          class="w-full bg-slate-950 text-xs font-semibold text-white rounded-xl px-3 py-2 border border-slate-800 focus:outline-none focus:border-amber-400 cursor-pointer"
        >
          <option value="featured">⚡ Featured &amp; Hot Drops</option>
          <option value="price-asc">💵 Price: Low to High</option>
          <option value="price-desc">
            💎 Rare &amp; Collector (High to Low)
          </option>
          <option value="rating">⭐ Highest Rated</option>
          <option value="newest">🆕 New Arrivals</option>
        </select>
      </div>

      <!-- Quick Toggles & Reset -->
      <div class="flex flex-col justify-end space-y-2">
        <div
          class="flex items-center justify-between gap-3 text-xs font-semibold text-slate-300"
        >
          <label class="inline-flex items-center gap-1.5 cursor-pointer">
            <input
              v-model="store.onlyDiscounted"
              type="checkbox"
              class="rounded text-amber-400 focus:ring-amber-400 accent-amber-400"
            />
            <span>On Sale Deals</span>
          </label>

          <label class="inline-flex items-center gap-1.5 cursor-pointer">
            <input
              v-model="store.onlyInStock"
              type="checkbox"
              class="rounded text-amber-400 focus:ring-amber-400 accent-amber-400"
            />
            <span>In Stock Only</span>
          </label>
        </div>

        <button
          v-if="hasActiveFilters"
          type="button"
          class="text-xs font-bold text-amber-400 hover:text-amber-300 underline text-right cursor-pointer"
          @click="store.resetFilters"
        >
          Reset All Filters
        </button>
      </div>
    </div>
  </div>
</template>
