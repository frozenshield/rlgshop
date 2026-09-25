<script setup lang="ts">
import { useRouter } from "vue-router";
import {
  CATEGORIES_DATA,
  TCG_SERIES_DATA,
} from "@/shared/constants/categories.data";
import { useCatalogStore } from "@/modules/catalog/catalog.store";
import type { ToyCategory, TcgSubCategory } from "@/shared/types/toy.types";

const router = useRouter();
const catalogStore = useCatalogStore();

const handleCategoryClick = (catId: ToyCategory) => {
  catalogStore.setCategory(catId);
  router.push("/catalog");
};

const handleTcgSeriesClick = (e: Event, seriesId: TcgSubCategory) => {
  e.stopPropagation();
  catalogStore.setTcgSeries(seriesId);
  router.push("/catalog");
};
</script>

<template>
  <div class="space-y-4 font-display">
    <div class="flex items-center justify-between">
      <div>
        <h2
          class="text-xl sm:text-2xl font-extrabold text-white tracking-tight"
        >
          Shop by Hobby Category
        </h2>
        <p class="text-xs text-slate-400 font-medium">
          Explore Trading Card Games, Japanese Model Kits, Scale Figures &amp;
          Supplies
        </p>
      </div>
      <router-link
        to="/catalog"
        class="text-xs font-bold text-amber-400 hover:text-amber-300 flex items-center gap-1 cursor-pointer transition-colors"
      >
        <span>View All Categories</span>
        <span>&rarr;</span>
      </router-link>
    </div>

    <!-- Core Featured Category Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
      <div
        v-for="cat in CATEGORIES_DATA"
        :key="cat.id"
        class="bg-slate-900/90 rounded-3xl p-6 border border-slate-800 hover:border-indigo-500/50 shadow-md hover:shadow-xl hover:-translate-y-1 transition-all flex flex-col justify-between cursor-pointer group relative overflow-hidden backdrop-blur-xs"
        @click="handleCategoryClick(cat.id)"
      >
        <!-- Top icon & badge -->
        <div class="flex items-start justify-between mb-4">
          <div
            class="w-14 h-14 rounded-2xl flex items-center justify-center text-3xl group-hover:scale-110 transition-transform bg-indigo-950/60 border border-indigo-500/30 shadow-inner"
          >
            {{ cat.icon }}
          </div>
          <span
            class="text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wider bg-indigo-950/80 text-indigo-300 border border-indigo-500/30 group-hover:bg-indigo-600 group-hover:text-white transition-colors"
          >
            Explore &rarr;
          </span>
        </div>

        <!-- Info -->
        <div class="space-y-2">
          <h3
            class="text-base sm:text-lg font-bold text-white group-hover:text-amber-400 transition-colors"
          >
            {{ cat.name }}
          </h3>
          <p class="text-xs text-slate-400 font-medium leading-relaxed">
            {{ cat.description }}
          </p>

          <!-- TCG Subcategories Direct Quick-Chips -->
          <div v-if="cat.id === 'tcg'" class="pt-2 flex flex-wrap gap-1.5">
            <span
              v-for="series in TCG_SERIES_DATA"
              :key="series.id"
              class="px-2.5 py-1 rounded-xl text-[11px] font-semibold bg-slate-950 text-indigo-300 hover:bg-amber-400 hover:text-slate-950 hover:border-amber-300 transition-colors cursor-pointer border border-indigo-500/30"
              @click="(e) => handleTcgSeriesClick(e, series.id)"
            >
              {{ series.icon }} {{ series.shortName }}
            </span>
          </div>
        </div>

        <!-- Bottom bar highlight -->
        <div
          class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-between text-xs font-medium text-slate-500 group-hover:text-slate-300 transition-colors"
        >
          <span>Official Factory Sealed</span>
          <span class="text-amber-400 font-bold"
            >Shop {{ cat.name.split(" ")[0] }} &rarr;</span
          >
        </div>
      </div>
    </div>
  </div>
</template>
