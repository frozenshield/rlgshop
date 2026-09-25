<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\RefBrand;
use App\Models\RefCategory;
use App\Models\RefCondition;
use App\Models\RefSubcategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'subcategory', 'brand', 'condition']);

        if ($request->filled('status')) {
            $query->where('status', strtolower($request->string('status')));
        }

        if ($request->filled('category_id')) {
            $query->where('ref_category_id', $request->integer('category_id'));
        }

        if ($request->filled('brand_id')) {
            $query->where('ref_brand_id', $request->integer('brand_id'));
        }

        if ($request->filled('condition_id')) {
            $query->where('ref_condition_id', $request->integer('condition_id'));
        }

        if ($request->filled('search')) {
            $term = $request->string('search');
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        $products = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'count' => $products->count(),
            'data' => $products,
        ]);
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:100',
            'price' => 'nullable|numeric|min:0',
            'sellingPrice' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'ref_category_id' => 'nullable|integer',
            'category_id' => 'nullable|integer',
            'category' => 'nullable|string',
            'ref_subcategory_id' => 'nullable|integer',
            'subcategory_id' => 'nullable|integer',
            'subcategory' => 'nullable|string',
            'ref_brand_id' => 'nullable|integer',
            'brand_id' => 'nullable|integer',
            'brand' => 'nullable|string',
            'vendor' => 'nullable|string',
            'ref_condition_id' => 'nullable|integer',
            'condition_id' => 'nullable|integer',
            'condition' => 'nullable|string',
            'weight' => 'nullable|numeric|min:0',
            'weightGrams' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'dimensionLength' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'dimensionWidth' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'dimensionHeight' => 'nullable|numeric|min:0',
            'status' => 'nullable|string',
            'image_url' => 'nullable|string',
            'gallery_images' => 'nullable|array',
        ]);

        $name = $validated['name'] ?? $validated['title'] ?? 'Untitled Product';
        $price = $validated['price'] ?? $validated['sellingPrice'] ?? 0.00;
        $stock = $validated['stock'] ?? 0;
        $description = $validated['description'] ?? null;

        // Resolve Category
        $categoryId = $validated['ref_category_id'] ?? $validated['category_id'] ?? null;
        if (! $categoryId && ! empty($validated['category'])) {
            $cat = RefCategory::where('desc', $validated['category'])->first();
            $categoryId = $cat?->id;
        }

        // Resolve Subcategory
        $subcategoryId = $validated['ref_subcategory_id'] ?? $validated['subcategory_id'] ?? null;
        if (! $subcategoryId && ! empty($validated['subcategory'])) {
            $sub = RefSubcategory::where('desc', $validated['subcategory'])->first();
            $subcategoryId = $sub?->id;
        }

        // Resolve Brand
        $brandId = $validated['ref_brand_id'] ?? $validated['brand_id'] ?? null;
        if (! $brandId && ! empty($validated['brand'])) {
            $brand = RefBrand::where('name', $validated['brand'])->first();
            $brandId = $brand?->id;
        } elseif (! $brandId && ! empty($validated['vendor'])) {
            $brand = RefBrand::where('name', $validated['vendor'])->first();
            $brandId = $brand?->id;
        }

        // Resolve Condition
        $conditionId = $validated['ref_condition_id'] ?? $validated['condition_id'] ?? null;
        if (! $conditionId && ! empty($validated['condition'])) {
            $cond = RefCondition::where('desc', $validated['condition'])->first();
            $conditionId = $cond?->id;
        }

        // Resolve Dimensions & Weight
        $weight = $validated['weight'] ?? $validated['weightGrams'] ?? null;
        $length = $validated['length'] ?? $validated['dimensionLength'] ?? null;
        $width = $validated['width'] ?? $validated['dimensionWidth'] ?? null;
        $height = $validated['height'] ?? $validated['dimensionHeight'] ?? null;

        // Resolve Publishing Status
        $rawStatus = strtolower($validated['status'] ?? 'active');
        $status = in_array($rawStatus, ['active', 'draft', 'archived'], true) ? $rawStatus : 'active';

        // Resolve SKU — use Gemini suggestion or generate one
        $sku = ! empty($validated['sku'])
            ? strtoupper(trim($validated['sku']))
            : $this->generateSku($name, $categoryId);

        // Ensure uniqueness by appending a suffix if needed
        if (Product::where('sku', $sku)->exists()) {
            $sku = $sku.'-'.strtoupper(Str::random(4));
        }

        $product = Product::create([
            'name' => $name,
            'sku' => $sku,
            'price' => $price,
            'stock' => $stock,
            'description' => $description,
            'ref_category_id' => $categoryId,
            'ref_subcategory_id' => $subcategoryId,
            'ref_brand_id' => $brandId,
            'ref_condition_id' => $conditionId,
            'weight' => $weight,
            'length' => $length,
            'width' => $width,
            'height' => $height,
            'status' => $status,
            'image_url' => $validated['image_url'] ?? null,
            'gallery_images' => $validated['gallery_images'] ?? null,
        ]);

        $product->load(['category', 'subcategory', 'brand', 'condition']);

        Log::info('Product created in database', ['product_id' => $product->id, 'name' => $product->name]);

        return response()->json([
            'success' => true,
            'message' => 'Product saved successfully to database.',
            'data' => $product,
        ], 201);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'subcategory', 'brand', 'condition', 'reviews']);

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $data = $request->only([
            'name',
            'sku',
            'stock',
            'description',
            'ref_brand_id',
            'ref_category_id',
            'ref_subcategory_id',
            'ref_condition_id',
            'price',
            'weight',
            'length',
            'width',
            'height',
            'status',
            'image_url',
            'gallery_images',
        ]);

        if (isset($data['status'])) {
            $data['status'] = strtolower($data['status']);
        }

        $product->update($data);
        $product->load(['category', 'subcategory', 'brand', 'condition']);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data' => $product,
        ]);
    }

    /**
     * Check if a product with a similar name or image URL already exists.
     * Used before saving to warn admins of potential duplicates.
     */
    public function checkDuplicate(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string',
            'image_url' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $duplicates = collect();

        // Match by identical or very similar name (case-insensitive, trimmed)
        if (! empty($request->name)) {
            $nameTrimmed = trim($request->name);
            $byName = Product::where('name', 'like', '%'.substr($nameTrimmed, 0, 30).'%')
                ->with(['category', 'brand'])
                ->get(['id', 'sku', 'name', 'ref_category_id', 'ref_brand_id', 'image_url', 'status']);

            $duplicates = $duplicates->merge($byName);
        }

        // Match by exact image URL (same photo uploaded twice)
        if (! empty($request->image_url)) {
            $byImage = Product::where('image_url', $request->image_url)
                ->with(['category', 'brand'])
                ->get(['id', 'sku', 'name', 'ref_category_id', 'ref_brand_id', 'image_url', 'status']);

            $duplicates = $duplicates->merge($byImage);
        }

        $duplicates = $duplicates->unique('id')->values();

        return response()->json([
            'success' => true,
            'has_duplicates' => $duplicates->isNotEmpty(),
            'count' => $duplicates->count(),
            'data' => $duplicates,
        ]);
    }

    /**
     * Generate a SKU code from the product name and category.
     *
     * Format: CAT-SLUG-XXXX  e.g. TCG-PIKACHU-V-BOXI-A3F2
     */
    protected function generateSku(string $productName, ?int $categoryId): string
    {
        // Derive a 2-4 char category prefix
        $catPrefix = 'PRD';
        if ($categoryId) {
            $cat = RefCategory::find($categoryId);
            if ($cat) {
                // Take first letters of each word, up to 3 chars
                $catPrefix = strtoupper(implode('', array_map(
                    fn ($w) => $w[0] ?? '',
                    preg_split('/[\s\-_]+/', $cat->desc) ?: []
                )));
                $catPrefix = substr($catPrefix, 0, 3) ?: 'PRD';
            }
        }

        // Slug from the first 3 significant words of the product name
        $words = preg_split('/\s+/', trim($productName)) ?: [];
        $slug = strtoupper(implode('-', array_map(
            fn ($w) => substr(preg_replace('/[^A-Z0-9]/i', '', $w), 0, 6),
            array_slice($words, 0, 3)
        )));

        $random = strtoupper(Str::random(4));

        return "{$catPrefix}-{$slug}-{$random}";
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
        ]);
    }
}
