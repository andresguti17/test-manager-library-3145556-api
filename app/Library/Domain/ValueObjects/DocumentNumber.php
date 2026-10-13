<?php

declare(strict_types=1);

namespace App\Library\Domain\ValueObjects;

use App\Library\Domain\Exceptions\InvalidDocumentException;

/** Member identity document: mandatory, trimmed, never empty (CA-05.1). */
final readonly class DocumentNumber
{
    public readonly string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new InvalidDocumentException();
        }
        $this->value = $trimmed;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
