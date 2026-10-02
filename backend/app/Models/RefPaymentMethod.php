<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * Associated sub-merchants / wallets (e.g. for QR payment).
     */
    public function merchants(): HasMany
    {
        return $this->hasMany(RefPaymentMerch::class, 'ref_payment_method_id');
    }
}
