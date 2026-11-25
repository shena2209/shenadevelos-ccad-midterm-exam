<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDashboardGradeRequest;
use App\Http\Requests\StoreDashboardSectionRequest;
use App\Http\Requests\StoreDashboardStudentRequest;
use App\Http\Requests\StoreDashboardSubjectRequest;
use App\Http\Requests\StoreDashboardTeacherRequest;
use App\Http\Requests\UpdateDashboardGradeRequest;
use App\Http\Requests\UpdateDashboardSectionRequest;
use App\Http\Requests\UpdateDashboardStudentRequest;
use App\Http\Requests\UpdateDashboardSubjectRequest;
use App\Http\Requests\UpdateDashboardTeacherRequest;
use App\Services\DashboardRecordService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardRecordController extends Controller
{
    public function __construct(private readonly DashboardRecordService $dashboardRecordService) {}

    public function index(): Response
    {
        return Inertia::render('dashboard', $this->dashboardRecordService->dashboardData());
    }

    public function storeStudent(StoreDashboardStudentRequest $request): RedirectResponse
    {
        $this->dashboardRecordService->createStudent($request->validated());

        return to_route('dashboard');
    }

    public function storeTeacher(StoreDashboardTeacherRequest $request): RedirectResponse
    {
        $this->dashboardRecordService->createTeacher($request->validated());

        return to_route('dashboard');
    }

    public function storeSubject(StoreDashboardSubjectRequest $request): RedirectResponse
    {
        $this->dashboardRecordService->createSubject($request->validated());

        return to_route('dashboard');
    }

    public function storeSection(StoreDashboardSectionRequest $request): RedirectResponse
    {
        $this->dashboardRecordService->createSection($request->validated());

        return to_route('dashboard');
    }

    public function updateStudent(UpdateDashboardStudentRequest $request, int $student): RedirectResponse
    {
        $this->dashboardRecordService->updateStudent($student, $request->validated());

        return back();
    }

    public function updateTeacher(UpdateDashboardTeacherRequest $request, int $teacher): RedirectResponse
    {
        $this->dashboardRecordService->updateTeacher($teacher, $request->validated());

        return back();
    }

    public function updateSubject(UpdateDashboardSubjectRequest $request, int $subject): RedirectResponse
    {
        $this->dashboardRecordService->updateSubject($subject, $request->validated());

        return back();
    }

    public function updateSection(UpdateDashboardSectionRequest $request, int $section): RedirectResponse
    {
        $this->dashboardRecordService->updateSection($section, $request->validated());

        return back();
    }

    public function destroyStudent(int $student): RedirectResponse
    {
        $this->dashboardRecordService->deleteStudent($student);

        return back();
    }

    public function destroyTeacher(int $teacher): RedirectResponse
    {
        $this->dashboardRecordService->deleteTeacher($teacher);

        return back();
    }

    public function destroySubject(int $subject): RedirectResponse
    {
        $this->dashboardRecordService->deleteSubject($subject);

        return back();
    }

    public function destroySection(int $section): RedirectResponse
    {
        $this->dashboardRecordService->deleteSection($section);

        return back();
    }

    public function storeGrade(StoreDashboardGradeRequest $request): RedirectResponse
    {
        $this->dashboardRecordService->createGrade($request->validated());

        return to_route('dashboard');
    }

    public function updateGrade(UpdateDashboardGradeRequest $request, int $grade): RedirectResponse
    {
        $this->dashboardRecordService->updateGrade($grade, $request->validated());

        return to_route('dashboard');
    }

    public function destroyGrade(int $grade): RedirectResponse
    {
        $this->dashboardRecordService->deleteGrade($grade);

        return to_route('dashboard');
    }
}
