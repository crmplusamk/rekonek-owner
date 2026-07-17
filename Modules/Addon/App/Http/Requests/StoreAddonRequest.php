<?php

namespace Modules\Addon\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'feature' => ['required', 'exists:features,id'],
            'name' => ['required', 'string', 'max:100'],
            'charge' => ['required', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_type' => ['required', 'in:recurring,onetime'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'feature' => 'fitur',
            'name' => 'nama addon',
            'charge' => 'charge',
            'price' => 'harga',
            'description' => 'deskripsi',
        ];
    }
}
