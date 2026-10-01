<script setup lang="ts">
import { ref } from "vue";
import { useCmsStore } from "@/modules/cms/cms.store";

const cmsStore = useCmsStore();

const email = ref("");
const subscribed = ref(false);

const handleSubscribe = () => {
  if (email.value.trim()) {
    subscribed.value = true;
    email.value = "";
  }
};
</script>

<template>
  <footer
    class="bg-white border-t border-slate-200 mt-20 pt-16 pb-12 font-display"
  >
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- 4 Badges Feature Strip -->
      <div
        class="grid grid-cols-2 md:grid-cols-4 gap-6 pb-12 border-b border-slate-200/80"
      >
        <div
          class="flex items-center gap-3.5 p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 cursor-pointer hover:bg-slate-100 transition-colors"
          @click="cmsStore.openPageModal('faq')"
        >
          <div class="text-3xl">🚚</div>
          <div>
            <h4 class="text-sm font-bold text-slate-900">
              Fast Nationwide Shipping
            </h4>
            <p class="text-xs text-slate-500">
              Free delivery on orders over ₱2,500
            </p>
          </div>
        </div>

        <div
          class="flex items-center gap-3.5 p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 cursor-pointer hover:bg-slate-100 transition-colors"
          @click="cmsStore.openPageModal('about')"
        >
          <div class="text-3xl">🛡️</div>
          <div>
            <h4 class="text-sm font-bold text-slate-900">
              100% Authentic Imports
            </h4>
            <p class="text-xs text-slate-500">
              Official Bandai, Pokémon &amp; Good Smile
            </p>
          </div>
        </div>

        <div
          class="flex items-center gap-3.5 p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 cursor-pointer hover:bg-slate-100 transition-colors"
          @click="cmsStore.openPageModal('terms')"
        >
          <div class="text-3xl">📦</div>
          <div>
            <h4 class="text-sm font-bold text-slate-900">
              Collector-Grade Packaging
            </h4>
            <p class="text-xs text-slate-500">
              Double-boxed with corner protectors
            </p>
          </div>
        </div>

        <div
          class="flex items-center gap-3.5 p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 cursor-pointer hover:bg-slate-100 transition-colors"
          @click="cmsStore.openPageModal('terms')"
        >
          <div class="text-3xl">🤝</div>
          <div>
            <h4 class="text-sm font-bold text-slate-900">
              Collector Guarantee
            </h4>
            <p class="text-xs text-slate-500">
              Hassle-free support &amp; mint delivery
            </p>
          </div>
        </div>
      </div>

      <!-- Footer Columns -->
      <div class="grid grid-cols-1 md:grid-cols-5 gap-10 py-12">
        <!-- Brand Info -->
        <div class="md:col-span-2 space-y-4">
          <div class="flex items-center gap-3">
            <img
              src="/logo.png"
              alt="RLG Online Shop Logo"
              class="w-12 h-12 object-contain drop-shadow-sm"
            />
            <span class="text-2xl font-black text-slate-900 tracking-tight">
              RLG <span class="text-rose-600">ONLINE SHOP</span>
            </span>
          </div>
          <p class="text-sm text-slate-500 leading-relaxed max-w-sm">
            Your premier online hobby shop for factory-sealed TCG booster boxes,
            authentic Japanese Bandai Gunpla model kits, collectible scale
            figures, and protective card accessories.
          </p>
          <div class="flex items-center gap-3 text-slate-400 text-lg">
            <button
              type="button"
              class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center cursor-pointer hover:bg-rose-100 hover:text-rose-600 transition-colors"
              title="About RLG Hobby Shop"
              @click="cmsStore.openPageModal('about')"
            >
              🃏
            </button>
            <button
              type="button"
              class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center cursor-pointer hover:bg-rose-100 hover:text-rose-600 transition-colors"
              title="Gunpla & Model Kits"
              @click="cmsStore.openPageModal('about')"
            >
              🤖
            </button>
            <button
              type="button"
              class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center cursor-pointer hover:bg-rose-100 hover:text-rose-600 transition-colors"
              title="Authenticity Guarantee"
              @click="cmsStore.openPageModal('terms')"
            >
              🛡️
            </button>
          </div>
        </div>

        <!-- Quick Links -->
        <div>
          <h4
            class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4"
          >
            Hobby Vault
          </h4>
          <ul class="space-y-2 text-sm text-slate-600 font-medium">
            <li>
              <router-link
                to="/catalog"
                class="hover:text-rose-600 transition-colors"
                >TCG Booster Boxes</router-link
              >
            </li>
            <li>
              <router-link
                to="/catalog"
                class="hover:text-rose-600 transition-colors"
                >Gunpla &amp; Model Kits</router-link
              >
            </li>
            <li>
              <router-link
                to="/catalog"
                class="hover:text-rose-600 transition-colors"
                >Scale Anime Figures</router-link
              >
            </li>
            <li>
              <router-link
                to="/catalog"
                class="hover:text-rose-600 transition-colors"
                >Deck Boxes &amp; Sleeves</router-link
              >
            </li>
            <li>
              <router-link
                to="/catalog"
                class="hover:text-rose-600 transition-colors"
                >Collector Toploaders</router-link
              >
            </li>
          </ul>
        </div>

        <!-- Customer Care & CMS Pages -->
        <div>
          <h4
            class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4"
          >
            Collector Care
          </h4>
          <ul class="space-y-2 text-sm text-slate-600 font-medium">
            <li>
              <router-link
                to="/orders"
                class="hover:text-rose-600 transition-colors"
                >Track Order Dispatch</router-link
              >
            </li>
            <li>
              <router-link
                to="/wishlist"
                class="hover:text-rose-600 transition-colors"
                >Saved Wishlist</router-link
              >
            </li>
            <li>
              <button
                type="button"
                class="hover:text-rose-600 transition-colors text-left cursor-pointer"
                @click="cmsStore.openPageModal('about')"
              >
                About Our Vault
              </button>
            </li>
            <li>
              <button
                type="button"
                class="hover:text-rose-600 transition-colors text-left cursor-pointer"
                @click="cmsStore.openPageModal('terms')"
              >
                Terms &amp; Authenticity
              </button>
            </li>
            <li>
              <button
                type="button"
                class="hover:text-rose-600 transition-colors text-left cursor-pointer"
                @click="cmsStore.openPageModal('privacy')"
              >
                Privacy Policy
              </button>
            </li>
            <li>
              <button
                type="button"
                class="hover:text-rose-600 transition-colors text-left cursor-pointer"
                @click="cmsStore.openPageModal('faq')"
              >
                Collector FAQs
              </button>
            </li>
            <li>
              <router-link
                to="/admin/login"
                class="text-slate-400 hover:text-slate-900 transition-colors flex items-center gap-1 font-bold text-xs pt-2"
                ><span>Staff Portal &bull; Login</span>
                <span
                  class="text-[9px] bg-slate-900 text-white px-1.5 py-0.2 rounded font-mono"
                  >ADMIN</span
                ></router-link
              >
            </li>
          </ul>
        </div>

        <!-- Newsletter Subscription -->
        <div>
          <h4
            class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4"
          >
            Collector VIP Club
          </h4>
          <p class="text-xs text-slate-500 mb-3">
            Get priority notifications on rare booster box restocks and new
            Gunpla pre-orders.
          </p>
          <form
            v-if="!subscribed"
            @submit.prevent="handleSubscribe"
            class="space-y-2"
          >
            <input
              v-model="email"
              type="email"
              placeholder="collector@hobby.com"
              required
              class="w-full text-xs px-3.5 py-2.5 bg-slate-100 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-200 font-medium"
            />
            <button
              type="submit"
              class="w-full bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold py-2.5 rounded-xl shadow-xs transition-all cursor-pointer"
            >
              Get 10% Off Code ⚡
            </button>
          </form>
          <div
            v-else
            class="p-3 bg-emerald-50 rounded-xl border border-emerald-200 text-xs font-bold text-emerald-700"
          >
            🎉 Welcome Collector! Check your inbox for your 10% off code.
          </div>
        </div>
      </div>

      <!-- Copyright Bar -->
      <div
        class="pt-8 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-400 gap-4"
      >
        <p>
          &copy; {{ new Date().getFullYear() }} RLG Hobby Shop. All brand
          trademarks (Bandai, The Pokémon Company, Bushiroad, Good Smile) belong
          to their respective owners.
        </p>
        <p class="flex items-center gap-1 font-semibold text-slate-500">
          RLG Hobby Shop &bull; Built with ⚡ Vue 3, PrimeVue &amp; Tailwind
        </p>
      </div>
    </div>
  </footer>
</template>
