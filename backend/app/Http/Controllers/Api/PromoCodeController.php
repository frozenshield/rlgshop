<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
{
    /**
     * Display a listing of promotional discount codes.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PromoCode::query();

        if ($request->boolean('active_only')) {
            $query->where('is_active', true)
                ->where(function ($q): void {
                    $q->whereNull('expiry_date')
                        ->orWhere('expiry_date', '>=', now()->toDateString());
                });
        }

        if ($request->filled('search')) {
            $term = $request->string('search');
            $query->where('code', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        }

        $promos = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'count' => $promos->count(),
            'data' => $promos,
        ]);
    }

    /**
     * Store a newly created promotional discount code.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:promo_codes,code',
            'type' => 'required|string|in:percentage,fixed,shipping',
            'value' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usageLimit' => 'nullable|integer|min:1',
            'expiry_date' => 'nullable|date',
            'expiryDate' => 'nullable|date',
            'is_active' => 'nullable|boolean',
            'isActive' => 'nullable|boolean',
            'min_order_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        $code = strtoupper(trim($validated['code']));
        $usageLimit = $validated['usage_limit'] ?? $validated['usageLimit'] ?? null;
        $expiryDate = $validated['expiry_date'] ?? $validated['expiryDate'] ?? null;
        $isActive = $validated['is_active'] ?? $validated['isActive'] ?? true;

        $promo = PromoCode::create([
            'code' => $code,
            'type' => $validated['type'],
            'value' => $validated['value'] ?? 0,
            'usage_count' => 0,
            'usage_limit' => $usageLimit,
            'expiry_date' => $expiryDate,
            'is_active' => $isActive,
            'min_order_amount' => $validated['min_order_amount'] ?? 0.00,
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Promo code {$promo->code} published successfully.",
            'data' => $promo,
        ], 201);
    }

    /**
     * Display the specified promo code.
     */
    public function show(PromoCode $promoCode): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $promoCode,
        ]);
    }

    /**
     * Update the specified promo code in storage.
     */
    public function update(Request $request, PromoCode $promoCode): JsonResponse
    {
        $validated = $request->validate([
            'code' => "nullable|string|max:50|unique:promo_codes,code,{$promoCode->id}",
            'type' => 'nullable|string|in:percentage,fixed,shipping',
            'value' => 'nullable|numeric|min:0',
            'usage_count' => 'nullable|integer|min:0',
            'usage_limit' => 'nullable|integer|min:0',
            'usageLimit' => 'nullable|integer|min:0',
            'expiry_date' => 'nullable|date',
            'expiryDate' => 'nullable|date',
            'is_active' => 'nullable|boolean',
            'isActive' => 'nullable|boolean',
            'min_order_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        $data = [];
        if (! empty($validated['code'])) {
            $data['code'] = strtoupper(trim($validated['code']));
        }
        if (isset($validated['type'])) {
            $data['type'] = $validated['type'];
        }
        if (isset($validated['value'])) {
            $data['value'] = $validated['value'];
        }
        if (isset($validated['usage_count'])) {
            $data['usage_count'] = $validated['usage_count'];
        }
        if (array_key_exists('usage_limit', $validated) || array_key_exists('usageLimit', $validated)) {
            $data['usage_limit'] = $validated['usage_limit'] ?? $validated['usageLimit'];
        }
        if (array_key_exists('expiry_date', $validated) || array_key_exists('expiryDate', $validated)) {
            $data['expiry_date'] = $validated['expiry_date'] ?? $validated['expiryDate'];
        }
        if (array_key_exists('is_active', $validated) || array_key_exists('isActive', $validated)) {
            $data['is_active'] = $validated['is_active'] ?? $validated['isActive'];
        }
        if (isset($validated['min_order_amount'])) {
            $data['min_order_amount'] = $validated['min_order_amount'];
        }
        if (isset($validated['description'])) {
            $data['description'] = $validated['description'];
        }

        $promoCode->update($data);

        return response()->json([
            'success' => true,
            'message' => "Promo code {$promoCode->code} updated successfully.",
            'data' => $promoCode,
        ]);
    }

    /**
     * Quick toggle promo code active status (Pause / Activate).
     */
    public function toggle(PromoCode $promoCode): JsonResponse
    {
        $promoCode->update(['is_active' => ! $promoCode->is_active]);

        $action = $promoCode->is_active ? 'activated' : 'paused';

        return response()->json([
            'success' => true,
            'message' => "Coupon {$promoCode->code} {$action} successfully.",
            'data' => $promoCode,
        ]);
    }

    /**
     * Remove the specified promo code from storage.
     */
    public function destroy(PromoCode $promoCode): JsonResponse
    {
        $code = $promoCode->code;
        $promoCode->delete();

        return response()->json([
            'success' => true,
            'message' => "Promo code {$code} deleted successfully.",
        ]);
    }

    /**
     * Validate a promo code at checkout and compute discount.
     */
    public function validateCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'cart_total' => 'nullable|numeric|min:0',
        ]);

        $code = strtoupper(trim($request->string('code')));
        $promo = PromoCode::where('code', $code)->first();

        if (! $promo) {
            return response()->json([
                'valid' => false,
                'message' => "Coupon code '{$code}' was not found.",
            ], 404);
        }

        if (! $promo->is_active) {
            return response()->json([
                'valid' => false,
                'message' => "Coupon code '{$code}' is currently paused or inactive.",
            ], 422);
        }

        if ($promo->is_expired) {
            return response()->json([
                'valid' => false,
                'message' => "Coupon code '{$code}' expired on {$promo->expiry_date->format('Y-m-d')}.",
            ], 422);
        }

        if ($promo->usage_limit !== null && $promo->usage_count >= $promo->usage_limit) {
            return response()->json([
                'valid' => false,
                'message' => "Coupon code '{$code}' has reached its maximum usage limit.",
            ], 422);
        }

        $cartTotal = (float) $request->input('cart_total', 0);
        if ($promo->min_order_amount > 0 && $cartTotal < $promo->min_order_amount) {
            return response()->json([
                'valid' => false,
                'message' => "Minimum order amount of ₱{$promo->min_order_amount} required to use this coupon.",
            ], 422);
        }

        // Calculate discount
        $discountAmount = 0.00;
        if ($promo->type === 'percentage') {
            $discountAmount = round(($cartTotal * (float) $promo->value) / 100, 2);
        } elseif ($promo->type === 'fixed') {
            $discountAmount = min((float) $promo->value, $cartTotal);
        }

        return response()->json([
            'valid' => true,
            'message' => "Promo code '{$code}' applied! ({$promo->formatted_discount})",
            'promo' => $promo,
            'discount_amount' => $discountAmount,
            'final_total' => max(0, $cartTotal - $discountAmount),
        ]);
    }
}
