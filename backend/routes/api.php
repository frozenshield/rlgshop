<?php

use App\Http\Controllers\Api\AiProductController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Models\RefCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Google Authentication
Route::get('/auth/google/redirect', [SocialAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);

// AI Automation & Catalog Helpers
Route::post('/ai/analyze-product-image', [AiProductController::class, 'analyzeImage']);
Route::get('/categories', function () {
    return response()->json(RefCategory::with('subcategories')->get());
});

// Authenticated user & actions
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    });
});
