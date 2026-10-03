<?php

namespace App\Services;

use App\Mail\StaticQrPaymentInstructionMail;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderFulfillment;
use App\Models\CustomerOrderItem;
use App\Models\CustomerProfile;
use App\Models\CustomerShippmentAddress;
use App\Models\RefOrderStatus;
use App\Models\RefPaymentMerch;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CustomerOrderService
{
    public function getOrders(array $filters): Collection
    {
        $query = CustomerOrder::with([
            'customerProfile.user',
            'status',
            'items',
            'fulfillment.carrier',
        ]);

        $currentUser = auth('sanctum')->user() ?? auth()->user();
        $authId = $currentUser?->id ?? auth('sanctum')->id() ?? auth()->id();

        // Staff and admin can view all orders or filter by user_id;
        // Regular customers and unauthenticated users are strictly scoped to auth()->id()
        if ($currentUser && in_array($currentUser->user_type, ['staff', 'admin'])) {
            if (! empty($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }
        } elseif ($authId) {
            $query->where('user_id', $authId);
        } else {
            $query->whereRaw('1 = 0');
        }

        if (! empty($filters['status'])) {
            $statusStr = strtolower(trim((string) $filters['status']));
            $query->whereHas('status', function ($q) use ($statusStr): void {
                $q->where('name', $statusStr);
            });
        }

        if (! empty($filters['carrier_id'])) {
            $query->whereHas('fulfillment', function ($fq) use ($filters): void {
                $fq->where('ref_shipping_carrier_id', $filters['carrier_id']);
            });
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search): void {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customerProfile', function ($pq) use ($search): void {
                        $pq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhereHas('user', function ($uq) use ($search): void {
                                $uq->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = strtolower($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
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

            $orderNumber = 'ORD-'.strtoupper(Str::random(8));

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

            $rawMethod = strtoupper(trim((string) ($data['payment_method'] ?? 'CARD')));
            $isQrPayment = str_contains($rawMethod, 'QR') || str_contains($rawMethod, 'WALLET');
            $qrMerchant = null;

            if ($isQrPayment || ! empty($data['qr_merchant_code'])) {
                $merchantCode = strtolower(trim((string) ($data['qr_merchant_code'] ?? '')));
                if ($merchantCode) {
                    $qrMerchant = RefPaymentMerch::where('code', $merchantCode)->first();
                }
                if (! $qrMerchant && str_contains($rawMethod, 'GOTYME')) {
                    $qrMerchant = RefPaymentMerch::where('code', 'gotyme')->first();
                } elseif (! $qrMerchant && str_contains($rawMethod, 'GCASH')) {
                    $qrMerchant = RefPaymentMerch::where('code', 'gcash')->first();
                } elseif (! $qrMerchant && str_contains($rawMethod, 'MARIBANK')) {
                    $qrMerchant = RefPaymentMerch::where('code', 'maribank')->first();
                } elseif (! $qrMerchant && str_contains($rawMethod, 'MAYA')) {
                    $qrMerchant = RefPaymentMerch::where('code', 'paymaya')->first();
                }

                if (! $qrMerchant && $isQrPayment) {
                    $qrMerchant = RefPaymentMerch::where('code', 'gotyme')->first()
                        ?? RefPaymentMerch::first();
                }

                if ($qrMerchant) {
                    $isQrPayment = true;
                    $paymentMethodName = 'QR - '.$qrMerchant->name;
                    $paymentStatus = 'Pending';
                    $statusId = 1; // Pending manual verification
                } else {
                    $paymentMethodName = 'QR Code';
                    $paymentStatus = 'Pending';
                    $statusId = 1;
                }
            } else {
                $paymentMethodName = $data['payment_method'] ?? 'Card';
                $paymentStatus = $data['payment_status'] ?? 'Paid';
            }

            $order = CustomerOrder::create([
                'order_number' => $orderNumber,
                'order_date' => now(),
                'customer_profile_id' => $customerProfileId,
                'user_id' => $userId,
                'total_amount' => $totalAmount,
                'payment_method' => $paymentMethodName,
                'payment_status' => $paymentStatus,
                'ref_order_status_id' => $statusId,
                'invoice_id' => 'INV-'.date('Y').'-'.rand(100, 999),
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

            // Option 1: Trigger Static QR Email with attached QR image
            if ($isQrPayment) {
                $recipientEmail = $data['customer_email']
                    ?? $order->customerProfile?->email
                    ?? $currentUser?->email;

                if ($recipientEmail) {
                    try {
                        Mail::to($recipientEmail)->send(new StaticQrPaymentInstructionMail($order, $qrMerchant));
                    } catch (\Throwable $e) {
                        Log::warning('Static QR payment instruction email could not be delivered: '.$e->getMessage());
                    }
                }
            }

            return $order->load(['customerProfile.user', 'customerProfile.shippingAddress', 'status', 'items', 'fulfillment.carrier']);
        });
    }

    public function updateStatus(CustomerOrder $customerOrder, array $data): ?CustomerOrder
    {
        $statusId = $data['ref_order_status_id'] ?? null;
        if (! $statusId && ! empty($data['status_name'])) {
            $rawStatus = trim((string) $data['status_name']);
            $snakeStatus = Str::snake($rawStatus);
            $normalizedStatus = str_replace(['-', ' '], '_', strtolower($rawStatus));
            $status = RefOrderStatus::where('name', strtolower($rawStatus))
                ->orWhere('name', $snakeStatus)
                ->orWhere('name', $normalizedStatus)
                ->orWhere('label', 'like', $rawStatus)
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
            $customerOrder->notes = trim($customerOrder->notes."\nRefund note: ".$data['notes']);
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
