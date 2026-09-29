import { defineStore } from 'pinia'
import { computed } from 'vue'
import { useStorage } from '@vueuse/core'
import type { ToyProduct } from '@/shared/types/toy.types'
import { MOCK_TOYS_DATA } from '@/shared/constants/mock-toys.data'
import { useAuthStore } from '../auth/auth.store'

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

  const fetchWishlist = async () => {
    const authStore = useAuthStore()
    if (!authStore.isAuthenticated) return

    try {
      const res = await fetch('/api/customer-favourites', {
        headers: {
          Authorization: `Bearer ${authStore.token}`,
          Accept: 'application/json'
        }
      })
      if (res.ok) {
        const data = await res.json()
        const items: any[] = data.data || []
        favoriteToyIds.value = items.map(item => item.product_id.toString())
      }
    } catch (e) {
      console.warn('Could not fetch wishlist', e)
    }
  }

  const toggleFavorite = async (toyId: string) => {
    const authStore = useAuthStore()
    const index = favoriteToyIds.value.indexOf(toyId)
    const isCurrentlyFavorite = index > -1

    // Optimistic update
    if (isCurrentlyFavorite) {
      favoriteToyIds.value.splice(index, 1)
    } else {
      favoriteToyIds.value.push(toyId)
    }

    // Sync with backend if authenticated
    if (authStore.isAuthenticated) {
      try {
        if (isCurrentlyFavorite) {
          // It was favorite, we just removed it locally, so DELETE
          await fetch(`/api/customer-favourites/${toyId}`, {
            method: 'DELETE',
            headers: {
              Authorization: `Bearer ${authStore.token}`,
              Accept: 'application/json'
            }
          })
        } else {
          // It was NOT favorite, we just added it locally, so POST
          await fetch('/api/customer-favourites', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              Authorization: `Bearer ${authStore.token}`,
              Accept: 'application/json'
            },
            body: JSON.stringify({ product_id: parseInt(toyId) })
          })
        }
      } catch (e) {
        console.warn('Failed to sync wishlist change', e)
      }
    }

    return !isCurrentlyFavorite
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
    fetchWishlist,
  }
})
