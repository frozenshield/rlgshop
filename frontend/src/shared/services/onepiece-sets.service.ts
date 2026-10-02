import type { RefOnePieceSetItem } from "@/shared/types/toy.types";

export interface OnePieceSetsResponse {
  success: boolean;
  count: number;
  data: RefOnePieceSetItem[];
}

export const fetchOnePieceSets = async (): Promise<RefOnePieceSetItem[]> => {
  try {
    const res = await fetch("/api/onepiece-sets");
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const json: OnePieceSetsResponse = await res.json();
    if (json.success && Array.isArray(json.data)) {
      return json.data;
    }
    return [];
  } catch (err) {
    console.warn(
      "Could not fetch One Piece sets from backend, using fallback data:",
      err,
    );
    return [
      {
        id: 1,
        code: "OP-01",
        name: "Romance Dawn",
        product_line: "Main Boosters (OP)",
        set_type: "Booster Pack",
        release_order: 1,
      },
      {
        id: 2,
        code: "OP-02",
        name: "Paramount War",
        product_line: "Main Boosters (OP)",
        set_type: "Booster Pack",
        release_order: 2,
      },
      {
        id: 3,
        code: "OP-03",
        name: "Pillars of Strength",
        product_line: "Main Boosters (OP)",
        set_type: "Booster Pack",
        release_order: 3,
      },
      {
        id: 4,
        code: "OP-04",
        name: "Kingdoms of Intrigue",
        product_line: "Main Boosters (OP)",
        set_type: "Booster Pack",
        release_order: 4,
      },
      {
        id: 5,
        code: "OP-05",
        name: "Awakening of the New Era",
        product_line: "Main Boosters (OP)",
        set_type: "Booster Pack",
        release_order: 5,
      },
      {
        id: 6,
        code: "OP-06",
        name: "Wings of the Captain",
        product_line: "Main Boosters (OP)",
        set_type: "Booster Pack",
        release_order: 6,
      },
      {
        id: 7,
        code: "OP-07",
        name: "500 Years in the Future",
        product_line: "Main Boosters (OP)",
        set_type: "Booster Pack",
        release_order: 7,
      },
      {
        id: 8,
        code: "OP-08",
        name: "Two Legends",
        product_line: "Main Boosters (OP)",
        set_type: "Booster Pack",
        release_order: 8,
      },
      {
        id: 9,
        code: "OP-09",
        name: "Emperors in the New World",
        product_line: "Main Boosters (OP)",
        set_type: "Booster Pack",
        release_order: 9,
      },
      {
        id: 18,
        code: "EB-01",
        name: "Memorial Collection",
        product_line: "Extra Boosters (EB)",
        set_type: "Extra Booster",
        release_order: 18,
      },
      {
        id: 23,
        code: "PRB-01",
        name: "Premium Booster - The Best",
        product_line: "Premium Boosters (PRB)",
        set_type: "Premium Booster",
        release_order: 23,
      },
    ];
  }
};
