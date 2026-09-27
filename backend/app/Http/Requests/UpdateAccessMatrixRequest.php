<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccessMatrixRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'can_read' => 'nullable|boolean',
            'can_create' => 'nullable|boolean',
            'can_update' => 'nullable|boolean',
            'can_delete' => 'nullable|boolean',
        ];
    }
}
