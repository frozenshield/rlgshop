import { storeToRefs } from 'pinia'
import { useWishlistStore } from './wishlist.store'
import { useCartStore } from '../cart/cart.store'

export const useWishlistComposable = () => {
  const wishlistStore = useWishlistStore()
  const cartStore = useCartStore()

  const { favoriteToyIds, favoriteToys, count } = storeToRefs(wishlistStore)

  const moveAllToCart = () => {
    favoriteToys.value.forEach((toy) => {
      cartStore.addItem(toy, 1)
    })
    wishlistStore.clearWishlist()
    cartStore.openDrawer()
  }

  const addSingleToCart = (toyId: string) => {
    const toy = favoriteToys.value.find((t) => t.id === toyId)
    if (toy) {
      cartStore.addItem(toy, 1)
      wishlistStore.toggleFavorite(toyId)
    }
  }

  return {
    favoriteToyIds,
    favoriteToys,
    count,
    isFavorite: wishlistStore.isFavorite,
    toggleFavorite: wishlistStore.toggleFavorite,
    clearWishlist: wishlistStore.clearWishlist,
    moveAllToCart,
    addSingleToCart,
  }
}
