<?php

namespace App\Http\Controllers;

use App\Models\CustomerFavourite;
use Illuminate\Http\Request;

class CustomerFavouriteController extends Controller
{
    /**
     * Resolve the active customer ID from Sanctum auth token, request input, or fallback.
     */
    protected function resolveUserId(Request $request): int
    {
        $user = auth('sanctum')->user() ?? auth()->user();
        if ($user) {
            return (int) $user->id;
        }

        $id = $request->input('user_id')
            ?? $request->input('customer_id')
            ?? $request->query('user_id')
            ?? $request->query('customer_id')
            ?? $request->header('X-Customer-Id');

        return $id ? (int) $id : 1;
    }

    /**
     * Display a listing of the customer's favourites with full product data.
     */
    public function index(Request $request)
    {
        $userId = $this->resolveUserId($request);

        $customerFavourites = CustomerFavourite::with([
            'product.category',
            'product.subcategory',
            'product.brand',
            'product.condition',
            'product.pokemonSet',
        ])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'count' => $customerFavourites->count(),
            'data' => $customerFavourites,
        ]);
    }

    /**
     * Store a newly created favourite (toggle: remove if already exists).
     */
    public function store(Request $request)
    {
        $userId = $this->resolveUserId($request);

        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ]);

        // Check if already favourited — toggle off
        $existing = CustomerFavourite::where('user_id', $userId)
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json([
                'success' => true,
                'action' => 'removed',
                'message' => 'Removed from favourites',
            ]);
        }

        $favourite = CustomerFavourite::create([
            'user_id' => $userId,
            'product_id' => $validated['product_id'],
        ]);

        return response()->json([
            'success' => true,
            'action' => 'added',
            'message' => 'Added to favourites',
            'data' => $favourite,
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CustomerFavourite $customerFavourite)
    {
        $customerFavourite->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer favourite deleted successfully',
        ]);
    }
}
