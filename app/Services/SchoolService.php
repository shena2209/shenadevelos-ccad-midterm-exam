<?php

namespace App\Services;

use App\Models\Section;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

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
            $student = $this->resolveStudent($data);

            $grade = $student->grades()->create([
                'subject_id' => $data['subject_id'],
                ...$this->gradeAttributes($data),
            ]);

            return $grade->load(['student.section', 'subject.teacher']);
        });
    }

    public function updateStudentGrade(int $id, array $data): StudentGrade
    {
        return DB::transaction(function () use ($id, $data) {
            $studentGrade = StudentGrade::findOrFail($id);
            $student = $this->resolveStudent($data, $studentGrade->student_id);
            $gradeData = [];

            foreach (['prelim_grade', 'midterm_grade', 'final_grade'] as $field) {
                $gradeData[$field] = array_key_exists($field, $data) ? $data[$field] : $studentGrade->{$field};
            }

            $studentGrade->update([
                'student_id' => $student->id,
                'subject_id' => $data['subject_id'] ?? $studentGrade->subject_id,
                ...$this->gradeAttributes($gradeData),
            ]);

            return $studentGrade->fresh()->load(['student.section', 'subject.teacher']);
        });
    }

    public function subjectDependencies(): array
    {
        return ['teachers' => Teacher::query()->orderBy('name')->get(['id', 'name'])];
    }

    public function studentGradeDependencies(): array
    {
        return [
            'subjects' => Subject::query()->with('teacher:id,name')->orderBy('subject_name')->get(),
            'sections' => Section::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function sanitizeSubject(array $data): array
    {
        return [
            'subject_code' => $this->sanitizeString($data['subject_code'] ?? $data['code'] ?? ''),
            'subject_name' => $this->sanitizeString($data['subject_name'] ?? $data['name'] ?? ''),
            'teacher_id' => $data['teacher_id'] ?? null,
        ];
    }

    private function resolveStudent(array $data, ?int $fallbackStudentId = null): Student
    {
        if (! empty($data['student_id'])) {
            return Student::findOrFail((int) $data['student_id']);
        }

        if (! empty($data['student_lrn'])) {
            $lrn = $this->formatLrn((string) $data['student_lrn']);

            return Student::firstOrCreate(
                ['lrn' => $lrn],
                [
                    'name' => $this->sanitizeString($data['student_name'] ?? ''),
                    'grade_level' => $data['grade_level'] ?? null,
                    'section_id' => $data['section_id'] ?? null,
                ]
            );
        }

        if ($fallbackStudentId !== null) {
            return Student::findOrFail($fallbackStudentId);
        }

        throw new InvalidArgumentException('A student record is required to create a grade.');
    }

    public function gradeAttributes(array $data): array
    {
        $grades = [];

        foreach (['prelim_grade', 'midterm_grade', 'final_grade'] as $field) {
            $grades[$field] = isset($data[$field]) ? (float) $data[$field] : null;
        }

        $isComplete = count(array_filter($grades, fn (?float $grade) => $grade !== null)) === count($grades);
        $average = $isComplete ? array_sum($grades) / count($grades) : null;

        return [
            ...$grades,
            'computed_gpa' => $average === null ? null : round($average, 2),
            'pass_fail_status' => $average === null ? null : ($average < 75.0 ? 'Failed' : 'Passed'),
        ];
    }

    private function formatLrn(string $lrn): string
    {
        return str_pad((string) $lrn, 12, '0', STR_PAD_LEFT);
    }

    private function sanitizeString(string $value): string
    {
        return trim(strip_tags($value));
    }
}
