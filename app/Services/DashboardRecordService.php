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
use Illuminate\Validation\ValidationException;

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

    public function updateStudent(int $id, array $data): Student
    {
        $data['name'] = $this->sanitizeString($data['name'] ?? '');
        $data['lrn'] = $this->sanitizeString($data['lrn'] ?? '');
        $data['grade_level'] = $this->sanitizeString($data['grade_level'] ?? '');
        $data['section_id'] = is_string($data['section_id'] ?? null)
            ? trim($data['section_id'])
            : ($data['section_id'] ?? null);
        $data = $this->validate($data, [
            'name' => ['required', 'string', 'max:150'],
            'lrn' => [
                'required',
                'digits:12',
                Rule::unique($this->schoolTable('students'), 'lrn')->ignore($id),
            ],
            'grade_level' => ['required', Rule::in(array_map(fn (int $level) => "Grade {$level}", range(1, 12)))],
            'section_id' => ['required', 'integer', Rule::exists($this->schoolTable('sections'), 'id')],
        ]);

        return DB::transaction(function () use ($id, $data) {
            $student = Student::query()->findOrFail($id);
            $student->update($data);

            return $student->fresh();
        });
    }

    public function deleteStudent(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $student = Student::query()->findOrFail($id);
            $school = $student->getConnection();
            $hasGrades = $school->table('student_grades')->where('student_id', $id)->exists();
            $hasAttendance = $school->table('student_attendances')->where('student_id', $id)->exists();

            if ($hasGrades || $hasAttendance) {
                throw ValidationException::withMessages([
                    'student' => 'This student cannot be deleted while grade or attendance records are linked to them.',
                ]);
            }

            return (bool) $student->delete();
        });
    }

    public function createTeacher(array $data): Teacher
    {
        $data['name'] = $this->sanitizeString($data['name'] ?? '');
        $data = $this->validate($data, [
            'name' => ['required', 'string', 'max:150'],
        ]);

        return DB::transaction(fn () => Teacher::on($this->schoolConnectionName())->create($data));
    }

    public function updateTeacher(int $id, array $data): Teacher
    {
        $data['name'] = $this->sanitizeString($data['name'] ?? '');
        $data = $this->validate($data, [
            'name' => ['required', 'string', 'max:150'],
        ]);

        return DB::transaction(function () use ($id, $data) {
            $teacher = Teacher::on($this->schoolConnectionName())->findOrFail($id);
            $teacher->update($data);

            return $teacher->fresh();
        });
    }

    public function deleteTeacher(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $teacher = Teacher::on($this->schoolConnectionName())->findOrFail($id);
            $hasSubjects = $teacher->getConnection()
                ->table('subjects')
                ->where('teacher_id', $id)
                ->exists();

            if ($hasSubjects) {
                throw ValidationException::withMessages([
                    'record' => 'This teacher cannot be deleted while subjects are assigned to them.',
                ]);
            }

            return (bool) $teacher->delete();
        });
    }

    public function createSubject(array $data): Subject
    {
        $data = $this->sanitizeSubject($data);
        $data = $this->validate($data, [
            'subject_name' => ['required', 'string', 'max:150'],
            'subject_code' => ['required', 'string', 'max:20', Rule::unique($this->schoolTable('subjects'), 'subject_code')],
            'teacher_id' => ['nullable', 'integer', Rule::exists($this->schoolTable('teachers'), 'id')],
        ]);

        return DB::transaction(fn () => Subject::on($this->schoolConnectionName())->create($data));
    }

    public function updateSubject(int $id, array $data): Subject
    {
        $data = $this->sanitizeSubject($data);
        $data = $this->validate($data, [
            'subject_name' => ['required', 'string', 'max:150'],
            'subject_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique($this->schoolTable('subjects'), 'subject_code')->ignore($id),
            ],
            'teacher_id' => ['nullable', 'integer', Rule::exists($this->schoolTable('teachers'), 'id')],
        ]);

        return DB::transaction(function () use ($id, $data) {
            $subject = Subject::on($this->schoolConnectionName())->findOrFail($id);
            $subject->update($data);

            return $subject->fresh();
        });
    }

    public function deleteSubject(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $subject = Subject::on($this->schoolConnectionName())->findOrFail($id);
            $hasGrades = $subject->getConnection()
                ->table('student_grades')
                ->where('subject_id', $id)
                ->exists();

            if ($hasGrades) {
                throw ValidationException::withMessages([
                    'record' => 'This subject cannot be deleted while student grade records are linked to it.',
                ]);
            }

            return (bool) $subject->delete();
        });
    }

    public function createSection(array $data): Section
    {
        $data['name'] = $this->sanitizeString($data['name'] ?? '');
        $data = $this->validate($data, [
            'name' => ['required', 'string', 'max:100', Rule::unique($this->schoolTable('sections'), 'name')],
        ]);

        return DB::transaction(fn () => Section::on($this->schoolConnectionName())->create($data));
    }

    public function updateSection(int $id, array $data): Section
    {
        $data['name'] = $this->sanitizeString($data['name'] ?? '');
        $data = $this->validate($data, [
            'name' => ['required', 'string', 'max:100', Rule::unique($this->schoolTable('sections'), 'name')->ignore($id)],
        ]);

        return DB::transaction(function () use ($id, $data) {
            $section = Section::on($this->schoolConnectionName())->findOrFail($id);
            $section->update($data);

            return $section->fresh();
        });
    }

    public function deleteSection(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $section = Section::on($this->schoolConnectionName())->findOrFail($id);
            $hasStudents = $section->getConnection()
                ->table('students')
                ->where('section_id', $id)
                ->exists();

            if ($hasStudents) {
                throw ValidationException::withMessages([
                    'record' => 'This section cannot be deleted while students are enrolled in it.',
                ]);
            }

            return (bool) $section->delete();
        });
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

    private function sanitizeSubject(array $data): array
    {
        $teacherId = $data['teacher_id'] ?? null;
        $teacherId = is_string($teacherId) ? trim($teacherId) : $teacherId;

        return [
            'subject_name' => $this->sanitizeString($data['subject_name'] ?? $data['name'] ?? ''),
            'subject_code' => $this->sanitizeString($data['subject_code'] ?? $data['code'] ?? ''),
            'teacher_id' => $teacherId === '' ? null : $teacherId,
        ];
    }

    private function schoolConnectionName(): string
    {
        return Student::query()->getConnection()->getName();
    }

    private function schoolTable(string $table): string
    {
        return config('database.school_connection').'.'.$table; //comment
    }

}
