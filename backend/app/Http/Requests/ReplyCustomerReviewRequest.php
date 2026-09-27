<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReplyCustomerReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'staff_reply' => 'required|string',
            'staff_id' => 'nullable|exists:staff,id',
        ];
    }
}
