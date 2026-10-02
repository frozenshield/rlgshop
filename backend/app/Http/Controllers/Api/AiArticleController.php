<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiArticleGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AiArticleController extends Controller
{
    public function __construct(
        protected AiArticleGeneratorService $articleGenerator
    ) {}

    /**
     * Generate an AI hobby guide or article with trusted source citations.
     */
    public function generate(Request $request): JsonResponse
    {
        @set_time_limit(180);
        @ini_set('max_execution_time', '180');

        $validated = $request->validate([
            'topic' => 'required|string|min:3|max:500',
            'category' => 'nullable|string|max:100',
            'audience' => 'nullable|string|max:50',
            'include_sources' => 'nullable|boolean',
        ]);

        try {
            $article = $this->articleGenerator->generate([
                'topic' => $validated['topic'],
                'category' => $validated['category'] ?? null,
                'audience' => $validated['audience'] ?? 'Intermediate',
                'include_sources' => $validated['include_sources'] ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Article generated successfully using trusted hobby sources.',
                'data' => $article,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'An error occurred while generating the article.',
            ], 500);
        }
    }
}
