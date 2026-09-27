<?php

namespace Database\Seeders;

use App\Models\CustomerOrder;
use App\Models\CustomerOrderFulfillment;
use App\Models\CustomerOrderItem;
use App\Models\Product;
use App\Models\RefOrderStatus;
use App\Models\RefShippingCarrier;
use Illuminate\Database\Seeder;

class CustomerOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statusMap = RefOrderStatus::all()->keyBy('name');
        $carrierMap = RefShippingCarrier::all()->keyBy('code');

        $sampleOrders = [
            [
                'order_number' => 'ORD-9842',
                'order_date' => '2026-09-24 09:15:00',
                'customer_name' => 'Marcus Tan',
                'customer_email' => 'marcus.tan@example.com',
                'customer_phone' => '+63 917 555 1234',
                'shipping_address' => '42 Orchid St, Unit 3B, New Manila',
                'city' => 'Quezon City',
                'postal_code' => '1112',
                'total_amount' => 7549.00,
                'payment_method' => 'GCASH',
                'payment_status' => 'Paid',
                'status_name' => 'processing',
                'refund_status' => 'None',
                'refund_amount' => 0.00,
                'invoice_id' => 'INV-2026-001',
                'packing_slip_printed' => true,
                'notes' => 'Please pack with corner bubble armor protectors.',
                'items' => [
                    [
                        'product_name' => 'Pokémon TCG: Scarlet & Violet 151 Elite Trainer Box',
                        'sku' => 'TCG-PKM-151-ETB',
                        'price' => 2799.00,
                        'quantity' => 1,
                        'subtotal' => 2799.00,
                        'image_url' => 'https://images.unsplash.com/photo-1628155930542-3c7a64e2c833?w=600&auto=format&fit=crop&q=80',
                    ],
                    [
                        'product_name' => 'One Piece Card Game: OP-05 Booster Box',
                        'sku' => 'TCG-OP-05-BOX',
                        'price' => 4750.00,
                        'quantity' => 1,
                        'subtotal' => 4750.00,
                        'image_url' => 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=600&auto=format&fit=crop&q=80',
                    ],
                ],
                'fulfillment' => null,
            ],
            [
                'order_number' => 'ORD-9843',
                'order_date' => '2026-09-23 15:40:00',
                'customer_name' => 'Elena Reyes',
                'customer_email' => 'elena.reyes@example.com',
                'customer_phone' => '+63 918 888 4567',
                'shipping_address' => '15 Katipunan Ave, Loyola Heights',
                'city' => 'Quezon City',
                'postal_code' => '1108',
                'total_amount' => 2688.00,
                'payment_method' => 'MAYA',
                'payment_status' => 'Paid',
                'status_name' => 'shipped',
                'refund_status' => 'None',
                'refund_amount' => 0.00,
                'invoice_id' => 'INV-2026-002',
                'packing_slip_printed' => true,
                'notes' => 'Deliver during business hours.',
                'items' => [
                    [
                        'product_name' => 'Monkey D. Luffy Gear 5 Sun God Nika Battle Figure',
                        'sku' => 'FIG-OP-LUFFY-G5',
                        'price' => 2688.00,
                        'quantity' => 1,
                        'subtotal' => 2688.00,
                        'image_url' => 'https://images.unsplash.com/photo-1594787318286-3d835c1d207f?w=600&auto=format&fit=crop&q=80',
                    ],
                ],
                'fulfillment' => [
                    'carrier_code' => 'jnt_ph',
                    'tracking_number' => 'PH-JT-894729104',
                    'packing_slip_printed' => true,
                    'shipped_at' => '2026-09-23 16:30:00',
                ],
            ],
            [
                'order_number' => 'ORD-9844',
                'order_date' => '2026-09-24 11:05:00',
                'customer_name' => 'David Cruz',
                'customer_email' => 'david.cruz@example.com',
                'customer_phone' => '+63 920 333 9988',
                'shipping_address' => 'Unit 1204 Paseo Heights, Salcedo Village',
                'city' => 'Makati City',
                'postal_code' => '1227',
                'total_amount' => 7900.00,
                'payment_method' => 'CASH ON DELIVERY',
                'payment_status' => 'Pending',
                'status_name' => 'pending',
                'refund_status' => 'None',
                'refund_amount' => 0.00,
                'invoice_id' => 'INV-2026-003',
                'packing_slip_printed' => false,
                'notes' => 'Call upon gate arrival.',
                'items' => [
                    [
                        'product_name' => 'Pokémon TCG: Terastal Festival ex Booster Box [SV8a]',
                        'sku' => 'TCG-PKM-SV8A-BOX',
                        'price' => 4200.00,
                        'quantity' => 1,
                        'subtotal' => 4200.00,
                        'image_url' => 'https://images.unsplash.com/photo-1613771404784-3a5686aa2be3?w=600&auto=format&fit=crop&q=80',
                    ],
                    [
                        'product_name' => 'Demon Slayer: Kamado Tanjirō Hinokami Kagura Figurine',
                        'sku' => 'FIG-DS-TANJIRO',
                        'price' => 3700.00,
                        'quantity' => 1,
                        'subtotal' => 3700.00,
                        'image_url' => 'https://images.unsplash.com/photo-1563089145-599997674d42?w=600&auto=format&fit=crop&q=80',
                    ],
                ],
                'fulfillment' => null,
            ],
            [
                'order_number' => 'ORD-9845',
                'order_date' => '2026-09-21 14:10:00',
                'customer_name' => 'Chloe Mendoza',
                'customer_email' => 'chloe.mendoza@example.com',
                'customer_phone' => '+63 905 111 2233',
                'shipping_address' => '84 Emerald Dr, Hillsborough Alabang',
                'city' => 'Muntinlupa City',
                'postal_code' => '1770',
                'total_amount' => 4499.00,
                'payment_method' => 'CREDIT CARD',
                'payment_status' => 'Paid',
                'status_name' => 'delivered',
                'refund_status' => 'None',
                'refund_amount' => 0.00,
                'invoice_id' => 'INV-2026-004',
                'packing_slip_printed' => true,
                'notes' => 'Left at reception desk with security guard.',
                'items' => [
                    [
                        'product_name' => 'Gundam RG 1/144 Hi-Nu Gundam Titanium Finish',
                        'sku' => 'GUN-RG-HINU-TF',
                        'price' => 4499.00,
                        'quantity' => 1,
                        'subtotal' => 4499.00,
                        'image_url' => 'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=600&auto=format&fit=crop&q=80',
                    ],
                ],
                'fulfillment' => [
                    'carrier_code' => 'lbc',
                    'tracking_number' => 'PH-LBC-9912048',
                    'packing_slip_printed' => true,
                    'shipped_at' => '2026-09-21 16:00:00',
                    'delivered_at' => '2026-09-23 10:15:00',
                ],
            ],
            [
                'order_number' => 'ORD-9846',
                'order_date' => '2026-09-22 08:30:00',
                'customer_name' => 'Kenji Sato',
                'customer_email' => 'kenji.sato@example.com',
                'customer_phone' => '+63 916 444 8877',
                'shipping_address' => '24th St, Two Serendra Tower, BGC',
                'city' => 'Taguig City',
                'postal_code' => '1634',
                'total_amount' => 3600.00,
                'payment_method' => 'GCASH',
                'payment_status' => 'Refunded',
                'status_name' => 'cancel',
                'refund_status' => 'Full',
                'refund_amount' => 3600.00,
                'invoice_id' => 'INV-2026-005',
                'packing_slip_printed' => false,
                'notes' => 'Customer requested cancellation prior to packing.',
                'items' => [
                    [
                        'product_name' => 'Yu-Gi-Oh! 25th Anniversary Rarity Collection II Box',
                        'sku' => 'TCG-YGO-RC02',
                        'price' => 3600.00,
                        'quantity' => 1,
                        'subtotal' => 3600.00,
                        'image_url' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=600&auto=format&fit=crop&q=80',
                    ],
                ],
                'fulfillment' => null,
            ],
        ];

        foreach ($sampleOrders as $data) {
            $status = $statusMap[$data['status_name']] ?? $statusMap['pending'];

            $order = CustomerOrder::updateOrCreate(
                ['order_number' => $data['order_number']],
                [
                    'order_date' => $data['order_date'],
                    'customer_name' => $data['customer_name'],
                    'customer_email' => $data['customer_email'],
                    'customer_phone' => $data['customer_phone'],
                    'shipping_address' => $data['shipping_address'],
                    'city' => $data['city'],
                    'postal_code' => $data['postal_code'],
                    'total_amount' => $data['total_amount'],
                    'payment_method' => $data['payment_method'],
                    'payment_status' => $data['payment_status'],
                    'ref_order_status_id' => $status->id,
                    'refund_status' => $data['refund_status'],
                    'refund_amount' => $data['refund_amount'],
                    'invoice_id' => $data['invoice_id'],
                    'packing_slip_printed' => $data['packing_slip_printed'],
                    'notes' => $data['notes'],
                ]
            );

            // Re-sync line items
            $order->items()->delete();
            foreach ($data['items'] as $item) {
                // Try linking to actual product if SKU matches
                $product = Product::where('sku', $item['sku'])->first();

                CustomerOrderItem::create([
                    'customer_order_id' => $order->id,
                    'product_id' => $product?->id,
                    'product_name' => $item['product_name'],
                    'sku' => $item['sku'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                    'image_url' => $item['image_url'],
                ]);
            }

            // Sync fulfillment if present
            if ($data['fulfillment']) {
                $carrier = $carrierMap[$data['fulfillment']['carrier_code']] ?? null;
                if ($carrier) {
                    CustomerOrderFulfillment::updateOrCreate(
                        ['customer_order_id' => $order->id],
                        [
                            'ref_shipping_carrier_id' => $carrier->id,
                            'tracking_number' => $data['fulfillment']['tracking_number'],
                            'packing_slip_printed' => $data['fulfillment']['packing_slip_printed'] ?? true,
                            'shipped_at' => $data['fulfillment']['shipped_at'] ?? now(),
                            'delivered_at' => $data['fulfillment']['delivered_at'] ?? null,
                        ]
                    );
                }
            }
        }
    }
}
