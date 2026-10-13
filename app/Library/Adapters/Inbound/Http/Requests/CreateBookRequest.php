<?php

declare(strict_types=1);

namespace App\Library\Adapters\Inbound\Http\Requests;

final class CreateBookRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:250'],
            'author' => ['required', 'string', 'max:200'],
            'isbn' => ['required', 'string', 'regex:/^\d{13}$/'],
            'category_id' => ['required', 'uuid'],
            'total_copies' => ['required', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'author.required' => 'El autor es obligatorio.',
            'isbn.required' => 'El ISBN es obligatorio.',
            'isbn.regex' => 'El ISBN debe tener exactamente 13 dígitos.',
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.uuid' => 'La categoría no es válida.',
            'total_copies.required' => 'El total de ejemplares es obligatorio.',
            'total_copies.integer' => 'El total de ejemplares debe ser un número entero.',
            'total_copies.min' => 'El total de ejemplares debe ser mayor o igual a 1.',
        ];
    }
}
