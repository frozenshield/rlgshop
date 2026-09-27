<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'nullable|exists:users,id',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
            'status' => 'nullable|in:ongoing,resolve',
        ];
    }
}
