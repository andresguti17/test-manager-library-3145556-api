<?php

declare(strict_types=1);

namespace App\Library\Domain\ValueObjects;

use App\Library\Domain\Exceptions\InvalidIsbnException;

/** ISBN of exactly 13 digits (CA-03.2). */
final readonly class Isbn
{
    public function __construct(public string $value)
    {
        if (!preg_match('/^\d{13}$/', $value)) {
            throw new InvalidIsbnException();
        }
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
