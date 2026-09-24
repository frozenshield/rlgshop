<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAdminStore } from '../admin.store'
import { formatCurrency } from '@/shared/utils/currency.util'

const router = useRouter()
const adminStore = useAdminStore()

// Basic Info
const title = ref('Pokémon TCG: Terastal Festival ex Booster Box (Japanese)')
const shortSummary = ref('High-class Japanese booster box packed with Eeveelutions, Stellar Tera Pokémon, and guaranteed SAR foil cards per box.')
const sku = ref('TCG-PKM-SV8A-BOX')
const barcode = ref('4521329394982')

// Rich text description simulation
const description = ref(
  '<h3>Official Japanese Pokémon Center Terastal Festival [sv8a]</h3>\n<p>Experience the sensational final set of 2024 featuring all nine beloved Eeveelutions in breathtaking Stellar Tera ex full-art illustrations!</p>\n<ul>\n  <li>10 booster packs per box (10 cards per pack)</li>\n  <li>Guaranteed Pokémon ex or higher in every single pack</li>\n  <li>Factory sealed with official Pokémon Center holographic tear strip</li>\n</ul>',
)

// Media Gallery
interface MediaItem {
  id: string
  url: string
  alt: string
  isPrimary: boolean
}

const mediaList = ref<MediaItem[]>([
  {
    id: 'm1',
    url: 'https://images.unsplash.com/photo-1628155930542-3c7a64e2c833?w=700&auto=format&fit=crop&q=80',
    alt: 'Terastal Festival sv8a Japanese Booster Box Factory Sealed Front View',
    isPrimary: true,
  },
  {
    id: 'm2',
    url: 'https://images.unsplash.com/photo-1613771404784-3a5686aa2be3?w=700&auto=format&fit=crop&q=80',
    alt: 'Terastal Festival Booster Pack Cards Display',
    isPrimary: false,
  },
])
const newImageUrl = ref('')

const addImage = () => {
  if (newImageUrl.value.trim()) {
    mediaList.value.push({
      id: 'm-' + Date.now(),
      url: newImageUrl.value.trim(),
      alt: title.value + ' photo',
      isPrimary: mediaList.value.length === 0,
    })
    newImageUrl.value = ''
  }
}

const setPrimaryMedia = (id: string) => {
  mediaList.value.forEach((m) => {
    m.isPrimary = m.id === id
  })
}

const removeMedia = (id: string) => {
  mediaList.value = mediaList.value.filter((m) => m.id !== id)
  if (mediaList.value.length > 0 && !mediaList.value.some((m) => m.isPrimary)) {
    mediaList.value[0].isPrimary = true
  }
}

// Pricing Strategy
const sellingPrice = ref(3850)
const compareAtPrice = ref(4200)
const costPerItem = ref(2550)

const profitMargin = computed(() => {
  if (sellingPrice.value <= 0) return 0
  const profit = sellingPrice.value - costPerItem.value
  return Math.round((profit / sellingPrice.value) * 100)
})

const netProfit = computed(() => {
  return Math.max(0, sellingPrice.value - costPerItem.value)
})

// Taxonomy & Organization
const primaryCategory = ref('TCG (Trading Cards)')
const secondaryCategory = ref('Pokémon TCG')
const brandVendor = ref('The Pokémon Company Japan')
const tagsInput = ref('Terastal Festival, sv8a, Eevee, Booster Box, Japanese')
const tags = computed(() => tagsInput.value.split(',').map((t) => t.trim()).filter(Boolean))

// Variants & Options Matrix
interface VariantRow {
  id: string
  option1: string // e.g. Edition
  option2: string // e.g. Condition
  sku: string
  price: number
  cost: number
  stock: number
}

const option1Name = ref('Packaging Format')
const option1Values = ref(['Single Sealed Box', 'Sealed Carton (12 Boxes)'])

const option2Name = ref('Condition')
const option2Values = ref(['Mint Factory Sealed', 'Collector Grade (Acrylic Cased)'])

