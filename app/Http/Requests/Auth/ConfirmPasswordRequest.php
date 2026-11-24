<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ConfirmPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['password' => ['required', 'string']];
    }

    public function confirmPassword(): void
    {
        if (! Auth::guard('web')->validate([
            'email' => $this->user()->email,
            'password' => $this->validated('password'),
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $this->session()->put('auth.password_confirmed_at', time());
    }
}
