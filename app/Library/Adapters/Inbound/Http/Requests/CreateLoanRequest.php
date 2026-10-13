<?php

declare(strict_types=1);

namespace App\Library\Adapters\Inbound\Http\Requests;

final class CreateLoanRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'member_id' => ['required', 'uuid'],
            'items' => ['required', 'array', 'min:1', 'max:3'],
            'items.*.book_id' => ['required', 'uuid'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'member_id.required' => 'El miembro es obligatorio.',
            'member_id.uuid' => 'El miembro no es válido.',
            'items.required' => 'El préstamo lleva de 1 a 3 libros.',
            'items.min' => 'El préstamo lleva de 1 a 3 libros.',
            'items.max' => 'El préstamo lleva de 1 a 3 libros.',
            'items.*.book_id.required' => 'Cada línea debe indicar el libro.',
            'items.*.book_id.uuid' => 'El libro no es válido.',
        ];
    }
}
