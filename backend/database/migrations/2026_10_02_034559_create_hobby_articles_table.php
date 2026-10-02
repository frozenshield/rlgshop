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
        Schema::create('hobby_articles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('category')->default('TCG Strategy');
            $table->string('author')->default('RLG Editorial Staff');
            $table->date('published_at')->nullable();
            $table->string('status')->default('Published');
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->json('sources')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hobby_articles');
    }
};
