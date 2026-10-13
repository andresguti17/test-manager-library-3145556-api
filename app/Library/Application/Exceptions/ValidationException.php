<?php

declare(strict_types=1);

namespace App\Library\Application\Exceptions;

final class ValidationException extends ApplicationException
{
    public function __construct(string $message)
    {
        parent::__construct('VALIDATION_ERROR', $message);
    }
}
