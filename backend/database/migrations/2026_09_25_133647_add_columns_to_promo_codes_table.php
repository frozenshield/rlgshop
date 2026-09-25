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
        Schema::table('promo_codes', function (Blueprint $table): void {
            $table->string('code')->unique()->after('id');
            $table->enum('type', ['percentage', 'fixed', 'shipping'])->default('percentage')->after('code');
            $table->decimal('value', 10, 2)->default(0.00)->after('type');
            $table->unsignedInteger('usage_count')->default(0)->after('value');
            $table->unsignedInteger('usage_limit')->nullable()->after('usage_count');
            $table->date('expiry_date')->nullable()->after('usage_limit');
            $table->boolean('is_active')->default(true)->after('expiry_date');
            $table->decimal('min_order_amount', 10, 2)->nullable()->default(0.00)->after('is_active');
            $table->string('description')->nullable()->after('min_order_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promo_codes', function (Blueprint $table): void {
            $table->dropColumn([
                'code',
                'type',
                'value',
                'usage_count',
                'usage_limit',
                'expiry_date',
                'is_active',
                'min_order_amount',
                'description',
            ]);
        });
    }
};
