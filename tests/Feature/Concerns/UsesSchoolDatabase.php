<?php

namespace Tests\Feature\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait UsesSchoolDatabase
{
    protected function configureSchoolDatabase(): void
    {
        config([
            'database.school_connection' => 'school',
            'database.connections.school' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('school');

        $schema = Schema::connection('school');
        $schema->create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });
        $schema->create('students', function (Blueprint $table) {
            $table->id();
            $table->string('lrn', 12)->unique();
            $table->string('name', 150);
            $table->string('grade_level', 20);
            $table->foreignId('section_id')->constrained('sections');
            $table->timestamps();
        });
        $schema->create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->timestamps();
        });
        $schema->create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('subject_code', 20)->unique();
            $table->string('subject_name', 150);
            $table->foreignId('teacher_id')->nullable()->constrained('teachers');
            $table->timestamps();
        });
        $schema->create('student_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students');
            $table->foreignId('subject_id')->constrained('subjects');
            $table->decimal('prelim_grade', 5, 2)->nullable();
            $table->decimal('midterm_grade', 5, 2)->nullable();
            $table->decimal('final_grade', 5, 2)->nullable();
            $table->decimal('computed_gpa', 5, 2)->nullable();
            $table->enum('pass_fail_status', ['Passed', 'Failed'])->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'subject_id']);
        });
    }
}
