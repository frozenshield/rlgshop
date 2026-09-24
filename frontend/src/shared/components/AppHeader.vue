<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '@/modules/cart/cart.store'
import { useWishlistStore } from '@/modules/wishlist/wishlist.store'
import { useCatalogStore } from '@/modules/catalog/catalog.store'
import { TCG_SERIES_DATA } from '@/shared/constants/categories.data'
import { formatCurrency } from '../utils/currency.util'
import BaseBadge from './BaseBadge.vue'
import type { TcgSubCategory } from '../types/toy.types'

const emit = defineEmits<{
  (e: 'open-advisor'): void
}>()

const router = useRouter()
const cartStore = useCartStore()
const wishlistStore = useWishlistStore()
const catalogStore = useCatalogStore()

const localSearchText = ref('')
const isSearchFocused = ref(false)
const isMobileMenuOpen = ref(false)
const isTcgDropdownOpen = ref(false)

const searchPreviewResults = computed(() => {
  if (!localSearchText.value.trim()) return []
  const query = localSearchText.value.toLowerCase().trim()
  return catalogStore.toys
    .filter((toy) =>
      toy.name.toLowerCase().includes(query) ||
      toy.category.includes(query) ||
      (toy.tcgSeries && toy.tcgSeries.toLowerCase().includes(query)) ||
      (toy.pokemonType && toy.pokemonType.toLowerCase().includes(query)),
    )
    .slice(0, 4)
})

const handleSearchSubmit = () => {
  if (!localSearchText.value.trim()) return
  catalogStore.setSearchQuery(localSearchText.value)
  isSearchFocused.value = false
  router.push('/catalog')
}

const handleSelectPreview = (toyId: string) => {
  const toy = catalogStore.toys.find((t) => t.id === toyId)
  if (toy) {
    catalogStore.openDetailModal(toy)
  }
  isSearchFocused.value = false
  localSearchText.value = ''
}

const navigateToCategory = (cat: 'tcg' | 'anime-figures' | 'anime-merchandise' | 'all') => {
  catalogStore.setCategory(cat)
  isMobileMenuOpen.value = false
  isTcgDropdownOpen.value = false
  router.push('/catalog')
}

const navigateToTcgSeries = (seriesId: TcgSubCategory) => {
  catalogStore.setTcgSeries(seriesId)
  isMobileMenuOpen.value = false
  isTcgDropdownOpen.value = false
  router.push('/catalog')
}
</script>

