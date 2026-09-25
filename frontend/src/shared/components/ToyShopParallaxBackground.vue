<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";

// Reactive scroll position for performant parallax interpolation
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
  scrollY.value = window.scrollY || window.pageYOffset;
});

onUnmounted(() => {
  window.removeEventListener("scroll", onScroll);
});
</script>

<template>
  <div
    class="fixed inset-0 pointer-events-none overflow-hidden z-0 select-none"
    aria-hidden="true"
  >
    <!-- 1. BASE THEME: Hobby Cutting Mat / Modeler Blueprint Grid -->
    <div
      class="absolute inset-0 opacity-[0.06] bg-grid-pattern"
      :style="{ transform: `translate3d(0, ${scrollY * 0.05}px, 0)` }"
    ></div>

    <!-- 2. AMBIENT GLOW ORBS (Deep Parallax Layer - 0.12x speed: 30% Rich Brand Color Lighting) -->
    <div
      class="absolute -top-32 -left-20 w-[550px] h-[550px] rounded-full bg-indigo-600/25 blur-[130px] will-change-transform pointer-events-none"
      :style="{ transform: `translate3d(0, ${scrollY * 0.12}px, 0)` }"
    ></div>
    <div
      class="absolute top-[30%] -right-28 w-[650px] h-[650px] rounded-full bg-violet-600/20 blur-[140px] will-change-transform pointer-events-none"
      :style="{ transform: `translate3d(0, ${scrollY * -0.08}px, 0)` }"
    ></div>
    <div
      class="absolute top-[65%] left-[8%] w-[550px] h-[550px] rounded-full bg-indigo-500/18 blur-[120px] will-change-transform pointer-events-none"
      :style="{ transform: `translate3d(0, ${scrollY * 0.15}px, 0)` }"
    ></div>
    <div
      class="absolute top-[110%] right-[12%] w-[500px] h-[500px] rounded-full bg-amber-400/12 blur-[110px] will-change-transform pointer-events-none"
      :style="{ transform: `translate3d(0, ${scrollY * 0.1}px, 0)` }"
    ></div>

    <!-- 3. SLOW LAYER: Watermark Hobby Store Accents (0.18x speed) -->
    <div
      class="absolute top-44 left-6 lg:left-14 will-change-transform"
      :style="{
        transform: `translate3d(0, ${scrollY * 0.18}px, 0) rotate(-12deg)`,
      }"
    >
      <div
        class="flex items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-900/80 border border-indigo-500/30 shadow-lg backdrop-blur-md text-[10px] font-black tracking-widest uppercase text-slate-300"
      >
        <span class="text-indigo-400 font-mono">01//</span>
        <span>TCG VAULT</span>
      </div>
    </div>

    <div
      class="absolute top-[650px] right-6 lg:right-16 will-change-transform"
      :style="{
        transform: `translate3d(0, ${scrollY * 0.16}px, 0) rotate(8deg)`,
      }"
    >
      <div
        class="flex items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-900/80 border border-indigo-500/30 shadow-lg backdrop-blur-md text-[10px] font-black tracking-widest uppercase text-slate-300"
      >
        <span class="text-violet-400 font-mono">02//</span>
        <span>BANDAI SPIRITS</span>
      </div>
    </div>

    <div
      class="absolute top-[1350px] left-10 lg:left-20 will-change-transform"
      :style="{
        transform: `translate3d(0, ${scrollY * 0.14}px, 0) rotate(-6deg)`,
      }"
    >
      <div
        class="flex items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-900/80 border border-indigo-500/30 shadow-lg backdrop-blur-md text-[10px] font-black tracking-widest uppercase text-slate-300"
      >
        <span class="text-amber-400 font-mono">03//</span>
        <span>SCALE FIGURES</span>
      </div>
    </div>

    <!-- 4. MID LAYER: Floating Collectibles & Holographic Icons (0.32x speed) -->
    <!-- Floating Pokémon/TCG Card Silhouette -->
    <div
      class="hidden md:block absolute top-[280px] -left-8 will-change-transform opacity-80 hover:opacity-100 transition-opacity"
      :style="{
        transform: `translate3d(0, ${scrollY * 0.28}px, 0) rotate(15deg)`,
      }"
    >
      <div
        class="w-24 h-36 rounded-xl bg-gradient-to-br from-indigo-900/60 via-slate-900/90 to-purple-900/60 p-1.5 border border-indigo-500/40 shadow-xl backdrop-blur-md animate-float-slow"
      >
        <div
          class="w-full h-full rounded-lg bg-slate-950/80 border border-indigo-500/20 flex flex-col items-center justify-between p-2"
        >
          <div
            class="w-full flex justify-between text-[8px] font-black text-indigo-300"
          >
            <span>TCG</span>
            <span class="text-amber-400">⚡ HP 180</span>
          </div>
          <span class="text-2xl filter drop-shadow-md">🃏</span>
          <div class="w-full h-1 bg-indigo-500/40 rounded-full"></div>
        </div>
      </div>
    </div>

    <!-- Floating Gunpla Mecha Crest / V-Fin -->
    <div
      class="hidden lg:block absolute top-[420px] -right-10 will-change-transform opacity-80"
      :style="{
        transform: `translate3d(0, ${scrollY * -0.25}px, 0) rotate(-18deg)`,
      }"
    >
      <div
        class="w-28 h-28 rounded-2xl bg-gradient-to-tr from-slate-900/90 via-indigo-950/80 to-slate-900/90 p-2 border border-indigo-500/40 shadow-xl backdrop-blur-md flex items-center justify-center animate-float-reverse"
      >
        <div class="text-center">
          <span class="text-3xl block">🤖</span>
          <span
            class="text-[8px] font-mono font-bold text-indigo-400 tracking-tighter uppercase block mt-1"
            >RX-78-2 VER.2.0</span
          >
        </div>
      </div>
    </div>

    <!-- Floating Polyhedral Dice / Hobby Sparkles -->
    <div
      class="absolute top-[850px] left-[5%] will-change-transform opacity-80"
      :style="{
        transform: `translate3d(0, ${scrollY * 0.35}px, 0) rotate(22deg)`,
      }"
    >
      <div
        class="w-12 h-12 rounded-xl bg-slate-900/90 border border-indigo-500/30 shadow-lg flex items-center justify-center text-lg animate-float text-slate-200"
      >
        🎲
      </div>
    </div>

    <!-- Floating Gashapon Capsule Ball -->
    <div
      class="hidden sm:block absolute top-[1050px] right-[8%] will-change-transform opacity-85"
      :style="{ transform: `translate3d(0, ${scrollY * 0.32}px, 0)` }"
    >
      <div
        class="w-16 h-16 rounded-full bg-gradient-to-b from-indigo-600/30 via-slate-900/90 to-purple-600/30 border border-indigo-400/40 p-1 shadow-xl backdrop-blur-md flex items-center justify-center animate-spin-veryslow"
      >
        <span class="text-xl">🏆</span>
      </div>
    </div>

    <!-- Floating Gold Star / Rare Collectible Badge -->
    <div
      class="absolute top-[1500px] right-[4%] will-change-transform opacity-90"
      :style="{
        transform: `translate3d(0, ${scrollY * 0.4}px, 0) rotate(-10deg)`,
      }"
    >
      <div
        class="px-2.5 py-1 rounded-full bg-amber-400/20 border border-amber-400/60 text-amber-300 text-xs font-bold flex items-center gap-1 shadow-lg shadow-amber-400/10 animate-float"
      >
        <span>⭐</span>
        <span class="text-[10px] tracking-wider font-black">ULTRA RARE</span>
      </div>
    </div>

    <!-- Floating Gaming Gem -->
    <div
      class="hidden md:block absolute top-[1750px] left-[8%] will-change-transform opacity-80"
      :style="{
        transform: `translate3d(0, ${scrollY * 0.24}px, 0) rotate(35deg)`,
      }"
    >
      <div
        class="w-11 h-11 rounded-lg bg-gradient-to-br from-indigo-900/60 to-purple-900/60 border border-indigo-500/30 shadow-md flex items-center justify-center text-base animate-pulse-slow text-indigo-300"
      >
        💎
      </div>
    </div>

    <!-- 5. FAST LAYER: Small Pixel Stars & Sparkle Particles (0.55x speed) -->
    <div
      class="absolute top-[200px] left-[25%] will-change-transform text-amber-400/80 text-sm animate-ping-slow"
      :style="{ transform: `translate3d(0, ${scrollY * 0.55}px, 0)` }"
    >
      ✦
    </div>
    <div
      class="absolute top-[520px] right-[30%] will-change-transform text-amber-400 text-base animate-pulse"
      :style="{ transform: `translate3d(0, ${scrollY * 0.48}px, 0)` }"
    >
      ✨
    </div>
    <div
      class="absolute top-[800px] left-[18%] will-change-transform text-indigo-400/80 text-xs animate-ping-slow"
      :style="{ transform: `translate3d(0, ${scrollY * 0.52}px, 0)` }"
    >
      ✦
    </div>
    <div
      class="absolute top-[1200px] right-[22%] will-change-transform text-amber-400/90 text-sm animate-pulse-slow"
      :style="{ transform: `translate3d(0, ${scrollY * 0.58}px, 0)` }"
    >
      ★
    </div>
    <div
      class="absolute top-[1600px] left-[32%] will-change-transform text-indigo-400/80 text-xs animate-ping-slow"
      :style="{ transform: `translate3d(0, ${scrollY * 0.45}px, 0)` }"
    >
      ✦
    </div>
  </div>
