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
        Schema::create('ref_shipping_carrier', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->string('short_name')->nullable();
            $table->string('tracking_url_template')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_shipping_carrier');
    }
};
