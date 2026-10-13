<?php

declare(strict_types=1);

namespace App\Library\Application\Exceptions;

final class DuplicateBookInLoanException extends ApplicationException
{
    public function __construct(string $message = 'El mismo libro no puede repetirse dentro de un préstamo.')
    {
        parent::__construct('DUPLICATE_BOOK_IN_LOAN', $message);
    }
}
