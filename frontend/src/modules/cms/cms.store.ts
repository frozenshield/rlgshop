import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { useStorage } from "@vueuse/core";
import type { CMSBanner, CMSStaticPages, CMSBlogPost, CMSPayload } from "./cms.types";

const API_BASE = "http://127.0.0.1:8000/api";

const DEFAULT_BANNERS: CMSBanner[] = [
  {
    id: "bnr-1",
    title: "Build, Collect & Battle. Your Premier Hobby Store.",
    subtitle:
      "Discover factory-sealed Trading Card Game booster boxes, authentic Japanese Bandai Gunpla kits, detailed anime scale figures, and premium card sleeves — shipped securely across the Philippines.",
    badgeText: "RLG HOBBY SHOP • OFFICIAL IMPORTS VAULT",
    ctaText: "Explore All Products",
    ctaLink: "/catalog",
    imageUrl:
      "https://images.unsplash.com/photo-1613771404784-3a5686aa2be3?w=700&auto=format&fit=crop&q=80",
    isActive: true,
  },
  {
    id: "bnr-2",
    title: "Level Up Your Collection: 10% - 20% Off Drops!",
    subtitle:
      "Apply collector code HOBBY10 or GUNPLA20 at checkout on all orders!",
    badgeText: "Collector Welcome Coupon ⚡",
    ctaText: "Shop Deals Now",
    ctaLink: "/catalog",
    imageUrl:
      "https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=700&auto=format&fit=crop&q=80",
    isActive: true,
  },
];

const DEFAULT_PAGES: CMSStaticPages = {
  about: `Welcome to RLG Hobby Shop, the Philippines' leading destination for authentic Japanese hobby imports, factory-sealed Trading Card Game booster boxes, Bandai Gunpla model kits, and collector display figures.\n\nFounded in 2024 by passionate hobbyists, we guarantee 100% genuine products with double-boxed collector-grade shipping armor nationwide.`,
  terms: `All sales of factory-sealed booster boxes and graded cards are authentic. Once security seals or plastic wraps are opened or tampered with, returns cannot be accepted due to secondary market volatility.\n\nDeliveries are handled by accredited Philippine air and land logistics couriers with insured tracking.`,
  privacy: `RLG Hobby Shop respects your data privacy. We only collect shipping addresses, email addresses, and phone numbers necessary to deliver your hobby parcels safely. We never sell your personal information to third parties.`,
  faq: `Q: Are all TCG booster boxes factory sealed?\nA: Yes! All Pokémon, One Piece, Hololive, and Weiß Schwarz booster boxes carry intact manufacturer shrink-wrap and security tape.\n\nQ: How fast is nationwide shipping?\nA: Metro Manila orders arrive in 1-2 business days; Provincial Luzon, Visayas, and Mindanao arrive in 3-5 business days.`,
};

