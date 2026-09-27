<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'avatar' => 'nullable|string',
            'address_line1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'favorite_franchise' => 'nullable|string|max:100',
            'bio' => 'nullable|string',
            'two_factor_auth' => 'nullable|boolean',
            'email_notifications' => 'nullable|boolean',
            'order_updates_sms' => 'nullable|boolean',
            'marketing_emails' => 'nullable|boolean',
            'currency_preference' => 'nullable|string|max:10',
            'public_collection' => 'nullable|boolean',
            'segment' => 'nullable|in:VIP,Regular,Wholesale,Inactive',
            'notes' => 'nullable|string',
        ];
    }
}
