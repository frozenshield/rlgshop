<script setup lang="ts">
interface Props {
  variant?: 'primary' | 'secondary' | 'accent' | 'outline' | 'ghost' | 'soft'
  size?: 'sm' | 'md' | 'lg'
  disabled?: boolean
  loading?: boolean
  type?: 'button' | 'submit' | 'reset'
  fullWidth?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  disabled: false,
  loading: false,
  type: 'button',
  fullWidth: false,
})

const emit = defineEmits<{
  (e: 'click', event: MouseEvent): void
}>()

const variantClasses: Record<string, string> = {
  primary:
    'bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white shadow-md shadow-red-200 active:scale-95',
  secondary:
    'bg-gradient-to-r from-amber-400 to-yellow-400 hover:from-amber-500 hover:to-yellow-500 text-slate-900 shadow-md shadow-amber-200 active:scale-95 font-extrabold',
  accent:
    'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white shadow-md shadow-blue-200 active:scale-95',
  outline:
    'border-2 border-red-500 text-red-600 hover:bg-red-50 active:scale-95 bg-white',
  ghost:
    'text-slate-700 hover:bg-amber-100/70 active:scale-95',
  soft:
    'bg-red-50 hover:bg-red-100 text-red-600 font-semibold active:scale-95',
}

const sizeClasses: Record<string, string> = {
  sm: 'text-xs px-3 py-1.5 rounded-xl font-bold gap-1.5',
  md: 'text-sm px-4 py-2.5 rounded-2xl font-bold gap-2',
  lg: 'text-base px-6 py-3.5 rounded-2xl font-bold gap-2.5 shadow-lg',
}
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
      disabled || loading ? 'opacity-60 cursor-not-allowed pointer-events-none' : '',
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
