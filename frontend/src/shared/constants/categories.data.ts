import type { ToyCategory, TcgSubCategory } from "../types/toy.types";

export interface TcgSeriesInfo {
  id: TcgSubCategory;
  name: string;
  shortName: string;
  icon: string;
  badgeColor: string;
  accentColor: string;
  bgClass: string;
  description: string;
}

export interface CategoryInfo {
  id: ToyCategory;
  name: string;
  icon: string;
  color: string;
  bgClass: string;
  description: string;
  subCategories?: TcgSeriesInfo[];
}

export const TCG_SERIES_DATA: TcgSeriesInfo[] = [
  {
    id: "pokemon",
    name: "Pokémon TCG",
    shortName: "Pokémon",
    icon: "⚡",
    badgeColor: "bg-yellow-400 text-slate-900 border-yellow-500",
    accentColor: "#FFCB05",
    bgClass:
      "bg-yellow-50 text-yellow-800 border-yellow-200 hover:bg-yellow-100",
    description:
      "Scarlet & Violet, 151, Elite Trainer Boxes, Booster Boxes, PSA Graded Holos & Ultra Rares",
  },
  {
    id: "yugioh",
    name: "Yu-Gi-Oh! OCG / TCG",
    shortName: "Yu-Gi-Oh!",
    icon: "👁️",
    badgeColor: "bg-purple-700 text-white border-purple-800",
    accentColor: "#7E22CE",
    bgClass: "bg-purple-50 text-purple-800 border-purple-200 hover:bg-purple-100",
    description:
      "Konami Official Card Game, Quarter Century Secret Rares, Booster Boxes & Structure Decks",
  },
  {
    id: "duel-masters",
    name: "Duel Masters TCG",
    shortName: "Duel Masters",
    icon: "⚔️",
    badgeColor: "bg-amber-600 text-white border-amber-700",
    accentColor: "#D97706",
    bgClass: "bg-amber-50 text-amber-800 border-amber-200 hover:bg-amber-100",
    description:
      "Takara Tomy Japanese Booster Boxes, Revolution Final Chapter, Super Decks & Legend Cards",
  },
  {
    id: "one-piece",
    name: "One Piece Card Game",
    shortName: "One Piece",
    icon: "🏴‍☠️",
    badgeColor: "bg-red-600 text-white border-red-700",
    accentColor: "#EE1515",
    bgClass: "bg-red-50 text-red-700 border-red-200 hover:bg-red-100",
    description:
      "Bandai OP-01 through OP-09 Booster Boxes, Manga Parallel Rares, Starter Decks & Leaders",
  },
  {
    id: "gundam-tcg",
    name: "Gundam Card Game",
    shortName: "Gundam TCG",
    icon: "🤖",
    badgeColor: "bg-blue-600 text-white border-blue-700",
    accentColor: "#2563EB",
    bgClass: "bg-blue-50 text-blue-800 border-blue-200 hover:bg-blue-100",
    description:
      "Bandai Gundam Card Game, Mobile Suit Pilot cards, Starter Decks & Collector Booster Boxes",
  },
  {
    id: "hololive",
    name: "Hololive Official Card Game",
    shortName: "Hololive",
    icon: "🎤",
    badgeColor: "bg-cyan-500 text-white border-cyan-600",
    accentColor: "#00D1FF",
    bgClass: "bg-cyan-50 text-cyan-800 border-cyan-200 hover:bg-cyan-100",
    description:
      "Official Hololive OCG, Cover Corp VTuber Booster Boxes, Signed Foil Cards & Oshi Decks",
  },
  {
    id: "dragon-ball",
    name: "Dragon Ball Super Card Game",
    shortName: "Dragon Ball",
    icon: "🐉",
    badgeColor: "bg-orange-500 text-white border-orange-600",
    accentColor: "#F97316",
    bgClass: "bg-orange-50 text-orange-800 border-orange-200 hover:bg-orange-100",
    description:
      "Bandai Fusion World & Masters Booster Boxes, Secret Rares, God Rares & Starter Decks",
  },
  {
    id: "weiss-schwarz",
    name: "Weiß Schwarz",
    shortName: "Weiß Schwarz",
    icon: "✨",
    badgeColor: "bg-indigo-600 text-white border-indigo-700",
    accentColor: "#4F46E5",
    bgClass:
      "bg-indigo-50 text-indigo-800 border-indigo-200 hover:bg-indigo-100",
    description:
      "Bushiroad Anime Crossover Sets, Frieren, Bocchi, Hololive & Gold Foil SP Signature Cards",
  },
  {
    id: "battle-spirit",
    name: "Battle Spirits TCG",
    shortName: "Battle Spirits",
    icon: "🔥",
    badgeColor: "bg-rose-600 text-white border-rose-700",
    accentColor: "#E11D48",
    bgClass: "bg-rose-50 text-rose-800 border-rose-200 hover:bg-rose-100",
    description:
      "Bandai Battle Spirits Booster Packs, Saga Brave Decks, X-Rare & Collaboration Cards",
  },
];

export const CATEGORIES_DATA: CategoryInfo[] = [
  {
    id: "tcg",
    name: "TCG (Trading Cards)",
    icon: "🃏",
    color: "#3B4CCA",
    bgClass: "bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100",
    description:
      "Pokémon, Yu-Gi-Oh!, One Piece, Dragon Ball, Gundam, Hololive & more Japanese Booster Boxes & Singles",
    subCategories: TCG_SERIES_DATA,
  },
  {
    id: "gunpla",
    name: "Gunpla & Model Kits",
    icon: "🤖",
    color: "#0284C7",
    bgClass: "bg-sky-50 text-sky-700 border-sky-200 hover:bg-sky-100",
    description:
      "High Grade (HG), Real Grade (RG), Master Grade (MG), Perfect Grade (PG) & mecha tools",
  },
  {
    id: "anime-figures",
    name: "Anime Figures",
    icon: "⚡",
    color: "#EE1515",
    bgClass: "bg-red-50 text-red-600 border-red-200 hover:bg-red-100",
    description:
      "Scale statues, Nendoroids, Pop Up Parade, action figures & articulated battle poses",
  },
  {
    id: "anime-merchandise",
    name: "Anime Merchandise",
    icon: "🎁",
    color: "#FFB703",
    bgClass: "bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100",
    description:
      "Authentic plushies, die-cast Pokéballs, collector pin badges, keychains, apparel & trainer gear",
  },
  {
    id: "toys-plushies",
    name: "Toys & Plushies",
    icon: "🧸",
    color: "#EC4899",
    bgClass: "bg-pink-50 text-pink-700 border-pink-200 hover:bg-pink-100",
    description:
      "Cuddly plushies, Nesoberi, squish pillows, capsule toys, Gachapon, blind boxes & novelty toys",
  },
];
