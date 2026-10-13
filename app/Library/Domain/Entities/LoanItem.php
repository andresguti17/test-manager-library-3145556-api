<?php

declare(strict_types=1);

namespace App\Library\Domain\Entities;

use App\Library\Domain\Exceptions\ItemAlreadyReturnedException;
use DateTimeImmutable;

/**
 * One book within a loan.
 *
 * Freezes title, ISBN and category name at loan time (RN-4, Article VIII):
 * later catalog renames never rewrite these values.
 */
final class LoanItem
{
    private ?DateTimeImmutable $returnedAt;

    public function __construct(
        public readonly string $id,
        public readonly string $bookId,
        public readonly string $bookTitle,
        public readonly string $isbn,
        public readonly string $categoryName,
        public readonly DateTimeImmutable $dueAt,
        ?DateTimeImmutable $returnedAt = null,
    ) {
        $this->returnedAt = $returnedAt;
    }

    public function returnedAt(): ?DateTimeImmutable
    {
        return $this->returnedAt;
    }

    public function isOpen(): bool
    {
        return $this->returnedAt === null;
    }

    public function isOverdue(DateTimeImmutable $now): bool
    {
        return $this->isOpen() && $this->dueAt < $now;
    }

    /** Sets returned_at exactly once (CA-07.2): never adds twice. */
    public function markReturned(DateTimeImmutable $now): void
    {
        if ($this->returnedAt !== null) {
            throw new ItemAlreadyReturnedException();
        }
        $this->returnedAt = $now;
    }

    /** Whole days between due_at and now (0 when not overdue). */
    public function daysLate(DateTimeImmutable $now): int
    {
        if (!$this->isOverdue($now)) {
            return 0;
        }
        return (int) $this->dueAt->diff($now)->format('%a');
    }
}
