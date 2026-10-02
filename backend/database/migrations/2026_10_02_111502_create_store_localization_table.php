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
        Schema::create('store_localization', function (Blueprint $table) {
            $table->id();
            $table->string('store_legal_name')->default('RLG Hobby Shop');
            $table->string('brand_logo_url')->nullable()->default('/logo.png');
            $table->string('brand_logo_title')->default('RLG Online Shop');
            $table->string('brand_logo_subtitle')->default('Storefront, Admin & Favicon');
            $table->foreignId('ref_currency_id')->nullable()->constrained('ref_currency')->nullOnDelete();
            $table->string('currency_code', 10)->default('PHP');
            $table->string('timezone')->default('Asia/Manila (GMT+8)');
            $table->foreignId('ref_weight_unit_id')->nullable()->constrained('ref_weight_unit')->nullOnDelete();
            $table->string('weight_unit_code', 20)->default('kg_g');
            $table->string('date_format', 50)->default('YYYY-MM-DD');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_localization');
    }
};
