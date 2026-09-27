<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:staff,email',
            'password' => 'nullable|string|min:6',
            'ref_staff_role_id' => 'nullable|integer',
            'staff_role_id' => 'nullable|integer',
            'role_id' => 'nullable|integer',
            'role' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ];
    }
}
