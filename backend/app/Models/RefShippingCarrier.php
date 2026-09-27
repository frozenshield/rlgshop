<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefShippingCarrier extends Model
{
    use HasFactory;

    protected $table = 'ref_shipping_carrier';

    protected $fillable = [
        'name',
        'code',
        'short_name',
        'tracking_url_template',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Fulfillments handled by this carrier.
     *
     * @return HasMany<CustomerOrderFulfillment, $this>
     */
    public function fulfillments(): HasMany
    {
        return $this->hasMany(CustomerOrderFulfillment::class, 'ref_shipping_carrier_id');
    }
}
