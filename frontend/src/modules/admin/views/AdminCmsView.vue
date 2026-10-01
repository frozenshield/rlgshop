<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useCmsStore } from '@/modules/cms/cms.store'
import type { CMSBanner, CMSBlogPost } from '@/modules/cms/cms.types'

const cmsStore = useCmsStore()

const activeTab = ref<'banners' | 'pages' | 'blog'>('banners')
const saveStatus = ref('')
const isError = ref(false)

// Static Pages Content selector
const selectedPage = ref<'about' | 'terms' | 'privacy' | 'faq'>('about')

// Blog Editor State
const isEditingBlog = ref(false)
const editingPostId = ref<string | null>(null)
const blogTitle = ref('')
const blogCategory = ref('TCG Strategy')
const blogAuthor = ref('Chief Deck Architect')
const blogSummary = ref('')
const blogContent = ref('')

onMounted(async () => {
  await cmsStore.fetchCmsData()
})

const triggerSaveAlert = (msg: string, error = false) => {
  saveStatus.value = msg
  isError.value = error
  setTimeout(() => {
    saveStatus.value = ''
    isError.value = false
  }, 4000)
}

// ─── Banner Actions ──────────────────────────────────────────────────────────
const saveBanners = async () => {
  const success = await cmsStore.saveBanners(cmsStore.banners)
  if (success) {
    triggerSaveAlert('Banners updated and deployed to storefront in real-time!')
  } else {
    triggerSaveAlert('Saved locally (backend sync had an issue, cached for offline)', false)
  }
}

const addNewBanner = () => {
  const newId = 'bnr-' + (cmsStore.banners.length + 1)
  cmsStore.banners.push({
    id: newId,
    title: 'New Collector Drop Announcement',
    subtitle: 'Limited release Japanese imports, booster boxes, and exclusive model kits.',
    badgeText: 'HOT NEW DROP 🔥',
    ctaText: 'Explore Now',
    ctaLink: '/catalog',
    imageUrl: 'https://images.unsplash.com/photo-1613771404784-3a5686aa2be3?w=700&auto=format&fit=crop&q=80',
    isActive: true,
  })
  triggerSaveAlert(`New banner slot #${newId} created. Don't forget to save changes!`)
}

const removeBanner = (index: number) => {
  if (cmsStore.banners.length <= 1) {
    alert('At least one banner slot must be preserved.')
    return
  }
  if (confirm('Are you sure you want to remove this banner?')) {
    cmsStore.banners.splice(index, 1)
    saveBanners()
  }
}

// ─── Static Pages Actions ────────────────────────────────────────────────────
const saveCurrentPage = async () => {
  const success = await cmsStore.savePages(cmsStore.pages)
  if (success) {
    triggerSaveAlert(`Page /${selectedPage.value} published and live on storefront!`)
  } else {
    triggerSaveAlert(`Page /${selectedPage.value} updated locally!`)
  }
}

// ─── Blog Articles Actions ───────────────────────────────────────────────────
const openNewPost = () => {
  editingPostId.value = null
  blogTitle.value = ''
  blogCategory.value = 'TCG Strategy'
  blogAuthor.value = 'RLG Editorial Staff'
  blogSummary.value = ''
  blogContent.value = ''
  isEditingBlog.value = true
}

const openEditPost = (post: CMSBlogPost) => {
  editingPostId.value = post.id
  blogTitle.value = post.title
  blogCategory.value = post.category
  blogAuthor.value = post.author
  blogSummary.value = post.summary
  blogContent.value = post.content || post.summary
  isEditingBlog.value = true
}

