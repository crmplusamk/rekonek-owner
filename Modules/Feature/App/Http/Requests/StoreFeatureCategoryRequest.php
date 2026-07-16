<?php

namespace Modules\Feature\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeatureCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama kategori',
        ];
    }
}
