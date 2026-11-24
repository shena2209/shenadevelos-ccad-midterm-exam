<?php

namespace App\Services;

use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Support\Collection;

class SchoolRecordsService
{
    public function students(): Collection
    {
        return Student::query()
            ->join('sections', 'students.section_id', '=', 'sections.id')
            ->orderBy('students.name')
            ->get(['students.id', 'students.name', 'students.lrn', 'students.grade_level', 'sections.name as section_name'])
            ->map(fn ($student) => [
                'id' => $student->id,
                'name' => $student->name,
                'detail' => "LRN {$student->lrn} · {$student->grade_level} · {$student->section_name}",
            ]);
    }

    public function teachers(): Collection
    {
        return Teacher::query()
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
            ]);
    }

    public function subjects(): Collection
    {
        return Subject::query()
            ->leftJoin('teachers', 'subjects.teacher_id', '=', 'teachers.id')
            ->orderBy('subjects.subject_name')
            ->get([
                'subjects.id',
                'subjects.subject_name',
                'subjects.subject_code',
                'teachers.name as teacher_name',
            ])
            ->map(fn ($subject) => [
                'id' => $subject->id,
                'name' => $subject->subject_name,
                'detail' => "{$subject->subject_code} · ".($subject->teacher_name ?? 'No teacher assigned'),
            ]);
    }

    public function sections(): Collection
    {
        return Section::query()
            ->leftJoin('students', 'students.section_id', '=', 'sections.id')
            ->select('sections.id', 'sections.name')
            ->selectRaw('COUNT(students.id) as student_count')
            ->groupBy('sections.id', 'sections.name')
            ->orderBy('sections.name')
            ->get()
            ->map(fn ($section) => [
                'id' => $section->id,
                'name' => $section->name,
                'detail' => $section->student_count === 1
                    ? '1 student enrolled'
                    : "{$section->student_count} students enrolled",
            ]);
    }
}
