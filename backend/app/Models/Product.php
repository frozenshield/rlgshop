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
        'stock',
        'description',
        'ref_category_id',
        'ref_subcategory_id',
        'price',
        'rating',
        'review_count',
        'image_url',
        'gallery_images',
    ];

    protected function casts(): array
    {
        return [
            'stock' => 'integer',
            'price' => 'decimal:2',
            'rating' => 'decimal:2',
            'review_count' => 'integer',
            'gallery_images' => 'array',
        ];
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
}
