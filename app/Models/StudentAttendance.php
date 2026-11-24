<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{
    protected $connection = 'school';

    protected $fillable = [
        'student_id',
        'attendance_date',
        'status',
        'notes',
    ];
}
