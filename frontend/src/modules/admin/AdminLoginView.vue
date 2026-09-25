<script setup lang="ts">
import { ref } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useAdminStore } from "./admin.store";
import type { AdminRole } from "./admin.types";

const router = useRouter();
const route = useRoute();
const adminStore = useAdminStore();

const email = ref("admin@rlghobby.com");
const password = ref("AdminPass2026!");
const role = ref<AdminRole>("super-admin");
const rememberMe = ref(true);
const showPassword = ref(false);
const isLoading = ref(false);
const errorMessage = ref("");

const handleLogin = async () => {
  if (!email.value || !password.value) {
    errorMessage.value =
      "Please provide both administrator email and security password.";
    return;
  }

  isLoading.value = true;
  errorMessage.value = "";

  try {
    await adminStore.login(email.value, password.value);
    const redirectTarget =
      (route.query.redirect as string) || "/admin/dashboard";
    router.push(redirectTarget);
  } catch (e: any) {
    errorMessage.value =
      e?.message ||
      "Access denied: Only authorized users registered in the staff roster can log in.";
  } finally {
    isLoading.value = false;
  }
};

const quickFillRole = (selectedRole: AdminRole, demoEmail: string) => {
  role.value = selectedRole;
  email.value = demoEmail;
  password.value = "AdminPass2026!";
  errorMessage.value = "";
};

const handleContinueSession = () => {
  router.push("/admin/dashboard");
};

const handleSignOutCurrent = () => {
  adminStore.logout();
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
        <!-- Currently Active Session Banner (if already logged in) -->
        <div
          v-if="adminStore.isAuthenticated && adminStore.currentAdmin"
          class="p-3.5 rounded-2xl bg-indigo-950/70 border border-indigo-500/30 text-xs space-y-2.5"
        >
          <div class="flex items-center justify-between">
            <span class="font-bold text-indigo-200 flex items-center gap-1.5">
              <span>👤</span> Active Session Detected
            </span>
            <span
              class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-bold"
            >
              Logged In
            </span>
          </div>
          <p class="text-slate-300 text-[11px]">
            Currently authenticated as
            <strong class="text-white">{{
              adminStore.currentAdmin.name
            }}</strong>
            (<span class="text-indigo-300 font-medium">{{
              adminStore.currentAdmin.roleLabel || adminStore.currentAdmin.role
            }}</span
            >).
          </p>
          <div class="flex items-center gap-2 pt-1">
            <button
              type="button"
              class="flex-1 py-1.5 px-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-[11px] rounded-lg shadow-sm transition-colors cursor-pointer"
              @click="handleContinueSession"
            >
              Enter Dashboard &rarr;
            </button>
            <button
              type="button"
              class="py-1.5 px-3 bg-slate-800 hover:bg-rose-900/50 text-slate-300 hover:text-rose-200 font-bold text-[11px] rounded-lg transition-colors cursor-pointer"
              @click="handleSignOutCurrent"
            >
              Sign Out
            </button>
          </div>
        </div>

        <div class="space-y-1">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-white tracking-tight">
              Staff Authentication
            </h2>
            <span
              class="inline-flex items-center gap-1.5 text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20"
            >
              <span
                class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"
              ></span>
              Staff Roster Only
            </span>
          </div>
          <p class="text-xs text-slate-400">
            Sign in with a registered staff table account to access scoped
            modules.
          </p>
        </div>

        <!-- Role Selector Tabs -->
        <div class="space-y-1.5">
          <div class="flex items-center justify-between">
            <label class="block text-xs font-bold text-slate-300"
              >Select Staff Account</label
            >
            <span class="text-[10px] text-slate-400">Database Verified</span>
          </div>
          <div
            class="grid grid-cols-3 gap-1.5 p-1 bg-slate-950/80 rounded-2xl border border-slate-800"
          >
            <button
              type="button"
              class="py-2 text-[11px] font-bold rounded-xl transition-all cursor-pointer text-center"
              :class="
                email === 'admin@rlghobby.com'
                  ? 'bg-slate-800 text-white shadow-xs'
                  : 'text-slate-400 hover:text-slate-200'
              "
              @click="quickFillRole('super-admin', 'admin@rlghobby.com')"
            >
              👑 Super Admin
            </button>
            <button
              type="button"
              class="py-2 text-[11px] font-bold rounded-xl transition-all cursor-pointer text-center"
              :class="
                email === 'rowena.ops@rlghobby.com'
                  ? 'bg-slate-800 text-white shadow-xs'
                  : 'text-slate-400 hover:text-slate-200'
              "
              @click="quickFillRole('manager', 'rowena.ops@rlghobby.com')"
            >
              💼 Manager
            </button>
            <button
              type="button"
              class="py-2 text-[11px] font-bold rounded-xl transition-all cursor-pointer text-center"
              :class="
                email === 'darwin.pack@rlghobby.com'
                  ? 'bg-slate-800 text-white shadow-xs'
                  : 'text-slate-400 hover:text-slate-200'
              "
              @click="quickFillRole('fulfillment', 'darwin.pack@rlghobby.com')"
            >
              📦 Fulfillment
            </button>
          </div>
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
          <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-300"
              >Staff Email</label
            >
            <div class="relative">
              <input
                v-model="email"
                type="email"
                required
                placeholder="staff@rlghobby.com"
                class="w-full text-xs px-3.5 py-3 rounded-xl bg-slate-950/90 border border-slate-800 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500/30 transition-all pl-10"
              />
              <span class="absolute left-3.5 top-3.5 text-slate-500 text-sm"
                >✉️</span
              >
            </div>
          </div>

          <div class="space-y-1">
            <div class="flex items-center justify-between">
              <label class="block text-xs font-bold text-slate-300"
                >Security Password</label
              >
              <a
                href="#"
                class="text-[11px] font-semibold text-rose-400 hover:underline"
                >Reset key?</a
              >
            </div>
            <div class="relative">
              <input
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                required
                placeholder="••••••••••••"
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
              <span>Remember station for 30 days</span>
            </label>
            <span class="text-[11px] text-slate-400 font-mono">v4.2.0-PRO</span>
          </div>

          <!-- Submit Button -->
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
            <span>{{
              isLoading
                ? "Verifying Credentials..."
                : "Access Command Center &rarr;"
            }}</span>
          </button>
        </form>

        <!-- Quick 1-Click Demo Shortcut -->
        <div class="pt-2 border-t border-slate-800/80 text-center">
          <p class="text-[11px] text-slate-400 mb-2">
            Development Demo Quick-Pass:
          </p>
          <button
            type="button"
            class="w-full py-2 px-3 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-200 text-xs font-bold transition-all border border-slate-700 cursor-pointer flex items-center justify-center gap-1.5"
            @click="
              () => {
                quickFillRole('super-admin', 'admin@rlghobby.com');
                handleLogin();
              }
            "
          >
            <span>⚡ Instant One-Click Admin Access</span>
          </button>
        </div>
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
