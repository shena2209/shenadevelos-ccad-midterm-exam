<?php

use App\Http\Controllers\DashboardRecordController;
use App\Http\Controllers\SchoolRecordsController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\StudentGradeController;
use App\Http\Controllers\SubjectController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? Redirect::route('dashboard')
        : Redirect::route('login');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::prefix('school-records')->name('school-records.')->controller(SchoolRecordsController::class)->group(function () {
        Route::get('students', 'students')->name('students');
        Route::get('teachers', 'teachers')->name('teachers');
        Route::get('subjects', 'subjects')->name('subjects');
        Route::get('sections', 'sections')->name('sections');
    });
    Route::prefix('school-records')->name('school-records.')->controller(DashboardRecordController::class)->group(function () {
        Route::patch('students/{student}', 'updateStudent')->name('students.update');
        Route::delete('students/{student}', 'destroyStudent')->name('students.destroy');
        Route::patch('teachers/{teacher}', 'updateTeacher')->name('teachers.update');
        Route::delete('teachers/{teacher}', 'destroyTeacher')->name('teachers.destroy');
        Route::patch('subjects/{subject}', 'updateSubject')->name('subjects.update');
        Route::delete('subjects/{subject}', 'destroySubject')->name('subjects.destroy');
        Route::patch('sections/{section}', 'updateSection')->name('sections.update');
        Route::delete('sections/{section}', 'destroySection')->name('sections.destroy');
    });

    Route::get('attendance', [StudentAttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance', [StudentAttendanceController::class, 'store'])->name('attendance.store');

    Route::get('dashboard', [DashboardRecordController::class, 'index'])->name('dashboard');

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
