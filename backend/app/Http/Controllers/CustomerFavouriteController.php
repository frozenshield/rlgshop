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
    public function index()
    {
        $customerFavourites = CustomerFavourite::all();

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
        $customerFavourite = CustomerFavourite::create($request->validated());

        return response()->json([
            'message' => 'Customer favourite created successfully',
            'data' => $customerFavourite
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CustomerFavourite $customerFavourite)
    {
        $customerFavourite->delete();

        return response()->json([
            'message' => 'Customer favourite deleted successfully'
        ]);
    }
}
