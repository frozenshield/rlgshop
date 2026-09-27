<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:100',
            'stock' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'ref_brand_id' => 'nullable|integer',
            'ref_category_id' => 'nullable|integer',
            'ref_subcategory_id' => 'nullable|integer',
            'ref_condition_id' => 'nullable|integer',
            'ref_pokemon_set_id' => 'nullable|integer',
            'pokemon_set' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'status' => 'nullable|string',
            'image_url' => 'nullable|string',
            'gallery_images' => 'nullable|array',
        ];
    }
}
