<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create ref_payment_merch table
        Schema::create('ref_payment_merch', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ref_payment_method_id')->constrained('ref_payment_method')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('qr_image_url');
            $table->text('instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Adjust ref_payment_method: Add QR method, remove separate gcash and paymaya
        $qrId = DB::table('ref_payment_method')->where('code', 'qr')->value('id');
        if (! $qrId) {
            $qrId = DB::table('ref_payment_method')->insertGetId([
                'name' => 'qr',
                'code' => 'qr',
                'label' => 'Static QR Code',
                'status' => 'active',
                'description' => 'Zero-fee direct bank and e-wallet QR scan (GoTyme, GCash, MariBank, Maya)',
                'icon' => 'pi pi-qrcode',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Remove separate gcash and paymaya from ref_payment_method
        DB::table('ref_payment_method')->whereIn('code', ['gcash', 'paymaya'])->delete();

        // 3. Seed the 4 merchants into ref_payment_merch
        $merchants = [
            [
                'ref_payment_method_id' => $qrId,
                'name' => 'GoTyme Bank',
                'code' => 'gotyme',
                'account_name' => 'RUSSEL LUIS GEMENTIZA',
                'account_number' => '•••••••• 5860',
                'qr_image_url' => '/images/qr/gotyme-qr.png',
                'instructions' => 'Scan with your GoTyme or any InstaPay app. Enter the exact total and save payment receipt screenshot.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ref_payment_method_id' => $qrId,
                'name' => 'GCash',
                'code' => 'gcash',
                'account_name' => 'RU***L LU*S G.',
                'account_number' => '+63 956 997 ****',
                'qr_image_url' => '/images/qr/gcash-qr.jpg',
                'instructions' => 'Scan via GCash app. Transfer fees may apply. Save transaction receipt to confirm payment.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ref_payment_method_id' => $qrId,
                'name' => 'MariBank',
                'code' => 'maribank',
                'account_name' => 'RUSSEL LUIS GEMENTIZA',
                'account_number' => 'MariBank(****4301)',
                'qr_image_url' => '/images/qr/maribank-qr.png',
                'instructions' => 'Scan via MariBank or any InstaPay e-wallet. Save transfer confirmation.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ref_payment_method_id' => $qrId,
                'name' => 'PayMaya',
                'code' => 'paymaya',
                'account_name' => 'Russel Luis Gementiza',
                'account_number' => '+63 *** *** 0813 (@russelluis)',
                'qr_image_url' => '/images/qr/maya-qr.jpg',
                'instructions' => 'Scan using Maya app. Transfer fees may apply. Save confirmation receipt.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($merchants as $m) {
            DB::table('ref_payment_merch')->updateOrInsert(
                ['code' => $m['code']],
                $m
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_payment_merch');
    }
};
