import { defineStore } from 'pinia'
import { computed } from 'vue'
import { useStorage } from '@vueuse/core'
import type { ToyProduct } from '@/shared/types/toy.types'
import { MOCK_TOYS_DATA } from '@/shared/constants/mock-toys.data'

export const useWishlistStore = defineStore('wishlistStore', () => {
  // Store wishlist product IDs in localStorage
  const favoriteToyIds = useStorage<string[]>('rlg-shop-wishlist-ids', [])

  const count = computed(() => favoriteToyIds.value.length)

  // Map IDs back to full product objects
  const favoriteToys = computed<ToyProduct[]>(() => {
    return MOCK_TOYS_DATA.filter((toy) => favoriteToyIds.value.includes(toy.id))
  })

  const isFavorite = (toyId: string): boolean => {
    return favoriteToyIds.value.includes(toyId)
  }

  const toggleFavorite = (toyId: string): boolean => {
    const index = favoriteToyIds.value.indexOf(toyId)
    if (index > -1) {
      favoriteToyIds.value.splice(index, 1)
      return false
    } else {
      favoriteToyIds.value.push(toyId)
      return true
    }
  }

  const clearWishlist = () => {
    favoriteToyIds.value = []
  }

  return {
    favoriteToyIds,
    favoriteToys,
    count,
    isFavorite,
    toggleFavorite,
    clearWishlist,
  }
})
