<script setup lang="ts">
import { computed } from 'vue'
import { formatCurrency } from '@/shared/utils/currency.util'

interface Props {
  subtotal: number
  threshold?: number
}

const props = withDefaults(defineProps<Props>(), {
  threshold: 50,
})

const remainingAmount = computed(() => {
  const diff = props.threshold - props.subtotal
  return diff > 0 ? diff : 0
})

const percentage = computed(() => {
  if (props.subtotal >= props.threshold) return 100
  return Math.min(100, Math.round((props.subtotal / props.threshold) * 100))
})
</script>

<template>
  <div class="bg-amber-50/80 rounded-2xl p-3 border border-amber-200/80 space-y-1.5">
    <div class="flex items-center justify-between text-xs font-bold">
      <span v-if="remainingAmount > 0" class="text-amber-900 flex items-center gap-1.5">
        <span>🚚</span>
        Add <span class="text-rose-600 font-extrabold">{{ formatCurrency(remainingAmount) }}</span> more for <span class="text-emerald-600 uppercase">Free Shipping!</span>
      </span>
      <span v-else class="text-emerald-700 flex items-center gap-1.5">
        <span>🎉</span>
        <span class="font-extrabold uppercase">You unlocked FREE Standard Shipping!</span>
      </span>
      <span class="text-slate-400 font-mono text-[11px]">{{ percentage }}%</span>
    </div>

    <!-- Animated progress bar -->
    <div class="w-full bg-amber-200/60 rounded-full h-2.5 overflow-hidden">
      <div
        class="bg-gradient-to-r from-amber-400 via-rose-400 to-emerald-400 h-2.5 rounded-full transition-all duration-500 ease-out"
        :style="{ width: `${percentage}%` }"
      ></div>
    </div>
  </div>
</template>
