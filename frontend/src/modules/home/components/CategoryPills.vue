<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import {
  CATEGORIES_DATA,
  TCG_SERIES_DATA,
} from "@/shared/constants/categories.data";
import { useCatalogStore } from "@/modules/catalog/catalog.store";
import type { ToyCategory, TcgSubCategory } from "@/shared/types/toy.types";

const router = useRouter();
const catalogStore = useCatalogStore();
const isTcgHovered = ref(false);

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
    <div
      class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 sm:gap-6 items-stretch"
    >
      <div
        v-for="cat in CATEGORIES_DATA"
        :key="cat.id"
        class="bg-slate-900/90 rounded-3xl p-6 border border-slate-800 hover:border-indigo-500/50 shadow-md hover:shadow-xl hover:-translate-y-1 transition-all flex flex-col justify-between cursor-pointer group relative backdrop-blur-xs"
        :class="cat.id === 'tcg' ? 'overflow-visible z-20' : 'overflow-hidden'"
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

          <!-- TCG Subcategories Hover Link & Floating Menu (Zero vertical stretch on other cards) -->
          <div
            v-if="cat.id === 'tcg'"
            class="relative pt-2"
            @mouseenter="isTcgHovered = true"
            @mouseleave="isTcgHovered = false"
          >
            <!-- Hover Link Trigger -->
            <button
              type="button"
              class="w-full py-1.5 px-3 rounded-xl text-xs font-bold bg-indigo-950/70 hover:bg-indigo-900/90 text-indigo-300 hover:text-white border border-indigo-500/30 hover:border-amber-400/50 flex items-center justify-between transition-all cursor-pointer group/pill"
              @click.stop="isTcgHovered = !isTcgHovered"
            >
              <span class="flex items-center gap-1.5">
                <span>🃏</span>
                <span>Select TCG Game</span>
              </span>
              <span
                class="text-[10px] text-amber-400 font-extrabold flex items-center gap-1"
              >
                <span>9 Franchises</span>
                <svg
                  class="w-3.5 h-3.5 transition-transform"
                  :class="isTcgHovered ? 'rotate-180' : ''"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2.5"
                    d="M19 9l-7 7-7-7"
                  />
                </svg>
              </span>
            </button>

            <!-- Floating Hover Popover (Absolute overlay, zero vertical stretching) -->
            <transition
              enter-active-class="transition duration-150 ease-out"
              enter-from-class="opacity-0 translate-y-1 scale-95"
              enter-to-class="opacity-100 translate-y-0 scale-100"
              leave-active-class="transition duration-100 ease-in"
              leave-from-class="opacity-100 translate-y-0 scale-100"
              leave-to-class="opacity-0 translate-y-1 scale-95"
            >
              <div
                v-if="isTcgHovered"
                class="absolute left-0 top-full pt-1.5 w-72 sm:w-80 z-50"
                @click.stop
              >
                <div
                  class="bg-slate-900/98 backdrop-blur-xl rounded-2xl p-2.5 border border-slate-700 shadow-2xl shadow-black/80 space-y-1 font-display"
                >
                  <div
                    class="px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 border-b border-slate-800 flex items-center justify-between"
                  >
                    <span>TCG Franchise Selector</span>
                    <span class="text-amber-400 font-bold">9 Series</span>
                  </div>
                  <div
                    class="max-h-64 overflow-y-auto space-y-1 pr-1 custom-scrollbar"
                  >
                    <button
                      v-for="series in TCG_SERIES_DATA"
                      :key="series.id"
                      type="button"
                      class="w-full text-left px-2.5 py-2 rounded-xl text-xs font-bold text-slate-200 hover:text-white hover:bg-slate-800 flex items-center justify-between transition-colors cursor-pointer group/series"
                      @click="(e) => handleTcgSeriesClick(e, series.id)"
                    >
                      <div class="flex items-center gap-2.5 min-w-0">
                        <span class="text-base">{{ series.icon }}</span>
                        <div class="truncate">
                          <p
                            class="font-bold text-white group-hover/series:text-amber-300 transition-colors"
                          >
                            {{ series.name }}
                          </p>
                          <p
                            class="text-[10px] text-slate-400 font-normal truncate"
                          >
                            {{ series.shortName }} • Booster Boxes &amp; Packs
                          </p>
                        </div>
                      </div>
                      <span
                        class="text-xs text-slate-500 group-hover/series:text-amber-400 group-hover/series:translate-x-0.5 transition-all"
                        >&rarr;</span
                      >
                    </button>
                  </div>
                  <div
                    class="pt-1.5 border-t border-slate-800 px-2 flex justify-between items-center text-[10px] text-slate-400"
                  >
                    <span>Click franchise to filter</span>
                    <span
                      class="text-amber-400 font-bold hover:underline cursor-pointer"
                      @click="handleCategoryClick('tcg')"
                    >
                      All TCG &rarr;
                    </span>
                  </div>
                </div>
              </div>
            </transition>
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
