<?php

declare(strict_types=1);

namespace App\Library\Adapters\Inbound\Http\Requests;

final class UploadCoverRequest extends ApiRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:2048'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'file.required' => 'La portada es obligatoria.',
            'file.file' => 'La portada debe ser un archivo.',
            'file.max' => 'La portada no puede superar los 2 MB.',
        ];
    }
}
