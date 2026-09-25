<script setup lang="ts">
import { ref, computed } from "vue";
import { useRoute } from "vue-router";
import AppHeader from "./shared/components/AppHeader.vue";
import AppFooter from "./shared/components/AppFooter.vue";
import CartDrawer from "./modules/cart/components/CartDrawer.vue";
import ToyGiftAdvisorModal from "./modules/toy-finder/ToyGiftAdvisorModal.vue";
import AuthModal from "./modules/auth/components/AuthModal.vue";

const route = useRoute();
const isAdminRoute = computed(() => route.path.startsWith("/admin"));

const isAdvisorOpen = ref(false);
const isAuthOpen = ref(false);

const openAdvisorModal = () => {
  isAdvisorOpen.value = true;
};

const closeAdvisorModal = () => {
  isAdvisorOpen.value = false;
};
</script>

<template>
  <div
    class="min-h-screen flex flex-col bg-slate-50/80 text-slate-800 selection:bg-rose-600 selection:text-white"
  >
    <!-- Header with logo, live search, cart & wishlist counters -->
    <AppHeader
      v-if="!isAdminRoute"
      @open-advisor="openAdvisorModal"
      @open-auth="isAuthOpen = true"
    />

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
    <CartDrawer v-if="!isAdminRoute" />

    <!-- Global Hobby Matcher Advisor Quiz Modal -->
    <ToyGiftAdvisorModal
      v-if="!isAdminRoute"
      :is-open="isAdvisorOpen"
      @close="closeAdvisorModal"
    />

    <!-- Global Auth Modal (Sign In / Register) -->
    <AuthModal
      v-if="!isAdminRoute"
      :is-open="isAuthOpen"
      @close="isAuthOpen = false"
    />

    <!-- Global Storefront Footer -->
    <AppFooter v-if="!isAdminRoute" />
  </div>
</template>
