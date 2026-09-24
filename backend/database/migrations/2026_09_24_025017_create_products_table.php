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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->nullable()->unique(); // SKU or code e.g. 'op-tcg-1', 'poke-fig-1'
            $table->string('name');
            $table->string('slug', 255)->unique();
            $table->text('description');

            // Categorization & Franchises (Generic for all Anime Figures, Merch & TCG)
            $table->string('category'); // 'tcg', 'anime-figures', 'anime-merchandise', etc.
            $table->string('subcategory')->nullable(); // 'pokemon', 'one-piece', 'hololive', 'frieren', etc.
            $table->string('franchise_badge')->nullable(); // e.g. 'One Piece 🏴‍☠️', 'Weiß Schwarz ✨', 'Hololive 🎤'
            $table->string('age_group')->nullable(); // '0-2', '3-5', '6-8', '9-12', '12+'
            $table->string('brand'); // 'Bandai', 'Good Smile', 'Takara Tomy', 'Bushiroad', etc.

            // Pricing & Inventory
            $table->decimal('price', 10, 2);
            $table->decimal('original_price', 10, 2)->nullable();
            $table->integer('discount_percent')->nullable()->default(0);
            $table->decimal('rating', 3, 2)->default(5.00);
            $table->integer('review_count')->default(0);
            $table->integer('stock')->default(0);

            // Media & Attributes
            $table->string('image_url');
            $table->json('gallery_images')->nullable();
            $table->json('tags')->nullable();
            $table->json('features')->nullable();

            // Merchandising flags
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_best_seller')->default(false);
            $table->boolean('is_new_arrival')->default(false);
            $table->string('safety_warning')->nullable();
            $table->timestamps();

            // Indexes for fast catalog browsing & filtering
            $table->index(['category', 'subcategory']);
            $table->index('price');
            $table->index('rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
