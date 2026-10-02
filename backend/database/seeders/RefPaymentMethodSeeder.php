<?php

namespace Database\Seeders;

use App\Models\RefPaymentMethod;
use Illuminate\Database\Seeder;

class RefPaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            [
                'name' => 'qr',
                'code' => 'qr',
                'label' => 'Static QR Code',
                'status' => 'active',
                'description' => 'Scan QR via GoTyme, GCash, MariBank, or Maya',
                'icon' => 'pi pi-qrcode',
            ],
            [
                'name' => 'stripe',
                'code' => 'stripe',
                'label' => 'Stripe',
                'status' => 'active',
                'description' => 'Global online credit card & secure payments',
                'icon' => 'pi pi-credit-card',
            ],
            [
                'name' => 'paypal',
                'code' => 'paypal',
                'label' => 'PayPal',
                'status' => 'active',
                'description' => 'Fast and secure worldwide payments via PayPal account',
                'icon' => 'pi pi-paypal',
            ],
            [
                'name' => 'visa/master card',
                'code' => 'visa_master_card',
                'label' => 'Visa / MasterCard',
                'status' => 'active',
                'description' => 'Direct debit and credit card processing with 3D Secure',
                'icon' => 'pi pi-credit-card',
            ],
            [
                'name' => 'cash on delivery',
                'code' => 'cod',
                'label' => 'Cash on Delivery',
                'status' => 'active',
                'description' => 'Pay with cash upon package arrival at your doorstep',
                'icon' => 'pi pi-money-bill',
            ],
        ];

        foreach ($methods as $method) {
            RefPaymentMethod::updateOrCreate(
                ['name' => $method['name']],
                $method
            );
        }
    }
}
