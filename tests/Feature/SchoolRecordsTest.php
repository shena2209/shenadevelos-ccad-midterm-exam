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
            ->where('records.0.detail', 'LRN 123456789012 · Grade 10 · Section A'));

        $this->get('/school-records/teachers')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('title', 'Teachers')
            ->has('records', 1)
            ->where('records.0.name', 'Teacher Example')
            ->where('records.0.detail', '1 subject assigned')
            ->where('records.0.subjects.0.name', 'Mathematics'));

        $this->get('/school-records/subjects')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('title', 'Subjects')
            ->has('records', 1)
            ->where('records.0.name', 'Mathematics')
            ->where('records.0.detail', 'MATH10 · Teacher Example')
            ->where('records.0.teacher_id', $teacherId)
            ->where('teachers.0.name', 'Teacher Example'));

        $this->get('/school-records/sections')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('title', 'Sections')
            ->has('records', 1)
            ->where('records.0.name', 'Section A')
            ->where('records.0.detail', '1 student enrolled')
            ->where('records.0.students.0.id', $studentId)
            ->where('records.0.students.0.name', 'Student Example')
            ->where('records.0.students.0.lrn', '123456789012'));

        $this->assertSame(1, $school->table('students')->where('id', $studentId)->count());
    }

    public function test_teachers_subjects_and_sections_can_be_edited_and_deleted(): void
    {
        $this->actingAs(User::factory()->create());
        $school = DB::connection('school');
        $teacherId = $school->table('teachers')->insertGetId(['name' => 'Teacher Example']);
        $subjectId = $school->table('subjects')->insertGetId([
            'subject_code' => 'MATH10',
            'subject_name' => 'Mathematics',
            'teacher_id' => $teacherId,
        ]);
        $duplicateSubjectId = $school->table('subjects')->insertGetId([
            'subject_code' => 'SCI10',
            'subject_name' => 'Science',
        ]);
        $sectionId = $school->table('sections')->insertGetId(['name' => 'Section A']);
        $duplicateSectionId = $school->table('sections')->insertGetId(['name' => 'Section C']);

        $this->patch("/school-records/teachers/{$teacherId}", [
            'name' => '  Updated Teacher  ',
        ])->assertRedirect('/school-records/teachers');

        $this->from('/school-records/subjects')
            ->patch("/school-records/subjects/{$subjectId}", [
                'subject_name' => 'Mathematics',
                'subject_code' => 'SCI10',
                'teacher_id' => $teacherId,
            ])
            ->assertRedirect('/school-records/subjects')
            ->assertSessionHasErrors('subject_code');

        $this->patch("/school-records/subjects/{$subjectId}", [
            'subject_name' => '  Updated Mathematics  ',
            'subject_code' => ' MATH11 ',
            'teacher_id' => $teacherId,
        ])->assertRedirect('/school-records/subjects');

        $this->from('/school-records/sections')
            ->patch("/school-records/sections/{$sectionId}", [
                'name' => 'Section C',
            ])
            ->assertRedirect('/school-records/sections')
            ->assertSessionHasErrors('name');

        $this->patch("/school-records/sections/{$sectionId}", [
            'name' => '  Section B  ',
        ])->assertRedirect('/school-records/sections');

        $this->assertDatabaseHas('teachers', ['id' => $teacherId, 'name' => 'Updated Teacher'], 'school');
        $this->assertDatabaseHas('subjects', [
            'id' => $subjectId,
            'subject_name' => 'Updated Mathematics',
            'subject_code' => 'MATH11',
            'teacher_id' => $teacherId,
        ], 'school');
        $this->assertDatabaseHas('sections', ['id' => $sectionId, 'name' => 'Section B'], 'school');

        $this->delete("/school-records/subjects/{$subjectId}")
            ->assertRedirect('/school-records/subjects');
        $this->delete("/school-records/teachers/{$teacherId}")
            ->assertRedirect('/school-records/teachers');
        $this->delete("/school-records/sections/{$sectionId}")
            ->assertRedirect('/school-records/sections');
        $this->delete("/school-records/subjects/{$duplicateSubjectId}")
            ->assertRedirect('/school-records/subjects');
        $this->delete("/school-records/sections/{$duplicateSectionId}")
            ->assertRedirect('/school-records/sections');

        $this->assertDatabaseMissing('subjects', ['id' => $subjectId], 'school');
        $this->assertDatabaseMissing('teachers', ['id' => $teacherId], 'school');
        $this->assertDatabaseMissing('sections', ['id' => $sectionId], 'school');
    }

    public function test_teachers_subjects_and_sections_with_linked_records_cannot_be_deleted(): void
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

        $this->from('/school-records/teachers')
            ->delete("/school-records/teachers/{$teacherId}")
            ->assertRedirect('/school-records/teachers')
            ->assertSessionHasErrors('record');
        $this->from('/school-records/subjects')
            ->delete("/school-records/subjects/{$subjectId}")
            ->assertRedirect('/school-records/subjects')
            ->assertSessionHasErrors('record');
        $this->from('/school-records/sections')
            ->delete("/school-records/sections/{$sectionId}")
            ->assertRedirect('/school-records/sections')
            ->assertSessionHasErrors('record');

        $this->assertDatabaseHas('teachers', ['id' => $teacherId], 'school');
        $this->assertDatabaseHas('subjects', ['id' => $subjectId], 'school');
        $this->assertDatabaseHas('sections', ['id' => $sectionId], 'school');
    }

    public function test_students_can_be_viewed_edited_and_deleted(): void
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

        $this->get('/school-records/students')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('school-records')
            ->where('records.0.lrn', '123456789012')
            ->where('records.0.grade_level', 'Grade 10')
            ->where('records.0.section_id', $sectionId)
            ->where('records.0.section_name', 'Section A')
            ->where('sections.0.id', $sectionId));

        $this->patch("/dashboard/records/students/{$studentId}", [
            'name' => 'Updated Student',
            'lrn' => '123456789012',
            'grade_level' => 'Grade 11',
            'section_id' => $sectionId,
        ])->assertRedirect('/school-records/students');

        $this->assertDatabaseHas('students', [
            'id' => $studentId,
            'name' => 'Updated Student',
            'grade_level' => 'Grade 11',
        ], 'school');

        $this->delete("/dashboard/records/students/{$studentId}")
            ->assertRedirect('/school-records/students');

        $this->assertDatabaseMissing('students', ['id' => $studentId], 'school');
    }

    public function test_students_with_grade_records_cannot_be_deleted(): void
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
        $subjectId = $school->table('subjects')->insertGetId([
            'subject_code' => 'MATH10',
            'subject_name' => 'Mathematics',
        ]);
        $school->table('student_grades')->insert([
            'student_id' => $studentId,
            'subject_id' => $subjectId,
        ]);

        $this->from('/school-records/students')
            ->delete("/dashboard/records/students/{$studentId}")
            ->assertRedirect('/school-records/students')
            ->assertSessionHasErrors('student');

        $this->assertDatabaseHas('students', ['id' => $studentId], 'school');
    }
}
