<script setup lang="ts">
import { useRouter } from 'vue-router'
import BaseButton from '@/shared/components/BaseButton.vue'

const emit = defineEmits<{
  (e: 'open-advisor'): void
}>()

const router = useRouter()
</script>

<template>
  <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-red-600 via-rose-600 to-amber-500 text-white shadow-xl shadow-red-200/60 p-8 sm:p-12 lg:p-16 font-display">
    <!-- Playful Floating Pokeball Watermark -->
    <div class="absolute -top-16 -right-16 w-80 h-80 rounded-full bg-white/10 blur-xl pointer-events-none flex items-center justify-center">
      <div class="w-60 h-60 rounded-full border-8 border-white/20"></div>
    </div>
    <div class="absolute -bottom-16 -left-12 w-72 h-72 rounded-full bg-amber-400/20 blur-3xl pointer-events-none"></div>

    <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
      <!-- Left Content (7 cols) -->
      <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
        <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-md px-4 py-1.5 rounded-full text-xs font-extrabold uppercase tracking-widest text-amber-200 border border-white/20 shadow-xs">
          <span>⚡</span>
          <span>RLG Online Shop &bull; Official Pokémon Store</span>
        </div>

        <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-[1.1]">
          Gotta Catch 'Em All! Toys, Cards &amp; Figures
        </h1>

        <p class="text-sm sm:text-base text-rose-100 max-w-xl font-medium leading-relaxed mx-auto lg:mx-0">
          Welcome to <strong class="text-white underline decoration-amber-300">RLG Online Shop</strong>! Explore authentic Pokémon Center plushies, competitive Scarlet &amp; Violet TCG booster boxes, articulated battle figures, and replica Pokéballs.
        </p>

        <!-- CTA Buttons -->
        <div class="flex flex-wrap items-center justify-center lg:justify-start gap-3 sm:gap-4 pt-2">
          <BaseButton
            variant="secondary"
            size="lg"
            class="shadow-xl shadow-amber-900/20 text-slate-900 font-extrabold"
            @click="router.push('/catalog')"
          >
            Catch All Toys &amp; Cards 🔴
          </BaseButton>

          <button
            type="button"
            class="px-6 py-3.5 rounded-2xl bg-white/20 hover:bg-white/30 text-white font-extrabold text-sm sm:text-base backdrop-blur-md transition-all active:scale-95 flex items-center gap-2 cursor-pointer border border-white/30"
            @click="emit('open-advisor')"
          >
            <span>⚡</span>
            <span>Poké-Match Quiz</span>
          </button>
        </div>

        <!-- Trust Badges -->
        <div class="pt-4 flex flex-wrap items-center justify-center lg:justify-start gap-4 sm:gap-6 text-xs font-bold text-amber-100">
          <div class="flex items-center gap-2">
            <span class="text-lg">⭐</span>
            <span>4.9 / 5 (15,000+ Happy Trainers)</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-lg">🛡️</span>
            <span>100% Genuine Pokémon Sealed Products</span>
          </div>
        </div>
      </div>

      <!-- Right Visual Grid (5 cols) -->
      <div class="lg:col-span-5 relative flex justify-center">
        <div class="relative w-72 sm:w-84 aspect-square">
          <!-- Main Hero Image Frame -->
          <div class="w-full h-full rounded-3xl overflow-hidden border-4 border-amber-300/80 shadow-2xl transform rotate-2 hover:rotate-0 transition-transform duration-500 bg-amber-100">
            <img
              src="https://images.unsplash.com/photo-1613771404784-3a5686aa2be3?w=700&auto=format&fit=crop&q=80"
              alt="Pikachu Pokémon Plush"
              class="w-full h-full object-cover"
            />
          </div>

          <!-- Floating Little Badge Top Left -->
          <div class="absolute -top-4 -left-4 bg-white text-slate-800 rounded-2xl p-3 shadow-xl border border-red-100 flex items-center gap-2 animate-bounce">
            <span class="text-2xl">⚡</span>
            <div class="text-left font-display">
              <p class="text-[10px] text-slate-400 font-bold uppercase">Fan Favorite</p>
              <p class="text-xs font-extrabold text-amber-500">Pikachu Plush 12"</p>
            </div>
          </div>

          <!-- Floating Little Badge Bottom Right -->
          <div class="absolute -bottom-4 -right-4 bg-white text-slate-800 rounded-2xl p-3 shadow-xl border border-red-100 flex items-center gap-2">
            <span class="text-2xl">🔥</span>
            <div class="text-left font-display">
              <p class="text-[10px] text-slate-400 font-bold uppercase">Mega Construx</p>
              <p class="text-xs font-extrabold text-red-600">Charizard Motion</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
