export type AgeGroup = '0-2' | '3-5' | '6-8' | '9-12' | '12+'

export type ToyCategory = 'tcg' | 'anime-figures' | 'anime-merchandise'

export type TcgSubCategory =
  | 'pokemon'
  | 'one-piece'
  | 'hololive'
  | 'duel-masters'
  | 'weiss-schwarz'

export interface CustomerReview {
  id: string
  author: string
  rating: number
  date: string
  comment: string
  verified: boolean
}

export interface ToyProduct {
  id: string
  name: string
  slug: string
  description: string
  category: ToyCategory
  tcgSeries?: TcgSubCategory // For TCG items: 'pokemon' | 'one-piece' | 'hololive' | 'duel-masters' | 'weiss-schwarz'
  ageGroup: AgeGroup
  pokemonType?: string // Type or Franchise Badge (e.g. Electric ⚡, One Piece 🏴‍☠️, Hololive 🎤)
  price: number
  originalPrice?: number
  discountPercent?: number
  rating: number
  reviewCount: number
  stock: number
  brand: string // Pokémon Official, Bandai, Bushiroad, Takara Tomy, Good Smile, etc.
  imageUrl: string
  galleryImages: string[]
  tags: string[]
  isFeatured?: boolean
  isBestSeller?: boolean
  isNewArrival?: boolean
  safetyWarning?: string
  features: string[]
}

export interface CartItem {
  toy: ToyProduct
  quantity: number
}

export interface DeliveryOption {
  id: 'standard' | 'express' | 'gift-wrapped'
  name: string
  price: number
  estimatedDays: string
  description: string
}

export interface CheckoutFormData {
  firstName: string
  lastName: string
  email: string
  phone: string
  streetAddress: string
  city: string
  postalCode: string
  notes?: string
  deliveryOption: 'standard' | 'express' | 'gift-wrapped'
  giftMessage?: string
  paymentMethod: 'card' | 'wallet' | 'cod'
  cardNumber?: string
  cardExpiry?: string
  cardCvv?: string
}

export interface PlacedOrder {
  orderId: string
  items: CartItem[]
  subtotal: number
  shippingCost: number
  discountAmount: number
  total: number
  shippingDetails: CheckoutFormData
  createdAt: string
  estimatedDeliveryDate: string
}

export interface ToyFilterState {
  searchQuery: string
  category: ToyCategory | 'all'
  tcgSeries: TcgSubCategory | 'all'
  ageGroup: AgeGroup | 'all'
  priceRange: [number, number]
  minRating: number
  sortBy: 'featured' | 'price-asc' | 'price-desc' | 'rating' | 'newest'
  onlyInStock: boolean
  onlyDiscounted: boolean
}

export interface GiftAdvisorAnswer {
  ageGroup?: AgeGroup
  interest?: string
  tcgSeries?: TcgSubCategory
  budget?: number
}
