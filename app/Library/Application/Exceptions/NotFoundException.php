<?php

declare(strict_types=1);

namespace App\Library\Application\Exceptions;

final class NotFoundException extends ApplicationException
{
    public function __construct(string $message = 'El recurso no existe.')
    {
        parent::__construct('NOT_FOUND', $message);
    }
}
