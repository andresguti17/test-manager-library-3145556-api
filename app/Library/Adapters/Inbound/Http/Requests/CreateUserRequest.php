<?php

declare(strict_types=1);

namespace App\Library\Adapters\Inbound\Http\Requests;

final class CreateUserRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string'],
            'role' => ['required', 'string', 'in:librarian'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'username.required' => 'El nombre de usuario es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
            'role.required' => 'El rol es obligatorio.',
            'role.in' => 'El rol debe ser «librarian».',
        ];
    }
}
