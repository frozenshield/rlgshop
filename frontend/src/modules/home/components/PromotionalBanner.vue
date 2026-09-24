<script setup lang="ts">
import { useRouter } from 'vue-router'
import BaseButton from '@/shared/components/BaseButton.vue'

const router = useRouter()
</script>

<template>
  <div class="rounded-3xl bg-gradient-to-r from-slate-950 via-rose-950 to-slate-900 text-white p-6 sm:p-10 shadow-lg border border-slate-800 relative overflow-hidden font-display">
    <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6 text-center md:text-left">
      <div class="space-y-2 max-w-xl">
        <span class="bg-rose-500 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">
          Collector Welcome Coupon ⚡
        </span>
        <h3 class="text-2xl sm:text-3xl font-black tracking-tight">
          Level Up Your Collection: 10% - 20% Off Drops!
        </h3>
        <p class="text-xs sm:text-sm text-slate-300 font-normal">
          Apply collector code <span class="bg-white/10 font-mono px-2 py-0.5 rounded font-bold text-amber-300">HOBBY10</span> or <span class="bg-white/10 font-mono px-2 py-0.5 rounded font-bold text-amber-300">GUNPLA20</span> at checkout on all orders!
        </p>
      </div>

      <button
        type="button"
        class="whitespace-nowrap flex-shrink-0 font-bold px-6 py-3 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white shadow-md transition-all active:scale-95 cursor-pointer text-sm"
        @click="router.push('/catalog')"
      >
        Shop Deals Now &rarr;
      </button>
    </div>
  </div>
</template>
