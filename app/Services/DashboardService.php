<?php

namespace App\Services;

use App\Models\Section;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\Teacher;

class DashboardService
{
    public function data(): array
    {
        $gradeStats = StudentGrade::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN pass_fail_status = 'Passed' THEN 1 ELSE 0 END) as passed")
            ->selectRaw("SUM(CASE WHEN pass_fail_status = 'Failed' THEN 1 ELSE 0 END) as failed")
            ->selectRaw('SUM(CASE WHEN pass_fail_status IS NULL THEN 1 ELSE 0 END) as pending')
            ->first();
        $gradeCount = (int) $gradeStats->total;
        $passedCount = (int) $gradeStats->passed;
        $failedCount = (int) $gradeStats->failed;
        $pendingCount = (int) $gradeStats->pending;

        $recentGrades = StudentGrade::query()
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

        $pendingGrades = StudentGrade::query()
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

        $gradeOutcomeRecords = StudentGrade::query()
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

        $subjectPerformance = StudentGrade::query()
            ->join('subjects', 'student_grades.subject_id', '=', 'subjects.id')
            ->select('subjects.subject_name as name', 'subjects.subject_code as code')
            ->selectRaw('(SUM(COALESCE(student_grades.prelim_grade, 0) + COALESCE(student_grades.midterm_grade, 0) + COALESCE(student_grades.final_grade, 0)) * 1.0) / NULLIF(COUNT(student_grades.prelim_grade) + COUNT(student_grades.midterm_grade) + COUNT(student_grades.final_grade), 0) as average_grade')
            ->selectRaw('COUNT(student_grades.id) as grade_count')
            ->selectRaw('COUNT(student_grades.prelim_grade) + COUNT(student_grades.midterm_grade) + COUNT(student_grades.final_grade) as assessment_count')
            ->groupBy('subjects.id', 'subjects.subject_name', 'subjects.subject_code')
            ->orderByDesc('average_grade')
            ->limit(5)
            ->get();

        $gradedCount = $passedCount + $failedCount;

        return [
            'stats' => [
                'students' => Student::query()->count(),
                'teachers' => Teacher::query()->count(),
                'subjects' => Subject::query()->count(),
                'sections' => Section::query()->count(),
                'grades' => $gradeCount,
                'passed' => $passedCount,
                'failed' => $failedCount,
                'pending' => $pendingCount,
                'passRate' => $gradedCount > 0 ? round(($passedCount / $gradedCount) * 100) : 0,
            ],
            'recentGrades' => $recentGrades,
            'pendingGrades' => $pendingGrades,
            'gradeOutcomeRecords' => $gradeOutcomeRecords,
            'subjectPerformance' => $subjectPerformance,
            'options' => [
                'students' => Student::query()
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
                'teachers' => Teacher::query()->orderBy('name')->get(['id', 'name']),
                'subjects' => Subject::query()
                    ->leftJoin('teachers', 'subjects.teacher_id', '=', 'teachers.id')
                    ->orderBy('subjects.subject_name')
                    ->get([
                        'subjects.id',
                        'subjects.subject_name as name',
                        'subjects.subject_code as code',
                        'subjects.teacher_id',
                        'teachers.name as teacher_name',
                    ]),
                'sections' => Section::query()->orderBy('name')->get(['id', 'name']),
            ],
        ];
    }
}
