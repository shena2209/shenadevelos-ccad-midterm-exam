<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDashboardStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $schoolConnection = config('database.school_connection');
        $student = $this->route('student');

        return [
            'name' => ['required', 'string', 'max:150'],
            'lrn' => ['required', 'digits:12', Rule::unique("{$schoolConnection}.students", 'lrn')->ignore($student)],
            'grade_level' => ['required', Rule::in($this->gradeLevels())],
            'section_id' => ['required', Rule::exists("{$schoolConnection}.sections", 'id')],
        ];
    }

    private function gradeLevels(): array
    {
        return array_map(fn (int $level) => "Grade {$level}", range(1, 12));
    }
}
