<script setup lang="ts">
import { useRouter } from 'vue-router'
import { CATEGORIES_DATA, TCG_SERIES_DATA } from '@/shared/constants/categories.data'
import { useCatalogStore } from '@/modules/catalog/catalog.store'
import type { ToyCategory, TcgSubCategory } from '@/shared/types/toy.types'

const router = useRouter()
const catalogStore = useCatalogStore()

const handleCategoryClick = (catId: ToyCategory) => {
  catalogStore.setCategory(catId)
  router.push('/catalog')
}

const handleTcgSeriesClick = (e: Event, seriesId: TcgSubCategory) => {
  e.stopPropagation()
  catalogStore.setTcgSeries(seriesId)
  router.push('/catalog')
}
</script>

<template>
  <div class="space-y-4 font-display">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
          Shop by Hobby Category
        </h2>
        <p class="text-xs text-slate-500 font-medium">Explore Trading Card Games, Japanese Model Kits, Scale Figures &amp; Supplies</p>
      </div>
      <router-link
        to="/catalog"
        class="text-xs font-bold text-rose-600 hover:text-rose-700 flex items-center gap-1 cursor-pointer transition-colors"
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
        class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all flex flex-col justify-between cursor-pointer group relative overflow-hidden"
        @click="handleCategoryClick(cat.id)"
      >
        <!-- Top icon & badge -->
        <div class="flex items-start justify-between mb-4">
          <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-3xl group-hover:scale-110 transition-transform bg-slate-50 border border-slate-200/80 shadow-2xs">
            {{ cat.icon }}
          </div>
          <span class="text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wider bg-slate-100 text-slate-600 group-hover:bg-rose-50 group-hover:text-rose-600 transition-colors">
            Explore &rarr;
          </span>
        </div>

        <!-- Info -->
        <div class="space-y-2">
          <h3 class="text-base sm:text-lg font-bold text-slate-900 group-hover:text-rose-600 transition-colors">
            {{ cat.name }}
          </h3>
          <p class="text-xs text-slate-500 font-medium leading-relaxed">
            {{ cat.description }}
          </p>

          <!-- TCG Subcategories Direct Quick-Chips -->
          <div v-if="cat.id === 'tcg'" class="pt-2 flex flex-wrap gap-1.5">
            <span
              v-for="series in TCG_SERIES_DATA"
              :key="series.id"
              class="px-2.5 py-1 rounded-xl text-[11px] font-semibold bg-slate-100 text-slate-700 hover:bg-slate-900 hover:text-white transition-colors cursor-pointer border border-slate-200"
              @click="(e) => handleTcgSeriesClick(e, series.id)"
            >
              {{ series.icon }} {{ series.shortName }}
            </span>
          </div>
        </div>

        <!-- Bottom bar highlight -->
        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-medium text-slate-400 group-hover:text-slate-800 transition-colors">
          <span>Official Factory Sealed</span>
          <span class="text-rose-600 font-bold">Shop {{ cat.name.split(' ')[0] }} &rarr;</span>
        </div>
      </div>
    </div>
  </div>
</template>
