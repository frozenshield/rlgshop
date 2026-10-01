import { storeToRefs } from "pinia";
import { useWishlistStore } from "./wishlist.store";
import { useCartStore } from "../cart/cart.store";

export const useWishlistComposable = () => {
  const wishlistStore = useWishlistStore();
  const cartStore = useCartStore();

  const { favoriteToys, favoriteToyIds, count, isLoading, isSyncing } =
    storeToRefs(wishlistStore);

  const moveAllToCart = async () => {
    const toys = [...favoriteToys.value].filter((toy) => toy.stock > 0);
    for (const toy of toys) {
      await cartStore.addItem(toy, 1);
    }
    await wishlistStore.clearWishlist();
    cartStore.openDrawer();
  };

  const addSingleToCart = async (toyId: string) => {
    const toy = favoriteToys.value.find((t) => t.id === toyId);
    if (toy && toy.stock > 0) {
      await cartStore.addItem(toy, 1);
      await wishlistStore.toggleFavorite(toyId);
    }
  };

  return {
    favoriteToyIds,
    favoriteToys,
    count,
    isLoading,
    isSyncing,
    isFavorite: wishlistStore.isFavorite,
    toggleFavorite: wishlistStore.toggleFavorite,
    clearWishlist: wishlistStore.clearWishlist,
    fetchFavorites: wishlistStore.fetchFavorites,
    moveAllToCart,
    addSingleToCart,
  };
};
