<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CustomerOrder extends Model
{
    use HasFactory;

    protected $table = 'customer_orders';

    protected $fillable = [
        'order_number',
        'order_date',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'city',
        'postal_code',
        'customer_profile_id',
        'user_id',
        'total_amount',
        'payment_method',
        'payment_status',
        'ref_order_status_id',
        'refund_status',
        'refund_amount',
        'invoice_id',
        'packing_slip_printed',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_date' => 'datetime',
            'total_amount' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'packing_slip_printed' => 'boolean',
        ];
    }

    /**
     * Status reference for this order.
     *
     * @return BelongsTo<RefOrderStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(RefOrderStatus::class, 'ref_order_status_id');
    }

    /**
     * Customer profile reference if available.
     *
     * @return BelongsTo<CustomerProfile, $this>
     */
    public function customerProfile(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class, 'customer_profile_id');
    }

    /**
     * User account reference if available.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Line items in this order.
     *
     * @return HasMany<CustomerOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CustomerOrderItem::class, 'customer_order_id');
    }

    /**
     * Fulfillment and shipping tracking record.
     *
     * @return HasOne<CustomerOrderFulfillment, $this>
     */
    public function fulfillment(): HasOne
    {
        return $this->hasOne(CustomerOrderFulfillment::class, 'customer_order_id')->latestOfMany();
    }

    /**
     * All fulfillment history records.
     *
     * @return HasMany<CustomerOrderFulfillment, $this>
     */
    public function fulfillments(): HasMany
    {
        return $this->hasMany(CustomerOrderFulfillment::class, 'customer_order_id');
    }
}
