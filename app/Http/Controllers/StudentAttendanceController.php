<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentAttendanceRequest;
use App\Services\StudentAttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentAttendanceController extends Controller
{
    public function __construct(private readonly StudentAttendanceService $studentAttendanceService) {}

    public function index(Request $request): Response
    {
        $date = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ])['date'] ?? now()->toDateString();

        $attendance = $this->studentAttendanceService->dailyAttendance($date);

        return Inertia::render('attendance', [
            'date' => $date,
            ...$attendance,
            'statuses' => ['Present', 'Absent', 'Late', 'Excused'],
        ]);
    }

    public function store(StoreStudentAttendanceRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $this->studentAttendanceService->saveAttendance($validated['date'], $validated['records']);

        return to_route('attendance.index', ['date' => $validated['date']])
            ->with('success', 'Attendance saved successfully.');
    }
}
