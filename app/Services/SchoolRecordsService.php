<?php

namespace App\Services;

use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SchoolRecordsService
{
    public function students(): Collection
    {
        return $this->schoolConnection()->table('students')
            ->join('sections', 'students.section_id', '=', 'sections.id')
            ->orderBy('students.name')
            ->get([
                'students.id',
                'students.name',
                'students.lrn',
                'students.grade_level',
                'students.section_id',
                'sections.name as section_name',
            ])
            ->map(fn ($student) => [
                'id' => $student->id,
                'name' => $student->name,
                'detail' => "LRN {$student->lrn} · {$student->grade_level} · {$student->section_name}",
                'lrn' => $student->lrn,
                'grade_level' => $student->grade_level,
                'section_id' => $student->section_id,
                'section_name' => $student->section_name,
            ]);
    }

    public function teachers(): Collection
    {
        return $this->schoolConnection()->table('teachers')
            ->leftJoin('subjects', 'subjects.teacher_id', '=', 'teachers.id')
            ->select('teachers.id', 'teachers.name')
            ->selectRaw('COUNT(subjects.id) as subject_count')
            ->groupBy('teachers.id', 'teachers.name')
            ->orderBy('teachers.name')
            ->get()
            ->map(fn ($teacher) => [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'detail' => $teacher->subject_count === 1
                    ? '1 subject assigned'
                    : "{$teacher->subject_count} subjects assigned",
                'subject_count' => (int) $teacher->subject_count,
            ]);
    }

    public function subjects(): Collection
    {
        return $this->schoolConnection()->table('subjects')
            ->leftJoin('teachers', 'subjects.teacher_id', '=', 'teachers.id')
            ->orderBy('subjects.subject_name')
            ->get([
                'subjects.id',
                'subjects.subject_name',
                'subjects.subject_code',
                'subjects.teacher_id',
                'teachers.name as teacher_name',
            ])
            ->map(fn ($subject) => [
                'id' => $subject->id,
                'name' => $subject->subject_name,
                'detail' => "{$subject->subject_code} · ".($subject->teacher_name ?? 'No teacher assigned'),
                'code' => $subject->subject_code,
                'teacher_id' => $subject->teacher_id,
                'teacher_name' => $subject->teacher_name,
            ]);
    }

    public function sections(): Collection
    {
        return $this->schoolConnection()->table('sections')
            ->leftJoin('students', 'students.section_id', '=', 'sections.id')
            ->orderBy('sections.name')
            ->orderBy('students.name')
            ->get([
                'sections.id as section_id',
                'sections.name as section_name',
                'students.id as student_id',
                'students.name as student_name',
                'students.lrn',
                'students.grade_level',
            ])
            ->groupBy('section_id')
            ->map(function (Collection $rows) {
                $section = $rows->first();
                $students = $rows
                    ->filter(fn ($row) => $row->student_id !== null)
                    ->map(fn ($row) => [
                        'id' => (int) $row->student_id,
                        'name' => (string) $row->student_name,
                        'lrn' => (string) $row->lrn,
                        'grade_level' => (string) $row->grade_level,
                    ])
                    ->values();
                $studentCount = $students->count();

                return [
                    'id' => $section->section_id,
                    'name' => $section->section_name,
                    'detail' => $studentCount === 1
                        ? '1 student enrolled'
                        : "{$studentCount} students enrolled",
                    'student_count' => $studentCount,
                    'students' => $students,
                ];
            })
            ->values();
    }

    public function sectionOptions(): Collection
    {
        return $this->schoolConnection()->table('sections')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function teacherOptions(): Collection
    {
        return $this->schoolConnection()->table('teachers')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function schoolConnection(): Connection
    {
        return DB::connection(config('database.school_connection'));
    }
}
