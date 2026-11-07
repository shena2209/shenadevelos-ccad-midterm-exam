<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentGradeRequest;
use App\Http\Requests\UpdateStudentGradeRequest;
use App\Models\StudentGrade;
use App\Services\SchoolService;
use Illuminate\Http\JsonResponse;

class StudentGradeController extends Controller
{
    public function __construct(private readonly SchoolService $schoolService) {}

    public function index(): JsonResponse
    {
        return response()->json(
            StudentGrade::query()
                ->with(['student.section', 'subject.teacher'])
                ->latest()
                ->paginate(15)
        );
    }

    public function create(): JsonResponse
    {
        return response()->json($this->schoolService->studentGradeDependencies());
    }

    public function store(StoreStudentGradeRequest $request): JsonResponse
    {
        $studentGrade = $this->schoolService->createStudentGrade($request->validated());

        return response()->json($studentGrade->load(['student.section', 'subject.teacher']), 201);
    }

    public function update(UpdateStudentGradeRequest $request, string $id): JsonResponse
    {
        return response()->json($this->schoolService->updateStudentGrade((int) $id, $request->validated()));
    }
}
