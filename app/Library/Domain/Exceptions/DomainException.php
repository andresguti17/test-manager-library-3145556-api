<?php

declare(strict_types=1);

namespace App\Library\Domain\Exceptions;

/**
 * Base error for domain invariant violations.
 *
 * Carries a stable error code (contract vocabulary, e.g. BOOK_NOT_AVAILABLE)
 * so the HTTP adapter can map it without parsing messages.
 * User-facing messages are in Spanish (RN-6).
 */
abstract class DomainException extends \DomainException
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
