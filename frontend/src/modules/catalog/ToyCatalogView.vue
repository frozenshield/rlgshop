<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useCatalogStore } from "./catalog.store";
import { useCatalogFilterComposable } from "./catalog-filter.composable";
import ToyFilterBar from "./components/ToyFilterBar.vue";
import ToyGrid from "./components/ToyGrid.vue";
import ToyDetailModal from "./components/ToyDetailModal.vue";

const catalogStore = useCatalogStore();
const gridTopRef = ref<HTMLElement | null>(null);

onMounted(() => {
  catalogStore.fetchProducts();
});

const {
  filteredToys,
  paginatedToys,
  currentPage,
  itemsPerPage,
  pageSizeOptions,
  totalPages,
  paginationStart,
  paginationEnd,
  displayedPages,
  setPage,
  prevPage,
  nextPage,
  hasActiveFilters,
  totalResults,
  selectedToyForModal,
  isDetailModalOpen,
  openDetailModal,
  closeDetailModal,
  resetFilters,
} = useCatalogFilterComposable();

const scrollToGrid = () => {
  if (gridTopRef.value) {
    gridTopRef.value.scrollIntoView({ behavior: "smooth", block: "start" });
  }
};

const handleSetPage = (p: number | string) => {
  if (typeof p === "number") {
    setPage(p);
    scrollToGrid();
  }
};

const handlePrev = () => {
  prevPage();
  scrollToGrid();
};

const handleNext = () => {
  nextPage();
  scrollToGrid();
};
</script>

