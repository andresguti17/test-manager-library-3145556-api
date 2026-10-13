<?php

declare(strict_types=1);

namespace App\Library\Domain\Exceptions;

final class InvalidDateRangeException extends DomainException
{
    public function __construct(string $message = 'La fecha de fin no puede ser anterior a la de inicio.')
    {
        parent::__construct('INVALID_DATE_RANGE', $message);
    }
}
