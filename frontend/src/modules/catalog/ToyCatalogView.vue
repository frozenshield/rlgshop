<script setup lang="ts">
import { useCatalogFilterComposable } from './catalog-filter.composable'
import ToyFilterBar from './components/ToyFilterBar.vue'
import ToyGrid from './components/ToyGrid.vue'
import ToyDetailModal from './components/ToyDetailModal.vue'

const {
  filteredToys,
  hasActiveFilters,
  totalResults,
  selectedToyForModal,
  isDetailModalOpen,
  openDetailModal,
  closeDetailModal,
  resetFilters,
} = useCatalogFilterComposable()
</script>

<template>
  <div class="min-h-screen py-8 font-display">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
      <!-- Catalog Page Header -->
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 bg-gradient-to-r from-red-600 via-rose-600 to-amber-500 rounded-3xl p-6 sm:p-8 text-white shadow-xl shadow-red-200/50 relative overflow-hidden">
        <!-- Background subtle Pokeball decoration -->
        <div class="absolute -right-10 -bottom-10 w-48 h-48 rounded-full border-16 border-white/10 pointer-events-none"></div>

        <div class="space-y-2 relative z-10">
          <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-xs px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
            <span class="w-2.5 h-2.5 rounded-full bg-amber-300 animate-ping"></span>
            <span>Official Pokémon &amp; Anime Vault</span>
          </div>
          <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight">
            Explore TCG Cards, Anime Figures &amp; Merch
          </h1>
          <p class="text-xs sm:text-sm text-red-100 max-w-xl font-medium">
            From factory-sealed Pokémon and One Piece booster boxes to high-grade anime statues and authentic Pokémon Center plushies.
          </p>
        </div>

        <div class="flex items-center gap-2 bg-white/15 backdrop-blur-xs px-4 py-2.5 rounded-2xl border border-white/25 self-start md:self-end relative z-10">
          <span class="text-xl">🔴</span>
          <span class="text-xs font-bold">{{ totalResults }} Items Available</span>
        </div>
      </div>

      <!-- Filters & Sorting Bar -->
      <ToyFilterBar
        :has-active-filters="hasActiveFilters"
        :total-count="totalResults"
      />

      <!-- Products Grid -->
      <ToyGrid
        :toys="filteredToys"
        @quick-view="openDetailModal"
        @reset-filters="resetFilters"
      />

      <!-- Quick View / Detail Modal -->
      <ToyDetailModal
        :toy="selectedToyForModal"
        :is-open="isDetailModalOpen"
        @close="closeDetailModal"
      />
    </div>
  </div>
</template>
