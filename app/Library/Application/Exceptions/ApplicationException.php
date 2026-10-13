<?php

declare(strict_types=1);

namespace App\Library\Application\Exceptions;

/**
 * Base error for application use cases.
 *
 * Carries the stable contract code (e.g. BOOK_NOT_AVAILABLE); only the
 * HTTP adapter translates it into status + body (architecture §5).
 */
abstract class ApplicationException extends \RuntimeException
{
    public function __construct(
        private readonly string $errorCode,
        string $message,
    ) {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }
}
