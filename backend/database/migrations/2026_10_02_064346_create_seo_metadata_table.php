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
        Schema::create('seo_metadata', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type')->default('global');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('page_name');
            $table->string('route_path');
            $table->string('meta_title', 255);
            $table->text('meta_description');
            $table->text('meta_keywords')->nullable();
            $table->string('focus_keyword')->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->string('og_title', 255)->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image_url', 500)->nullable();
            $table->string('twitter_card', 50)->default('summary_large_image');
            $table->string('robots', 100)->default('index, follow');
            $table->json('structured_data_json')->nullable();
            $table->integer('seo_score')->default(85);
            $table->boolean('ai_generated')->default(false);
            $table->string('ai_model', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_metadata');
    }
};
