<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerShippmentAddress extends Model
{
    use HasFactory;

    protected $table = 'customer_shippment_address';

    protected $fillable = [
        'user_id',
        'recipient_name',
        'phone',
        'shipping_address',
        'city',
        'postal_code',
        'country',
        'is_default',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<CustomerProfile, $this>
     */
    public function profiles(): HasMany
    {
        return $this->hasMany(CustomerProfile::class, 'customer_shippment_address_id');
    }
}
