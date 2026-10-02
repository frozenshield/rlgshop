<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RefCurrency;
use App\Models\RefWeightUnit;
use App\Models\StoreLocalization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocalizationController extends Controller
{
    /**
     * Get list of currencies from ref_currency.
     */
    public function currencies(Request $request): JsonResponse
    {
        $query = RefCurrency::query();

        if ($request->boolean('active_only', false) || ! $request->has('all')) {
            $query->where('is_active', true);
        }

        // Return all currencies ordered with popular currencies first, then alphabetical
        $popular = ['PHP', 'USD', 'JPY', 'EUR', 'GBP', 'CAD', 'AUD', 'SGD', 'HKD', 'KRW', 'CNY', 'TWD'];

        $currencies = $query->get()->sortBy(function (RefCurrency $curr) use ($popular) {
            $idx = array_search($curr->code, $popular);

            return $idx !== false ? sprintf('%02d_%s', $idx, $curr->name) : '99_'.$curr->name;
        })->values();

        return response()->json([
            'success' => true,
            'data' => $currencies,
        ]);
    }

    /**
     * Get list of weight units from ref_weight_unit.
     */
    public function weightUnits(Request $request): JsonResponse
    {
        $query = RefWeightUnit::query();

        if ($request->boolean('active_only', false) || ! $request->has('all')) {
            $query->where('is_active', true);
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('id')->get(),
        ]);
    }

    /**
     * Get store localization settings.
     */
    public function show(): JsonResponse
    {
        $settings = StoreLocalization::getSettings();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update store localization settings.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_legal_name' => 'nullable|string|max:255',
            'storeName' => 'nullable|string|max:255',
            'brand_logo_url' => 'nullable|string|max:500',
            'brand_logo_title' => 'nullable|string|max:255',
            'brand_logo_subtitle' => 'nullable|string|max:255',
            'currency_code' => 'nullable|string|max:10',
            'currency' => 'nullable|string|max:10',
            'ref_currency_id' => 'nullable|integer|exists:ref_currency,id',
            'timezone' => 'nullable|string|max:255',
            'weight_unit_code' => 'nullable|string|max:20',
            'weightUnit' => 'nullable|string|max:20',
            'ref_weight_unit_id' => 'nullable|integer|exists:ref_weight_unit,id',
            'date_format' => 'nullable|string|max:50',
        ]);

        $settings = StoreLocalization::getSettings();

        $updateData = [];

        if (! empty($validated['store_legal_name'])) {
            $updateData['store_legal_name'] = $validated['store_legal_name'];
        } elseif (! empty($validated['storeName'])) {
            $updateData['store_legal_name'] = $validated['storeName'];
        }

        if (isset($validated['brand_logo_url'])) {
            $updateData['brand_logo_url'] = $validated['brand_logo_url'];
        }

        if (! empty($validated['brand_logo_title'])) {
            $updateData['brand_logo_title'] = $validated['brand_logo_title'];
        }

        if (! empty($validated['brand_logo_subtitle'])) {
            $updateData['brand_logo_subtitle'] = $validated['brand_logo_subtitle'];
        }

        $currCode = $validated['currency_code'] ?? $validated['currency'] ?? null;
        if ($currCode) {
            $updateData['currency_code'] = $currCode;
            $curr = RefCurrency::where('code', $currCode)->first();
            if ($curr) {
                $updateData['ref_currency_id'] = $curr->id;
            }
        } elseif (! empty($validated['ref_currency_id'])) {
            $curr = RefCurrency::find($validated['ref_currency_id']);
            if ($curr) {
                $updateData['ref_currency_id'] = $curr->id;
                $updateData['currency_code'] = $curr->code;
            }
        }

        if (! empty($validated['timezone'])) {
            $updateData['timezone'] = $validated['timezone'];
        }

        $unitCode = $validated['weight_unit_code'] ?? $validated['weightUnit'] ?? null;
        if ($unitCode) {
            $updateData['weight_unit_code'] = $unitCode;
            $unit = RefWeightUnit::where('code', $unitCode)->first();
            if ($unit) {
                $updateData['ref_weight_unit_id'] = $unit->id;
            }
        } elseif (! empty($validated['ref_weight_unit_id'])) {
            $unit = RefWeightUnit::find($validated['ref_weight_unit_id']);
            if ($unit) {
                $updateData['ref_weight_unit_id'] = $unit->id;
                $updateData['weight_unit_code'] = $unit->code;
            }
        }

        if (! empty($validated['date_format'])) {
            $updateData['date_format'] = $validated['date_format'];
        }

        $settings->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Store localization settings updated successfully.',
            'data' => $settings->fresh(['currency', 'weightUnit']),
        ]);
    }
}
