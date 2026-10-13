<?php

declare(strict_types=1);

namespace App\Library\Application\Exceptions;

final class InvalidCredentialsException extends ApplicationException
{
    public function __construct(string $message = 'Credenciales inválidas.')
    {
        parent::__construct('INVALID_CREDENTIALS', $message);
    }
}
