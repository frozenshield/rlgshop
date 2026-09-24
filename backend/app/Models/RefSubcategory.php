<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefSubcategory extends Model
{
    use HasFactory;

    protected $table = 'ref_subcategories';

    protected $fillable = [
        'ref_category_id',
        'desc',
    ];

    /**
     * Get the category that owns this subcategory.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(RefCategory::class, 'ref_category_id');
    }

    /**
     * Get the products belonging to this subcategory.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'ref_subcategory_id');
    }
}
