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
        Schema::create('ref_pokemon_set', function (Blueprint $table): void {
            $table->id();
            $table->string('series', 100);
            $table->string('series_years', 50)->nullable();
            $table->text('japanese_set');
            $table->string('japanese_code', 100)->nullable()->index();
            $table->text('english_set');
            $table->string('set_type', 50)->default('Main Expansion');
            $table->text('notes')->nullable();
            $table->integer('release_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_pokemon_set');
    }
};
