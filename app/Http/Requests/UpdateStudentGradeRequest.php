<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id'    => ['required', 'exists:students,id'],
            'subject_id'    => ['required', 'exists:subjects,id'],
            'teacher_id'    => ['required', 'exists:teachers,id'],
            'section_id'    => ['required', 'exists:sections,id'],
            'prelim_grade'  => ['required', 'numeric', 'between:0,100'],
            'midterm_grade' => ['required', 'numeric', 'between:0,100'],
            'final_grade'   => ['required', 'numeric', 'between:0,100'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required'    => 'A student must be selected.',
            'student_id.exists'      => 'The selected student does not exist.',
            'subject_id.required'    => 'A subject must be selected.',
            'subject_id.exists'      => 'The selected subject does not exist.',
            'teacher_id.required'    => 'A teacher must be selected.',
            'teacher_id.exists'      => 'The selected teacher does not exist.',
            'section_id.required'    => 'A section must be selected.',
            'section_id.exists'      => 'The selected section does not exist.',
            'prelim_grade.required'  => 'The prelim grade is required.',
            'prelim_grade.numeric'   => 'The prelim grade must be a number.',
            'prelim_grade.between'   => 'The prelim grade must be between 0 and 100.',
            'midterm_grade.required' => 'The midterm grade is required.',
            'midterm_grade.numeric'  => 'The midterm grade must be a number.',
            'midterm_grade.between'  => 'The midterm grade must be between 0 and 100.',
            'final_grade.required'   => 'The final grade is required.',
            'final_grade.numeric'    => 'The final grade must be a number.',
            'final_grade.between'    => 'The final grade must be between 0 and 100.',
        ];
    }
}