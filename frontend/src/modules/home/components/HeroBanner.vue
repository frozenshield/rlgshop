<script setup lang="ts">
import { useRouter } from 'vue-router'
import BaseButton from '@/shared/components/BaseButton.vue'

const emit = defineEmits<{
  (e: 'open-advisor'): void
}>()

const router = useRouter()
</script>

<template>
  <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 text-white shadow-2xl border border-slate-800 p-8 sm:p-12 lg:p-16 font-display">
    <!-- Subtle Modern Glow Accents -->
    <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-rose-600/15 blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-24 -left-24 w-96 h-96 rounded-full bg-indigo-600/15 blur-3xl pointer-events-none"></div>

    <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
      <!-- Left Content (7 cols) -->
      <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider text-rose-300 border border-white/10 shadow-xs">
          <span>✨</span>
          <span>RLG HOBBY SHOP &bull; OFFICIAL IMPORTS VAULT</span>
        </div>

        <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-[1.1]">
          Build, Collect &amp; Battle. <br />
          <span class="bg-gradient-to-r from-rose-400 via-amber-300 to-indigo-400 bg-clip-text text-transparent">
            Your Premier Hobby Store.
          </span>
        </h1>

        <p class="text-sm sm:text-base text-slate-300 max-w-xl font-normal leading-relaxed mx-auto lg:mx-0">
          Discover factory-sealed Trading Card Game booster boxes, authentic Japanese Bandai Gunpla kits, detailed anime scale figures, and premium card sleeves — shipped securely across the Philippines.
        </p>

        <!-- CTA Buttons -->
        <div class="flex flex-wrap items-center justify-center lg:justify-start gap-3 sm:gap-4 pt-2">
          <button
            type="button"
            class="px-6 py-3.5 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm sm:text-base shadow-lg shadow-rose-900/40 transition-all active:scale-95 cursor-pointer flex items-center gap-2"
            @click="router.push('/catalog')"
          >
            <span>Explore All Products</span>
            <span>&rarr;</span>
          </button>

          <button
            type="button"
            class="px-6 py-3.5 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-100 font-bold text-sm sm:text-base transition-all active:scale-95 flex items-center gap-2 cursor-pointer border border-slate-700 shadow-sm"
            @click="emit('open-advisor')"
          >
            <span>🎯</span>
            <span>Hobby Matcher</span>
          </button>
        </div>

        <!-- Trust Badges -->
        <div class="pt-4 flex flex-wrap items-center justify-center lg:justify-start gap-4 sm:gap-6 text-xs font-semibold text-slate-400">
          <div class="flex items-center gap-2">
            <span class="text-amber-400">⭐</span>
            <span class="text-slate-200 font-bold">4.9 / 5</span>
            <span>(10,000+ Hobbyists)</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-emerald-400">🛡️</span>
            <span>100% Factory Sealed &amp; Mint</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-rose-400">⚡</span>
            <span>Fast PH Dispatch</span>
          </div>
        </div>
      </div>

      <!-- Right Visual Grid (5 cols) -->
      <div class="lg:col-span-5 relative flex justify-center">
        <div class="relative w-72 sm:w-84 aspect-square">
          <!-- Main Hero Image Frame -->
          <div class="w-full h-full rounded-3xl overflow-hidden border border-slate-700/80 shadow-2xl transform rotate-1 hover:rotate-0 transition-transform duration-500 bg-slate-800">
            <img
              src="https://images.unsplash.com/photo-1613771404784-3a5686aa2be3?w=700&auto=format&fit=crop&q=80"
              alt="Hobby Collectibles & TCG"
              class="w-full h-full object-cover"
            />
          </div>

          <!-- Floating Badge Top Left -->
          <div class="absolute -top-4 -left-4 bg-slate-900/90 backdrop-blur-md text-white rounded-2xl p-3 shadow-xl border border-slate-700 flex items-center gap-2.5">
            <span class="text-xl">🃏</span>
            <div class="text-left font-display">
              <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Trading Cards</p>
              <p class="text-xs font-bold text-rose-400">Factory Sealed TCG</p>
            </div>
          </div>

          <!-- Floating Badge Bottom Right -->
          <div class="absolute -bottom-4 -right-4 bg-slate-900/90 backdrop-blur-md text-white rounded-2xl p-3 shadow-xl border border-slate-700 flex items-center gap-2.5">
            <span class="text-xl">🤖</span>
            <div class="text-left font-display">
              <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Model Kits</p>
              <p class="text-xs font-bold text-amber-400">Bandai Gunpla Imports</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
