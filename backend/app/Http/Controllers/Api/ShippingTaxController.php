<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShippingTax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingTaxController extends Controller
{
    /**
     * Display the current active shipping rates and VAT settings, or all rules.
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

        $shippingTax = ShippingTax::where('is_active', true)->latest('id')->first();

        return response()->json([
            'success' => true,
            'data' => $shippingTax,
        ]);
    }

    /**
     * Store a new shipping rate and tax rule.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'standard_shipping_fee' => 'required|numeric|min:0',
            'free_shipping_threshold' => 'required|numeric|min:0',
            'vat_percentage' => 'required|numeric|min:0|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $shippingTax = ShippingTax::create([
            'name' => $validated['name'] ?? 'Standard Logistics & Philippine VAT',
            'standard_shipping_fee' => $validated['standard_shipping_fee'],
            'free_shipping_threshold' => $validated['free_shipping_threshold'],
            'vat_percentage' => $validated['vat_percentage'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'New shipping fee and VAT rule added successfully.',
            'data' => $shippingTax,
        ], 201);
    }

    /**
     * Update an existing shipping rate and tax rule, or fallback to the primary active rule.
     */
    public function update(Request $request, ?ShippingTax $shippingTax = null): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'nullable|integer|exists:shipping_tax,id',
            'name' => 'nullable|string|max:255',
            'standard_shipping_fee' => 'nullable|numeric|min:0',
            'flatShippingRate' => 'nullable|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'freeShippingThreshold' => 'nullable|numeric|min:0',
            'vat_percentage' => 'nullable|numeric|min:0|max:100',
            'taxRatePercent' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        if (! $shippingTax || ! $shippingTax->exists) {
            if (! empty($validated['id'])) {
                $shippingTax = ShippingTax::find($validated['id']);
            } else {
                $shippingTax = ShippingTax::getActive();
            }
        }

        $updateData = [];

        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }

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

    /**
     * Toggle the active status of a shipping rate and tax rule.
     */
    public function toggle(ShippingTax $shippingTax): JsonResponse
    {
        $shippingTax->update([
            'is_active' => ! $shippingTax->is_active,
        ]);

        $statusText = $shippingTax->is_active ? 'active' : 'inactive';

        return response()->json([
            'success' => true,
            'message' => "Rule status toggled to {$statusText}.",
            'data' => $shippingTax->fresh(),
        ]);
    }

    /**
     * Delete a shipping rate and tax rule.
     */
    public function destroy(ShippingTax $shippingTax): JsonResponse
    {
        $shippingTax->delete();

        return response()->json([
            'success' => true,
            'message' => 'Shipping and tax rule deleted successfully.',
        ]);
    }
}
