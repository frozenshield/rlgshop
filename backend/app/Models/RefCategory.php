<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefCategory extends Model
{
    use HasFactory;

    protected $table = 'ref_categories';

    protected $fillable = [
        'desc',
    ];

    /**
     * Get the subcategories for this category.
     */
    public function subcategories(): HasMany
    {
        return $this->hasMany(RefSubcategory::class, 'ref_category_id');
    }

    /**
     * Get the products belonging to this category.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'ref_category_id');
    }
}
