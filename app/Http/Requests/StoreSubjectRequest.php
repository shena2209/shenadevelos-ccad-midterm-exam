<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name') && ! $this->has('subject_name')) {
            $this->merge(['subject_name' => $this->input('name')]);
        }

        if ($this->has('code') && ! $this->has('subject_code')) {
            $this->merge(['subject_code' => $this->input('code')]);
        }
    }

    public function rules(): array
    {
        $schoolConnection = config('database.school_connection');

        return [
            'subject_code' => ['required', 'string', 'max:20', Rule::unique("{$schoolConnection}.subjects", 'subject_code')],
            'subject_name' => ['required', 'string', 'max:150'],
            'teacher_id' => ['nullable', Rule::exists("{$schoolConnection}.teachers", 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'subject_code.required' => 'The subject code is required.',
            'subject_code.unique' => 'This subject code already exists.',
            'subject_code.max' => 'The subject code may not exceed 20 characters.',
            'subject_name.required' => 'The subject name is required.',
            'subject_name.max' => 'The subject name may not exceed 150 characters.',
            'teacher_id.exists' => 'The selected teacher does not exist.',
        ];
    }
}
