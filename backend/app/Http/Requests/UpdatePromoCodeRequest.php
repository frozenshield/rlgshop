<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePromoCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('promo_code') ? $this->route('promo_code')->id : null;

        return [
            'code' => "nullable|string|max:50|unique:promo_codes,code,{$id}",
            'type' => 'nullable|string|in:percentage,fixed,shipping',
            'value' => 'nullable|numeric|min:0',
            'usage_count' => 'nullable|integer|min:0',
            'usage_limit' => 'nullable|integer|min:0',
            'usageLimit' => 'nullable|integer|min:0',
            'expiry_date' => 'nullable|date',
            'expiryDate' => 'nullable|date',
            'is_active' => 'nullable|boolean',
            'isActive' => 'nullable|boolean',
            'min_order_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ];
    }
}
