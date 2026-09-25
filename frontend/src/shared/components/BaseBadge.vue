<script setup lang="ts">
interface Props {
  variant?:
    | "primary"
    | "secondary"
    | "accent"
    | "mint"
    | "warning"
    | "purple"
    | "neutral";
  size?: "sm" | "md";
  pill?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  variant: "primary",
  size: "sm",
  pill: true,
});

const variantClasses: Record<string, string> = {
  primary: "bg-red-100 text-red-700 border border-red-200",
  secondary: "bg-amber-100 text-amber-800 border border-amber-200",
  accent: "bg-blue-100 text-blue-700 border border-blue-200",
  mint: "bg-emerald-100 text-emerald-700 border border-emerald-200",
  warning: "bg-orange-100 text-orange-800 border border-orange-200",
  purple: "bg-purple-100 text-purple-700 border border-purple-200",
  neutral: "bg-slate-100 text-slate-700 border border-slate-200",
};

const sizeClasses: Record<string, string> = {
  sm: "text-xs px-2.5 py-0.5 font-semibold",
  md: "text-sm px-3 py-1 font-bold",
};
</script>

<template>
  <span
    :class="[
      'inline-flex items-center gap-1 font-display tracking-wide transition-all',
      pill ? 'rounded-full' : 'rounded-lg',
      variantClasses[props.variant],
      sizeClasses[props.size],
    ]"
  >
    <slot name="icon"></slot>
    <slot></slot>
  </span>
</template>
