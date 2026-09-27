<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetCustomerMessagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'nullable|string|in:ongoing,resolve,all',
            'search' => 'nullable|string|max:255',
            'user_id' => 'nullable|integer|exists:users,id',
        ];
    }
}