const DEFAULT_BLOGS: CMSBlogPost[] = [
  {
    id: "post-1",
    title: "Top 5 One Piece Card Game Meta Decks in OP-07 Egghead",
    category: "TCG Strategy",
    author: "Chief Deck Architect",
    date: "2026-09-20",
    status: "Published",
    summary:
      "A deep dive into Yellow Vegapunk, Blue Doflamingo, and Green Bonney tournament tier lists.",
    content:
      "A deep dive into Yellow Vegapunk, Blue Doflamingo, and Green Bonney tournament tier lists. Learn matchup mulligans, leader powers, and counter strategies to pilot your deck into top cut.\n\nKey staples like 8c Katakuri and 10c Ace remain dominant across regional qualifiers.",
  },
  {
    id: "post-2",
    title: "Beginner Guide: Essential Tools for Building Your First RG Gunpla",
    category: "Gunpla & Modeling",
    author: "Master Builder Ken",
    date: "2026-09-15",
    status: "Published",
    summary:
      "Single blade nippers, sanding sponges, panel lining markers, and topcoat finishes explained.",
    content:
      "Single blade nippers, sanding sponges, panel lining markers, and topcoat finishes explained. Protect your parts and achieve pristine nub removal on Real Grade kits without plastic stress marks.\n\nFinish with a matte topcoat for a display-worthy anime-accurate finish.",
  },
  {
    id: "post-3",
    title: "Anime Scale Figure Care: Cleaning, Display Lighting & UV Protection",
    category: "Figure Collection",
    author: "Curator Mia",
    date: "2026-09-10",
    status: "Published",
    summary:
      "Avoid yellowing, paint transfer, and drooping joints with proper temperature and LED casing.",
    content:
      "Keep your prize and scale figures in pristine showroom condition. Avoid direct sunlight which causes PVC yellowing and pigment bleaching. Use acrylic display cases with dust-proof seals and soft goat-hair brushes for cleaning.",
  },
  {
    id: "post-4",
    title: "Grading TCG Cards in the Philippines: PSA vs BGS vs CGC Guide",
    category: "TCG Preservation",
    author: "Grading Specialist Leo",
    date: "2026-09-05",
    status: "Published",
    summary:
      "Centering, corners, edges, and surface preparation before submitting your grail cards.",
    content:
      "Learn the grading criteria between PSA, Beckett, and CGC. Step-by-step submission preparation: micro-fiber wipe down, penny sleeve insertion without corner dings, and semi-rigid card savers for maximum transit protection.",
  },
  {
    id: "post-5",
    title: "Gunpla Panel Lining: Tamiya Accent Color vs Gundam Marker Fine Point",
    category: "Gunpla & Modeling",
    author: "Master Builder Ken",
    date: "2026-08-28",
    status: "Published",
    summary:
      "Capillary action enameled wash technique vs mechanical precision ink pens compared.",
    content:
      "Enhance every mechanical seam on your High Grade and Master Grade mobile suits. We compare enamel pour washes with alcohol-based fine markers, detailing cleanup with lighter fluid and gloss coat preparation.",
  },
];

