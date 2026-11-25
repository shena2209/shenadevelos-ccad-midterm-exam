<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDashboardSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('subject_name') && ! $this->has('name')) {
            $this->merge(['name' => $this->input('subject_name')]);
        }

        if ($this->has('subject_code') && ! $this->has('code')) {
            $this->merge(['code' => $this->input('subject_code')]);
        }
    }

    public function rules(): array
    {
        $schoolConnection = config('database.school_connection');

        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique("{$schoolConnection}.subjects", 'subject_code')->ignore($this->route('subject')),
            ],
            'teacher_id' => ['nullable', Rule::exists("{$schoolConnection}.teachers", 'id')],
        ];
    }
}