<template>
  <!-- Top Flash Announcement Bar -->
  <div class="bg-gradient-to-r from-red-600 via-amber-400 to-red-600 text-white text-xs font-bold py-1.5 px-4 text-center tracking-wide flex items-center justify-center gap-2 shadow-sm">
    <span class="text-amber-200">⚡</span>
    <span>WELCOME TO RLG ONLINE SHOP! Use Trainer code <span class="bg-white/20 px-2 py-0.5 rounded font-mono font-extrabold text-amber-100">PIKACHU10</span> for 10% Off Orders Over $50!</span>
    <span class="text-amber-200">⚡</span>
  </div>

  <!-- Main Navbar -->
  <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-red-100 shadow-sm transition-all font-display">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-20 gap-4">
        <!-- Logo -->
        <router-link to="/" class="flex items-center gap-3 group select-none flex-shrink-0">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-red-600 via-red-500 to-amber-400 flex items-center justify-center shadow-md shadow-red-200 group-hover:scale-105 transition-transform duration-200 p-2">
            <!-- Pokeball Icon -->
            <svg viewBox="0 0 100 100" class="w-full h-full drop-shadow-xs group-hover:rotate-45 transition-transform duration-300">
              <circle cx="50" cy="50" r="48" fill="#1E293B" />
              <path d="M 4 50 A 46 46 0 0 1 96 50 Z" fill="#EE1515" />
              <path d="M 4 50 A 46 46 0 0 0 96 50 Z" fill="#FFFFFF" />
              <line x1="4" y1="50" x2="96" y2="50" stroke="#1E293B" stroke-width="8" />
              <circle cx="50" cy="50" r="15" fill="#1E293B" />
              <circle cx="50" cy="50" r="9" fill="#FFFFFF" stroke="#94A3B8" stroke-width="1.5" />
              <circle cx="50" cy="50" r="4" fill="#E2E8F0" />
            </svg>
          </div>
          <div>
            <div class="flex items-center gap-1.5">
              <span class="text-2xl font-extrabold tracking-tight bg-gradient-to-r from-red-600 via-red-500 to-amber-500 bg-clip-text text-transparent">
                RLG Online Shop
              </span>
              <BaseBadge variant="warning" size="sm">Pokémon &amp; Anime</BaseBadge>
            </div>
            <p class="text-[11px] text-slate-400 font-semibold tracking-wider uppercase">TCG • Figures • Merchandise</p>
          </div>
        </router-link>

        <!-- Desktop Navigation Links -->
        <nav class="hidden md:flex items-center gap-1 lg:gap-2 text-sm font-bold text-slate-700">
          <router-link
            to="/"
            class="px-3 py-2 rounded-xl hover:text-red-600 hover:bg-red-50 transition-colors"
            active-class="text-red-600 bg-red-50/80"
          >
            Home
          </router-link>

          <router-link
            to="/catalog"
            class="px-3 py-2 rounded-xl hover:text-red-600 hover:bg-red-50 transition-colors"
            active-class="text-red-600 bg-red-50/80"
            @click="catalogStore.setCategory('all')"
          >
            All Vault
          </router-link>

          <!-- TCG Dropdown Menu (Pokemon, One Piece, Hololive, Duel Masters, Weiss Schwarz) -->
          <div
            class="relative"
            @mouseenter="isTcgDropdownOpen = true"
            @mouseleave="isTcgDropdownOpen = false"
          >
            <button
              type="button"
              class="px-3 py-2 rounded-xl hover:text-blue-600 hover:bg-blue-50 transition-colors flex items-center gap-1.5 cursor-pointer"
              :class="catalogStore.selectedCategory === 'tcg' ? 'text-blue-600 bg-blue-50' : ''"
              @click="navigateToCategory('tcg')"
            >
              <span>🃏 TCG Cards</span>
              <svg class="w-3.5 h-3.5 transition-transform" :class="isTcgDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            <!-- TCG Dropdown Popover -->
            <transition
              enter-active-class="transition duration-150 ease-out"
              enter-from-class="opacity-0 translate-y-1"
              enter-to-class="opacity-100 translate-y-0"
              leave-active-class="transition duration-100 ease-in"
              leave-from-class="opacity-100 translate-y-0"
              leave-to-class="opacity-0 translate-y-1"
            >
              <div
                v-if="isTcgDropdownOpen"
                class="absolute top-full left-0 w-64 bg-white rounded-2xl shadow-xl border border-blue-100 p-2 space-y-1 z-50 font-display"
              >
                <div class="px-3 py-1.5 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 border-b border-slate-100">
                  Select TCG Franchise:
                </div>
                <button
                  type="button"
                  class="w-full text-left px-3 py-2 rounded-xl hover:bg-blue-50 flex items-center justify-between text-xs font-bold text-slate-800 transition-colors cursor-pointer"
                  @click="navigateToCategory('tcg')"
                >
                  <span class="flex items-center gap-2">
                    <span>🃏</span>
                    <span>All TCG Games</span>
                  </span>
                  <span class="text-[10px] text-blue-600 font-bold">&rarr;</span>
                </button>
                <button
                  v-for="series in TCG_SERIES_DATA"
                  :key="series.id"
                  type="button"
                  class="w-full text-left px-3 py-2 rounded-xl hover:bg-blue-50 flex items-center justify-between text-xs font-bold text-slate-800 transition-colors cursor-pointer"
                  @click="navigateToTcgSeries(series.id)"
                >
                  <span class="flex items-center gap-2">
                    <span>{{ series.icon }}</span>
                    <span>{{ series.name }}</span>
                  </span>
                  <span class="text-[10px] text-slate-400 group-hover:text-blue-600">Browse</span>
                </button>
              </div>
            </transition>
          </div>

          <!-- Figures Button -->
          <button
            type="button"
            class="px-3 py-2 rounded-xl hover:text-red-600 hover:bg-red-50 transition-colors flex items-center gap-1 cursor-pointer"
            :class="catalogStore.selectedCategory === 'anime-figures' ? 'text-red-600 bg-red-50' : ''"
            @click="navigateToCategory('anime-figures')"
          >
            <span>⚡ Figures</span>
          </button>

          <!-- Merchandise Button -->
          <button
            type="button"
            class="px-3 py-2 rounded-xl hover:text-amber-700 hover:bg-amber-50 transition-colors flex items-center gap-1 cursor-pointer"
            :class="catalogStore.selectedCategory === 'anime-merchandise' ? 'text-amber-700 bg-amber-50' : ''"
            @click="navigateToCategory('anime-merchandise')"
          >
            <span>🎁 Merch</span>
          </button>

          <!-- Smart Poké Partner Finder Trigger Button -->
          <button
            type="button"
            class="px-3 py-2 rounded-xl text-amber-900 bg-amber-100 hover:bg-amber-200 transition-all flex items-center gap-1.5 border border-amber-300 shadow-xs cursor-pointer active:scale-95"
            @click="emit('open-advisor')"
          >
            <span>⚡</span>
            <span class="font-extrabold">Poké-Match Quiz</span>
          </button>
        </nav>

        <!-- Search Bar with Live Popover -->
        <div class="relative hidden sm:block flex-1 max-w-xs lg:max-w-sm">
          <div class="relative">
            <label for="desktop-search" class="sr-only">Search Products</label>
            <input
              id="desktop-search"
              v-model="localSearchText"
              type="text"
              aria-label="Search Pokémon, One Piece, TCG products"
              placeholder="Search Pokémon, One Piece, TCG..."
              class="w-full bg-slate-100/90 focus:bg-white text-sm text-slate-800 rounded-full pl-10 pr-4 py-2.5 border border-transparent focus:border-red-400 focus:outline-none focus:ring-2 focus:ring-red-200 transition-all"
              @focus="isSearchFocused = true"
              @keyup.enter="handleSearchSubmit"
            />
            <svg
              class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2.5"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
              />
            </svg>
          </div>

          <!-- Live Search Autocomplete Popover -->
          <div
            v-if="isSearchFocused && searchPreviewResults.length > 0"
            class="absolute top-12 left-0 right-0 bg-white rounded-2xl shadow-xl border border-red-100 p-2 space-y-1 z-50 animate-scale-up font-display"
          >
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 px-3 py-1">
              Matching Vault Items:
            </div>
            <button
              v-for="item in searchPreviewResults"
              :key="item.id"
              type="button"
              class="w-full text-left flex items-center gap-3 p-2 rounded-xl hover:bg-red-50/60 cursor-pointer transition-colors"
              @mousedown.prevent="handleSelectPreview(item.id)"
            >
              <img
                :src="item.imageUrl"
                :alt="item.name"
                class="w-10 h-10 rounded-lg object-cover bg-amber-50"
              />
              <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-slate-800 truncate">{{ item.name }}</p>
                <p class="text-[11px] text-red-600 font-extrabold">{{ formatCurrency(item.price) }}</p>
              </div>
            </button>
          </div>
        </div>

        <!-- Right: Actions (Wishlist & Cart) -->
        <div class="flex items-center gap-2 sm:gap-3">
          <!-- Wishlist Button -->
          <router-link
            to="/wishlist"
            class="relative p-2.5 rounded-2xl hover:bg-red-50 text-slate-600 hover:text-red-600 transition-colors"
            title="Saved Wishlist"
            aria-label="Trainer Wishlist"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
              />
            </svg>
            <span
              v-if="wishlistStore.count > 0"
              class="absolute -top-1 -right-1 bg-red-600 text-white font-bold text-[11px] w-5 h-5 rounded-full flex items-center justify-center shadow-sm animate-scale-up"
            >
              {{ wishlistStore.count }}
            </span>
          </router-link>

          <!-- Cart Trigger Button -->
          <button
            type="button"
            class="relative flex items-center gap-2 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white px-3.5 sm:px-4 py-2.5 rounded-2xl shadow-md shadow-red-200 transition-all active:scale-95 cursor-pointer"
            aria-label="View Poké-Bag Cart"
            @click="cartStore.openDrawer"
          >
            <div class="relative">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                />
              </svg>
              <span
                v-if="cartStore.totalItemCount > 0"
                class="absolute -top-2.5 -right-2.5 bg-amber-400 text-slate-900 font-extrabold text-[10px] w-4.5 h-4.5 rounded-full flex items-center justify-center border-2 border-red-600"
              >
                {{ cartStore.totalItemCount }}
              </span>
            </div>
            <span class="hidden sm:inline text-xs font-bold font-mono">
              {{ formatCurrency(cartStore.subtotal) }}
            </span>
          </button>

          <!-- Mobile Hamburger Toggle -->
          <button
            type="button"
            class="md:hidden p-2 rounded-2xl hover:bg-slate-100 text-slate-600"
            aria-label="Toggle navigation menu"
            @click="isMobileMenuOpen = !isMobileMenuOpen"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                v-if="!isMobileMenuOpen"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16"
              />
              <path
                v-else
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>
      </div>

      <!-- Mobile Dropdown Navigation -->
      <div v-if="isMobileMenuOpen" class="md:hidden py-4 border-t border-red-100 space-y-2">
        <div class="px-2 pb-2">
          <label for="mobile-search" class="sr-only">Mobile Search Products</label>
          <input
            id="mobile-search"
            v-model="localSearchText"
            type="text"
            aria-label="Search Pokémon, One Piece, TCG products"
            placeholder="Search Pokémon, One Piece, TCG..."
            class="w-full bg-slate-100 text-sm rounded-xl px-4 py-2 border border-slate-200"
            @keyup.enter="handleSearchSubmit"
          />
        </div>
        <router-link
          to="/"
          class="block px-4 py-2 rounded-xl text-sm font-bold text-slate-700 hover:bg-red-50"
          @click="isMobileMenuOpen = false"
        >
          Home
        </router-link>
        <router-link
          to="/catalog"
          class="block px-4 py-2 rounded-xl text-sm font-bold text-slate-700 hover:bg-red-50"
          @click="isMobileMenuOpen = false"
        >
          All Vault Products
        </router-link>

        <!-- TCG Subcategories in Mobile -->
        <div class="bg-blue-50/70 p-3 rounded-2xl border border-blue-200/80 space-y-1.5 mx-2">
          <div class="text-[11px] font-extrabold uppercase text-blue-900 tracking-wider">
            🃏 TCG Franchises:
          </div>
          <button
            type="button"
            class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs font-bold text-blue-950 hover:bg-blue-100 flex items-center justify-between"
            @click="navigateToCategory('tcg')"
          >
            <span>All TCG Games</span>
            <span>&rarr;</span>
          </button>
          <button
            v-for="series in TCG_SERIES_DATA"
            :key="series.id"
            type="button"
            class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs font-bold text-slate-700 hover:bg-blue-100 flex items-center justify-between"
            @click="navigateToTcgSeries(series.id)"
          >
            <span>{{ series.icon }} {{ series.name }}</span>
            <span class="text-[10px] text-slate-400">View</span>
          </button>
        </div>

        <button
          type="button"
          class="w-full text-left px-4 py-2 rounded-xl text-sm font-bold text-slate-700 hover:bg-red-50 cursor-pointer"
          @click="navigateToCategory('anime-figures')"
        >
          ⚡ Anime Figures
        </button>
        <button
          type="button"
          class="w-full text-left px-4 py-2 rounded-xl text-sm font-bold text-slate-700 hover:bg-red-50 cursor-pointer"
          @click="navigateToCategory('anime-merchandise')"
        >
          🎁 Anime Merchandise
        </button>

        <router-link
          to="/wishlist"
          class="block px-4 py-2 rounded-xl text-sm font-bold text-slate-700 hover:bg-red-50"
          @click="isMobileMenuOpen = false"
        >
          Trainer Wishlist ({{ wishlistStore.count }})
        </router-link>
        <button
          type="button"
          class="w-full text-left px-4 py-2 rounded-xl text-sm font-bold text-amber-900 bg-amber-100 cursor-pointer"
          @click="() => { isMobileMenuOpen = false; emit('open-advisor') }"
        >
          ⚡ Poké-Match Quiz
        </button>
      </div>
    </div>
  </header>
</template>
