<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\UsesSchoolDatabase;
use Tests\TestCase;

class SchoolGradingTest extends TestCase
{
    use RefreshDatabase;
    use UsesSchoolDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureSchoolDatabase();
    }

    public function test_dashboard_creates_records_in_the_school_database_and_calculates_grade_status(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post('/dashboard/records/sections', [
            'name' => 'Section A',
        ])->assertRedirect('/dashboard');
        $sectionId = DB::connection('school')->table('sections')->value('id');

        $this->post('/dashboard/records/students', [
            'name' => 'Student Example',
            'lrn' => '123456789012',
            'grade_level' => 'Grade 10',
            'section_id' => $sectionId,
        ])->assertRedirect('/dashboard');

        $this->post('/dashboard/records/teachers', [
            'name' => 'Teacher Example',
        ])->assertRedirect('/dashboard');
        $teacherId = DB::connection('school')->table('teachers')->value('id');

        $this->post('/dashboard/records/subjects', [
            'name' => 'Mathematics',
            'code' => 'MATH10',
            'teacher_id' => $teacherId,
        ])->assertRedirect('/dashboard');

        $studentId = DB::connection('school')->table('students')->value('id');
        $subjectId = DB::connection('school')->table('subjects')->value('id');

        $this->post('/dashboard/records/grades', [
            'student_id' => $studentId,
            'subject_id' => $subjectId,
            'prelim_grade' => 75,
            'midterm_grade' => 75,
            'final_grade' => 75,
        ])->assertRedirect('/dashboard');

        $this->assertDatabaseHas('student_grades', [
            'student_id' => $studentId,
            'computed_gpa' => 75,
            'pass_fail_status' => 'Passed',
        ], 'school');
    }

    public function test_dashboard_rejects_duplicate_grade_record_for_same_student_and_subject(): void
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
        $subjectId = $school->table('subjects')->insertGetId(['subject_code' => 'MATH10', 'subject_name' => 'Mathematics']);
        $grade = [
            'student_id' => $studentId,
            'subject_id' => $subjectId,
            'prelim_grade' => 74,
            'midterm_grade' => 74,
            'final_grade' => 74,
        ];

        $this->post('/dashboard/records/grades', $grade)->assertRedirect('/dashboard');
        $this->assertDatabaseHas('student_grades', [
            'student_id' => $studentId,
            'computed_gpa' => 74,
            'pass_fail_status' => 'Failed',
        ], 'school');

        $this->from('/dashboard')
            ->post('/dashboard/records/grades', $grade)
            ->assertRedirect('/dashboard')
            ->assertSessionHasErrors('student_id');

        $this->assertSame(1, DB::connection('school')->table('student_grades')->count());
    }
}
