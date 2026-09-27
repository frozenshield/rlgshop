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
  | "Accepted"
  | "Canceled"
  | "Refunded"
  | "Returned";

export interface RefShippingCarrierItem {
  id: number;
  name: string;
  code: string;
  short_name?: string;
  tracking_url_template?: string;
  is_active: boolean;
}

export interface RefOrderStatusItem {
  id: number;
  name: string;
  label: string;
  badge_color?: string;
  description?: string;
}

export interface AdminOrderItem {
  id: string;
  productId?: number;
  name: string;
  sku: string;
  price: number;
  quantity: number;
  subtotal?: number;
  imageUrl: string;
  category?: string;
  brand?: string;
  condition?: string;
}

export interface AdminOrder {
  id: string;
  backendId?: number;
  customerName: string;
  customerEmail: string;
  customerPhone: string;
  shippingAddress: string;
  city: string;
  postalCode: string;
  items: AdminOrderItem[];
  total: number;
  status: OrderStatus;
  statusLabel?: string;
  statusBadgeColor?: string;
  paymentMethod: string;
  paymentStatus?: string;
  trackingNumber?: string;
  carrier?: string;
  carrierId?: number;
  carrierTrackingUrl?: string;
  packingSlipPrinted: boolean;
  refundStatus?: "None" | "Partial" | "Full";
  refundAmount?: number;
  invoiceId: string;
  createdAt: string;
  rawOrderDate?: string;
  notes?: string;
  customerProfile?: any;
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

export interface CustomerMessageItem {
  id: number;
  userId?: number | null;
  customerName: string;
  email: string;
  phone?: string;
  subject: string;
  message: string;
  status: "ongoing" | "resolve";
  staffReply?: string | null;
  staffName?: string | null;
  resolvedAt?: string | null;
  createdAt: string;
}

export interface CustomerReviewItem {
  id: number;
  userId?: number | null;
  productId?: number | null;
  productName: string;
  productImage?: string | null;
  customerName: string;
  email: string;
  stars: number;
  message: string;
  image?: string | null;
  staffReply?: string | null;
  staffName?: string | null;
  repliedAt?: string | null;
  createdAt: string;
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
