<?php

declare(strict_types=1);

namespace App\Library\Application\Exceptions;

final class DocumentTakenException extends ApplicationException
{
    public function __construct(string $message = 'El documento ya está registrado en otro miembro.')
    {
        parent::__construct('DOCUMENT_TAKEN', $message);
    }
}
