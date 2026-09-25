import { createRouter, createWebHistory } from "vue-router";
import { routes } from "./routes";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return savedPosition;
    return { top: 0, behavior: "smooth" };
  },
});

router.beforeEach((to, from, next) => {
  // Update browser document title
  if (to.meta.title && typeof to.meta.title === "string") {
    document.title = to.meta.title;
  }

  // Admin route protection
  const isAdminRoute = to.path.startsWith("/admin");
  const isAdminLogin = to.path === "/admin/login";

  // Verify stored admin session from localStorage
  let isAdminAuthenticated = false;
  const rawAdminSession = localStorage.getItem("rlg-admin-session");
  if (
    rawAdminSession &&
    rawAdminSession !== "null" &&
    rawAdminSession !== "undefined"
  ) {
    try {
      const parsed = JSON.parse(rawAdminSession);
      if (parsed && (parsed.id || parsed.email)) {
        isAdminAuthenticated = true;
      }
    } catch {
      isAdminAuthenticated = false;
    }
  }

  if (isAdminRoute) {
    if (!isAdminAuthenticated && !isAdminLogin) {
      // User is not logged in as admin -> redirect to /admin/login
      const redirectTarget =
        to.fullPath !== "/admin" ? to.fullPath : "/admin/dashboard";
      return next({
        path: "/admin/login",
        query: { redirect: redirectTarget },
      });
    }

    if (isAdminAuthenticated && isAdminLogin) {
      // Already logged in admin visiting login page -> redirect to dashboard
      return next({ path: "/admin/dashboard" });
    }
  }

  next();
});

export default router;
