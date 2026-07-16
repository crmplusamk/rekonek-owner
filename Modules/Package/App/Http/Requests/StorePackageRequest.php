<?php

namespace Modules\Package\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration' => ['required', 'integer', 'min:1'],
            'duration_type' => ['required', 'in:month,day'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama package',
            'price' => 'harga',
            'duration' => 'durasi',
            'duration_type' => 'tipe durasi',
            'description' => 'deskripsi',
        ];
    }
}
