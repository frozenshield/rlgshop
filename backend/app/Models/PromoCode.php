<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PromoCode extends Model
{
    use HasFactory;

    protected $table = 'promo_codes';

    protected $fillable = [
        'code',
        'type',
        'value',
        'usage_count',
        'usage_limit',
        'expiry_date',
        'is_active',
        'min_order_amount',
        'description',
    ];

    protected $appends = [
        'formatted_discount',
        'is_expired',
        'is_valid',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'usage_count' => 'integer',
            'usage_limit' => 'integer',
            'expiry_date' => 'date:Y-m-d',
            'is_active' => 'boolean',
            'min_order_amount' => 'decimal:2',
        ];
    }

    /**
     * Human-readable formatted discount string matching the UI.
     * e.g. "10% OFF", "₱150 Fixed Reduction", "Free Shipping"
     */
    public function getFormattedDiscountAttribute(): string
    {
        return match ($this->type) {
            'percentage' => ((int) $this->value == $this->value ? (int) $this->value : $this->value).'% OFF',
            'fixed' => '₱'.number_format((float) $this->value, 2).' Fixed Reduction',
            'shipping' => 'Free Shipping',
            default => (string) $this->value,
        };
    }

    /**
     * Check if the promo code is expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        if (empty($this->attributes['expiry_date'])) {
            return false;
        }

        return Carbon::parse($this->attributes['expiry_date'])->endOfDay()->isPast();
    }

    /**
     * Check if the coupon is currently usable.
     */
    public function getIsValidAttribute(): bool
    {
        if (! $this->is_active || $this->getIsExpiredAttribute()) {
            return false;
        }

        if ($this->usage_limit !== null && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * CamelCase alias getter for usageCount.
     */
    public function getUsageCountAttribute(): int
    {
        return (int) ($this->attributes['usage_count'] ?? 0);
    }

    /**
     * CamelCase alias setter for usageCount.
     */
    public function setUsageCountAttribute($value): void
    {
        $this->attributes['usage_count'] = $value;
    }

    /**
     * CamelCase alias getter for usageLimit.
     */
    public function getUsageLimitAttribute(): ?int
    {
        return isset($this->attributes['usage_limit']) ? (int) $this->attributes['usage_limit'] : null;
    }

    /**
     * CamelCase alias setter for usageLimit.
     */
    public function setUsageLimitAttribute($value): void
    {
        $this->attributes['usage_limit'] = $value;
    }

    /**
     * CamelCase alias getter for expiryDate.
     */
    public function getExpiryDateAttribute(): ?string
    {
        if (! isset($this->attributes['expiry_date'])) {
            return null;
        }

        return substr($this->attributes['expiry_date'], 0, 10);
    }

    /**
     * CamelCase alias setter for expiryDate.
     */
    public function setExpiryDateAttribute($value): void
    {
        $this->attributes['expiry_date'] = $value;
    }

    /**
     * CamelCase alias getter for isActive.
     */
    public function getIsActiveAttribute(): bool
    {
        return (bool) ($this->attributes['is_active'] ?? true);
    }

    /**
     * CamelCase alias setter for isActive.
     */
    public function setIsActiveAttribute($value): void
    {
        $this->attributes['is_active'] = $value;
    }
}
