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
        Schema::table('customer_profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('customer_profiles', 'customer_shippment_address_id')) {
                $table->foreignId('customer_shippment_address_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('customer_shippment_address')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table): void {
            if (Schema::hasColumn('customer_profiles', 'customer_shippment_address_id')) {
                $table->dropForeign(['customer_shippment_address_id']);
                $table->dropColumn('customer_shippment_address_id');
            }
        });
    }
};
