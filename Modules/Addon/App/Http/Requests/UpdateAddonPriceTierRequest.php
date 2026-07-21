<?php

namespace Modules\Addon\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Addon\App\Models\AddonPriceTier;

class UpdateAddonPriceTierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * addon_id tier tidak boleh diubah lewat form edit — diambil dari data tier itu sendiri
     * (route wildcard {id}), dipakai hanya untuk scoping unique check min_quantity.
     */
    protected function prepareForValidation(): void
    {
        $tier = AddonPriceTier::find($this->route('id'));

        $this->merge([
            'addon_id' => $tier?->addon_id,
        ]);
    }

    public function rules(): array
    {
        $tierId = $this->route('id');

        return [
            'min_quantity' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('addon_price_tiers')
                    ->where(fn ($query) => $query->where('addon_id', $this->addon_id))
                    ->ignore($tierId),
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