export const useCmsStore = defineStore("cmsStore", () => {
  // Synchronous localStorage persistence with defaults
  const banners = useStorage<CMSBanner[]>("rlg-cms-banners", DEFAULT_BANNERS);
  const pages = useStorage<CMSStaticPages>("rlg-cms-pages", DEFAULT_PAGES);
  const blogs = useStorage<CMSBlogPost[]>("rlg-cms-blogs", DEFAULT_BLOGS);

  // Auto-seed missing defaults if cached blog list has fewer than 5
  if (Array.isArray(blogs.value) && blogs.value.length < DEFAULT_BLOGS.length) {
    const existingIds = new Set(blogs.value.map((b) => b.id));
    const missing = DEFAULT_BLOGS.filter((b) => !existingIds.has(b.id));
    if (missing.length > 0) {
      blogs.value = [...blogs.value, ...missing];
    }
  }

  const isLoading = ref(false);
  const isSaving = ref(false);
  const lastSyncTime = ref<number | null>(null);

  // Storefront Modal State for Static Pages (About, Terms, Privacy, FAQ)
  const isPageModalOpen = ref(false);
  const activePageKey = ref<string>("about");

  // Storefront Modal State for Blog Post reader
  const isBlogModalOpen = ref(false);
  const selectedBlogPost = ref<CMSBlogPost | null>(null);

  // ─── Computed Getters ────────────────────────────────────────────────────────
  const activeBanners = computed(() => {
    return banners.value.filter((b) => b.isActive);
  });

  const heroBanner = computed<CMSBanner | null>(() => {
    // Return bnr-1 if active, or first active banner
    const bnr1 = banners.value.find((b) => b.id === "bnr-1");
    if (bnr1 && bnr1.isActive) return bnr1;
    return activeBanners.value.length > 0 ? activeBanners.value[0] : null;
  });

  const promoBanner = computed<CMSBanner | null>(() => {
    // Return bnr-2 if active, or second active banner
    const bnr2 = banners.value.find((b) => b.id === "bnr-2");
    if (bnr2 && bnr2.isActive) return bnr2;
    return activeBanners.value.length > 1 ? activeBanners.value[1] : null;
  });

  const publishedBlogs = computed(() => {
    return blogs.value.filter(
      (b) => !b.status || b.status.toLowerCase() === "published"
    );
  });

  // ─── API Sync Actions ────────────────────────────────────────────────────────
  const fetchCmsData = async () => {
    isLoading.value = true;
    try {
      const res = await fetch(`${API_BASE}/cms`);
      if (res.ok) {
        const json = await res.json();
        if (json.success && json.data) {
          const data: CMSPayload = json.data;
          if (Array.isArray(data.banners) && data.banners.length > 0) {
            banners.value = data.banners;
          }
          if (data.pages && typeof data.pages === "object") {
            pages.value = { ...DEFAULT_PAGES, ...data.pages };
          }
          if (Array.isArray(data.blogs) && data.blogs.length > 0) {
            blogs.value = data.blogs;
          }
          lastSyncTime.value = Date.now();
        }
      }
    } catch (err) {
      console.warn("[CMS Store] fetch error, using local/cached CMS data", err);
    } finally {
      isLoading.value = false;
    }
  };

  const saveBanners = async (newBanners: CMSBanner[]): Promise<boolean> => {
    isSaving.value = true;
    // Immediate reactive local update
    banners.value = [...newBanners];
    try {
      const res = await fetch(`${API_BASE}/cms/banners`, {
        method: "PUT",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({ value: newBanners }),
      });
      return res.ok;
    } catch (err) {
      console.error("[CMS Store] failed to save banners to backend", err);
      return false;
    } finally {
      isSaving.value = false;
    }
  };

  const savePages = async (newPages: CMSStaticPages): Promise<boolean> => {
    isSaving.value = true;
    pages.value = { ...newPages };
    try {
      const res = await fetch(`${API_BASE}/cms/pages`, {
        method: "PUT",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({ value: newPages }),
      });
      return res.ok;
    } catch (err) {
      console.error("[CMS Store] failed to save pages to backend", err);
      return false;
    } finally {
      isSaving.value = false;
    }
  };

  const saveBlogs = async (newBlogs: CMSBlogPost[]): Promise<boolean> => {
    isSaving.value = true;
    blogs.value = [...newBlogs];
    try {
      const res = await fetch(`${API_BASE}/cms/blogs`, {
        method: "PUT",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({ value: newBlogs }),
      });
      return res.ok;
    } catch (err) {
      console.error("[CMS Store] failed to save blogs to backend", err);
      return false;
    } finally {
      isSaving.value = false;
    }
  };

  // ─── Modal Triggers for Storefront ──────────────────────────────────────────
  const openPageModal = (pageKey: string) => {
    activePageKey.value = pageKey;
    isPageModalOpen.value = true;
  };

  const closePageModal = () => {
    isPageModalOpen.value = false;
  };

  const openBlogModal = (post: CMSBlogPost) => {
    selectedBlogPost.value = post;
    isBlogModalOpen.value = true;
  };

  const closeBlogModal = () => {
    isBlogModalOpen.value = false;
    selectedBlogPost.value = null;
  };

  return {
    banners,
    pages,
    blogs,
    isLoading,
    isSaving,
    lastSyncTime,
    // Getters
    activeBanners,
    heroBanner,
    promoBanner,
    publishedBlogs,
    // Actions
    fetchCmsData,
    saveBanners,
    savePages,
    saveBlogs,
    // Modal controls
    isPageModalOpen,
    activePageKey,
    openPageModal,
    closePageModal,
    isBlogModalOpen,
    selectedBlogPost,
    openBlogModal,
    closeBlogModal,
  };
});
