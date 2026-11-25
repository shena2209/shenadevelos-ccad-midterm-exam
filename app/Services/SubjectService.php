<?php

namespace App\Services;

use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Connection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SubjectService
{
    public function paginate(): LengthAwarePaginator
    {
        return Subject::query()->with('teacher')->orderBy('subject_name')->paginate(15);
    }

    public function dependencies(): array
    {
        return ['teachers' => Teacher::query()->orderBy('name')->get(['id', 'name'])];
    }

    public function create(array $data): Subject
    {
        return $this->schoolConnection()->transaction(
            fn () => Subject::create($this->sanitize($data))
        )->load('teacher');
    }

    public function update(int $id, array $data): Subject
    {
        return $this->schoolConnection()->transaction(function () use ($id, $data) {
            $subject = Subject::findOrFail($id);
            $subject->update($this->sanitize($data));

            return $subject->load('teacher');
        });
    }

    private function sanitize(array $data): array
    {
        return [
            'subject_code' => $this->sanitizeString($data['subject_code'] ?? $data['code'] ?? ''),
            'subject_name' => $this->sanitizeString($data['subject_name'] ?? $data['name'] ?? ''),
            'teacher_id' => $data['teacher_id'] ?? null,
        ];
    }

    private function sanitizeString(string $value): string
    {
        return trim(strip_tags($value));
    }

    private function schoolConnection(): Connection
    {
        return DB::connection(config('database.school_connection'));
    }
}
