<?php

declare(strict_types=1);

namespace App\Library\Domain\Exceptions;

final class BookNotAvailableException extends DomainException
{
    public function __construct(string $message = 'No quedan ejemplares disponibles.')
    {
        parent::__construct('BOOK_NOT_AVAILABLE', $message);
    }
}