<template>
  <div class="min-h-screen py-8 font-display">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
      <!-- Catalog Page Header -->
      <div
        class="flex flex-col md:flex-row md:items-end justify-between gap-4 bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950/60 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-indigo-500/25 relative overflow-hidden font-display"
      >
        <!-- Rich Brand Glow Accent -->
        <div
          class="absolute -top-16 -right-16 w-64 h-64 rounded-full bg-indigo-600/25 blur-3xl pointer-events-none"
        ></div>

        <div class="space-y-2 relative z-10">
          <div
            class="inline-flex items-center gap-2 bg-indigo-950/80 backdrop-blur-md px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider text-indigo-300 border border-indigo-500/30"
          >
            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
            <span>Official Hobby &amp; TCG Vault</span>
          </div>
          <h1 class="text-2xl sm:text-4xl font-black tracking-tight text-white">
            Explore Trading Cards, Anime Figures &amp; Collectibles
          </h1>
          <p class="text-xs sm:text-sm text-slate-300 max-w-xl font-normal">
            Factory-sealed booster boxes, authentic Japanese Bandai model kits,
            collectible scale figures, and protective card supplies.
          </p>
        </div>

        <div
          class="flex items-center gap-2 bg-slate-900/80 backdrop-blur-md px-4 py-2.5 rounded-2xl border border-slate-700 self-start md:self-end relative z-10"
        >
          <span class="text-base">📦</span>
          <span class="text-xs font-bold text-amber-300"
            >{{ totalResults }} Items Available</span
          >
        </div>
      </div>

      <!-- Filters & Sorting Bar -->
      <ToyFilterBar
        :has-active-filters="hasActiveFilters"
        :total-count="totalResults"
      />

      <!-- Scroll target for pagination anchor -->
      <div ref="gridTopRef" class="scroll-mt-24"></div>

      <!-- Products Grid (Paginated View) -->
      <ToyGrid
        :toys="paginatedToys"
        @quick-view="openDetailModal"
        @reset-filters="resetFilters"
      />

      <!-- Storefront Pagination Controls -->
      <div
        v-if="totalResults > 0"
        class="bg-white/80 backdrop-blur-md rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4 font-display"
      >
        <!-- Left: Showing Count & Range Info -->
        <div class="flex items-center gap-2 text-xs text-slate-600 font-medium">
          <span>Showing</span>
          <span class="font-bold text-slate-900 font-mono">{{ paginationStart }}</span>
          <span>–</span>
          <span class="font-bold text-slate-900 font-mono">{{ paginationEnd }}</span>
          <span>of</span>
          <span class="font-extrabold text-indigo-700 font-mono">{{ totalResults }}</span>
          <span>products</span>
          <span class="text-slate-300">|</span>
          <span class="text-slate-500">Page {{ currentPage }} of {{ totalPages }}</span>
        </div>

        <!-- Center: Pagination Controls -->
        <div class="flex items-center gap-1.5 flex-wrap justify-center">
          <!-- First Page -->
          <button
            type="button"
            :disabled="currentPage === 1"
            class="px-2.5 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-extrabold text-slate-600 hover:bg-slate-50 hover:text-slate-900 disabled:opacity-40 disabled:cursor-not-allowed transition-all cursor-pointer shadow-xs"
            title="First Page"
            @click="handleSetPage(1)"
          >
            &laquo;
          </button>

          <!-- Prev Page -->
          <button
            type="button"
            :disabled="currentPage === 1"
            class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-600 hover:bg-slate-50 hover:text-slate-900 disabled:opacity-40 disabled:cursor-not-allowed transition-all cursor-pointer shadow-xs flex items-center gap-1"
            @click="handlePrev"
          >
            <span>&lsaquo;</span>
            <span class="hidden sm:inline">Prev</span>
          </button>

          <!-- Page Numbers & Ellipses -->
          <template v-for="(p, idx) in displayedPages" :key="idx">
            <span
              v-if="p === '...'"
              class="px-2 py-1 text-slate-400 font-extrabold text-xs select-none"
            >
              &hellip;
            </span>
            <button
              v-else
              type="button"
              class="min-w-8 h-8 px-2.5 rounded-xl text-xs font-extrabold transition-all cursor-pointer shadow-xs"
              :class="
                currentPage === p
                  ? 'bg-slate-900 text-white shadow-slate-900/20 border border-slate-900'
                  : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'
              "
              @click="handleSetPage(p)"
            >
              {{ p }}
            </button>
          </template>

          <!-- Next Page -->
          <button
            type="button"
            :disabled="currentPage === totalPages"
            class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-600 hover:bg-slate-50 hover:text-slate-900 disabled:opacity-40 disabled:cursor-not-allowed transition-all cursor-pointer shadow-xs flex items-center gap-1"
            @click="handleNext"
          >
            <span class="hidden sm:inline">Next</span>
            <span>&rsaquo;</span>
          </button>

          <!-- Last Page -->
          <button
            type="button"
            :disabled="currentPage === totalPages"
            class="px-2.5 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-extrabold text-slate-600 hover:bg-slate-50 hover:text-slate-900 disabled:opacity-40 disabled:cursor-not-allowed transition-all cursor-pointer shadow-xs"
            title="Last Page"
            @click="handleSetPage(totalPages)"
          >
            &raquo;
          </button>
        </div>

        <!-- Right: Items per page selector -->
        <div class="flex items-center gap-2 text-xs text-slate-500 font-medium">
          <span class="hidden sm:inline">Per page:</span>
          <div class="flex items-center bg-slate-100 p-0.5 rounded-xl border border-slate-200/80">
            <button
              v-for="size in pageSizeOptions"
              :key="size"
              type="button"
              class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer"
              :class="
                itemsPerPage === size
                  ? 'bg-white text-slate-900 shadow-xs'
                  : 'text-slate-500 hover:text-slate-800'
              "
              @click="itemsPerPage = size"
            >
              {{ size }}
            </button>
          </div>
        </div>
      </div>

      <!-- Quick View / Detail Modal -->
      <ToyDetailModal
        :toy="selectedToyForModal"
        :is-open="isDetailModalOpen"
        @close="closeDetailModal"
      />
    </div>
  </div>
</template>
