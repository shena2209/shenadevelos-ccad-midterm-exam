<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardRecordController;
use App\Http\Controllers\SchoolRecordsController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\StudentGradeController;
use App\Http\Controllers\SubjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::prefix('school-records')->name('school-records.')->controller(SchoolRecordsController::class)->group(function () {
        Route::get('students', 'students')->name('students');
        Route::get('teachers', 'teachers')->name('teachers');
        Route::get('subjects', 'subjects')->name('subjects');
        Route::get('sections', 'sections')->name('sections');
    });

    Route::get('attendance', [StudentAttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance', [StudentAttendanceController::class, 'store'])->name('attendance.store');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('dashboard/records')->name('dashboard.records.')->controller(DashboardRecordController::class)->group(function () {
        Route::post('students', 'storeStudent')->name('students.store');
        Route::post('teachers', 'storeTeacher')->name('teachers.store');
        Route::post('subjects', 'storeSubject')->name('subjects.store');
        Route::post('sections', 'storeSection')->name('sections.store');
        Route::post('grades', 'storeGrade')->name('grades.store');
        Route::patch('grades/{grade}', 'updateGrade')->name('grades.update');
        Route::delete('grades/{grade}', 'destroyGrade')->name('grades.destroy');
    });

    Route::resource('students', StudentGradeController::class);
    Route::resource('subjects', SubjectController::class);
    Route::resource('StudentGrades', StudentGradeController::class);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
