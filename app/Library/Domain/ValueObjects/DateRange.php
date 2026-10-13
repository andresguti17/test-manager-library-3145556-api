<?php

declare(strict_types=1);

namespace App\Library\Domain\ValueObjects;

use App\Library\Domain\Exceptions\InvalidDateRangeException;
use DateTimeImmutable;

/** Closed UTC date range; end must not precede start (CA-10.2). */
final readonly class DateRange
{
    public function __construct(
        public DateTimeImmutable $start,
        public DateTimeImmutable $end,
    ) {
        if ($end < $start) {
            throw new InvalidDateRangeException();
        }
    }

    public function contains(DateTimeImmutable $instant): bool
    {
        return $instant >= $this->start && $instant <= $this->end;
    }
}
