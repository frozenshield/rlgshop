<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefCondition extends Model
{
    use HasFactory;

    protected $table = 'ref_conditions';

    protected $fillable = [
        'desc',
    ];

    /**
     * Get all products with this condition.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'ref_condition_id');
    }
}
