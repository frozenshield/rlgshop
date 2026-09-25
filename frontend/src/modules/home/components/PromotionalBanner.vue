<script setup lang="ts">
import { useRouter } from "vue-router";
import BaseButton from "@/shared/components/BaseButton.vue";

const router = useRouter();
</script>

<template>
  <div
    class="rounded-3xl bg-gradient-to-r from-slate-950 via-indigo-950/70 to-slate-900 text-white p-6 sm:p-10 shadow-2xl border border-indigo-500/30 relative overflow-hidden font-display"
  >
    <div
      class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6 text-center md:text-left"
    >
      <div class="space-y-2 max-w-xl">
        <span
          class="bg-indigo-600 text-white text-xs font-black px-3 py-1 rounded-full uppercase tracking-wider shadow-sm"
        >
          Collector Welcome Coupon ⚡
        </span>
        <h3 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
          Level Up Your Collection: 10% - 20% Off Drops!
        </h3>
        <p class="text-xs sm:text-sm text-slate-300 font-normal">
          Apply collector code
          <span
            class="bg-indigo-950 border border-indigo-500/40 font-mono px-2 py-0.5 rounded font-bold text-amber-300"
            >HOBBY10</span
          >
          or
          <span
            class="bg-indigo-950 border border-indigo-500/40 font-mono px-2 py-0.5 rounded font-bold text-amber-300"
            >GUNPLA20</span
          >
          at checkout on all orders!
        </p>
      </div>

      <button
        type="button"
        class="whitespace-nowrap flex-shrink-0 font-black px-6 py-3.5 rounded-2xl bg-amber-400 hover:bg-amber-300 text-slate-950 shadow-lg shadow-amber-400/25 border border-amber-300 transition-all active:scale-95 cursor-pointer text-sm"
        @click="router.push('/catalog')"
      >
        Shop Deals Now &rarr;
      </button>
    </div>
  </div>
</template>
