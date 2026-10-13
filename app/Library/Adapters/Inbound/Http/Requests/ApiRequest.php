<?php

declare(strict_types=1);

namespace App\Library\Adapters\Inbound\Http\Requests;

use App\Library\Adapters\Inbound\Http\Support\LibraryExceptionHandler;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/** Base request: validation failures use the contract 422 shape. */
abstract class ApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        $details = [];
        foreach ($validator->errors()->toArray() as $field => $issues) {
            foreach ((array) $issues as $issue) {
                $details[] = ['field' => (string) $field, 'issue' => (string) $issue];
            }
        }
        throw new HttpResponseException(
            LibraryExceptionHandler::body('VALIDATION_ERROR', 'La solicitud contiene campos inválidos.', 422, $details),
        );
    }
}
