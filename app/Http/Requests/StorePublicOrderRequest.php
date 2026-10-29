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
            'customer_name' => ['nullable', 'string', 'max:80'],
            'customer_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9() +\-]{10,20}$/'],
            'whatsapp_consent' => ['nullable', 'boolean'],
            'marketing_consent' => ['nullable', 'boolean'],
            'payment_method' => ['required', 'in:cash,pix,debit,credit,mercadopago'],
            'payment_mode' => ['sometimes', 'required', 'in:offline,online'],
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
            'items.*.option_ids' => ['nullable', 'array', 'max:30'],
            'items.*.option_ids.*' => ['integer'],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            if ($this->input('payment_mode', 'offline') === 'online' && $this->input('payment_method') !== 'mercadopago') {
                $validator->errors()->add('payment_method', 'Escolha o pagamento online pelo Mercado Pago.');
            }
            if ($this->input('payment_mode', 'offline') === 'offline' && $this->input('payment_method') === 'mercadopago') {
                $validator->errors()->add('payment_method', 'Escolha uma forma de pagamento presencial.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'delivery_zone_id.required_if' => 'Selecione a região de entrega para receber o pedido no endereço informado.',
            'postal_code.required_if' => 'Informe o CEP para a entrega.',
            'postal_code.regex' => 'Informe um CEP válido com oito números.',
            'street.required_if' => 'Informe a rua para a entrega.',
            'number.required_if' => 'Informe o número do endereço de entrega.',
            'neighborhood.required_if' => 'Informe o bairro para a entrega.',
            'city.required_if' => 'Informe o município para a entrega.',
            'state.required_if' => 'Informe a sigla do estado para a entrega.',
            'state.size' => 'Informe a sigla do estado com duas letras, como MG.',
            'items.required' => 'Adicione pelo menos um item ao pedido antes de continuar.',
            'items.min' => 'Adicione pelo menos um item ao pedido antes de continuar.',
        ];
    }
}
