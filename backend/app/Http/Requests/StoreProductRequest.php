<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
        ];
    }
}
