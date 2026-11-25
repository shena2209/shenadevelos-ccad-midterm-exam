<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $schoolConnection = config('database.school_connection');

        return [
            'student_id' => ['nullable', Rule::exists("{$schoolConnection}.students", 'id')],
            'student_lrn' => ['nullable', 'digits:12', 'required_without:student_id'],
            'student_name' => ['nullable', 'string', 'max:150'],
            'section_id' => ['nullable', Rule::exists("{$schoolConnection}.sections", 'id')],
            'subject_id' => ['required', Rule::exists("{$schoolConnection}.subjects", 'id')],
            'prelim_grade' => ['sometimes', 'nullable', 'numeric', 'between:0,100'],
            'midterm_grade' => ['sometimes', 'nullable', 'numeric', 'between:0,100'],
            'final_grade' => ['sometimes', 'nullable', 'numeric', 'between:0,100'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.exists' => 'The selected student does not exist.',
            'student_lrn.required_without' => 'A student LRN is required when no student is selected.',
            'student_lrn.digits' => 'The student LRN must be a 12-digit number.',
            'student_name.max' => 'The student name may not exceed 150 characters.',
            'section_id.exists' => 'The selected section does not exist.',
            'subject_id.required' => 'A subject must be selected.',
            'subject_id.exists' => 'The selected subject does not exist.',
            'prelim_grade.numeric' => 'The prelim grade must be a number.',
            'prelim_grade.between' => 'The prelim grade must be between 0 and 100.',
            'midterm_grade.numeric' => 'The midterm grade must be a number.',
            'midterm_grade.between' => 'The midterm grade must be between 0 and 100.',
            'final_grade.numeric' => 'The final grade must be a number.',
            'final_grade.between' => 'The final grade must be between 0 and 100.',
        ];
    }
}
