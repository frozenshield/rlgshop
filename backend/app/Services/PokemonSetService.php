<?php

namespace App\Services;

use App\Models\RefPokemonSet;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PokemonSetService
{
    public function getSets(array $filters): Collection|LengthAwarePaginator
    {
        $query = RefPokemonSet::query();

        if (!empty($filters['series'])) {
            $query->bySeries($filters['series']);
        }

        if (!empty($filters['set_type'])) {
            $query->where('set_type', $filters['set_type']);
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['code'])) {
            $code = trim($filters['code']);
            $query->where('japanese_code', 'like', "%{$code}%");
        }

        $query->orderBy('release_order', 'asc');

        if (!empty($filters['paginate']) && filter_var($filters['paginate'], FILTER_VALIDATE_BOOLEAN)) {
            $perPage = (int) ($filters['per_page'] ?? 25);
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    public function show(string $identifier): ?RefPokemonSet
    {
        if (is_numeric($identifier)) {
            return RefPokemonSet::find($identifier);
        }

        return RefPokemonSet::where('japanese_code', $identifier)
            ->orWhere('english_code', $identifier)
            ->orWhere('japanese_set', $identifier)
            ->orWhere('english_set', $identifier)
            ->first();
    }

    public function getSeries(): array
    {
        $seriesSummary = RefPokemonSet::select('series')
            ->selectRaw('MIN(series_years) as series_years, COUNT(id) as sets_count')
            ->groupBy('series')
            ->orderBy('id', 'desc')
            ->get();

        return $seriesSummary->map(function ($s) {
            return [
                'series' => $s->series,
                'series_years' => $s->series_years ?? '',
                'sets_count' => $s->sets_count,
            ];
        })->toArray();
    }
}
