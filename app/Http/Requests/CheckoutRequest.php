<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_address_id' => [
                'required',
                'exists:customer_address,id,user_id,' . $this->user()->id
            ],
            'payment_method' => 'required|in:cod,vnpay,momo',
            'shipping_service_id' => 'required|integer',
            'voucher_code' => 'nullable|string',
            'note' => 'nullable|string|max:500'
        ];
    }

    public function messages(): array
    {
        return [
            'customer_address_id.exists' => 'Địa chỉ giao hàng không hợp lệ.',
            'payment_method.in' => 'Phương thức thanh toán không được hỗ trợ.',
        ];
    }
}
