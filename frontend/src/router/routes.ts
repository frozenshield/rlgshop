import type { RouteRecordRaw } from 'vue-router'

const HomeView = () => import('@/modules/home/HomeView.vue')
const ToyCatalogView = () => import('@/modules/catalog/ToyCatalogView.vue')
const CheckoutView = () => import('@/modules/checkout/CheckoutView.vue')
const WishlistView = () => import('@/modules/wishlist/WishlistView.vue')

export const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'home',
    component: HomeView,
    meta: {
      title: 'Home | RLG Online Shop - Pokémon Toys & TCG Collectibles',
    },
  },
  {
    path: '/catalog',
    name: 'catalog',
    component: ToyCatalogView,
    meta: {
      title: 'All Pokémon Toys, Cards & Gear | RLG Online Shop',
    },
  },
  {
    path: '/wishlist',
    name: 'wishlist',
    component: WishlistView,
    meta: {
      title: 'Trainer Wishlist | RLG Online Shop',
    },
  },
  {
    path: '/checkout',
    name: 'checkout',
    component: CheckoutView,
    meta: {
      title: 'Express Poké-Checkout | RLG Online Shop',
    },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  },
]
