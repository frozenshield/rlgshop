import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useStorage } from '@vueuse/core'
import type { CartItem, ToyProduct } from '@/shared/types/toy.types'

export const useCartStore = defineStore('cartStore', () => {
  // Persisted cart in localStorage so trainer's items stay safe
  const items = useStorage<CartItem[]>('rlg-shop-cart-items', [])
  const isDrawerOpen = ref(false)
  const appliedPromo = useStorage<string | null>('rlg-shop-promo', null)
  const promoDiscountPercentage = ref(0)

  // Pokemon theme valid promo codes
  const VALID_PROMOS: Record<string, number> = {
    PIKACHU10: 10,
    POKEBALL20: 20,
    MASTERBALL: 25,
  }

  // Set initial promo discount if stored
  if (appliedPromo.value && VALID_PROMOS[appliedPromo.value]) {
    promoDiscountPercentage.value = VALID_PROMOS[appliedPromo.value]
  }

  // Getters
  const totalItemCount = computed(() =>
    items.value.reduce((total, item) => total + item.quantity, 0),
  )

  const subtotal = computed(() =>
    items.value.reduce((sum, item) => sum + item.toy.price * item.quantity, 0),
  )

  const promoDiscount = computed(() => {
    if (promoDiscountPercentage.value <= 0) return 0
    return (subtotal.value * promoDiscountPercentage.value) / 100
  })

  // Free shipping threshold at $50
  const freeShippingThreshold = 50
  const freeShippingProgress = computed(() => {
    if (subtotal.value >= freeShippingThreshold) return 100
    return Math.min(100, Math.round((subtotal.value / freeShippingThreshold) * 100))
  })

  const amountNeededForFreeShipping = computed(() => {
    const diff = freeShippingThreshold - subtotal.value
    return diff > 0 ? diff : 0
  })

  const standardShippingCost = computed(() => {
    if (items.value.length === 0) return 0
    return subtotal.value >= freeShippingThreshold ? 0 : 5.99
  })

  const grandTotal = computed(() => {
    return Math.max(0, subtotal.value - promoDiscount.value + standardShippingCost.value)
  })

  // Actions
  const openDrawer = () => {
    isDrawerOpen.value = true
  }

  const closeDrawer = () => {
    isDrawerOpen.value = false
  }

  const toggleDrawer = () => {
    isDrawerOpen.value = !isDrawerOpen.value
  }

  const addItem = (toy: ToyProduct, quantity = 1) => {
    const existingIndex = items.value.findIndex((i) => i.toy.id === toy.id)
    if (existingIndex > -1) {
      const currentQty = items.value[existingIndex].quantity
      items.value[existingIndex].quantity = Math.min(toy.stock, currentQty + quantity)
    } else {
      items.value.push({
        toy,
        quantity: Math.min(toy.stock, quantity),
      })
    }
    openDrawer()
  }

  const updateQuantity = (toyId: string, quantity: number) => {
    const existingIndex = items.value.findIndex((i) => i.toy.id === toyId)
    if (existingIndex > -1) {
      if (quantity <= 0) {
        removeItem(toyId)
      } else {
        const maxStock = items.value[existingIndex].toy.stock
        items.value[existingIndex].quantity = Math.min(maxStock, quantity)
      }
    }
  }

  const removeItem = (toyId: string) => {
    items.value = items.value.filter((i) => i.toy.id !== toyId)
  }

  const clearCart = () => {
    items.value = []
    appliedPromo.value = null
    promoDiscountPercentage.value = 0
  }

  const applyPromo = (code: string): { success: boolean; message: string } => {
    const normalized = code.trim().toUpperCase()
    if (VALID_PROMOS[normalized]) {
      appliedPromo.value = normalized
      promoDiscountPercentage.value = VALID_PROMOS[normalized]
      return {
        success: true,
        message: `Trainer Promo ${normalized} applied: ${VALID_PROMOS[normalized]}% off! ⚡`,
      }
    }
    return {
      success: false,
      message: 'Invalid code! Try PIKACHU10 or POKEBALL20',
    }
  }

  const removePromo = () => {
    appliedPromo.value = null
    promoDiscountPercentage.value = 0
  }

  return {
    items,
    isDrawerOpen,
    appliedPromo,
    promoDiscountPercentage,
    totalItemCount,
    subtotal,
    promoDiscount,
    freeShippingThreshold,
    freeShippingProgress,
    amountNeededForFreeShipping,
    standardShippingCost,
    grandTotal,

    openDrawer,
    closeDrawer,
    toggleDrawer,
    addItem,
    updateQuantity,
    removeItem,
    clearCart,
    applyPromo,
    removePromo,
  }
})
