<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useCatalogStore } from '../catalog/catalog.store'
import HeroBanner from './components/HeroBanner.vue'
import CategoryPills from './components/CategoryPills.vue'
import PromotionalBanner from './components/PromotionalBanner.vue'
import ToyCard from '../catalog/components/ToyCard.vue'
import ToyDetailModal from '../catalog/components/ToyDetailModal.vue'
import BaseButton from '@/shared/components/BaseButton.vue'
import type { ToyProduct } from '@/shared/types/toy.types'

const emit = defineEmits<{
  (e: 'open-advisor'): void
}>()

const router = useRouter()
const catalogStore = useCatalogStore()

const featuredToys = computed<ToyProduct[]>(() => {
  return catalogStore.toys.filter((t) => t.isFeatured || t.isBestSeller).slice(0, 4)
})

const tcgToys = computed<ToyProduct[]>(() => {
  return catalogStore.toys.filter((t) => t.category === 'tcg').slice(0, 4)
})

const handleQuickView = (toy: ToyProduct) => {
  catalogStore.openDetailModal(toy)
}
</script>

<template>
  <div class="min-h-screen py-6 sm:py-8 space-y-12 sm:space-y-16 font-display">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12 sm:space-y-16">
      <!-- 1. Hero Banner -->
      <HeroBanner @open-advisor="emit('open-advisor')" />

      <!-- 2. Category Explorer Pills (TCG, Anime Figures, Anime Merch) -->
      <CategoryPills />

      <!-- 3. Featured & Trending Toys Section -->
      <section class="space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-xl">🔥</span>
              <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                Hobby Favorites &amp; Best Sellers
              </h2>
            </div>
            <p class="text-xs text-slate-500 font-medium">Top-rated sealed booster boxes, Anime merchandise, and collectible figures this week</p>
          </div>

          <BaseButton
            variant="ghost"
            size="sm"
            @click="router.push('/catalog')"
          >
            <span>See All Best Sellers</span>
            <span>&rarr;</span>
          </BaseButton>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <ToyCard
            v-for="toy in featuredToys"
            :key="toy.id"
            :toy="toy"
            @quick-view="handleQuickView"
          />
        </div>
      </section>

      <!-- 4. Flash Sale Promo Banner -->
      <PromotionalBanner />

      <!-- 5. TCG & Collector Cards Spotlight -->
      <section class="space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-xl">🃏</span>
              <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                Trading Card Games (TCG) Spotlight
              </h2>
            </div>
            <p class="text-xs text-slate-500 font-medium">Factory-sealed Pokémon, One Piece, Yu-Gi-Oh! and Weiß Schwarz booster boxes</p>
          </div>

          <button
            type="button"
            class="text-xs font-bold text-rose-600 hover:text-rose-700 flex items-center gap-1 cursor-pointer transition-colors"
            @click="() => { catalogStore.setCategory('tcg'); router.push('/catalog') }"
          >
            <span>Explore All TCG</span>
            <span>&rarr;</span>
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <ToyCard
            v-for="toy in tcgToys"
            :key="toy.id"
            :toy="toy"
            @quick-view="handleQuickView"
          />
        </div>
      </section>

      <!-- 6. Verified Hobbyist Reviews Section -->
      <section class="bg-white rounded-3xl p-8 sm:p-12 border border-slate-200/80 shadow-xs space-y-6 text-center">
        <div class="max-w-xl mx-auto space-y-1">
          <span class="text-xs font-bold uppercase tracking-wider text-rose-600 bg-rose-50 px-3 py-1 rounded-full border border-rose-100">
            Verified Hobbyist Reviews
          </span>
          <h3 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">
            Trusted by 10,000+ Collectors &amp; Builders
          </h3>
          <p class="text-xs text-slate-500">Read authentic reviews from card players, model kit builders, and anime enthusiasts</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-left">
          <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200/80 shadow-2xs space-y-3">
            <div class="flex text-amber-400 text-sm">⭐⭐⭐⭐⭐</div>
            <p class="text-xs text-slate-600 leading-relaxed font-medium">
              "The Scarlet &amp; Violet 151 Elite Trainer Box and OP-05 booster box arrived in factory-sealed mint condition. The double-boxed packaging protected the corners perfectly!"
            </p>
            <div class="flex items-center gap-2.5 pt-1">
              <span class="w-8 h-8 rounded-full bg-rose-100 text-rose-700 font-bold text-xs flex items-center justify-center">MT</span>
              <div>
                <h5 class="text-xs font-bold text-slate-800">Marcus T.</h5>
                <p class="text-[10px] text-slate-400">TCG Sealed Collector</p>
              </div>
            </div>
          </div>

          <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200/80 shadow-2xs space-y-3">
            <div class="flex text-amber-400 text-sm">⭐⭐⭐⭐⭐</div>
            <p class="text-xs text-slate-600 leading-relaxed font-medium">
              "Got the Luffy Gear 5 figure and Gunpla kits. 100% authentic licensed goods with pristine runners and flawless paint applications. Best hobby shop online!"
            </p>
            <div class="flex items-center gap-2.5 pt-1">
              <span class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-bold text-xs flex items-center justify-center">ER</span>
              <div>
                <h5 class="text-xs font-bold text-slate-800">Elena R.</h5>
                <p class="text-[10px] text-slate-400">Gunpla &amp; Figure Hobbyist</p>
              </div>
            </div>
          </div>

          <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200/80 shadow-2xs space-y-3">
            <div class="flex text-amber-400 text-sm">⭐⭐⭐⭐⭐</div>
            <p class="text-xs text-slate-600 leading-relaxed font-medium">
              "The Hobby Matcher quiz recommended the ideal deck sleeves and Charizard collection box for a tournament gift. Fast delivery and stellar customer service!"
            </p>
            <div class="flex items-center gap-2.5 pt-1">
              <span class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 font-bold text-xs flex items-center justify-center">DC</span>
              <div>
                <h5 class="text-xs font-bold text-slate-800">David C.</h5>
                <p class="text-[10px] text-slate-400">Competitive Card Player</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Quick View Modal -->
      <ToyDetailModal
        :toy="catalogStore.selectedToyForModal"
        :is-open="catalogStore.isDetailModalOpen"
        @close="catalogStore.closeDetailModal"
      />
    </div>
  </div>
</template>
