<?php

namespace App\Services;

use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
        $data = $this->sanitize($data);
        $data = $this->validate($data, [
            'subject_code' => ['required', 'string', 'max:20', Rule::unique($this->schoolTable('subjects'), 'subject_code')],
            'subject_name' => ['required', 'string', 'max:255'],
            'teacher_id' => ['nullable', 'integer', Rule::exists($this->schoolTable('teachers'), 'id')],
        ]);

        return DB::transaction(fn () => Subject::create($data)->load('teacher'));
    }

    public function update(int $id, array $data): Subject
    {
        $data = $this->sanitize($data);
        $data = $this->validate($data, [
            'subject_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique($this->schoolTable('subjects'), 'subject_code')->ignore($id),
            ],
            'subject_name' => ['required', 'string', 'max:255'],
            'teacher_id' => ['nullable', 'integer', Rule::exists($this->schoolTable('teachers'), 'id')],
        ]);

        return DB::transaction(function () use ($id, $data) {
            $subject = Subject::findOrFail($id);
            $subject->update($data);

            return $subject->load('teacher');
        });
    }

    private function sanitize(array $data): array
    {
        return [
            'subject_code' => $this->sanitizeString($data['subject_code'] ?? $data['code'] ?? ''),
            'subject_name' => $this->sanitizeString($data['subject_name'] ?? $data['name'] ?? ''),
            'teacher_id' => ($data['teacher_id'] ?? null) === '' ? null : ($data['teacher_id'] ?? null),
        ];
    }

    private function sanitizeString(mixed $value): mixed
    {
        return is_string($value) ? trim(strip_tags($value)) : $value;
    }

    private function validate(array $data, array $rules): array
    {
        return Validator::make($data, $rules)->validate();
    }

    private function schoolTable(string $table): string
    {
        return config('database.school_connection').'.'.$table;
    }

}
