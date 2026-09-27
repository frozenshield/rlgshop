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
  const getStoredToken = (): string | null => {
    if (typeof window === "undefined" || !window.localStorage) return null;
    const t = localStorage.getItem("auth_token");
    return t && t !== "null" && t !== "undefined" ? t : null;
  };

  const getStoredUser = (): UserProfile | null => {
    if (typeof window === "undefined" || !window.localStorage) return null;
    const raw = localStorage.getItem("auth_user");
    if (!raw || raw === "null" || raw === "undefined") return null;
    try {
      return typeof raw === "string" ? JSON.parse(raw) : raw;
    } catch {
      return null;
    }
  };

  const token = ref<string | null>(getStoredToken());
  const storedUser = ref<UserProfile | null>(getStoredUser());

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
    try {
      localStorage.setItem("auth_token", newToken);
    } catch {}
    if (user) {
      currentUser.value = { ...currentUser.value, ...user };
      storedUser.value = currentUser.value;
      try {
        localStorage.setItem("auth_user", JSON.stringify(currentUser.value));
      } catch {}
    }
  };

  const fetchCurrentUser = async () => {
    if (!token.value) return;
    try {
      const res = await fetch("/api/user", {
        headers: {
          Authorization: `Bearer ${token.value}`,
          Accept: "application/json",
        },
      });
      if (res.ok) {
        const data = await res.json();
        currentUser.value = { ...currentUser.value, ...data };
        storedUser.value = currentUser.value;
        try {
          localStorage.setItem("auth_user", JSON.stringify(currentUser.value));
        } catch {}
      }
    } catch {
      // Backend not running or offline; keep cached user
    }
  };

  const updateProfile = async (fields: Partial<UserProfile>) => {
    // 1. Optimistic update
    currentUser.value = { ...currentUser.value, ...fields };
    storedUser.value = currentUser.value;
    try {
      localStorage.setItem("auth_user", JSON.stringify(currentUser.value));
    } catch {}

    // 2. Sync to Backend (if online)
    if (token.value) {
      try {
        await fetch("/api/user/profile", {
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
        await fetch("/api/user/settings", {
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
    const savedToken = token.value;

    // Immediately clear reactive state and storage synchronously so UI updates instantly
    token.value = null;
    storedUser.value = null;
    try {
      localStorage.removeItem("auth_token");
      localStorage.removeItem("auth_user");
    } catch {}

    currentUser.value = {
      id: 0,
      name: "",
      username: "",
      email: "",
      avatar: null,
      user_type: "customer",
    };

    // Notify backend asynchronously without blocking or failing client-side logout
    if (savedToken) {
      try {
        await fetch("/api/logout", {
          method: "POST",
          headers: {
            Authorization: `Bearer ${savedToken}`,
            Accept: "application/json",
          },
          signal: AbortSignal.timeout(1500),
        });
      } catch {
        // Handled silently
      }
    }
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
