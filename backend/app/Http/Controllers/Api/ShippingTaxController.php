<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShippingTax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingTaxController extends Controller
{
    /**
     * Display the current active shipping rates and VAT settings.
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->boolean('all')) {
            $rates = ShippingTax::orderBy('id', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $rates,
            ]);
        }

        $shippingTax = ShippingTax::getActive();

        return response()->json([
            'success' => true,
            'data' => $shippingTax,
        ]);
    }

    /**
     * Update or save the active shipping rates and VAT settings.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'standard_shipping_fee' => 'nullable|numeric|min:0',
            'flatShippingRate' => 'nullable|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'freeShippingThreshold' => 'nullable|numeric|min:0',
            'vat_percentage' => 'nullable|numeric|min:0|max:100',
            'taxRatePercent' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $shippingTax = ShippingTax::getActive();

        $updateData = [];

        if (isset($validated['standard_shipping_fee'])) {
            $updateData['standard_shipping_fee'] = $validated['standard_shipping_fee'];
        } elseif (isset($validated['flatShippingRate'])) {
            $updateData['standard_shipping_fee'] = $validated['flatShippingRate'];
        }

        if (isset($validated['free_shipping_threshold'])) {
            $updateData['free_shipping_threshold'] = $validated['free_shipping_threshold'];
        } elseif (isset($validated['freeShippingThreshold'])) {
            $updateData['free_shipping_threshold'] = $validated['freeShippingThreshold'];
        }

        if (isset($validated['vat_percentage'])) {
            $updateData['vat_percentage'] = $validated['vat_percentage'];
        } elseif (isset($validated['taxRatePercent'])) {
            $updateData['vat_percentage'] = $validated['taxRatePercent'];
        }

        if (isset($validated['is_active'])) {
            $updateData['is_active'] = $validated['is_active'];
        }

        $shippingTax->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Shipping rates and VAT rules updated successfully.',
            'data' => $shippingTax->fresh(),
        ]);
    }
}
