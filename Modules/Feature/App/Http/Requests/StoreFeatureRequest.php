<?php

namespace Modules\Feature\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent' => ['required', 'exists:features,id'],
            'name' => ['required', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'parent' => 'kategori',
            'name' => 'nama fitur',
        ];
    }
}
