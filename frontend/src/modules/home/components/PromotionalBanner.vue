<script setup lang="ts">
import { computed } from "vue";
import { useRouter } from "vue-router";
import { useCmsStore } from "@/modules/cms/cms.store";

const router = useRouter();
const cmsStore = useCmsStore();

const promo = computed(() => {
  return (
    cmsStore.promoBanner || {
      id: "bnr-2",
      title: "Level Up Your Collection: 10% - 20% Off Drops!",
      subtitle:
        "Apply collector code HOBBY10 or GUNPLA20 at checkout on all orders!",
      badgeText: "Collector Welcome Coupon ⚡",
      ctaText: "Shop Deals Now",
      ctaLink: "/catalog",
      imageUrl:
        "https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=700&auto=format&fit=crop&q=80",
      isActive: true,
    }
  );
});
</script>

<template>
  <div
    v-if="promo && promo.isActive"
    class="rounded-3xl bg-gradient-to-r from-slate-950 via-indigo-950/70 to-slate-900 text-white p-6 sm:p-10 shadow-2xl border border-indigo-500/30 relative overflow-hidden font-display transition-all duration-300"
  >
    <div
      class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6 text-center md:text-left"
    >
      <div class="space-y-2 max-w-xl">
        <span
          v-if="promo.badgeText"
          class="bg-indigo-600 text-white text-xs font-black px-3 py-1 rounded-full uppercase tracking-wider shadow-sm inline-block"
        >
          {{ promo.badgeText }}
        </span>
        <h3 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
          {{ promo.title }}
        </h3>
        <p class="text-xs sm:text-sm text-slate-300 font-normal leading-relaxed whitespace-pre-line">
          {{ promo.subtitle }}
        </p>
      </div>

      <button
        type="button"
        class="whitespace-nowrap flex-shrink-0 font-black px-6 py-3.5 rounded-2xl bg-amber-400 hover:bg-amber-300 text-slate-950 shadow-lg shadow-amber-400/25 border border-amber-300 transition-all active:scale-95 cursor-pointer text-sm flex items-center gap-2"
        @click="router.push(promo.ctaLink || '/catalog')"
      >
        <span>{{ promo.ctaText || 'Shop Deals Now' }}</span>
        <span>&rarr;</span>
      </button>
    </div>
  </div>
</template>
