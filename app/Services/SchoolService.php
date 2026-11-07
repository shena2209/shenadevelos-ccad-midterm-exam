<?php

namespace App\Services;

use App\Models\Section;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;

class SchoolService
{
    public function createSubject(array $data): Subject
    {
        return DB::transaction(fn () => Subject::create($this->sanitizeSubject($data)));
    }

    public function updateSubject(int $id, array $data): Subject
    {
        return DB::transaction(function () use ($id, $data) {
            $subject = Subject::findOrFail($id);
            $subject->update($this->sanitizeSubject($data));

            return $subject->load('teacher');
        });
    }

    public function createStudentGrade(array $data): StudentGrade
    {
        return DB::transaction(function () use ($data) {
            $student = Student::firstOrNew([
                'student_lrn' => $this->formatLrn($data['student_lrn']),
            ]);
            $student->fill([
                'student_name' => $this->sanitizeString($data['student_name']),
                'section_id' => $data['section_id'],
            ])->save();

            return $student->studentGrades()->create($this->gradeAttributes($data));
        });
    }

    public function updateStudentGrade(int $id, array $data): StudentGrade
    {
        return DB::transaction(function () use ($id, $data) {
            $studentGrade = StudentGrade::findOrFail($id);
            $student = Student::firstOrNew([
                'student_lrn' => $this->formatLrn($data['student_lrn']),
            ]);
            $student->fill([
                'student_name' => $this->sanitizeString($data['student_name']),
                'section_id' => $data['section_id'],
            ])->save();

            $studentGrade->update([
                ...$this->gradeAttributes($data),
                'student_id' => $student->id,
            ]);

            return $studentGrade->load(['student.section', 'subject.teacher']);
        });
    }

    public function subjectDependencies(): array
    {
        return ['teachers' => Teacher::query()->orderBy('full_name')->get(['id', 'full_name'])];
    }

    public function studentGradeDependencies(): array
    {
        return [
            'subjects' => Subject::query()->with('teacher:id,full_name')->orderBy('subject_name')->get(),
            'sections' => Section::query()->orderBy('grade_level')->orderBy('section_name')->get(),
        ];
    }

    private function sanitizeSubject(array $data): array
    {
        return [
            'subject_code' => $this->sanitizeString($data['subject_code']),
            'subject_name' => $this->sanitizeString($data['subject_name']),
            'teacher_id' => $data['teacher_id'],
        ];
    }

    private function gradeAttributes(array $data): array
    {
        $prelim = (float) $data['prelim_grade'];
        $midterm = (float) $data['midterm_grade'];
        $final = (float) $data['final_grade'];
        $average = ($prelim + $midterm + $final) / 3;

        return [
            'subject_id' => $data['subject_id'],
            'prelim_grade' => $prelim,
            'midterm_grade' => $midterm,
            'final_grade' => $final,
            'computed_gpa' => round($average, 2),
            'pass_fail_status' => $average < 75.0 ? 'Failed' : 'Passed',
        ];
    }

    private function formatLrn(string $lrn): string
    {
        return str_pad($lrn, 12, '0', STR_PAD_LEFT);
    }

    private function sanitizeString(string $value): string
    {
        return trim(strip_tags($value));
    }
}
