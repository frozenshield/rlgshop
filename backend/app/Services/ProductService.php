<?php

namespace App\Services;

use App\Models\Product;
use App\Models\RefBrand;
use App\Models\RefCategory;
use App\Models\RefCondition;
use App\Models\RefPokemonSet;
use App\Models\RefSubcategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductService
{
    public function getProducts(array $filters): LengthAwarePaginator
    {
        $query = Product::with(['category', 'subcategory', 'brand', 'pokemonSet', 'condition']);

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', strtolower(trim($filters['status'])));
        }

        if (! empty($filters['category_id'])) {
            $query->where('ref_category_id', (int) $filters['category_id']);
        }
        if (! empty($filters['brand_id'])) {
            $query->where('ref_brand_id', (int) $filters['brand_id']);
        }

        if (! empty($filters['sort_by'])) {
            $sortField = in_array($filters['sort_by'], ['name', 'price', 'stock', 'created_at'])
                ? $filters['sort_by']
                : 'created_at';
            $sortOrder = (! empty($filters['sort_dir']) && strtolower($filters['sort_dir']) === 'asc') ? 'asc' : 'desc';
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        if ($perPage < 1 || $perPage > 100) {
            $perPage = 15;
        }

        return $query->paginate($perPage);
    }

    public function createProduct(array $data): Product
    {
        $name = trim($data['name'] ?? $data['title'] ?? 'Unnamed Product');
        $price = (float) ($data['price'] ?? $data['sellingPrice'] ?? 0);
        $stock = (int) ($data['stock'] ?? 0);

        $description = null;
        if (! empty($data['description'])) {
            $description = $this->cleanPlainTextDescription($data['description']);
        }

        $categoryId = $data['ref_category_id'] ?? $data['category_id'] ?? null;
        if (! $categoryId && ! empty($data['category'])) {
            $cat = RefCategory::where('desc', $data['category'])->first();
            $categoryId = $cat?->id;
        }

        $subcategoryId = $data['ref_subcategory_id'] ?? $data['subcategory_id'] ?? null;
        if (! $subcategoryId && ! empty($data['subcategory'])) {
            $sub = RefSubcategory::where('desc', $data['subcategory'])->first();
            $subcategoryId = $sub?->id;
        }

        $brandId = $data['ref_brand_id'] ?? $data['brand_id'] ?? null;
        if (! $brandId && ! empty($data['brand'])) {
            $brand = RefBrand::where('name', $data['brand'])->first();
            $brandId = $brand?->id;
        } elseif (! $brandId && ! empty($data['vendor'])) {
            $brand = RefBrand::where('name', $data['vendor'])->first();
            $brandId = $brand?->id;
        }

        $conditionId = $data['ref_condition_id'] ?? $data['condition_id'] ?? null;
        if (! $conditionId && ! empty($data['condition'])) {
            $cond = RefCondition::where('desc', $data['condition'])->first();
            $conditionId = $cond?->id;
        }

        $pokemonSetId = $data['ref_pokemon_set_id'] ?? $data['pokemon_set_id'] ?? null;
        if (! $pokemonSetId && ! empty($data['pokemon_set'])) {
            $setName = $data['pokemon_set'];
            $setRecord = RefPokemonSet::where('japanese_set', $setName)
                ->orWhere('japanese_code', $setName)
                ->orWhere('english_set', $setName)
                ->first();
            $pokemonSetId = $setRecord?->id;
        }

        $weight = $data['weight'] ?? $data['weightGrams'] ?? null;
        $length = $data['length'] ?? $data['dimensionLength'] ?? null;
        $width = $data['width'] ?? $data['dimensionWidth'] ?? null;
        $height = $data['height'] ?? $data['dimensionHeight'] ?? null;

        $rawStatus = strtolower($data['status'] ?? 'active');
        $status = in_array($rawStatus, ['active', 'draft', 'archived'], true) ? $rawStatus : 'active';

        $sku = ! empty($data['sku'])
            ? strtoupper(trim($data['sku']))
            : $this->generateSku($name, $categoryId);

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
            'image_url' => $data['image_url'] ?? null,
            'gallery_images' => $data['gallery_images'] ?? null,
        ]);

        $product->load(['category', 'subcategory', 'brand', 'condition']);

        Log::info('Product created in database', ['product_id' => $product->id, 'name' => $product->name]);

        return $product;
    }

    public function updateProduct(Product $product, array $data): Product
    {
        if (isset($data['description']) && ! empty($data['description'])) {
            $data['description'] = $this->cleanPlainTextDescription((string) $data['description']);
        }

        if (isset($data['status'])) {
            $data['status'] = strtolower($data['status']);
        }

        $product->update($data);

        return $product->load(['category', 'subcategory', 'brand', 'condition']);
    }

    public function checkDuplicate(array $data): \Illuminate\Support\Collection
    {
        $duplicates = collect();

        if (! empty($data['name'])) {
            $nameTrimmed = trim($data['name']);
            $byName = Product::where('name', 'like', '%'.substr($nameTrimmed, 0, 30).'%')
                ->with(['category', 'brand'])
                ->get(['id', 'sku', 'name', 'ref_category_id', 'ref_brand_id', 'image_url', 'status']);

            $duplicates = $duplicates->merge($byName);
        }

        if (! empty($data['image_url'])) {
            $byImage = Product::where('image_url', $data['image_url'])
                ->with(['category', 'brand'])
                ->get(['id', 'sku', 'name', 'ref_category_id', 'ref_brand_id', 'image_url', 'status']);

            $duplicates = $duplicates->merge($byImage);
        }

        return $duplicates->unique('id')->values();
    }

    public function deleteProduct(Product $product): void
    {
        $product->delete();
    }

    protected function generateSku(string $productName, ?int $categoryId): string
    {
        $catPrefix = 'PRD';
        if ($categoryId) {
            $cat = RefCategory::find($categoryId);
            if ($cat) {
                $catPrefix = strtoupper(implode('', array_map(
                    fn ($w) => $w[0] ?? '',
                    preg_split('/[\s\-_]+/', $cat->desc) ?: []
                )));
                $catPrefix = substr($catPrefix, 0, 3) ?: 'PRD';
            }
        }

        $words = preg_split('/\s+/', trim($productName)) ?: [];
        $slug = strtoupper(implode('-', array_map(
            fn ($w) => substr(preg_replace('/[^A-Z0-9]/i', '', $w), 0, 6),
            array_slice($words, 0, 3)
        )));

        $random = strtoupper(Str::random(4));

        return "{$catPrefix}-{$slug}-{$random}";
    }

    protected function cleanPlainTextDescription(string $desc): string
    {
        $desc = preg_replace('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/is', "\n\n$1\n", $desc);
        $desc = preg_replace('/<li[^>]*>(.*?)<\/li>/is', "\t• $1\n", $desc);
        $desc = preg_replace('/<br\s*\/?>/i', "\n", $desc);
        $desc = preg_replace('/<\/(p|div)>/i', "\n\n", $desc);
        $desc = strip_tags($desc);
        $desc = html_entity_decode($desc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $desc = str_replace("\xc2\xa0", ' ', $desc);
        $desc = preg_replace("/\n{3,}/", "\n\n", $desc);

        return trim($desc);
    }
}
