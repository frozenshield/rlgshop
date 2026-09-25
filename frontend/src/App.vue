<script setup lang="ts">
import { ref, computed } from "vue";
import { useRoute } from "vue-router";
import AppHeader from "./shared/components/AppHeader.vue";
import AppFooter from "./shared/components/AppFooter.vue";
import CartDrawer from "./modules/cart/components/CartDrawer.vue";
import ToyGiftAdvisorModal from "./modules/toy-finder/ToyGiftAdvisorModal.vue";
import AuthModal from "./modules/auth/components/AuthModal.vue";
import UserProfileModal from "./modules/auth/components/UserProfileModal.vue";
import UserSettingsModal from "./modules/auth/components/UserSettingsModal.vue";
import AppToast from "./shared/components/AppToast.vue";
import ToyShopParallaxBackground from "./shared/components/ToyShopParallaxBackground.vue";

const route = useRoute();
const isAdminRoute = computed(() => route.path.startsWith("/admin"));

const isAdvisorOpen = ref(false);
const isAuthOpen = ref(false);
const isProfileOpen = ref(false);
const isSettingsOpen = ref(false);

const toastVisible = ref(false);
const toastMessage = ref("");
const toastType = ref<"success" | "error" | "info">("success");

const showToast = (
  msg: string,
  type: "success" | "error" | "info" = "success",
) => {
  toastMessage.value = msg;
  toastType.value = type;
  toastVisible.value = true;
};

const openAdvisorModal = () => {
  isAdvisorOpen.value = true;
};

const closeAdvisorModal = () => {
  isAdvisorOpen.value = false;
};
</script>

<template>
  <div
    class="min-h-screen flex flex-col bg-[#090d16] text-slate-100 selection:bg-amber-400 selection:text-slate-950 relative font-display"
  >
    <!-- Toy Shop Parallax Background -->
    <ToyShopParallaxBackground v-if="!isAdminRoute" />

    <!-- Header with logo, live search, cart & wishlist counters -->
    <AppHeader
      v-if="!isAdminRoute"
      class="relative z-30"
      @open-advisor="openAdvisorModal"
      @open-auth="isAuthOpen = true"
      @open-profile="isProfileOpen = true"
      @open-settings="isSettingsOpen = true"
      @logout="showToast('Signed out successfully. See you again! 👋', 'info')"
    />

    <!-- Main View Viewport -->
    <main class="flex-1 relative z-10">
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
      @success="(msg: string) => showToast(msg, 'success')"
    />

    <!-- Collector Profile Modal -->
    <UserProfileModal
      v-if="!isAdminRoute"
      :is-open="isProfileOpen"
      @close="isProfileOpen = false"
      @saved="(msg: string) => showToast(msg, 'success')"
    />

    <!-- Collector Settings Modal -->
    <UserSettingsModal
      v-if="!isAdminRoute"
      :is-open="isSettingsOpen"
      @close="isSettingsOpen = false"
      @saved="(msg: string) => showToast(msg, 'success')"
    />

    <!-- Global Toast Notifications -->
    <AppToast
      :is-visible="toastVisible"
      :message="toastMessage"
      :type="toastType"
      @close="toastVisible = false"
    />

    <!-- Global Storefront Footer -->
    <AppFooter v-if="!isAdminRoute" class="relative z-20" />
  </div>
</template>
