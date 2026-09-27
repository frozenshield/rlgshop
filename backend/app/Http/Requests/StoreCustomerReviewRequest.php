<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'nullable|exists:users,id',
            'product_id' => 'required|exists:products,id',
            'stars' => 'required|integer|min:1|max:5',
            'message' => 'required|string',
            'image' => 'nullable|string',
        ];
    }
}
