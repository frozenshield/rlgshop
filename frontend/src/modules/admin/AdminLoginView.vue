<script setup lang="ts">
import { ref } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useAdminStore } from "./admin.store";

const router = useRouter();
const route = useRoute();
const adminStore = useAdminStore();

const username = ref("");
const password = ref("");
const rememberMe = ref(false);
const showPassword = ref(false);
const isLoading = ref(false);
const errorMessage = ref("");

const handleLogin = async () => {
  if (!username.value.trim() || !password.value) {
    errorMessage.value = "Please enter both username and password.";
    return;
  }

  isLoading.value = true;
  errorMessage.value = "";

  try {
    await adminStore.login(username.value.trim(), password.value);
    let redirectTarget = (route.query.redirect as string) || "/admin/dashboard";
    if (
      !redirectTarget ||
      redirectTarget === "/admin" ||
      redirectTarget === "/admin/" ||
      redirectTarget.startsWith("/admin/login")
    ) {
      redirectTarget = "/admin/dashboard";
    }
    await router.replace(redirectTarget);
  } catch (e: any) {
    errorMessage.value =
      e?.message ||
      "Access denied: Only authorized users registered in the staff roster can log in.";
  } finally {
    isLoading.value = false;
  }
};
</script>

<template>
  <div
    class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center p-4 sm:p-6 font-display relative overflow-hidden select-none"
  >
    <!-- Ambient Background Gradients -->
    <div
      class="absolute -top-40 -left-40 w-96 h-96 rounded-full bg-rose-600/10 blur-[120px] pointer-events-none"
    ></div>
    <div
      class="absolute -bottom-40 -right-40 w-96 h-96 rounded-full bg-indigo-600/10 blur-[120px] pointer-events-none"
    ></div>
    <div
      class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-4xl h-96 rounded-full bg-slate-900/40 blur-[140px] pointer-events-none"
    ></div>

    <div class="w-full max-w-md relative z-10 space-y-6">
      <!-- Top Branding Emblem -->
      <div class="text-center space-y-2">
        <router-link to="/" class="inline-flex items-center gap-3 group">
          <div
            class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-rose-600 via-slate-800 to-indigo-600 flex items-center justify-center shadow-xl shadow-rose-900/20 border border-slate-700 group-hover:scale-105 transition-transform text-white"
          >
            <svg
              viewBox="0 0 24 24"
              class="w-7 h-7 fill-none stroke-current"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <rect x="3" y="3" width="18" height="18" rx="4" />
              <path d="m9 12 2 2 4-4" />
              <path d="M12 3v4" />
              <path d="M12 17v4" />
            </svg>
          </div>
          <div class="text-left">
            <h1
              class="text-xl font-black tracking-tight text-white flex items-center gap-1.5"
            >
              RLG <span class="text-rose-500">HOBBY</span>
              <span
                class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-800 text-slate-300 border border-slate-700"
                >OS</span
              >
            </h1>
            <p class="text-[11px] text-slate-400 font-medium">
              Store Operations &amp; Command Center
            </p>
          </div>
        </router-link>
      </div>

      <!-- Main Login Card -->
      <div
        class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/90 rounded-3xl p-6 sm:p-8 shadow-2xl shadow-black/80 space-y-6 relative"
      >
        <div class="space-y-1">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-white tracking-tight">
              Staff Login
            </h2>
            <span
              class="inline-flex items-center gap-1.5 text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20"
            >
              <span
                class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"
              ></span>
              Vault Secure
            </span>
          </div>
          <p class="text-xs text-slate-400">
            Sign in with your staff account to access the command center.
          </p>
        </div>

        <!-- Error Alert -->
        <div
          v-if="errorMessage"
          class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs font-semibold flex items-center gap-2"
        >
          <span>⚠️</span>
          <span>{{ errorMessage }}</span>
        </div>

        <!-- Form Inputs -->
        <form @submit.prevent="handleLogin" class="space-y-4">
          <!-- Username / Email Field -->
          <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-300"
              >Username or Email</label
            >
            <div class="relative">
              <input
                v-model="username"
                type="text"
                required
                autocomplete="username"
                placeholder="Enter username or email"
                class="w-full text-xs px-3.5 py-3 rounded-xl bg-slate-950/90 border border-slate-800 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500/30 transition-all pl-10"
              />
              <span class="absolute left-3.5 top-3.5 text-slate-500 text-sm"
                >👤</span
              >
            </div>
          </div>

          <!-- Password Field -->
          <div class="space-y-1">
            <div class="flex items-center justify-between">
              <label class="block text-xs font-bold text-slate-300"
                >Password</label
              >
            </div>
            <div class="relative">
              <input
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                required
                autocomplete="current-password"
                placeholder="Enter your password"
                class="w-full text-xs px-3.5 py-3 rounded-xl bg-slate-950/90 border border-slate-800 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500/30 transition-all pl-10 pr-10"
              />
              <span class="absolute left-3.5 top-3.5 text-slate-500 text-sm"
                >🔒</span
              >
              <button
                type="button"
                class="absolute right-3.5 top-3.5 text-slate-400 hover:text-slate-200 text-xs cursor-pointer"
                @click="showPassword = !showPassword"
              >
                {{ showPassword ? "🙈" : "👁️" }}
              </button>
            </div>
          </div>

          <!-- Remember Me Checkbox -->
          <div class="flex items-center justify-between text-xs pt-1">
            <label
              class="flex items-center gap-2 cursor-pointer text-slate-300"
            >
              <input
                v-model="rememberMe"
                type="checkbox"
                class="rounded accent-rose-600 w-4 h-4 cursor-pointer"
              />
              <span>Remember me</span>
            </label>
            <span class="text-[11px] text-slate-500 font-mono">v4.2.0-PRO</span>
          </div>

          <!-- Ordinary Login Button -->
          <button
            type="submit"
            :disabled="isLoading"
            class="w-full py-3.5 px-4 bg-gradient-to-r from-rose-600 via-rose-700 to-indigo-600 hover:from-rose-500 hover:to-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-rose-950/50 transition-all active:scale-98 cursor-pointer flex items-center justify-center gap-2 disabled:opacity-60"
          >
            <svg
              v-if="isLoading"
              class="animate-spin h-4 w-4 text-white"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              ></circle>
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              ></path>
            </svg>
            <span>{{ isLoading ? "Logging in..." : "Log In" }}</span>
          </button>
        </form>
      </div>

      <!-- Footer Info & Return to Store -->
      <div
        class="flex items-center justify-between text-xs text-slate-500 px-2"
      >
        <router-link
          to="/"
          class="hover:text-rose-400 transition-colors flex items-center gap-1"
        >
          <span>&larr;</span>
          <span>Back to Storefront</span>
        </router-link>
        <span class="flex items-center gap-1">
          <span>🛡️</span>
          <span>Encrypted Session Guard</span>
        </span>
      </div>
    </div>
  </div>
</template>
