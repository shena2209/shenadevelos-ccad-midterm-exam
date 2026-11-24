<?php

namespace App\Http\Controllers;

use App\Services\DashboardRecordService;
use App\Services\SchoolRecordsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class SchoolRecordsController extends Controller
{
    public function __construct(
        private readonly SchoolRecordsService $schoolRecordsService,
        private readonly DashboardRecordService $dashboardRecordService,
    ) {}

    public function students(): Response
    {
        return $this->render('Students', $this->schoolRecordsService->students(), [
            'sections' => $this->schoolRecordsService->studentSections(),
        ]);
    }

    public function teachers(): Response
    {
        return $this->render('Teachers', $this->schoolRecordsService->teachers());
    }

    public function subjects(): Response
    {
        return $this->render('Subjects', $this->schoolRecordsService->subjects(), [
            'teachers' => $this->schoolRecordsService->subjectTeachers(),
        ]);
    }

    public function sections(): Response
    {
        return $this->render('Sections', $this->schoolRecordsService->sections());
    }

    public function updateTeacher(Request $request, int $teacher): RedirectResponse
    {
        $this->dashboardRecordService->updateTeacher($teacher, $request->all());

        return to_route('school-records.teachers');
    }

    public function destroyTeacher(int $teacher): RedirectResponse
    {
        $this->dashboardRecordService->deleteTeacher($teacher);

        return to_route('school-records.teachers');
    }

    public function updateSubject(Request $request, int $subject): RedirectResponse
    {
        $this->dashboardRecordService->updateSubject($subject, $request->all());

        return to_route('school-records.subjects');
    }

    public function destroySubject(int $subject): RedirectResponse
    {
        $this->dashboardRecordService->deleteSubject($subject);

        return to_route('school-records.subjects');
    }

    public function updateSection(Request $request, int $section): RedirectResponse
    {
        $this->dashboardRecordService->updateSection($section, $request->all());

        return to_route('school-records.sections');
    }

    public function destroySection(int $section): RedirectResponse
    {
        $this->dashboardRecordService->deleteSection($section);

        return to_route('school-records.sections');
    }

    private function render(string $title, Collection $records, array $props = []): Response
    {
        return Inertia::render('school-records', [
            'title' => $title,
            'records' => $records,
            ...$props,
        ]);
    }
}
