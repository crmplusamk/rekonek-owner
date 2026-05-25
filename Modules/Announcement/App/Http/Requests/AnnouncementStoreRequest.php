<?php

namespace Modules\Announcement\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Announcement\App\Models\Announcement;

class AnnouncementStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $companyRules = ['nullable', 'array'];

        if ($this->input('target_mode') === 'company') {
            $companyRules = ['required', 'array', 'min:1'];
        }

        return [
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'type' => ['required', Rule::in([
                Announcement::TYPE_INFO,
                Announcement::TYPE_WARNING,
                Announcement::TYPE_SUCCESS,
                Announcement::TYPE_DANGER,
            ])],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after:start_at'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'target_mode' => ['required', Rule::in(['global', 'company'])],
            'company_ids' => $companyRules,
            'company_ids.*' => ['nullable', 'string'],
            'action_label' => ['nullable', 'string', 'max:100'],
            'action_url' => ['nullable', 'url', 'required_with:action_label'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'priority' => $this->input('priority', 0),
            'company_ids' => array_values(array_filter((array) $this->input('company_ids', []))),
        ]);
    }
}