const variantMatrix = ref<VariantRow[]>([
  {
    id: 'vr-1',
    option1: 'Single Sealed Box',
    option2: 'Mint Factory Sealed',
    sku: 'TCG-PKM-SV8A-BOX',
    price: 3850,
    cost: 2550,
    stock: 24,
  },
  {
    id: 'vr-2',
    option1: 'Single Sealed Box',
    option2: 'Collector Grade (Acrylic Cased)',
    sku: 'TCG-PKM-SV8A-BOX-ACR',
    price: 4350,
    cost: 2850,
    stock: 8,
  },
  {
    id: 'vr-3',
    option1: 'Sealed Carton (12 Boxes)',
    option2: 'Mint Factory Sealed',
    sku: 'TCG-PKM-SV8A-CASE',
    price: 45000,
    cost: 30000,
    stock: 3,
  },
])

// Shipping & Physical Specs
const weightGrams = ref(320)
const dimensionLength = ref(14)
const dimensionWidth = ref(14)
const dimensionHeight = ref(4)
const hsCode = ref('9504.40.00')
const countryOfOrigin = ref('Japan')

// Search Engine Optimization (SEO)
const seoTitle = ref('Pokémon TCG: Terastal Festival sv8a Booster Box - RLG Hobby Shop')
const seoDescription = ref('Buy authentic Japanese Pokémon Terastal Festival ex sv8a booster box. 100% factory sealed with guaranteed SAR pulls. Fast Philippine courier delivery.')
const urlHandle = ref('pokemon-terastal-festival-sv8a-booster-box')

// Visibility & Publishing
const publishStatus = ref<'Draft' | 'Active' | 'Archived'>('Active')
const isScheduled = ref(false)
const scheduledDate = ref('2026-10-01T00:00')

const isSaving = ref(false)
const successNotice = ref('')

