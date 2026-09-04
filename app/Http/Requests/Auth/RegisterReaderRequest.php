<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class RegisterReaderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'string', 'email', 'max:190', $this->emailAvailable()],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.min' => 'Choose a password of at least 12 characters.',
        ];
    }

    private function emailAvailable(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $email = strtolower($value);
            $closed = User::onlyTrashed()->where('email', $email)->exists();

            if ($closed) {
                $fail('This account was closed. Contact the library administrator to reopen it.');
            } elseif (User::where('email', $email)->exists()) {
                $fail('An account with this email already exists — sign in instead.');
            }
        };
    }
}
