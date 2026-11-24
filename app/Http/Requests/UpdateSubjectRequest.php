<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubjectRequest extends FormRequest
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
        $subjectId = $this->route('subject');
        $schoolConnection = config('database.school_connection');

        return [
            'subject_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique("{$schoolConnection}.subjects", 'subject_code')->ignore($subjectId),
            ],
            'subject_name' => ['required', 'string', 'max:255'],
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
            'subject_name.max' => 'The subject name may not exceed 255 characters.',
            'teacher_id.exists' => 'The selected teacher does not exist.',
        ];
    }
}