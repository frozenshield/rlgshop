<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('staff') ? $this->route('staff')->id : null;

        return [
            'name' => 'nullable|string|max:255',
            'email' => "nullable|string|email|max:255|unique:staff,email,{$id}",
            'password' => 'nullable|string|min:6',
            'ref_staff_role_id' => 'nullable|integer',
            'staff_role_id' => 'nullable|integer',
            'role_id' => 'nullable|integer',
            'role' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ];
    }
}
