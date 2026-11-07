<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'unique:subjects,code'],
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'The subject code is required.',
            'code.unique'   => 'This subject code already exists.',
            'code.max'      => 'The subject code may not exceed 20 characters.',
            'name.required' => 'The subject name is required.',
            'name.max'      => 'The subject name may not exceed 255 characters.',
        ];
    }
}