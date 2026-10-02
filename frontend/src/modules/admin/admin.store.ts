import { defineStore } from "pinia";
import { ref, computed, watch } from "vue";
import { useStorage, StorageSerializers } from "@vueuse/core";
import type {
  AdminRole,
  AdminUser,
  AdminOrder,
  InventoryItem,
  CustomerProfile,
  CustomerInquiry,
  PromoCode,
  AbandonedCart,
  CMSBanner,
  StaffMember,
  StoreSettings,
  OrderStatus,
  StaffModulePermission,
  StaffModulesResponse,
  RefShippingCarrierItem,
  RefOrderStatusItem,
  CustomerMessageItem,
  CustomerReviewItem,
  AdminChatConversation,
  AdminChatMessage,
  DashboardMetrics,
  DashboardData,
  DashboardTopProduct,
  DashboardTopReferrer,
} from "./admin.types";
import { useCmsStore } from "@/modules/cms/cms.store";

const getStoredAdminSession = (): AdminUser | null => {
  if (typeof window === "undefined" || !window.localStorage) return null;
  const raw = localStorage.getItem("rlg-admin-session");
  if (!raw || raw === "null" || raw === "undefined") return null;
  try {
    const parsed = typeof raw === "string" ? JSON.parse(raw) : raw;
    if (parsed && typeof parsed === "object" && (parsed.id || parsed.email)) {
      if (parsed.id === "ADM-1001" && parsed.lastLogin === "Active Now") {
        localStorage.removeItem("rlg-admin-session");
        return null;
      }
      return parsed as AdminUser;
    }
  } catch (e) {
    console.warn("Failed to parse admin session from localStorage", e);
  }
  return null;
};

