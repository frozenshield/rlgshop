<script setup lang="ts">
import { ref, computed, onMounted, watchEffect } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useAdminStore } from "./admin.store";

const router = useRouter();
const route = useRoute();
const adminStore = useAdminStore();

const isSidebarCollapsed = ref(false);
const isMobileMenuOpen = ref(false);

// Guard: if no admin session, redirect to login
onMounted(() => {
  if (!adminStore.currentAdmin) {
    router.replace("/admin/login");
  } else {
    // Refresh permissions for currently logged in admin/staff
    adminStore.fetchStaffPermissions();
  }
});

const handleSignOut = () => {
  adminStore.logout();
  router.push("/admin/login");
};

// Master list of all admin sidebar modules
const allNavItems = computed(() => [
  {
    path: "/admin/dashboard",
    code: "dashboard",
    name: "Dashboard",
    icon: "📊",
    badge: null,
  },
  {
    path: "/admin/orders",
    code: "orders",
    name: "Orders",
    icon: "📦",
    badge: adminStore.metrics?.pendingOrdersCount ?? null,
  },
  {
    path: "/admin/inventory",
    code: "inventory",
    name: "Inventory & Stock",
    icon: "🏷️",
    badge: adminStore.metrics?.lowStockCount ? "Low" : null,
  },
  {
    path: "/admin/products",
    code: "products",
    name: "Product Catalog",
    icon: "➕",
    badge: "New",
  },
  {
    path: "/admin/customers",
    code: "customers",
    name: "Customers & CRM",
    icon: "👥",
    badge: adminStore.metrics?.unreadInquiriesCount || null,
  },
  {
    path: "/admin/marketing",
    code: "marketing",
    name: "Marketing & Promos",
    icon: "🎯",
    badge: null,
  },
  {
    path: "/admin/cms",
    code: "cms",
    name: "CMS & Storefront",
    icon: "🎨",
    badge: null,
  },
  {
    path: "/admin/analytics",
    code: "analytics",
    name: "Analytics & Reports",
    icon: "📈",
    badge: null,
  },
  {
    path: "/admin/settings",
    code: "settings",
    name: "Settings & Config",
    icon: "⚙️",
    badge: null,
  },
]);

// Filter visible sidebar items by staff access matrix permissions
const navItems = computed(() => {
  return allNavItems.value.filter((item) =>
    adminStore.canAccess(item.code, item.path),
  );
});

// Friendly role label
const roleLabel = computed(() => {
  if (adminStore.currentAdmin?.roleLabel) {
    return adminStore.currentAdmin.roleLabel;
  }
  const r = adminStore.currentAdmin?.role;
  if (r === "super-admin") return "Super Admin (Unrestricted)";
  if (r === "manager") return "Store Manager (Catalog & Operations)";
  if (r === "fulfillment") return "Fulfillment Staff (Packing & Shipping only)";
  return "Staff Member";
});

// Access matrix route guard: if navigating to an unauthorized module URL, redirect to dashboard or first allowed module
watchEffect(() => {
  if (
    adminStore.currentAdmin &&
    route.path.startsWith("/admin") &&
    route.path !== "/admin/login"
  ) {
    if (navItems.value.length > 0 && !adminStore.canAccessPath(route.path)) {
      const fallbackTarget = navItems.value[0]?.path || "/admin/dashboard";
      if (route.path !== fallbackTarget) {
        router.replace(fallbackTarget);
      }
    }
  }
});
</script>

