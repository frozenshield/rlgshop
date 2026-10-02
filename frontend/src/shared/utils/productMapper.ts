import type {
  ToyProduct,
  ToyCategory,
  TcgSubCategory,
} from "@/shared/types/toy.types";

/**
 * Maps a Laravel database Product record (with optional relationships: category, subcategory, brand, condition, pokemon_set)
 * to the frontend ToyProduct interface.
 */
export function mapApiProductToToy(p: any): ToyProduct {
  if (!p) {
    throw new Error("Cannot map null or undefined product");
  }

  // 1. Resolve Category
  const catDesc = (p.category?.desc || "").toLowerCase();
  let category: ToyCategory = "tcg";
  if (p.ref_category_id === 2 || catDesc.includes("gunpla")) {
    category = "gunpla";
  } else if (p.ref_category_id === 3 || catDesc.includes("figure")) {
    category = "anime-figures";
  } else if (p.ref_category_id === 4 || catDesc.includes("merch")) {
    category = "anime-merchandise";
  } else if (
    p.ref_category_id === 5 ||
    catDesc.includes("plush") ||
    catDesc.includes("toy")
  ) {
    category = "toys-plushies";
  }

  // 2. Resolve TCG Series if applicable
  const subDesc = (p.subcategory?.desc || "").toLowerCase();
  const subId = Number(p.ref_subcategory_id || p.subcategory?.id || 0);
  let tcgSeries: TcgSubCategory | undefined = undefined;
  if (category === "tcg") {
    if (subId === 1 || subDesc.includes("pokemon")) {
      tcgSeries = "pokemon";
    } else if (
      subId === 2 ||
      subDesc.includes("yugioh") ||
      subDesc.includes("yu-gi-oh")
    ) {
      tcgSeries = "yugioh";
    } else if (subId === 3 || subDesc.includes("duel")) {
      tcgSeries = "duel-masters";
    } else if (
      subId === 4 ||
      subDesc.includes("one piece") ||
      subDesc.includes("one-piece")
    ) {
      tcgSeries = "one-piece";
    } else if (subId === 5 || subDesc.includes("gundam")) {
      tcgSeries = "gundam-tcg";
    } else if (subId === 6 || subDesc.includes("hololive")) {
      tcgSeries = "hololive";
    } else if (
      subId === 7 ||
      subDesc.includes("dragon ball") ||
      subDesc.includes("dragonball")
    ) {
      tcgSeries = "dragon-ball";
    } else if (subId === 8 || subDesc.includes("weiss")) {
      tcgSeries = "weiss-schwarz";
    } else if (
      subId === 9 ||
      subDesc.includes("battle spirit") ||
      subDesc.includes("battle-spirit")
    ) {
      tcgSeries = "battle-spirit";
    } else {
      // Default to pokemon if TCG
      tcgSeries = "pokemon";
    }
  }

  // 3. Numbers & Pricing
  const price =
    typeof p.price === "number" ? p.price : parseFloat(p.price || "0") || 0;
  const rating =
    typeof p.rating === "number"
      ? p.rating
      : parseFloat(p.rating || "4.8") || 4.8;
  const reviewCount = typeof p.review_count === "number" ? p.review_count : 0;
  const stock =
    typeof p.stock === "number" ? p.stock : parseInt(p.stock || "0", 10) || 0;

  // 4. Image Fallbacks
  let imageUrl = p.image_url;
  if (!imageUrl || typeof imageUrl !== "string" || imageUrl.trim() === "") {
    if (category === "gunpla") {
      imageUrl =
        "https://images.unsplash.com/photo-1594787318286-3d835c1d207f?w=600&auto=format&fit=crop&q=80";
    } else if (category === "anime-figures") {
      imageUrl =
        "https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=600&auto=format&fit=crop&q=80";
    } else {
      imageUrl =
        "https://images.unsplash.com/photo-1628155930542-3c7a64e2c833?w=600&auto=format&fit=crop&q=80";
    }
  }

  // 5. Gallery Images
  let galleryImages: string[] = [];
  if (Array.isArray(p.gallery_images) && p.gallery_images.length > 0) {
    galleryImages = p.gallery_images;
  } else if (imageUrl) {
    galleryImages = [imageUrl];
  }

  // 6. Tags & Set Codes
  const tags: string[] = [];
  if (tcgSeries) tags.push(tcgSeries);
  if (p.subcategory?.desc) tags.push(p.subcategory.desc);
  if (p.brand?.name) tags.push(p.brand.name);
  if (p.pokemon_set?.japanese_set) tags.push(p.pokemon_set.japanese_set);
  if (p.name && p.name.includes("151")) tags.push("151");
  if (p.name && p.name.toLowerCase().includes("charizard"))
    tags.push("Charizard");

  // One Piece Set Code extraction
  let onePieceSetCode: string | undefined = undefined;
  if (tcgSeries === "one-piece" || (p.name && /OP-|EB-|PRB-|ST-/i.test(p.name))) {
    const opMatch =
      (p.name || "").match(/\b(OP-\d+|EB-\d+|PRB-\d+|ST-\d+)\b/i) ||
      (p.sku || "").match(/\b(OP-\d+|EB-\d+|PRB-\d+|ST-\d+)\b/i);
    const code = opMatch?.[1]?.toUpperCase();
    if (code) {
      onePieceSetCode = code;
      tags.push(code);
    }
  }

  // 7. Slug
  const slug =
    p.slug ||
    p.name
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/(^-|-$)/g, "");

  return {
    id: String(p.id),
    name: p.name,
    slug,
    description: p.description || "",
    category,
    tcgSeries,
    ageGroup: "9-12",
    pokemonType:
      tcgSeries === "pokemon"
        ? "Pokémon ⚡"
        : tcgSeries === "yugioh"
          ? "Yu-Gi-Oh! 👁️"
          : tcgSeries === "duel-masters"
            ? "Duel Masters ⚔️"
            : tcgSeries === "one-piece"
              ? "One Piece 🏴‍☠️"
              : tcgSeries === "gundam-tcg"
                ? "Gundam TCG 🤖"
                : tcgSeries === "hololive"
                  ? "Hololive 🎤"
                  : tcgSeries === "dragon-ball"
                    ? "Dragon Ball 🐉"
                    : tcgSeries === "weiss-schwarz"
                      ? "Weiß Schwarz ✨"
                      : tcgSeries === "battle-spirit"
                        ? "Battle Spirits 🔥"
                        : undefined,
    price,
    originalPrice: price > 0 ? Math.round(price * 1.15) : undefined,
    discountPercent: 15,
    rating: rating > 0 ? rating : 4.8,
    reviewCount,
    stock,
    brand: p.brand?.name || "Official Japanese Vault",
    condition: p.condition?.desc || "Brand New / Factory Sealed",
    imageUrl,
    galleryImages,
    tags,
    pokemonSetSeries: p.pokemon_set?.series || undefined,
    pokemonSetCode:
      p.pokemon_set?.japanese_code || p.pokemon_set?.series || undefined,
    onePieceSetCode,
    isFeatured: true,
    isBestSeller: Number(p.id) <= 6,
    isNewArrival: true,
    safetyWarning:
      "Choking hazard: contains small collectible parts. Not suitable for children under 3 years.",
    features: [
      "100% Genuine Japanese / Asian English collector distribution",
      "Factory-sealed protective packaging",
      "Authenticity guaranteed by RLG Shop quality control",
    ],
  };
}
