<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
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

    public function test_incomplete_grade_records_are_pending_until_all_assessments_are_entered(): void
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

        $this->post('/dashboard/records/grades', [
            'student_id' => $studentId,
            'subject_id' => $subjectId,
            'prelim_grade' => 80,
            'midterm_grade' => null,
            'final_grade' => null,
        ])->assertRedirect('/dashboard');

        $gradeId = $school->table('student_grades')->value('id');
        $this->assertDatabaseHas('student_grades', [
            'id' => $gradeId,
            'computed_gpa' => null,
            'pass_fail_status' => null,
        ], 'school');

        $this->get('/dashboard')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard')
            ->where('stats.pending', 1)
            ->has('pendingGrades', 1)
            ->where('pendingGrades.0.id', $gradeId));

        $this->patch("/dashboard/records/grades/{$gradeId}", [
            'student_id' => $studentId,
            'subject_id' => $subjectId,
            'midterm_grade' => 80,
            'final_grade' => 80,
        ])->assertRedirect('/dashboard');

        $this->assertDatabaseHas('student_grades', [
            'id' => $gradeId,
            'computed_gpa' => 80,
            'pass_fail_status' => 'Passed',
        ], 'school');

        $this->get('/dashboard')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('stats.pending', 0)
            ->has('pendingGrades', 0));
    }

    public function test_subject_performance_averages_entered_assessment_scores_only(): void
    {
        $this->actingAs(User::factory()->create());
        $school = DB::connection('school');
        $sectionId = $school->table('sections')->insertGetId(['name' => 'Section A']);
        $studentIds = [];
        foreach (['Student One', 'Student Two', 'Student Three'] as $index => $name) {
            $studentIds[] = $school->table('students')->insertGetId([
                'lrn' => '12345678901'.($index + 2),
                'name' => $name,
                'grade_level' => 'Grade 10',
                'section_id' => $sectionId,
            ]);
        }
        $mathSubjectId = $school->table('subjects')->insertGetId(['subject_code' => 'MATH10', 'subject_name' => 'Mathematics']);
        $scienceSubjectId = $school->table('subjects')->insertGetId(['subject_code' => 'SCI10', 'subject_name' => 'Science']);
        $school->table('student_grades')->insert([
            [
                'student_id' => $studentIds[0],
                'subject_id' => $mathSubjectId,
                'prelim_grade' => 50,
                'midterm_grade' => null,
                'final_grade' => null,
            ],
            [
                'student_id' => $studentIds[1],
                'subject_id' => $mathSubjectId,
                'prelim_grade' => 75,
                'midterm_grade' => 100,
                'final_grade' => null,
            ],
            [
                'student_id' => $studentIds[2],
                'subject_id' => $mathSubjectId,
                'prelim_grade' => null,
                'midterm_grade' => null,
                'final_grade' => null,
            ],
            [
                'student_id' => $studentIds[0],
                'subject_id' => $scienceSubjectId,
                'prelim_grade' => null,
                'midterm_grade' => null,
                'final_grade' => null,
            ],
        ]);

        $this->get('/dashboard')->assertInertia(fn (AssertableInertia $page) => $page
            ->has('subjectPerformance', 2)
            ->where('subjectPerformance.0.average_grade', fn ($average) => abs((float) $average - 75) < 0.001)
            ->where('subjectPerformance.0.grade_count', 3)
            ->where('subjectPerformance.0.assessment_count', 3)
            ->where('subjectPerformance.1.average_grade', null)
            ->where('subjectPerformance.1.assessment_count', 0));
    }

    public function test_dashboard_lists_completed_passed_and_failed_grade_records(): void
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
        $passedSubjectId = $school->table('subjects')->insertGetId(['subject_code' => 'MATH10', 'subject_name' => 'Mathematics']);
        $failedSubjectId = $school->table('subjects')->insertGetId(['subject_code' => 'SCI10', 'subject_name' => 'Science']);
        $school->table('student_grades')->insert([
            [
                'student_id' => $studentId,
                'subject_id' => $passedSubjectId,
                'prelim_grade' => 80,
                'midterm_grade' => 80,
                'final_grade' => 80,
                'computed_gpa' => 80,
                'pass_fail_status' => 'Passed',
            ],
            [
                'student_id' => $studentId,
                'subject_id' => $failedSubjectId,
                'prelim_grade' => 70,
                'midterm_grade' => 70,
                'final_grade' => 70,
                'computed_gpa' => 70,
                'pass_fail_status' => 'Failed',
            ],
        ]);

        $this->get('/dashboard')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('stats.passed', 1)
            ->where('stats.failed', 1)
            ->has('gradeOutcomeRecords', 2)
            ->has('pendingGrades', 0));
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

    public function test_dashboard_can_update_grade_values_and_recalculate_the_result(): void
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
        $gradeId = $school->table('student_grades')->insertGetId([
            'student_id' => $studentId,
            'subject_id' => $subjectId,
            'prelim_grade' => 80,
            'midterm_grade' => 80,
            'final_grade' => 80,
        ]);

        $this->patch("/dashboard/records/grades/{$gradeId}", [
            'student_id' => $studentId,
            'subject_id' => $subjectId,
            'prelim_grade' => 60,
            'midterm_grade' => 70,
            'final_grade' => 80,
        ])->assertRedirect('/dashboard');

        $this->assertDatabaseHas('student_grades', [
            'id' => $gradeId,
            'computed_gpa' => 70,
            'pass_fail_status' => 'Failed',
        ], 'school');
    }

    public function test_dashboard_rejects_editing_a_grade_to_a_duplicate_student_and_subject_pair(): void
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
        $firstSubjectId = $school->table('subjects')->insertGetId(['subject_code' => 'MATH10', 'subject_name' => 'Mathematics']);
        $secondSubjectId = $school->table('subjects')->insertGetId(['subject_code' => 'SCI10', 'subject_name' => 'Science']);
        $school->table('student_grades')->insert([
            'student_id' => $firstStudentId,
            'subject_id' => $firstSubjectId,
        ]);
        $gradeId = $school->table('student_grades')->insertGetId([
            'student_id' => $secondStudentId,
            'subject_id' => $secondSubjectId,
        ]);

        $this->from('/dashboard')
            ->patch("/dashboard/records/grades/{$gradeId}", [
                'student_id' => $firstStudentId,
                'subject_id' => $firstSubjectId,
                'prelim_grade' => 80,
                'midterm_grade' => 80,
                'final_grade' => 80,
            ])
            ->assertRedirect('/dashboard')
            ->assertSessionHasErrors('student_id');

        $this->assertDatabaseHas('student_grades', [
            'id' => $gradeId,
            'student_id' => $secondStudentId,
            'subject_id' => $secondSubjectId,
        ], 'school');
    }

    public function test_dashboard_can_delete_a_grade_record(): void
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
        $gradeId = $school->table('student_grades')->insertGetId([
            'student_id' => $studentId,
            'subject_id' => $subjectId,
        ]);

        $this->delete("/dashboard/records/grades/{$gradeId}")->assertRedirect('/dashboard');

        $this->assertDatabaseMissing('student_grades', ['id' => $gradeId], 'school');
    }
}
