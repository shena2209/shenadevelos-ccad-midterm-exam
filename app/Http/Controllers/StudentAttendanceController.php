<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceIndexRequest;
use App\Http\Requests\StoreStudentAttendanceRequest;
use App\Services\StudentAttendanceService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StudentAttendanceController extends Controller
{
    public function __construct(private readonly StudentAttendanceService $studentAttendanceService) {}

    public function index(AttendanceIndexRequest $request): Response
    {
        $date = $request->validated('date');

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
