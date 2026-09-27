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
        Schema::create('customer_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number')->unique(); // e.g. ORD-9842
            $table->dateTime('order_date')->nullable();

            // Linked customer profile foreign key
            $table->foreignId('customer_profile_id')->nullable()->constrained('customer_profiles')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Total & Payment
            $table->decimal('total_amount', 12, 2)->default(0.00);
            $table->string('payment_method')->default('GCash'); // GCASH, MAYA, CASH ON DELIVERY, CREDIT CARD
            $table->string('payment_status')->default('Paid'); // Paid, Pending, Refunded

            // Order Status (Foreign Key to ref_order_status)
            $table->foreignId('ref_order_status_id')->constrained('ref_order_status');

            // Refund details
            $table->string('refund_status')->default('None'); // None, Partial, Full
            $table->decimal('refund_amount', 12, 2)->default(0.00);

            // Document & Processing tracking
            $table->string('invoice_id')->nullable(); // e.g. INV-2026-001
            $table->boolean('packing_slip_printed')->default(false);
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_orders');
    }
};
