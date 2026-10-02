<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBundle extends Model
{
    use HasFactory;

    protected $table = 'product_bundles';

    protected $fillable = [
        'primary_product_id',
        'title',
        'bundle_product_ids',
        'discount_percentage',
        'conversion_lift',
        'badge_text',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'primary_product_id' => 'integer',
            'bundle_product_ids' => 'array',
            'discount_percentage' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Primary product this bundle is anchored to.
     */
    public function primaryProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'primary_product_id');
    }

    /**
     * Get Eloquent models for bundled companion products.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    public function getBundledProductsAttribute()
    {
        $ids = is_array($this->bundle_product_ids) ? $this->bundle_product_ids : [];
        if (empty($ids)) {
            return collect();
        }

        return Product::whereIn('id', $ids)->get();
    }
}

