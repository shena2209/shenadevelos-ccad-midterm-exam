<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    protected $fillable = [
            'name', 
            'grade_level'];

    public function grades(): HasMany
    {
        return $this->hasMany(StudentGrade::class);
    }
}