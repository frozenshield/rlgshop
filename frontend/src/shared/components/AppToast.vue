<script setup lang="ts">
import { watch } from "vue";

const props = defineProps<{
  message: string;
  type?: "success" | "error" | "info";
  isVisible: boolean;
}>();

const emit = defineEmits<{
  (e: "close"): void;
}>();

let timer: ReturnType<typeof setTimeout> | null = null;

watch(
  () => props.isVisible,
  (newVal) => {
    if (timer) clearTimeout(timer);
    if (newVal) {
      timer = setTimeout(() => {
        emit("close");
      }, 3500);
    }
  },
);
</script>

<template>
  <teleport to="body">
    <transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="opacity-0 translate-y-4 scale-95"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="opacity-100 translate-y-0 scale-100"
      leave-to-class="opacity-0 translate-y-4 scale-95"
    >
      <div
        v-if="isVisible"
        class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 flex flex-col items-center max-w-md w-full px-4 pointer-events-auto"
      >
        <div
          class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-2xl shadow-xl border backdrop-blur-md font-display overflow-hidden relative"
          :class="
            type === 'error'
              ? 'bg-rose-950/90 text-white border-rose-800'
              : 'bg-slate-900/90 text-white border-slate-700'
          "
        >
          <div class="flex items-center gap-2.5">
            <span class="text-base">{{ type === "error" ? "⚠️" : "🎉" }}</span>
            <span class="text-xs font-bold leading-snug">{{ message }}</span>
          </div>

          <button
            type="button"
            class="p-1 text-slate-400 hover:text-white transition-colors cursor-pointer text-xs"
            @click="emit('close')"
          >
            ✕
          </button>

          <!-- Shrinking Progress Bar -->
          <div
            class="absolute bottom-0 left-0 h-0.5 bg-rose-500 w-full animate-toast-shrink"
          ></div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<style scoped>
@keyframes toastShrink {
  from {
    width: 100%;
  }
  to {
    width: 0%;
  }
}
.animate-toast-shrink {
  animation: toastShrink 3.5s linear forwards;
}
</style>
