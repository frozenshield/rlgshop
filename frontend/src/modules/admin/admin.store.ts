import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useStorage } from '@vueuse/core'
import type {
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
} from './admin.types'

export const useAdminStore = defineStore('adminStore', () => {
  // Current logged in admin session
  const currentAdmin = useStorage<AdminUser | null>('rlg-admin-session', null)

  // Dashboard timeframe filter
  const dashboardTimeframe = ref<'today' | 'week' | 'month'>('week')

  // Orders
  const orders = useStorage<AdminOrder[]>('rlg-admin-orders', [
    {
      id: 'ORD-9842',
      customerName: 'Marcus Tan',
      customerEmail: 'marcus.tan@example.com',
      customerPhone: '+63 917 555 1234',
      shippingAddress: '42 Orchid St, Unit 3B, New Manila',
      city: 'Quezon City',
      postalCode: '1112',
      items: [
        {
          id: 'poke-tcg-1',
          name: 'Pokémon TCG: Scarlet & Violet 151 Elite Trainer Box',
          sku: 'TCG-PKM-151-ETB',
          price: 2799,
          quantity: 1,
          imageUrl: 'https://images.unsplash.com/photo-1628155930542-3c7a64e2c833?w=600&auto=format&fit=crop&q=80',
        },
        {
          id: 'op-tcg-1',
          name: 'One Piece Card Game: OP-05 Booster Box',
          sku: 'TCG-OP-05-BOX',
          price: 4750,
          quantity: 1,
          imageUrl: 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=600&auto=format&fit=crop&q=80',
        },
      ],
      total: 7549,
      status: 'Processing',
      paymentMethod: 'GCash',
      packingSlipPrinted: true,
      invoiceId: 'INV-2026-001',
      createdAt: '2026-09-24 09:15 AM',
      notes: 'Please pack with corner bubble armor protectors.',
    },
    {
      id: 'ORD-9843',
      customerName: 'Elena Reyes',
      customerEmail: 'elena.reyes@example.com',
      customerPhone: '+63 918 888 4567',
      shippingAddress: '15 Katipunan Ave, Loyola Heights',
      city: 'Quezon City',
      postalCode: '1108',
      items: [
        {
          id: 'poke-fig-4',
          name: 'Monkey D. Luffy Gear 5 Sun God Nika Battle Figure',
          sku: 'FIG-OP-LUFFY-G5',
          price: 2688,
          quantity: 1,
          imageUrl: 'https://images.unsplash.com/photo-1594787318286-3d835c1d207f?w=600&auto=format&fit=crop&q=80',
        },
      ],
      total: 2688,
      status: 'Shipped',
      trackingNumber: 'PH-JT-894729104',
      carrier: 'J&T Express Courier',
      paymentMethod: 'Maya',
      packingSlipPrinted: true,
      invoiceId: 'INV-2026-002',
      createdAt: '2026-09-23 03:40 PM',
    },
    {
      id: 'ORD-9844',
      customerName: 'David Cruz',
      customerEmail: 'david.cruz@example.com',
      customerPhone: '+63 920 333 9988',
      shippingAddress: '88 Ayala Avenue, Tower One, Suite 12A',
      city: 'Makati City',
      postalCode: '1226',
      items: [
        {
          id: 'holo-tcg-1',
          name: 'Hololive Official Card Game: Blooming Radiance Booster Box',
          sku: 'TCG-HOLO-BP01',
          price: 3950,
          quantity: 2,
          imageUrl: 'https://images.unsplash.com/photo-1563089145-599997674d42?w=600&auto=format&fit=crop&q=80',
        },
      ],
      total: 7900,
      status: 'Pending',
      paymentMethod: 'Cash on Delivery',
      packingSlipPrinted: false,
      invoiceId: 'INV-2026-003',
      createdAt: '2026-09-24 11:05 AM',
      notes: 'Call before delivery.',
    },
    {
      id: 'ORD-9845',
      customerName: 'Chloe Mendoza',
      customerEmail: 'chloe.m@example.com',
      customerPhone: '+63 905 111 2233',
      shippingAddress: '22 Alabang-Zapote Rd',
      city: 'Muntinlupa City',
      postalCode: '1780',
      items: [
        {
          id: 'poke-tcg-2',
          name: 'Pokémon TCG: Charizard ex Super-Premium Collection Box',
          sku: 'TCG-PKM-CHZ-EX',
          price: 4499,
          quantity: 1,
          imageUrl: 'https://images.unsplash.com/photo-1613771404784-3a5686aa2be3?w=600&auto=format&fit=crop&q=80',
        },
      ],
      total: 4499,
      status: 'Delivered',
      trackingNumber: 'PH-LBC-9912048',
      carrier: 'LBC Express',
      paymentMethod: 'Credit Card',
      packingSlipPrinted: true,
      invoiceId: 'INV-2026-004',
      createdAt: '2026-09-21 02:10 PM',
    },
    {
      id: 'ORD-9846',
      customerName: 'Kenji Sato',
      customerEmail: 'kenji.sato@example.com',
      customerPhone: '+63 916 444 8877',
      shippingAddress: '7 Fort Victoria, BGC',
      city: 'Taguig City',
      postalCode: '1634',
      items: [
        {
          id: 'dm-tcg-1',
          name: 'Duel Masters: Abyss Revolution 24-Pack Booster Box',
          sku: 'TCG-DM-DM23-RP1',
          price: 3600,
          quantity: 1,
          imageUrl: 'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?w=600&auto=format&fit=crop&q=80',
        },
      ],
      total: 3600,
      status: 'Canceled',
      paymentMethod: 'GCash',
      refundStatus: 'Full',
      refundAmount: 3600,
      packingSlipPrinted: false,
      invoiceId: 'INV-2026-005',
      createdAt: '2026-09-22 08:30 AM',
      notes: 'Customer requested cancellation prior to dispatch.',
    },
  ])

  // Inventory & Stock
  const inventory = useStorage<InventoryItem[]>('rlg-admin-inventory', [
    {
      id: 'inv-1',
      sku: 'TCG-PKM-151-ETB',
      barcode: '0820650853685',
      name: 'Pokémon TCG: Scarlet & Violet 151 Elite Trainer Box',
      category: 'TCG (Cards)',
      stock: 6,
      lowStockThreshold: 10,
      costPrice: 1950,
      sellingPrice: 2799,
      vendor: 'The Pokémon Company Intl.',
      leadTimeDays: 7,
      variants: [
        { id: 'v1', title: 'English Edition', sku: 'TCG-PKM-151-ETB-EN', price: 2799, costPrice: 1950, stock: 4 },
        { id: 'v2', title: 'Japanese Edition', sku: 'TCG-PKM-151-ETB-JP', price: 3200, costPrice: 2200, stock: 2 },
      ],
    },
    {
      id: 'inv-2',
      sku: 'TCG-OP-05-BOX',
      barcode: '4570118080512',
      name: 'One Piece Card Game: Awakening of the New Era (OP-05)',
      category: 'TCG (Cards)',
      stock: 12,
      lowStockThreshold: 8,
      costPrice: 3400,
      sellingPrice: 4750,
      vendor: 'Bandai Namco Asia',
      leadTimeDays: 14,
      variants: [
        { id: 'v3', title: 'Factory Sealed Box', sku: 'TCG-OP-05-BOX-F', price: 4750, costPrice: 3400, stock: 12 },
      ],
    },
    {
      id: 'inv-3',
      sku: 'FIG-GNDM-RG-RX78',
      barcode: '4573102663047',
      name: 'RG 1/144 RX-78-2 Gundam Ver.2.0 Gunpla Model Kit',
      category: 'Gunpla & Models',
      stock: 4,
      lowStockThreshold: 10,
      costPrice: 1800,
      sellingPrice: 2550,
      vendor: 'Bandai Spirits Hobby',
      leadTimeDays: 10,
      variants: [
        { id: 'v4', title: 'Standard Kit', sku: 'FIG-GNDM-RG-STD', price: 2550, costPrice: 1800, stock: 4 },
      ],
    },
    {
      id: 'inv-4',
      sku: 'FIG-OP-LUFFY-G5',
      barcode: '4983164883492',
      name: 'Monkey D. Luffy Gear 5 Sun God Nika Battle Figure (8-Inch)',
      category: 'Anime Figures',
      stock: 14,
      lowStockThreshold: 5,
      costPrice: 1750,
      sellingPrice: 2688,
      vendor: 'Bandai Spirits / Banpresto',
      leadTimeDays: 12,
      variants: [],
    },
    {
      id: 'inv-5',
      sku: 'SUP-SLV-KMC-MAT',
      barcode: '4521086001004',
      name: 'KMC Hyper Matte Premium Tournament Card Sleeves (80ct)',
      category: 'Hobby Supplies',
      stock: 45,
      lowStockThreshold: 15,
      costPrice: 180,
      sellingPrice: 350,
      vendor: 'KMC Card Barrier Japan',
      leadTimeDays: 5,
      variants: [
        { id: 'v5', title: 'Midnight Black', sku: 'SUP-SLV-KMC-BLK', price: 350, costPrice: 180, stock: 25 },
        { id: 'v6', title: 'Clear Gloss', sku: 'SUP-SLV-KMC-CLR', price: 350, costPrice: 180, stock: 20 },
      ],
    },
  ])

  // Customers
  const customers = useStorage<CustomerProfile[]>('rlg-admin-customers', [
    {
      id: 'CUST-101',
      name: 'Marcus Tan',
      email: 'marcus.tan@example.com',
      phone: '+63 917 555 1234',
      city: 'Quezon City',
      totalOrders: 9,
      lifetimeValue: 48500,
      segment: 'VIP',
      lastOrderDate: '2026-09-24',
      notes: 'Avid sealed Pokémon & One Piece booster box collector.',
    },
    {
      id: 'CUST-102',
      name: 'Elena Reyes',
      email: 'elena.reyes@example.com',
      phone: '+63 918 888 4567',
      city: 'Quezon City',
      totalOrders: 4,
      lifetimeValue: 12900,
      segment: 'Regular',
      lastOrderDate: '2026-09-23',
    },
    {
      id: 'CUST-103',
      name: 'Metro Card Haven Inc.',
      email: 'procurement@metrocardhaven.ph',
      phone: '+63 922 777 9900',
      city: 'Pasig City',
      totalOrders: 18,
      lifetimeValue: 245000,
      segment: 'Wholesale',
      lastOrderDate: '2026-09-18',
      notes: 'Wholesale card shop in Megamall. Inquiring on case break pricing.',
    },
    {
      id: 'CUST-104',
      name: 'Rafael Villanueva',
      email: 'rafael.v@example.com',
      phone: '+63 908 444 3322',
      city: 'Cebu City',
      totalOrders: 1,
      lifetimeValue: 1890,
      segment: 'Inactive',
      lastOrderDate: '2026-04-12',
    },
  ])

  // Inquiries & Centralized Inbox
  const inquiries = useStorage<CustomerInquiry[]>('rlg-admin-inquiries', [
    {
      id: 'INQ-301',
      customerName: 'Marcus Tan',
      email: 'marcus.tan@example.com',
      type: 'inquiry',
      subject: 'Pre-order allocation for One Piece OP-09 The Four Emperors?',
      message: 'Hi team, will you be taking pre-orders for OP-09 cases? How many booster boxes per customer is the limit?',
      date: '2026-09-24 10:30 AM',
      status: 'unread',
    },
    {
      id: 'INQ-302',
      customerName: 'Carlos Dizon',
      email: 'carlos.dizon@example.com',
      type: 'dispute',
      subject: 'Courier delay on parcel tracking #PH-JT-894729104',
      message: 'My package tracking shows pending pickup at warehouse for 48 hours. Can you expedite with J&T?',
      date: '2026-09-23 04:15 PM',
      status: 'in-progress',
    },
    {
      id: 'INQ-303',
      customerName: 'Sarah Jenkins',
      email: 'sarah.j@example.com',
      type: 'review',
      subject: 'Review awaiting moderation: "RG RX-78-2 Gundam Ver.2.0"',
      message: '5 Stars - "Flawless runners, perfectly protected with bubble film and double box. Will order again!"',
      date: '2026-09-22 01:20 PM',
      status: 'resolved',
    },
  ])

  // Marketing & Promo Codes
  const promoCodes = useStorage<PromoCode[]>('rlg-admin-promos', [
    {
      id: 'PROMO-1',
      code: 'HOBBY10',
      type: 'percentage',
      value: 10,
      usageCount: 142,
      usageLimit: 500,
      expiryDate: '2026-12-31',
      isActive: true,
    },
    {
      id: 'PROMO-2',
      code: 'GUNPLA20',
      type: 'percentage',
      value: 20,
      usageCount: 88,
      usageLimit: 200,
      expiryDate: '2026-10-31',
      isActive: true,
    },
    {
      id: 'PROMO-3',
      code: 'FREESHIPPH',
      type: 'shipping',
      value: 0,
      usageCount: 65,
      usageLimit: 300,
      expiryDate: '2026-11-15',
      isActive: true,
    },
  ])

  // Abandoned Carts
  const abandonedCarts = useStorage<AbandonedCart[]>('rlg-admin-abandoned-carts', [
    {
      id: 'AB-881',
      customerEmail: 'joshua.hobby@gmail.com',
      customerName: 'Joshua Aquino',
      itemsCount: 2,
      totalValue: 5498,
      lastActive: '2 hours ago',
      recovered: false,
      reminderSent: false,
    },
    {
      id: 'AB-882',
      customerEmail: 'mika.otaku@yahoo.com',
      customerName: 'Mika Fernandez',
      itemsCount: 1,
      totalValue: 2688,
      lastActive: '6 hours ago',
      recovered: false,
      reminderSent: true,
    },
  ])

  // CMS Banners
  const cmsBanners = useStorage<CMSBanner[]>('rlg-admin-cms-banners', [
    {
      id: 'bnr-1',
      title: 'Build, Collect & Battle. Your Premier Hobby Store.',
      subtitle: 'Factory-sealed TCG booster boxes, authentic Japanese Bandai Gunpla kits, detailed anime scale figures.',
      badgeText: 'RLG HOBBY SHOP • OFFICIAL IMPORTS VAULT',
      ctaText: 'Explore All Products',
      ctaLink: '/catalog',
      imageUrl: 'https://images.unsplash.com/photo-1613771404784-3a5686aa2be3?w=700&auto=format&fit=crop&q=80',
      isActive: true,
    },
    {
      id: 'bnr-2',
      title: 'Level Up Your Collection: 10% - 20% Off Drops!',
      subtitle: 'Apply collector code HOBBY10 or GUNPLA20 at checkout on all orders.',
      badgeText: 'Collector Welcome Coupon ⚡',
      ctaText: 'Shop Deals Now',
      ctaLink: '/catalog',
      imageUrl: 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=700&auto=format&fit=crop&q=80',
      isActive: true,
    },
  ])

  // Staff & RBAC
  const staffMembers = useStorage<StaffMember[]>('rlg-admin-staff', [
    {
      id: 'STF-01',
      name: 'Admin Chief',
      email: 'admin@rlghobby.com',
      role: 'Super Admin',
      permissions: ['All Modules', 'Financial Refunds', 'Staff Management', 'CMS Publishing'],
      isActive: true,
    },
    {
      id: 'STF-02',
      name: 'Rowena Santos',
      email: 'rowena.ops@rlghobby.com',
      role: 'Store Manager',
      permissions: ['Order Management', 'Inventory Control', 'CRM Support', 'Promotions'],
      isActive: true,
    },
    {
      id: 'STF-03',
      name: 'Darwin Gomez',
      email: 'darwin.pack@rlghobby.com',
      role: 'Fulfillment Staff',
      permissions: ['View Orders', 'Print Packing Slips', 'Update Shipping Status'],
      isActive: true,
    },
  ])

  // Store Settings
  const settings = useStorage<StoreSettings>('rlg-admin-settings', {
    storeName: 'RLG Hobby Shop',
    currency: 'PHP',
    currencySymbol: '₱',
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
  })

  // Getters / Computed Metrics for Dashboard
  const metrics = computed(() => {
    const totalSales = orders.value
      .filter((o) => o.status !== 'Canceled')
      .reduce((sum, o) => sum + o.total, 0)
    const pendingOrdersCount = orders.value.filter((o) => o.status === 'Pending').length
    const lowStockCount = inventory.value.filter((i) => i.stock <= i.lowStockThreshold).length
    const unreadInquiriesCount = inquiries.value.filter((i) => i.status === 'unread').length

    return {
      totalRevenue: totalSales,
      totalOrdersCount: orders.value.length,
      activeVisitorsToday: 1842,
      pendingOrdersCount,
      lowStockCount,
      unreadInquiriesCount,
      grossSales: totalSales * 1.12,
      netSales: totalSales,
      totalTax: totalSales * 0.12,
      averageOrderValue: Math.round(totalSales / (orders.value.length || 1)),
    }
  })

  // Top performers
  const topProducts = computed(() => [
    { name: 'One Piece OP-05 Awakening of the New Era', unitsSold: 94, revenue: 446500, share: '38%' },
    { name: 'Pokémon TCG 151 Elite Trainer Box', unitsSold: 78, revenue: 218322, share: '24%' },
    { name: 'Hololive OCG Blooming Radiance Booster Box', unitsSold: 56, revenue: 221200, share: '19%' },
    { name: 'RG 1/144 RX-78-2 Gundam Ver.2.0 Kit', unitsSold: 42, revenue: 107100, share: '12%' },
  ])

  const topReferrers = computed(() => [
    { source: 'Facebook TCG & Gunpla Philippines Groups', visitors: 6420, conversionRate: '4.8%' },
    { source: 'Google Organic Search ("RLG Hobby Shop PH")', visitors: 4980, conversionRate: '6.2%' },
    { source: 'YouTube Hobbyist Unboxing & Card Reviews', visitors: 2840, conversionRate: '5.1%' },
    { source: 'Direct & Bookmark Collectors', visitors: 2110, conversionRate: '8.4%' },
  ])

  // Authentication Actions
  const login = (email: string, role: 'super-admin' | 'manager' | 'fulfillment' = 'super-admin'): boolean => {
    currentAdmin.value = {
      id: 'ADM-' + Math.floor(1000 + Math.random() * 9000),
      name: email.split('@')[0].toUpperCase(),
      email,
      role,
      lastLogin: new Date().toLocaleTimeString(),
    }
    return true
  }

  const logout = () => {
    currentAdmin.value = null
  }

  // Order Management Actions
  const updateOrderStatus = (orderId: string, newStatus: OrderStatus) => {
    const order = orders.value.find((o) => o.id === orderId)
    if (order) {
      order.status = newStatus
    }
  }

  const attachTracking = (orderId: string, trackingNumber: string, carrier: string) => {
    const order = orders.value.find((o) => o.id === orderId)
    if (order) {
      order.trackingNumber = trackingNumber
      order.carrier = carrier
      order.status = 'Shipped'
    }
  }

  const markPackingSlipPrinted = (orderId: string) => {
    const order = orders.value.find((o) => o.id === orderId)
    if (order) {
      order.packingSlipPrinted = true
    }
  }

  const processRefund = (orderId: string, amount: number, isFull: boolean) => {
    const order = orders.value.find((o) => o.id === orderId)
    if (order) {
      order.refundStatus = isFull ? 'Full' : 'Partial'
      order.refundAmount = amount
      if (isFull) {
        order.status = 'Canceled'
      }
    }
  }

  // Inventory Actions
  const updateStock = (itemId: string, newStock: number) => {
    const item = inventory.value.find((i) => i.id === itemId)
    if (item) {
      item.stock = newStock
    }
  }

  const addInventoryItem = (newItem: Omit<InventoryItem, 'id'>) => {
    const id = 'inv-' + (inventory.value.length + 1)
    inventory.value.unshift({ id, ...newItem })
  }

  // Promo Engine Actions
  const addPromoCode = (promo: Omit<PromoCode, 'id' | 'usageCount'>) => {
    promoCodes.value.unshift({
      id: 'PROMO-' + (promoCodes.value.length + 1),
      usageCount: 0,
      ...promo,
    })
  }

  const togglePromoCode = (id: string) => {
    const promo = promoCodes.value.find((p) => p.id === id)
    if (promo) {
      promo.isActive = !promo.isActive
    }
  }

  const sendAbandonedCartReminder = (cartId: string) => {
    const cart = abandonedCarts.value.find((c) => c.id === cartId)
    if (cart) {
      cart.reminderSent = true
    }
  }

  // Inquiry Actions
  const markInquiryStatus = (id: string, status: 'unread' | 'in-progress' | 'resolved') => {
    const inq = inquiries.value.find((i) => i.id === id)
    if (inq) {
      inq.status = status
    }
  }

  return {
    currentAdmin,
    dashboardTimeframe,
    orders,
    inventory,
    customers,
    inquiries,
    promoCodes,
    abandonedCarts,
    cmsBanners,
    staffMembers,
    settings,
    metrics,
    topProducts,
    topReferrers,
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
  }
})
