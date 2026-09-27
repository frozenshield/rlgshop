<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerProfile extends Model
{
    use HasFactory;

    protected $table = 'customer_profiles';

    protected $fillable = [
        'user_id',
        'customer_shippment_address_id',
        'name',
        'username',
        'phone',
        'avatar',
        'address_line1',
        'city',
        'postal_code',
        'country',
        'favorite_franchise',
        'bio',
        'two_factor_auth',
        'email_notifications',
        'order_updates_sms',
        'marketing_emails',
        'currency_preference',
        'public_collection',
        'segment',
        'segment_rank',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'customer_shippment_address_id' => 'integer',
            'two_factor_auth' => 'boolean',
            'email_notifications' => 'boolean',
            'order_updates_sms' => 'boolean',
            'marketing_emails' => 'boolean',
            'public_collection' => 'boolean',
        ];
    }

    /**
     * Get the user this customer profile belongs to.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the active shipment address for this customer profile.
     *
     * @return BelongsTo<CustomerShippmentAddress, $this>
     */
    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerShippmentAddress::class, 'customer_shippment_address_id');
    }

    /**
     * Customer orders placed by this profile.
     *
     * @return HasMany<CustomerOrder, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(CustomerOrder::class, 'customer_profile_id');
    }
}
