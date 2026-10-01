import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { ToyProduct } from '@/shared/types/toy.types'
import { mapApiProductToToy } from '@/shared/utils/productMapper'
import { useAuthStore } from '@/modules/auth/auth.store'

const API_BASE = 'http://127.0.0.1:8000/api'

export const useWishlistStore = defineStore('wishlistStore', () => {
  const authStore = useAuthStore()

  // Full product objects loaded from backend
  const favoriteToys = ref<ToyProduct[]>([])
  const isLoading = ref(false)
  const isSyncing = ref(false)

  const count = computed(() => favoriteToys.value.length)
  const favoriteToyIds = computed(() => favoriteToys.value.map((t) => t.id))

  const isFavorite = (toyId: string): boolean => {
    return favoriteToys.value.some((t) => t.id === toyId)
  }

  // ─── Build request headers ────────────────────────────────────────────────
  const getHeaders = (): Record<string, string> => {
    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    }
    if (authStore.token) {
      headers['Authorization'] = `Bearer ${authStore.token}`
    }
    return headers
  }

  const getUserId = (): number => {
    return (authStore.currentUser?.id as number) || 1
  }

  // ─── Fetch favourites from backend ───────────────────────────────────────
  const fetchFavorites = async () => {
    isLoading.value = true
    try {
      const userId = getUserId()
      const res = await fetch(
        `${API_BASE}/customer-favourites?user_id=${userId}`,
        { headers: getHeaders() },
      )
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const json = await res.json()
      const items: any[] = json.data ?? []

      favoriteToys.value = items
        .filter((item) => item.product != null)
        .map((item) => {
          try {
            return mapApiProductToToy(item.product)
          } catch {
            return null
          }
        })
        .filter(Boolean) as ToyProduct[]
    } catch (err) {
      console.error('[wishlist] fetchFavorites error:', err)
    } finally {
      isLoading.value = false
    }
  }

  // ─── Toggle favourite (add or remove) ────────────────────────────────────
  const toggleFavorite = async (toyId: string): Promise<boolean> => {
    isSyncing.value = true
    const productId = parseInt(toyId, 10)
    const userId = getUserId()

    // Optimistic update
    const alreadyFav = isFavorite(toyId)
    if (alreadyFav) {
      favoriteToys.value = favoriteToys.value.filter((t) => t.id !== toyId)
    }

    try {
      const res = await fetch(`${API_BASE}/customer-favourites`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          product_id: productId,
          user_id: userId,
        }),
      })
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const json = await res.json()

      if (json.action === 'added') {
        // Re-fetch to get full product data
        await fetchFavorites()
        return true
      } else {
        // action === 'removed' — optimistic update already applied
        return false
      }
    } catch (err) {
      console.error('[wishlist] toggleFavorite error:', err)
      // Rollback optimistic update on error
      await fetchFavorites()
      return alreadyFav
    } finally {
      isSyncing.value = false
    }
  }

  // ─── Remove by favourite record ID (used in destroy endpoint) ─────────────
  const removeFavoriteById = async (favouriteRecordId: number) => {
    try {
      await fetch(`${API_BASE}/customer-favourites/${favouriteRecordId}`, {
        method: 'DELETE',
        headers: getHeaders(),
      })
      await fetchFavorites()
    } catch (err) {
      console.error('[wishlist] removeFavoriteById error:', err)
    }
  }

  // ─── Clear all (local + remote) ──────────────────────────────────────────
  const clearWishlist = async () => {
    // Remove each favourite one by one via toggle
    const ids = [...favoriteToys.value.map((t) => t.id)]
    favoriteToys.value = [] // optimistic clear
    for (const id of ids) {
      try {
        await fetch(`${API_BASE}/customer-favourites`, {
          method: 'POST',
          headers: getHeaders(),
          body: JSON.stringify({
            product_id: parseInt(id, 10),
            user_id: getUserId(),
          }),
        })
      } catch {
        // ignore individual errors
      }
    }
  }

  return {
    favoriteToys,
    favoriteToyIds,
    count,
    isLoading,
    isSyncing,
    isFavorite,
    toggleFavorite,
    clearWishlist,
    fetchFavorites,
    removeFavoriteById,
  }
})
