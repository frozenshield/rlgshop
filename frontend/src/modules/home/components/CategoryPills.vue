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
          Shop by Specialty Category
        </h2>
        <p class="text-xs text-slate-500 font-medium">Explore authentic Trading Cards, Anime Figures &amp; Exclusive Merch</p>
      </div>
      <router-link
        to="/catalog"
        class="text-xs font-bold text-red-600 hover:text-red-700 flex items-center gap-1 cursor-pointer transition-colors"
      >
        <span>View All Vault</span>
        <span>&rarr;</span>
      </router-link>
    </div>

    <!-- 3 Core Featured Category Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
      <div
        v-for="cat in CATEGORIES_DATA"
        :key="cat.id"
        class="bg-white rounded-3xl p-6 border border-amber-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all flex flex-col justify-between cursor-pointer group relative overflow-hidden"
        @click="handleCategoryClick(cat.id)"
      >
        <!-- Top icon & badge -->
        <div class="flex items-start justify-between mb-4">
          <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-4xl group-hover:scale-110 transition-transform bg-amber-50/80 border border-amber-200/60 shadow-xs">
            {{ cat.icon }}
          </div>
          <span class="text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider bg-slate-100 text-slate-600 group-hover:bg-red-50 group-hover:text-red-600 transition-colors">
            Explore &rarr;
          </span>
        </div>

        <!-- Info -->
        <div class="space-y-2">
          <h3 class="text-base sm:text-lg font-extrabold text-slate-900 group-hover:text-red-600 transition-colors">
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
              class="px-2.5 py-1 rounded-xl text-[11px] font-bold bg-blue-50 text-blue-800 hover:bg-blue-600 hover:text-white transition-colors cursor-pointer border border-blue-200/60"
              @click="(e) => handleTcgSeriesClick(e, series.id)"
            >
              {{ series.icon }} {{ series.shortName }}
            </span>
          </div>
        </div>

        <!-- Bottom bar highlight -->
        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-slate-400 group-hover:text-slate-800 transition-colors">
          <span>Official Licensed Collection</span>
          <span class="text-red-600 font-extrabold">Shop {{ cat.name.split(' ')[0] }} &rarr;</span>
        </div>
      </div>
    </div>
  </div>
</template>
