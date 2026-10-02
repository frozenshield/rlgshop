<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_profile_id' => 'nullable|exists:customer_profiles,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_name' => 'required|string',
            'items.*.sku' => 'nullable|string',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.image_url' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'city' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'qr_merchant_code' => 'nullable|string|max:50',
            'payment_status' => 'nullable|in:Pending,Paid,Failed,Refunded',
            'ref_order_status_id' => 'nullable|exists:ref_order_status,id',
            'status_name' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }
}
