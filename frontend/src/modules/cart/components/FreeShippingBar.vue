<script setup lang="ts">
import { computed } from "vue";
import { formatCurrency } from "@/shared/utils/currency.util";

interface Props {
  subtotal: number;
  threshold?: number;
}

const props = withDefaults(defineProps<Props>(), {
  threshold: 50,
});

const remainingAmount = computed(() => {
  const diff = props.threshold - props.subtotal;
  return diff > 0 ? diff : 0;
});

const percentage = computed(() => {
  if (props.subtotal >= props.threshold) return 100;
  return Math.min(100, Math.round((props.subtotal / props.threshold) * 100));
});
</script>

<template>
  <div
    class="bg-slate-50 rounded-2xl p-3.5 border border-slate-200/90 space-y-1.5 font-display"
  >
    <div class="flex items-center justify-between text-xs font-semibold">
      <span
        v-if="remainingAmount > 0"
        class="text-slate-700 flex items-center gap-1.5"
      >
        <span>🚚</span>
        <span
          >Add
          <strong class="text-rose-600">{{
            formatCurrency(remainingAmount)
          }}</strong>
          more for
          <strong class="text-emerald-600 uppercase"
            >Free Nationwide Shipping!</strong
          ></span
        >
      </span>
      <span v-else class="text-emerald-700 flex items-center gap-1.5 font-bold">
        <span>🎉</span>
        <span class="uppercase">You unlocked FREE Nationwide Shipping!</span>
      </span>
      <span class="text-slate-400 font-mono text-[11px] font-bold"
        >{{ percentage }}%</span
      >
    </div>

    <!-- Animated progress bar -->
    <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
      <div
        class="bg-gradient-to-r from-rose-500 to-emerald-500 h-2 rounded-full transition-all duration-500 ease-out"
        :style="{ width: `${percentage}%` }"
      ></div>
    </div>
  </div>
</template>
