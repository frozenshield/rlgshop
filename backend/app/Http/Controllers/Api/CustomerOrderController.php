<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderFulfillment;
use App\Models\CustomerOrderItem;
use App\Models\RefOrderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerOrderController extends Controller
{
    /**
     * Display a listing of customer orders with filters and eager-loaded relations.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CustomerOrder::with([
            'status',
            'items',
            'fulfillment.carrier',
            'fulfillments.carrier',
        ]);

        // Search by order number, customer name, email, phone, or tracking number
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search): void {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhereHas('fulfillment', function ($fq) use ($search): void {
                        $fq->where('tracking_number', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by status (by status id or status name)
        if ($request->filled('status') && $request->input('status') !== 'All') {
            $statusInput = $request->input('status');
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

        // Filter by carrier
        if ($request->filled('carrier_id')) {
            $query->whereHas('fulfillment', function ($fq) use ($request): void {
                $fq->where('ref_shipping_carrier_id', $request->input('carrier_id'));
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = strtolower((string) $request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['order_date', 'total_amount', 'created_at', 'order_number'])) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $orders = $query->get();

        return response()->json([
            'success' => true,
            'count' => $orders->count(),
            'data' => $orders,
        ]);
    }

    /**
     * Store a new customer order with its line items.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'shipping_address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'payment_method' => 'nullable|string|max:50',
            'payment_status' => 'nullable|string|max:50',
            'ref_order_status_id' => 'nullable|exists:ref_order_status,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_name' => 'required|string',
            'items.*.sku' => 'nullable|string',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.image_url' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $request): JsonResponse {
            // Default status to 'pending' or 'processing'
            $statusId = $validated['ref_order_status_id'] ?? null;
            if (! $statusId) {
                $defaultStatus = RefOrderStatus::where('name', 'processing')->first()
                    ?? RefOrderStatus::where('name', 'pending')->first();
                $statusId = $defaultStatus?->id;
            }

            // Generate order number ORD-XXXX
            $orderNumber = $request->input('order_number');
            if (! $orderNumber) {
                $orderNumber = 'ORD-'.strtoupper(Str::random(4));
                while (CustomerOrder::where('order_number', $orderNumber)->exists()) {
                    $orderNumber = 'ORD-'.strtoupper(Str::random(4));
                }
            }

            // Calculate total amount
            $totalAmount = 0.00;
            foreach ($validated['items'] as $item) {
                $totalAmount += ((float) $item['price']) * ((int) $item['quantity']);
            }

            $order = CustomerOrder::create([
                'order_number' => $orderNumber,
                'order_date' => now(),
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'shipping_address' => $validated['shipping_address'] ?? null,
                'city' => $validated['city'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'user_id' => $request->user()?->id,
                'total_amount' => $totalAmount,
                'payment_method' => $validated['payment_method'] ?? 'GCash',
                'payment_status' => $validated['payment_status'] ?? 'Paid',
                'ref_order_status_id' => $statusId,
                'invoice_id' => 'INV-'.date('Y').'-'.rand(100, 999),
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
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

            $order->load(['status', 'items', 'fulfillment.carrier']);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully.',
                'data' => $order,
            ], 201);
        });
    }

    /**
     * Display a specific order.
     */
    public function show(CustomerOrder $customerOrder): JsonResponse
    {
        $customerOrder->load([
            'status',
            'items.product',
            'fulfillment.carrier',
            'fulfillments.carrier',
            'customerProfile',
        ]);

        return response()->json([
            'success' => true,
            'data' => $customerOrder,
        ]);
    }

    /**
     * Update order status.
     */
    public function updateStatus(Request $request, CustomerOrder $customerOrder): JsonResponse
    {
        $validated = $request->validate([
            'ref_order_status_id' => 'nullable|exists:ref_order_status,id',
            'status_name' => 'nullable|string',
        ]);

        $statusId = $validated['ref_order_status_id'] ?? null;
        if (! $statusId && ! empty($validated['status_name'])) {
            $status = RefOrderStatus::where('name', strtolower(trim($validated['status_name'])))
                ->orWhere('label', 'like', trim($validated['status_name']))
                ->first();
            if ($status) {
                $statusId = $status->id;
            }
        }

        if (! $statusId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid order status specified.',
            ], 422);
        }

        $customerOrder->update([
            'ref_order_status_id' => $statusId,
        ]);

        $customerOrder->load(['status', 'items', 'fulfillment.carrier']);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => $customerOrder,
        ]);
    }

    /**
     * Attach fulfillment & tracking number using a shipping carrier from ref_shipping_carrier.
     */
    public function attachFulfillment(Request $request, CustomerOrder $customerOrder): JsonResponse
    {
        $validated = $request->validate([
            'ref_shipping_carrier_id' => 'required|exists:ref_shipping_carrier,id',
            'tracking_number' => 'required|string|max:100',
            'packing_slip_printed' => 'nullable|boolean',
            'mark_shipped' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $customerOrder): JsonResponse {
            $fulfillment = CustomerOrderFulfillment::updateOrCreate(
                ['customer_order_id' => $customerOrder->id],
                [
                    'ref_shipping_carrier_id' => $validated['ref_shipping_carrier_id'],
                    'tracking_number' => $validated['tracking_number'],
                    'packing_slip_printed' => $validated['packing_slip_printed'] ?? true,
                    'shipped_at' => now(),
                    'notes' => $validated['notes'] ?? null,
                ]
            );

            // Update order status to shipped if mark_shipped is true (default true)
            $markShipped = $validated['mark_shipped'] ?? true;
            if ($markShipped) {
                $shippedStatus = RefOrderStatus::where('name', 'shipped')->first();
                if ($shippedStatus) {
                    $customerOrder->ref_order_status_id = $shippedStatus->id;
                }
            }

            if ($validated['packing_slip_printed'] ?? true) {
                $customerOrder->packing_slip_printed = true;
            }

            $customerOrder->save();
            $customerOrder->load(['status', 'items', 'fulfillment.carrier']);

            return response()->json([
                'success' => true,
                'message' => 'Fulfillment and tracking attached successfully.',
                'data' => [
                    'order' => $customerOrder,
                    'fulfillment' => $fulfillment->load('carrier'),
                ],
            ]);
        });
    }

    /**
     * Process order refund.
     */
    public function processRefund(Request $request, CustomerOrder $customerOrder): JsonResponse
    {
        $validated = $request->validate([
            'refund_amount' => 'required|numeric|min:0.01',
            'is_full_refund' => 'required|boolean',
            'notes' => 'nullable|string',
        ]);

        $refundAmount = (float) $validated['refund_amount'];
        $isFull = (bool) $validated['is_full_refund'];

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

        if (! empty($validated['notes'])) {
            $customerOrder->notes = trim($customerOrder->notes."\nRefund note: ".$validated['notes']);
        }

        $customerOrder->save();
        $customerOrder->load(['status', 'items', 'fulfillment.carrier']);

        return response()->json([
            'success' => true,
            'message' => 'Refund processed successfully.',
            'data' => $customerOrder,
        ]);
    }

    /**
     * Mark packing slip as printed.
     */
    public function markPackingSlipPrinted(CustomerOrder $customerOrder): JsonResponse
    {
        $customerOrder->update([
            'packing_slip_printed' => true,
        ]);

        if ($customerOrder->fulfillment) {
            $customerOrder->fulfillment->update([
                'packing_slip_printed' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Packing slip marked as printed.',
            'data' => $customerOrder->load(['status', 'items', 'fulfillment.carrier']),
        ]);
    }
}
