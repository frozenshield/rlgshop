<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerCartRequest;
use App\Models\CustomerCart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerCartController extends Controller
{
    /**
     * Resolve the active customer ID from Sanctum auth token, session, request input, header, or query.
     */
    protected function resolveCustomerId(Request $request): ?int
    {
        $user = auth('sanctum')->user() ?? auth()->user();
        if ($user) {
            return (int) $user->id;
        }

        $id = $request->input('customer_id')
            ?? $request->query('customer_id')
            ?? $request->header('X-Customer-Id');

        return $id ? (int) $id : 1;
    }

    public function index(Request $request): JsonResponse
    {
        $customerId = $this->resolveCustomerId($request);

        $customerCartItems = CustomerCart::with([
            'product.category',
            'product.subcategory',
            'product.brand',
            'product.condition',
            'product.pokemonSet',
        ])
            ->where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'count' => $customerCartItems->count(),
            'data' => $customerCartItems,
        ]);
    }

    public function store(CustomerCartRequest $request): JsonResponse
    {
        $customerId = $this->resolveCustomerId($request);
        $validatedData = $request->validated();
        $validatedData['customer_id'] = $customerId;

        $customerCart = CustomerCart::where('customer_id', $customerId)
            ->where('product_id', $validatedData['product_id'])
            ->first();

        if ($customerCart) {
            $customerCart->update([
                'quantity' => $validatedData['quantity'],
            ]);
        } else {
            $customerCart = CustomerCart::create($validatedData);
        }

        $customerCart->load([
            'product.category',
            'product.subcategory',
            'product.brand',
            'product.condition',
            'product.pokemonSet',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart successfully.',
            'data' => $customerCart,
        ], 201);
    }

    public function update(CustomerCartRequest $request, CustomerCart $customerCart): JsonResponse
    {
        $user = auth('sanctum')->user() ?? auth()->user();
        if ($user && $customerCart->customer_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
            ], 403);
        }

        $validatedData = $request->validated();
        $customerCart->update([
            'quantity' => $validatedData['quantity'],
        ]);

        $customerCart->load([
            'product.category',
            'product.subcategory',
            'product.brand',
            'product.condition',
            'product.pokemonSet',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cart item updated successfully.',
            'data' => $customerCart,
        ]);
    }

    public function destroy(Request $request, CustomerCart $customerCart): JsonResponse
    {
        $user = auth('sanctum')->user() ?? auth()->user();
        if ($user && $customerCart->customer_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
            ], 403);
        }

        $customerCart->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart item removed successfully.',
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $customerId = $this->resolveCustomerId($request);

        CustomerCart::where('customer_id', $customerId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared successfully.',
        ]);
    }
}
