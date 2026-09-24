<script setup lang="ts">
import { useRouter } from 'vue-router'
import BaseButton from '@/shared/components/BaseButton.vue'

const router = useRouter()
</script>

<template>
  <div class="rounded-3xl bg-gradient-to-r from-blue-700 via-indigo-700 to-red-600 text-white p-6 sm:p-10 shadow-lg relative overflow-hidden font-display">
    <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6 text-center md:text-left">
      <div class="space-y-2 max-w-xl">
        <span class="bg-amber-400 text-slate-950 text-xs font-black px-3 py-1 rounded-full uppercase tracking-wider">
          Professor Oak's Welcome Gift ⚡
        </span>
        <h3 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
          Level Up Your Deck: 10% - 20% Off Pokémon Drops!
        </h3>
        <p class="text-xs sm:text-sm text-blue-100 font-medium">
          Apply Trainer code <span class="bg-white/20 font-mono px-2 py-0.5 rounded-md font-bold text-amber-200">PIKACHU10</span> or <span class="bg-white/20 font-mono px-2 py-0.5 rounded-md font-bold text-amber-200">POKEBALL20</span> at checkout on all orders!
        </p>
      </div>

      <BaseButton
        variant="secondary"
        size="lg"
        class="whitespace-nowrap flex-shrink-0 font-extrabold"
        @click="router.push('/catalog')"
      >
        Catch Deals Now &rarr;
      </BaseButton>
    </div>
  </div>
</template>
