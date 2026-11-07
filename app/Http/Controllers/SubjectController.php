<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubjectRequest;
use App\Http\Requests\UpdateSubjectRequest;
use App\Models\Subject;
use App\Services\SchoolService;
use Illuminate\Http\JsonResponse;

class SubjectController extends Controller
{
    public function __construct(private readonly SchoolService $schoolService) {}

    public function index(): JsonResponse
    {
        return response()->json(
            Subject::query()->with('teacher')->orderBy('subject_name')->paginate(15)
        );
    }

    public function create(): JsonResponse
    {
        return response()->json($this->schoolService->subjectDependencies());
    }

    public function store(StoreSubjectRequest $request): JsonResponse
    {
        $subject = $this->schoolService->createSubject($request->validated());

        return response()->json($subject->load('teacher'), 201);
    }

    public function update(UpdateSubjectRequest $request, string $id): JsonResponse
    {
        return response()->json($this->schoolService->updateSubject((int) $id, $request->validated()));
    }
}
