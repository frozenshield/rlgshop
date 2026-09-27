<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AiChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => 'required|string|min:1|max:1000',
            'history' => 'nullable|array',
            'history.*.role' => 'nullable|string',
            'history.*.content' => 'nullable|string',
        ];
    }
}