<template>
  <div
    class="min-h-screen bg-slate-100/70 text-slate-800 flex font-display select-none"
  >
    <!-- Desktop Sidebar -->
    <aside
      class="hidden md:flex flex-col bg-slate-950 text-slate-300 border-r border-slate-800 transition-all duration-300 z-30"
      :class="isSidebarCollapsed ? 'w-20' : 'w-64'"
    >
      <!-- Brand Logo / Header -->
      <div
        class="h-20 flex items-center justify-between px-5 border-b border-slate-800/80"
      >
        <router-link
          to="/admin/dashboard"
          class="flex items-center gap-3 overflow-hidden"
        >
          <div
            class="w-10 h-10 rounded-xl bg-gradient-to-tr from-rose-600 via-slate-800 to-indigo-600 flex items-center justify-center text-white font-black text-lg flex-shrink-0 shadow-md"
          >
            <svg
              viewBox="0 0 24 24"
              class="w-5 h-5 fill-none stroke-current"
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
          <div v-if="!isSidebarCollapsed" class="min-w-0">
            <h2 class="text-sm font-black text-white tracking-tight truncate">
              RLG <span class="text-rose-500">HOBBY</span>
            </h2>
            <p class="text-[10px] text-slate-400 font-medium">Command Center</p>
          </div>
        </router-link>

        <button
          type="button"
          class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer"
          :title="isSidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
          @click="isSidebarCollapsed = !isSidebarCollapsed"
        >
          <svg
            class="w-4 h-4 transition-transform"
            :class="isSidebarCollapsed ? 'rotate-180' : ''"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M11 19l-7-7 7-7m8 14l-7-7 7-7"
            />
          </svg>
        </button>
      </div>

      <!-- Navigation Links -->
      <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <router-link
          v-for="item in navItems"
          :key="item.path"
          :to="item.path"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition-all group"
          :class="
            route.path.startsWith(item.path)
              ? 'bg-rose-600 text-white shadow-sm'
              : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200'
          "
          :title="item.name"
        >
          <span class="text-lg flex-shrink-0">{{ item.icon }}</span>
          <span v-if="!isSidebarCollapsed" class="flex-1 truncate">{{
            item.name
          }}</span>
          <span
            v-if="!isSidebarCollapsed && item.badge"
            class="text-[10px] font-extrabold px-1.5 py-0.2 rounded-full"
            :class="
              route.path.startsWith(item.path)
                ? 'bg-white/20 text-white'
                : 'bg-slate-800 text-rose-400'
            "
          >
            {{ item.badge }}
          </span>
        </router-link>
      </nav>

      <!-- Bottom Staff Profile & Quick Logout -->
      <div class="p-3 border-t border-slate-800/80">
        <div
          class="flex items-center gap-3 p-2 rounded-xl bg-slate-900/60 border border-slate-800/60"
          :class="isSidebarCollapsed ? 'justify-center' : ''"
        >
          <div
            class="w-8 h-8 rounded-full bg-gradient-to-tr from-rose-500 to-indigo-600 text-white font-bold text-xs flex items-center justify-center flex-shrink-0"
          >
            {{
              (adminStore.currentAdmin?.name || "Admin").charAt(0).toUpperCase()
            }}
          </div>
          <div v-if="!isSidebarCollapsed" class="flex-1 min-w-0">
            <p class="text-xs font-bold text-white truncate">
              {{ adminStore.currentAdmin?.name || "Administrator" }}
            </p>
            <p class="text-[10px] text-slate-400 capitalize truncate">
              {{ roleLabel }}
            </p>
          </div>
          <button
            v-if="!isSidebarCollapsed"
            type="button"
            class="text-slate-400 hover:text-rose-400 p-1 rounded transition-colors cursor-pointer text-xs"
            title="Sign out"
            @click="handleSignOut"
          >
            🚪
          </button>
        </div>
      </div>
    </aside>

    <!-- Main Workspace -->
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
      <!-- Top Navbar -->
      <header
        class="h-20 bg-white border-b border-slate-200 px-4 sm:px-8 flex items-center justify-between gap-4 sticky top-0 z-20 shadow-xs"
      >
        <div class="flex items-center gap-3">
          <!-- Mobile Menu Toggle -->
          <button
            type="button"
            class="md:hidden p-2 rounded-xl text-slate-600 hover:bg-slate-100"
            @click="isMobileMenuOpen = !isMobileMenuOpen"
          >
            <svg
              class="w-6 h-6"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16"
              />
            </svg>
          </button>

          <!-- Breadcrumb Title -->
          <div class="flex items-center gap-2 text-xs font-bold text-slate-400">
            <span>Admin</span>
            <span>/</span>
            <span class="text-slate-800 capitalize">{{
              (route.name || "dashboard").toString().replace("admin-", "")
            }}</span>
          </div>
        </div>

        <!-- Right Header Items -->
        <div class="flex items-center gap-3 sm:gap-4">
          <!-- View Storefront Link -->
          <router-link
            to="/"
            target="_blank"
            class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors border border-slate-200"
          >
            <span>Live Storefront</span>
            <span>↗</span>
          </router-link>

          <!-- Quick Action: New Product -->
          <router-link
            v-if="adminStore.canAccessPath('/admin/products')"
            to="/admin/products"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 transition-colors shadow-xs"
          >
            <span>+ Add Product</span>
          </router-link>

          <!-- Logout Button Mobile -->
          <button
            type="button"
            class="md:hidden p-2 text-slate-500 hover:text-rose-600"
            title="Sign out"
            @click="handleSignOut"
          >
            🚪
          </button>
        </div>
      </header>

      <!-- Page Content Area -->
      <main class="flex-1 p-4 sm:p-8 max-w-7xl w-full mx-auto">
        <router-view />
      </main>
    </div>

    <!-- Mobile Drawer -->
    <div
      v-if="isMobileMenuOpen"
      class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 md:hidden flex"
      @click="isMobileMenuOpen = false"
    >
      <div
        class="w-64 bg-slate-950 text-slate-300 h-full p-4 space-y-4"
        @click.stop
      >
        <div
          class="flex items-center justify-between pb-3 border-b border-slate-800"
        >
          <span class="text-sm font-black text-white">RLG HOBBY OS</span>
          <button
            type="button"
            class="text-slate-400"
            @click="isMobileMenuOpen = false"
          >
            ✕
          </button>
        </div>

        <nav class="space-y-1">
          <router-link
            v-for="item in navItems"
            :key="item.path"
            :to="item.path"
            class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-bold transition-all"
            :class="
              route.path.startsWith(item.path)
                ? 'bg-rose-600 text-white'
                : 'text-slate-400 hover:bg-slate-900'
            "
            @click="isMobileMenuOpen = false"
          >
            <span>{{ item.icon }}</span>
            <span>{{ item.name }}</span>
          </router-link>
        </nav>

        <div class="pt-4 border-t border-slate-800">
          <button
            type="button"
            class="w-full py-2 bg-slate-800 text-rose-400 font-bold text-xs rounded-xl"
            @click="handleSignOut"
          >
            Sign Out
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
