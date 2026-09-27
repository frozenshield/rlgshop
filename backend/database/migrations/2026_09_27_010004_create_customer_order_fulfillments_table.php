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
        Schema::create('customer_order_fulfillments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_order_id')->constrained('customer_orders')->cascadeOnDelete();
            $table->foreignId('ref_shipping_carrier_id')->constrained('ref_shipping_carrier');
            $table->string('tracking_number');
            $table->string('shipping_label_url')->nullable();
            $table->boolean('packing_slip_printed')->default(false);
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_order_fulfillments');
    }
};
