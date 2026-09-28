<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'nullable|integer|exists:users,id',
            'admin_id' => 'nullable|integer|exists:users,id',
            'message' => 'nullable|string|max:5000',
        ];
    }
}
