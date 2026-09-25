<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";
import { useRouter } from "vue-router";
import BaseButton from "@/shared/components/BaseButton.vue";

const emit = defineEmits<{
  (e: "open-advisor"): void;
}>();

const router = useRouter();

const scrollY = ref(0);
let ticking = false;

const onScroll = () => {
  if (!ticking) {
    window.requestAnimationFrame(() => {
      scrollY.value = window.scrollY || window.pageYOffset;
      ticking = false;
    });
    ticking = true;
  }
};

onMounted(() => {
  window.addEventListener("scroll", onScroll, { passive: true });
});

onUnmounted(() => {
  window.removeEventListener("scroll", onScroll);
});
</script>

<template>
  <div
    class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 text-white shadow-2xl border border-indigo-500/20 p-8 sm:p-12 lg:p-16 font-display group"
  >
    <!-- Rich Brand Ambient Glow Accents with Parallax Drift (30% Rich Brand Aura) -->
    <div
      class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-indigo-600/25 blur-3xl pointer-events-none will-change-transform"
      :style="{ transform: `translate3d(0, ${scrollY * 0.15}px, 0)` }"
    ></div>
    <div
      class="absolute -bottom-24 -left-24 w-96 h-96 rounded-full bg-violet-600/20 blur-3xl pointer-events-none will-change-transform"
      :style="{ transform: `translate3d(0, ${scrollY * -0.12}px, 0)` }"
    ></div>

    <!-- Background Blueprint Watermark Grid -->
    <div
      class="absolute inset-0 opacity-[0.06] bg-[radial-gradient(#818cf8_1px,transparent_1px)] [background-size:20px_20px] pointer-events-none"
    ></div>

    <div
      class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center"
    >
      <!-- Left Content (7 cols) -->
      <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
        <div
          class="inline-flex items-center gap-2 bg-indigo-950/70 backdrop-blur-md px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider text-indigo-300 border border-indigo-500/30 shadow-xs"
        >
          <span>✨</span>
          <span>RLG HOBBY SHOP &bull; OFFICIAL IMPORTS VAULT</span>
        </div>

        <h1
          class="text-3xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-[1.1] text-white"
        >
          Build, Collect &amp; Battle. <br />
          <span
            class="bg-gradient-to-r from-white via-indigo-200 to-indigo-400 bg-clip-text text-transparent"
          >
            Your Premier Hobby Store.
          </span>
        </h1>

        <p
          class="text-sm sm:text-base text-slate-300 max-w-xl font-normal leading-relaxed mx-auto lg:mx-0"
        >
          Discover factory-sealed Trading Card Game booster boxes, authentic
          Japanese Bandai Gunpla kits, detailed anime scale figures, and premium
          card sleeves — shipped securely across the Philippines.
        </p>

        <!-- CTA Buttons (10% High-Contrast Accent for Primary Action) -->
        <div
          class="flex flex-wrap items-center justify-center lg:justify-start gap-3 sm:gap-4 pt-2"
        >
          <button
            type="button"
            class="px-6 py-3.5 rounded-2xl bg-amber-400 hover:bg-amber-300 active:scale-95 text-slate-950 font-black text-sm sm:text-base shadow-lg shadow-amber-400/25 border border-amber-300 transition-all cursor-pointer flex items-center gap-2"
            @click="router.push('/catalog')"
          >
            <span>Explore All Products</span>
            <span>&rarr;</span>
          </button>

          <button
            type="button"
            class="px-6 py-3.5 rounded-2xl bg-slate-900/80 hover:bg-slate-800 active:scale-95 text-slate-200 font-bold text-sm sm:text-base transition-all flex items-center gap-2 cursor-pointer border border-slate-700 shadow-sm"
            @click="emit('open-advisor')"
          >
            <span>🎯</span>
            <span>Hobby Matcher</span>
          </button>
        </div>

        <!-- Trust Badges -->
        <div
          class="pt-4 flex flex-wrap items-center justify-center lg:justify-start gap-4 sm:gap-6 text-xs font-semibold text-slate-400"
        >
          <div class="flex items-center gap-2">
            <span class="text-amber-400">⭐</span>
            <span class="text-slate-200 font-bold">4.9 / 5</span>
            <span>(10,000+ Hobbyists)</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-indigo-400">🛡️</span>
            <span>100% Factory Sealed &amp; Mint</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-amber-400">⚡</span>
            <span>Fast PH Dispatch</span>
          </div>
        </div>
      </div>

      <!-- Right Visual Grid (5 cols) with Parallax Floating Layers -->
      <div class="lg:col-span-5 relative flex justify-center">
        <div class="relative w-72 sm:w-84 aspect-square">
          <!-- Main Hero Image Frame with gentle depth -->
          <div
            class="w-full h-full rounded-3xl overflow-hidden border border-slate-700/80 shadow-2xl transition-transform duration-700 bg-slate-800 will-change-transform"
            :style="{
              transform: `translate3d(0, ${scrollY * 0.04}px, 0) rotate(1deg)`,
            }"
          >
            <img
              src="https://images.unsplash.com/photo-1613771404784-3a5686aa2be3?w=700&auto=format&fit=crop&q=80"
              alt="Hobby Collectibles & TCG"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
            />
          </div>

          <!-- Parallax Floating Badge Top Left: TCG Cards -->
          <div
            class="absolute -top-5 -left-5 bg-slate-900/95 backdrop-blur-md text-white rounded-2xl p-3 shadow-2xl border border-slate-700 flex items-center gap-2.5 transition-transform duration-300 will-change-transform z-20"
            :style="{ transform: `translate3d(0, ${-scrollY * 0.08}px, 0)` }"
          >
            <span class="text-2xl animate-pulse">🃏</span>
            <div class="text-left font-display">
              <p
                class="text-[10px] text-slate-400 font-bold uppercase tracking-wider"
              >
                Trading Cards
              </p>
              <p class="text-xs font-bold text-rose-400">Factory Sealed TCG</p>
            </div>
          </div>

          <!-- Parallax Floating Badge Bottom Right: Gunpla Models -->
          <div
            class="absolute -bottom-5 -right-5 bg-slate-900/95 backdrop-blur-md text-white rounded-2xl p-3 shadow-2xl border border-slate-700 flex items-center gap-2.5 transition-transform duration-300 will-change-transform z-20"
            :style="{ transform: `translate3d(0, ${scrollY * 0.08}px, 0)` }"
          >
            <span class="text-2xl">🤖</span>
            <div class="text-left font-display">
              <p
                class="text-[10px] text-slate-400 font-bold uppercase tracking-wider"
              >
                Model Kits
              </p>
              <p class="text-xs font-bold text-amber-400">
                Bandai Gunpla Imports
              </p>
            </div>
          </div>

          <!-- Parallax Floating Pill Mid-Left: Authentic Japanese Import -->
          <div
            class="hidden sm:flex absolute top-1/2 -right-6 -translate-y-1/2 bg-indigo-950/90 backdrop-blur-md text-white rounded-full px-3 py-1 shadow-lg border border-indigo-700/80 items-center gap-1.5 transition-transform duration-300 will-change-transform z-20"
            :style="{ transform: `translate3d(0, ${-scrollY * 0.05}px, 0)` }"
          >
            <span class="text-xs">🇯🇵</span>
            <span
              class="text-[10px] font-bold text-indigo-300 uppercase tracking-tight"
              >Direct JP Import</span
            >
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
