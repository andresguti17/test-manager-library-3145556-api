<?php

declare(strict_types=1);

namespace App\Library\Domain\Exceptions;

final class InvalidQuantityException extends DomainException
{
    public function __construct(string $message = 'La cantidad debe ser mayor o igual a 1.')
    {
        parent::__construct('VALIDATION_ERROR', $message);
    }
}
