<?php

declare(strict_types=1);

namespace App\Library\Application\Exceptions;

final class BookHasOpenLoansException extends ApplicationException
{
    public function __construct(string $message = 'El libro tiene préstamos abiertos y no puede eliminarse.')
    {
        parent::__construct('BOOK_HAS_OPEN_LOANS', $message);
    }
}
