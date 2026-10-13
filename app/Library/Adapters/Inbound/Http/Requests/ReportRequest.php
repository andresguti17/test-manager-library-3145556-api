<?php

declare(strict_types=1);

namespace App\Library\Adapters\Inbound\Http\Requests;

final class ReportRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'from' => ['required', 'date'],
            'to' => ['required', 'date'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'from.required' => 'La fecha de inicio es obligatoria.',
            'from.date' => 'La fecha de inicio no es válida.',
            'to.required' => 'La fecha de fin es obligatoria.',
            'to.date' => 'La fecha de fin no es válida.',
        ];
    }
}
