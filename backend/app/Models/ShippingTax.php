<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingTax extends Model
{
    use HasFactory;

    protected $table = 'shipping_tax';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'standard_shipping_fee',
        'free_shipping_threshold',
        'vat_percentage',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'standard_shipping_fee' => 'decimal:2',
            'free_shipping_threshold' => 'decimal:2',
            'vat_percentage' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope a query to only include active shipping and tax configurations.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get current active shipping and tax configuration or fallback to standard defaults.
     */
    public static function getActive(): self
    {
        return static::active()->latest('id')->first() ?? static::create([
            'standard_shipping_fee' => 100.00,
            'free_shipping_threshold' => 2500.00,
            'vat_percentage' => 12.00,
            'is_active' => true,
        ]);
    }

    /**
     * Compatibility alias getter for flat_shipping_rate.
     */
    public function getFlatShippingRateAttribute(): float
    {
        return (float) $this->standard_shipping_fee;
    }

    /**
     * Compatibility alias setter for flat_shipping_rate.
     */
    public function setFlatShippingRateAttribute(float|int|string $value): void
    {
        $this->attributes['standard_shipping_fee'] = $value;
    }

    /**
     * Compatibility alias getter for tax_rate_percent.
     */
    public function getTaxRatePercentAttribute(): float
    {
        return (float) $this->vat_percentage;
    }

    /**
     * Compatibility alias setter for tax_rate_percent.
     */
    public function setTaxRatePercentAttribute(float|int|string $value): void
    {
        $this->attributes['vat_percentage'] = $value;
    }
}