const handleSaveProduct = () => {
  isSaving.value = true
  setTimeout(() => {
    // Add to inventory & catalog simulation
    adminStore.addInventoryItem({
      sku: sku.value,
      barcode: barcode.value,
      name: title.value,
      category: primaryCategory.value,
      stock: variantMatrix.value.reduce((s, v) => s + v.stock, 0),
      lowStockThreshold: 5,
      costPrice: costPerItem.value,
      sellingPrice: sellingPrice.value,
      vendor: brandVendor.value,
      leadTimeDays: 7,
      variants: variantMatrix.value.map((v) => ({
        id: v.id,
        title: `${v.option1} / ${v.option2}`,
        sku: v.sku,
        price: v.price,
        costPrice: v.cost,
        stock: v.stock,
      })),
    })

    isSaving.value = false
    successNotice.value = `"${title.value}" published successfully to product catalog!`
    setTimeout(() => {
      router.push('/admin/inventory')
    }, 1500)
  }, 600)
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Product Catalog &amp; Upload Module</h1>
        <p class="text-xs text-slate-500">Comprehensive multi-tab editor: WYSIWYG, image gallery, profit margin calculator, variant matrices, and SEO.</p>
      </div>

      <div class="flex items-center gap-3">
        <button
          type="button"
          class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-100 font-bold text-xs cursor-pointer"
          @click="router.push('/admin/inventory')"
        >
          Cancel
        </button>
        <button
          type="button"
          class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-md cursor-pointer flex items-center gap-1.5"
          :disabled="isSaving"
          @click="handleSaveProduct"
        >
          <span>{{ isSaving ? 'Publishing...' : '🚀 Save & Publish Product' }}</span>
        </button>
      </div>
    </div>

    <!-- Alert -->
    <div v-if="successNotice" class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-xl animate-fade-in">
      ✓ {{ successNotice }}
    </div>

    <!-- 2 Column Master Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
      <!-- Left Column (8 cols): Content, Gallery, Pricing, Variants, Specs -->
      <div class="lg:col-span-8 space-y-6">
        <!-- 1. Basic Information -->
        <div class="bg-white p-6 sm:p-7 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">1. Basic Product Information</h2>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Product Title *</label>
            <input
              v-model="title"
              type="text"
              class="w-full text-sm font-bold p-3 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Short Summary (Shown in cards &amp; previews)</label>
            <input
              v-model="shortSummary"
              type="text"
              class="w-full text-xs p-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900 text-slate-600"
            />
          </div>

          <!-- WYSIWYG Editor Simulation -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Rich Description (WYSIWYG)</label>
            <div class="border border-slate-200 rounded-xl overflow-hidden">
              <!-- Toolbar -->
              <div class="flex items-center gap-1 bg-slate-50 p-2 border-b border-slate-200 text-xs text-slate-700 select-none">
                <button type="button" class="p-1 px-2 hover:bg-slate-200 rounded font-bold">B</button>
                <button type="button" class="p-1 px-2 hover:bg-slate-200 rounded italic">I</button>
                <button type="button" class="p-1 px-2 hover:bg-slate-200 rounded underline">U</button>
                <span class="w-[1px] h-4 bg-slate-300 mx-1"></span>
                <button type="button" class="p-1 px-2 hover:bg-slate-200 rounded">H2</button>
                <button type="button" class="p-1 px-2 hover:bg-slate-200 rounded">H3</button>
                <span class="w-[1px] h-4 bg-slate-300 mx-1"></span>
                <button type="button" class="p-1 px-2 hover:bg-slate-200 rounded">&bull; Bullet List</button>
                <button type="button" class="p-1 px-2 hover:bg-slate-200 rounded">1. Number List</button>
              </div>
              <textarea
                v-model="description"
                rows="5"
                class="w-full p-3 text-xs font-mono text-slate-700 focus:outline-none"
              ></textarea>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">SKU (Stock Keeping Unit) *</label>
              <input v-model="sku" type="text" class="w-full text-xs font-mono font-bold p-2.5 rounded-xl border border-slate-200 uppercase" />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Barcode (UPC / EAN / JAN) *</label>
              <input v-model="barcode" type="text" class="w-full text-xs font-mono p-2.5 rounded-xl border border-slate-200" />
            </div>
          </div>
        </div>

        <!-- 2. Media Gallery & Management -->
        <div class="bg-white p-6 sm:p-7 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">2. Media Gallery &amp; Alt-Text</h2>
            <span class="text-xs text-slate-400">{{ mediaList.length }} images attached</span>
          </div>

          <!-- Drag and Drop Dropzone -->
          <div class="p-6 border-2 border-dashed border-slate-200 rounded-2xl text-center space-y-2 bg-slate-50">
            <span class="text-3xl">📸</span>
            <p class="text-xs font-bold text-slate-700">Drag &amp; drop high-resolution JPG, PNG, WEBP files here</p>
            <p class="text-[11px] text-slate-400">Recommended size: 1200x1200px square on neutral white/grey background</p>

            <div class="flex items-center justify-center gap-2 max-w-sm mx-auto pt-2">
              <input
                v-model="newImageUrl"
                type="url"
                placeholder="Or paste image URL..."
                class="text-xs p-2 rounded-xl border border-slate-200 bg-white flex-1"
              />
              <button
                type="button"
                class="px-3 py-2 bg-slate-900 text-white font-bold text-xs rounded-xl cursor-pointer"
                @click="addImage"
              >
                Add URL
              </button>
            </div>
          </div>

          <!-- Existing Images Grid -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
            <div
              v-for="img in mediaList"
              :key="img.id"
              class="relative rounded-xl overflow-hidden border-2 bg-slate-50 group flex flex-col justify-between"
              :class="img.isPrimary ? 'border-rose-600 ring-2 ring-rose-200' : 'border-slate-200'"
            >
              <div class="aspect-square relative overflow-hidden">
                <img :src="img.url" :alt="img.alt" class="w-full h-full object-cover" />
                <span
                  v-if="img.isPrimary"
                  class="absolute top-1.5 left-1.5 bg-rose-600 text-white font-bold text-[9px] px-2 py-0.5 rounded shadow-2xs"
                >
                  Primary
                </span>
                <button
                  type="button"
                  class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-slate-900/80 text-white text-xs opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer"
                  title="Delete image"
                  @click="removeMedia(img.id)"
                >
                  ✕
                </button>
              </div>

              <!-- Alt text input -->
              <div class="p-2 bg-white space-y-1">
                <input
                  v-model="img.alt"
                  type="text"
                  placeholder="Alt text (SEO & a11y)"
                  class="w-full text-[10px] p-1 border border-slate-200 rounded"
                  title="Image description for visually impaired and Google Image search"
                />
                <button
                  v-if="!img.isPrimary"
                  type="button"
                  class="w-full text-[10px] font-bold text-slate-500 hover:text-slate-900 text-center py-0.5 cursor-pointer"
                  @click="setPrimaryMedia(img.id)"
                >
                  Set as Primary
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- 3. Pricing Strategy & Profit Margin Engine -->
        <div class="bg-white p-6 sm:p-7 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">3. Pricing Strategy &amp; Margins</h2>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Selling Price (PHP ₱) *</label>
              <input
                v-model.number="sellingPrice"
                type="number"
                class="w-full text-base font-black p-2.5 rounded-xl border border-slate-200 text-slate-900 focus:outline-none focus:border-slate-900"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Compare-at Price (Strikethrough)</label>
              <input
                v-model.number="compareAtPrice"
                type="number"
                class="w-full text-xs p-2.5 rounded-xl border border-slate-200 text-slate-400 line-through"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Cost Per Item (Supplier Wholesale)</label>
              <input
                v-model.number="costPerItem"
                type="number"
                class="w-full text-xs p-2.5 rounded-xl border border-slate-200 text-slate-600 font-mono"
              />
            </div>
          </div>

          <!-- Profit Margin Telemetry Widget -->
          <div class="p-4 bg-emerald-50/70 rounded-2xl border border-emerald-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <span class="text-2xl">📈</span>
              <div>
                <span class="text-[10px] font-bold uppercase text-emerald-800 tracking-wider">Automatic Profit Margin</span>
                <p class="text-sm font-extrabold text-emerald-900">
                  {{ profitMargin }}% Margin &bull; +{{ formatCurrency(netProfit) }} Gross Profit per Box
                </p>
              </div>
            </div>
            <span class="text-xs font-bold text-emerald-700 bg-white px-3 py-1 rounded-full shadow-2xs">
              Healthy Target
            </span>
          </div>
        </div>

        <!-- 4. Variants & Matrix Builder -->
        <div class="bg-white p-6 sm:p-7 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">4. Product Variants &amp; Matrix</h2>
              <p class="text-xs text-slate-500">Configure multiple options like format, condition, edition, or grades</p>
            </div>
          </div>

          <div class="divide-y divide-slate-100 border border-slate-200 rounded-xl overflow-hidden">
            <div
              v-for="row in variantMatrix"
              :key="row.id"
              class="p-3 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs"
            >
              <div class="min-w-0">
                <span class="font-bold text-slate-900 block truncate">{{ row.option1 }} &bull; {{ row.option2 }}</span>
                <span class="font-mono text-[10px] text-slate-400">SKU: {{ row.sku }}</span>
              </div>

              <div class="flex items-center gap-3">
                <div class="w-24">
                  <label class="block text-[9px] font-bold text-slate-400">Price (₱)</label>
                  <input v-model.number="row.price" type="number" class="w-full p-1 border rounded font-bold" />
                </div>
                <div class="w-20">
                  <label class="block text-[9px] font-bold text-slate-400">Stock</label>
                  <input v-model.number="row.stock" type="number" class="w-full p-1 border rounded font-bold" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 5. Shipping & Physical Specifications -->
        <div class="bg-white p-6 sm:p-7 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">5. Shipping &amp; Customs Specs</h2>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Weight (Grams)</label>
              <input v-model.number="weightGrams" type="number" class="w-full p-2.5 rounded-xl border border-slate-200 font-bold" />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Length (cm)</label>
              <input v-model.number="dimensionLength" type="number" class="w-full p-2.5 rounded-xl border border-slate-200" />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Width (cm)</label>
              <input v-model.number="dimensionWidth" type="number" class="w-full p-2.5 rounded-xl border border-slate-200" />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Height (cm)</label>
              <input v-model.number="dimensionHeight" type="number" class="w-full p-2.5 rounded-xl border border-slate-200" />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs pt-1">
            <div>
              <label class="block font-bold text-slate-700 mb-1">HS Tariff Code (International Customs)</label>
              <input v-model="hsCode" type="text" class="w-full p-2.5 rounded-xl border border-slate-200 font-mono text-slate-700" />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Country of Origin</label>
              <input v-model="countryOfOrigin" type="text" class="w-full p-2.5 rounded-xl border border-slate-200 font-bold text-slate-700" />
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column (4 cols): Visibility, Taxonomy, SEO Preview -->
      <div class="lg:col-span-4 space-y-6">
        <!-- Status & Visibility Card -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
          <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Publishing Status</h3>

          <div class="space-y-2">
            <label
              v-for="st in (['Active', 'Draft', 'Archived'] as const)"
              :key="st"
              class="flex items-center justify-between p-3 rounded-xl border cursor-pointer transition-all"
              :class="publishStatus === st ? 'border-slate-900 bg-slate-50' : 'border-slate-200'"
            >
              <span class="text-xs font-bold text-slate-800">{{ st }}</span>
              <input v-model="publishStatus" type="radio" :value="st" class="accent-slate-900" />
            </label>
          </div>

          <!-- Scheduled Publishing Toggle -->
          <div class="pt-3 border-t border-slate-100 space-y-2 text-xs">
            <label class="flex items-center gap-2 font-bold text-slate-700 cursor-pointer">
              <input v-model="isScheduled" type="checkbox" class="accent-rose-600 rounded" />
              <span>Schedule Drop / Limited Release</span>
            </label>
            <div v-if="isScheduled" class="pt-1 animate-fade-in">
              <input v-model="scheduledDate" type="datetime-local" class="w-full p-2 rounded-xl border border-slate-200 font-mono text-xs" />
            </div>
          </div>
        </div>

        <!-- Organization & Taxonomy Card -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4 text-xs">
          <h3 class="font-bold text-slate-900 uppercase tracking-wider text-xs">Taxonomy &amp; Brand</h3>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Primary Category</label>
            <select v-model="primaryCategory" class="w-full p-2.5 rounded-xl border border-slate-200 bg-white">
              <option value="TCG (Trading Cards)">TCG (Trading Cards)</option>
              <option value="Gunpla & Models">Gunpla & Models</option>
              <option value="Anime Figures">Anime Figures</option>
              <option value="Hobby Supplies">Hobby Supplies</option>
            </select>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Secondary / Sub-Category</label>
            <input v-model="secondaryCategory" type="text" class="w-full p-2.5 rounded-xl border border-slate-200" />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Brand / Vendor Manufacturer</label>
            <input v-model="brandVendor" type="text" class="w-full p-2.5 rounded-xl border border-slate-200 font-bold" />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Tags &amp; Smart Collections (comma separated)</label>
            <textarea v-model="tagsInput" rows="2" class="w-full p-2.5 rounded-xl border border-slate-200 text-[11px]"></textarea>
            <div class="flex flex-wrap gap-1 mt-1.5">
              <span v-for="(t, i) in tags" :key="i" class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[10px] font-semibold">
                #{{ t }}
              </span>
            </div>
          </div>
        </div>

        <!-- SEO Details Card with Google Snippet -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4 text-xs">
          <h3 class="font-bold text-slate-900 uppercase tracking-wider text-xs">Search Engine Optimization (SEO)</h3>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Page Meta Title</label>
            <input v-model="seoTitle" type="text" class="w-full p-2.5 rounded-xl border border-slate-200" />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Page Meta Description</label>
            <textarea v-model="seoDescription" rows="3" class="w-full p-2.5 rounded-xl border border-slate-200 text-slate-600"></textarea>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Custom URL Handle</label>
            <input v-model="urlHandle" type="text" class="w-full p-2.5 rounded-xl border border-slate-200 font-mono text-[11px]" />
          </div>

          <!-- SERP Snippet Preview -->
          <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
            <span class="text-[9px] font-bold text-slate-400 uppercase">Google Result Preview:</span>
            <div class="text-[10px] text-slate-500 font-mono truncate">rlghobby.ph/product/{{ urlHandle }}</div>
            <h4 class="text-xs font-bold text-blue-700 line-clamp-1">{{ seoTitle }}</h4>
            <p class="text-[11px] text-slate-600 line-clamp-2">{{ seoDescription }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
