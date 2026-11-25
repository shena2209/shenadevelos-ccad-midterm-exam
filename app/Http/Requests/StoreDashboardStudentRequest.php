<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDashboardStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $schoolConnection = config('database.school_connection');

        return [
            'name' => ['required', 'string', 'max:150'],
            'lrn' => ['required', 'digits:12', Rule::unique("{$schoolConnection}.students", 'lrn')],
            'grade_level' => ['required', Rule::in($this->gradeLevels())],
            'section_id' => ['required', Rule::exists("{$schoolConnection}.sections", 'id')],
        ];
    }

    private function gradeLevels(): array
    {
        return array_map(fn (int $level) => "Grade {$level}", range(1, 12));
    }
}
