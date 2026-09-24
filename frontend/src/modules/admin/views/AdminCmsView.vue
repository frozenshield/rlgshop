<script setup lang="ts">
import { ref } from 'vue'
import { useAdminStore } from '../admin.store'

const adminStore = useAdminStore()

const activeTab = ref<'banners' | 'pages' | 'blog'>('banners')
const saveStatus = ref('')

// Static Pages Content
const selectedPage = ref<'about' | 'terms' | 'privacy' | 'faq'>('about')
const pageContent = ref({
  about: `Welcome to RLG Hobby Shop, the Philippines' leading destination for authentic Japanese hobby imports, factory-sealed Trading Card Game booster boxes, Bandai Gunpla model kits, and collector display figures.\n\nFounded in 2024 by passionate hobbyists, we guarantee 100% genuine products with double-boxed collector-grade shipping armor nationwide.`,
  terms: `All sales of factory-sealed booster boxes and graded cards are authentic. Once security seals or plastic wraps are opened or tampered with, returns cannot be accepted due to secondary market volatility.\n\nDeliveries are handled by accredited Philippine air and land logistics couriers with insured tracking.`,
  privacy: `RLG Hobby Shop respects your data privacy. We only collect shipping addresses, email addresses, and phone numbers necessary to deliver your hobby parcels safely. We never sell your personal information to third parties.`,
  faq: `Q: Are all TCG booster boxes factory sealed?\nA: Yes! All Pokémon, One Piece, Hololive, and Weiß Schwarz booster boxes carry intact manufacturer shrink-wrap and security tape.\n\nQ: How fast is nationwide shipping?\nA: Metro Manila orders arrive in 1-2 business days; Provincial Luzon, Visayas, and Mindanao arrive in 3-5 business days.`,
})

// Blog Articles
const blogPosts = ref([
  {
    id: 'post-1',
    title: 'Top 5 One Piece Card Game Meta Decks in OP-07 Egghead',
    category: 'TCG Strategy',
    author: 'Chief Deck Architect',
    date: '2026-09-20',
    status: 'Published',
    summary: 'A deep dive into Yellow Vegapunk, Blue Doflamingo, and Green Bonney tournament tier lists.',
  },
  {
    id: 'post-2',
    title: 'Beginner Guide: Essential Tools for Building Your First RG Gunpla',
    category: 'Gunpla & Modeling',
    author: 'Master Builder Ken',
    date: '2026-09-15',
    status: 'Published',
    summary: 'Single blade nippers, sanding sponges, panel lining markers, and topcoat finishes explained.',
  },
])

// Blog Editor State
const isEditingBlog = ref(false)
const blogTitle = ref('')
const blogCategory = ref('TCG Strategy')
const blogBody = ref('')

const openNewPost = () => {
  blogTitle.value = ''
  blogCategory.value = 'TCG Strategy'
  blogBody.value = ''
  isEditingBlog.value = true
}

const savePost = () => {
  if (blogTitle.value.trim()) {
    blogPosts.value.unshift({
      id: 'post-' + (blogPosts.value.length + 1),
      title: blogTitle.value,
      category: blogCategory.value,
      author: 'RLG Editorial Staff',
      date: new Date().toISOString().slice(0, 10),
      status: 'Published',
      summary: blogBody.value.slice(0, 100) + '...',
    })
    isEditingBlog.value = false
    triggerSaveAlert('New blog article published to storefront!')
  }
}

