<?php

use App\Http\Controllers\DashboardRecordController;
use App\Http\Controllers\SchoolRecordsController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\StudentGradeController;
use App\Http\Controllers\SubjectController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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
        Route::patch('teachers/{teacher}', 'updateTeacher')->name('teachers.update');
        Route::delete('teachers/{teacher}', 'destroyTeacher')->name('teachers.destroy');
        Route::patch('subjects/{subject}', 'updateSubject')->name('subjects.update');
        Route::delete('subjects/{subject}', 'destroySubject')->name('subjects.destroy');
        Route::patch('sections/{section}', 'updateSection')->name('sections.update');
        Route::delete('sections/{section}', 'destroySection')->name('sections.destroy');
    });

    Route::get('attendance', [StudentAttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance', [StudentAttendanceController::class, 'store'])->name('attendance.store');

    Route::get('dashboard', function () {
        $school = DB::connection(config('database.school_connection'));
        $gradeCount = $school->table('student_grades')->count();
        $passedCount = $school->table('student_grades')
            ->where('pass_fail_status', 'Passed')
            ->count();
        $failedCount = $school->table('student_grades')
            ->where('pass_fail_status', 'Failed')
            ->count();
        $pendingCount = $school->table('student_grades')
            ->whereNull('pass_fail_status')
            ->count();

        $recentGrades = $school->table('student_grades')
            ->join('students', 'student_grades.student_id', '=', 'students.id')
            ->join('subjects', 'student_grades.subject_id', '=', 'subjects.id')
            ->join('sections', 'students.section_id', '=', 'sections.id')
            ->select([
                'student_grades.id',
                'student_grades.student_id',
                'student_grades.subject_id',
                'student_grades.prelim_grade',
                'student_grades.midterm_grade',
                'student_grades.final_grade',
                'students.name as student_name',
                'students.lrn',
                'subjects.subject_name as subject_name',
                'subjects.subject_code as subject_code',
                'students.grade_level',
                'sections.name as section_name',
                'student_grades.pass_fail_status',
            ])
            ->selectRaw('COALESCE(student_grades.computed_gpa, (student_grades.prelim_grade + student_grades.midterm_grade + student_grades.final_grade) / 3) as average_grade')
            ->orderByDesc('student_grades.updated_at')
            ->limit(8)
            ->get();

        $pendingGrades = $school->table('student_grades')
            ->join('students', 'student_grades.student_id', '=', 'students.id')
            ->join('subjects', 'student_grades.subject_id', '=', 'subjects.id')
            ->join('sections', 'students.section_id', '=', 'sections.id')
            ->whereNull('student_grades.pass_fail_status')
            ->select([
                'student_grades.id',
                'student_grades.prelim_grade',
                'student_grades.midterm_grade',
                'student_grades.final_grade',
                'students.name as student_name',
                'students.lrn',
                'students.grade_level',
                'subjects.subject_name as subject_name',
                'subjects.subject_code as subject_code',
                'sections.name as section_name',
            ])
            ->orderByDesc('student_grades.updated_at')
            ->get();

        $gradeOutcomeRecords = $school->table('student_grades')
            ->join('students', 'student_grades.student_id', '=', 'students.id')
            ->join('subjects', 'student_grades.subject_id', '=', 'subjects.id')
            ->join('sections', 'students.section_id', '=', 'sections.id')
            ->whereIn('student_grades.pass_fail_status', ['Passed', 'Failed'])
            ->select([
                'student_grades.id',
                'student_grades.prelim_grade',
                'student_grades.midterm_grade',
                'student_grades.final_grade',
                'student_grades.pass_fail_status',
                'students.name as student_name',
                'students.lrn',
                'students.grade_level',
                'subjects.subject_name as subject_name',
                'subjects.subject_code as subject_code',
                'sections.name as section_name',
            ])
            ->selectRaw('COALESCE(student_grades.computed_gpa, (student_grades.prelim_grade + student_grades.midterm_grade + student_grades.final_grade) / 3) as average_grade')
            ->orderByDesc('student_grades.updated_at')
            ->get();

        $subjectPerformance = $school->table('student_grades')
            ->join('subjects', 'student_grades.subject_id', '=', 'subjects.id')
            ->select('subjects.subject_name as name', 'subjects.subject_code as code')
            ->selectRaw('(SUM(COALESCE(student_grades.prelim_grade, 0) + COALESCE(student_grades.midterm_grade, 0) + COALESCE(student_grades.final_grade, 0)) * 1.0) / NULLIF(COUNT(student_grades.prelim_grade) + COUNT(student_grades.midterm_grade) + COUNT(student_grades.final_grade), 0) as average_grade')
            ->selectRaw('COUNT(student_grades.id) as grade_count')
            ->selectRaw('COUNT(student_grades.prelim_grade) + COUNT(student_grades.midterm_grade) + COUNT(student_grades.final_grade) as assessment_count')
            ->groupBy('subjects.id', 'subjects.subject_name', 'subjects.subject_code')
            ->orderByDesc('average_grade')
            ->limit(5)
            ->get();

        return Inertia::render('dashboard', [
            'stats' => [
                'students' => $school->table('students')->count(),
                'teachers' => $school->table('teachers')->count(),
                'subjects' => $school->table('subjects')->count(),
                'sections' => $school->table('sections')->count(),
                'grades' => $gradeCount,
                'passed' => $passedCount,
                'failed' => $failedCount,
                'pending' => $pendingCount,
                'passRate' => $passedCount + $failedCount > 0
                    ? round(($passedCount / ($passedCount + $failedCount)) * 100)
                    : 0,
            ],
            'recentGrades' => $recentGrades,
            'pendingGrades' => $pendingGrades,
            'gradeOutcomeRecords' => $gradeOutcomeRecords,
            'subjectPerformance' => $subjectPerformance,
            'options' => [
                'students' => $school->table('students')
                    ->join('sections', 'students.section_id', '=', 'sections.id')
                    ->orderBy('students.name')
                    ->get([
                        'students.id',
                        'students.name',
                        'students.lrn',
                        'students.grade_level',
                        'students.section_id',
                        'sections.name as section_name',
                    ]),
                'teachers' => $school->table('teachers')->orderBy('name')->get(['id', 'name']),
                'subjects' => $school->table('subjects')
                    ->leftJoin('teachers', 'subjects.teacher_id', '=', 'teachers.id')
                    ->orderBy('subjects.subject_name')
                    ->get([
                        'subjects.id',
                        'subjects.subject_name as name',
                        'subjects.subject_code as code',
                        'subjects.teacher_id',
                        'teachers.name as teacher_name',
                    ]),
                'sections' => $school->table('sections')->orderBy('name')->get(['id', 'name']),
            ],
        ]);
    })->name('dashboard');

    Route::prefix('dashboard/records')->name('dashboard.records.')->controller(DashboardRecordController::class)->group(function () {
        Route::post('students', 'storeStudent')->name('students.store');
        Route::patch('students/{student}', 'updateStudent')->name('students.update');
        Route::delete('students/{student}', 'destroyStudent')->name('students.destroy');
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
