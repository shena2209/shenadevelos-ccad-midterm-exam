<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
            'lrn', 
            'name',
            'grade_level'];

    public function grades(): HasMany
    {
        return $this->hasMany(StudentGrade::class);
    }
}   