const savePost = async () => {
  if (!blogTitle.value.trim()) return

  if (editingPostId.value) {
    // Update existing post
    const index = cmsStore.blogs.findIndex((b) => b.id === editingPostId.value)
    if (index !== -1) {
      cmsStore.blogs[index] = {
        ...cmsStore.blogs[index],
        title: blogTitle.value.trim(),
        category: blogCategory.value,
        author: blogAuthor.value.trim() || 'RLG Editorial Staff',
        summary: blogSummary.value.trim() || blogContent.value.slice(0, 120) + '...',
        content: blogContent.value.trim() || blogSummary.value.trim(),
      }
    }
    triggerSaveAlert('Article updated and saved to storefront!')
  } else {
    // Add new post
    cmsStore.blogs.unshift({
      id: 'post-' + Date.now(),
      title: blogTitle.value.trim(),
      category: blogCategory.value,
      author: blogAuthor.value.trim() || 'RLG Editorial Staff',
      date: new Date().toISOString().slice(0, 10),
      status: 'Published',
      summary: blogSummary.value.trim() || blogContent.value.slice(0, 120) + '...',
      content: blogContent.value.trim() || blogSummary.value.trim(),
    })
    triggerSaveAlert('New article published and live on storefront!')
  }

  await cmsStore.saveBlogs(cmsStore.blogs)
  isEditingBlog.value = false
}

