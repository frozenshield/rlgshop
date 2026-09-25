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
        Schema::create('customer_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();

            // Personal Information
            $table->string('name')->nullable();
            $table->string('username')->nullable();
            $table->string('phone')->nullable();
            $table->text('avatar')->nullable();

            // Default Shipping Address
            $table->string('address_line1')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Philippines');

            // Collector Identity & Bio
            $table->string('favorite_franchise')->default('Pokémon TCG');
            $table->text('bio')->nullable();

            // Account & Notification Settings (Preferences)
            $table->boolean('two_factor_auth')->default(false);
            $table->boolean('email_notifications')->default(true);
            $table->boolean('order_updates_sms')->default(true);
            $table->boolean('marketing_emails')->default(false);
            $table->string('currency_preference')->default('PHP');
            $table->boolean('public_collection')->default(true);

            // CRM & Segmentation
            $table->enum('segment', ['VIP', 'Regular', 'Wholesale', 'Inactive'])->default('Regular');
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_profiles');
    }
};
