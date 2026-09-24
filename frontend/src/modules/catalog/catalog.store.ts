import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { ToyProduct, ToyCategory, TcgSubCategory, AgeGroup } from '@/shared/types/toy.types'
import { MOCK_TOYS_DATA } from '@/shared/constants/mock-toys.data'

export const useCatalogStore = defineStore('catalogStore', () => {
  // State
  const toys = ref<ToyProduct[]>(MOCK_TOYS_DATA)
  const searchQuery = ref('')
  const selectedCategory = ref<ToyCategory | 'all'>('all')
  const selectedTcgSeries = ref<TcgSubCategory | 'all'>('all')
  const selectedAgeGroup = ref<AgeGroup | 'all'>('all')
  const maxPriceFilter = ref<number>(100)
  const minRatingFilter = ref<number>(0)
  const sortBy = ref<'featured' | 'price-asc' | 'price-desc' | 'rating' | 'newest'>('featured')
  const onlyInStock = ref(false)
  const onlyDiscounted = ref(false)

  // Quick view / detail modal state
  const selectedToyForModal = ref<ToyProduct | null>(null)
  const isDetailModalOpen = ref(false)

  // Actions
  const openDetailModal = (toy: ToyProduct) => {
    selectedToyForModal.value = toy
    isDetailModalOpen.value = true
  }

  const closeDetailModal = () => {
    isDetailModalOpen.value = false
    selectedToyForModal.value = null
  }

  const setCategory = (cat: ToyCategory | 'all') => {
    selectedCategory.value = cat
    if (cat !== 'tcg') {
      selectedTcgSeries.value = 'all'
    }
  }

  const setTcgSeries = (series: TcgSubCategory | 'all') => {
    selectedTcgSeries.value = series
    if (series !== 'all') {
      selectedCategory.value = 'tcg'
    }
  }

  const setAgeGroup = (age: AgeGroup | 'all') => {
    selectedAgeGroup.value = age
  }

  const setSearchQuery = (q: string) => {
    searchQuery.value = q
  }

  const resetFilters = () => {
    searchQuery.value = ''
    selectedCategory.value = 'all'
    selectedTcgSeries.value = 'all'
    selectedAgeGroup.value = 'all'
    maxPriceFilter.value = 100
    minRatingFilter.value = 0
    sortBy.value = 'featured'
    onlyInStock.value = false
    onlyDiscounted.value = false
  }

  return {
    toys,
    searchQuery,
    selectedCategory,
    selectedTcgSeries,
    selectedAgeGroup,
    maxPriceFilter,
    minRatingFilter,
    sortBy,
    onlyInStock,
    onlyDiscounted,
    selectedToyForModal,
    isDetailModalOpen,

    openDetailModal,
    closeDetailModal,
    setCategory,
    setTcgSeries,
    setAgeGroup,
    setSearchQuery,
    resetFilters,
  }
})
