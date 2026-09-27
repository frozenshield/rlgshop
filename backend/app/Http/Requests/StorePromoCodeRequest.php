<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePromoCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50|unique:promo_codes,code',
            'type' => 'required|string|in:percentage,fixed,shipping',
            'value' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usageLimit' => 'nullable|integer|min:1',
            'expiry_date' => 'nullable|date',
            'expiryDate' => 'nullable|date',
            'is_active' => 'nullable|boolean',
            'isActive' => 'nullable|boolean',
            'min_order_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ];
    }
}
