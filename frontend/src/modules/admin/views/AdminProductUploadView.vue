<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useAdminStore } from "../admin.store";

const router = useRouter();
const adminStore = useAdminStore();

interface CategoryItem {
  id: number;
  desc: string;
  subcategories: { id: number; desc: string; ref_category_id: number }[];
}

interface BrandItem {
  id: number;
  name: string;
}

interface ConditionItem {
  id: number;
  desc: string;
}

const dbCategories = ref<CategoryItem[]>([]);
const dbBrands = ref<BrandItem[]>([]);
const dbConditions = ref<ConditionItem[]>([]);

const fetchTaxonomy = async () => {
  try {
    const [catRes, brandRes, condRes] = await Promise.all([
      fetch("/api/categories"),
      fetch("/api/brands"),
      fetch("/api/conditions"),
    ]);
    if (catRes.ok) {
      dbCategories.value = await catRes.json();
    }
    if (brandRes.ok) {
      dbBrands.value = await brandRes.json();
    }
    if (condRes.ok) {
      dbConditions.value = await condRes.json();
    }
  } catch (e) {
    console.error("Failed to load taxonomy from API", e);
  }
};

onMounted(() => {
  fetchTaxonomy();
});

// Basic Info
const title = ref("");
const sku = ref("");
const condition = ref("");

// Description (plain text)
const description = ref("");

// Media Gallery
interface MediaItem {
  id: string;
  url: string;
  alt: string;
  isPrimary: boolean;
}

const mediaList = ref<MediaItem[]>([]);
const newImageUrl = ref("");

const addImage = () => {
  if (newImageUrl.value.trim()) {
    mediaList.value.push({
      id: "m-" + Date.now(),
      url: newImageUrl.value.trim(),
      alt: title.value + " photo",
      isPrimary: mediaList.value.length === 0,
    });
    newImageUrl.value = "";
  }
};

const setPrimaryMedia = (id: string) => {
  mediaList.value.forEach((m) => {
    m.isPrimary = m.id === id;
  });
};

const removeMedia = (id: string) => {
  mediaList.value = mediaList.value.filter((m) => m.id !== id);
  if (mediaList.value.length > 0 && !mediaList.value.some((m) => m.isPrimary)) {
    mediaList.value[0].isPrimary = true;
  }
};

// AI Automation & Image Upload State
const isAnalyzingWithAi = ref(false);
const autoFillWithAi = ref(true);
const aiStatusMessage = ref("");
const aiError = ref("");
const aiSuccess = ref("");
const fileInput = ref<HTMLInputElement | null>(null);
const isDraggingOver = ref(false);

const triggerFileInput = () => {
  fileInput.value?.click();
};

const handleFileSelect = async (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    await processUploadedImage(target.files[0]);
    target.value = "";
  }
};

const handleDrop = async (e: DragEvent) => {
  isDraggingOver.value = false;
  if (e.dataTransfer?.files && e.dataTransfer.files[0]) {
    await processUploadedImage(e.dataTransfer.files[0]);
  }
};

const processUploadedImage = async (file: File) => {
  const localPreviewUrl = URL.createObjectURL(file);
  const newMediaId = "m-" + Date.now();

  mediaList.value.unshift({
    id: newMediaId,
    url: localPreviewUrl,
    alt: file.name,
    isPrimary: true,
  });
  mediaList.value.slice(1).forEach((m) => (m.isPrimary = false));

  if (!autoFillWithAi.value) {
    return;
  }

  await runAiAnalysis(file, newMediaId);
};

const analyzeUrlWithAi = async () => {
  if (!newImageUrl.value.trim()) return;
  const url = newImageUrl.value.trim();
  addImage();
  const lastItem = mediaList.value[mediaList.value.length - 1];
  if (autoFillWithAi.value && lastItem) {
    await runAiAnalysis(url, lastItem.id);
  }
};