const deletePost = async (id: string) => {
  if (confirm('Are you sure you want to delete this article?')) {
    cmsStore.blogs = cmsStore.blogs.filter((b) => b.id !== id)
    await cmsStore.saveBlogs(cmsStore.blogs)
    triggerSaveAlert('Article removed.')
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
      <div>
        <div class="flex items-center gap-2">
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Content Management System (CMS)</h1>
          <span class="text-[10px] font-black px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase">
            Live Storefront Sync
          </span>
        </div>
        <p class="text-xs text-slate-500 mt-1">
          Changes made here immediately update the storefront hero banners, promotional coupons, legal policy pages, and blogs.
        </p>
      </div>

      <!-- Tabs -->
      <div class="flex items-center gap-1 p-1 bg-slate-100 rounded-xl border border-slate-200">
        <button
          type="button"
          class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5"
          :class="activeTab === 'banners' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
          @click="activeTab = 'banners'"
        >
          <span>🎨</span>
          <span>Store Banners</span>
          <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-slate-200 text-slate-700">{{ cmsStore.banners.length }}</span>
        </button>
        <button
          type="button"
          class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5"
          :class="activeTab === 'pages' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
          @click="activeTab = 'pages'"
        >
          <span>📄</span>
          <span>Static Pages</span>
        </button>
        <button
          type="button"
          class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5"
          :class="activeTab === 'blog' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
          @click="activeTab = 'blog'"
        >
          <span>✍️</span>
          <span>Blog &amp; Guides</span>
          <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-slate-200 text-slate-700">{{ cmsStore.blogs.length }}</span>
        </button>
      </div>
    </div>

    <!-- Alert Message -->
    <div
      v-if="saveStatus"
      class="p-4 rounded-xl text-xs font-bold flex items-center justify-between shadow-sm transition-all animate-fade-in"
      :class="isError ? 'bg-rose-50 border border-rose-200 text-rose-800' : 'bg-emerald-50 border border-emerald-200 text-emerald-800'"
    >
      <div class="flex items-center gap-2">
        <span>{{ isError ? '❌' : '✓' }}</span>
        <span>{{ saveStatus }}</span>
      </div>
      <button type="button" class="text-slate-400 hover:text-slate-600 font-bold" @click="saveStatus = ''">✕</button>
    </div>

    <!-- TAB 1: Store Banners -->
    <div v-if="activeTab === 'banners'" class="space-y-6">
      <div class="flex items-center justify-between bg-slate-50 p-4 rounded-xl border border-slate-200">
        <div>
          <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Storefront Banner Slots</h2>
          <p class="text-[11px] text-slate-500">Banner 1 controls the top Hero Banner. Banner 2 controls the Promotional Collector Coupon strip.</p>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 transition-colors cursor-pointer"
            @click="addNewBanner"
          >
            + Add Banner Slot
          </button>
          <button
            type="button"
            class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-xs transition-colors cursor-pointer flex items-center gap-2"
            :disabled="cmsStore.isSaving"
            @click="saveBanners"
          >
            <span v-if="cmsStore.isSaving" class="animate-spin text-amber-400">⚡</span>
            <span>Deploy Banners to Storefront</span>
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-6">
        <div
          v-for="(banner, idx) in cmsStore.banners"
          :key="banner.id"
          class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4"
        >
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2">
              <span class="text-xs font-bold text-rose-600 bg-rose-50 px-2.5 py-0.5 rounded-md border border-rose-200">
                Slot #{{ idx + 1 }} &bull; {{ banner.id }}
              </span>
              <span v-if="idx === 0" class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">
                Primary Hero Banner
              </span>
              <span v-else-if="idx === 1" class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200">
                Promo Strip Banner
              </span>
            </div>

            <div class="flex items-center gap-4">
              <label class="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer select-none">
                <input
                  v-model="banner.isActive"
                  type="checkbox"
                  class="accent-rose-600 w-4 h-4 rounded"
                />
                <span :class="banner.isActive ? 'text-emerald-700 font-black' : 'text-slate-400'">
                  {{ banner.isActive ? 'Visible on Storefront' : 'Hidden on Storefront' }}
                </span>
              </label>

              <button
                v-if="cmsStore.banners.length > 1"
                type="button"
                class="text-xs text-slate-400 hover:text-rose-600 font-bold px-2 py-1 transition-colors cursor-pointer"
                title="Delete this banner"
                @click="removeBanner(idx)"
              >
                ✕ Delete
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Form Inputs -->
            <div class="lg:col-span-7 space-y-3">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Banner Headline Title</label>
                <input
                  v-model="banner.title"
                  type="text"
                  placeholder="e.g. Build, Collect & Battle. Your Premier Hobby Store."
                  class="w-full text-xs p-2.5 rounded-xl border border-slate-300 font-bold focus:border-slate-900 focus:outline-none"
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Top Badge Label</label>
                <input
                  v-model="banner.badgeText"
                  type="text"
                  placeholder="e.g. RLG HOBBY SHOP • OFFICIAL IMPORTS VAULT"
                  class="w-full text-xs p-2.5 rounded-xl border border-slate-300 focus:border-slate-900 focus:outline-none font-semibold text-rose-600"
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Subtitle / Descriptive Copy</label>
                <textarea
                  v-model="banner.subtitle"
                  rows="3"
                  placeholder="Detailed subtitle or promo explanation..."
                  class="w-full text-xs p-2.5 rounded-xl border border-slate-300 focus:border-slate-900 focus:outline-none leading-relaxed"
                ></textarea>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Button Action Text</label>
                  <input
                    v-model="banner.ctaText"
                    type="text"
                    placeholder="e.g. Explore All Products"
                    class="w-full text-xs p-2.5 rounded-xl border border-slate-300 font-bold focus:border-slate-900 focus:outline-none"
                  />
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Destination Route Link</label>
                  <input
                    v-model="banner.ctaLink"
                    type="text"
                    placeholder="e.g. /catalog"
                    class="w-full text-xs p-2.5 rounded-xl border border-slate-300 font-mono text-slate-600 focus:border-slate-900 focus:outline-none"
                  />
                </div>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Banner Image Asset URL</label>
                <input
                  v-model="banner.imageUrl"
                  type="text"
                  placeholder="https://..."
                  class="w-full text-xs p-2 rounded-xl border border-slate-300 text-slate-600 font-mono focus:border-slate-900 focus:outline-none"
                />
              </div>
            </div>

            <!-- Real-time Visual Preview -->
            <div class="lg:col-span-5 space-y-2">
              <label class="block text-xs font-bold text-slate-700">Live Visual Preview (Storefront Render)</label>
              <div class="relative aspect-video rounded-2xl overflow-hidden border border-slate-800 bg-slate-950 flex items-center justify-center shadow-lg group">
                <img
                  :src="banner.imageUrl"
                  :alt="banner.title"
                  class="w-full h-full object-cover opacity-75 group-hover:scale-105 transition-transform duration-500"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/50 to-transparent p-4 flex flex-col justify-end text-white text-left font-display">
                  <span class="text-[9px] font-black text-rose-400 uppercase tracking-widest line-clamp-1 mb-1">
                    {{ banner.badgeText || 'BADGE TEXT' }}
                  </span>
                  <h4 class="text-sm font-black line-clamp-2 leading-snug text-white">
                    {{ banner.title || 'Headline Title' }}
                  </h4>
                  <p class="text-[10px] text-slate-300 line-clamp-2 mt-1 leading-tight">
                    {{ banner.subtitle || 'Subtitle body copy...' }}
                  </p>
                  <div class="mt-2.5 flex items-center gap-2">
                    <span class="text-[10px] font-black bg-amber-400 text-slate-950 px-2.5 py-1 rounded-lg">
                      {{ banner.ctaText || 'Button' }} &rarr;
                    </span>
                    <span v-if="!banner.isActive" class="text-[9px] font-bold bg-rose-600/90 text-white px-2 py-0.5 rounded">
                      (Hidden)
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="flex justify-end pt-3 border-t border-slate-100">
            <button
              type="button"
              class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-xs transition-colors cursor-pointer flex items-center gap-2"
              @click="saveBanners"
            >
              <span>Deploy Banner Changes</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 2: Static Legal & Policy Pages -->
    <div v-else-if="activeTab === 'pages'" class="space-y-4">
      <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
          <div>
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Storefront Static Pages &amp; Policies</h2>
            <p class="text-xs text-slate-500">Edit legal disclaimers, about story, return policy, and customer FAQs that are served to customer links</p>
          </div>

          <!-- Page Tabs -->
          <div class="flex gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200">
            <button
              v-for="p in (['about', 'terms', 'privacy', 'faq'] as const)"
              :key="p"
              type="button"
              class="px-3.5 py-1.5 rounded-lg text-xs font-bold uppercase transition-all cursor-pointer"
              :class="selectedPage === p ? 'bg-white text-slate-900 shadow-xs font-black' : 'text-slate-500 hover:text-slate-800'"
              @click="selectedPage = p"
            >
              {{ p }}
            </button>
          </div>
        </div>

        <div>
          <div class="flex items-center justify-between mb-2">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
              Content Editor for: <span class="text-rose-600 font-mono">/{{ selectedPage }}</span>
            </label>
            <span class="text-[11px] text-slate-400">Plain text &amp; multi-line formatting supported</span>
          </div>

          <textarea
            v-model="cmsStore.pages[selectedPage]"
            rows="12"
            class="w-full text-xs p-4 rounded-xl border border-slate-300 font-mono leading-relaxed focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900"
          ></textarea>
        </div>

        <div class="flex items-center justify-between pt-2 border-t border-slate-100">
          <span class="text-xs text-slate-500">
            Clicking publish updates customer links on the storefront footer &amp; legal modals instantly.
          </span>
          <button
            type="button"
            class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-xs transition-colors cursor-pointer flex items-center gap-2"
            :disabled="cmsStore.isSaving"
            @click="saveCurrentPage"
          >
            <span v-if="cmsStore.isSaving" class="animate-spin text-amber-400">⚡</span>
            <span>Publish /{{ selectedPage }} to Storefront</span>
          </button>
        </div>
      </div>
    </div>

    <!-- TAB 3: Community Blog & SEO Articles -->
    <div v-else class="space-y-4">
      <div class="flex items-center justify-between bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Hobby Guides &amp; Articles</h2>
          <p class="text-xs text-slate-500">Publish guides, meta tournament deck reviews, and Gunpla build tutorials displayed on the storefront</p>
        </div>
        <button
          type="button"
          class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold shadow-xs transition-colors cursor-pointer"
          @click="openNewPost"
        >
          + Write New Article
        </button>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
        <div
          v-for="post in cmsStore.blogs"
          :key="post.id"
          class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/50 transition-colors"
        >
          <div class="space-y-1.5 flex-1">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">
                {{ post.category }}
              </span>
              <h3 class="text-xs font-extrabold text-slate-900">{{ post.title }}</h3>
            </div>
            <p class="text-xs text-slate-500 leading-relaxed">{{ post.summary }}</p>
            <div class="flex items-center gap-2 text-[11px] text-slate-400">
              <span>By {{ post.author }}</span>
              <span>&bull;</span>
              <span>{{ post.date }}</span>
              <span>&bull;</span>
              <span class="text-emerald-600 font-bold">{{ post.status }}</span>
            </div>
          </div>

          <div class="flex items-center gap-2 shrink-0">
            <button
              type="button"
              class="text-xs font-bold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-lg transition-colors cursor-pointer"
              @click="openEditPost(post)"
            >
              Edit &rarr;
            </button>
            <button
              type="button"
              class="text-xs font-bold text-rose-500 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-2.5 py-1.5 rounded-lg transition-colors cursor-pointer"
              title="Delete article"
              @click="deletePost(post.id)"
            >
              ✕
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Write / Edit Post Modal -->
    <teleport to="body">
      <div v-if="isEditingBlog" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-xl w-full p-6 space-y-4 shadow-2xl border border-slate-200 font-display">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <h3 class="text-base font-bold text-slate-900">
                {{ editingPostId ? 'Edit Collector Article' : 'Author New Collector Article' }}
              </h3>
              <p class="text-xs text-slate-500">Draft content for community engagement and organic SEO traffic</p>
            </div>
            <button
              type="button"
              class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center cursor-pointer hover:bg-slate-200"
              @click="isEditingBlog = false"
            >
              ✕
            </button>
          </div>

          <div class="space-y-3 text-xs">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Article Headline Title</label>
              <input
                v-model="blogTitle"
                type="text"
                placeholder="e.g. How to Protect Your Cards from Humidity in the PH"
                class="w-full p-2.5 rounded-xl border border-slate-300 font-bold focus:border-slate-900 focus:outline-none"
              />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-bold text-slate-700 mb-1">Category</label>
                <select v-model="blogCategory" class="w-full p-2.5 rounded-xl border border-slate-300 bg-white">
                  <option value="TCG Strategy">TCG Strategy</option>
                  <option value="Gunpla & Modeling">Gunpla & Modeling</option>
                  <option value="Care & Preservation">Care & Preservation</option>
                  <option value="Market Trends">Market Trends &amp; Collectibles</option>
                </select>
              </div>

              <div>
                <label class="block font-bold text-slate-700 mb-1">Author Name</label>
                <input
                  v-model="blogAuthor"
                  type="text"
                  placeholder="e.g. Chief Deck Architect"
                  class="w-full p-2.5 rounded-xl border border-slate-300 focus:border-slate-900 focus:outline-none"
                />
              </div>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Summary (1-2 sentences)</label>
              <input
                v-model="blogSummary"
                type="text"
                placeholder="Brief summary shown on article cards..."
                class="w-full p-2.5 rounded-xl border border-slate-300 focus:border-slate-900 focus:outline-none"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Article Body Content</label>
              <textarea
                v-model="blogContent"
                rows="6"
                placeholder="Write full guide or strategy breakdown here..."
                class="w-full p-3 rounded-xl border border-slate-300 leading-relaxed focus:border-slate-900 focus:outline-none font-sans"
              ></textarea>
            </div>
          </div>

          <div class="flex gap-3 pt-2">
            <button
              type="button"
              class="flex-1 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs hover:bg-slate-200 cursor-pointer"
              @click="isEditingBlog = false"
            >
              Cancel
            </button>
            <button
              type="button"
              class="flex-1 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition-colors cursor-pointer"
              @click="savePost"
            >
              Publish Article
            </button>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>
