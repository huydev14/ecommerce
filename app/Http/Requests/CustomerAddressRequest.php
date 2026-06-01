<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CustomerAddressRequest extends FormRequest
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
        return [
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',

            'province_id' => 'required|integer',
            'district_id' => 'required|integer',
            'ward_code' => 'required|string',

            'province_name' => 'required|string',
            'district_name' => 'required|string',
            'ward_name' => 'required|string',

            'specific_address' => 'required|string|max:255',
            'is_default' => 'boolean',
            'label' => 'required|string|max:50',
            'delivery_note' => 'nullable|string|max:255',
        ];
    }
}
