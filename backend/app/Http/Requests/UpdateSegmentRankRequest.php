<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSegmentRankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'segment_rank' => 'required|string|in:VIP,Regular,Wholesale,Inactive',
            'notes' => 'nullable|string',
        ];
    }
}
