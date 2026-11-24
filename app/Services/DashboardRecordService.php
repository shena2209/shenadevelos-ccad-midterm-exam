<?php

namespace App\Services;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DashboardRecordService
{
    public function __construct(private readonly StudentGradeService $studentGradeService) {}

    public function createStudent(array $data): void
    {
        $school = $this->schoolConnection();
        $school->transaction(fn () => $school->table('students')->insert([
            ...$data,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function createTeacher(array $data): void
    {
        $school = $this->schoolConnection();
        $school->transaction(fn () => $school->table('teachers')->insert([
            ...$data,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function createSubject(array $data): void
    {
        $school = $this->schoolConnection();
        $school->transaction(fn () => $school->table('subjects')->insert([
            'subject_name' => $data['name'],
            'subject_code' => $data['code'],
            'teacher_id' => $data['teacher_id'] ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function createSection(array $data): void
    {
        $school = $this->schoolConnection();
        $school->transaction(fn () => $school->table('sections')->insert([
            ...$data,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function updateStudent(int $id, array $data): void
    {
        $school = $this->schoolConnection();
        $school->transaction(function () use ($school, $id, $data): void {
            $school->table('students')->where('id', $id)->firstOrFail();
            $school->table('students')->where('id', $id)->update([
                ...$data,
                'updated_at' => now(),
            ]);
        });
    }

    public function updateTeacher(int $id, array $data): void
    {
        $school = $this->schoolConnection();
        $school->transaction(function () use ($school, $id, $data): void {
            $school->table('teachers')->where('id', $id)->firstOrFail();
            $school->table('teachers')->where('id', $id)->update([
                ...$data,
                'updated_at' => now(),
            ]);
        });
    }

    public function updateSubject(int $id, array $data): void
    {
        $school = $this->schoolConnection();
        $school->transaction(function () use ($school, $id, $data): void {
            $school->table('subjects')->where('id', $id)->firstOrFail();
            $school->table('subjects')->where('id', $id)->update([
                'subject_name' => $data['name'],
                'subject_code' => $data['code'],
                'teacher_id' => $data['teacher_id'] ?: null,
                'updated_at' => now(),
            ]);
        });
    }

    public function updateSection(int $id, array $data): void
    {
        $school = $this->schoolConnection();
        $school->transaction(function () use ($school, $id, $data): void {
            $school->table('sections')->where('id', $id)->firstOrFail();
            $school->table('sections')->where('id', $id)->update([
                ...$data,
                'updated_at' => now(),
            ]);
        });
    }

    public function deleteStudent(int $id): void
    {
        $school = $this->schoolConnection();
        $school->transaction(function () use ($school, $id): void {
            $school->table('students')->where('id', $id)->firstOrFail();

            if ($school->table('student_grades')->where('student_id', $id)->exists()
                || $school->table('student_attendances')->where('student_id', $id)->exists()) {
                throw ValidationException::withMessages([
                    'record' => 'This student cannot be deleted while grade or attendance records are linked to them.',
                ]);
            }

            $school->table('students')->where('id', $id)->delete();
        });
    }

    public function deleteTeacher(int $id): void
    {
        $school = $this->schoolConnection();
        $school->transaction(function () use ($school, $id): void {
            $school->table('teachers')->where('id', $id)->firstOrFail();

            if ($school->table('subjects')->where('teacher_id', $id)->exists()) {
                throw ValidationException::withMessages([
                    'record' => 'This teacher cannot be deleted while subjects are assigned to them.',
                ]);
            }

            $school->table('teachers')->where('id', $id)->delete();
        });
    }

    public function deleteSubject(int $id): void
    {
        $school = $this->schoolConnection();
        $school->transaction(function () use ($school, $id): void {
            $school->table('subjects')->where('id', $id)->firstOrFail();

            if ($school->table('student_grades')->where('subject_id', $id)->exists()) {
                throw ValidationException::withMessages([
                    'record' => 'This subject cannot be deleted while grade records are linked to it.',
                ]);
            }

            $school->table('subjects')->where('id', $id)->delete();
        });
    }

    public function deleteSection(int $id): void
    {
        $school = $this->schoolConnection();
        $school->transaction(function () use ($school, $id): void {
            $school->table('sections')->where('id', $id)->firstOrFail();

            if ($school->table('students')->where('section_id', $id)->exists()) {
                throw ValidationException::withMessages([
                    'record' => 'This section cannot be deleted while students are enrolled in it.',
                ]);
            }

            $school->table('sections')->where('id', $id)->delete();
        });
    }

    public function createGrade(array $data): void
    {
        $school = $this->schoolConnection();
        $school->transaction(fn () => $school->table('student_grades')->insert([
            ...$data,
            ...$this->studentGradeService->gradeAttributes($data),
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function updateGrade(int $id, array $data): void
    {
        $school = $this->schoolConnection();
        $school->transaction(function () use ($school, $id, $data): void {
            $existingGrade = $school->table('student_grades')->where('id', $id)->firstOrFail();
            $grades = [];

            foreach (['prelim_grade', 'midterm_grade', 'final_grade'] as $field) {
                $grades[$field] = array_key_exists($field, $data) ? $data[$field] : $existingGrade->{$field};
            }

            $school->table('student_grades')->where('id', $id)->update([
                ...$data,
                ...$this->studentGradeService->gradeAttributes($grades),
                'updated_at' => now(),
            ]);
        });
    }

    public function deleteGrade(int $id): void
    {
        $school = $this->schoolConnection();
        $school->transaction(function () use ($school, $id): void {
            $school->table('student_grades')->where('id', $id)->firstOrFail();
            $school->table('student_grades')->where('id', $id)->delete();
        });
    }

    private function schoolConnection(): Connection
    {
        return DB::connection(config('database.school_connection'));
    }
}