const runAiAnalysis = async (source: File | string, mediaItemId: string) => {
  isAnalyzingWithAi.value = true;
  aiError.value = "";
  aiSuccess.value = "";
  aiStatusMessage.value =
    "Google Gemini is analyzing the image to detect product name, description, categories, and tags...";

  try {
    const formData = new FormData();
    if (source instanceof File) {
      formData.append("image", source);
    } else {
      formData.append("image_url", source);
    }

    const response = await fetch("/api/ai/analyze-product-image", {
      method: "POST",
      body: formData,
    });

    const res = await response.json();

    if (!response.ok || !res.success) {
      throw new Error(res.message || "Failed to analyze image with AI.");
    }

    const data = res.data;

    if (data.title) title.value = data.title;
    if (data.description) description.value = data.description;
    if (data.primary_category) primaryCategory.value = data.primary_category;
    if (data.secondary_category)
      secondaryCategory.value = data.secondary_category;
    if (data.brand) brandVendor.value = data.brand;
    if (data.weight) weightGrams.value = Number(data.weight);
    if (data.length) dimensionLength.value = Number(data.length);
    if (data.width) dimensionWidth.value = Number(data.width);
    if (data.height) dimensionHeight.value = Number(data.height);
    if (data.status) {
      const s = String(data.status).toLowerCase();
      if (s === "draft") publishStatus.value = "Draft";
      else if (s === "archived") publishStatus.value = "Archived";
      else publishStatus.value = "Active";
    }
    if (Array.isArray(data.tags)) tagsInput.value = data.tags.join(", ");
    if (data.suggested_price) {
      sellingPrice.value = Number(data.suggested_price);
    }
    if (data.sku_suggestion) sku.value = data.sku_suggestion;
    if (data.sku) sku.value = data.sku;
    if (data.condition) condition.value = data.condition;

    // Update with server stored image URL if returned
    if (data.image_url) {
      const found = mediaList.value.find((m) => m.id === mediaItemId);
      if (found) {
        found.url = data.image_url;
        found.alt = data.title || found.alt;
      }
    }

    aiSuccess.value = `✨ Auto-populated "${data.title}" successfully with Google Gemini!`;
  } catch (err: any) {
    aiError.value =
      err.message || "An unexpected error occurred while analyzing the image.";
  } finally {
    isAnalyzingWithAi.value = false;
    aiStatusMessage.value = "";
  }
};

// Pricing & Stock
const sellingPrice = ref<number | "">("");
const stock = ref<number | "">("");

// Taxonomy & Organization
const primaryCategory = ref("");
const secondaryCategory = ref("");
const brandVendor = ref("");

const currentSubcategories = computed(() => {
  const selectedCat = dbCategories.value.find(
    (c) => c.desc === primaryCategory.value,
  );
  return selectedCat?.subcategories || [];
});

const tagsInput = ref("");
const tags = computed(() =>
  tagsInput.value
    .split(",")
    .map((t) => t.trim())
    .filter(Boolean),
);

// Shipping & Physical Specs
const weightGrams = ref<number | "">("");
const dimensionLength = ref<number | "">("");
const dimensionWidth = ref<number | "">("");
const dimensionHeight = ref<number | "">("");

// Visibility & Publishing
const publishStatus = ref<"Draft" | "Active" | "Archived">("Draft");
const isScheduled = ref(false);
const scheduledDate = ref("");

const isSaving = ref(false);
const successNotice = ref("");
const saveError = ref("");

