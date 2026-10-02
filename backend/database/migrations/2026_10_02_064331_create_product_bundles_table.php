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
        Schema::create('product_bundles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('primary_product_id')->constrained('products')->onDelete('cascade');
            $table->string('title');
            $table->json('bundle_product_ids');
            $table->decimal('discount_percentage', 5, 2)->default(10.00);
            $table->string('conversion_lift')->default('+20.0%');
            $table->string('badge_text')->default('Frequently Bought Together');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_bundles');
    }
};
