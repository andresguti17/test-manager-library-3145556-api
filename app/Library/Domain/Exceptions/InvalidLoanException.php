<?php

declare(strict_types=1);

namespace App\Library\Domain\Exceptions;

final class InvalidLoanException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct('VALIDATION_ERROR', $message);
    }
}
