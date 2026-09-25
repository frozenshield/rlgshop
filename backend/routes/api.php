<?php

use App\Http\Controllers\Api\AccessMatrixController;
use App\Http\Controllers\Api\AiProductController;
use App\Http\Controllers\Api\CustomerProfileController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PromoCodeController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Models\RefBrand;
use App\Models\RefCategory;
use App\Models\RefCondition;
use App\Models\RefModule;
use App\Models\RefStaffRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Username / Password Authentication
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Google Authentication
Route::get('/auth/google/redirect', [SocialAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);

// AI Automation & Catalog Helpers
Route::post('/ai/analyze-product-image', [AiProductController::class, 'analyzeImage']);
Route::get('/categories', function () {
    return response()->json(RefCategory::with('subcategories')->get());
});
Route::get('/brands', function () {
    return response()->json(RefBrand::orderBy('name')->get());
});
Route::get('/conditions', function () {
    return response()->json(RefCondition::all());
});
Route::get('/staff-roles', function () {
    return response()->json(RefStaffRole::with('accessMatrices.module')->get());
});
Route::get('/modules', function () {
    return response()->json(RefModule::all());
});
Route::get('/access-matrix', [AccessMatrixController::class, 'index']);
Route::put('/access-matrix/{access_matrix}', [AccessMatrixController::class, 'update']);
Route::get('/staff-modules', [AccessMatrixController::class, 'getStaffModules']);
Route::get('/staff/{staff}/modules', [AccessMatrixController::class, 'getStaffModules']);

// Products Catalog API
Route::apiResource('products', ProductController::class);

// Staff RBAC Management API
Route::apiResource('staff', StaffController::class);

// Customer Profiles CRM & Management API
Route::apiResource('customer-profiles', CustomerProfileController::class);

// Promotional Discount Codes Engine API
Route::post('/promo-codes/validate', [PromoCodeController::class, 'validateCode']);
Route::post('/promo-codes/{promo_code}/toggle', [PromoCodeController::class, 'toggle']);
Route::apiResource('promo-codes', PromoCodeController::class);

// Authenticated user & actions
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [CustomerProfileController::class, 'getCurrentProfile']);
    Route::get('/user/profile', [CustomerProfileController::class, 'getCurrentProfile']);
    Route::match(['put', 'post'], '/user/profile', [CustomerProfileController::class, 'updateCurrentProfile']);

    Route::get('/user/settings', [CustomerProfileController::class, 'getCurrentSettings']);
    Route::match(['put', 'post'], '/user/settings', [CustomerProfileController::class, 'updateCurrentSettings']);

    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    });
});
