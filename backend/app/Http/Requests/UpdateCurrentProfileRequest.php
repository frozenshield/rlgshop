<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCurrentProfileRequest extends FormRequest
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
        ];
    }
}
