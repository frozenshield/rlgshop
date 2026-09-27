<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetCustomerOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'nullable|string|max:50',
            'search' => 'nullable|string|max:255',
            'carrier_id' => 'nullable|integer|exists:ref_shipping_carrier,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'sort_by' => 'nullable|string|in:order_date,total_amount,created_at,order_number',
            'sort_dir' => 'nullable|string|in:asc,desc',
        ];
    }
}
