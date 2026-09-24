import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useCatalogStore } from './catalog.store'
import type { ToyProduct } from '@/shared/types/toy.types'

export const useCatalogFilterComposable = () => {
  const store = useCatalogStore()
  const {
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
  } = storeToRefs(store)

  // Filtered and Sorted Toy List
  const filteredToys = computed<ToyProduct[]>(() => {
    return toys.value
      .filter((toy) => {
        // Search query match
        if (searchQuery.value.trim()) {
          const query = searchQuery.value.toLowerCase().trim()
          const matchesName = toy.name.toLowerCase().includes(query)
          const matchesBrand = toy.brand.toLowerCase().includes(query)
          const matchesTag = toy.tags.some((tag) => tag.toLowerCase().includes(query))
          const matchesSeries = toy.tcgSeries && toy.tcgSeries.toLowerCase().includes(query)
          if (!matchesName && !matchesBrand && !matchesTag && !matchesSeries) return false
        }

        // Category filter
        if (selectedCategory.value !== 'all' && toy.category !== selectedCategory.value) {
          return false
        }

        // TCG Series filter (pokemon, one-piece, hololive, duel-masters, weiss-schwarz)
        if (selectedTcgSeries.value !== 'all' && toy.tcgSeries !== selectedTcgSeries.value) {
          return false
        }

        // Age group filter
        if (selectedAgeGroup.value !== 'all' && toy.ageGroup !== selectedAgeGroup.value) {
          return false
        }

        // Price range
        if (toy.price > maxPriceFilter.value) {
          return false
        }

        // Rating
        if (minRatingFilter.value > 0 && toy.rating < minRatingFilter.value) {
          return false
        }

        // In-stock only
        if (onlyInStock.value && toy.stock <= 0) {
          return false
        }

        // Discounted only
        if (onlyDiscounted.value && (!toy.discountPercent || toy.discountPercent <= 0)) {
          return false
        }

        return true
      })
      .sort((a, b) => {
        switch (sortBy.value) {
          case 'price-asc':
            return a.price - b.price
          case 'price-desc':
            return b.price - a.price
          case 'rating':
            return b.rating - a.rating
          case 'newest':
            return (b.isNewArrival ? 1 : 0) - (a.isNewArrival ? 1 : 0)
          case 'featured':
          default:
            return (b.isFeatured ? 1 : 0) - (a.isFeatured ? 1 : 0)
        }
      })
  })

  const hasActiveFilters = computed<boolean>(() => {
    return (
      searchQuery.value !== '' ||
      selectedCategory.value !== 'all' ||
      selectedTcgSeries.value !== 'all' ||
      selectedAgeGroup.value !== 'all' ||
      maxPriceFilter.value < 100 ||
      minRatingFilter.value > 0 ||
      onlyInStock.value ||
      onlyDiscounted.value
    )
  })

  const totalResults = computed(() => filteredToys.value.length)

  return {
    filteredToys,
    hasActiveFilters,
    totalResults,
    selectedToyForModal,
    isDetailModalOpen,

    openDetailModal: store.openDetailModal,
    closeDetailModal: store.closeDetailModal,
    setCategory: store.setCategory,
    setTcgSeries: store.setTcgSeries,
    setAgeGroup: store.setAgeGroup,
    setSearchQuery: store.setSearchQuery,
    resetFilters: store.resetFilters,
  }
}
