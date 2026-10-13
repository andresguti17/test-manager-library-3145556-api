<?php

declare(strict_types=1);

namespace App\Library\Application\Exceptions;

final class IsbnTakenException extends ApplicationException
{
    public function __construct(string $message = 'El ISBN ya está registrado en otro libro activo.')
    {
        parent::__construct('ISBN_TAKEN', $message);
    }
}
