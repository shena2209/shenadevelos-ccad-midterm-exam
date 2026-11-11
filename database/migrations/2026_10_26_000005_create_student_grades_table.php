<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_grades', function (Blueprint $table) {
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

    public function down(): void
    {
        Schema::dropIfExists('student_grades');
    }
};