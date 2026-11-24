<?php

namespace App\Services;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

class StudentAttendanceService
{
    public function dailyAttendance(string $date): array
    {
        $school = $this->schoolConnection();
        $students = $school->table('students')
            ->join('sections', 'students.section_id', '=', 'sections.id')
            ->leftJoin('student_attendances as attendance', function ($join) use ($date) {
                $join->on('attendance.student_id', '=', 'students.id')
                    ->where('attendance.attendance_date', '=', $date);
            })
            ->orderBy('sections.name')
            ->orderBy('students.name')
            ->get([
                'students.id',
                'students.name',
                'students.lrn',
                'students.grade_level',
                'sections.name as section_name',
                'attendance.status',
                'attendance.notes',
            ]);

        $present = $students->where('status', 'Present')->count();
        $late = $students->where('status', 'Late')->count();
        $totalStudents = $students->count();
        $history = $school->table('student_attendances')
            ->select('attendance_date')
            ->selectRaw('COUNT(*) as marked')
            ->selectRaw("SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present")
            ->selectRaw("SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent")
            ->selectRaw("SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late")
            ->selectRaw("SUM(CASE WHEN status = 'Excused' THEN 1 ELSE 0 END) as excused")
            ->groupBy('attendance_date')
            ->orderByDesc('attendance_date')
            ->limit(20)
            ->get()
            ->map(fn ($record) => [
                'date' => (string) $record->attendance_date,
                'marked' => (int) $record->marked,
                'present' => (int) $record->present,
                'absent' => (int) $record->absent,
                'late' => (int) $record->late,
                'excused' => (int) $record->excused,
            ]);
        $attendanceDetails = $school->table('student_attendances as attendance')
            ->join('students', 'attendance.student_id', '=', 'students.id')
            ->join('sections', 'students.section_id', '=', 'sections.id')
            ->whereIn('attendance.attendance_date', $history->pluck('date'))
            ->orderBy('students.name')
            ->get([
                'attendance.student_id',
                'attendance.attendance_date',
                'attendance.status',
                'attendance.notes',
                'students.name',
                'students.lrn',
                'students.grade_level',
                'sections.name as section_name',
            ])
            ->groupBy(fn ($record) => (string) $record->attendance_date);
        $history = $history->map(function (array $record) use ($attendanceDetails) {
            $record['students'] = $attendanceDetails
                ->get($record['date'], collect())
                ->map(fn ($student) => [
                    'id' => (int) $student->student_id,
                    'name' => (string) $student->name,
                    'lrn' => (string) $student->lrn,
                    'grade_level' => (string) $student->grade_level,
                    'section_name' => (string) $student->section_name,
                    'status' => (string) $student->status,
                    'notes' => $student->notes,
                ])
                ->values()
                ->all();

            return $record;
        });

        return [
            'students' => $students,
            'history' => $history,
            'stats' => [
                'totalStudents' => $totalStudents,
                'marked' => $students->whereNotNull('status')->count(),
                'present' => $present,
                'absent' => $students->where('status', 'Absent')->count(),
                'late' => $late,
                'excused' => $students->where('status', 'Excused')->count(),
                'unmarked' => $students->whereNull('status')->count(),
                'attendanceRate' => $totalStudents > 0 ? (int) round((($present + $late) / $totalStudents) * 100) : 0,
            ],
        ];
    }

    public function saveAttendance(string $date, array $records): void
    {
        $now = now();
        $rows = array_map(fn (array $record) => [
            'student_id' => $record['student_id'],
            'attendance_date' => $date,
            'status' => $record['status'],
            'notes' => $record['notes'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $records);

        $school = $this->schoolConnection();
        $school->transaction(fn () => $school->table('student_attendances')->upsert(
            $rows,
            ['student_id', 'attendance_date'],
            ['status', 'notes', 'updated_at'],
        ));
    }

    private function schoolConnection(): Connection
    {
        return DB::connection(config('database.school_connection'));
    }
}
