<script setup lang="ts">
interface Props {
  rating: number;
  maxStars?: number;
  reviewCount?: number;
  size?: "sm" | "md";
  showCount?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  maxStars: 5,
  size: "sm",
  showCount: true,
});
</script>

<template>
  <div class="inline-flex items-center gap-1.5">
    <div class="flex items-center text-amber-400">
      <template v-for="star in maxStars" :key="star">
        <svg
          :class="[
            size === 'sm' ? 'w-3.5 h-3.5' : 'w-4 h-4',
            star <= Math.round(rating)
              ? 'text-amber-400 fill-amber-400'
              : 'text-slate-200 fill-slate-200',
          ]"
          viewBox="0 0 24 24"
        >
          <path
            d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"
          />
        </svg>
      </template>
    </div>
    <span v-if="showCount" class="text-xs font-semibold text-slate-500">
      {{ rating.toFixed(1) }}
      <span v-if="reviewCount" class="text-slate-400">({{ reviewCount }})</span>
    </span>
  </div>
</template>
