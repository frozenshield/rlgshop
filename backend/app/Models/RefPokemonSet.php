<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefPokemonSet extends Model
{
    use HasFactory;

    protected $table = 'ref_pokemon_set';

    protected $fillable = [
        'subcategories_id',
        'series',
        'series_years',
        'japanese_set',
        'japanese_code',
        'english_set',
        'set_type',
        'notes',
        'release_order',
    ];

    protected function casts(): array
    {
        return [
            'subcategories_id' => 'integer',
            'release_order' => 'integer',
        ];
    }

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

    /**
     * Get the subcategory this Pokemon set belongs to.
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(RefSubcategory::class, 'subcategories_id');
    }

    /**
     * Compatibility alias for ref_subcategory_id.
     */
    public function getRefSubcategoryIdAttribute(): ?int
    {
        return $this->subcategories_id;
    }
}
