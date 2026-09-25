<script setup lang="ts">
import type { ToyProduct } from "@/shared/types/toy.types";
import ToyCard from "./ToyCard.vue";

interface Props {
  toys: ToyProduct[];
}

defineProps<Props>();

const emit = defineEmits<{
  (e: "quick-view", toy: ToyProduct): void;
  (e: "reset-filters"): void;
}>();
</script>

<template>
  <div>
    <!-- Products Grid -->
    <div
      v-if="toys.length > 0"
      class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"
    >
      <ToyCard
        v-for="toy in toys"
        :key="toy.id"
        :toy="toy"
        @quick-view="(t) => emit('quick-view', t)"
      />
    </div>

    <!-- Empty State -->
    <div
      v-else
      class="bg-slate-900/90 rounded-3xl p-12 text-center border border-slate-800 shadow-xl max-w-lg mx-auto my-8 space-y-4 backdrop-blur-md"
    >
      <div class="text-6xl animate-bounce">🔍</div>
      <h3 class="text-xl font-bold font-display text-white">
        No Products Matched Your Filters
      </h3>
      <p class="text-xs text-slate-400 max-w-sm mx-auto">
        Try adjusting your budget slider, selecting a different category, or
        clearing your search term to discover more items!
      </p>
      <button
        type="button"
        class="bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs px-5 py-2.5 rounded-2xl shadow-lg shadow-amber-400/25 border border-amber-300 transition-all cursor-pointer"
        @click="emit('reset-filters')"
      >
        Reset All Filters
      </button>
    </div>
  </div>
</template>
