<script setup lang="ts">
import type { CartItem } from "@/shared/types/toy.types";
import { formatCurrency } from "@/shared/utils/currency.util";

interface Props {
  item: CartItem;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  (e: "update-quantity", toyId: string, quantity: number): void;
  (e: "remove", toyId: string): void;
  (e: "toggle-select", toyId: string): void;
}>();

const increment = () => {
  if (props.item.quantity < props.item.toy.stock) {
    emit("update-quantity", props.item.toy.id, props.item.quantity + 1);
  }
};

const decrement = () => {
  if (props.item.quantity > 1) {
    emit("update-quantity", props.item.toy.id, props.item.quantity - 1);
  } else {
    emit("remove", props.item.toy.id);
  }
};
</script>

<template>
  <div
    class="flex items-center gap-3 py-3 border-b border-slate-800/80 group transition-opacity"
    :class="item.selected === false ? 'opacity-55' : 'opacity-100'"
  >
    <!-- Item Selection Checkbox -->
    <label
      class="flex items-center cursor-pointer p-1 -ml-1 flex-shrink-0"
      :title="
        item.selected === false
          ? 'Click to select for checkout'
          : 'Click to unselect'
      "
    >
      <input
        type="checkbox"
        :checked="item.selected !== false"
        class="w-4 h-4 rounded text-amber-400 bg-slate-800 border-slate-700 focus:ring-amber-400 focus:ring-offset-slate-900 cursor-pointer accent-amber-400"
        @change="emit('toggle-select', item.toy.id)"
      />
    </label>

    <!-- Thumbnail -->
    <img
      :src="item.toy.imageUrl"
      :alt="item.toy.name"
      class="w-16 h-16 rounded-xl object-contain bg-slate-950 border border-slate-800 flex-shrink-0 p-1"
    />

    <!-- Info -->
    <div class="flex-1 min-w-0">
      <h4
        class="text-xs sm:text-sm font-bold text-slate-100 line-clamp-1 group-hover:text-amber-400 transition-colors"
      >
        {{ item.toy.name }}
      </h4>
      <p class="text-[11px] text-slate-400 font-semibold mb-1">
        Age: {{ item.toy.ageGroup }} | {{ item.toy.brand }}
      </p>
      <div class="flex items-center gap-2">
        <span
          class="text-xs sm:text-sm font-extrabold text-amber-400 font-mono"
        >
          {{ formatCurrency(item.toy.price) }}
        </span>
        <span
          v-if="item.toy.originalPrice"
          class="text-[11px] text-slate-500 line-through font-mono"
        >
          {{ formatCurrency(item.toy.originalPrice) }}
        </span>
      </div>
    </div>

    <!-- Stepper Quantity & Delete -->
    <div class="flex flex-col items-end gap-1.5 flex-shrink-0">
      <div
        class="flex items-center border border-slate-700 rounded-xl bg-slate-950 p-0.5"
      >
        <button
          type="button"
          class="w-6 h-6 rounded-lg bg-slate-800 shadow-xs hover:bg-slate-700 text-slate-200 text-xs font-bold flex items-center justify-center transition-colors cursor-pointer"
          title="Decrease"
          aria-label="Decrease quantity"
          @click="decrement"
        >
          -
        </button>
        <span class="w-7 text-center text-xs font-bold text-white">
          {{ item.quantity }}
        </span>
        <button
          type="button"
          :disabled="item.quantity >= item.toy.stock"
          class="w-6 h-6 rounded-lg bg-slate-800 shadow-xs hover:bg-slate-700 text-slate-200 text-xs font-bold flex items-center justify-center transition-colors disabled:opacity-40 cursor-pointer"
          title="Increase"
          aria-label="Increase quantity"
          @click="increment"
        >
          +
        </button>
      </div>

      <!-- Remove Button -->
      <button
        type="button"
        class="text-[11px] text-slate-400 hover:text-rose-400 font-semibold transition-colors flex items-center gap-1 cursor-pointer"
        @click="emit('remove', item.toy.id)"
      >
        <svg
          class="w-3.5 h-3.5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
          />
        </svg>
        <span>Remove</span>
      </button>
    </div>
  </div>
</template>
