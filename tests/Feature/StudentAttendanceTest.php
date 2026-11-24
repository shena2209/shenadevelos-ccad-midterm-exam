<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\Feature\Concerns\UsesSchoolDatabase;
use Tests\TestCase;

class StudentAttendanceTest extends TestCase
{
    use RefreshDatabase;
    use UsesSchoolDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureSchoolDatabase();
    }

    public function test_daily_attendance_can_be_saved_updated_and_counted(): void
    {
        $this->actingAs(User::factory()->create());
        $school = DB::connection('school');
        $sectionId = $school->table('sections')->insertGetId(['name' => 'Section A']);
        $firstStudentId = $school->table('students')->insertGetId([
            'lrn' => '123456789012',
            'name' => 'First Student',
            'grade_level' => 'Grade 10',
            'section_id' => $sectionId,
        ]);
        $secondStudentId = $school->table('students')->insertGetId([
            'lrn' => '123456789013',
            'name' => 'Second Student',
            'grade_level' => 'Grade 10',
            'section_id' => $sectionId,
        ]);
        $date = '2026-11-18';

        $this->get("/attendance?date={$date}")->assertInertia(fn (AssertableInertia $page) => $page
            ->component('attendance')
            ->where('stats.totalStudents', 2)
            ->where('stats.unmarked', 2)
            ->where('stats.attendanceRate', 0));

        $this->post('/attendance', [
            'date' => $date,
            'records' => [
                ['student_id' => $firstStudentId, 'status' => 'Present', 'notes' => ''],
                ['student_id' => $secondStudentId, 'status' => 'Absent', 'notes' => ''],
            ],
        ])->assertRedirect("/attendance?date={$date}");

        $this->assertDatabaseHas('student_attendances', [
            'student_id' => $firstStudentId,
            'attendance_date' => $date,
            'status' => 'Present',
        ], 'school');

        $this->get("/attendance?date={$date}")->assertInertia(fn (AssertableInertia $page) => $page
            ->where('stats.marked', 2)
            ->where('stats.unmarked', 0)
            ->where('stats.present', 1)
            ->where('stats.absent', 1)
            ->where('stats.attendanceRate', 50)
            ->where('history.0.date', $date)
            ->where('history.0.marked', 2)
            ->where('history.0.present', 1)
            ->where('history.0.absent', 1));

        $this->post('/attendance', [
            'date' => $date,
            'records' => [
                ['student_id' => $firstStudentId, 'status' => 'Late', 'notes' => 'Arrived late'],
                ['student_id' => $secondStudentId, 'status' => 'Excused', 'notes' => ''],
            ],
        ])->assertRedirect("/attendance?date={$date}");

        $this->assertSame(2, $school->table('student_attendances')->count());
        $this->assertDatabaseHas('student_attendances', [
            'student_id' => $firstStudentId,
            'attendance_date' => $date,
            'status' => 'Late',
            'notes' => 'Arrived late',
        ], 'school');

        $this->get("/attendance?date={$date}")->assertInertia(fn (AssertableInertia $page) => $page
            ->where('stats.late', 1)
            ->where('stats.excused', 1)
            ->where('stats.attendanceRate', 50)
            ->where('history.0.students.0.name', 'First Student')
            ->where('history.0.students.0.status', 'Late')
            ->where('history.0.students.0.notes', 'Arrived late')
            ->where('history.0.students.1.name', 'Second Student')
            ->where('history.0.students.1.status', 'Excused'));
    }

    public function test_daily_attendance_rejects_duplicate_students_and_invalid_statuses(): void
    {
        $this->actingAs(User::factory()->create());
        $school = DB::connection('school');
        $sectionId = $school->table('sections')->insertGetId(['name' => 'Section A']);
        $studentId = $school->table('students')->insertGetId([
            'lrn' => '123456789012',
            'name' => 'Student Example',
            'grade_level' => 'Grade 10',
            'section_id' => $sectionId,
        ]);

        $this->from('/attendance')
            ->post('/attendance', [
                'date' => '2026-11-18',
                'records' => [
                    ['student_id' => $studentId, 'status' => 'Present'],
                    ['student_id' => $studentId, 'status' => 'Unexcused'],
                ],
            ])
            ->assertRedirect('/attendance')
            ->assertSessionHasErrors(['records.0.student_id', 'records.1.student_id', 'records.1.status']);

        $this->assertSame(0, $school->table('student_attendances')->count());
    }
}
