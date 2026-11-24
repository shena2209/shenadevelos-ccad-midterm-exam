<?php

namespace App\Services;

use App\Models\Section;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\Subject;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Illuminate\Validation\Rule;

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
        $data = $this->validate($this->sanitize($data));

        return DB::transaction(function () use ($data) {
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
        $data = $this->validate($this->sanitize($data), $id);

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

    private function sanitize(array $data): array
    {
        foreach (['student_id', 'subject_id', 'section_id'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim($data[$field]);
            }
        }

        foreach (['student_lrn', 'student_name', 'grade_level'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = is_string($data[$field])
                    ? $this->sanitizeString($data[$field])
                    : $data[$field];
            }
        }

        foreach (['prelim_grade', 'midterm_grade', 'final_grade'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim($data[$field]);
            }
        }

        return $data;
    }

    private function validate(array $data, ?int $ignoreGradeId = null): array
    {
        $studentRule = Rule::unique($this->schoolTable('student_grades'), 'student_id')
            ->where(fn ($query) => $query->where('subject_id', $data['subject_id'] ?? null));

        if ($ignoreGradeId !== null) {
            $studentRule->ignore($ignoreGradeId);
        }

        return Validator::make($data, [
            'student_id' => [
                'nullable',
                'integer',
                Rule::exists($this->schoolTable('students'), 'id'),
                $studentRule,
            ],
            'student_lrn' => ['nullable', 'digits:12', 'required_without:student_id'],
            'student_name' => ['nullable', 'string', 'max:150'],
            'grade_level' => ['nullable', 'string', 'max:20'],
            'section_id' => ['nullable', 'integer', Rule::exists($this->schoolTable('sections'), 'id')],
            'subject_id' => ['required', 'integer', Rule::exists($this->schoolTable('subjects'), 'id')],
            'prelim_grade' => ['nullable', 'numeric', 'between:0,100'],
            'midterm_grade' => ['nullable', 'numeric', 'between:0,100'],
            'final_grade' => ['nullable', 'numeric', 'between:0,100'],
        ])->validate();
    }

    private function schoolTable(string $table): string
    {
        return config('database.school_connection').'.'.$table;
    }

}
