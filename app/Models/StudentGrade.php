<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentGrade extends Model
{
    protected $fillable = [
        'student_id',
        'subject_id',
        'teacher_id',
        'section_id',
        'prelim_grade',
        'midterm_grade',
        'final_grade',
        'computed_gpa',
        'pass_fail_status',
    ];

    protected $casts = [
        'prelim_grade'  => 'decimal:2',
        'midterm_grade' => 'decimal:2',
        'final_grade'   => 'decimal:2',
        'computed_gpa'  => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}