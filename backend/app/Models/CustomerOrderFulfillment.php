<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerOrderFulfillment extends Model
{
    use HasFactory;

    protected $table = 'customer_order_fulfillments';

    protected $fillable = [
        'customer_order_id',
        'ref_shipping_carrier_id',
        'tracking_number',
        'shipping_label_url',
        'packing_slip_printed',
        'shipped_at',
        'delivered_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'packing_slip_printed' => 'boolean',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * Order associated with this fulfillment.
     *
     * @return BelongsTo<CustomerOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'customer_order_id');
    }

    /**
     * Shipping carrier selected for this fulfillment.
     *
     * @return BelongsTo<RefShippingCarrier, $this>
     */
    public function carrier(): BelongsTo
    {
        return $this->belongsTo(RefShippingCarrier::class, 'ref_shipping_carrier_id');
    }
}
