<?php

namespace App\Services;

use App\Models\RefOnePieceSet;
use Illuminate\Database\Eloquent\Collection;

class OnePieceSetService
{
    /**
     * Retrieve One Piece sets filtered by query criteria.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, RefOnePieceSet>
     */
    public function getSets(array $filters): Collection
    {
        $query = RefOnePieceSet::query();

        if (! empty($filters['product_line'])) {
            $query->where('product_line', $filters['product_line']);
        }

        if (! empty($filters['set_type'])) {
            $query->where('set_type', $filters['set_type']);
        }

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->orderBy('release_order', 'asc')->get();
    }

    /**
     * Show a specific One Piece set by id or code/name.
     */
    public function show(string $identifier): ?RefOnePieceSet
    {
        if (is_numeric($identifier)) {
            return RefOnePieceSet::find($identifier);
        }

        return RefOnePieceSet::where('code', strtoupper(trim($identifier)))
            ->orWhere('name', 'like', "%{$identifier}%")
            ->first();
    }

    /**
     * Get product lines summary with counts and codes.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getProductLines(): array
    {
        $lines = RefOnePieceSet::select('product_line', 'set_type')
            ->selectRaw('COUNT(id) as sets_count')
            ->groupBy('product_line', 'set_type')
            ->orderBy('product_line', 'asc')
            ->get();

        return $lines->map(function ($line): array {
            return [
                'product_line' => $line->product_line,
                'set_type' => $line->set_type,
                'sets_count' => (int) $line->sets_count,
            ];
        })->toArray();
    }
}