const handleSaveProduct = async () => {
  if (!title.value.trim()) {
    saveError.value = "Please enter a product title before saving.";
    return;
  }

  isSaving.value = true;
  saveError.value = "";
  successNotice.value = "";

  try {
    const primaryImg =
      mediaList.value.find((m) => m.isPrimary)?.url ||
      mediaList.value[0]?.url ||
      "";
    const gallery = mediaList.value.map((m) => m.url);

    // Resolve category id
    const selectedCat = dbCategories.value.find(
      (c) => c.desc === primaryCategory.value,
    );
    const categoryId = selectedCat?.id || null;

    // Resolve subcategory id
    const selectedSub = selectedCat?.subcategories?.find(
      (s) => s.desc === secondaryCategory.value,
    );
    const subcategoryId = selectedSub?.id || null;

    // Resolve brand id
    const selectedBrand = dbBrands.value.find(
      (b) => b.name === brandVendor.value,
    );
    const brandId = selectedBrand?.id || null;

    // Resolve condition id
    const selectedCond = dbConditions.value.find(
      (c) => c.desc === condition.value,
    );
    const conditionIdVal = selectedCond?.id || null;

    const payload = {
      name: title.value.trim(),
      sku: sku.value ? sku.value.trim() : null,
      price: Number(sellingPrice.value) || 0,
      stock: Number(stock.value) || 0,
      description: description.value,
      ref_category_id: categoryId,
      category: primaryCategory.value,
      ref_subcategory_id: subcategoryId,
      subcategory: secondaryCategory.value,
      ref_brand_id: brandId,
      brand: brandVendor.value,
      ref_condition_id: conditionIdVal,
      condition_id: conditionIdVal,
      condition: condition.value,
      weight: Number(weightGrams.value) || null,
      length: Number(dimensionLength.value) || null,
      width: Number(dimensionWidth.value) || null,
      height: Number(dimensionHeight.value) || null,
      status: publishStatus.value.toLowerCase(),
      image_url: primaryImg,
      gallery_images: gallery,
    };

    const response = await fetch("/api/products", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(payload),
    });

    const res = await response.json();

    if (!response.ok || !res.success) {
      throw new Error(res.message || "Failed to save product in database.");
    }

    const savedProduct = res.data;

    // Update SKU ref if server generated or assigned one
    if (savedProduct.sku) {
      sku.value = savedProduct.sku;
    }

    // Also add to inventory state in store
    adminStore.addInventoryItem({
      sku: savedProduct.sku || sku.value || `PRD-${savedProduct.id}`,
      barcode: savedProduct.sku || sku.value || `BC-${savedProduct.id}`,
      name: savedProduct.name,
      category: primaryCategory.value,
      condition: condition.value,
      stock: Number(savedProduct.stock) || 0,
      lowStockThreshold: 5,
      costPrice: 0,
      sellingPrice: Number(savedProduct.price) || 0,
      vendor: brandVendor.value,
      leadTimeDays: 7,
      variants: [],
    });

    successNotice.value = `"${savedProduct.name}" saved successfully to database! (Product ID #${savedProduct.id})`;

    setTimeout(() => {
      router.push("/admin/inventory");
    }, 1500);
  } catch (err: any) {
    saveError.value =
      err.message || "Failed to save product to database. Please try again.";
  } finally {
    isSaving.value = false;
  }
};
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div
      class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs"
    >
      <div>
        <h1
          class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight"
        >
          Product Catalog &amp; Upload Module
        </h1>
        <p class="text-xs text-slate-500">
          Upload products with AI vision auto-fill, media gallery, pricing, and
          classification.
        </p>
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
          <span>{{
            isSaving ? "Publishing..." : "🚀 Save & Publish Product"
          }}</span>
        </button>
      </div>
    </div>

    <!-- Alert -->
    <div
      v-if="successNotice"
      class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-xl animate-fade-in"
    >
      ✓ {{ successNotice }}
    </div>

    <!-- Save Error Alert -->
    <div
      v-if="saveError"
      class="p-3.5 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold rounded-xl animate-fade-in flex items-center justify-between"
    >
      <div class="flex items-center gap-2">
        <span>⚠️</span>
        <span>{{ saveError }}</span>
      </div>
      <button
        type="button"
        class="text-rose-500 hover:text-rose-800 text-sm font-bold"
        @click="saveError = ''"
      >
        ✕
      </button>
    </div>

    <!-- AI Success Alert -->
    <div
      v-if="aiSuccess"
      class="p-3.5 bg-indigo-50 border border-indigo-200 text-indigo-900 text-xs font-bold rounded-xl animate-fade-in flex items-center justify-between"
    >
      <div class="flex items-center gap-2">
        <span class="text-base">✨</span>
        <span>{{ aiSuccess }}</span>
      </div>
      <button
        type="button"
        class="text-indigo-600 hover:text-indigo-900 text-xs"
        @click="aiSuccess = ''"
      >
        ✕
      </button>
    </div>

    <!-- AI Error Alert -->
    <div
      v-if="aiError"
      class="p-3.5 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold rounded-xl animate-fade-in flex items-center justify-between"
    >
      <div class="flex items-center gap-2">
        <span class="text-base">⚠️</span>
        <span>{{ aiError }}</span>
      </div>
      <button
        type="button"
        class="text-rose-600 hover:text-rose-900 text-xs"
        @click="aiError = ''"
      >
        ✕
      </button>
    </div>

    <!-- AI Analysis In-Progress Banner -->
    <div
      v-if="isAnalyzingWithAi"
      class="p-4 bg-gradient-to-r from-indigo-50 via-purple-50 to-pink-50 border border-indigo-200 text-indigo-950 text-xs font-bold rounded-xl shadow-xs flex items-center gap-3 animate-pulse"
    >
      <div
        class="w-5 h-5 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin shrink-0"
      ></div>
      <div>
        <p class="font-extrabold text-sm text-indigo-900">
          Google Gemini AI is analyzing your product image...
        </p>
        <p class="text-[11px] text-indigo-700 font-normal">
          {{ aiStatusMessage }}
        </p>
      </div>
    </div>

    <!-- 2 Column Master Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
      <!-- Left Column (8 cols): Content, Gallery, Pricing, Variants, Specs -->
      <div class="lg:col-span-8 space-y-6">
        <!-- 1. Basic Information -->
        <div
          class="bg-white p-6 sm:p-7 rounded-2xl border border-slate-200/80 shadow-xs space-y-4"
        >
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
            1. Basic Product Information
          </h2>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1"
              >Product Title *</label
            >
            <input
              v-model="title"
              type="text"
              placeholder="e.g. Pokémon TCG: Terastal Festival ex Booster Box"
              class="w-full text-sm font-bold p-3 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-900"
            />
          </div>

          <!-- Description (plain text) -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1"
              >Description</label
            >
            <textarea
              v-model="description"
              rows="5"
              placeholder="Detailed product description, features, box contents..."
              class="w-full text-xs text-slate-700 p-3 rounded-xl border border-slate-200 focus:outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 resize-y transition-all"
            ></textarea>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-1">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1"
                >SKU (Stock Keeping Unit) *</label
              >
              <input
                v-model="sku"
                type="text"
                placeholder="e.g. TCG-PKM-001"
                class="w-full text-xs font-mono font-bold p-2.5 rounded-xl border border-slate-200 uppercase"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1"
                >Condition *</label
              >
              <select
                v-model="condition"
                class="w-full text-xs font-bold p-2.5 rounded-xl border border-slate-200 bg-white"
              >
                <option value="" disabled>Select condition...</option>
                <template v-if="dbConditions.length > 0">
                  <option
                    v-for="cond in dbConditions"
                    :key="cond.id"
                    :value="cond.desc"
                  >
                    {{ cond.desc }}
                  </option>
                </template>
                <template v-else>
                  <option value="Brandnew">Brandnew</option>
                  <option value="MISB">MISB</option>
                  <option value="BIB">BIB</option>
                  <option value="Near Mint">Near Mint</option>
                  <option value="Lightly Played">Lightly Played</option>
                  <option value="Moderately Played">Moderately Played</option>
                  <option value="Heavily Played">Heavily Played</option>
                  <option value="Damaged">Damaged</option>
                  <option value="Loose">Loose</option>
                </template>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1"
                >Available Stock *</label
              >
              <input
                v-model.number="stock"
                type="number"
                min="0"
                placeholder="0"
                class="w-full text-xs font-bold p-2.5 rounded-xl border border-slate-200"
              />
            </div>
          </div>
        </div>

        <!-- 2. Media Gallery & Management -->
        <div
          class="bg-white p-6 sm:p-7 rounded-2xl border border-slate-200/80 shadow-xs space-y-4"
        >
          <div class="flex items-center justify-between">
            <h2
              class="text-sm font-bold text-slate-900 uppercase tracking-wider"
            >
              2. Media Gallery &amp; Alt-Text
            </h2>
            <span class="text-xs text-slate-400"
              >{{ mediaList.length }} images attached</span
            >
          </div>

          <!-- Hidden File Input for Native File Dialog -->
          <input
            ref="fileInput"
            type="file"
            accept="image/png,image/jpeg,image/webp,image/jpg"
            class="hidden"
            @change="handleFileSelect"
          />

          <!-- AI Toggle Banner -->
          <div
            class="flex items-center justify-between p-3.5 bg-gradient-to-r from-indigo-50 via-purple-50 to-pink-50 rounded-xl border border-indigo-100 shadow-2xs"
          >
            <div class="flex items-center gap-2.5">
              <span class="text-xl">✨</span>
              <div>
                <p
                  class="text-xs font-black text-indigo-950 flex items-center gap-1.5"
                >
                  Google Gemini Auto-Population
                  <span
                    class="px-1.5 py-0.2 rounded bg-indigo-600 text-white text-[9px] font-bold"
                    >AI Vision</span
                  >
                </p>
                <p class="text-[11px] text-indigo-700">
                  Upload an image to auto-generate title, description,
                  categories, tags &amp; specs
                </p>
              </div>
            </div>
            <label
              class="relative inline-flex items-center cursor-pointer select-none"
            >
              <input
                v-model="autoFillWithAi"
                type="checkbox"
                class="sr-only peer"
              />
              <div
                class="w-9 h-5 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"
              ></div>
            </label>
          </div>

          <!-- Drag and Drop Dropzone -->
          <div
            class="p-6 border-2 border-dashed rounded-2xl text-center space-y-3 transition-all cursor-pointer"
            :class="[
              isDraggingOver
                ? 'border-indigo-600 bg-indigo-50/70 scale-[0.99]'
                : 'border-slate-200 hover:border-indigo-400 bg-slate-50/80',
              isAnalyzingWithAi ? 'opacity-60 pointer-events-none' : '',
            ]"
            @dragover.prevent="isDraggingOver = true"
            @dragleave.prevent="isDraggingOver = false"
            @drop.prevent="handleDrop"
            @click="triggerFileInput"
          >
            <div
              class="w-12 h-12 mx-auto rounded-full bg-indigo-100/80 flex items-center justify-center text-2xl shadow-2xs"
            >
              📸
            </div>
            <div>
              <p class="text-xs font-bold text-slate-800">
                <span class="text-indigo-600 underline font-extrabold"
                  >Click to upload product photo</span
                >
                or drag &amp; drop here
              </p>
              <p class="text-[11px] text-slate-500 mt-0.5">
                PNG, JPG, WEBP up to 15MB &bull; Gemini analyzes box art, figure
                tags, &amp; specs
              </p>
            </div>

            <div
              class="flex items-center justify-center gap-2 max-w-sm mx-auto pt-2"
              @click.stop
            >
              <input
                v-model="newImageUrl"
                type="url"
                placeholder="Or paste direct image URL..."
                class="text-xs p-2 rounded-xl border border-slate-200 bg-white flex-1 focus:outline-none focus:border-indigo-600"
                @keyup.enter="analyzeUrlWithAi"
              />
              <button
                type="button"
                class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl cursor-pointer flex items-center gap-1 transition-all"
                :disabled="isAnalyzingWithAi || !newImageUrl.trim()"
                @click="analyzeUrlWithAi"
              >
                <span>Add URL</span>
                <span v-if="autoFillWithAi" class="text-xs">✨</span>
              </button>
            </div>
          </div>

          <!-- Existing Images Grid -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
            <div
              v-for="img in mediaList"
              :key="img.id"
              class="relative rounded-xl overflow-hidden border-2 bg-slate-50 group flex flex-col justify-between"
              :class="
                img.isPrimary
                  ? 'border-rose-600 ring-2 ring-rose-200'
                  : 'border-slate-200'
              "
            >
              <div class="aspect-square relative overflow-hidden">
                <img
                  :src="img.url"
                  :alt="img.alt"
                  class="w-full h-full object-cover"
                />
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

        <!-- 3. Pricing -->
        <div
          class="bg-white p-6 sm:p-7 rounded-2xl border border-slate-200/80 shadow-xs space-y-4"
        >
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
            3. Pricing
          </h2>

          <div class="max-w-xs">
            <label class="block text-xs font-bold text-slate-700 mb-1"
              >Selling Price (PHP ₱) *</label
            >
            <input
              v-model.number="sellingPrice"
              type="number"
              min="0"
              placeholder="e.g. 3850"
              class="w-full text-base font-black p-2.5 rounded-xl border border-slate-200 text-slate-900 focus:outline-none focus:border-slate-900"
            />
          </div>
        </div>

        <!-- 4. Shipping & Physical Specifications -->
        <div
          class="bg-white p-6 sm:p-7 rounded-2xl border border-slate-200/80 shadow-xs space-y-4"
        >
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
            4. Shipping &amp; Physical Specs
          </h2>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
            <div>
              <label class="block font-bold text-slate-700 mb-1"
                >Weight (Grams)</label
              >
              <input
                v-model.number="weightGrams"
                type="number"
                placeholder="0"
                class="w-full p-2.5 rounded-xl border border-slate-200 font-bold"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1"
                >Length (cm)</label
              >
              <input
                v-model.number="dimensionLength"
                type="number"
                placeholder="0"
                class="w-full p-2.5 rounded-xl border border-slate-200"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1"
                >Width (cm)</label
              >
              <input
                v-model.number="dimensionWidth"
                type="number"
                placeholder="0"
                class="w-full p-2.5 rounded-xl border border-slate-200"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1"
                >Height (cm)</label
              >
              <input
                v-model.number="dimensionHeight"
                type="number"
                placeholder="0"
                class="w-full p-2.5 rounded-xl border border-slate-200"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column (4 cols): Visibility, Taxonomy, SEO Preview -->
      <div class="lg:col-span-4 space-y-6">
        <!-- Status & Visibility Card -->
        <div
          class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4"
        >
          <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
            Publishing Status
          </h3>

          <div class="space-y-2">
            <label
              v-for="st in ['Active', 'Draft', 'Archived'] as const"
              :key="st"
              class="flex items-center justify-between p-3 rounded-xl border cursor-pointer transition-all"
              :class="
                publishStatus === st
                  ? 'border-slate-900 bg-slate-50'
                  : 'border-slate-200'
              "
            >
              <span class="text-xs font-bold text-slate-800">{{ st }}</span>
              <input
                v-model="publishStatus"
                type="radio"
                :value="st"
                class="accent-slate-900"
              />
            </label>
          </div>

          <!-- Scheduled Publishing Toggle -->
          <div class="pt-3 border-t border-slate-100 space-y-2 text-xs">
            <label
              class="flex items-center gap-2 font-bold text-slate-700 cursor-pointer"
            >
              <input
                v-model="isScheduled"
                type="checkbox"
                class="accent-rose-600 rounded"
              />
              <span>Schedule Drop / Limited Release</span>
            </label>
            <div v-if="isScheduled" class="pt-1 animate-fade-in">
              <input
                v-model="scheduledDate"
                type="datetime-local"
                class="w-full p-2 rounded-xl border border-slate-200 font-mono text-xs"
              />
            </div>
          </div>
        </div>

        <!-- Organization & Taxonomy Card -->
        <div
          class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4 text-xs"
        >
          <h3 class="font-bold text-slate-900 uppercase tracking-wider text-xs">
            Taxonomy &amp; Brand
          </h3>

          <div>
            <label class="block font-bold text-slate-700 mb-1"
              >Primary Category</label
            >
            <select
              v-model="primaryCategory"
              class="w-full p-2.5 rounded-xl border border-slate-200 bg-white"
            >
              <option value="" disabled>Select category...</option>
              <template v-if="dbCategories.length > 0">
                <option
                  v-for="cat in dbCategories"
                  :key="cat.id"
                  :value="cat.desc"
                >
                  {{ cat.desc }}
                </option>
              </template>
              <template v-else>
                <option value="TCG (Trading Cards)">TCG (Trading Cards)</option>
                <option value="Gunpla">Gunpla</option>
                <option value="Anime Figures">Anime Figures</option>
                <option value="Anime Merch Collectibles">
                  Anime Merch Collectibles
                </option>
              </template>
            </select>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1"
              >Secondary / Sub-Category</label
            >
            <input
              v-model="secondaryCategory"
              type="text"
              list="subcategories-list"
              class="w-full p-2.5 rounded-xl border border-slate-200"
              placeholder="e.g. Pokemon, HG, Scale Figures..."
            />
            <datalist id="subcategories-list">
              <option
                v-for="sub in currentSubcategories"
                :key="sub.id"
                :value="sub.desc"
              />
            </datalist>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1"
              >Brand / Vendor Manufacturer</label
            >
            <input
              v-model="brandVendor"
              type="text"
              list="brands-list"
              class="w-full p-2.5 rounded-xl border border-slate-200 font-bold"
              placeholder="e.g. The Pokémon Company, Bandai, Good Smile..."
            />
            <datalist id="brands-list">
              <option
                v-for="brand in dbBrands"
                :key="brand.id"
                :value="brand.name"
              />
            </datalist>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1"
              >Tags &amp; Smart Collections (comma separated)</label
            >
            <textarea
              v-model="tagsInput"
              rows="2"
              placeholder="e.g. Booster Box, Japanese, Limited Edition..."
              class="w-full p-2.5 rounded-xl border border-slate-200 text-[11px]"
            ></textarea>
            <div class="flex flex-wrap gap-1 mt-1.5">
              <span
                v-for="(t, i) in tags"
                :key="i"
                class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[10px] font-semibold"
              >
                #{{ t }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
