<?php

declare(strict_types=1);

namespace App\Library\Adapters\Inbound\Http\Requests;

final class CreateMemberRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:200'],
            'document_number' => ['required', 'string', 'max:30'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'full_name.required' => 'El nombre del miembro es obligatorio.',
            'document_number.required' => 'El documento es obligatorio.',
        ];
    }
}
