<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DashboardRecordController extends Controller
{
    public function storeStudent(Request $request): RedirectResponse
    {
        $school = DB::connection(config('database.school_connection'));
        $gradeLevels = array_map(fn (int $level) => "Grade {$level}", range(1, 12));
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'lrn' => ['required', 'digits:12', Rule::unique('school.students', 'lrn')],
            'grade_level' => ['required', Rule::in($gradeLevels)],
            'section_id' => ['required', Rule::exists('school.sections', 'id')],
        ]);

        $school->table('students')->insert([
            ...$data,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return to_route('dashboard');
    }

    public function storeTeacher(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
        ]);

        DB::connection(config('database.school_connection'))->table('teachers')->insert([
            ...$data,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return to_route('dashboard');
    }

    public function storeSubject(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:20', Rule::unique('school.subjects', 'subject_code')],
            'teacher_id' => ['nullable', Rule::exists('school.teachers', 'id')],
        ]);

        DB::connection(config('database.school_connection'))->table('subjects')->insert([
            'subject_name' => $data['name'],
            'subject_code' => $data['code'],
            'teacher_id' => $data['teacher_id'] ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return to_route('dashboard');
    }

    public function storeSection(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('school.sections', 'name')],
        ]);

        DB::connection(config('database.school_connection'))->table('sections')->insert([
            ...$data,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return to_route('dashboard');
    }

    public function storeGrade(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => [
                'required',
                Rule::exists('school.students', 'id'),
                Rule::unique('school.student_grades', 'student_id')
                    ->where('subject_id', $request->input('subject_id')),
            ],
            'subject_id' => ['required', Rule::exists('school.subjects', 'id')],
            'prelim_grade' => ['required', 'numeric', 'between:0,100'],
            'midterm_grade' => ['required', 'numeric', 'between:0,100'],
            'final_grade' => ['required', 'numeric', 'between:0,100'],
        ], [
            'student_id.unique' => 'A grade record already exists for this student and subject.',
        ]);

        $average = ((float) $data['prelim_grade'] + (float) $data['midterm_grade'] + (float) $data['final_grade']) / 3;

        DB::connection(config('database.school_connection'))->table('student_grades')->insert([
            ...$data,
            'computed_gpa' => round($average, 2),
            'pass_fail_status' => $average < 75 ? 'Failed' : 'Passed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return to_route('dashboard');
    }
}
