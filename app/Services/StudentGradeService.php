<?php

namespace App\Services;

use App\Models\Section;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\Subject;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StudentGradeService
{
    public function paginate(): LengthAwarePaginator
    {
        return StudentGrade::query()
            ->with(['student.section', 'subject.teacher'])
            ->latest()
            ->paginate(15);
    }

    public function dependencies(): array
    {
        return [
            'subjects' => Subject::query()->with('teacher:id,name')->orderBy('subject_name')->get(),
            'sections' => Section::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function create(array $data): StudentGrade
    {
        return $this->schoolConnection()->transaction(function () use ($data) {
            $student = $this->resolveStudent($data);
            $grade = $student->grades()->create([
                'subject_id' => $data['subject_id'],
                ...$this->gradeAttributes($data),
            ]);

            return $grade->load(['student.section', 'subject.teacher']);
        });
    }

    public function update(int $id, array $data): StudentGrade
    {
        return $this->schoolConnection()->transaction(function () use ($id, $data) {
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

    private function resolveStudent(array $data, ?int $fallbackStudentId = null): Student
    {
        if (! empty($data['student_id'])) {
            return Student::findOrFail((int) $data['student_id']);
        }

        if (! empty($data['student_lrn'])) {
            $lrn = str_pad((string) $data['student_lrn'], 12, '0', STR_PAD_LEFT);

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

    private function sanitizeString(string $value): string
    {
        return trim(strip_tags($value));
    }

    private function schoolConnection(): \Illuminate\Database\Connection
    {
        return DB::connection(config('database.school_connection'));
    }
}
