<?php

declare(strict_types=1);

namespace App\Library\Adapters\Inbound\Http\Requests;

final class SearchBooksRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string'],
            'category_id' => ['nullable', 'uuid'],
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'category_id.uuid' => 'La categoría no es válida.',
            'page.integer' => 'La página debe ser un número entero.',
            'page.min' => 'La página debe ser mayor o igual a 1.',
            'size.integer' => 'El tamaño debe ser un número entero.',
            'size.min' => 'El tamaño debe ser mayor o igual a 1.',
            'size.max' => 'El tamaño no puede superar 50.',
        ];
    }
}
