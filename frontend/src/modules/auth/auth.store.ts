import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { useStorage } from "@vueuse/core";

export interface UserProfile {
  id?: number | string;
  name: string;
  username?: string;
  email: string;
  phone?: string;
  avatar?: string | null;
  user_type?: "customer" | "staff" | "admin";
  // Shipping / Address Fields
  address_line1?: string;
  city?: string;
  postal_code?: string;
  country?: string;
  // Collector Hobby Fields
  favorite_franchise?: string;
  bio?: string;
}

export interface UserSettings {
  email_notifications: boolean;
  order_updates_sms: boolean;
  marketing_emails: boolean;
  currency_preference: "PHP" | "USD" | "JPY";
  two_factor_auth: boolean;
  public_collection: boolean;
}

export const useAuthStore = defineStore("authStore", () => {
  // ─── State ──────────────────────────────────────────────────────────────────
  const token = useStorage<string | null>("auth_token", null);
  const storedUser = useStorage<UserProfile | null>("auth_user", null);

  const currentUser = ref<UserProfile>(
    storedUser.value || {
      id: 1,
      name: "Collector",
      username: "hobby_collector",
      email: "collector@rlgshop.local",
      phone: "+63 912 345 6789",
      avatar: null,
      user_type: "customer",
      address_line1: "123 Hobby St, Metro Manila",
      city: "Quezon City",
      postal_code: "1100",
      country: "Philippines",
      favorite_franchise: "Pokémon TCG",
      bio: "Sealed box collector and Gunpla builder since 2018.",
    },
  );

  const settings = ref<UserSettings>({
    email_notifications: true,
    order_updates_sms: true,
    marketing_emails: false,
    currency_preference: "PHP",
    two_factor_auth: false,
    public_collection: true,
  });

  const isAuthenticated = computed(() => !!token.value);

  // ─── Actions ────────────────────────────────────────────────────────────────
  const setAuth = (newToken: string, user?: Partial<UserProfile>) => {
    token.value = newToken;
    if (user) {
      currentUser.value = { ...currentUser.value, ...user };
      storedUser.value = currentUser.value;
    }
  };

  const fetchCurrentUser = async () => {
    if (!token.value) return;
    try {
      const res = await fetch("http://localhost:8000/api/user", {
        headers: {
          Authorization: `Bearer ${token.value}`,
          Accept: "application/json",
        },
      });
      if (res.ok) {
        const data = await res.json();
        currentUser.value = { ...currentUser.value, ...data };
        storedUser.value = currentUser.value;
      }
    } catch {
      // Backend not running or offline; keep cached user
    }
  };

  const updateProfile = async (fields: Partial<UserProfile>) => {
    // 1. Optimistic update
    currentUser.value = { ...currentUser.value, ...fields };
    storedUser.value = currentUser.value;

    // 2. Sync to Backend (if online)
    if (token.value) {
      try {
        await fetch("http://localhost:8000/api/user/profile", {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${token.value}`,
            Accept: "application/json",
          },
          body: JSON.stringify(fields),
        });
      } catch {
        // Handled silently or by caller
      }
    }
    return currentUser.value;
  };

  const updateSettings = async (newSettings: Partial<UserSettings>) => {
    settings.value = { ...settings.value, ...newSettings };
    // Sync to backend hook if token is present
    if (token.value) {
      try {
        await fetch("http://localhost:8000/api/user/settings", {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${token.value}`,
            Accept: "application/json",
          },
          body: JSON.stringify(newSettings),
        });
      } catch {
        // Handled silently
      }
    }
    return settings.value;
  };

  const logout = async () => {
    if (token.value) {
      try {
        await fetch("http://localhost:8000/api/logout", {
          method: "POST",
          headers: {
            Authorization: `Bearer ${token.value}`,
            Accept: "application/json",
          },
        });
      } catch {
        // Ignore network errors on logout
      }
    }
    token.value = null;
    storedUser.value = null;
    localStorage.removeItem("auth_token");
    localStorage.removeItem("auth_user");
  };

  return {
    token,
    currentUser,
    settings,
    isAuthenticated,
    setAuth,
    fetchCurrentUser,
    updateProfile,
    updateSettings,
    logout,
  };
});
