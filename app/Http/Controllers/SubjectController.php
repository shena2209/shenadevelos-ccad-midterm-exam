<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubjectRequest;
use App\Http\Requests\UpdateSubjectRequest;
use App\Services\SubjectService;
use Illuminate\Http\JsonResponse;

class SubjectController extends Controller
{
    public function __construct(private readonly SubjectService $subjectService) {}

    public function index(): JsonResponse
    {
        return response()->json($this->subjectService->paginate());
    }

    public function create(): JsonResponse
    {
        return response()->json($this->subjectService->dependencies());
    }

    public function store(StoreSubjectRequest $request): JsonResponse
    {
        $subject = $this->subjectService->create($request->validated());

        return response()->json($subject, 201);
    }

    public function update(UpdateSubjectRequest $request, string $id): JsonResponse
    {
        return response()->json($this->subjectService->update((int) $id, $request->validated()));
    }
}
