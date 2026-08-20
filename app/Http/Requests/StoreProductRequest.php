<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
            'category_id'=>['required','integer','exists:product_categories,id'],
            'name'=>['required','string','max:150'],
            'description'=>['nullable','string','max:2000'],
            'price'=>['required','decimal:0,2','gte:0'],
            'station'=>['nullable','in:kitchen,bar'],
        ];
    }
}
