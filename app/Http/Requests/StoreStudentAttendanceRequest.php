<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $schoolConnection = config('database.school_connection');

        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'integer', 'distinct', Rule::exists("{$schoolConnection}.students", 'id')],
            'records.*.status' => ['required', Rule::in(['Present', 'Absent', 'Late', 'Excused'])],
            'records.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
