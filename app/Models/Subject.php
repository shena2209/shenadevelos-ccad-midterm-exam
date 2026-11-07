<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    protected $fillable = ['code', 'name'];

    public function grades(): HasMany
    {
        return $this->hasMany(StudentGrade::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_grades')
            ->withPivot([
                'teacher_id',
                'section_id',
                'prelim_grade',
                'midterm_grade',
                'final_grade',
                'computed_gpa',
                'pass_fail_status',
            ])
            ->withTimestamps();
    }
}