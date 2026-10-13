<?php

declare(strict_types=1);

namespace App\Library\Adapters\Inbound\Http\Requests;

final class ListLoansRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:open,overdue,closed'],
            'member_id' => ['nullable', 'uuid'],
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'from.date' => 'La fecha de inicio no es válida.',
            'to.date' => 'La fecha de fin no es válida.',
            'status.in' => 'El estado debe ser «open», «overdue» o «closed».',
            'member_id.uuid' => 'El miembro no es válido.',
            'page.integer' => 'La página debe ser un número entero.',
            'page.min' => 'La página debe ser mayor o igual a 1.',
            'size.integer' => 'El tamaño debe ser un número entero.',
            'size.min' => 'El tamaño debe ser mayor o igual a 1.',
            'size.max' => 'El tamaño no puede superar 50.',
        ];
    }
}
