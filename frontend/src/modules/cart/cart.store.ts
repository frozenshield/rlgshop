import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { useStorage } from "@vueuse/core";
import type { CartItem, ToyProduct } from "@/shared/types/toy.types";
import { useAuthStore } from "@/modules/auth/auth.store";
import { mapApiProductToToy } from "@/shared/utils/productMapper";

export const useCartStore = defineStore("cartStore", () => {
  // Persisted cart in localStorage
  const items = useStorage<CartItem[]>("rlg-shop-cart-items", []);
  const isDrawerOpen = ref(false);
  const authStore = useAuthStore();
  const appliedPromo = useStorage<string | null>("rlg-shop-promo", null);
  const promoDiscountPercentage = ref(0);
  const isLoading = ref(false);

  // Hobby & collector valid promo codes
  const VALID_PROMOS: Record<string, number> = {
    HOBBY10: 10,
    GUNPLA20: 20,
    COLLECTOR25: 25,
    PIKACHU10: 10,
    POKEBALL20: 20,
    MASTERBALL: 25,
  };

  // Set initial promo discount if stored
  if (appliedPromo.value && VALID_PROMOS[appliedPromo.value]) {
    promoDiscountPercentage.value = VALID_PROMOS[appliedPromo.value];
  }

  // Purge any legacy dummy / mock items (e.g. mock items with string IDs like "toy-1", "charizard-etb")
  const purgeDummyItems = () => {
    if (Array.isArray(items.value) && items.value.length > 0) {
      items.value = items.value.filter((item) => {
        if (!item || !item.toy) return false;
        const idStr = String(item.toy.id);
        // Only keep items with positive integer IDs matching database products
        return /^\d+$/.test(idStr);
      });
    }
  };
  purgeDummyItems();

  // Helper to build request headers
  const getHeaders = (): Record<string, string> => {
    const headers: Record<string, string> = {
      "Content-Type": "application/json",
      Accept: "application/json",
    };
    if (authStore.token) {
      headers.Authorization = `Bearer ${authStore.token}`;
    }
    return headers;
  };

  const getCustomerId = (): number => {
    if (authStore.currentUser?.id) {
      const parsed = parseInt(
        String(authStore.currentUser.id).replace(/\D/g, ""),
        10,
      );
      if (!isNaN(parsed) && parsed > 0) return parsed;
    }
    return 1;
  };

  // Getters
  const selectedItems = computed(() =>
    items.value.filter((item) => item.selected !== false),
  );

  const selectedItemCount = computed(() =>
    selectedItems.value.reduce((total, item) => total + item.quantity, 0),
  );

  const isAllSelected = computed(
    () =>
      items.value.length > 0 &&
      items.value.every((item) => item.selected !== false),
  );

  const totalItemCount = computed(() =>
    items.value.reduce((total, item) => total + item.quantity, 0),
  );

  const subtotal = computed(() =>
    selectedItems.value.reduce(
      (sum, item) => sum + (Number(item.toy.price) || 0) * item.quantity,
      0,
    ),
  );

  const promoDiscount = computed(() => {
    if (promoDiscountPercentage.value <= 0) return 0;
    return (subtotal.value * promoDiscountPercentage.value) / 100;
  });

  // Free shipping threshold at ₱5,000 (or $50)
  const freeShippingThreshold = 5000;
  const freeShippingProgress = computed(() => {
    if (subtotal.value >= freeShippingThreshold) return 100;
    return Math.min(
      100,
      Math.round((subtotal.value / freeShippingThreshold) * 100),
    );
  });

  const amountNeededForFreeShipping = computed(() => {
    const diff = freeShippingThreshold - subtotal.value;
    return diff > 0 ? diff : 0;
  });

  const standardShippingCost = computed(() => {
    if (selectedItems.value.length === 0) return 0;
    return subtotal.value >= freeShippingThreshold ? 0 : 150;
  });

  const grandTotal = computed(() => {
    return Math.max(
      0,
      subtotal.value - promoDiscount.value + standardShippingCost.value,
    );
  });

  // Actions
  const openDrawer = () => {
    isDrawerOpen.value = true;
  };

  const closeDrawer = () => {
    isDrawerOpen.value = false;
  };

  const toggleDrawer = () => {
    isDrawerOpen.value = !isDrawerOpen.value;
  };

  const fetchCart = async () => {
    isLoading.value = true;
    try {
      const customerId = getCustomerId();
      const res = await fetch(`/api/customer-cart?customer_id=${customerId}`, {
        headers: getHeaders(),
      });
      if (res.ok) {
        const result = await res.json();
        if (result.success && Array.isArray(result.data)) {
          // Map backend records to frontend CartItem
          const prevSelectedMap = new Map<number, boolean>();
          items.value.forEach((i) => {
            if (i.cartItemId)
              prevSelectedMap.set(i.cartItemId, i.selected !== false);
          });

          items.value = result.data.map((backendItem: any) => {
            const mappedToy = mapApiProductToToy(backendItem.product);
            const isSelected = prevSelectedMap.has(backendItem.id)
              ? prevSelectedMap.get(backendItem.id)
              : true;
            return {
              cartItemId: backendItem.id,
              toy: mappedToy,
              quantity: backendItem.quantity,
              selected: isSelected,
            };
          });
        }
      }
    } catch (e) {
      console.warn("Could not fetch customer cart from API", e);
    } finally {
      isLoading.value = false;
    }
  };

  // Synchronize cart on initial store creation
  fetchCart();

  const addItem = async (toy: ToyProduct, quantity = 1) => {
    if (!toy || toy.stock <= 0) {
      console.warn("Item is sold out and cannot be added to cart", toy);
      return;
    }

    const numericProductId = parseInt(String(toy.id).replace(/\D/g, ""), 10);
    const existingIndex = items.value.findIndex(
      (i) => String(i.toy.id) === String(toy.id),
    );
    let cartItemId =
      existingIndex > -1 ? items.value[existingIndex].cartItemId : undefined;
    const currentQty =
      existingIndex > -1 ? items.value[existingIndex].quantity : 0;
    const newQty = Math.min(toy.stock || 99, currentQty + quantity);

    if (existingIndex > -1) {
      items.value[existingIndex].quantity = newQty;
      items.value[existingIndex].selected = true;
    } else {
      items.value.push({
        toy,
        quantity: Math.min(toy.stock || 99, quantity),
        selected: true,
      });
    }
    openDrawer();

    // Sync to Backend Database API
    try {
      const method = cartItemId ? "PUT" : "POST";
      const url = cartItemId
        ? `/api/customer-cart/${cartItemId}`
        : "/api/customer-cart";
      const body = {
        customer_id: getCustomerId(),
        product_id:
          !isNaN(numericProductId) && numericProductId > 0
            ? numericProductId
            : 1,
        quantity: newQty,
      };

      const res = await fetch(url, {
        method,
        headers: getHeaders(),
        body: JSON.stringify(body),
      });

      if (res.ok) {
        const result = await res.json();
        if (result.success && result.data) {
          const index = items.value.findIndex(
            (i) => String(i.toy.id) === String(toy.id),
          );
          if (index > -1) {
            items.value[index].cartItemId = result.data.id;
            if (result.data.product) {
              items.value[index].toy = mapApiProductToToy(result.data.product);
            }
          }
        }
      }
    } catch (e) {
      console.warn("Failed to sync cart add to backend", e);
    }
  };

  const toggleSelectItem = (toyId: string) => {
    const item = items.value.find((i) => String(i.toy.id) === String(toyId));
    if (item) {
      item.selected = item.selected === false ? true : false;
    }
  };

  const toggleSelectAll = (selectAll?: boolean) => {
    const targetState =
      typeof selectAll === "boolean" ? selectAll : !isAllSelected.value;
    items.value.forEach((item) => {
      item.selected = targetState;
    });
  };

  const removeSelectedItems = async () => {
    const itemsToRemove = items.value.filter((i) => i.selected !== false);
    items.value = items.value.filter((i) => i.selected === false);
    if (items.value.length === 0) {
      appliedPromo.value = null;
      promoDiscountPercentage.value = 0;
    }

    for (const item of itemsToRemove) {
      if (item.cartItemId) {
        try {
          await fetch(`/api/customer-cart/${item.cartItemId}`, {
            method: "DELETE",
            headers: getHeaders(),
          });
        } catch (e) {
          console.warn(
            `Failed to sync removal of cart item ${item.cartItemId}`,
            e,
          );
        }
      }
    }
  };

  const updateQuantity = async (toyId: string, quantity: number) => {
    const existingIndex = items.value.findIndex(
      (i) => String(i.toy.id) === String(toyId),
    );
    if (existingIndex > -1) {
      if (quantity <= 0) {
        await removeItem(toyId);
      } else {
        const maxStock = items.value[existingIndex].toy.stock || 99;
        const newQty = Math.min(maxStock, quantity);
        items.value[existingIndex].quantity = newQty;

        const cartItemId = items.value[existingIndex].cartItemId;
        if (cartItemId) {
          try {
            await fetch(`/api/customer-cart/${cartItemId}`, {
              method: "PUT",
              headers: getHeaders(),
              body: JSON.stringify({
                quantity: newQty,
                customer_id: getCustomerId(),
              }),
            });
          } catch (e) {
            console.warn(
              `Failed to sync quantity update for cart item ${cartItemId}`,
              e,
            );
          }
        }
      }
    }
  };

  const removeItem = async (toyId: string) => {
    const itemToRemove = items.value.find(
      (i) => String(i.toy.id) === String(toyId),
    );
    items.value = items.value.filter((i) => String(i.toy.id) !== String(toyId));

    if (itemToRemove?.cartItemId) {
      try {
        await fetch(`/api/customer-cart/${itemToRemove.cartItemId}`, {
          method: "DELETE",
          headers: getHeaders(),
        });
      } catch (e) {
        console.warn(
          `Failed to sync removal of cart item ${itemToRemove.cartItemId}`,
          e,
        );
      }
    }
  };

  const clearCart = async () => {
    const currentItems = [...items.value];
    items.value = [];
    appliedPromo.value = null;
    promoDiscountPercentage.value = 0;

    for (const item of currentItems) {
      if (item.cartItemId) {
        try {
          await fetch(`/api/customer-cart/${item.cartItemId}`, {
            method: "DELETE",
            headers: getHeaders(),
          });
        } catch (e) {
          console.warn(
            `Failed to sync clear of cart item ${item.cartItemId}`,
            e,
          );
        }
      }
    }
  };

  const applyPromo = (code: string): { success: boolean; message: string } => {
    const normalized = code.trim().toUpperCase();
    if (VALID_PROMOS[normalized]) {
      appliedPromo.value = normalized;
      promoDiscountPercentage.value = VALID_PROMOS[normalized];
      return {
        success: true,
        message: `Trainer Promo ${normalized} applied: ${VALID_PROMOS[normalized]}% off! ⚡`,
      };
    }
    return {
      success: false,
      message: "Invalid code! Try PIKACHU10 or POKEBALL20",
    };
  };

  const removePromo = () => {
    appliedPromo.value = null;
    promoDiscountPercentage.value = 0;
  };

  return {
    items,
    selectedItems,
    selectedItemCount,
    isAllSelected,
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
    isLoading,

    openDrawer,
    closeDrawer,
    toggleDrawer,
    fetchCart,
    addItem,
    toggleSelectItem,
    toggleSelectAll,
    removeSelectedItems,
    updateQuantity,
    removeItem,
    clearCart,
    applyPromo,
    removePromo,
  };
});
