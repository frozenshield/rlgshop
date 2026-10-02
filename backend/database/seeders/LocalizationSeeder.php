<?php

namespace Database\Seeders;

use App\Models\RefCurrency;
use App\Models\RefWeightUnit;
use App\Models\StoreLocalization;
use Illuminate\Database\Seeder;

class LocalizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            // Asia-Pacific & Primary Store Currencies
            ['code' => 'PHP', 'name' => 'Philippine Peso', 'symbol' => '₱', 'label' => 'Philippine Peso (PHP ₱)', 'exchange_rate' => 1.0000, 'is_active' => true],
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'label' => 'US Dollar (USD $)', 'exchange_rate' => 0.0175, 'is_active' => true],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥', 'label' => 'Japanese Yen (JPY ¥)', 'exchange_rate' => 2.6500, 'is_active' => true],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'label' => 'Euro (EUR €)', 'exchange_rate' => 0.0162, 'is_active' => true],
            ['code' => 'GBP', 'name' => 'British Pound Sterling', 'symbol' => '£', 'label' => 'British Pound (GBP £)', 'exchange_rate' => 0.0135, 'is_active' => true],
            ['code' => 'SGD', 'name' => 'Singapore Dollar', 'symbol' => 'S$', 'label' => 'Singapore Dollar (SGD S$)', 'exchange_rate' => 0.0235, 'is_active' => true],
            ['code' => 'HKD', 'name' => 'Hong Kong Dollar', 'symbol' => 'HK$', 'label' => 'Hong Kong Dollar (HKD HK$)', 'exchange_rate' => 0.1360, 'is_active' => true],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'AU$', 'label' => 'Australian Dollar (AUD AU$)', 'exchange_rate' => 0.0270, 'is_active' => true],
            ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => 'CA$', 'label' => 'Canadian Dollar (CAD CA$)', 'exchange_rate' => 0.0245, 'is_active' => true],
            ['code' => 'KRW', 'name' => 'South Korean Won', 'symbol' => '₩', 'label' => 'South Korean Won (KRW ₩)', 'exchange_rate' => 24.2000, 'is_active' => true],
            ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => 'CN¥', 'label' => 'Chinese Yuan (CNY CN¥)', 'exchange_rate' => 0.1260, 'is_active' => true],
            ['code' => 'TWD', 'name' => 'New Taiwan Dollar', 'symbol' => 'NT$', 'label' => 'New Taiwan Dollar (TWD NT$)', 'exchange_rate' => 0.5600, 'is_active' => true],
            ['code' => 'THB', 'name' => 'Thai Baht', 'symbol' => '฿', 'label' => 'Thai Baht (THB ฿)', 'exchange_rate' => 0.6100, 'is_active' => true],
            ['code' => 'MYR', 'name' => 'Malaysian Ringgit', 'symbol' => 'RM', 'label' => 'Malaysian Ringgit (MYR RM)', 'exchange_rate' => 0.0760, 'is_active' => true],
            ['code' => 'IDR', 'name' => 'Indonesian Rupiah', 'symbol' => 'Rp', 'label' => 'Indonesian Rupiah (IDR Rp)', 'exchange_rate' => 280.0000, 'is_active' => true],
            ['code' => 'VND', 'name' => 'Vietnamese Dong', 'symbol' => '₫', 'label' => 'Vietnamese Dong (VND ₫)', 'exchange_rate' => 445.0000, 'is_active' => true],
            ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹', 'label' => 'Indian Rupee (INR ₹)', 'exchange_rate' => 1.4800, 'is_active' => true],
            ['code' => 'NZD', 'name' => 'New Zealand Dollar', 'symbol' => 'NZ$', 'label' => 'New Zealand Dollar (NZD NZ$)', 'exchange_rate' => 0.0295, 'is_active' => true],

            // Europe & Americas
            ['code' => 'CHF', 'name' => 'Swiss Franc', 'symbol' => 'CHF', 'label' => 'Swiss Franc (CHF CHF)', 'exchange_rate' => 0.0155, 'is_active' => true],
            ['code' => 'SEK', 'name' => 'Swedish Krona', 'symbol' => 'kr', 'label' => 'Swedish Krona (SEK kr)', 'exchange_rate' => 0.1850, 'is_active' => true],
            ['code' => 'NOK', 'name' => 'Norwegian Krone', 'symbol' => 'kr', 'label' => 'Norwegian Krone (NOK kr)', 'exchange_rate' => 0.1900, 'is_active' => true],
            ['code' => 'DKK', 'name' => 'Danish Krone', 'symbol' => 'kr', 'label' => 'Danish Krone (DKK kr)', 'exchange_rate' => 0.1210, 'is_active' => true],
            ['code' => 'PLN', 'name' => 'Polish Zloty', 'symbol' => 'zł', 'label' => 'Polish Zloty (PLN zł)', 'exchange_rate' => 0.0700, 'is_active' => true],
            ['code' => 'CZK', 'name' => 'Czech Koruna', 'symbol' => 'Kč', 'label' => 'Czech Koruna (CZK Kč)', 'exchange_rate' => 0.4100, 'is_active' => true],
            ['code' => 'HUF', 'name' => 'Hungarian Forint', 'symbol' => 'Ft', 'label' => 'Hungarian Forint (HUF Ft)', 'exchange_rate' => 6.5000, 'is_active' => true],
            ['code' => 'RON', 'name' => 'Romanian Leu', 'symbol' => 'lei', 'label' => 'Romanian Leu (RON lei)', 'exchange_rate' => 0.0810, 'is_active' => true],
            ['code' => 'TRY', 'name' => 'Turkish Lira', 'symbol' => '₺', 'label' => 'Turkish Lira (TRY ₺)', 'exchange_rate' => 0.6000, 'is_active' => true],
            ['code' => 'MXN', 'name' => 'Mexican Peso', 'symbol' => 'MX$', 'label' => 'Mexican Peso (MXN MX$)', 'exchange_rate' => 0.3500, 'is_active' => true],
            ['code' => 'BRL', 'name' => 'Brazilian Real', 'symbol' => 'R$', 'label' => 'Brazilian Real (BRL R$)', 'exchange_rate' => 0.0980, 'is_active' => true],
            ['code' => 'ARS', 'name' => 'Argentine Peso', 'symbol' => 'ARS$', 'label' => 'Argentine Peso (ARS ARS$)', 'exchange_rate' => 17.5000, 'is_active' => true],
            ['code' => 'CLP', 'name' => 'Chilean Peso', 'symbol' => 'CLP$', 'label' => 'Chilean Peso (CLP CLP$)', 'exchange_rate' => 16.8000, 'is_active' => true],
            ['code' => 'COP', 'name' => 'Colombian Peso', 'symbol' => 'COL$', 'label' => 'Colombian Peso (COP COL$)', 'exchange_rate' => 74.0000, 'is_active' => true],
            ['code' => 'PEN', 'name' => 'Peruvian Sol', 'symbol' => 'S/', 'label' => 'Peruvian Sol (PEN S/)', 'exchange_rate' => 0.0650, 'is_active' => true],

            // Middle East & Africa
            ['code' => 'AED', 'name' => 'United Arab Emirates Dirham', 'symbol' => 'AED', 'label' => 'UAE Dirham (AED د.إ)', 'exchange_rate' => 0.0640, 'is_active' => true],
            ['code' => 'SAR', 'name' => 'Saudi Riyal', 'symbol' => 'SAR', 'label' => 'Saudi Riyal (SAR ﷼)', 'exchange_rate' => 0.0650, 'is_active' => true],
            ['code' => 'QAR', 'name' => 'Qatari Riyal', 'symbol' => 'QAR', 'label' => 'Qatari Riyal (QAR ﷼)', 'exchange_rate' => 0.0630, 'is_active' => true],
            ['code' => 'KWD', 'name' => 'Kuwaiti Dinar', 'symbol' => 'KWD', 'label' => 'Kuwaiti Dinar (KWD د.ك)', 'exchange_rate' => 0.0053, 'is_active' => true],
            ['code' => 'ILS', 'name' => 'Israeli New Shekel', 'symbol' => '₪', 'label' => 'Israeli Shekel (ILS ₪)', 'exchange_rate' => 0.0640, 'is_active' => true],
            ['code' => 'ZAR', 'name' => 'South African Rand', 'symbol' => 'R', 'label' => 'South African Rand (ZAR R)', 'exchange_rate' => 0.3150, 'is_active' => true],
            ['code' => 'EGP', 'name' => 'Egyptian Pound', 'symbol' => 'E£', 'label' => 'Egyptian Pound (EGP E£)', 'exchange_rate' => 0.8500, 'is_active' => true],
            ['code' => 'NGN', 'name' => 'Nigerian Naira', 'symbol' => '₦', 'label' => 'Nigerian Naira (NGN ₦)', 'exchange_rate' => 28.5000, 'is_active' => true],
            ['code' => 'KES', 'name' => 'Kenyan Shilling', 'symbol' => 'KSh', 'label' => 'Kenyan Shilling (KES KSh)', 'exchange_rate' => 2.2500, 'is_active' => true],
            ['code' => 'PKR', 'name' => 'Pakistani Rupee', 'symbol' => '₨', 'label' => 'Pakistani Rupee (PKR ₨)', 'exchange_rate' => 4.8800, 'is_active' => true],
            ['code' => 'BDT', 'name' => 'Bangladeshi Taka', 'symbol' => '৳', 'label' => 'Bangladeshi Taka (BDT ৳)', 'exchange_rate' => 2.1000, 'is_active' => true],
            ['code' => 'RUB', 'name' => 'Russian Ruble', 'symbol' => '₽', 'label' => 'Russian Ruble (RUB ₽)', 'exchange_rate' => 1.6200, 'is_active' => true],
        ];

        foreach ($currencies as $curr) {
            RefCurrency::updateOrCreate(['code' => $curr['code']], $curr);
        }

        $weightUnits = [
            ['code' => 'kg_g', 'name' => 'Kilograms (kg) • Grams (g)', 'symbol' => 'kg / g', 'system' => 'metric', 'is_active' => true],
            ['code' => 'lb_oz', 'name' => 'Pounds (lb) • Ounces (oz)', 'symbol' => 'lb / oz', 'system' => 'imperial', 'is_active' => true],
            ['code' => 'kg', 'name' => 'Kilograms (kg)', 'symbol' => 'kg', 'system' => 'metric', 'is_active' => true],
            ['code' => 'g', 'name' => 'Grams (g)', 'symbol' => 'g', 'system' => 'metric', 'is_active' => true],
            ['code' => 'lb', 'name' => 'Pounds (lb)', 'symbol' => 'lb', 'system' => 'imperial', 'is_active' => true],
            ['code' => 'oz', 'name' => 'Ounces (oz)', 'symbol' => 'oz', 'system' => 'imperial', 'is_active' => true],
        ];

        foreach ($weightUnits as $unit) {
            RefWeightUnit::updateOrCreate(['code' => $unit['code']], $unit);
        }

        $php = RefCurrency::where('code', 'PHP')->first();
        $kgG = RefWeightUnit::where('code', 'kg_g')->first();

        StoreLocalization::firstOrCreate([], [
            'store_legal_name' => 'RLG Hobby Shop',
            'brand_logo_url' => '/logo.png',
            'brand_logo_title' => 'RLG Online Shop',
            'brand_logo_subtitle' => 'Storefront, Admin & Favicon',
            'ref_currency_id' => $php?->id,
            'currency_code' => 'PHP',
            'timezone' => 'Asia/Manila (GMT+8)',
            'ref_weight_unit_id' => $kgG?->id,
            'weight_unit_code' => 'kg_g',
            'date_format' => 'YYYY-MM-DD',
        ]);
    }
}
