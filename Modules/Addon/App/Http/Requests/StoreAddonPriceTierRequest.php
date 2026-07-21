<?php

namespace Modules\Addon\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAddonPriceTierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** addon_id diambil dari route wildcard {addon}, bukan input form. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'addon_id' => $this->route('addon'),
        ]);
    }

    public function rules(): array
    {
        return [
            'addon_id' => ['required', 'exists:addons,id'],
            'min_quantity' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('addon_price_tiers')->where(fn ($query) => $query->where('addon_id', $this->addon_id)),
            ],
            'type' => ['required', 'in:unit_price,percent'],
            'value' => [
                'required',
                'numeric',
                'min:0',
                Rule::when($this->input('type') === 'percent', ['max:100']),
            ],
            'label' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'addon_id' => 'addon',
            'min_quantity' => 'minimal kuantitas',
            'type' => 'tipe',
            'value' => 'nilai',
            'label' => 'label',
            'is_active' => 'status aktif',
        ];
    }

    public function messages(): array
    {
        return [
            'min_quantity.unique' => 'Aturan diskon dengan minimal kuantitas tersebut sudah ada untuk addon ini.',
            'value.max' => 'Nilai diskon persen maksimal 100.',
        ];
    }
}
