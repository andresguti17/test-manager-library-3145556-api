<?php

declare(strict_types=1);

namespace App\Library\Domain\ValueObjects;

use App\Library\Domain\Exceptions\InvalidQuantityException;

/** Positive quantity, e.g. total copies of a book (≥ 1, CA-03.3). */
final readonly class Quantity
{
    public function __construct(public int $value)
    {
        if ($value < 1) {
            throw new InvalidQuantityException();
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
