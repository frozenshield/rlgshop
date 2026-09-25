<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefBrand extends Model
{
    use HasFactory;

    protected $table = 'ref_brands';

    protected $fillable = [
        'name',
    ];

    /**
     * Get all products associated with this brand.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'ref_brand_id');
    }
}
