<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnalyzeProductImageRequest;
use App\Services\GeminiProductAnalyzer;
use Illuminate\Http\JsonResponse;
use Throwable;

class AiProductController extends Controller
{
    public function __construct(
        protected GeminiProductAnalyzer $analyzerService
    ) {}

    public function analyzeImage(AnalyzeProductImageRequest $request): JsonResponse
    {
        if (! $request->hasFile('image') && ! $request->filled('image_url')) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a product image file or image URL.',
            ], 422);
        }

        try {
            $imageInput = $request->file('image') ?? $request->input('image_url');
            $productData = $this->analyzerService->analyze($imageInput);

            return response()->json([
                'success' => true,
                'data' => $productData,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
