<script setup lang="ts">
import { useToyFinderComposable } from './toy-finder.composable'
import { useCartStore } from '../cart/cart.store'
import type { AgeGroup } from '@/shared/types/toy.types'
import { TCG_SERIES_DATA } from '@/shared/constants/categories.data'
import { formatCurrency, formatAgeGroup } from '@/shared/utils/currency.util'
import BaseButton from '@/shared/components/BaseButton.vue'

interface Props {
  isOpen: boolean
}

defineProps<Props>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

const {
  currentStep,
  selectedAge,
  selectedCategory,
  selectedBudget,
  recommendedToys,
  selectAge,
  selectInterest,
  submitBudget,
  resetFinder,
} = useToyFinderComposable()

const cartStore = useCartStore()

const ageOptions: { id: AgeGroup; label: string; icon: string; desc: string }[] = [
  { id: '0-2', label: 'Baby & Toddler', icon: '🍼', desc: '0 - 2 Years (Soft & Cuddle Plush)' },
  { id: '3-5', label: 'Rookie Explorer', icon: '⚡', desc: '3 - 5 Years (Starter Figures & Gear)' },
  { id: '6-8', label: 'Junior Collector', icon: '🎒', desc: '6 - 8 Years (Action Battles & TCG Starter)' },
  { id: '9-12', label: 'Ace Collector', icon: '🃏', desc: '9 - 12 Years (Elite Trainer Boxes & Scale Statues)' },
  { id: '12+', label: 'Master Hobbyist', icon: '🏆', desc: '12+ Years (Rare Booster Boxes & High-End Scales)' },
]

const tcgFranchises = TCG_SERIES_DATA

const handleAddRecommended = (toyId: string) => {
  const toy = recommendedToys.value.find((t) => t.id === toyId)
  if (toy) {
    cartStore.addItem(toy, 1)
  }
}
</script>

