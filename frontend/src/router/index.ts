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

  // When visiting /admin directly, always route to /admin/login first
  if (to.path === "/admin" || to.path === "/admin/") {
    return next({ path: "/admin/login" });
  }

  // Allow unrestricted access to the admin login page
  if (isAdminLogin) {
    return next();
  }

  // Verify stored admin session from localStorage
  let isAdminAuthenticated = false;
  let parsedSession: any = null;
  const rawAdminSession = localStorage.getItem("rlg-admin-session");
  if (
    rawAdminSession &&
    rawAdminSession !== "null" &&
    rawAdminSession !== "undefined"
  ) {
    try {
      parsedSession = JSON.parse(rawAdminSession);
      if (parsedSession && (parsedSession.id || parsedSession.email)) {
        isAdminAuthenticated = true;
      }
    } catch {
      isAdminAuthenticated = false;
    }
  }

  if (isAdminRoute) {
    // If not logged in, redirect to login page
    if (!isAdminAuthenticated) {
      return next({
        path: "/admin/login",
        query: { redirect: to.fullPath },
      });
    }

    // Role-based Access Matrix enforcement on child routes
    if (parsedSession && parsedSession.role !== "super-admin") {
      const allowedCodes: string[] = parsedSession.allowedCodes || [];
      const allowedPaths: string[] = parsedSession.allowedPaths || [];

      // Extract module code from e.g. /admin/settings -> settings
      const match = to.path.match(/^\/admin\/([a-z0-9_-]+)/i);
      const moduleCode = match ? match[1].toLowerCase() : null;

      if (moduleCode && moduleCode !== "dashboard") {
        const hasCodeAccess =
          allowedCodes.length > 0 ? allowedCodes.includes(moduleCode) : true;
        const hasPathAccess =
          allowedPaths.length > 0
            ? allowedPaths.some(
                (p) =>
                  p.toLowerCase() === to.path.toLowerCase() ||
                  to.path.toLowerCase().startsWith(p.toLowerCase() + "/"),
              )
            : true;

        if (!hasCodeAccess && !hasPathAccess) {
          // Block unauthorized module and redirect to permitted dashboard
          return next({ path: "/admin/dashboard" });
        }
      }
    }
  }

  next();
});

export default router;
