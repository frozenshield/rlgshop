import { storeToRefs } from 'pinia'
import { useCartStore } from './cart.store'
import { formatCurrency } from '@/shared/utils/currency.util'
import { ref } from 'vue'

export const useCartComposable = () => {
  const store = useCartStore()
  const {
    items,
    isDrawerOpen,
    appliedPromo,
    promoDiscountPercentage,
    totalItemCount,
    subtotal,
    promoDiscount,
    freeShippingProgress,
    amountNeededForFreeShipping,
    standardShippingCost,
    grandTotal,
  } = storeToRefs(store)

  const promoInput = ref('')
  const promoStatus = ref<{ message: string; isError: boolean } | null>(null)

  const handleApplyPromo = () => {
    if (!promoInput.value.trim()) return
    const result = store.applyPromo(promoInput.value)
    promoStatus.value = {
      message: result.message,
      isError: !result.success,
    }
    if (result.success) {
      promoInput.value = ''
    }
  }

  const handleRemovePromo = () => {
    store.removePromo()
    promoStatus.value = null
  }

  return {
    items,
    isDrawerOpen,
    appliedPromo,
    promoDiscountPercentage,
    totalItemCount,
    subtotal,
    promoDiscount,
    freeShippingProgress,
    amountNeededForFreeShipping,
    standardShippingCost,
    grandTotal,
    promoInput,
    promoStatus,

    formattedSubtotal: () => formatCurrency(subtotal.value),
    formattedTotal: () => formatCurrency(grandTotal.value),
    formattedDiscount: () => formatCurrency(promoDiscount.value),

    openDrawer: store.openDrawer,
    closeDrawer: store.closeDrawer,
    toggleDrawer: store.toggleDrawer,
    addItem: store.addItem,
    updateQuantity: store.updateQuantity,
    removeItem: store.removeItem,
    clearCart: store.clearCart,
    handleApplyPromo,
    handleRemovePromo,
  }
}
