<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:tiket_masuk,makanan,minuman,souvenir,baju_merchandise,produk_umkm'],
            'price' => ['required', 'numeric', 'min:0'],
            'type' => ['nullable', 'in:addon,pos'],
            'stock' => ['required', 'integer', 'min:0'],
            'for_kasir_makanan' => ['nullable', 'boolean'],
        ];
    }
}
