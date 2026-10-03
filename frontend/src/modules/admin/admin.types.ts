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
  | "In Transit"
  | "Delivered"
  | "Completed"
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

export interface ProductBundleItem {
  id: number;
  primary_product_id: number;
  primary_product?: any;
  title: string;
  bundle_product_ids: number[];
  bundled_products?: any[];
  discount_percentage: number;
  conversion_lift: string;
  badge_text: string;
  is_active: boolean;
  created_at?: string;
}

export interface AbandonedCart {
  id: string | number;
  customer_id?: number | null;
  customerEmail: string;
  customerName: string;
  itemsCount: number;
  totalValue: number;
  cartItems?: any[];
  recoveryToken?: string;
  discountCode?: string;
  discountPercent?: number;
  lastActive: string;
  recovered: boolean;
  reminderSent: boolean;
  reminderSentAt?: string | null;
}

export interface SeoMetadataItem {
  id?: number;
  entity_type: string;
  entity_id?: number | null;
  page_name: string;
  route_path: string;
  meta_title: string;
  meta_description: string;
  meta_keywords?: string;
  focus_keyword?: string;
  canonical_url?: string;
  og_title?: string;
  og_description?: string;
  og_image_url?: string;
  twitter_card?: string;
  robots?: string;
  structured_data_json?: any;
  seo_score?: number;
  ai_generated?: boolean;
  ai_model?: string;
  is_active?: boolean;
  created_at?: string;
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

export interface AdminChatMessage {
  id: number;
  conversation_id: number;
  sender_id: number;
  content: string;
  is_read: boolean;
  created_at: string;
  sender?: {
    id: number;
    name: string;
    email: string;
    user_type?: string;
  };
}

export interface AdminChatConversation {
  id: number;
  customer_id: number;
  admin_id?: number | null;
  status: "active" | "closed" | "resolved";
  created_at: string;
  updated_at: string;
  unread_count?: number;
  customer?: {
    id: number;
    name: string;
    email: string;
    phone?: string;
    customer_profile?: {
      name?: string;
      phone?: string;
      avatar?: string;
      segment_rank?: string;
    };
  };
  admin?: {
    id: number;
    name: string;
    email: string;
  };
  latest_message?: AdminChatMessage;
}

export interface DashboardMetrics {
  total_revenue: number;
  total_orders: number;
  average_order_value: number;
  total_added_cart: number;
  total_cart_unique_items: number;
  total_added_favourite: number;
  pending_orders_count: number;
  low_stock_count: number;
  unread_inquiries_count: number;
  active_visitors_today: number;
  checkout_active_visitors?: number;
  telemetry_source?: string;
  telemetry_label?: string;
  telemetry_window?: string;
  ga4_configured?: boolean;
  revenue_growth_pct: number;
  order_velocity_pct: number;
  aov_bundle_lift: number;
}

export interface DashboardTopProduct {
  name: string;
  unitsSold: number;
  revenue: number;
  share: string;
}

export interface DashboardTopReferrer {
  source: string;
  visitors: number;
  conversionRate: string;
}

export interface DashboardData {
  metrics: DashboardMetrics;
  top_products: DashboardTopProduct[];
  top_referrers: DashboardTopReferrer[];
  recent_orders: any[];
}

export interface ExecutiveKpis {
  gross_sales: number;
  total_refunds: number;
  net_store_sales: number;
  vat_collected: number;
  est_logistics_expense: number;
  total_orders: number;
  average_order_value: number;
  tied_up_capital: number;
  total_inventory_units: number;
  low_stock_count: number;
  out_of_stock_count: number;
  total_active_skus: number;
  active_customers_count: number;
  total_cart_items: number;
  total_favourites_count: number;
}

export interface MonthlyTrajectoryItem {
  month_key: string;
  month: string;
  year: number;
  gross: number;
  refunds: number;
  net: number;
  orders: number;
  height: string;
}

export interface HighVelocityItem {
  sku: string;
  name: string;
  stock: number;
  price: number;
  velocityPerDay: number;
  daysToStockout: number;
  runRate: 'Critical' | 'High' | 'Moderate';
}

export interface DeadStockItem {
  sku: string;
  name: string;
  stock: number;
  price: number;
  daysInStock: number;
  unitsSold30d: number;
  tiedUpCapital: number;
}

export interface CategoryDistributionItem {
  id: number;
  name: string;
  revenue: number;
  unitsSold: number;
  productCount: number;
  sharePct: number;
}

export interface PaymentGatewayItem {
  method: string;
  orders: number;
  volume: number;
  sharePct: number;
}

export interface ExecutiveAnalyticsReportResponse {
  success: boolean;
  timeframe: string;
  period_label: string;
  generated_at: string;
  currency: string;
  kpis: ExecutiveKpis;
  monthlyTrajectory: MonthlyTrajectoryItem[];
  highVelocityItems: HighVelocityItem[];
  deadStockItems: DeadStockItem[];
  categoryDistribution: CategoryDistributionItem[];
  paymentGatewayDistribution: PaymentGatewayItem[];
}
