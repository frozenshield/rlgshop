<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefPaymentMethod extends Model
{
    use HasFactory;

    protected $table = 'ref_payment_method';

    protected $fillable = [
        'name',
        'code',
        'label',
        'status',
        'description',
        'icon',
    ];

    /**
     * Scope a query to only include active payment methods.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Check if payment method is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