export const useAdminStore = defineStore("adminStore", () => {
  // Current logged in admin session (persisted synchronously in localStorage)
  const currentAdmin = ref<AdminUser | null>(getStoredAdminSession());

  // Clear legacy mock session that was auto-seeded by default
  if (
    currentAdmin.value &&
    currentAdmin.value.id === "ADM-1001" &&
    currentAdmin.value.lastLogin === "Active Now"
  ) {
    currentAdmin.value = null;
    localStorage.removeItem("rlg-admin-session");
  }

  const isAuthenticated = computed(() => {
    return (
      !!currentAdmin.value &&
      typeof currentAdmin.value === "object" &&
      !!currentAdmin.value.email
    );
  });

  const KNOWN_ROSTER: Record<
    string,
    {
      name: string;
      role: AdminRole;
      roleLabel: string;
      roleId: number;
    }
  > = {
    "admin@rlghobby.com": {
      name: "Admin Chief",
      role: "super-admin",
      roleLabel: "Super Admin (Unrestricted)",
      roleId: 1,
    },
    admin: {
      name: "Admin Chief",
      role: "super-admin",
      roleLabel: "Super Admin (Unrestricted)",
      roleId: 1,
    },
    "russelluisg@gmail.com": {
      name: "Russel Luis Gementiza",
      role: "super-admin",
      roleLabel: "Super Admin (Unrestricted)",
      roleId: 1,
    },
    russelluisg: {
      name: "Russel Luis Gementiza",
      role: "super-admin",
      roleLabel: "Super Admin (Unrestricted)",
      roleId: 1,
    },
    "rowena.ops@rlghobby.com": {
      name: "Rowena Santos",
      role: "manager",
      roleLabel: "Store Manager (Catalog & Operations)",
      roleId: 2,
    },
    "rowena.ops": {
      name: "Rowena Santos",
      role: "manager",
      roleLabel: "Store Manager (Catalog & Operations)",
      roleId: 2,
    },
    "manager@rlghobby.com": {
      name: "Store Manager Demo",
      role: "manager",
      roleLabel: "Store Manager (Catalog & Operations)",
      roleId: 2,
    },
    manager: {
      name: "Store Manager Demo",
      role: "manager",
      roleLabel: "Store Manager (Catalog & Operations)",
      roleId: 2,
    },
    "darwin.pack@rlghobby.com": {
      name: "Darwin Gomez",
      role: "fulfillment",
      roleLabel: "Fulfillment Staff (Packing & Shipping only)",
      roleId: 3,
    },
    "darwin.pack": {
      name: "Darwin Gomez",
      role: "fulfillment",
      roleLabel: "Fulfillment Staff (Packing & Shipping only)",
      roleId: 3,
    },
    "packer@rlghobby.com": {
      name: "Fulfillment Packer Demo",
      role: "fulfillment",
      roleLabel: "Fulfillment Staff (Packing & Shipping only)",
      roleId: 3,
    },
    packer: {
      name: "Fulfillment Packer Demo",
      role: "fulfillment",
      roleLabel: "Fulfillment Staff (Packing & Shipping only)",
      roleId: 3,
    },
  };

  const DEFAULT_ROLE_CODES: Record<string, string[]> = {
    "super-admin": [
      "dashboard",
      "orders",
      "inventory",
      "products",
      "customers",
      "marketing",
      "cms",
      "analytics",
      "settings",
    ],
    manager: [
      "dashboard",
      "orders",
      "inventory",
      "products",
      "customers",
      "marketing",
      "analytics",
    ],
    fulfillment: ["dashboard", "orders", "inventory", "products"],
  };

  // Staff Role Access Control Matrix & Allowed Modules
  const DEFAULT_ROLE_PATHS: Record<string, string[]> = {
    "super-admin": [
      "/admin/dashboard",
      "/admin/orders",
      "/admin/inventory",
      "/admin/products",
      "/admin/customers",
      "/admin/marketing",
      "/admin/cms",
      "/admin/analytics",
      "/admin/settings",
    ],
    manager: [
      "/admin/dashboard",
      "/admin/orders",
      "/admin/inventory",
      "/admin/products",
      "/admin/customers",
      "/admin/marketing",
      "/admin/analytics",
    ],
    fulfillment: [
      "/admin/dashboard",
      "/admin/orders",
      "/admin/inventory",
      "/admin/products",
    ],
  };

  const allowedModulePaths = ref<string[]>(
    currentAdmin.value?.allowedPaths ||
      (currentAdmin.value?.role
        ? DEFAULT_ROLE_PATHS[currentAdmin.value.role] || []
        : []),
  );
  const allowedModuleCodes = ref<string[]>(
    currentAdmin.value?.allowedCodes ||
      (currentAdmin.value?.role
        ? DEFAULT_ROLE_CODES[currentAdmin.value.role] || []
        : []),
  );

  // Keep permissions synced when currentAdmin changes or hydrates
  watch(
    () => currentAdmin.value,
    (admin) => {
      if (admin && typeof admin === "object") {
        if (admin.allowedPaths && admin.allowedPaths.length > 0) {
          allowedModulePaths.value = admin.allowedPaths;
        } else if (admin.role && DEFAULT_ROLE_PATHS[admin.role]) {
          allowedModulePaths.value = DEFAULT_ROLE_PATHS[admin.role];
        }
        if (admin.allowedCodes && admin.allowedCodes.length > 0) {
          allowedModuleCodes.value = admin.allowedCodes;
        } else if (admin.role && DEFAULT_ROLE_CODES[admin.role]) {
          allowedModuleCodes.value = DEFAULT_ROLE_CODES[admin.role];
        }
      }
    },
    { immediate: true },
  );

  const staffPermissions = ref<StaffModulePermission[]>([]);
  const isPermissionsLoaded = ref(false);

  const fetchStaffPermissions = async (
    roleOverride?: string,
    emailOverride?: string,
  ): Promise<StaffModulesResponse | null> => {
    const activeRole =
      roleOverride || currentAdmin.value?.role || "super-admin";
    const activeEmail = emailOverride || currentAdmin.value?.email || "";

    try {
      const params = new URLSearchParams();
      if (activeRole) params.append("role", activeRole);
      if (activeEmail) params.append("email", activeEmail);

      const res = await fetch(`/api/staff-modules?${params.toString()}`);
      if (res.ok) {
        const data: StaffModulesResponse = await res.json();
        if (data && data.success) {
          allowedModulePaths.value = data.allowed_paths || [];
          allowedModuleCodes.value = data.allowed_codes || [];
          staffPermissions.value = data.modules || [];
          isPermissionsLoaded.value = true;

          if (currentAdmin.value) {
            currentAdmin.value.allowedPaths = allowedModulePaths.value;
            currentAdmin.value.allowedCodes = allowedModuleCodes.value;
          }
          return data;
        }
      }
    } catch (err) {
      console.warn(
        "Could not fetch staff modules API, using fallback matrix:",
        err,
      );
    }

    // Offline / fallback defaults based on role
    const fallbackPaths =
      DEFAULT_ROLE_PATHS[activeRole.toLowerCase()] ||
      DEFAULT_ROLE_PATHS["super-admin"];
    const fallbackCodes =
      DEFAULT_ROLE_CODES[activeRole.toLowerCase()] ||
      DEFAULT_ROLE_CODES["super-admin"];
    allowedModulePaths.value = fallbackPaths;
    allowedModuleCodes.value = fallbackCodes;
    isPermissionsLoaded.value = true;
    return null;
  };

  const canAccess = (code?: string, path?: string): boolean => {
    if (!currentAdmin.value) return false;
    // Super-admin always has full access
    if (currentAdmin.value.role === "super-admin") return true;

    // Check module code if provided
    if (code && allowedModuleCodes.value.length > 0) {
      return allowedModuleCodes.value.includes(code.toLowerCase());
    }

    // Check path if provided
    if (path && allowedModulePaths.value.length > 0) {
      const normalizedPath = path.toLowerCase().replace(/\/$/, "");
      const matched = allowedModulePaths.value.some((p) => {
        const normalizedP = p.toLowerCase().replace(/\/$/, "");
        return (
          normalizedPath === normalizedP ||
          normalizedPath.startsWith(normalizedP + "/")
        );
      });
      if (matched) return true;
    }

    // Fallback based on stored code
    if (code) {
      const fallbackCodes =
        DEFAULT_ROLE_CODES[currentAdmin.value.role] ||
        DEFAULT_ROLE_CODES["super-admin"];
      return fallbackCodes.includes(code.toLowerCase());
    }

    return false;
  };

  const canAccessPath = (path: string): boolean => {
    if (!currentAdmin.value) return false;
    if (currentAdmin.value.role === "super-admin") return true;

    // Extract module code from /admin/:module
    const match = path.match(/^\/admin\/([a-z0-9_-]+)/i);
    const moduleCode = match ? match[1].toLowerCase() : null;

    if (moduleCode) {
      return canAccess(moduleCode, path);
    }

    return canAccess(undefined, path);
  };

  const canAccessModule = (code: string): boolean => {
    return canAccess(code);
  };

  // Eagerly hydrate permissions if an admin session is already active
  if (currentAdmin.value) {
    fetchStaffPermissions(currentAdmin.value.role, currentAdmin.value.email);
  }

  // Dashboard timeframe filter
  const dashboardTimeframe = ref<"today" | "week" | "month">("week");

  // Orders
  const orders = useStorage<AdminOrder[]>("rlg-admin-orders", [
    {
      id: "ORD-9842",
      customerName: "Marcus Tan",
      customerEmail: "marcus.tan@example.com",
      customerPhone: "+63 917 555 1234",
      shippingAddress: "42 Orchid St, Unit 3B, New Manila",
      city: "Quezon City",
      postalCode: "1112",
      items: [
        {
          id: "poke-tcg-1",
          name: "Pokémon TCG: Scarlet & Violet 151 Elite Trainer Box",
          sku: "TCG-PKM-151-ETB",
          price: 2799,
          quantity: 1,
          imageUrl:
            "https://images.unsplash.com/photo-1628155930542-3c7a64e2c833?w=600&auto=format&fit=crop&q=80",
        },
        {
          id: "op-tcg-1",
          name: "One Piece Card Game: OP-05 Booster Box",
          sku: "TCG-OP-05-BOX",
          price: 4750,
          quantity: 1,
          imageUrl:
            "https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=600&auto=format&fit=crop&q=80",
        },
      ],
      total: 7549,
      status: "Processing",
      paymentMethod: "GCash",
      packingSlipPrinted: true,
      invoiceId: "INV-2026-001",
      createdAt: "2026-09-24 09:15 AM",
      notes: "Please pack with corner bubble armor protectors.",
    },
    {
      id: "ORD-9843",
      customerName: "Elena Reyes",
      customerEmail: "elena.reyes@example.com",
      customerPhone: "+63 918 888 4567",
      shippingAddress: "15 Katipunan Ave, Loyola Heights",
      city: "Quezon City",
      postalCode: "1108",
      items: [
        {
          id: "poke-fig-4",
          name: "Monkey D. Luffy Gear 5 Sun God Nika Battle Figure",
          sku: "FIG-OP-LUFFY-G5",
          price: 2688,
          quantity: 1,
          imageUrl:
            "https://images.unsplash.com/photo-1594787318286-3d835c1d207f?w=600&auto=format&fit=crop&q=80",
        },
      ],
      total: 2688,
      status: "Shipped",
      trackingNumber: "PH-JT-894729104",
      carrier: "J&T Express Courier",
      paymentMethod: "Maya",
      packingSlipPrinted: true,
      invoiceId: "INV-2026-002",
      createdAt: "2026-09-23 03:40 PM",
    },
    {
      id: "ORD-9844",
      customerName: "David Cruz",
      customerEmail: "david.cruz@example.com",
      customerPhone: "+63 920 333 9988",
      shippingAddress: "88 Ayala Avenue, Tower One, Suite 12A",
      city: "Makati City",
      postalCode: "1226",
      items: [
        {
          id: "holo-tcg-1",
          name: "Hololive Official Card Game: Blooming Radiance Booster Box",
          sku: "TCG-HOLO-BP01",
          price: 3950,
          quantity: 2,
          imageUrl:
            "https://images.unsplash.com/photo-1563089145-599997674d42?w=600&auto=format&fit=crop&q=80",
        },
      ],
      total: 7900,
      status: "Pending",
      paymentMethod: "Cash on Delivery",
      packingSlipPrinted: false,
      invoiceId: "INV-2026-003",
      createdAt: "2026-09-24 11:05 AM",
      notes: "Call before delivery.",
    },
    {
      id: "ORD-9845",
      customerName: "Chloe Mendoza",
      customerEmail: "chloe.m@example.com",
      customerPhone: "+63 905 111 2233",
      shippingAddress: "22 Alabang-Zapote Rd",
      city: "Muntinlupa City",
      postalCode: "1780",
      items: [
        {
          id: "poke-tcg-2",
          name: "Pokémon TCG: Charizard ex Super-Premium Collection Box",
          sku: "TCG-PKM-CHZ-EX",
          price: 4499,
          quantity: 1,
          imageUrl:
            "https://images.unsplash.com/photo-1613771404784-3a5686aa2be3?w=600&auto=format&fit=crop&q=80",
        },
      ],
      total: 4499,
      status: "Delivered",
      trackingNumber: "PH-LBC-9912048",
      carrier: "LBC Express",
      paymentMethod: "Credit Card",
      packingSlipPrinted: true,
      invoiceId: "INV-2026-004",
      createdAt: "2026-09-21 02:10 PM",
    },
    {
      id: "ORD-9846",
      customerName: "Kenji Sato",
      customerEmail: "kenji.sato@example.com",
      customerPhone: "+63 916 444 8877",
      shippingAddress: "7 Fort Victoria, BGC",
      city: "Taguig City",
      postalCode: "1634",
      items: [
        {
          id: "dm-tcg-1",
          name: "Duel Masters: Abyss Revolution 24-Pack Booster Box",
          sku: "TCG-DM-DM23-RP1",
          price: 3600,
          quantity: 1,
          imageUrl:
            "https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?w=600&auto=format&fit=crop&q=80",
        },
      ],
      total: 3600,
      status: "Canceled",
      paymentMethod: "GCash",
      refundStatus: "Full",
      refundAmount: 3600,
      packingSlipPrinted: false,
      invoiceId: "INV-2026-005",
      createdAt: "2026-09-22 08:30 AM",
      notes: "Customer requested cancellation prior to dispatch.",
    },
  ]);

  // Inventory & Stock (Loaded directly from backend API /api/products)
  if (typeof localStorage !== "undefined") {
    localStorage.removeItem("rlg-admin-inventory");
  }
  const inventory = ref<InventoryItem[]>([]);

  // Customers
  const customers = useStorage<CustomerProfile[]>("rlg-admin-customers", [
    {
      id: "CUST-101",
      name: "Marcus Tan",
      email: "marcus.tan@example.com",
      phone: "+63 917 555 1234",
      city: "Quezon City",
      totalOrders: 9,
      lifetimeValue: 48500,
      segment: "VIP",
      lastOrderDate: "2026-09-24",
      notes: "Avid sealed Pokémon & One Piece booster box collector.",
    },
    {
      id: "CUST-102",
      name: "Elena Reyes",
      email: "elena.reyes@example.com",
      phone: "+63 918 888 4567",
      city: "Quezon City",
      totalOrders: 4,
      lifetimeValue: 12900,
      segment: "Regular",
      lastOrderDate: "2026-09-23",
    },
    {
      id: "CUST-103",
      name: "Metro Card Haven Inc.",
      email: "procurement@metrocardhaven.ph",
      phone: "+63 922 777 9900",
      city: "Pasig City",
      totalOrders: 18,
      lifetimeValue: 245000,
      segment: "Wholesale",
      lastOrderDate: "2026-09-18",
      notes:
        "Wholesale card shop in Megamall. Inquiring on case break pricing.",
    },
    {
      id: "CUST-104",
      name: "Rafael Villanueva",
      email: "rafael.v@example.com",
      phone: "+63 908 444 3322",
      city: "Cebu City",
      totalOrders: 1,
      lifetimeValue: 1890,
      segment: "Inactive",
      lastOrderDate: "2026-04-12",
    },
  ]);

  // Customer Messages (backed by customer_message table)
  const customerMessages = ref<CustomerMessageItem[]>([]);
  const isLoadingMessages = ref(false);

  // Customer Reviews (backed by customer_review table)
  const customerReviews = ref<CustomerReviewItem[]>([]);
  const isLoadingReviews = ref(false);

  // Inquiries & Centralized Inbox (legacy fallback)
  const inquiries = useStorage<CustomerInquiry[]>("rlg-admin-inquiries", [
    {
      id: "INQ-301",
      customerName: "Marcus Tan",
      email: "marcus.tan@example.com",
      type: "inquiry",
      subject: "Pre-order allocation for One Piece OP-09 The Four Emperors?",
      message:
        "Hi team, will you be taking pre-orders for OP-09 cases? How many booster boxes per customer is the limit?",
      date: "2026-09-24 10:30 AM",
      status: "unread",
    },
    {
      id: "INQ-302",
      customerName: "Carlos Dizon",
      email: "carlos.dizon@example.com",
      type: "dispute",
      subject: "Courier delay on parcel tracking #PH-JT-894729104",
      message:
        "My package tracking shows pending pickup at warehouse for 48 hours. Can you expedite with J&T?",
      date: "2026-09-23 04:15 PM",
      status: "in-progress",
    },
    {
      id: "INQ-303",
      customerName: "Sarah Jenkins",
      email: "sarah.j@example.com",
      type: "review",
      subject: 'Review awaiting moderation: "RG RX-78-2 Gundam Ver.2.0"',
      message:
        '5 Stars - "Flawless runners, perfectly protected with bubble film and double box. Will order again!"',
      date: "2026-09-22 01:20 PM",
      status: "resolved",
    },
  ]);

  // Marketing & Promo Codes
  const promoCodes = useStorage<PromoCode[]>("rlg-admin-promos", [
    {
      id: "PROMO-1",
      code: "HOBBY10",
      type: "percentage",
      value: 10,
      usageCount: 142,
      usageLimit: 500,
      expiryDate: "2026-12-31",
      isActive: true,
    },
    {
      id: "PROMO-2",
      code: "GUNPLA20",
      type: "percentage",
      value: 20,
      usageCount: 88,
      usageLimit: 200,
      expiryDate: "2026-10-31",
      isActive: true,
    },
    {
      id: "PROMO-3",
      code: "FREESHIPPH",
      type: "shipping",
      value: 0,
      usageCount: 65,
      usageLimit: 300,
      expiryDate: "2026-11-15",
      isActive: true,
    },
  ]);

  // Abandoned Carts
  const abandonedCarts = useStorage<AbandonedCart[]>(
    "rlg-admin-abandoned-carts",
    [
      {
        id: "AB-881",
        customerEmail: "joshua.hobby@gmail.com",
        customerName: "Joshua Aquino",
        itemsCount: 2,
        totalValue: 5498,
        lastActive: "2 hours ago",
        recovered: false,
        reminderSent: false,
      },
      {
        id: "AB-882",
        customerEmail: "mika.otaku@yahoo.com",
        customerName: "Mika Fernandez",
        itemsCount: 1,
        totalValue: 2688,
        lastActive: "6 hours ago",
        recovered: false,
        reminderSent: true,
      },
    ],
  );

  // CMS Banners (Delegated to centralized useCmsStore with live backend API sync)
  const cmsStore = useCmsStore();
  const cmsBanners = computed({
    get: () => cmsStore.banners,
    set: (val: CMSBanner[]) => {
      cmsStore.banners = val;
    },
  });

  // Staff & RBAC (Fetched directly from backend API /api/staff)
  if (typeof localStorage !== "undefined") {
    localStorage.removeItem("rlg-admin-staff");
  }
  const staffMembers = ref<StaffMember[]>([]);

  // Store Settings
  const settings = useStorage<StoreSettings>("rlg-admin-settings", {
    storeName: "RLG Hobby Shop",
    currency: "PHP",
    currencySymbol: "₱",
    taxRatePercent: 12,
    flatShippingRate: 150,
    freeShippingThreshold: 2500,
    gateways: {
      gcash: true,
      maya: true,
      stripe: true,
      paypal: true,
      cod: true,
    },
  });

  // Live Dashboard Telemetry State (Stored Procedure API)
  const dashboardData = ref<DashboardData | null>(null);
  const isLoadingDashboard = ref(false);

  const fetchDashboardData = async (timeframe = dashboardTimeframe.value) => {
    isLoadingDashboard.value = true;
    try {
      const res = await fetch(`/api/admin/dashboard?timeframe=${timeframe}`);
      if (res.ok) {
        const json = await res.json();
        if (json.success && json.data) {
          dashboardData.value = json.data;
        }
      }
    } catch (err) {
      console.warn("[Admin Store] Failed to fetch dashboard telemetry", err);
    } finally {
      isLoadingDashboard.value = false;
    }
  };

  // Getters / Computed Metrics for Dashboard
  const metrics = computed(() => {
    if (dashboardData.value?.metrics) {
      const dm = dashboardData.value.metrics;
      return {
        totalRevenue: dm.total_revenue,
        totalOrdersCount: dm.total_orders,
        averageOrderValue: dm.average_order_value,
        totalAddedCart: dm.total_added_cart,
        totalCartUniqueItems: dm.total_cart_unique_items,
        totalAddedFavourite: dm.total_added_favourite,
        pendingOrdersCount: dm.pending_orders_count,
        lowStockCount: dm.low_stock_count,
        unreadInquiriesCount: dm.unread_inquiries_count,
        activeVisitorsToday: dm.active_visitors_today,
        checkoutActiveVisitors: dm.checkout_active_visitors ?? 0,
        telemetrySource: dm.telemetry_source ?? 'local_sessions',
        telemetryLabel: dm.telemetry_label ?? 'Live Storefront Sessions',
        telemetryWindow: dm.telemetry_window ?? 'Rolling 30m window',
        isGa4Configured: !!dm.ga4_configured,
        revenueGrowthPct: dm.revenue_growth_pct,
        orderVelocityPct: dm.order_velocity_pct,
        aovBundleLift: dm.aov_bundle_lift,
        grossSales: dm.total_revenue * 1.12,
        netSales: dm.total_revenue,
        totalTax: dm.total_revenue * 0.12,
      };
    }

    const totalSales = orders.value
      .filter((o) => o.status !== "Canceled")
      .reduce((sum, o) => sum + o.total, 0);
    const pendingOrdersCount = orders.value.filter(
      (o) => o.status === "Pending",
    ).length;
    const lowStockCount = inventory.value.filter(
      (i) => i.stock <= i.lowStockThreshold,
    ).length;
    const unreadInquiriesCount =
      customerMessages.value.length > 0
        ? customerMessages.value.filter((m) => m.status === "ongoing").length
        : inquiries.value.filter((i) => i.status === "unread").length;

    return {
      totalRevenue: totalSales,
      totalOrdersCount: orders.value.length,
      averageOrderValue: Math.round(totalSales / (orders.value.length || 1)),
      totalAddedCart: 1,
      totalCartUniqueItems: 1,
      totalAddedFavourite: 1,
      pendingOrdersCount,
      lowStockCount,
      unreadInquiriesCount,
      activeVisitorsToday: 1,
      checkoutActiveVisitors: 0,
      telemetrySource: 'local_sessions',
      telemetryLabel: 'Live Storefront Sessions',
      telemetryWindow: 'Rolling 30m window',
      isGa4Configured: false,
      revenueGrowthPct: 18.4,
      orderVelocityPct: 12.1,
      aovBundleLift: 420.00,
      grossSales: totalSales * 1.12,
      netSales: totalSales,
      totalTax: totalSales * 0.12,
    };
  });

  // Top performers
  const topProducts = computed(() => {
    if (dashboardData.value?.top_products && dashboardData.value.top_products.length > 0) {
      return dashboardData.value.top_products;
    }
    return [
      {
        name: "One Piece OP-05 Awakening of the New Era",
        unitsSold: 94,
        revenue: 446500,
        share: "38%",
      },
      {
        name: "Pokémon TCG 151 Elite Trainer Box",
        unitsSold: 78,
        revenue: 218322,
        share: "24%",
      },
      {
        name: "Hololive OCG Blooming Radiance Booster Box",
        unitsSold: 56,
        revenue: 221200,
        share: "19%",
      },
      {
        name: "RG 1/144 RX-78-2 Gundam Ver.2.0 Kit",
        unitsSold: 42,
        revenue: 107100,
        share: "12%",
      },
    ];
  });

  const topReferrers = computed(() => [
    {
      source: "Facebook TCG & Gunpla Philippines Groups",
      visitors: 6420,
      conversionRate: "4.8%",
    },
    {
      source: 'Google Organic Search ("RLG Hobby Shop PH")',
      visitors: 4980,
      conversionRate: "6.2%",
    },
    {
      source: "YouTube Hobbyist Unboxing & Card Reviews",
      visitors: 2840,
      conversionRate: "5.1%",
    },
    {
      source: "Direct & Bookmark Collectors",
      visitors: 2110,
      conversionRate: "8.4%",
    },
  ]);

  // Authentication Actions
  const login = async (email: string, password?: string): Promise<boolean> => {
    const trimmedEmail = email.trim().toLowerCase();

    try {
      const res = await fetch("/api/auth/staff-login", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({
          username: trimmedEmail,
          email: trimmedEmail,
          password,
        }),
      });

      const data = await res.json().catch(() => null);

      if (!res.ok || !data?.success) {
        const errorMsg =
          data?.message ||
          "Access denied: Only authorized users registered in the staff roster can log in.";
        throw new Error(errorMsg);
      }

      const staff = data.staff;
      let mappedRole: AdminRole = "super-admin";
      if (
        staff.role_id === 2 ||
        (staff.role_name && staff.role_name.toLowerCase().includes("manager"))
      ) {
        mappedRole = "manager";
      } else if (
        staff.role_id === 3 ||
        (staff.role_name &&
          staff.role_name.toLowerCase().includes("fulfillment"))
      ) {
        mappedRole = "fulfillment";
      }

      const sessionData: AdminUser = {
        id: "STF-" + staff.id,
        name: staff.name,
        email: staff.email,
        role: mappedRole,
        roleLabel: staff.role_label || staff.role_name,
        token: data.token,
        lastLogin: new Date().toLocaleTimeString(),
        allowedCodes: staff.allowed_codes || [],
        allowedPaths: staff.allowed_paths || [],
      };

      currentAdmin.value = sessionData;
      try {
        localStorage.setItem("rlg-admin-session", JSON.stringify(sessionData));
      } catch (e) {
        console.warn("Could not write session to localStorage", e);
      }

      allowedModuleCodes.value = staff.allowed_codes || [];
      allowedModulePaths.value = staff.allowed_paths || [];
      if (staff.permissions) {
        staffPermissions.value = Object.entries(staff.permissions).map(
          ([code, perm]: [string, any]) => ({
            id: 0,
            matrix_id: 0,
            code,
            name: code,
            path: `/admin/${code}`,
            can_read: !!perm.can_read,
            can_create: !!perm.can_create,
            can_update: !!perm.can_update,
            can_delete: !!perm.can_delete,
          }),
        );
      }
      isPermissionsLoaded.value = true;
      return true;
    } catch (err: any) {
      // If error message came from backend response, bubble it up directly!
      if (
        err?.message &&
        (err.message.toLowerCase().includes("access denied") ||
          err.message.toLowerCase().includes("password") ||
          err.message.toLowerCase().includes("credentials") ||
          err.message.toLowerCase().includes("deactivated"))
      ) {
        throw err;
      }

      // Check fallback known roster if backend is unreachable
      const rosterEntry = KNOWN_ROSTER[trimmedEmail];
      if (!rosterEntry) {
        throw new Error(
          "Access denied: Only authorized users registered in the staff roster can log in.",
        );
      }

      // In offline mode with valid staff roster email:
      const fallbackSession: AdminUser = {
        id: "STF-" + rosterEntry.roleId,
        name: rosterEntry.name,
        email: trimmedEmail,
        role: rosterEntry.role,
        roleLabel: rosterEntry.roleLabel,
        lastLogin: new Date().toLocaleTimeString(),
        allowedCodes: DEFAULT_ROLE_CODES[rosterEntry.role],
        allowedPaths: DEFAULT_ROLE_PATHS[rosterEntry.role],
      };
      currentAdmin.value = fallbackSession;
      try {
        localStorage.setItem(
          "rlg-admin-session",
          JSON.stringify(fallbackSession),
        );
      } catch (e) {
        console.warn("Could not write session to localStorage", e);
      }

      allowedModuleCodes.value = DEFAULT_ROLE_CODES[rosterEntry.role];
      allowedModulePaths.value = DEFAULT_ROLE_PATHS[rosterEntry.role];
      isPermissionsLoaded.value = true;
      return true;
    }
  };

  const logout = () => {
    currentAdmin.value = null;
    allowedModulePaths.value = [];
    allowedModuleCodes.value = [];
    staffPermissions.value = [];
    isPermissionsLoaded.value = false;
    localStorage.removeItem("rlg-admin-session");
  };

  // Order Reference Data & Database Sync
  const shippingCarriers = ref<RefShippingCarrierItem[]>([]);
  const orderStatuses = ref<RefOrderStatusItem[]>([]);
  const isLoadingOrders = ref(false);

  const fetchShippingCarriers = async () => {
    try {
      const res = await fetch("/api/ref-shipping-carriers");
      if (res.ok) {
        shippingCarriers.value = await res.json();
      }
    } catch (e) {
      console.warn("Could not load shipping carriers from API", e);
    }
  };

  const fetchOrderStatuses = async () => {
    try {
      const res = await fetch("/api/ref-order-statuses");
      if (res.ok) {
        orderStatuses.value = await res.json();
      }
    } catch (e) {
      console.warn("Could not load order statuses from API", e);
    }
  };

  const mapBackendOrderToAdminOrder = (bo: any): AdminOrder => {
    const rawStatus = bo.status?.label || bo.status?.name || "Processing";
    let normalizedStatus: OrderStatus = "Processing";
    const lower = rawStatus.toLowerCase();
    if (lower === "pending") normalizedStatus = "Pending";
    else if (lower === "processing") normalizedStatus = "Processing";
    else if (lower === "shipped") normalizedStatus = "Shipped";
    else if (lower === "delivered") normalizedStatus = "Delivered";
    else if (lower === "accepted") normalizedStatus = "Accepted";
    else if (lower === "cancel" || lower === "canceled")
      normalizedStatus = "Canceled";
    else if (lower === "refund" || lower === "refunded")
      normalizedStatus = "Refunded";
    else if (lower === "return" || lower === "returned")
      normalizedStatus = "Returned";

    return {
      id: bo.order_number,
      backendId: bo.id,
      customerName: bo.customer_name,
      customerEmail: bo.customer_email || "",
      customerPhone: bo.customer_phone || "",
      shippingAddress: bo.shipping_address || "",
      city: bo.city || "",
      postalCode: bo.postal_code || "",
      items: (bo.items || []).map((it: any) => {
        const itemPrice = Number(it.price) || 0;
        const itemQty = Number(it.quantity) || 1;
        const itemSubtotal = Number(it.subtotal) || itemPrice * itemQty;
        return {
          id: String(it.id || it.sku || Math.random()),
          productId: it.product_id ? Number(it.product_id) : undefined,
          name: it.product_name,
          sku: it.sku || "",
          price: itemPrice,
          quantity: itemQty,
          subtotal: itemSubtotal,
          imageUrl:
            it.image_url ||
            it.product?.image_url ||
            "https://images.unsplash.com/photo-1628155930542-3c7a64e2c833?w=600&auto=format&fit=crop&q=80",
          category:
            it.product?.category?.name ||
            it.product?.primary_category ||
            undefined,
          brand: it.product?.brand?.name || it.product?.brand || undefined,
          condition:
            it.product?.condition?.name || it.product?.condition || undefined,
        };
      }),
      total: Number(bo.total_amount) || 0,
      status: normalizedStatus,
      statusLabel: bo.status?.label || normalizedStatus,
      statusBadgeColor: bo.status?.badge_color || undefined,
      paymentMethod: bo.payment_method || "GCash",
      paymentStatus: bo.payment_status || "Paid",
      trackingNumber: bo.fulfillment?.tracking_number || undefined,
      carrier:
        bo.fulfillment?.carrier?.name ||
        bo.fulfillment?.carrier?.short_name ||
        undefined,
      carrierId: bo.fulfillment?.ref_shipping_carrier_id || undefined,
      carrierTrackingUrl:
        bo.fulfillment?.carrier?.tracking_url_template || undefined,
      packingSlipPrinted: Boolean(
        bo.packing_slip_printed || bo.fulfillment?.packing_slip_printed,
      ),
      refundStatus: (bo.refund_status as any) || "None",
      refundAmount: Number(bo.refund_amount) || 0,
      invoiceId: bo.invoice_id || "INV-" + bo.order_number,
      createdAt: bo.order_date
        ? new Date(bo.order_date).toLocaleString("en-US", {
            dateStyle: "medium",
            timeStyle: "short",
          })
        : new Date(bo.created_at).toLocaleString("en-US", {
            dateStyle: "medium",
            timeStyle: "short",
          }),
      rawOrderDate: bo.order_date || bo.created_at || undefined,
      notes: bo.notes || undefined,
      customerProfile: bo.customer_profile || undefined,
    };
  };

  const fetchOrders = async () => {
    isLoadingOrders.value = true;
    try {
      const headers: Record<string, string> = {};
      if (currentAdmin.value?.token) {
        headers["Authorization"] = `Bearer ${currentAdmin.value.token}`;
      }
      const res = await fetch("/api/customer-orders", { headers });
      if (res.ok) {
        const json = await res.json();
        if (json.success && Array.isArray(json.data) && json.data.length > 0) {
          orders.value = json.data.map(mapBackendOrderToAdminOrder);
        }
      }
    } catch (e) {
      console.warn(
        "Could not fetch orders from API, using fallback store orders",
        e,
      );
    } finally {
      isLoadingOrders.value = false;
    }
  };

  // Order Management Actions
  const updateOrderStatus = async (orderId: string, newStatus: OrderStatus) => {
    const order = orders.value.find((o) => o.id === orderId);
    if (order) {
      order.status = newStatus;
    }

    try {
      const targetId = order?.backendId || orderId;
      await fetch(`/api/customer-orders/${targetId}/status`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ status_name: newStatus.toLowerCase() }),
      });
    } catch (e) {
      console.warn("Could not sync order status to backend:", e);
    }
  };

  const attachTracking = async (
    orderId: string,
    trackingNumber: string,
    carrierNameOrId: string | number,
    carrierIdInput?: number,
  ) => {
    const order = orders.value.find((o) => o.id === orderId);
    let resolvedCarrierId = carrierIdInput;
    let resolvedCarrierName = String(carrierNameOrId);

    if (typeof carrierNameOrId === "number") {
      resolvedCarrierId = carrierNameOrId;
      const c = shippingCarriers.value.find((x) => x.id === carrierNameOrId);
      if (c) resolvedCarrierName = c.name;
    } else if (!resolvedCarrierId) {
      const c = shippingCarriers.value.find(
        (x) =>
          x.name.toLowerCase().includes(carrierNameOrId.toLowerCase()) ||
          (x.short_name &&
            x.short_name.toLowerCase().includes(carrierNameOrId.toLowerCase())),
      );
      if (c) {
        resolvedCarrierId = c.id;
        resolvedCarrierName = c.name;
      }
    }

    // Default to first carrier if still unresolved
    if (!resolvedCarrierId && shippingCarriers.value.length > 0) {
      resolvedCarrierId = shippingCarriers.value[0].id;
      resolvedCarrierName = shippingCarriers.value[0].name;
    }

    if (order) {
      order.trackingNumber = trackingNumber;
      order.carrier = resolvedCarrierName;
      order.status = "Shipped";
      order.packingSlipPrinted = true;
    }

    try {
      const targetId = order?.backendId || orderId;
      if (resolvedCarrierId) {
        await fetch(`/api/customer-orders/${targetId}/fulfillment`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            ref_shipping_carrier_id: resolvedCarrierId,
            tracking_number: trackingNumber,
            mark_shipped: true,
            packing_slip_printed: true,
          }),
        });
      }
    } catch (e) {
      console.warn("Could not sync fulfillment to backend:", e);
    }
  };

  const markPackingSlipPrinted = async (orderId: string) => {
    const order = orders.value.find((o) => o.id === orderId);
    if (order) {
      order.packingSlipPrinted = true;
    }

    try {
      const targetId = order?.backendId || orderId;
      await fetch(`/api/customer-orders/${targetId}/print-packing-slip`, {
        method: "POST",
      });
    } catch (e) {
      console.warn("Could not sync packing slip printed to backend:", e);
    }
  };

  const processRefund = async (
    orderId: string,
    amount: number,
    isFull: boolean,
  ) => {
    const order = orders.value.find((o) => o.id === orderId);
    if (order) {
      order.refundStatus = isFull ? "Full" : "Partial";
      order.refundAmount = amount;
      if (isFull) {
        order.status = "Canceled";
      }
    }

    try {
      const targetId = order?.backendId || orderId;
      await fetch(`/api/customer-orders/${targetId}/refund`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          refund_amount: amount,
          is_full_refund: isFull,
        }),
      });
    } catch (e) {
      console.warn("Could not sync refund to backend:", e);
    }
  };

  // Inventory Actions
  const isLoadingInventory = ref(false);

  const fetchInventory = async () => {
    isLoadingInventory.value = true;
    try {
      const res = await fetch("/api/products?per_page=1000");
      if (res.ok) {
        const json = await res.json();
        if (json.success && Array.isArray(json.data)) {
          inventory.value = json.data.map((p: any) => ({
            id: String(p.id),
            sku: p.sku || `PRD-${p.id}`,
            barcode: p.barcode || (p.sku ? `BC-${p.sku}` : `BC-${p.id}`),
            name: p.name,
            category: p.category?.desc || "General",
            condition: p.condition?.desc || "Brandnew",
            stock: Number(p.stock) || 0,
            lowStockThreshold: 5,
            costPrice:
              Number(p.cost_price || (Number(p.price) * 0.7).toFixed(2)) || 0,
            sellingPrice: Number(p.price) || 0,
            vendor: p.brand?.name || "Various",
            leadTimeDays: 7,
            variants: [],
          }));
        }
      }
    } catch (e) {
      console.error("Failed to load inventory products from backend:", e);
    } finally {
      isLoadingInventory.value = false;
    }
  };

  const updateStock = async (itemId: string, newStock: number) => {
    const item = inventory.value.find((i) => i.id === itemId);
    if (item) {
      item.stock = newStock;
    }
    const isDbProduct = !isNaN(Number(itemId));
    if (isDbProduct) {
      try {
        await fetch(`/api/products/${itemId}`, {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },
          body: JSON.stringify({ stock: newStock }),
        });
      } catch (e) {
        console.error("Failed to persist stock update to backend:", e);
      }
    }
  };

  const addInventoryItem = (newItem: Omit<InventoryItem, "id">) => {
    const id = "inv-" + (inventory.value.length + 1);
    inventory.value.unshift({ id, ...newItem });
  };

  // Promo Engine Actions
  const addPromoCode = (promo: Omit<PromoCode, "id" | "usageCount">) => {
    promoCodes.value.unshift({
      id: "PROMO-" + (promoCodes.value.length + 1),
      usageCount: 0,
      ...promo,
    });
  };

  const togglePromoCode = (id: string) => {
    const promo = promoCodes.value.find((p) => p.id === id);
    if (promo) {
      promo.isActive = !promo.isActive;
    }
  };

  const sendAbandonedCartReminder = (cartId: string) => {
    const cart = abandonedCarts.value.find((c) => c.id === cartId);
    if (cart) {
      cart.reminderSent = true;
    }
  };

  // Inquiry Actions
  const markInquiryStatus = (
    id: string,
    status: "unread" | "in-progress" | "resolved",
  ) => {
    const inq = inquiries.value.find((i) => i.id === id);
    if (inq) {
      inq.status = status;
    }
  };

  const fetchCustomerMessages = async () => {
    isLoadingMessages.value = true;
    try {
      const headers: Record<string, string> = {};
      if (currentAdmin.value?.token) {
        headers["Authorization"] = `Bearer ${currentAdmin.value.token}`;
      }
      const res = await fetch("/api/customer-messages", { headers });
      if (res.ok) {
        const json = await res.json();
        if (json.success && Array.isArray(json.data)) {
          customerMessages.value = json.data.map((m: any) => ({
            id: m.id,
            userId: m.user_id,
            customerName:
              m.user?.customer_profile?.name || m.user?.name || "Customer",
            email: m.user?.email || "customer@example.com",
            phone: m.user?.customer_profile?.phone || undefined,
            subject: m.subject || "Customer Inquiry",
            message: m.message,
            status: m.status || "ongoing",
            staffReply: m.staff_reply,
            staffName: m.staff?.name,
            resolvedAt: m.resolved_at
              ? new Date(m.resolved_at).toLocaleString("en-US", {
                  dateStyle: "short",
                  timeStyle: "short",
                })
              : null,
            createdAt: m.created_at
              ? new Date(m.created_at).toLocaleString("en-US", {
                  dateStyle: "short",
                  timeStyle: "short",
                })
              : "Recent",
          }));
        }
      }
    } catch (e) {
      console.warn("Could not fetch customer messages from API", e);
    } finally {
      isLoadingMessages.value = false;
    }
  };

  const fetchCustomerReviews = async () => {
    isLoadingReviews.value = true;
    try {
      const res = await fetch("/api/customer-reviews");
      if (res.ok) {
        const json = await res.json();
        if (json.success && Array.isArray(json.data)) {
          customerReviews.value = json.data.map((r: any) => ({
            id: r.id,
            userId: r.user_id,
            productId: r.product_id,
            productName: r.product?.name || "Store Item",
            productImage: r.product?.image_url,
            customerName:
              r.user?.customer_profile?.name ||
              m_extractName(r.user) ||
              "Collector",
            email: r.user?.email || "customer@example.com",
            stars: Number(r.stars) || 5,
            message: r.message,
            image: r.image,
            staffReply: r.staff_reply,
            staffName: r.staff?.name,
            repliedAt: r.replied_at
              ? new Date(r.replied_at).toLocaleString("en-US", {
                  dateStyle: "short",
                  timeStyle: "short",
                })
              : null,
            createdAt: r.created_at
              ? new Date(r.created_at).toLocaleString("en-US", {
                  dateStyle: "short",
                  timeStyle: "short",
                })
              : "Recent",
          }));
        }
      }
    } catch (e) {
      console.warn("Could not fetch customer reviews from API", e);
    } finally {
      isLoadingReviews.value = false;
    }
  };

  const m_extractName = (user: any): string => {
    if (!user) return "Collector";
    return user.name || "Collector";
  };

  const replyToCustomerMessage = async (id: number, replyText: string) => {
    try {
      const staffIdNum = currentAdmin.value?.id
        ? parseInt(currentAdmin.value.id.replace(/\D/g, ""))
        : 1;

      const res = await fetch(`/api/customer-messages/${id}/reply`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          staff_reply: replyText,
          staff_id: isNaN(staffIdNum) ? 1 : staffIdNum,
          status: "resolve",
        }),
      });

      if (res.ok) {
        const json = await res.json();
        if (json.success && json.data) {
          const idx = customerMessages.value.findIndex((m) => m.id === id);
          if (idx !== -1) {
            customerMessages.value[idx].status = "resolve";
            customerMessages.value[idx].staffReply = replyText;
            customerMessages.value[idx].staffName =
              currentAdmin.value?.name || "Staff Support";
            customerMessages.value[idx].resolvedAt = "Just now";
          }
        }
      }
    } catch (e) {
      console.error("Failed to reply to customer message", e);
    }
  };

  const toggleCustomerMessageStatus = async (
    id: number,
    newStatus: "ongoing" | "resolve",
  ) => {
    try {
      const res = await fetch(`/api/customer-messages/${id}/status`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ status: newStatus }),
      });

      if (res.ok) {
        const idx = customerMessages.value.findIndex((m) => m.id === id);
        if (idx !== -1) {
          customerMessages.value[idx].status = newStatus;
          if (newStatus === "resolve") {
            customerMessages.value[idx].resolvedAt = "Just now";
          }
        }
      }
    } catch (e) {
      console.error("Failed to update customer message status", e);
    }
  };

  // Real-time Chat Conversations
  const conversations = ref<AdminChatConversation[]>([]);
  const activeConversationId = ref<number | null>(null);
  const activeConversationMessages = ref<AdminChatMessage[]>([]);
  const isLoadingConversations = ref(false);
  const isLoadingChatMessages = ref(false);

  const activeConversation = computed(() => {
    return (
      conversations.value.find((c) => c.id === activeConversationId.value) ||
      null
    );
  });

  const fetchConversations = async (status?: string) => {
    isLoadingConversations.value = true;
    try {
      const headers: Record<string, string> = {};
      if (currentAdmin.value?.token) {
        headers["Authorization"] = `Bearer ${currentAdmin.value.token}`;
      }
      const url =
        status && status !== "all"
          ? `/api/conversations?status=${status}`
          : "/api/conversations";
      const res = await fetch(url, { headers });
      if (res.ok) {
        const json = await res.json();
        if (json.success && Array.isArray(json.data)) {
          conversations.value = json.data;
          if (
            activeConversationId.value === null &&
            conversations.value.length > 0
          ) {
            activeConversationId.value = conversations.value[0].id;
            await fetchConversationMessages(conversations.value[0].id);
          }
        }
      }
    } catch (e) {
      console.warn("Could not fetch conversations", e);
    } finally {
      isLoadingConversations.value = false;
    }
  };

  const fetchConversationMessages = async (convId: number) => {
    activeConversationId.value = convId;
    isLoadingChatMessages.value = true;
    try {
      const headers: Record<string, string> = {};
      if (currentAdmin.value?.token) {
        headers["Authorization"] = `Bearer ${currentAdmin.value.token}`;
      }
      const res = await fetch(`/api/conversations/${convId}/messages`, {
        headers,
      });
      if (res.ok) {
        const json = await res.json();
        if (json.success && Array.isArray(json.data)) {
          activeConversationMessages.value = json.data;
          const conv = conversations.value.find((c) => c.id === convId);
          if (conv) {
            conv.unread_count = 0;
          }
        }
      }
    } catch (e) {
      console.warn("Could not fetch conversation messages", e);
    } finally {
      isLoadingChatMessages.value = false;
    }
  };

  const sendAdminChatMessage = async (convId: number, content: string) => {
    if (!content.trim()) return;
    try {
      const headers: Record<string, string> = {
        "Content-Type": "application/json",
      };
      if (currentAdmin.value?.token) {
        headers["Authorization"] = `Bearer ${currentAdmin.value.token}`;
      }
      const res = await fetch(`/api/conversations/${convId}/messages`, {
        method: "POST",
        headers,
        body: JSON.stringify({ content: content.trim() }),
      });
      if (res.ok) {
        const json = await res.json();
        if (json.success && json.data) {
          activeConversationMessages.value.push(json.data);
          const conv = conversations.value.find((c) => c.id === convId);
          if (conv) {
            conv.latest_message = json.data;
            conv.updated_at = new Date().toISOString();
          }
        }
      }
    } catch (e) {
      console.error("Failed to send admin chat message", e);
    }
  };

  const updateChatConversationStatus = async (
    convId: number,
    status: "active" | "closed" | "resolved",
  ) => {
    try {
      const headers: Record<string, string> = {
        "Content-Type": "application/json",
      };
      if (currentAdmin.value?.token) {
        headers["Authorization"] = `Bearer ${currentAdmin.value.token}`;
      }
      const res = await fetch(`/api/conversations/${convId}/status`, {
        method: "PATCH",
        headers,
        body: JSON.stringify({ status }),
      });
      if (res.ok) {
        const json = await res.json();
        if (json.success && json.data) {
          const conv = conversations.value.find((c) => c.id === convId);
          if (conv) {
            conv.status = status;
          }
        }
      }
    } catch (e) {
      console.error("Failed to update conversation status", e);
    }
  };

  const replyToCustomerReview = async (id: number, replyText: string) => {
    try {
      const staffIdNum = currentAdmin.value?.id
        ? parseInt(currentAdmin.value.id.replace(/\D/g, ""))
        : 1;

      const res = await fetch(`/api/customer-reviews/${id}/reply`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          staff_reply: replyText,
          staff_id: isNaN(staffIdNum) ? 1 : staffIdNum,
        }),
      });

      if (res.ok) {
        const idx = customerReviews.value.findIndex((r) => r.id === id);
        if (idx !== -1) {
          customerReviews.value[idx].staffReply = replyText;
          customerReviews.value[idx].staffName =
            currentAdmin.value?.name || "Store Admin";
          customerReviews.value[idx].repliedAt = "Just now";
        }
      }
    } catch (e) {
      console.error("Failed to reply to customer review", e);
    }
  };

  const deleteCustomerReview = async (id: number) => {
    try {
      const res = await fetch(`/api/customer-reviews/${id}`, {
        method: "DELETE",
      });

      if (res.ok) {
        customerReviews.value = customerReviews.value.filter(
          (r) => r.id !== id,
        );
      }
    } catch (e) {
      console.error("Failed to delete customer review", e);
    }
  };

  return {
    currentAdmin,
    isAuthenticated,
    dashboardTimeframe,
    orders,
    shippingCarriers,
    orderStatuses,
    isLoadingOrders,
    fetchShippingCarriers,
    fetchOrderStatuses,
    fetchOrders,
    inventory,
    isLoadingInventory,
    fetchInventory,
    customers,
    inquiries,
    customerMessages,
    isLoadingMessages,
    customerReviews,
    isLoadingReviews,
    conversations,
    activeConversationId,
    activeConversationMessages,
    activeConversation,
    isLoadingConversations,
    isLoadingChatMessages,
    fetchConversations,
    fetchConversationMessages,
    sendAdminChatMessage,
    updateChatConversationStatus,
    fetchCustomerMessages,
    fetchCustomerReviews,
    replyToCustomerMessage,
    toggleCustomerMessageStatus,
    replyToCustomerReview,
    deleteCustomerReview,
    promoCodes,
    abandonedCarts,
    cmsBanners,
    staffMembers,
    settings,
    metrics,
    dashboardData,
    isLoadingDashboard,
    fetchDashboardData,
    topProducts,
    topReferrers,
    allowedModulePaths,
    allowedModuleCodes,
    staffPermissions,
    isPermissionsLoaded,
    fetchStaffPermissions,
    canAccess,
    canAccessPath,
    canAccessModule,
    login,
    logout,
    updateOrderStatus,
    attachTracking,
    markPackingSlipPrinted,
    processRefund,
    updateStock,
    addInventoryItem,
    addPromoCode,
    togglePromoCode,
    sendAbandonedCartReminder,
    markInquiryStatus,
  };
});
