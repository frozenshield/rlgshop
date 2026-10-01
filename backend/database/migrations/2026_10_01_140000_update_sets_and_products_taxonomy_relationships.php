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
        // 1. Remove set foreign keys from products table so products rely purely on category & subcategory
        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'ref_pokemon_set_id')) {
                $table->dropConstrainedForeignId('ref_pokemon_set_id');
            }
            if (Schema::hasColumn('products', 'ref_onepiece_set_id')) {
                $table->dropConstrainedForeignId('ref_onepiece_set_id');
            }
        });

        // 2. Add subcategories_id foreign key to ref_pokemon_set
        Schema::table('ref_pokemon_set', function (Blueprint $table): void {
            if (! Schema::hasColumn('ref_pokemon_set', 'subcategories_id')) {
                $table->foreignId('subcategories_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('ref_subcategories')
                    ->nullOnDelete();
            }
        });

        // 3. Add subcategories_id foreign key to ref_onepiece_set
        Schema::table('ref_onepiece_set', function (Blueprint $table): void {
            if (! Schema::hasColumn('ref_onepiece_set', 'subcategories_id')) {
                $table->foreignId('subcategories_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('ref_subcategories')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ref_onepiece_set', function (Blueprint $table): void {
            if (Schema::hasColumn('ref_onepiece_set', 'subcategories_id')) {
                $table->dropConstrainedForeignId('subcategories_id');
            }
        });

        Schema::table('ref_pokemon_set', function (Blueprint $table): void {
            if (Schema::hasColumn('ref_pokemon_set', 'subcategories_id')) {
                $table->dropConstrainedForeignId('subcategories_id');
            }
        });

        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'ref_pokemon_set_id')) {
                $table->foreignId('ref_pokemon_set_id')
                    ->nullable()
                    ->after('ref_condition_id')
                    ->constrained('ref_pokemon_set')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('products', 'ref_onepiece_set_id')) {
                $table->foreignId('ref_onepiece_set_id')
                    ->nullable()
                    ->after('ref_pokemon_set_id')
                    ->constrained('ref_onepiece_set')
                    ->nullOnDelete();
            }
        });
    }
};