<template>
  <teleport to="body">
    <transition
      enter-active-class="transition-opacity duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 overflow-y-auto"
        @click="emit('close')"
      >
        <div
          class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 shadow-2xl relative border border-slate-200 font-display space-y-6 max-h-[90vh] overflow-y-auto"
          @click.stop
        >
          <!-- Modal Header -->
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
              <span class="text-2xl">🎯</span>
              <div>
                <h3 class="text-lg font-extrabold text-slate-900">RLG Collector &amp; Gift Matcher</h3>
                <p class="text-[11px] text-slate-500 font-semibold">Step {{ currentStep }} of 3 - Discover the perfect hobby &amp; TCG collectible</p>
              </div>
            </div>
            <button
              type="button"
              class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center cursor-pointer transition-colors"
              @click="emit('close')"
            >
              ✕
            </button>
          </div>

          <!-- Step 1: Age Selection -->
          <div v-if="currentStep === 1" class="space-y-4">
            <div class="text-center space-y-1">
              <h4 class="text-base font-extrabold text-slate-900">Who is this gift or collectible for?</h4>
              <p class="text-xs text-slate-500">Select experience level or age group to match appropriate items.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
              <button
                v-for="opt in ageOptions"
                :key="opt.id"
                type="button"
                class="p-4 rounded-2xl border-2 border-slate-200 hover:border-slate-900 hover:bg-slate-50/70 transition-all text-left flex items-center gap-3.5 group cursor-pointer"
                @click="selectAge(opt.id)"
              >
                <span class="text-3xl group-hover:scale-110 transition-transform">{{ opt.icon }}</span>
                <div>
                  <h5 class="text-xs font-extrabold text-slate-800 group-hover:text-slate-900 transition-colors">{{ opt.label }}</h5>
                  <p class="text-[11px] text-slate-400 font-semibold">{{ opt.desc }}</p>
                </div>
              </button>
            </div>
          </div>

          <!-- Step 2: Interest Selection -->
          <div v-else-if="currentStep === 2" class="space-y-4">
            <div class="text-center space-y-1">
              <h4 class="text-base font-extrabold text-slate-900">Which franchise or category are you looking for?</h4>
              <p class="text-xs text-slate-500">Pick a specific TCG franchise or anime collectible type.</p>
            </div>

            <!-- TCG Franchises -->
            <div class="space-y-2 pt-1">
              <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">🃏 Trading Card Games (TCG)</span>
                <button
                  type="button"
                  class="text-[11px] font-bold text-rose-600 hover:underline cursor-pointer"
                  @click="selectInterest('tcg')"
                >
                  All TCG &rarr;
                </button>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <button
                  v-for="series in tcgFranchises"
                  :key="series.id"
                  type="button"
                  class="p-3 rounded-2xl border-2 border-slate-200 hover:border-slate-900 hover:bg-slate-50/70 transition-all text-left flex items-center gap-3 group cursor-pointer"
                  @click="selectInterest('tcg', series.id)"
                >
                  <span class="text-2xl group-hover:scale-110 transition-transform">{{ series.icon }}</span>
                  <div class="min-w-0">
                    <h5 class="text-xs font-extrabold text-slate-800 group-hover:text-slate-900 transition-colors truncate">
                      {{ series.name }}
                    </h5>
                    <p class="text-[10px] text-slate-400 font-medium truncate">{{ series.description }}</p>
                  </div>
                </button>
              </div>
            </div>

            <!-- Anime Figures & Merchandise -->
            <div class="space-y-2 pt-2 border-t border-slate-100">
              <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">⚡ Figures &amp; Merchandise</span>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <button
                  type="button"
                  class="p-3 rounded-2xl border-2 border-slate-200 hover:border-slate-900 hover:bg-slate-50/70 transition-all text-left flex items-center gap-3 group cursor-pointer"
                  @click="selectInterest('anime-figures')"
                >
                  <span class="text-2xl group-hover:scale-110 transition-transform">⚡</span>
                  <div class="min-w-0">
                    <h5 class="text-xs font-extrabold text-slate-800 group-hover:text-slate-900 transition-colors truncate">Anime Figures</h5>
                    <p class="text-[10px] text-slate-400 font-medium truncate">Scale statues &amp; articulated figures</p>
                  </div>
                </button>
                <button
                  type="button"
                  class="p-3 rounded-2xl border-2 border-slate-200 hover:border-slate-900 hover:bg-slate-50/70 transition-all text-left flex items-center gap-3 group cursor-pointer"
                  @click="selectInterest('anime-merchandise')"
                >
                  <span class="text-2xl group-hover:scale-110 transition-transform">🎁</span>
                  <div class="min-w-0">
                    <h5 class="text-xs font-extrabold text-slate-800 group-hover:text-slate-900 transition-colors truncate">Anime Merchandise</h5>
                    <p class="text-[10px] text-slate-400 font-medium truncate">Plushies, model accessories &amp; pins</p>
                  </div>
                </button>
              </div>
            </div>
          </div>

          <!-- Step 3: Budget Selection -->
          <div v-else-if="currentStep === 3" class="space-y-5">
            <div class="text-center space-y-1">
              <h4 class="text-base font-extrabold text-slate-900">What is your target budget?</h4>
              <p class="text-xs text-slate-500">We will find the highest-rated collectibles within your price range.</p>
            </div>

            <div class="bg-slate-50 p-6 rounded-3xl border border-slate-200 text-center space-y-3">
              <div class="text-3xl font-extrabold text-slate-900 font-display">
                Up to {{ formatCurrency(selectedBudget) }}
              </div>
              <input
                v-model.number="selectedBudget"
                type="range"
                min="20"
                max="120"
                step="5"
                class="w-full accent-rose-600 cursor-pointer"
              />
              <div class="flex justify-between text-xs text-slate-400 font-bold">
                <span>{{ formatCurrency(20) }}</span>
                <span>{{ formatCurrency(60) }}</span>
                <span>{{ formatCurrency(120) }}</span>
              </div>
            </div>

            <BaseButton
              variant="primary"
              size="lg"
              fullWidth
              @click="submitBudget(selectedBudget)"
            >
              Reveal Matching Products 🔍
            </BaseButton>
          </div>

          <!-- Step 4: Results Display -->
          <div v-else-if="currentStep === 4" class="space-y-4">
            <div class="text-center space-y-1">
              <span class="text-2xl">✨</span>
              <h4 class="text-base font-extrabold text-slate-900">Your Recommended Matches!</h4>
              <p class="text-xs text-slate-500">
                Custom recommendations for Rank: {{ formatAgeGroup(selectedAge || 'all') }}
              </p>
            </div>

            <div class="space-y-3">
              <div
                v-for="toy in recommendedToys"
                :key="toy.id"
                class="p-3.5 rounded-2xl border border-slate-200 bg-slate-50/50 flex items-center justify-between gap-3"
              >
                <img :src="toy.imageUrl" :alt="toy.name" class="w-14 h-14 rounded-xl object-cover border border-slate-200" />
                <div class="flex-1 min-w-0">
                  <h5 class="text-xs font-bold text-slate-800 truncate">{{ toy.name }}</h5>
                  <p class="text-[11px] text-slate-400 font-semibold">{{ toy.brand }}</p>
                  <span class="text-xs font-extrabold text-slate-900">{{ formatCurrency(toy.price) }}</span>
                </div>
                <BaseButton variant="primary" size="sm" @click="handleAddRecommended(toy.id)">
                  Add to Cart
                </BaseButton>
              </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-between items-center">
              <button
                type="button"
                class="text-xs font-bold text-slate-500 hover:text-slate-900 cursor-pointer"
                @click="resetFinder"
              >
                &larr; Search Again
              </button>
              <BaseButton variant="secondary" size="sm" @click="emit('close')">
                Done
              </BaseButton>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>
