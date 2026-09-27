<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePromoCodeRequest;
use App\Http\Requests\UpdatePromoCodeRequest;
use App\Http\Requests\ValidatePromoCodeRequest;
use App\Models\PromoCode;
use App\Services\PromoCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
{
    public function __construct(
        protected PromoCodeService $promoCodeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $promos = $this->promoCodeService->getPromoCodes($request->all());

        return response()->json([
            'success' => true,
            'count' => $promos->count(),
            'data' => $promos,
        ]);
    }

    public function store(StorePromoCodeRequest $request): JsonResponse
    {
        $promo = $this->promoCodeService->createPromoCode($request->validated());

        return response()->json([
            'success' => true,
            'message' => "Promo code {$promo->code} published successfully.",
            'data' => $promo,
        ], 201);
    }

    public function show(PromoCode $promoCode): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $promoCode,
        ]);
    }

    public function update(UpdatePromoCodeRequest $request, PromoCode $promoCode): JsonResponse
    {
        $updatedPromo = $this->promoCodeService->updatePromoCode($promoCode, $request->validated());

        return response()->json([
            'success' => true,
            'message' => "Promo code {$updatedPromo->code} updated successfully.",
            'data' => $updatedPromo,
        ]);
    }

    public function toggle(PromoCode $promoCode): JsonResponse
    {
        $promoCode = $this->promoCodeService->toggleStatus($promoCode);

        $action = $promoCode->is_active ? 'activated' : 'paused';

        return response()->json([
            'success' => true,
            'message' => "Coupon {$promoCode->code} {$action} successfully.",
            'data' => $promoCode,
        ]);
    }

    public function destroy(PromoCode $promoCode): JsonResponse
    {
        $code = $promoCode->code;
        $this->promoCodeService->deletePromoCode($promoCode);

        return response()->json([
            'success' => true,
            'message' => "Promo code {$code} deleted successfully.",
        ]);
    }

    public function validateCode(ValidatePromoCodeRequest $request): JsonResponse
    {
        $result = $this->promoCodeService->validateCode(
            $request->input('code'),
            (float) $request->input('cart_total', 0)
        );

        $statusCode = $result['status_code'] ?? 200;
        unset($result['status_code']);

        return response()->json($result, $statusCode);
    }
}
