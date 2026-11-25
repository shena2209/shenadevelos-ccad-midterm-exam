<?php

namespace App\Services;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DashboardRecordService
{
    public function __construct(private readonly StudentGradeService $studentGradeService) {}

    public function dashboardData(): array
    {
        $school = $this->schoolConnection();
        $gradeStats = $school->table('student_grades')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN pass_fail_status = 'Passed' THEN 1 ELSE 0 END) as passed")
            ->selectRaw("SUM(CASE WHEN pass_fail_status = 'Failed' THEN 1 ELSE 0 END) as failed")
            ->selectRaw('SUM(CASE WHEN pass_fail_status IS NULL THEN 1 ELSE 0 END) as pending')
            ->first();
        $passedCount = (int) $gradeStats->passed;
        $failedCount = (int) $gradeStats->failed;

        $recentGrades = $school->table('student_grades')
            ->join('students', 'student_grades.student_id', '=', 'students.id')
            ->join('subjects', 'student_grades.subject_id', '=', 'subjects.id')
            ->join('sections', 'students.section_id', '=', 'sections.id')
            ->select([
                'student_grades.id',
                'student_grades.student_id',
                'student_grades.subject_id',
                'student_grades.prelim_grade',
                'student_grades.midterm_grade',
                'student_grades.final_grade',
                'students.name as student_name',
                'students.lrn',
                'subjects.subject_name as subject_name',
                'subjects.subject_code as subject_code',
                'students.grade_level',
                'sections.name as section_name',
                'student_grades.pass_fail_status',
            ])
            ->selectRaw('COALESCE(student_grades.computed_gpa, (student_grades.prelim_grade + student_grades.midterm_grade + student_grades.final_grade) / 3) as average_grade')
            ->orderByDesc('student_grades.updated_at')
            ->limit(8)
            ->get();

        $pendingGrades = $school->table('student_grades')
            ->join('students', 'student_grades.student_id', '=', 'students.id')
            ->join('subjects', 'student_grades.subject_id', '=', 'subjects.id')
            ->join('sections', 'students.section_id', '=', 'sections.id')
            ->whereNull('student_grades.pass_fail_status')
            ->select([
                'student_grades.id',
                'student_grades.prelim_grade',
                'student_grades.midterm_grade',
                'student_grades.final_grade',
                'students.name as student_name',
                'students.lrn',
                'students.grade_level',
                'subjects.subject_name as subject_name',
                'subjects.subject_code as subject_code',
                'sections.name as section_name',
            ])
            ->orderByDesc('student_grades.updated_at')
            ->get();

        $gradeOutcomeRecords = $school->table('student_grades')
            ->join('students', 'student_grades.student_id', '=', 'students.id')
            ->join('subjects', 'student_grades.subject_id', '=', 'subjects.id')
            ->join('sections', 'students.section_id', '=', 'sections.id')
            ->whereIn('student_grades.pass_fail_status', ['Passed', 'Failed'])
            ->select([
                'student_grades.id',
                'student_grades.prelim_grade',
                'student_grades.midterm_grade',
                'student_grades.final_grade',
                'student_grades.pass_fail_status',
                'students.name as student_name',
                'students.lrn',
                'students.grade_level',
                'subjects.subject_name as subject_name',
                'subjects.subject_code as subject_code',
                'sections.name as section_name',
            ])
            ->selectRaw('COALESCE(student_grades.computed_gpa, (student_grades.prelim_grade + student_grades.midterm_grade + student_grades.final_grade) / 3) as average_grade')
            ->orderByDesc('student_grades.updated_at')
            ->get();

        $subjectPerformance = $school->table('student_grades')
            ->join('subjects', 'student_grades.subject_id', '=', 'subjects.id')
            ->select('subjects.subject_name as name', 'subjects.subject_code as code')
            ->selectRaw('(SUM(COALESCE(student_grades.prelim_grade, 0) + COALESCE(student_grades.midterm_grade, 0) + COALESCE(student_grades.final_grade, 0)) * 1.0) / NULLIF(COUNT(student_grades.prelim_grade) + COUNT(student_grades.midterm_grade) + COUNT(student_grades.final_grade), 0) as average_grade')
            ->selectRaw('COUNT(student_grades.id) as grade_count')
            ->selectRaw('COUNT(student_grades.prelim_grade) + COUNT(student_grades.midterm_grade) + COUNT(student_grades.final_grade) as assessment_count')
            ->groupBy('subjects.id', 'subjects.subject_name', 'subjects.subject_code')
            ->orderByDesc('average_grade')
            ->limit(5)
            ->get();

        return [
            'stats' => [
                'students' => $school->table('students')->count(),
                'teachers' => $school->table('teachers')->count(),
                'subjects' => $school->table('subjects')->count(),
                'sections' => $school->table('sections')->count(),
                'grades' => (int) $gradeStats->total,
                'passed' => $passedCount,
                'failed' => $failedCount,
                'pending' => (int) $gradeStats->pending,
                'passRate' => $passedCount + $failedCount > 0
                    ? round(($passedCount / ($passedCount + $failedCount)) * 100)
                    : 0,
            ],
            'recentGrades' => $recentGrades,
            'pendingGrades' => $pendingGrades,
            'gradeOutcomeRecords' => $gradeOutcomeRecords,
            'subjectPerformance' => $subjectPerformance,
            'options' => [
                'students' => $school->table('students')
                    ->join('sections', 'students.section_id', '=', 'sections.id')
                    ->orderBy('students.name')
                    ->get([
                        'students.id',
                        'students.name',
                        'students.lrn',
                        'students.grade_level',
                        'students.section_id',
                        'sections.name as section_name',
                    ]),
                'teachers' => $school->table('teachers')->orderBy('name')->get(['id', 'name']),
                'subjects' => $school->table('subjects')
                    ->leftJoin('teachers', 'subjects.teacher_id', '=', 'teachers.id')
                    ->orderBy('subjects.subject_name')
                    ->get([
                        'subjects.id',
                        'subjects.subject_name as name',
                        'subjects.subject_code as code',
                        'subjects.teacher_id',
                        'teachers.name as teacher_name',
                    ]),
                'sections' => $school->table('sections')->orderBy('name')->get(['id', 'name']),
            ],
        ];
    }

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
            'teacher_id' => $data['teacher_id'] ?? null,
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
                'teacher_id' => $data['teacher_id'] ?? null,
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
