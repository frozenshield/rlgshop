<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBundle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductBundleController extends Controller
{
    /**
     * Display a listing of product bundle pairings.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ProductBundle::with('primaryProduct');

        if ($request->has('primary_product_id')) {
            $query->where('primary_product_id', $request->input('primary_product_id'));
        }

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        $bundles = $query->latest()->get();

        // If no bundles exist yet, auto-seed popular default pairings
        if ($bundles->isEmpty()) {
            $this->seedDefaultBundles();
            $bundles = ProductBundle::with('primaryProduct')->latest()->get();
        }

        $data = $bundles->map(function (ProductBundle $bundle) {
            $companionIds = is_array($bundle->bundle_product_ids) ? $bundle->bundle_product_ids : [];
            $companions = Product::whereIn('id', $companionIds)->get();

            return [
                'id' => $bundle->id,
                'primary_product_id' => $bundle->primary_product_id,
                'primary_product' => $bundle->primaryProduct,
                'title' => $bundle->title,
                'bundle_product_ids' => $bundle->bundle_product_ids,
                'bundled_products' => $companions,
                'discount_percentage' => (float) $bundle->discount_percentage,
                'conversion_lift' => $bundle->conversion_lift,
                'badge_text' => $bundle->badge_text,
                'is_active' => (bool) $bundle->is_active,
                'created_at' => $bundle->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Store a newly created bundle pairing.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'primary_product_id' => 'required|exists:products,id',
            'title' => 'required|string|max:255',
            'bundle_product_ids' => 'required|array|min:1',
            'bundle_product_ids.*' => 'integer|exists:products,id',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'conversion_lift' => 'nullable|string|max:50',
            'badge_text' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $bundle = ProductBundle::create([
            'primary_product_id' => $validated['primary_product_id'],
            'title' => $validated['title'],
            'bundle_product_ids' => array_values(array_unique($validated['bundle_product_ids'])),
            'discount_percentage' => $validated['discount_percentage'] ?? 10.00,
            'conversion_lift' => $validated['conversion_lift'] ?? '+20.0%',
            'badge_text' => $validated['badge_text'] ?? 'Frequently Bought Together',
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $bundle->load('primaryProduct');

        return response()->json([
            'success' => true,
            'message' => 'Product bundle created successfully',
            'data' => $bundle,
        ], 201);
    }

    /**
     * Display the specified bundle.
     */
    public function show(int $id): JsonResponse
    {
        $bundle = ProductBundle::with('primaryProduct')->findOrFail($id);
        $companionIds = is_array($bundle->bundle_product_ids) ? $bundle->bundle_product_ids : [];
        $companions = Product::whereIn('id', $companionIds)->get();

        return response()->json([
            'success' => true,
            'data' => array_merge($bundle->toArray(), [
                'bundled_products' => $companions,
            ]),
        ]);
    }

    /**
     * Update the specified bundle.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $bundle = ProductBundle::findOrFail($id);

        $validated = $request->validate([
            'primary_product_id' => 'sometimes|exists:products,id',
            'title' => 'sometimes|string|max:255',
            'bundle_product_ids' => 'sometimes|array|min:1',
            'bundle_product_ids.*' => 'integer|exists:products,id',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'conversion_lift' => 'nullable|string|max:50',
            'badge_text' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        if (isset($validated['bundle_product_ids'])) {
            $validated['bundle_product_ids'] = array_values(array_unique($validated['bundle_product_ids']));
        }

        $bundle->update($validated);
        $bundle->load('primaryProduct');

        return response()->json([
            'success' => true,
            'message' => 'Product bundle updated successfully',
            'data' => $bundle,
        ]);
    }

    /**
     * Toggle the active status of the specified bundle.
     */
    public function toggle(int $id): JsonResponse
    {
        $bundle = ProductBundle::findOrFail($id);
        $bundle->is_active = ! $bundle->is_active;
        $bundle->save();

        return response()->json([
            'success' => true,
            'is_active' => $bundle->is_active,
            'message' => $bundle->is_active ? 'Bundle activated' : 'Bundle deactivated',
        ]);
    }

    /**
     * Remove the specified bundle.
     */
    public function destroy(int $id): JsonResponse
    {
        $bundle = ProductBundle::findOrFail($id);
        $bundle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product bundle deleted successfully',
        ]);
    }

    /**
     * Seed initial sensible bundles from available products.
     */
    protected function seedDefaultBundles(): void
    {
        $products = Product::take(6)->get();
        if ($products->count() < 2) {
            return;
        }

        $p1 = $products[0];
        $p2 = $products[1];
        $p3 = $products->get(2, $p2);

        ProductBundle::create([
            'primary_product_id' => $p1->id,
            'title' => 'Ultimate Collector Protection Pack',
            'bundle_product_ids' => [$p2->id],
            'discount_percentage' => 12.50,
            'conversion_lift' => '+24.5%',
            'badge_text' => '🔥 Most Popular Combo',
            'is_active' => true,
        ]);

        if ($products->count() >= 3) {
            ProductBundle::create([
                'primary_product_id' => $p2->id,
                'title' => 'Pro Builder Display & Lining Set',
                'bundle_product_ids' => [$p3->id],
                'discount_percentage' => 15.00,
                'conversion_lift' => '+31.0%',
                'badge_text' => '⭐ Recommended Upgrade',
                'is_active' => true,
            ]);
        }
    }
}
