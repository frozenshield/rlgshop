import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { useStorage } from "@vueuse/core";
import type { CartItem, ToyProduct } from "@/shared/types/toy.types";
import { useAuthStore } from "@/modules/auth/auth.store";

export const useCartStore = defineStore("cartStore", () => {
  // Persisted cart in localStorage so trainer's items stay safe
  const items = useStorage<CartItem[]>("rlg-shop-cart-items", []);
  const isDrawerOpen = ref(false);
  const authStore = useAuthStore();
  const appliedPromo = useStorage<string | null>("rlg-shop-promo", null);
  const promoDiscountPercentage = ref(0);

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
      (sum, item) => sum + item.toy.price * item.quantity,
      0,
    ),
  );

  const promoDiscount = computed(() => {
    if (promoDiscountPercentage.value <= 0) return 0;
    return (subtotal.value * promoDiscountPercentage.value) / 100;
  });

  // Free shipping threshold at $50
  const freeShippingThreshold = 50;
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
    return subtotal.value >= freeShippingThreshold ? 0 : 5.99;
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
    if (!authStore.isAuthenticated) return;
    try {
      const res = await fetch("/api/customer-cart", {
        headers: {
          Authorization: `Bearer ${authStore.token}`,
          Accept: "application/json",
        },
      });
      if (res.ok) {
        const result = await res.json();
        if (result.success && result.data) {
          items.value = result.data.map((backendItem: any) => ({
            cartItemId: backendItem.id,
            toy: backendItem.product,
            quantity: backendItem.quantity,
            selected: true,
          }));
        }
      }
    } catch (e) {
      console.warn("Could not fetch customer cart", e);
    }
  };

  const addItem = async (toy: ToyProduct, quantity = 1) => {
    const existingIndex = items.value.findIndex((i) => i.toy.id === toy.id);
    let cartItemId = undefined;

    if (existingIndex > -1) {
      const currentQty = items.value[existingIndex].quantity;
      items.value[existingIndex].quantity = Math.min(
        toy.stock,
        currentQty + quantity,
      );
      items.value[existingIndex].selected = true;
      cartItemId = items.value[existingIndex].cartItemId;
    } else {
      items.value.push({
        toy,
        quantity: Math.min(toy.stock, quantity),
        selected: true,
      });
    }
    openDrawer();

    if (authStore.isAuthenticated) {
      try {
        const method = cartItemId ? "PUT" : "POST";
        const url = cartItemId ? `/api/customer-cart/${cartItemId}` : "/api/customer-cart";
        const body = {
          product_id: toy.id,
          quantity: existingIndex > -1 ? items.value[existingIndex].quantity : Math.min(toy.stock, quantity)
        };
        const res = await fetch(url, {
          method,
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${authStore.token}`,
            Accept: "application/json",
          },
          body: JSON.stringify(body),
        });

        if (res.ok) {
          const result = await res.json();
          if (result.success && result.data) {
            const index = items.value.findIndex((i) => i.toy.id === toy.id);
            if (index > -1) {
              items.value[index].cartItemId = result.data.id;
            }
          }
        }
      } catch (e) {
        console.warn("Failed to sync cart add", e);
      }
    }
  };

  const toggleSelectItem = (toyId: string) => {
    const item = items.value.find((i) => i.toy.id === toyId);
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

    if (authStore.isAuthenticated) {
        for (const item of itemsToRemove) {
            if (item.cartItemId) {
                try {
                    await fetch(`/api/customer-cart/${item.cartItemId}`, {
                        method: "DELETE",
                        headers: {
                            Authorization: `Bearer ${authStore.token}`,
                        }
                    });
                } catch (e) {
                    console.warn(`Failed to sync removal of cart item ${item.cartItemId}`, e);
                }
            }
        }
    }
  };

  const updateQuantity = async (toyId: string, quantity: number) => {
    const existingIndex = items.value.findIndex((i) => i.toy.id === toyId);
    if (existingIndex > -1) {
      if (quantity <= 0) {
        await removeItem(toyId);
      } else {
        const maxStock = items.value[existingIndex].toy.stock;
        items.value[existingIndex].quantity = Math.min(maxStock, quantity);

        if (authStore.isAuthenticated) {
            const cartItemId = items.value[existingIndex].cartItemId;
            if (cartItemId) {
                try {
                    await fetch(`/api/customer-cart/${cartItemId}`, {
                        method: "PUT",
                        headers: {
                            "Content-Type": "application/json",
                            Authorization: `Bearer ${authStore.token}`,
                        },
                        body: JSON.stringify({
                            product_id: toyId,
                            quantity: items.value[existingIndex].quantity
                        })
                    });
                } catch (e) {
                    console.warn(`Failed to sync quantity update for cart item ${cartItemId}`, e);
                }
            }
        }
      }
    }
  };

  const removeItem = async (toyId: string) => {
    const itemToRemove = items.value.find((i) => i.toy.id === toyId);
    items.value = items.value.filter((i) => i.toy.id !== toyId);

    if (authStore.isAuthenticated && itemToRemove?.cartItemId) {
        try {
            await fetch(`/api/customer-cart/${itemToRemove.cartItemId}`, {
                method: "DELETE",
                headers: {
                    Authorization: `Bearer ${authStore.token}`,
                }
            });
        } catch (e) {
            console.warn(`Failed to sync removal of cart item ${itemToRemove.cartItemId}`, e);
        }
    }
  };

  const clearCart = async () => {
    const currentItems = [...items.value];
    items.value = [];
    appliedPromo.value = null;
    promoDiscountPercentage.value = 0;

    if (authStore.isAuthenticated) {
        for (const item of currentItems) {
            if (item.cartItemId) {
                try {
                    await fetch(`/api/customer-cart/${item.cartItemId}`, {
                        method: "DELETE",
                        headers: {
                            Authorization: `Bearer ${authStore.token}`,
                        }
                    });
                } catch (e) {
                    console.warn(`Failed to sync clear of cart item ${item.cartItemId}`, e);
                }
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
