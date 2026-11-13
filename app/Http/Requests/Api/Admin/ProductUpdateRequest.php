<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'category' => ['sometimes', 'required', 'in:tiket_masuk,makanan,minuman,souvenir,baju_merchandise,produk_umkm'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'type' => ['sometimes', 'nullable', 'in:addon,pos'],
            'stock' => ['sometimes', 'required', 'integer', 'min:0'],
            'for_kasir_makanan' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
