<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefPaymentMerch extends Model
{
    use HasFactory;

    protected $table = 'ref_payment_merch';

    protected $fillable = [
        'ref_payment_method_id',
        'name',
        'code',
        'account_name',
        'account_number',
        'qr_image_url',
        'instructions',
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
     * Scope to active merchants only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Parent payment method.
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(RefPaymentMethod::class, 'ref_payment_method_id');
    }
}
