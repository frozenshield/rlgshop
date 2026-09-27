<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttachOrderFulfillmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ref_shipping_carrier_id' => 'required|exists:ref_shipping_carrier,id',
            'tracking_number' => 'required|string|max:100',
            'packing_slip_printed' => 'nullable|boolean',
            'mark_shipped' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ];
    }
}
