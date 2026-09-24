<script setup lang="ts">
import { ref } from 'vue'
import AppHeader from './shared/components/AppHeader.vue'
import AppFooter from './shared/components/AppFooter.vue'
import CartDrawer from './modules/cart/components/CartDrawer.vue'
import ToyGiftAdvisorModal from './modules/toy-finder/ToyGiftAdvisorModal.vue'

const isAdvisorOpen = ref(false)

const openAdvisorModal = () => {
  isAdvisorOpen.value = true
}

const closeAdvisorModal = () => {
  isAdvisorOpen.value = false
}
</script>

<template>
  <div class="min-h-screen flex flex-col bg-amber-50/40 text-slate-800 selection:bg-red-600 selection:text-white">
    <!-- Header with logo, live search, cart & wishlist counters -->
    <AppHeader @open-advisor="openAdvisorModal" />

    <!-- Main View Viewport -->
    <main class="flex-1">
      <router-view v-slot="{ Component }">
        <transition
          mode="out-in"
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 translate-y-1"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 translate-y-1"
        >
          <component :is="Component" @open-advisor="openAdvisorModal" />
        </transition>
      </router-view>
    </main>

    <!-- Global Cart Drawer Slide-Over -->
    <CartDrawer />

    <!-- Global Poke-Match Advisor Quiz Modal -->
    <ToyGiftAdvisorModal
      :is-open="isAdvisorOpen"
      @close="closeAdvisorModal"
    />

    <!-- Global Pokemon Center Footer -->
    <AppFooter />
  </div>
</template>
