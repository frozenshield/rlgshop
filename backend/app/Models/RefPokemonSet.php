<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefPokemonSet extends Model
{
    use HasFactory;

    protected $table = 'ref_pokemon_set';

    protected $fillable = [
        'series',
        'series_years',
        'japanese_set',
        'japanese_code',
        'english_set',
        'set_type',
        'notes',
        'release_order',
    ];

    /**
     * Scope a query to search across Japanese set, code, English set, or series.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $q) use ($term): void {
            $q->where('japanese_set', 'like', "%{$term}%")
                ->orWhere('japanese_code', 'like', "%{$term}%")
                ->orWhere('english_set', 'like', "%{$term}%")
                ->orWhere('series', 'like', "%{$term}%");
        });
    }

    /**
     * Scope a query to filter by series.
     */
    public function scopeBySeries(Builder $query, ?string $series): Builder
    {
        if (blank($series)) {
            return $query;
        }

        return $query->where('series', $series);
    }
}
