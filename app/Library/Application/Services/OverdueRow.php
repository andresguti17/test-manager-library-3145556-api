<?php

declare(strict_types=1);

namespace App\Library\Application\Services;

use DateTimeImmutable;

final readonly class OverdueRow
{
    public function __construct(
        public string $loanId,
        public string $itemId,
        public string $bookTitle,
        public string $memberId,
        public string $memberName,
        public DateTimeImmutable $dueAt,
        public int $daysLate,
    ) {}
}
