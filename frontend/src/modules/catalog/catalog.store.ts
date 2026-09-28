import { defineStore } from "pinia";
import { ref } from "vue";
import type {
  ToyProduct,
  ToyCategory,
  TcgSubCategory,
  AgeGroup,
} from "@/shared/types/toy.types";
import { MOCK_TOYS_DATA } from "@/shared/constants/mock-toys.data";
import { mapApiProductToToy } from "@/shared/utils/productMapper";

export const useCatalogStore = defineStore("catalogStore", () => {
  // State
  const toys = ref<ToyProduct[]>(MOCK_TOYS_DATA);
  const isLoading = ref(false);
  const isLoaded = ref(false);
  const searchQuery = ref("");
  const selectedCategory = ref<ToyCategory | "all">("all");
  const selectedTcgSeries = ref<TcgSubCategory | "all">("all");
  const selectedPokemonSeries = ref<string | "all">("all");
  const selectedPokemonSet = ref<string | "all">("all");
  const selectedAgeGroup = ref<AgeGroup | "all">("all");
  const maxPriceFilter = ref<number>(15000);
  const minRatingFilter = ref<number>(0);
  const sortBy = ref<
    "featured" | "price-asc" | "price-desc" | "rating" | "newest"
  >("featured");
  const onlyInStock = ref(false);
  const onlyDiscounted = ref(false);

  // Quick view / detail modal state
  const selectedToyForModal = ref<ToyProduct | null>(null);
  const isDetailModalOpen = ref(false);

  // Actions
  const fetchProducts = async (force = false) => {
    if (isLoaded.value && !force && toys.value.length > 0) return;
    isLoading.value = true;
    try {
      const res = await fetch("/api/products?per_page=100");
      if (res.ok) {
        const json = await res.json();
        const items = Array.isArray(json.data)
          ? json.data
          : Array.isArray(json)
            ? json
            : [];
        if (items.length > 0) {
          toys.value = items.map(mapApiProductToToy);
          isLoaded.value = true;
        }
      }
    } catch (e) {
      console.warn(
        "Could not fetch database products for catalog, using fallback",
        e,
      );
    } finally {
      isLoading.value = false;
    }
  };

  // Auto-fetch on store creation
  fetchProducts();

  const openDetailModal = (toy: ToyProduct) => {
    selectedToyForModal.value = toy;
    isDetailModalOpen.value = true;
  };

  const closeDetailModal = () => {
    isDetailModalOpen.value = false;
    selectedToyForModal.value = null;
  };

  const setCategory = (cat: ToyCategory | "all") => {
    selectedCategory.value = cat;
    if (cat !== "tcg") {
      selectedTcgSeries.value = "all";
      selectedPokemonSeries.value = "all";
      selectedPokemonSet.value = "all";
    }
  };

  const setTcgSeries = (series: TcgSubCategory | "all") => {
    selectedTcgSeries.value = series;
    if (series !== "all") {
      selectedCategory.value = "tcg";
    }
    if (series !== "pokemon") {
      selectedPokemonSeries.value = "all";
      selectedPokemonSet.value = "all";
    }
  };

  const setPokemonSeries = (series: string | "all") => {
    selectedPokemonSeries.value = series;
    selectedPokemonSet.value = "all";
  };

  const setPokemonSet = (setId: string | "all") => {
    selectedPokemonSet.value = setId;
  };

  const setAgeGroup = (age: AgeGroup | "all") => {
    selectedAgeGroup.value = age;
  };

  const setSearchQuery = (q: string) => {
    searchQuery.value = q;
  };

  const resetFilters = () => {
    searchQuery.value = "";
    selectedCategory.value = "all";
    selectedTcgSeries.value = "all";
    selectedPokemonSeries.value = "all";
    selectedPokemonSet.value = "all";
    selectedAgeGroup.value = "all";
    maxPriceFilter.value = 15000;
    minRatingFilter.value = 0;
    sortBy.value = "featured";
    onlyInStock.value = false;
    onlyDiscounted.value = false;
  };

  return {
    toys,
    isLoading,
    isLoaded,
    searchQuery,
    selectedCategory,
    selectedTcgSeries,
    selectedPokemonSeries,
    selectedPokemonSet,
    selectedAgeGroup,
    maxPriceFilter,
    minRatingFilter,
    sortBy,
    onlyInStock,
    onlyDiscounted,
    selectedToyForModal,
    isDetailModalOpen,

    fetchProducts,
    openDetailModal,
    closeDetailModal,
    setCategory,
    setTcgSeries,
    setPokemonSeries,
    setPokemonSet,
    setAgeGroup,
    setSearchQuery,
    resetFilters,
  };
});
