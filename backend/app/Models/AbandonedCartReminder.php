<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbandonedCartReminder extends Model
{
    use HasFactory;

    protected $table = 'abandoned_cart_reminders';

    protected $fillable = [
        'customer_id',
        'customer_name',
        'customer_email',
        'items_count',
        'total_value',
        'cart_items',
        'recovery_token',
        'discount_code',
        'discount_percent',
        'reminder_sent',
        'reminder_sent_at',
        'recovered',
        'recovered_at',
        'last_active_at',
    ];

    protected function casts(): array
    {
        return [
            'customer_id' => 'integer',
            'items_count' => 'integer',
            'total_value' => 'decimal:2',
            'cart_items' => 'array',
            'discount_percent' => 'integer',
            'reminder_sent' => 'boolean',
            'reminder_sent_at' => 'datetime',
            'recovered' => 'boolean',
            'recovered_at' => 'datetime',
            'last_active_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}

