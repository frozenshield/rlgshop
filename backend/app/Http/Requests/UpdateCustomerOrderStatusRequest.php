<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ref_order_status_id' => 'nullable|exists:ref_order_status,id',
            'status_name' => 'nullable|string',
            'payment_status' => 'nullable|in:Pending,Paid,Failed,Refunded',
        ];
    }
}
