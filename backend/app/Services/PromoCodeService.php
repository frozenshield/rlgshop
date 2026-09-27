<?php

namespace App\Services;

use App\Models\PromoCode;
use Illuminate\Database\Eloquent\Collection;

class PromoCodeService
{
    public function getPromoCodes(array $filters): Collection
    {
        $query = PromoCode::query();

        if (!empty($filters['active_only'])) {
            $query->where('is_active', true)
                ->where(function ($q): void {
                    $q->whereNull('expiry_date')
                        ->orWhere('expiry_date', '>=', now()->toDateString());
                });
        }

        if (!empty($filters['search'])) {
            $term = trim($filters['search']);
            $query->where('code', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        }

        return $query->orderBy('id', 'desc')->get();
    }

    public function createPromoCode(array $data): PromoCode
    {
        $code = strtoupper(trim($data['code']));
        $usageLimit = $data['usage_limit'] ?? $data['usageLimit'] ?? null;
        $expiryDate = $data['expiry_date'] ?? $data['expiryDate'] ?? null;
        $isActive = $data['is_active'] ?? $data['isActive'] ?? true;

        return PromoCode::create([
            'code' => $code,
            'type' => $data['type'],
            'value' => $data['value'] ?? 0,
            'usage_count' => 0,
            'usage_limit' => $usageLimit,
            'expiry_date' => $expiryDate,
            'is_active' => $isActive,
            'min_order_amount' => $data['min_order_amount'] ?? 0.00,
            'description' => $data['description'] ?? null,
        ]);
    }

    public function updatePromoCode(PromoCode $promoCode, array $data): PromoCode
    {
        $updateData = [];
        if (! empty($data['code'])) {
            $updateData['code'] = strtoupper(trim($data['code']));
        }
        if (isset($data['type'])) {
            $updateData['type'] = $data['type'];
        }
        if (isset($data['value'])) {
            $updateData['value'] = $data['value'];
        }
        if (isset($data['usage_count'])) {
            $updateData['usage_count'] = $data['usage_count'];
        }
        if (array_key_exists('usage_limit', $data) || array_key_exists('usageLimit', $data)) {
            $updateData['usage_limit'] = $data['usage_limit'] ?? $data['usageLimit'];
        }
        if (array_key_exists('expiry_date', $data) || array_key_exists('expiryDate', $data)) {
            $updateData['expiry_date'] = $data['expiry_date'] ?? $data['expiryDate'];
        }
        if (array_key_exists('is_active', $data) || array_key_exists('isActive', $data)) {
            $updateData['is_active'] = $data['is_active'] ?? $data['isActive'];
        }
        if (isset($data['min_order_amount'])) {
            $updateData['min_order_amount'] = $data['min_order_amount'];
        }
        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }

        $promoCode->update($updateData);

        return $promoCode;
    }

    public function toggleStatus(PromoCode $promoCode): PromoCode
    {
        $promoCode->update(['is_active' => ! $promoCode->is_active]);
        return $promoCode;
    }

    public function deletePromoCode(PromoCode $promoCode): void
    {
        $promoCode->delete();
    }

    public function validateCode(string $code, float $cartTotal): array
    {
        $code = strtoupper(trim($code));
        $promo = PromoCode::where('code', $code)->first();

        if (! $promo) {
            return [
                'valid' => false,
                'status_code' => 404,
                'message' => "Coupon code '{$code}' was not found.",
            ];
        }

        if (! $promo->is_active) {
            return [
                'valid' => false,
                'status_code' => 422,
                'message' => "Coupon code '{$code}' is currently paused or inactive.",
            ];
        }

        if ($promo->is_expired) {
            return [
                'valid' => false,
                'status_code' => 422,
                'message' => "Coupon code '{$code}' expired on {$promo->expiry_date->format('Y-m-d')}.",
            ];
        }

        if ($promo->usage_limit !== null && $promo->usage_count >= $promo->usage_limit) {
            return [
                'valid' => false,
                'status_code' => 422,
                'message' => "Coupon code '{$code}' has reached its maximum usage limit.",
            ];
        }

        if ($promo->min_order_amount > 0 && $cartTotal < $promo->min_order_amount) {
            return [
                'valid' => false,
                'status_code' => 422,
                'message' => "Minimum order amount of ₱{$promo->min_order_amount} required to use this coupon.",
            ];
        }

        $discountAmount = 0.00;
        if ($promo->type === 'percentage') {
            $discountAmount = round(($cartTotal * (float) $promo->value) / 100, 2);
        } elseif ($promo->type === 'fixed') {
            $discountAmount = min((float) $promo->value, $cartTotal);
        }

        return [
            'valid' => true,
            'status_code' => 200,
            'message' => "Promo code '{$code}' applied! ({$promo->formatted_discount})",
            'promo' => $promo,
            'discount_amount' => $discountAmount,
            'final_total' => max(0, $cartTotal - $discountAmount),
        ];
    }
}
