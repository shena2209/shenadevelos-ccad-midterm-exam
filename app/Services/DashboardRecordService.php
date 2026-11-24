<?php

namespace App\Services;

use App\Models\Section;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DashboardRecordService
{
    public function __construct(private readonly StudentGradeService $studentGradeService) {}

    public function createStudent(array $data): Student
    {
        $data['name'] = $this->sanitizeString($data['name'] ?? '');
        $data['lrn'] = $this->sanitizeString($data['lrn'] ?? '');
        $data['grade_level'] = $this->sanitizeString($data['grade_level'] ?? '');
        $data['section_id'] = is_string($data['section_id'] ?? null)
            ? trim($data['section_id'])
            : ($data['section_id'] ?? null);
        $data = $this->validate($data, [
            'name' => ['required', 'string', 'max:150'],
            'lrn' => ['required', 'digits:12', Rule::unique($this->schoolTable('students'), 'lrn')],
            'grade_level' => ['required', Rule::in(array_map(fn (int $level) => "Grade {$level}", range(1, 12)))],
            'section_id' => ['required', 'integer', Rule::exists($this->schoolTable('sections'), 'id')],
        ]);

        return DB::transaction(fn () => Student::create($data));
    }

    public function createTeacher(array $data): Teacher
    {
        $data['name'] = $this->sanitizeString($data['name'] ?? '');
        $data = $this->validate($data, [
            'name' => ['required', 'string', 'max:150'],
        ]);

        return DB::transaction(fn () => Teacher::create($data));
    }

    public function createSubject(array $data): Subject
    {
        $data['name'] = $this->sanitizeString($data['name'] ?? '');
        $data['code'] = $this->sanitizeString($data['code'] ?? '');
        $data['teacher_id'] = $data['teacher_id'] ?: null;
        $data['teacher_id'] = is_string($data['teacher_id'])
            ? trim($data['teacher_id'])
            : $data['teacher_id'];
        $data['teacher_id'] = $data['teacher_id'] === '' ? null : $data['teacher_id'];
        $data = $this->validate($data, [
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:20', Rule::unique($this->schoolTable('subjects'), 'subject_code')],
            'teacher_id' => ['nullable', 'integer', Rule::exists($this->schoolTable('teachers'), 'id')],
        ]);

        return DB::transaction(fn () => Subject::create([
            'subject_name' => $data['name'],
            'subject_code' => $data['code'],
            'teacher_id' => $data['teacher_id'] ?: null,
        ]));
    }

    public function createSection(array $data): Section
    {
        $data['name'] = $this->sanitizeString($data['name'] ?? '');
        $data = $this->validate($data, [
            'name' => ['required', 'string', 'max:100', Rule::unique($this->schoolTable('sections'), 'name')],
        ]);

        return DB::transaction(fn () => Section::create($data));
    }

    public function createGrade(array $data): StudentGrade
    {
        $data = $this->sanitizeGrades($data);
        $data = $this->validateGrade($data);
        return DB::transaction(fn () => StudentGrade::create([
            ...$data,
            ...$this->studentGradeService->gradeAttributes($data),
        ]));
    }

    public function updateGrade(int $id, array $data): StudentGrade
    {
        $data = $this->sanitizeGrades($data);
        $data = $this->validateGrade($data, $id);
        return DB::transaction(function () use ($id, $data) {
            $existingGrade = StudentGrade::query()->findOrFail($id);
            $grades = [];

            foreach (['prelim_grade', 'midterm_grade', 'final_grade'] as $field) {
                $grades[$field] = array_key_exists($field, $data) ? $data[$field] : $existingGrade->{$field};
            }

            $existingGrade->update([
                ...$data,
                ...$this->studentGradeService->gradeAttributes($grades),
            ]);

            return $existingGrade->fresh();
        });
    }

    public function deleteGrade(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $grade = StudentGrade::query()->findOrFail($id);

            return (bool) $grade->delete();
        });
    }

    private function validateGrade(array $data, ?int $ignoreGradeId = null): array
    {
        $studentRule = Rule::unique($this->schoolTable('student_grades'), 'student_id')
            ->where(fn ($query) => $query->where('subject_id', $data['subject_id'] ?? null));

        if ($ignoreGradeId !== null) {
            $studentRule->ignore($ignoreGradeId);
        }

        return $this->validate($data, [
            'student_id' => [
                'required',
                'integer',
                Rule::exists($this->schoolTable('students'), 'id'),
                $studentRule,
            ],
            'subject_id' => ['required', 'integer', Rule::exists($this->schoolTable('subjects'), 'id')],
            'prelim_grade' => ['nullable', 'numeric', 'between:0,100'],
            'midterm_grade' => ['nullable', 'numeric', 'between:0,100'],
            'final_grade' => ['nullable', 'numeric', 'between:0,100'],
        ]);
    }

    private function sanitizeGrades(array $data): array
    {
        foreach (['student_id', 'subject_id'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim($data[$field]);
            }
        }

        foreach (['prelim_grade', 'midterm_grade', 'final_grade'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null && $data[$field] !== '') {
                $data[$field] = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
            } elseif (array_key_exists($field, $data) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }

    private function validate(array $data, array $rules): array
    {
        return Validator::make($data, $rules)->validate();
    }

    private function sanitizeString(mixed $value): mixed
    {
        return is_string($value) ? trim(strip_tags($value)) : $value;
    }

    private function schoolTable(string $table): string
    {
        return config('database.school_connection').'.'.$table;
    }

}
