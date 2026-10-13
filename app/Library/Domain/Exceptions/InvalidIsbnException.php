<?php

declare(strict_types=1);

namespace App\Library\Domain\Exceptions;

final class InvalidIsbnException extends DomainException
{
    public function __construct(string $message = 'El ISBN debe tener 13 dígitos.')
    {
        parent::__construct('VALIDATION_ERROR', $message);
    }
}
