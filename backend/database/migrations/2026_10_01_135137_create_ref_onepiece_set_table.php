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
        Schema::create('ref_onepiece_set', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique()->index();
            $table->string('name', 255);
            $table->string('product_line', 100);
            $table->string('set_type', 50);
            $table->string('release_date', 100)->nullable();
            $table->string('status', 50)->default('released');
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->json('chase_cards')->nullable();
            $table->boolean('is_subset')->default(true);
            $table->integer('release_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_onepiece_set');
    }
};
