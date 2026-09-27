<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttachOrderFulfillmentRequest;
use App\Http\Requests\RefundCustomerOrderRequest;
use App\Http\Requests\StoreCustomerOrderRequest;
use App\Http\Requests\UpdateCustomerOrderStatusRequest;
use App\Models\CustomerOrder;
use App\Services\CustomerOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    public function __construct(
        protected CustomerOrderService $customerOrderService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $orders = $this->customerOrderService->getOrders($request->all());

        return response()->json([
            'success' => true,
            'count' => $orders->count(),
            'data' => $orders,
        ]);
    }

    public function store(StoreCustomerOrderRequest $request): JsonResponse
    {
        $order = $this->customerOrderService->createOrder(
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully.',
            'data' => $order,
        ], 201);
    }

    public function show(CustomerOrder $customerOrder): JsonResponse
    {
        $customerOrder->load([
            'status',
            'items.product',
            'fulfillment.carrier',
            'fulfillments.carrier',
            'customerProfile.shippingAddress',
            'customerProfile.user',
        ]);

        return response()->json([
            'success' => true,
            'data' => $customerOrder,
        ]);
    }

    public function updateStatus(UpdateCustomerOrderStatusRequest $request, CustomerOrder $customerOrder): JsonResponse
    {
        $order = $this->customerOrderService->updateStatus($customerOrder, $request->validated());

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid order status specified.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => $order,
        ]);
    }

    public function attachFulfillment(AttachOrderFulfillmentRequest $request, CustomerOrder $customerOrder): JsonResponse
    {
        $data = $this->customerOrderService->attachFulfillment($customerOrder, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Fulfillment and tracking attached successfully.',
            'data' => $data,
        ]);
    }

    public function processRefund(RefundCustomerOrderRequest $request, CustomerOrder $customerOrder): JsonResponse
    {
        $order = $this->customerOrderService->processRefund($customerOrder, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Refund processed successfully.',
            'data' => $order,
        ]);
    }

    public function markPackingSlipPrinted(CustomerOrder $customerOrder): JsonResponse
    {
        $order = $this->customerOrderService->markPackingSlipPrinted($customerOrder);

        return response()->json([
            'success' => true,
            'message' => 'Packing slip marked as printed.',
            'data' => $order,
        ]);
    }
}
