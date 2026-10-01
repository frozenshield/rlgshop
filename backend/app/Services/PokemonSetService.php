<?php

namespace App\Services;

use App\Models\RefPokemonSet;
use Illuminate\Database\Eloquent\Collection;

class PokemonSetService
{
    public function getSets(array $filters): Collection
    {
        $query = RefPokemonSet::query();

        if (! empty($filters['series'])) {
            $query->where('series', $filters['series']);
        }

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search): void {
                $q->where('english_set', 'like', "%{$search}%")
                    ->orWhere('japanese_set', 'like', "%{$search}%")
                    ->orWhere('english_code', 'like', "%{$search}%")
                    ->orWhere('japanese_code', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('id', 'desc')->get();
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
