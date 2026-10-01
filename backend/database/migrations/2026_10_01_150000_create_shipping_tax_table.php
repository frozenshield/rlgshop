<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipping_tax', function (Blueprint $table): void {
            $table->id();
            $table->decimal('standard_shipping_fee', 10, 2)->default(100.00); // Standard Nationwide Shipping (PHP)
            $table->decimal('free_shipping_threshold', 10, 2)->default(2500.00); // Free Shipping Order Threshold (PHP)
            $table->decimal('vat_percentage', 5, 2)->default(12.00); // Philippine VAT Percentage (%)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_tax');
    }
};
