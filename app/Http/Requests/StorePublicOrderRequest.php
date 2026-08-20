<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePublicOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'channel' => ['required', 'in:pickup,delivery'],
            'customer_name' => ['required', 'string', 'max:80'],
            'customer_phone' => ['required', 'string', 'max:20', 'regex:/^[0-9() +\-]{10,20}$/'],
            'whatsapp_consent' => ['nullable', 'boolean'],
            'marketing_consent' => ['nullable', 'boolean'],
            'payment_method' => ['required', 'in:cash,pix,debit,credit'],
            'change_for' => ['nullable', 'numeric', 'min:0'],
            'coupon_code' => ['nullable', 'string', 'max:40'],
            'delivery_zone_id' => ['nullable', 'integer', 'required_if:channel,delivery'],
            'postal_code' => ['nullable', 'regex:/^\d{5}-?\d{3}$/', 'required_if:channel,delivery'],
            'street' => ['nullable', 'string', 'max:150', 'required_if:channel,delivery'],
            'number' => ['nullable', 'string', 'max:30', 'required_if:channel,delivery'],
            'complement' => ['nullable', 'string', 'max:100'],
            'neighborhood' => ['nullable', 'string', 'max:100', 'required_if:channel,delivery'],
            'city' => ['nullable', 'string', 'max:100', 'required_if:channel,delivery'],
            'state' => ['nullable', 'string', 'size:2', 'required_if:channel,delivery'],
            'reference' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }
}
