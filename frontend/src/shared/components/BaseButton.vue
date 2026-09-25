<script setup lang="ts">
interface Props {
  variant?: "primary" | "secondary" | "accent" | "outline" | "ghost" | "soft";
  size?: "sm" | "md" | "lg";
  disabled?: boolean;
  loading?: boolean;
  type?: "button" | "submit" | "reset";
  fullWidth?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  variant: "primary",
  size: "md",
  disabled: false,
  loading: false,
  type: "button",
  fullWidth: false,
});

const emit = defineEmits<{
  (e: "click", event: MouseEvent): void;
}>();

const variantClasses: Record<string, string> = {
  primary:
    "bg-slate-900 hover:bg-slate-800 text-white shadow-xs active:scale-95",
  secondary:
    "bg-slate-100 hover:bg-slate-200 text-slate-800 active:scale-95 font-bold",
  accent:
    "bg-rose-600 hover:bg-rose-700 text-white shadow-xs shadow-rose-600/20 active:scale-95",
  outline:
    "border border-slate-300 text-slate-700 hover:bg-slate-50 hover:border-slate-400 active:scale-95 bg-white",
  ghost: "text-slate-700 hover:bg-slate-100 active:scale-95",
  soft: "bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold active:scale-95",
};

const sizeClasses: Record<string, string> = {
  sm: "text-xs px-3 py-1.5 rounded-xl font-bold gap-1.5",
  md: "text-sm px-4 py-2.5 rounded-xl font-bold gap-2",
  lg: "text-base px-6 py-3 rounded-xl font-bold gap-2.5 shadow-sm",
};
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="[
      'inline-flex items-center justify-center font-display transition-all duration-200 cursor-pointer select-none',
      sizeClasses[props.size],
      variantClasses[props.variant],
      fullWidth ? 'w-full' : '',
      disabled || loading
        ? 'opacity-60 cursor-not-allowed pointer-events-none'
        : '',
    ]"
    @click="(e) => emit('click', e)"
  >
    <svg
      v-if="loading"
      class="animate-spin -ml-1 mr-2 h-4 w-4 text-current"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle
        class="opacity-25"
        cx="12"
        cy="12"
        r="10"
        stroke="currentColor"
        stroke-width="4"
      ></circle>
      <path
        class="opacity-75"
        fill="currentColor"
        d="M4 12a8 8 0 018-8v8H4z"
      ></path>
    </svg>
    <slot name="prefix"></slot>
    <slot></slot>
    <slot name="suffix"></slot>
  </button>
</template>
