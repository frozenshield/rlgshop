export type AdminRole = 'super-admin' | 'manager' | 'fulfillment'

export interface AdminUser {
  id: string
  name: string
  email: string
  role: AdminRole
  avatarUrl?: string
  lastLogin: string
}

export type OrderStatus = 'Pending' | 'Processing' | 'Shipped' | 'Delivered' | 'Canceled'

export interface AdminOrderItem {
  id: string
  name: string
  sku: string
  price: number
  quantity: number
  imageUrl: string
}

export interface AdminOrder {
  id: string
  customerName: string
  customerEmail: string
  customerPhone: string
  shippingAddress: string
  city: string
  postalCode: string
  items: AdminOrderItem[]
  total: number
  status: OrderStatus
  paymentMethod: string
  trackingNumber?: string
  carrier?: string
  packingSlipPrinted: boolean
  refundStatus?: 'None' | 'Partial' | 'Full'
  refundAmount?: number
  invoiceId: string
  createdAt: string
  notes?: string
}

export interface ProductVariant {
  id: string
  title: string // e.g. "Japanese / Factory Sealed"
  sku: string
  price: number
  costPrice: number
  stock: number
  barcode?: string
}

export interface InventoryItem {
  id: string
  sku: string
  barcode: string
  name: string
  category: string
  stock: number
  lowStockThreshold: number
  costPrice: number
  sellingPrice: number
  vendor: string
  leadTimeDays: number
  variants: ProductVariant[]
}

export interface CustomerProfile {
  id: string
  name: string
  email: string
  phone: string
  city: string
  totalOrders: number
  lifetimeValue: number
  segment: 'VIP' | 'Regular' | 'Wholesale' | 'Inactive'
  lastOrderDate: string
  notes?: string
}

export interface CustomerInquiry {
  id: string
  customerName: string
  email: string
  type: 'inquiry' | 'dispute' | 'review'
  subject: string
  message: string
  date: string
  status: 'unread' | 'in-progress' | 'resolved'
}

export interface PromoCode {
  id: string
  code: string
  type: 'percentage' | 'fixed' | 'shipping'
  value: number
  usageCount: number
  usageLimit: number
  expiryDate: string
  isActive: boolean
}

export interface AbandonedCart {
  id: string
  customerEmail: string
  customerName: string
  itemsCount: number
  totalValue: number
  lastActive: string
  recovered: boolean
  reminderSent: boolean
}

export interface CMSBanner {
  id: string
  title: string
  subtitle: string
  badgeText: string
  ctaText: string
  ctaLink: string
  imageUrl: string
  isActive: boolean
}

export interface StaffMember {
  id: string
  name: string
  email: string
  role: 'Super Admin' | 'Store Manager' | 'Fulfillment Staff'
  permissions: string[]
  isActive: boolean
}

export interface StoreSettings {
  storeName: string
  currency: string
  currencySymbol: string
  taxRatePercent: number
  flatShippingRate: number
  freeShippingThreshold: number
  gateways: {
    gcash: boolean
    maya: boolean
    stripe: boolean
    paypal: boolean
    cod: boolean
  }
}
