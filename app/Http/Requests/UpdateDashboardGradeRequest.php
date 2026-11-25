<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDashboardGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $schoolConnection = config('database.school_connection');

        return [
            'student_id' => [
                'required',
                Rule::exists("{$schoolConnection}.students", 'id'),
                Rule::unique("{$schoolConnection}.student_grades", 'student_id')
                    ->where('subject_id', $this->input('subject_id'))
                    ->ignore($this->route('grade')),
            ],
            'subject_id' => ['required', Rule::exists("{$schoolConnection}.subjects", 'id')],
            'prelim_grade' => ['sometimes', 'nullable', 'numeric', 'between:0,100'],
            'midterm_grade' => ['sometimes', 'nullable', 'numeric', 'between:0,100'],
            'final_grade' => ['sometimes', 'nullable', 'numeric', 'between:0,100'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.unique' => 'A grade record already exists for this student and subject.',
        ];
    }
}
