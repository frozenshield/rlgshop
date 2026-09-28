<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CustomerCartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            return [
                'customer_id' => 'nullable|integer|exists:users,id',
                'product_id' => 'nullable|integer|exists:products,id',
                'quantity' => 'required|integer|min:1',
            ];
        }

        return [
            'customer_id' => 'nullable|integer|exists:users,id',
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ];
    }
}
