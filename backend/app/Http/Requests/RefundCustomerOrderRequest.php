<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefundCustomerOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'refund_amount' => 'required|numeric|min:0.01',
            'is_full_refund' => 'required|boolean',
            'notes' => 'nullable|string',
        ];
    }
}
