<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefOnePieceSet extends Model
{
    use HasFactory;

    protected $table = 'ref_onepiece_set';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'subcategories_id',
        'code',
        'name',
        'product_line',
        'set_type',
        'release_date',
        'status',
        'description',
        'notes',
        'chase_cards',
        'is_subset',
        'release_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subcategories_id' => 'integer',
            'chase_cards' => 'array',
            'is_subset' => 'boolean',
            'release_order' => 'integer',
        ];
    }

    /**
     * Scope a query to search across code, name, or product line.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $q) use ($term): void {
            $q->where('code', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%")
                ->orWhere('product_line', 'like', "%{$term}%")
                ->orWhere('set_type', 'like', "%{$term}%");
        });
    }

    /**
     * Scope a query to filter by product line.
     */
    public function scopeByProductLine(Builder $query, ?string $productLine): Builder
    {
        if (blank($productLine)) {
            return $query;
        }

        return $query->where('product_line', 'like', "%{$productLine}%");
    }

    /**
     * Scope a query to only include Extra Boosters (EB).
     */
    public function scopeExtraBoosters(Builder $query): Builder
    {
        return $query->where('code', 'like', 'EB-%');
    }

    /**
     * Scope a query to only include Premium Boosters (PRB).
     */
    public function scopePremiumBoosters(Builder $query): Builder
    {
        return $query->where('code', 'like', 'PRB-%');
    }

    /**
     * Scope a query to only include Main Boosters (OP).
     */
    public function scopeMainBoosters(Builder $query): Builder
    {
        return $query->where('code', 'like', 'OP-%');
    }

    /**
     * Get the subcategory this One Piece set belongs to.
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
