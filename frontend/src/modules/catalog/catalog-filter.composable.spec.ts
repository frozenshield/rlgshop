import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useCatalogStore } from './catalog.store'
import { useCatalogFilterComposable } from './catalog-filter.composable'

describe('catalog-filter.composable', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('initially returns all toys', () => {
    const { filteredToys } = useCatalogFilterComposable()
    expect(filteredToys.value.length).toBeGreaterThan(0)
  })

  it('filters toys by category', () => {
    const store = useCatalogStore()
    const { filteredToys } = useCatalogFilterComposable()

    store.setCategory('tcg')
    expect(filteredToys.value.every((toy) => toy.category === 'tcg')).toBe(true)
    expect(filteredToys.value.length).toBeGreaterThan(0)
  })

  it('filters toys by search query', () => {
    const store = useCatalogStore()
    const { filteredToys } = useCatalogFilterComposable()

    store.setSearchQuery('Pikachu')
    expect(filteredToys.value.length).toBeGreaterThan(0)
    expect(filteredToys.value[0].name).toContain('Pikachu')
  })

  it('sorts toys by price ascending', () => {
    const store = useCatalogStore()
    const { filteredToys } = useCatalogFilterComposable()

    store.sortBy = 'price-asc'
    const prices = filteredToys.value.map((t) => t.price)
    for (let i = 0; i < prices.length - 1; i++) {
      expect(prices[i]).toBeLessThanOrEqual(prices[i + 1])
    }
  })

  it('resets filters properly', () => {
    const store = useCatalogStore()
    const { hasActiveFilters, resetFilters } = useCatalogFilterComposable()

    store.setCategory('anime-merchandise')
    expect(hasActiveFilters.value).toBe(true)

    resetFilters()
    expect(hasActiveFilters.value).toBe(false)
    expect(store.selectedCategory).toBe('all')
  })
})
