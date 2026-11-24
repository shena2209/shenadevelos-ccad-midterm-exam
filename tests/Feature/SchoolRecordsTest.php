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
            ->where('records.0.detail', '1 subject assigned'));

        $this->get('/school-records/subjects')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('title', 'Subjects')
            ->has('records', 1)
            ->where('records.0.name', 'Mathematics')
            ->where('records.0.detail', 'MATH10 · Teacher Example'));

        $this->get('/school-records/sections')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('title', 'Sections')
            ->has('records', 1)
            ->where('records.0.name', 'Section A')
            ->where('records.0.detail', '1 student enrolled'));

        $this->assertSame(1, $school->table('students')->where('id', $studentId)->count());
    }
}
