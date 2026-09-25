<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GeminiProductAnalyzer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AiProductController extends Controller
{
    /**
     * Analyze a product image and return AI-generated details.
     */
    public function analyzeImage(Request $request, GeminiProductAnalyzer $analyzer): JsonResponse
    {
        $request->validate([
            'image' => 'nullable|file|image|max:15360',
            'image_url' => 'nullable|url',
        ]);

        if (! $request->hasFile('image') && ! $request->filled('image_url')) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a product image file or image URL.',
            ], 422);
        }

        try {
            $imageInput = $request->file('image') ?? $request->input('image_url');
            $productData = $analyzer->analyze($imageInput);

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
