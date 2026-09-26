import type { RouteRecordRaw } from "vue-router";

const HomeView = () => import("@/modules/home/HomeView.vue");
const ToyCatalogView = () => import("@/modules/catalog/ToyCatalogView.vue");
const CheckoutView = () => import("@/modules/checkout/CheckoutView.vue");
const WishlistView = () => import("@/modules/wishlist/WishlistView.vue");
const AuthCallbackView = () => import("@/modules/auth/AuthCallbackView.vue");

export const routes: RouteRecordRaw[] = [
  {
    path: "/",
    name: "home",
    component: HomeView,
    meta: {
      title: "Home | RLG Hobby Shop - Pokémon Toys & TCG Collectibles",
    },
  },
  {
    path: "/catalog",
    name: "catalog",
    component: ToyCatalogView,
    meta: {
      title: "All Pokémon Toys, Cards & Gear | RLG Hobby Shop",
    },
  },
  {
    path: "/wishlist",
    name: "wishlist",
    component: WishlistView,
    meta: {
      title: "Trainer Wishlist | RLG Hobby Shop",
    },
  },
  {
    path: "/checkout",
    name: "checkout",
    component: CheckoutView,
    meta: {
      title: "Express Poké-Checkout | RLG Hobby Shop",
    },
  },
  {
    path: "/auth/callback",
    name: "auth-callback",
    component: AuthCallbackView,
    meta: {
      title: "Authenticating... | RLG Hobby Shop",
    },
  },
  // --- Admin Portal Routes ---
  {
    path: "/admin/login",
    name: "admin-login",
    component: () => import("@/modules/admin/AdminLoginView.vue"),
    meta: {
      title: "Admin Staff Login | RLG Hobby Command Center",
      isAdmin: false,
    },
  },
  {
    path: "/admin",
    component: () => import("@/modules/admin/AdminLayout.vue"),
    redirect: "/admin/dashboard",
    meta: {
      isAdmin: true,
    },
    children: [
      {
        path: "dashboard",
        name: "admin-dashboard",
        component: () => import("@/modules/admin/views/AdminDashboardView.vue"),
        meta: { title: "Dashboard & Command Center | RLG Admin" },
      },
      {
        path: "orders",
        name: "admin-orders",
        component: () => import("@/modules/admin/views/AdminOrdersView.vue"),
        meta: { title: "Order Management & Fulfillment | RLG Admin" },
      },
      {
        path: "inventory",
        name: "admin-inventory",
        component: () => import("@/modules/admin/views/AdminInventoryView.vue"),
        meta: { title: "Inventory & Stock Control | RLG Admin" },
      },
      {
        path: "products",
        name: "admin-products",
        component: () =>
          import("@/modules/admin/views/AdminProductUploadView.vue"),
        meta: { title: "Product Catalog & Upload Module | RLG Admin" },
      },
      {
        path: "customers",
        name: "admin-customers",
        component: () => import("@/modules/admin/views/AdminCustomersView.vue"),
        meta: { title: "Customer Management CRM & Inbox | RLG Admin" },
      },
      {
        path: "marketing",
        name: "admin-marketing",
        component: () => import("@/modules/admin/views/AdminMarketingView.vue"),
        meta: { title: "Marketing, Promos & SEO | RLG Admin" },
      },
      {
        path: "cms",
        name: "admin-cms",
        component: () => import("@/modules/admin/views/AdminCmsView.vue"),
        meta: { title: "CMS & Storefront Pages | RLG Admin" },
      },
      {
        path: "analytics",
        name: "admin-analytics",
        component: () => import("@/modules/admin/views/AdminAnalyticsView.vue"),
        meta: { title: "Analytics & Financial Reporting | RLG Admin" },
      },
      {
        path: "settings",
        name: "admin-settings",
        component: () => import("@/modules/admin/views/AdminSettingsView.vue"),
        meta: { title: "Settings, RBAC & Gateways | RLG Admin" },
      },
    ],
  },
  {
    path: "/:pathMatch(.*)*",
    redirect: "/",
  },
];
