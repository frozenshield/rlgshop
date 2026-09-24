<script setup lang="ts">
import { ref } from 'vue'

const email = ref('')
const subscribed = ref(false)

const handleSubscribe = () => {
  if (email.value.trim()) {
    subscribed.value = true
    email.value = ''
  }
}
</script>

<template>
  <footer class="bg-white border-t border-red-100 mt-20 pt-16 pb-12 font-display">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- 4 Badges Feature Strip -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-6 pb-12 border-b border-red-100">
        <div class="flex items-center gap-3.5 p-3 rounded-2xl bg-red-50/60 border border-red-100">
          <div class="text-3xl">🕊️</div>
          <div>
            <h4 class="text-sm font-bold text-slate-800">Pidgey Express Delivery</h4>
            <p class="text-xs text-slate-500">Free shipping on orders over $50</p>
          </div>
        </div>

        <div class="flex items-center gap-3.5 p-3 rounded-2xl bg-amber-50/60 border border-amber-100">
          <div class="text-3xl">⚡</div>
          <div>
            <h4 class="text-sm font-bold text-slate-800">100% Authentic Pokémon</h4>
            <p class="text-xs text-slate-500">Official Pokémon Center &amp; Jazwares</p>
          </div>
        </div>

        <div class="flex items-center gap-3.5 p-3 rounded-2xl bg-emerald-50/60 border border-emerald-100">
          <div class="text-3xl">🎁</div>
          <div>
            <h4 class="text-sm font-bold text-slate-800">Premier Ball Gift Wrap</h4>
            <p class="text-xs text-slate-500">Custom trainer card &amp; ribbon</p>
          </div>
        </div>

        <div class="flex items-center gap-3.5 p-3 rounded-2xl bg-blue-50/60 border border-blue-100">
          <div class="text-3xl">🛡️</div>
          <div>
            <h4 class="text-sm font-bold text-slate-800">30-Day Trainer Return</h4>
            <p class="text-xs text-slate-500">Hassle-free toy returns guarantee</p>
          </div>
        </div>
      </div>

      <!-- Footer Columns -->
      <div class="grid grid-cols-1 md:grid-cols-5 gap-10 py-12">
        <!-- Brand Info -->
        <div class="md:col-span-2 space-y-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-red-600 to-amber-400 flex items-center justify-center p-2 shadow-sm">
              <svg viewBox="0 0 100 100" class="w-full h-full">
                <circle cx="50" cy="50" r="48" fill="#1E293B" />
                <path d="M 4 50 A 46 46 0 0 1 96 50 Z" fill="#EE1515" />
                <path d="M 4 50 A 46 46 0 0 0 96 50 Z" fill="#FFFFFF" />
                <line x1="4" y1="50" x2="96" y2="50" stroke="#1E293B" stroke-width="8" />
                <circle cx="50" cy="50" r="15" fill="#1E293B" />
                <circle cx="50" cy="50" r="9" fill="#FFFFFF" stroke="#94A3B8" stroke-width="1.5" />
              </svg>
            </div>
            <span class="text-2xl font-extrabold bg-gradient-to-r from-red-600 to-amber-500 bg-clip-text text-transparent">
              RLG Online Shop
            </span>
          </div>
          <p class="text-sm text-slate-500 leading-relaxed max-w-sm">
            Your #1 destination for official Pokémon toys, trading card game booster boxes, huggable plushies, and collector Pokéball replicas. Gotta catch 'em all!
          </p>
          <div class="flex items-center gap-3 text-slate-400 text-lg">
            <span class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center cursor-pointer hover:bg-red-100 hover:text-red-600 transition-colors">⚡</span>
            <span class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center cursor-pointer hover:bg-red-100 hover:text-red-600 transition-colors">🔴</span>
            <span class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center cursor-pointer hover:bg-red-100 hover:text-red-600 transition-colors">🎮</span>
          </div>
        </div>

        <!-- Quick Links -->
        <div>
          <h4 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Categories</h4>
          <ul class="space-y-2 text-sm text-slate-600 font-medium">
            <li><router-link to="/catalog" class="hover:text-red-600 transition-colors">Poké Plushies</router-link></li>
            <li><router-link to="/catalog" class="hover:text-red-600 transition-colors">TCG Booster Packs</router-link></li>
            <li><router-link to="/catalog" class="hover:text-red-600 transition-colors">Battle Action Figures</router-link></li>
            <li><router-link to="/catalog" class="hover:text-red-600 transition-colors">Mega Building Sets</router-link></li>
            <li><router-link to="/catalog" class="hover:text-red-600 transition-colors">Pokéballs &amp; Belts</router-link></li>
          </ul>
        </div>

        <!-- Customer Care -->
        <div>
          <h4 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Trainer Support</h4>
          <ul class="space-y-2 text-sm text-slate-600 font-medium">
            <li><router-link to="/catalog" class="hover:text-red-600 transition-colors">Track Poké-Delivery</router-link></li>
            <li><router-link to="/wishlist" class="hover:text-red-600 transition-colors">Saved Pokémon Wishlist</router-link></li>
            <li><a href="#" class="hover:text-red-600 transition-colors">Card Authenticity Guide</a></li>
            <li><a href="#" class="hover:text-red-600 transition-colors">Shipping &amp; Returns</a></li>
            <li><a href="#" class="hover:text-red-600 transition-colors">Trainer FAQs</a></li>
          </ul>
        </div>

        <!-- Newsletter Subscription -->
        <div>
          <h4 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Join the Trainer Club</h4>
          <p class="text-xs text-slate-500 mb-3">Get secret drop alerts for rare Charizard boxes and exclusive coupons.</p>
          <form v-if="!subscribed" @submit.prevent="handleSubscribe" class="space-y-2">
            <input
              v-model="email"
              type="email"
              placeholder="trainer@pokemon.com"
              required
              class="w-full text-xs px-3.5 py-2.5 bg-slate-100 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-red-200"
            />
            <button
              type="submit"
              class="w-full bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2.5 rounded-xl shadow-sm transition-all cursor-pointer"
            >
              Catch 15% Off Code ⚡
            </button>
          </form>
          <div v-else class="p-3 bg-emerald-50 rounded-xl border border-emerald-200 text-xs font-bold text-emerald-700">
            🎉 Welcome Trainer! Check your inbox for your 15% off code.
          </div>
        </div>
      </div>

      <!-- Copyright Bar -->
      <div class="pt-8 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-400 gap-4">
        <p>&copy; {{ new Date().getFullYear() }} RLG Online Shop. All Pokémon character names and images are trademarks of Nintendo / Creatures Inc. / GAME FREAK.</p>
        <p class="flex items-center gap-1 font-semibold text-slate-500">
          RLG Online Shop &bull; Built with ⚡ Vue 3 &amp; TypeScript
        </p>
      </div>
    </div>
  </footer>
</template>
