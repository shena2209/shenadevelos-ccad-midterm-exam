<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\Feature\Concerns\UsesSchoolDatabase;
use Tests\TestCase;

class SchoolRecordsTest extends TestCase
{
    use RefreshDatabase;
    use UsesSchoolDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureSchoolDatabase();
    }

    public function test_sidebar_record_pages_only_return_the_selected_record_type(): void
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
        $teacherId = $school->table('teachers')->insertGetId(['name' => 'Teacher Example']);
        $school->table('subjects')->insert([
            'subject_code' => 'MATH10',
            'subject_name' => 'Mathematics',
            'teacher_id' => $teacherId,
        ]);

        $this->get('/school-records/students')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('school-records')
            ->where('title', 'Students')
            ->has('records', 1)
            ->where('records.0.name', 'Student Example')
            ->where('records.0.detail', 'LRN 123456789012 · Grade 10 · Section A')
            ->where('records.0.lrn', '123456789012')
            ->where('records.0.section_id', $sectionId)
            ->has('options.sections', 1));

        $this->get('/school-records/teachers')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('title', 'Teachers')
            ->has('records', 1)
            ->where('records.0.name', 'Teacher Example')
            ->where('records.0.detail', '1 subject assigned')
            ->where('records.0.subject_count', 1));

        $this->get('/school-records/subjects')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('title', 'Subjects')
            ->has('records', 1)
            ->where('records.0.name', 'Mathematics')
            ->where('records.0.detail', 'MATH10 · Teacher Example')
            ->where('records.0.code', 'MATH10')
            ->where('records.0.teacher_id', $teacherId)
            ->has('options.teachers', 1));

        $this->get('/school-records/sections')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('title', 'Sections')
            ->has('records', 1)
            ->where('records.0.name', 'Section A')
            ->where('records.0.detail', '1 student enrolled')
            ->where('records.0.student_count', 1)
            ->where('records.0.students.0.name', 'Student Example')
            ->where('records.0.students.0.lrn', '123456789012')
            ->where('records.0.students.0.grade_level', 'Grade 10'));

        $this->assertSame(1, $school->table('students')->where('id', $studentId)->count());
    }

    public function test_school_records_can_be_updated_and_deleted(): void
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
        $teacherId = $school->table('teachers')->insertGetId(['name' => 'Teacher Example']);
        $subjectId = $school->table('subjects')->insertGetId([
            'subject_code' => 'MATH10',
            'subject_name' => 'Mathematics',
            'teacher_id' => $teacherId,
        ]);

        $this->patch("/school-records/students/{$studentId}", [
            'name' => 'Updated Student',
            'lrn' => '123456789013',
            'grade_level' => 'Grade 11',
            'section_id' => $sectionId,
        ])->assertRedirect();
        $this->patch("/school-records/teachers/{$teacherId}", [
            'name' => 'Updated Teacher',
        ])->assertRedirect();
        $this->patch("/school-records/subjects/{$subjectId}", [
            'name' => 'Updated Mathematics',
            'code' => 'MATH11',
            'teacher_id' => '',
        ])->assertRedirect();
        $this->patch("/school-records/sections/{$sectionId}", [
            'name' => 'Updated Section',
        ])->assertRedirect();

        $this->assertDatabaseHas('students', ['id' => $studentId, 'name' => 'Updated Student', 'lrn' => '123456789013'], 'school');
        $this->assertDatabaseHas('teachers', ['id' => $teacherId, 'name' => 'Updated Teacher'], 'school');
        $this->assertDatabaseHas('subjects', [
            'id' => $subjectId,
            'subject_name' => 'Updated Mathematics',
            'subject_code' => 'MATH11',
            'teacher_id' => null,
        ], 'school');
        $this->assertDatabaseHas('sections', ['id' => $sectionId, 'name' => 'Updated Section'], 'school');

        $this->delete("/school-records/students/{$studentId}")->assertRedirect();
        $this->delete("/school-records/teachers/{$teacherId}")->assertRedirect();
        $this->delete("/school-records/subjects/{$subjectId}")->assertRedirect();
        $this->delete("/school-records/sections/{$sectionId}")->assertRedirect();

        $this->assertDatabaseMissing('students', ['id' => $studentId], 'school');
        $this->assertDatabaseMissing('teachers', ['id' => $teacherId], 'school');
        $this->assertDatabaseMissing('subjects', ['id' => $subjectId], 'school');
        $this->assertDatabaseMissing('sections', ['id' => $sectionId], 'school');
    }

    public function test_school_records_with_linked_data_cannot_be_deleted(): void
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
        $teacherId = $school->table('teachers')->insertGetId(['name' => 'Teacher Example']);
        $subjectId = $school->table('subjects')->insertGetId([
            'subject_code' => 'MATH10',
            'subject_name' => 'Mathematics',
            'teacher_id' => $teacherId,
        ]);
        $school->table('student_grades')->insert([
            'student_id' => $studentId,
            'subject_id' => $subjectId,
        ]);
        $school->table('student_attendances')->insert([
            'student_id' => $studentId,
            'attendance_date' => '2026-11-24',
            'status' => 'Present',
        ]);

        foreach ([
            "/school-records/students/{$studentId}",
            "/school-records/teachers/{$teacherId}",
            "/school-records/subjects/{$subjectId}",
            "/school-records/sections/{$sectionId}",
        ] as $uri) {
            $this->from('/school-records/students')
                ->delete($uri)
                ->assertRedirect('/school-records/students')
                ->assertSessionHasErrors('record');
        }

        $this->assertDatabaseHas('students', ['id' => $studentId], 'school');
        $this->assertDatabaseHas('teachers', ['id' => $teacherId], 'school');
        $this->assertDatabaseHas('subjects', ['id' => $subjectId], 'school');
        $this->assertDatabaseHas('sections', ['id' => $sectionId], 'school');
    }
}
