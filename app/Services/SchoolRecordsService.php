<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;

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
                'lrn' => $student->lrn,
                'grade_level' => $student->grade_level,
                'section_id' => $student->section_id,
                'section_name' => $student->section_name,
                'detail' => "LRN {$student->lrn} · {$student->grade_level} · {$student->section_name}",
            ]);
    }

    public function studentSections(): Collection
    {
        return $this->schoolConnection()->table('sections')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function subjectTeachers(): Collection
    {
        return $this->schoolConnection()->table('teachers')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function teachers(): Collection
    {
        $subjectsByTeacher = $this->schoolConnection()->table('subjects')
            ->orderBy('subject_name')
            ->get(['id', 'teacher_id', 'subject_name', 'subject_code'])
            ->groupBy('teacher_id');

        return $this->schoolConnection()->table('teachers')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function ($teacher) use ($subjectsByTeacher) {
                $subjects = $subjectsByTeacher->get($teacher->id, collect())
                    ->map(fn ($subject) => [
                        'id' => (int) $subject->id,
                        'name' => (string) $subject->subject_name,
                        'code' => (string) $subject->subject_code,
                    ])
                    ->values();
                $subjectCount = $subjects->count();

                return [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'detail' => $subjectCount === 1 ? '1 subject assigned' : "{$subjectCount} subjects assigned",
                    'subjects' => $subjects,
                ];
            });
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
                'code' => $subject->subject_code,
                'teacher_id' => $subject->teacher_id,
                'teacher_name' => $subject->teacher_name,
                'detail' => "{$subject->subject_code} · ".($subject->teacher_name ?? 'No teacher assigned'),
            ]);
    }

    public function sections(): Collection
    {
        $studentsBySection = $this->schoolConnection()->table('students')
            ->orderBy('name')
            ->get(['id', 'name', 'lrn', 'grade_level', 'section_id'])
            ->groupBy('section_id')
            ->map(fn (Collection $students) => $students->map(fn ($student) => [
                'id' => (int) $student->id,
                'name' => (string) $student->name,
                'lrn' => (string) $student->lrn,
                'grade_level' => (string) $student->grade_level,
            ])->values());

        return $this->schoolConnection()->table('sections')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function ($section) use ($studentsBySection) {
                $students = $studentsBySection->get($section->id, collect());
                $studentCount = $students->count();

                return [
                    'id' => $section->id,
                    'name' => $section->name,
                    'detail' => $studentCount === 1 ? '1 student enrolled' : "{$studentCount} students enrolled",
                    'students' => $students,
                ];
            });
    }

    private function schoolConnection(): Connection
    {
        return Student::query()->getConnection();
    }
}