</template>

<style scoped>
/* Modeler Cutting Mat Blueprint Grid */
.bg-grid-pattern {
  background-size: 36px 36px;
  background-image:
    linear-gradient(to right, rgba(99, 102, 241, 0.08) 1px, transparent 1px),
    linear-gradient(to bottom, rgba(99, 102, 241, 0.08) 1px, transparent 1px);
}

/* Smooth Floating Animations */
@keyframes float {
  0%,
  100% {
    transform: translateY(0px) rotate(0deg);
  }
  50% {
    transform: translateY(-10px) rotate(3deg);
  }
}

@keyframes floatSlow {
  0%,
  100% {
    transform: translateY(0px) rotate(0deg);
  }
  50% {
    transform: translateY(-16px) rotate(-4deg);
  }
}

@keyframes floatReverse {
  0%,
  100% {
    transform: translateY(0px) rotate(0deg);
  }
  50% {
    transform: translateY(12px) rotate(4deg);
  }
}

@keyframes spinVerySlow {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

.animate-float {
  animation: float 4s ease-in-out infinite;
}

.animate-float-slow {
  animation: floatSlow 6s ease-in-out infinite;
}

.animate-float-reverse {
  animation: floatReverse 5s ease-in-out infinite;
}

.animate-spin-veryslow {
  animation: spinVerySlow 24s linear infinite;
}

.animate-pulse-slow {
  animation: pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.animate-ping-slow {
  animation: ping 3s cubic-bezier(0, 0, 0.2, 1) infinite;
}

/* Accessibility: Disable motion for users who prefer reduced motion */
@media (prefers-reduced-motion: reduce) {
  * {
    animation: none !important;
    transition: none !important;
    transform: none !important;
  }
}
</style>
