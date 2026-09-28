<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Http\Requests\CustomerCartRequest;
use App\Models\CustomerCart;

class CustomerCartController extends Controller
{
    public function store(CustomerCartRequest $request)
    {
        $user = auth()->user();

        $validatedData = $request->validated();
        $validatedData['customer_id'] = $user->id;

        $customerCart = CustomerCart::where('customer_id', $user->id)
            ->where('product_id', $validatedData['product_id'])
            ->first();

        if ($customerCart) {
            $customerCart->update([
                'quantity' => $validatedData['quantity']
            ]);
        } else {
            $customerCart = CustomerCart::create($validatedData);
        }

        $customerCart->load('product');

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart successfully.',
            'data' => $customerCart,
        ], 201);
    }

    public function index()
    {
        $user = auth()->user();
        $customerCartItems = CustomerCart::with('product')->where('customer_id', $user->id)->get();

        return response()->json([
            'success' => true,
            'count' => $customerCartItems->count(),
            'data' => $customerCartItems,
        ]);
    }

    public function update(CustomerCartRequest $request, CustomerCart $customerCart)
    {
        $user = auth()->user();

        if ($customerCart->customer_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
            ], 403);
        }

        $validatedData = $request->validated();
        $customerCart->update($validatedData);

        $customerCart->load('product');

        return response()->json([
            'success' => true,
            'message' => 'Cart item updated successfully.',
            'data' => $customerCart,
        ]);
    }

    public function destroy(CustomerCart $customerCart)
    {
        $user = auth()->user();

        if ($customerCart->customer_id !== $user->id) {
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
}
