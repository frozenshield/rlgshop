<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'sku',
        'stock',
        'description',
        'ref_brand_id',
        'ref_category_id',
        'ref_subcategory_id',
        'ref_condition_id',
        'ref_pokemon_set_id',
        'condition_id',
        'price',
        'weight',
        'length',
        'width',
        'height',
        'status',
        'rating',
        'review_count',
        'image_url',
        'gallery_images',
    ];

    protected function casts(): array
    {
        return [
            'stock' => 'integer',
            'ref_brand_id' => 'integer',
            'ref_category_id' => 'integer',
            'ref_subcategory_id' => 'integer',
            'ref_condition_id' => 'integer',
            'ref_pokemon_set_id' => 'integer',
            'price' => 'decimal:2',
            'weight' => 'decimal:2',
            'length' => 'decimal:2',
            'width' => 'decimal:2',
            'height' => 'decimal:2',
            'rating' => 'decimal:2',
            'review_count' => 'integer',
            'gallery_images' => 'array',
        ];
    }

    /**
     * Get the brand of this product.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(RefBrand::class, 'ref_brand_id');
    }

    /**
     * Get the condition of this product.
     */
    public function condition(): BelongsTo
    {
        return $this->belongsTo(RefCondition::class, 'ref_condition_id');
    }

    /**
     * Compatibility alias getter for condition_id.
     */
    public function getConditionIdAttribute(): ?int
    {
        return $this->attributes['ref_condition_id'] ?? null;
    }

    /**
     * Compatibility alias setter for condition_id.
     */
    public function setConditionIdAttribute(?int $value): void
    {
        $this->attributes['ref_condition_id'] = $value;
    }

    /**
     * Get the category of this product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(RefCategory::class, 'ref_category_id');
    }

    /**
     * Get the subcategory of this product.
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(RefSubcategory::class, 'ref_subcategory_id');
    }

    /**
     * Get all reviews for this product.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the Pokemon set reference if applicable.
     */
    public function pokemonSet(): BelongsTo
    {
        return $this->belongsTo(RefPokemonSet::class, 'ref_pokemon_set_id');
    }
}