const triggerSaveAlert = (msg: string) => {
  saveStatus.value = msg
  setTimeout(() => {
    saveStatus.value = ''
  }, 3500)
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Content Management System (CMS)</h1>
        <p class="text-xs text-slate-500">Edit storefront hero banners, manage static policy pages, and author community blog articles.</p>
      </div>

      <!-- Tabs -->
      <div class="flex items-center gap-1 p-1 bg-slate-100 rounded-xl border border-slate-200">
        <button
          type="button"
          class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer"
          :class="activeTab === 'banners' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
          @click="activeTab = 'banners'"
        >
          🎨 Store Banners
        </button>
        <button
          type="button"
          class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer"
          :class="activeTab === 'pages' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
          @click="activeTab = 'pages'"
        >
          📄 Static Pages
        </button>
        <button
          type="button"
          class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer"
          :class="activeTab === 'blog' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
          @click="activeTab = 'blog'"
        >
          ✍️ Blog &amp; Guides
        </button>
      </div>
    </div>

    <!-- Alert Message -->
    <div v-if="saveStatus" class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-xl animate-fade-in">
      ✓ {{ saveStatus }}
    </div>

    <!-- TAB 1: Store Banners -->
    <div v-if="activeTab === 'banners'" class="space-y-4">
      <div class="grid grid-cols-1 gap-6">
        <div
          v-for="banner in adminStore.cmsBanners"
          :key="banner.id"
          class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4"
        >
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-rose-600 bg-rose-50 px-2.5 py-0.5 rounded-md border border-rose-200">
              Banner ID: {{ banner.id }}
            </span>
            <label class="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
              <input v-model="banner.isActive" type="checkbox" class="accent-rose-600 rounded" />
              <span>Visible on Storefront</span>
            </label>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-3">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Banner Headline</label>
                <input v-model="banner.title" type="text" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 font-bold" />
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Badge Text</label>
                <input v-model="banner.badgeText" type="text" class="w-full text-xs p-2.5 rounded-xl border border-slate-200" />
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Subtitle / Body Copy</label>
                <textarea v-model="banner.subtitle" rows="2" class="w-full text-xs p-2.5 rounded-xl border border-slate-200"></textarea>
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Button Text</label>
                  <input v-model="banner.ctaText" type="text" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 font-bold" />
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Target Link</label>
                  <input v-model="banner.ctaLink" type="text" class="w-full text-xs p-2.5 rounded-xl border border-slate-200" />
                </div>
              </div>
            </div>

            <!-- Banner Visual Preview -->
            <div class="space-y-2">
              <label class="block text-xs font-bold text-slate-700">Image Asset Preview</label>
              <div class="relative aspect-video rounded-xl overflow-hidden border border-slate-200 bg-slate-900 flex items-center justify-center">
                <img :src="banner.imageUrl" :alt="banner.title" class="w-full h-full object-cover opacity-80" />
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent p-4 flex flex-col justify-end text-white text-left font-display">
                  <span class="text-[10px] font-bold text-rose-400 uppercase">{{ banner.badgeText }}</span>
                  <h4 class="text-sm font-black line-clamp-1">{{ banner.title }}</h4>
                  <p class="text-[10px] text-slate-300 line-clamp-1">{{ banner.subtitle }}</p>
                </div>
              </div>
              <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Change Image URL</label>
                <input v-model="banner.imageUrl" type="text" class="w-full text-xs p-2 rounded-xl border border-slate-200 text-slate-600 font-mono" />
              </div>
            </div>
          </div>

          <div class="flex justify-end pt-2 border-t border-slate-100">
            <button
              type="button"
              class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-xs cursor-pointer"
              @click="triggerSaveAlert('Banner updated and deployed to storefront!')"
            >
              Save Banner Changes
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 2: Static Legal & Policy Pages -->
    <div v-else-if="activeTab === 'pages'" class="space-y-4">
      <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Storefront Static Pages &amp; Policies</h2>
            <p class="text-xs text-slate-500">Edit legal disclaimers, about story, return policy, and customer FAQs</p>
          </div>

          <!-- Page Tabs -->
          <div class="flex gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200">
            <button
              v-for="p in (['about', 'terms', 'privacy', 'faq'] as const)"
              :key="p"
              type="button"
              class="px-3 py-1 rounded-lg text-xs font-bold uppercase transition-all cursor-pointer"
              :class="selectedPage === p ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500'"
              @click="selectedPage = p"
            >
              {{ p }}
            </button>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">
            Content Editor for /{{ selectedPage }}
          </label>
          <textarea
            v-model="pageContent[selectedPage]"
            rows="10"
            class="w-full text-xs p-4 rounded-xl border border-slate-200 font-mono leading-relaxed focus:outline-none focus:border-slate-900"
          ></textarea>
        </div>

        <div class="flex justify-end">
          <button
            type="button"
            class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-xs cursor-pointer"
            @click="triggerSaveAlert(`Page /${selectedPage} updated and published!`)"
          >
            Publish Page Updates
          </button>
        </div>
      </div>
    </div>

    <!-- TAB 3: Community Blog & SEO Articles -->
    <div v-else class="space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Hobby Guides &amp; Articles</h2>
          <p class="text-xs text-slate-500">Publish guides, meta tournament deck reviews, and Gunpla build tutorials</p>
        </div>
        <button
          type="button"
          class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold shadow-xs cursor-pointer"
          @click="openNewPost"
        >
          + Write New Article
        </button>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
        <div v-for="post in blogPosts" :key="post.id" class="p-5 flex items-center justify-between gap-4">
          <div class="space-y-1">
            <div class="flex items-center gap-2">
              <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">
                {{ post.category }}
              </span>
              <h3 class="text-xs font-extrabold text-slate-900">{{ post.title }}</h3>
            </div>
            <p class="text-xs text-slate-500">{{ post.summary }}</p>
            <div class="flex items-center gap-2 text-[11px] text-slate-400">
              <span>By {{ post.author }}</span>
              <span>&bull;</span>
              <span>{{ post.date }}</span>
              <span>&bull;</span>
              <span class="text-emerald-600 font-bold">{{ post.status }}</span>
            </div>
          </div>
          <button type="button" class="text-xs font-bold text-slate-600 hover:text-slate-900 cursor-pointer">
            Edit &rarr;
          </button>
        </div>
      </div>
    </div>

    <!-- Write Post Modal -->
    <teleport to="body">
      <div v-if="isEditingBlog" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-xl w-full p-6 space-y-4 shadow-2xl border border-slate-200 font-display">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <h3 class="text-base font-bold text-slate-900">Author New Collector Article</h3>
              <p class="text-xs text-slate-500">Draft content for community engagement and organic SEO traffic</p>
            </div>
            <button type="button" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center cursor-pointer" @click="isEditingBlog = false">
              ✕
            </button>
          </div>

          <div class="space-y-3 text-xs">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Article Headline Title</label>
              <input v-model="blogTitle" type="text" placeholder="e.g. How to Protect Your Cards from Humidity in the PH" class="w-full p-2.5 rounded-xl border border-slate-300 font-bold" />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Category</label>
              <select v-model="blogCategory" class="w-full p-2.5 rounded-xl border border-slate-300 bg-white">
                <option value="TCG Strategy">TCG Strategy</option>
                <option value="Gunpla & Modeling">Gunpla & Modeling</option>
                <option value="Care & Preservation">Care & Preservation</option>
                <option value="Market Trends">Market Trends & Collectibles</option>
              </select>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Article Body Content (Markdown supported)</label>
              <textarea v-model="blogBody" rows="6" placeholder="Write comprehensive guide here..." class="w-full p-3 rounded-xl border border-slate-300"></textarea>
            </div>
          </div>

          <div class="flex gap-3 pt-2">
            <button type="button" class="flex-1 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs" @click="isEditingBlog = false">
              Cancel
            </button>
            <button type="button" class="flex-1 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs" @click="savePost">
              Publish Article
            </button>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>
