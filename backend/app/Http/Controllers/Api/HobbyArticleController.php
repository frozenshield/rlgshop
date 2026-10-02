<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HobbyArticle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HobbyArticleController extends Controller
{
    /**
     * Display a listing of hobby articles and guides.
     */
    public function index(Request $request): JsonResponse
    {
        $query = HobbyArticle::query();

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('featured')) {
            $query->where('is_featured', filter_var($request->input('featured'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $articles = $query->orderBy('published_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'count' => $articles->count(),
            'data' => $articles,
        ]);
    }

    /**
     * Store a newly created hobby article or guide.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:hobby_articles,slug',
            'category' => 'nullable|string|max:100',
            'author' => 'nullable|string|max:100',
            'published_at' => 'nullable|date',
            'status' => 'nullable|string|in:Published,Draft,Archived',
            'summary' => 'nullable|string',
            'content' => 'nullable|string',
            'image_url' => 'nullable|string|max:500',
            'sources' => 'nullable|array',
            'is_featured' => 'nullable|boolean',
        ]);

        if (empty($validated['slug'])) {
            $baseSlug = Str::slug($validated['title']);
            $slug = $baseSlug;
            $counter = 1;
            while (HobbyArticle::where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        $validated['category'] = $validated['category'] ?? 'TCG Strategy';
        $validated['author'] = $validated['author'] ?? 'RLG Editorial Staff';
        $validated['status'] = $validated['status'] ?? 'Published';
        $validated['published_at'] = $validated['published_at'] ?? now()->toDateString();

        $article = HobbyArticle::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Hobby article created successfully.',
            'data' => $article,
        ], 201);
    }

    /**
     * Display the specified hobby article by ID or slug.
     */
    public function show(string|int $idOrSlug): JsonResponse
    {
        $article = is_numeric($idOrSlug)
            ? HobbyArticle::find($idOrSlug)
            : HobbyArticle::where('slug', $idOrSlug)->first();

        if (! $article) {
            return response()->json([
                'success' => false,
                'message' => 'Hobby article not found.',
            ], 404);
        }

        // Increment view count
        $article->increment('views_count');

        return response()->json([
            'success' => true,
            'data' => $article,
        ]);
    }

    /**
     * Update the specified hobby article.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $article = HobbyArticle::find($id);

        if (! $article) {
            return response()->json([
                'success' => false,
                'message' => 'Hobby article not found.',
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:hobby_articles,slug,'.$article->id,
            'category' => 'nullable|string|max:100',
            'author' => 'nullable|string|max:100',
            'published_at' => 'nullable|date',
            'status' => 'nullable|string|in:Published,Draft,Archived',
            'summary' => 'nullable|string',
            'content' => 'nullable|string',
            'image_url' => 'nullable|string|max:500',
            'sources' => 'nullable|array',
            'is_featured' => 'nullable|boolean',
        ]);

        $article->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Hobby article updated successfully.',
            'data' => $article,
        ]);
    }

    /**
     * Remove the specified hobby article.
     */
    public function destroy(int $id): JsonResponse
    {
        $article = HobbyArticle::find($id);

        if (! $article) {
            return response()->json([
                'success' => false,
                'message' => 'Hobby article not found.',
            ], 404);
        }

        $article->delete();

        return response()->json([
            'success' => true,
            'message' => 'Hobby article deleted successfully.',
        ]);
    }
}
