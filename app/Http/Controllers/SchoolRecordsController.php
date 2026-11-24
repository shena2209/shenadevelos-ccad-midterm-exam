<?php

namespace App\Http\Controllers;

use App\Services\SchoolRecordsService;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class SchoolRecordsController extends Controller
{
    public function __construct(private readonly SchoolRecordsService $schoolRecordsService) {}

    public function students(): Response
    {
        return $this->render('Students', $this->schoolRecordsService->students());
    }

    public function teachers(): Response
    {
        return $this->render('Teachers', $this->schoolRecordsService->teachers());
    }

    public function subjects(): Response
    {
        return $this->render('Subjects', $this->schoolRecordsService->subjects());
    }

    public function sections(): Response
    {
        return $this->render('Sections', $this->schoolRecordsService->sections());
    }

    private function render(string $title, Collection $records): Response
    {
        return Inertia::render('school-records', [
            'title' => $title,
            'records' => $records,
        ]);
    }
}
