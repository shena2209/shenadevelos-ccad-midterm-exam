<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentGradeRequest;
use App\Http\Requests\UpdateStudentGradeRequest;
use App\Services\StudentGradeService;
use Illuminate\Http\JsonResponse;

class StudentGradeController extends Controller
{
    public function __construct(private readonly StudentGradeService $studentGradeService) {}

    public function index(): JsonResponse
    {
        return response()->json($this->studentGradeService->paginate());
    }

    public function create(): JsonResponse
    {
        return response()->json($this->studentGradeService->dependencies());
    }

    public function store(StoreStudentGradeRequest $request): JsonResponse
    {
        $studentGrade = $this->studentGradeService->create($request->validated());

        return response()->json($studentGrade, 201);
    }

    public function update(UpdateStudentGradeRequest $request, string $id): JsonResponse
    {
        return response()->json($this->studentGradeService->update((int) $id, $request->validated()));
    }
}
