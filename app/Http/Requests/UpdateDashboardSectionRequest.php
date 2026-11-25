<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDashboardSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $schoolConnection = config('database.school_connection');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique("{$schoolConnection}.sections", 'name')->ignore($this->route('section')),
            ],
        ];
    }
}
