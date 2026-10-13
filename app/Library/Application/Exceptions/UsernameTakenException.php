<?php

declare(strict_types=1);

namespace App\Library\Application\Exceptions;

final class UsernameTakenException extends ApplicationException
{
    public function __construct(string $message = 'El nombre de usuario ya está en uso.')
    {
        parent::__construct('USERNAME_TAKEN', $message);
    }
}
