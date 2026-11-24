<?php

namespace App\Http\Controllers;

use App\Services\DashboardRecordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardRecordController extends Controller
{
    public function __construct(private readonly DashboardRecordService $dashboardRecordService) {}

    public function storeStudent(Request $request): RedirectResponse
    {
        $this->dashboardRecordService->createStudent($request->all());

        return to_route('dashboard');
    }

    public function storeTeacher(Request $request): RedirectResponse
    {
        $this->dashboardRecordService->createTeacher($request->all());

        return to_route('dashboard');
    }

    public function storeSubject(Request $request): RedirectResponse
    {
        $this->dashboardRecordService->createSubject($request->all());

        return to_route('dashboard');
    }

    public function storeSection(Request $request): RedirectResponse
    {
        $this->dashboardRecordService->createSection($request->all());

        return to_route('dashboard');
    }

    public function storeGrade(Request $request): RedirectResponse
    {
        $this->dashboardRecordService->createGrade($request->all());

        return to_route('dashboard');
    }

    public function updateGrade(Request $request, int $grade): RedirectResponse
    {
        $this->dashboardRecordService->updateGrade($grade, $request->all());

        return to_route('dashboard');
    }

    public function destroyGrade(int $grade): RedirectResponse
    {
        $this->dashboardRecordService->deleteGrade($grade);

        return to_route('dashboard');
    }
}
