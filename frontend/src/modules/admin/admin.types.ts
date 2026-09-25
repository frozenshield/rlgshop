export type AdminRole = "super-admin" | "manager" | "fulfillment";

export interface StaffModulePermission {
  id: number;
  matrix_id: number;
  code: string;
  name: string;
  path: string;
  description?: string;
  can_read: boolean;
  can_create: boolean;
  can_update: boolean;
  can_delete: boolean;
}

export interface StaffModulesResponse {
  success: boolean;
  role: {
    id: number;
    name: string;
    label?: string;
    permissions_label?: string;
  };
  staff?: {
    id: number;
    name: string;
    email: string;
  } | null;
  modules: StaffModulePermission[];
  allowed_paths: string[];
  allowed_codes: string[];
}

export interface AdminUser {
  id: string;
  name: string;
  email: string;
  role: AdminRole;
  roleLabel?: string;
  token?: string;
  avatarUrl?: string;
  lastLogin: string;
  allowedPaths?: string[];
  allowedCodes?: string[];
}

export type OrderStatus =
  | "Pending"
  | "Processing"
  | "Shipped"
  | "Delivered"
  | "Canceled";

export interface AdminOrderItem {
  id: string;
  name: string;
  sku: string;
  price: number;
  quantity: number;
  imageUrl: string;
}

export interface AdminOrder {
  id: string;
  customerName: string;
  customerEmail: string;
  customerPhone: string;
  shippingAddress: string;
  city: string;
  postalCode: string;
  items: AdminOrderItem[];
  total: number;
  status: OrderStatus;
  paymentMethod: string;
  trackingNumber?: string;
  carrier?: string;
  packingSlipPrinted: boolean;
  refundStatus?: "None" | "Partial" | "Full";
  refundAmount?: number;
  invoiceId: string;
  createdAt: string;
  notes?: string;
}

export interface ProductVariant {
  id: string;
  title: string; // e.g. "Japanese / Factory Sealed"
  sku: string;
  price: number;
  costPrice: number;
  stock: number;
  barcode?: string;
}

export interface InventoryItem {
  id: string;
  sku: string;
  barcode: string;
  name: string;
  category: string;
  condition?: string;
  stock: number;
  lowStockThreshold: number;
  costPrice: number;
  sellingPrice: number;
  vendor: string;
  leadTimeDays: number;
  variants: ProductVariant[];
}

export interface CustomerProfile {
  id: string;
  name: string;
  email: string;
  phone: string;
  city: string;
  totalOrders: number;
  lifetimeValue: number;
  segment: "VIP" | "Regular" | "Wholesale" | "Inactive";
  lastOrderDate: string;
  notes?: string;
}

export interface CustomerInquiry {
  id: string;
  customerName: string;
  email: string;
  type: "inquiry" | "dispute" | "review";
  subject: string;
  message: string;
  date: string;
  status: "unread" | "in-progress" | "resolved";
}

export interface PromoCode {
  id: string;
  code: string;
  type: "percentage" | "fixed" | "shipping";
  value: number;
  usageCount: number;
  usageLimit: number;
  expiryDate: string;
  isActive: boolean;
}

export interface AbandonedCart {
  id: string;
  customerEmail: string;
  customerName: string;
  itemsCount: number;
  totalValue: number;
  lastActive: string;
  recovered: boolean;
  reminderSent: boolean;
}

export interface CMSBanner {
  id: string;
  title: string;
  subtitle: string;
  badgeText: string;
  ctaText: string;
  ctaLink: string;
  imageUrl: string;
  isActive: boolean;
}

export interface StaffMember {
  id: string | number;
  name: string;
  email: string;
  role: string;
  roleLabel?: string;
  refStaffRoleId?: number;
  permissions: string[];
  isActive: boolean;
  roleDetails?: any;
}

export interface StoreSettings {
  storeName: string;
  currency: string;
  currencySymbol: string;
  taxRatePercent: number;
  flatShippingRate: number;
  freeShippingThreshold: number;
  gateways: {
    gcash: boolean;
    maya: boolean;
    stripe: boolean;
    paypal: boolean;
    cod: boolean;
  };
}
