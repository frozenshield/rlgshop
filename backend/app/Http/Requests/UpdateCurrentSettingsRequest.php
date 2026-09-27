<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCurrentSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'two_factor_auth' => 'nullable|boolean',
            'email_notifications' => 'nullable|boolean',
            'order_updates_sms' => 'nullable|boolean',
            'marketing_emails' => 'nullable|boolean',
            'currency_preference' => 'nullable|string|max:10',
            'public_collection' => 'nullable|boolean',
            'current_password' => 'nullable|string',
            'new_password' => 'nullable|string|min:8|confirmed',
        ];
    }
}
