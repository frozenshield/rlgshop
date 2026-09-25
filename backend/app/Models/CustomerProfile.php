<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProfile extends Model
{
    use HasFactory;

    protected $table = 'customer_profiles';

    protected $fillable = [
        'user_id',
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
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'two_factor_auth' => 'boolean',
            'email_notifications' => 'boolean',
            'order_updates_sms' => 'boolean',
            'marketing_emails' => 'boolean',
            'public_collection' => 'boolean',
        ];
    }

    /**
     * Get the user this customer profile belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
