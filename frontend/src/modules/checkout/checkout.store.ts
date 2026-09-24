import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useStorage } from '@vueuse/core'
import type { CheckoutFormData, PlacedOrder } from '@/shared/types/toy.types'
import { useCartStore } from '../cart/cart.store'

export const useCheckoutStore = defineStore('checkoutStore', () => {
  const cartStore = useCartStore()

  // Last completed order
  const lastPlacedOrder = ref<PlacedOrder | null>(null)
  const isSuccessModalOpen = ref(false)
  const isSubmitting = ref(false)

  // Saved orders history
  const orderHistory = useStorage<PlacedOrder[]>('rlg-shop-order-history', [])

  const initialFormData: CheckoutFormData = {
    firstName: '',
    lastName: '',
    email: '',
    phone: '',
    streetAddress: '',
    city: '',
    postalCode: '',
    notes: '',
    deliveryOption: 'standard',
    giftMessage: '',
    paymentMethod: 'card',
    cardNumber: '',
    cardExpiry: '',
    cardCvv: '',
  }

  const formData = ref<CheckoutFormData>({ ...initialFormData })

  // Delivery option fees
  const getDeliveryFee = (option: 'standard' | 'express' | 'gift-wrapped'): number => {
    switch (option) {
      case 'express':
        return 12.99
      case 'gift-wrapped':
        return 7.99
      case 'standard':
      default:
        return cartStore.standardShippingCost
    }
  }

  const placeOrder = async (data: CheckoutFormData): Promise<PlacedOrder> => {
    isSubmitting.value = true

    // Simulate payment processing latency
    await new Promise((resolve) => setTimeout(resolve, 1200))

    const orderId = `PKM-${Math.floor(100000 + Math.random() * 900000)}`
    const shippingFee = getDeliveryFee(data.deliveryOption)
    const orderTotal = Math.max(0, cartStore.subtotal - cartStore.promoDiscount + shippingFee)

    const deliveryDays = data.deliveryOption === 'express' ? 2 : 4
    const estDate = new Date()
    estDate.setDate(estDate.getDate() + deliveryDays)

    const order: PlacedOrder = {
      orderId,
      items: [...cartStore.items],
      subtotal: cartStore.subtotal,
      shippingCost: shippingFee,
      discountAmount: cartStore.promoDiscount,
      total: orderTotal,
      shippingDetails: { ...data },
      createdAt: new Date().toISOString(),
      estimatedDeliveryDate: estDate.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
      }),
    }

    lastPlacedOrder.value = order
    orderHistory.value.unshift(order)

    // Clear cart on successful order
    cartStore.clearCart()

    isSubmitting.value = false
    isSuccessModalOpen.value = true

    return order
  }

  const closeSuccessModal = () => {
    isSuccessModalOpen.value = false
  }

  const resetForm = () => {
    formData.value = { ...initialFormData }
  }

  return {
    lastPlacedOrder,
    isSuccessModalOpen,
    isSubmitting,
    orderHistory,
    formData,
    placeOrder,
    closeSuccessModal,
    resetForm,
    getDeliveryFee,
  }
})
