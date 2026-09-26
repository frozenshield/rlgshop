import type {
  RefPokemonSeriesItem,
  RefPokemonSetItem,
} from "@/shared/types/toy.types";

export interface PokemonSeriesResponse {
  success: boolean;
  data: RefPokemonSeriesItem[];
}

export interface PokemonSetsResponse {
  success: boolean;
  count: number;
  data: RefPokemonSetItem[];
}

export const fetchPokemonSeries = async (): Promise<RefPokemonSeriesItem[]> => {
  try {
    const res = await fetch("/api/pokemon-sets/series");
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const json: PokemonSeriesResponse = await res.json();
    if (json.success && Array.isArray(json.data)) {
      return json.data;
    }
    return [];
  } catch (err) {
    console.warn(
      "Could not fetch Pokemon series from backend, using fallback data:",
      err,
    );
    return [
      {
        series: "Scarlet & Violet",
        series_years: "2023-2025",
        sets_count: 15,
        min_order: 1,
      },
      {
        series: "Sword & Shield",
        series_years: "2019-2022",
        sets_count: 23,
        min_order: 16,
      },
      {
        series: "Sun & Moon",
        series_years: "2016-2019",
        sets_count: 26,
        min_order: 39,
      },
      {
        series: "XY",
        series_years: "2013-2016",
        sets_count: 13,
        min_order: 65,
      },
      {
        series: "Black & White",
        series_years: "2010-2013",
        sets_count: 9,
        min_order: 78,
      },
      {
        series: "Original / Classic Series",
        series_years: "1996-2000",
        sets_count: 1,
        min_order: 87,
      },
    ];
  }
};

export const fetchPokemonSets = async (
  series?: string,
): Promise<RefPokemonSetItem[]> => {
  try {
    const url =
      series && series !== "all"
        ? `/api/pokemon-sets?series=${encodeURIComponent(series)}`
        : "/api/pokemon-sets";
    const res = await fetch(url);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const json: PokemonSetsResponse = await res.json();
    if (json.success && Array.isArray(json.data)) {
      return json.data;
    }
    return [];
  } catch (err) {
    console.warn(
      "Could not fetch Pokemon sets from backend, using fallback data:",
      err,
    );
    return [
      {
        id: 1,
        series: "Scarlet & Violet",
        series_years: "2023-2025",
        japanese_set: "Scarlet ex / Violet ex",
        japanese_code: "SV1S / SV1V",
        english_set: "Scarlet & Violet Base Set",
        set_type: "Main Expansion",
        release_order: 1,
      },
      {
        id: 4,
        series: "Scarlet & Violet",
        series_years: "2023-2025",
        japanese_set: "Pokémon Card 151",
        japanese_code: "SV2a",
        english_set: "Scarlet & Violet-151",
        set_type: "Subset",
        release_order: 4,
      },
      {
        id: 8,
        series: "Scarlet & Violet",
        series_years: "2023-2025",
        japanese_set: "Shiny Treasure ex",
        japanese_code: "SV4a",
        english_set: "Paldean Fates",
        set_type: "High-Class Pack",
        release_order: 8,
      },
    ];
  }
};
