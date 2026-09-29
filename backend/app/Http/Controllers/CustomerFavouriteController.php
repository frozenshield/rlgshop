<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerFavouriteRequest;
use App\Models\CustomerFavourite;
use Illuminate\Http\Request;

class CustomerFavouriteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $customerFavourites = CustomerFavourite::where('user_id', $request->user()->id)->get();

        return response()->json([
            'message' => 'Customer favourites retrieved successfully',
            'data' => $customerFavourites
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerFavouriteRequest $request)
    {
        $validated = $request->validated();
        $validated['user_id'] = $request->user()->id;

        $customerFavourite = CustomerFavourite::firstOrCreate(
            ['user_id' => $validated['user_id'], 'product_id' => $validated['product_id']],
            $validated
        );

        return response()->json([
            'message' => 'Customer favourite created successfully',
            'data' => $customerFavourite
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $productId)
    {
        $deleted = CustomerFavourite::where('user_id', $request->user()->id)
            ->where('product_id', $productId)
            ->delete();

        if ($deleted) {
            return response()->json([
                'message' => 'Customer favourite deleted successfully'
            ]);
        }

        return response()->json([
            'message' => 'Customer favourite not found'
        ], 404);
    }
}
