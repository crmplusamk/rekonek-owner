<?php

namespace Modules\Addon\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
            'name' => 'nama addon',
            'charge' => 'charge',
            'price' => 'harga',
            'description' => 'deskripsi',
        ];
    }
}
