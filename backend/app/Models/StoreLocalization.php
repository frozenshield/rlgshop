<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreLocalization extends Model
{
    use HasFactory;

    protected $table = 'store_localization';

    protected $fillable = [
        'store_legal_name',
        'brand_logo_url',
        'brand_logo_title',
        'brand_logo_subtitle',
        'ref_currency_id',
        'currency_code',
        'timezone',
        'ref_weight_unit_id',
        'weight_unit_code',
        'date_format',
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(RefCurrency::class, 'ref_currency_id');
    }

    public function weightUnit(): BelongsTo
    {
        return $this->belongsTo(RefWeightUnit::class, 'ref_weight_unit_id');
    }

    /**
     * Get or initialize the singleton store localization settings.
     */
    public static function getSettings(): self
    {
        $settings = static::with(['currency', 'weightUnit'])->first();

        if ($settings) {
            return $settings;
        }

        $currency = RefCurrency::where('code', 'PHP')->first();
        $weightUnit = RefWeightUnit::where('code', 'kg_g')->first();

        return static::create([
            'store_legal_name' => 'RLG Hobby Shop',
            'brand_logo_url' => '/logo.png',
            'brand_logo_title' => 'RLG Online Shop',
            'brand_logo_subtitle' => 'Storefront, Admin & Favicon',
            'ref_currency_id' => $currency?->id,
            'currency_code' => $currency?->code ?? 'PHP',
            'timezone' => 'Asia/Manila (GMT+8)',
            'ref_weight_unit_id' => $weightUnit?->id,
            'weight_unit_code' => $weightUnit?->code ?? 'kg_g',
            'date_format' => 'YYYY-MM-DD',
        ]);
    }
}
