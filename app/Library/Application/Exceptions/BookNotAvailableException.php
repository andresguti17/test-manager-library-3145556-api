<?php

declare(strict_types=1);

namespace App\Library\Application\Exceptions;

final class BookNotAvailableException extends ApplicationException
{
    public function __construct(string $message = 'No quedan ejemplares disponibles.')
    {
        parent::__construct('BOOK_NOT_AVAILABLE', $message);
    }
}
