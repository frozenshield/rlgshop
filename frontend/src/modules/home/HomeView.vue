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
                Top Trending &amp; Best Sellers
              </h2>
            </div>
            <p class="text-xs text-slate-500 font-medium">Most caught Pokémon &amp; anime collectibles by trainers this week</p>
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
                TCG (Trading Cards) Spotlight
              </h2>
            </div>
            <p class="text-xs text-slate-500 font-medium">Factory-sealed Pokémon and One Piece booster boxes, Elite Trainer Boxes &amp; Holos</p>
          </div>

          <button
            type="button"
            class="text-xs font-bold text-red-600 hover:text-red-700 flex items-center gap-1 cursor-pointer transition-colors"
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

      <!-- 6. Pokémon & Anime Fan Reviews Section -->
      <section class="bg-amber-100/40 rounded-3xl p-8 sm:p-12 border border-amber-200/80 space-y-6 text-center">
        <div class="max-w-xl mx-auto space-y-1">
          <span class="text-xs font-extrabold uppercase tracking-widest text-amber-900 bg-amber-200/80 px-3 py-1 rounded-full">
            Gym Leader Approved
          </span>
          <h3 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">
            Loved by Over 15,000+ Collectors &amp; Trainers
          </h3>
          <p class="text-xs text-slate-500">Read what collectors, parents, and trainers say about RLG Online Shop</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-left">
          <div class="bg-white rounded-2xl p-5 border border-red-100 shadow-sm space-y-3">
            <div class="flex text-amber-400 text-sm">⭐⭐⭐⭐⭐</div>
            <p class="text-xs text-slate-600 leading-relaxed font-medium">
              "The Scarlet &amp; Violet 151 Elite Trainer Box arrived in factory-sealed mint condition. Pulled the special illustration rare Snorlax! Best TCG source online."
            </p>
            <div class="flex items-center gap-2.5 pt-1">
              <span class="w-8 h-8 rounded-full bg-red-100 text-red-600 font-bold text-xs flex items-center justify-center">AK</span>
              <div>
                <h5 class="text-xs font-bold text-slate-800">Ash K.</h5>
                <p class="text-[10px] text-slate-400">Pallet Town Collector</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-5 border border-amber-100 shadow-sm space-y-3">
            <div class="flex text-amber-400 text-sm">⭐⭐⭐⭐⭐</div>
            <p class="text-xs text-slate-600 leading-relaxed font-medium">
              "Got the giant 24\" Snorlax plush and the Luffy Gear 5 figure for my room setup. Quality is 10/10 official licensed goods. Super satisfied with RLG Online Shop!"
            </p>
            <div class="flex items-center gap-2.5 pt-1">
              <span class="w-8 h-8 rounded-full bg-amber-100 text-amber-800 font-bold text-xs flex items-center justify-center">MW</span>
              <div>
                <h5 class="text-xs font-bold text-slate-800">Misty W.</h5>
                <p class="text-[10px] text-slate-400">Cerulean Anime Enthusiast</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-5 border border-emerald-100 shadow-sm space-y-3">
            <div class="flex text-amber-400 text-sm">⭐⭐⭐⭐⭐</div>
            <p class="text-xs text-slate-600 leading-relaxed font-medium">
              "The Gift Advisor recommended the Mega Charizard battle statue for my nephew. He finished building it in one weekend and loves the mechanized wing-flapping crank!"
            </p>
            <div class="flex items-center gap-2.5 pt-1">
              <span class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-800 font-bold text-xs flex items-center justify-center">BS</span>
              <div>
                <h5 class="text-xs font-bold text-slate-800">Brock S.</h5>
                <p class="text-[10px] text-slate-400">Pewter Gym Leader</p>
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
