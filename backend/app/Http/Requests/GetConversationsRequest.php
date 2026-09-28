<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetConversationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'nullable|string|in:active,closed,resolved,all',
            'search' => 'nullable|string|max:100',
            'customer_id' => 'nullable|integer|exists:users,id',
        ];
    }
}
