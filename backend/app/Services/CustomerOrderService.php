<?php

namespace App\Services;

use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\CustomerOrderFulfillment;
use App\Models\CustomerProfile;
use App\Models\CustomerShippmentAddress;
use App\Models\RefOrderStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerOrderService
{
    public function getOrders(array $filters): Collection
    {
        $query = CustomerOrder::with([
            'status',
            'items',
            'fulfillment.carrier',
            'fulfillments.carrier',
            'customerProfile.shippingAddress',
            'customerProfile.user',
        ]);

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search): void {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customerProfile', function ($cq) use ($search): void {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhereHas('user', function ($uq) use ($search): void {
                                $uq->where('email', 'like', "%{$search}%")
                                    ->orWhere('name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('shippingAddress', function ($aq) use ($search): void {
                                $aq->where('shipping_address', 'like', "%{$search}%")
                                    ->orWhere('city', 'like', "%{$search}%");
                            });
                    })
                    ->orWhereHas('fulfillment', function ($fq) use ($search): void {
                        $fq->where('tracking_number', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'All') {
            $statusInput = $filters['status'];
            if (is_numeric($statusInput)) {
                $query->where('ref_order_status_id', $statusInput);
            } else {
                $statusSlug = strtolower(trim((string) $statusInput));
                $query->whereHas('status', function ($sq) use ($statusSlug): void {
                    $sq->where('name', $statusSlug)
                        ->orWhere('label', 'like', $statusSlug);
                });
            }
        }

        if (!empty($filters['carrier_id'])) {
            $query->whereHas('fulfillment', function ($fq) use ($filters): void {
                $fq->where('ref_shipping_carrier_id', $filters['carrier_id']);
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['order_date', 'total_amount', 'created_at', 'order_number'])) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->get();
    }

    public function createOrder(array $data, ?User $currentUser = null): CustomerOrder
    {
        return DB::transaction(function () use ($data, $currentUser) {
            $totalAmount = array_reduce($data['items'], function ($carry, $item) {
                return $carry + ((float) $item['price'] * (int) $item['quantity']);
            }, 0);

            $orderNumber = $data['order_number'] ?? 'ORD-' . strtoupper(Str::random(8));

            // ensure unique order number
            while (CustomerOrder::where('order_number', $orderNumber)->exists()) {
                $orderNumber = 'ORD-' . strtoupper(Str::random(8));
            }

            $statusName = $data['status_name'] ?? 'pending';
            $statusId = $data['ref_order_status_id'] ?? null;

            if (! $statusId) {
                $statusRecord = RefOrderStatus::where('name', strtolower(trim($statusName)))->first();
                $statusId = $statusRecord?->id ?? 1;
            }

            $customerProfileId = $data['customer_profile_id'] ?? null;
            $userId = $currentUser?->id;

            if (! $customerProfileId && (! empty($data['customer_name']) || ! empty($data['customer_email']))) {
                $user = null;
                if (! empty($data['customer_email'])) {
                    $user = User::firstOrCreate(
                        ['email' => $data['customer_email']],
                        [
                            'name' => $data['customer_name'] ?? 'Customer',
                            'user_type' => 'customer',
                        ]
                    );
                    $userId = $user->id;
                }

                $shipmentAddress = null;
                if (! empty($data['shipping_address'])) {
                    $shipmentAddress = CustomerShippmentAddress::create([
                        'user_id' => $userId,
                        'recipient_name' => $data['customer_name'] ?? null,
                        'phone' => $data['customer_phone'] ?? null,
                        'shipping_address' => $data['shipping_address'],
                        'city' => $data['city'] ?? 'Metro Manila',
                        'postal_code' => $data['postal_code'] ?? '1000',
                        'country' => 'Philippines',
                        'is_default' => true,
                    ]);
                }

                $profile = CustomerProfile::firstOrCreate(
                    ['user_id' => $userId],
                    [
                        'customer_shippment_address_id' => $shipmentAddress?->id,
                        'name' => $data['customer_name'] ?? $user?->name ?? 'Customer',
                        'phone' => $data['customer_phone'] ?? null,
                        'address_line1' => $data['shipping_address'] ?? null,
                        'city' => $data['city'] ?? 'Metro Manila',
                        'postal_code' => $data['postal_code'] ?? '1000',
                    ]
                );

                if ($shipmentAddress && ! $profile->customer_shippment_address_id) {
                    $profile->update(['customer_shippment_address_id' => $shipmentAddress->id]);
                }

                $customerProfileId = $profile->id;
            }

            $order = CustomerOrder::create([
                'order_number' => $orderNumber,
                'order_date' => now(),
                'customer_profile_id' => $customerProfileId,
                'user_id' => $userId,
                'total_amount' => $totalAmount,
                'payment_method' => $data['payment_method'] ?? 'GCASH',
                'payment_status' => $data['payment_status'] ?? 'Paid',
                'ref_order_status_id' => $statusId,
                'invoice_id' => 'INV-' . date('Y') . '-' . rand(100, 999),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $price = (float) $item['price'];
                $qty = (int) $item['quantity'];
                CustomerOrderItem::create([
                    'customer_order_id' => $order->id,
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'sku' => $item['sku'] ?? null,
                    'price' => $price,
                    'quantity' => $qty,
                    'subtotal' => $price * $qty,
                    'image_url' => $item['image_url'] ?? null,
                ]);
            }

            return $order->load(['customerProfile.user', 'customerProfile.shippingAddress', 'status', 'items', 'fulfillment.carrier']);
        });
    }

    public function updateStatus(CustomerOrder $customerOrder, array $data): ?CustomerOrder
    {
        $statusId = $data['ref_order_status_id'] ?? null;
        if (! $statusId && ! empty($data['status_name'])) {
            $status = RefOrderStatus::where('name', strtolower(trim($data['status_name'])))
                ->orWhere('label', 'like', trim($data['status_name']))
                ->first();
            if ($status) {
                $statusId = $status->id;
            }
        }

        if (! $statusId) {
            return null;
        }

        $customerOrder->update([
            'ref_order_status_id' => $statusId,
        ]);

        return $customerOrder->load(['status', 'items', 'fulfillment.carrier']);
    }

    public function attachFulfillment(CustomerOrder $customerOrder, array $data): array
    {
        return DB::transaction(function () use ($data, $customerOrder) {
            $fulfillment = CustomerOrderFulfillment::updateOrCreate(
                ['customer_order_id' => $customerOrder->id],
                [
                    'ref_shipping_carrier_id' => $data['ref_shipping_carrier_id'],
                    'tracking_number' => $data['tracking_number'],
                    'packing_slip_printed' => $data['packing_slip_printed'] ?? true,
                    'shipped_at' => now(),
                    'notes' => $data['notes'] ?? null,
                ]
            );

            $markShipped = $data['mark_shipped'] ?? true;
            if ($markShipped) {
                $shippedStatus = RefOrderStatus::where('name', 'shipped')->first();
                if ($shippedStatus) {
                    $customerOrder->ref_order_status_id = $shippedStatus->id;
                }
            }

            if ($data['packing_slip_printed'] ?? true) {
                $customerOrder->packing_slip_printed = true;
            }

            $customerOrder->save();
            $customerOrder->load(['status', 'items', 'fulfillment.carrier']);

            return [
                'order' => $customerOrder,
                'fulfillment' => $fulfillment->load('carrier'),
            ];
        });
    }

    public function processRefund(CustomerOrder $customerOrder, array $data): CustomerOrder
    {
        $refundAmount = (float) $data['refund_amount'];
        $isFull = (bool) $data['is_full_refund'];

        $customerOrder->refund_amount = $refundAmount;
        $customerOrder->refund_status = $isFull ? 'Full' : 'Partial';

        if ($isFull) {
            $customerOrder->payment_status = 'Refunded';
            $refundStatus = RefOrderStatus::where('name', 'refund')->first()
                ?? RefOrderStatus::where('name', 'cancel')->first();
            if ($refundStatus) {
                $customerOrder->ref_order_status_id = $refundStatus->id;
            }
        }

        if (! empty($data['notes'])) {
            $customerOrder->notes = trim($customerOrder->notes . "\nRefund note: " . $data['notes']);
        }

        $customerOrder->save();

        return $customerOrder->load(['status', 'items', 'fulfillment.carrier']);
    }

    public function markPackingSlipPrinted(CustomerOrder $customerOrder): CustomerOrder
    {
        $customerOrder->update([
            'packing_slip_printed' => true,
        ]);

        if ($customerOrder->fulfillment) {
            $customerOrder->fulfillment->update([
                'packing_slip_printed' => true,
            ]);
        }

        return $customerOrder->load(['status', 'items', 'fulfillment.carrier']);
    }
}
