<?php

namespace App\Http\Controllers;

use App\Services\DashboardRecordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DashboardRecordController extends Controller
{
    public function __construct(private readonly DashboardRecordService $dashboardRecordService) {}

    public function storeStudent(Request $request): RedirectResponse
    {
        $gradeLevels = array_map(fn (int $level) => "Grade {$level}", range(1, 12));
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'lrn' => ['required', 'digits:12', Rule::unique('school.students', 'lrn')], // this must be done in request
            'grade_level' => ['required', Rule::in($gradeLevels)],
            'section_id' => ['required', Rule::exists('school.sections', 'id')],
        ]);

        $this->dashboardRecordService->createStudent($data);

        return to_route('dashboard');
    }

    public function storeTeacher(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
        ]);

        $this->dashboardRecordService->createTeacher($data);

        return to_route('dashboard');
    }

    public function storeSubject(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:20', Rule::unique('school.subjects', 'subject_code')], // this must be done in request
            'teacher_id' => ['nullable', Rule::exists('school.teachers', 'id')],
        ]);

        $this->dashboardRecordService->createSubject($data);

        return to_route('dashboard');
    }

    public function storeSection(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('school.sections', 'name')], // this must be done in request
        ]);

        $this->dashboardRecordService->createSection($data);

        return to_route('dashboard');
    }

    public function updateStudent(Request $request, int $student): RedirectResponse
    {
        $gradeLevels = array_map(fn (int $level) => "Grade {$level}", range(1, 12));
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'lrn' => ['required', 'digits:12', Rule::unique('school.students', 'lrn')->ignore($student)],
            'grade_level' => ['required', Rule::in($gradeLevels)],
            'section_id' => ['required', Rule::exists('school.sections', 'id')],
        ]);

        $this->dashboardRecordService->updateStudent($student, $data);

        return back();
    }

    public function updateTeacher(Request $request, int $teacher): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
        ]);

        $this->dashboardRecordService->updateTeacher($teacher, $data);

        return back();
    }

    public function updateSubject(Request $request, int $subject): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:20', Rule::unique('school.subjects', 'subject_code')->ignore($subject)],
            'teacher_id' => ['nullable', Rule::exists('school.teachers', 'id')],
        ]);

        $this->dashboardRecordService->updateSubject($subject, $data);

        return back();
    }

    public function updateSection(Request $request, int $section): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('school.sections', 'name')->ignore($section)],
        ]);

        $this->dashboardRecordService->updateSection($section, $data);

        return back();
    }

    public function destroyStudent(int $student): RedirectResponse
    {
        $this->dashboardRecordService->deleteStudent($student);

        return back();
    }

    public function destroyTeacher(int $teacher): RedirectResponse
    {
        $this->dashboardRecordService->deleteTeacher($teacher);

        return back();
    }

    public function destroySubject(int $subject): RedirectResponse
    {
        $this->dashboardRecordService->deleteSubject($subject);

        return back();
    }

    public function destroySection(int $section): RedirectResponse
    {
        $this->dashboardRecordService->deleteSection($section);

        return back();
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
            'prelim_grade' => ['nullable', 'numeric', 'between:0,100'], // this must be done in request
            'midterm_grade' => ['nullable', 'numeric', 'between:0,100'],
            'final_grade' => ['nullable', 'numeric', 'between:0,100'],
        ], [
            'student_id.unique' => 'A grade record already exists for this student and subject.',
        ]);

        $this->dashboardRecordService->createGrade($data);

        return to_route('dashboard');
    }

    public function updateGrade(Request $request, int $grade): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => [
                'required',
                Rule::exists('school.students', 'id'),
                Rule::unique('school.student_grades', 'student_id')
                    ->where('subject_id', $request->input('subject_id'))
                    ->ignore($grade),
            ],
            'subject_id' => ['required', Rule::exists('school.subjects', 'id')],
            'prelim_grade' => ['sometimes', 'nullable', 'numeric', 'between:0,100'], // this must be done in request
            'midterm_grade' => ['sometimes', 'nullable', 'numeric', 'between:0,100'],
            'final_grade' => ['sometimes', 'nullable', 'numeric', 'between:0,100'],
        ], [
            'student_id.unique' => 'A grade record already exists for this student and subject.',
        ]);

        $this->dashboardRecordService->updateGrade($grade, $data);

        return to_route('dashboard');
    }

    public function destroyGrade(int $grade): RedirectResponse
    {
        $this->dashboardRecordService->deleteGrade($grade);

        return to_route('dashboard');
    }
}
