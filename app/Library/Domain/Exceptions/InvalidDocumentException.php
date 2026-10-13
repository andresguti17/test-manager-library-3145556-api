<?php

declare(strict_types=1);

namespace App\Library\Domain\Exceptions;

final class InvalidDocumentException extends DomainException
{
    public function __construct(string $message = 'El documento es obligatorio.')
    {
        parent::__construct('VALIDATION_ERROR', $message);
    }
}
