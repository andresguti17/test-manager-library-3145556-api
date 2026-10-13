<?php

declare(strict_types=1);

namespace App\Library\Domain\Entities;

use App\Library\Domain\Enums\LoanStatus;
use App\Library\Domain\Exceptions\DuplicateBookInLoanException;
use App\Library\Domain\Exceptions\InvalidBookException;
use App\Library\Domain\Exceptions\InvalidLoanException;
use DateTimeImmutable;

/**
 * Loan aggregate root: one member, 1–3 lines, no repeated book (CA-06).
 *
 * AddItem calls Book::withdraw before adding the line: deducting the copy
 * and creating the line are a single operation. ReturnItem restocks once.
 * Status is computed, never stored (Article VII).
 */
final class Loan
{
    public const MAX_ITEMS = 3;
    public const LOAN_DAYS = 14;
    public const MAX_OPEN_BOOKS_PER_MEMBER = 3;

    /** @var list<LoanItem> */
    private array $items = [];

    /**
     * @param list<LoanItem> $items
     */
    public function __construct(
        public readonly string $id,
        public readonly string $memberId,
        public readonly string $loanedByUserId,
        public readonly DateTimeImmutable $loanedAt,
        array $items = [],
    ) {
        foreach ($items as $item) {
            $this->addExistingItem($item);
        }
    }

    /** @return list<LoanItem> */
    public function items(): array
    {
        return array_values($this->items);
    }

    public function findItem(string $itemId): ?LoanItem
    {
        foreach ($this->items as $item) {
            if ($item->id === $itemId) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Adds one line for the book, withdrawing a copy first.
     *
     * @throws DuplicateBookInLoanException when the book is already in the loan (CA-06.2)
     * @throws InvalidLoanException when the loan already has 3 lines (CA-06.1)
     */
    public function addItem(Book $book, string $itemId): LoanItem
    {
        if (count($this->items) >= self::MAX_ITEMS) {
            throw new InvalidLoanException('Un préstamo lleva de 1 a 3 líneas.');
        }
        foreach ($this->items as $existing) {
            if ($existing->bookId === $book->id) {
                throw new DuplicateBookInLoanException();
            }
        }
        $book->withdraw();
        $item = new LoanItem(
            id: $itemId,
            bookId: $book->id,
            bookTitle: $book->title(),
            isbn: (string) $book->isbn,
            categoryName: $book->categoryName(),
            dueAt: $this->loanedAt->modify(sprintf('+%d days', self::LOAN_DAYS)),
        );
        $this->items[] = $item;
        return $item;
    }

    /**
     * Returns one line: sets returned_at once and restocks the book once
     * (CA-07.1, CA-07.2).
     */
    public function returnItem(string $itemId, Book $book, DateTimeImmutable $now): LoanItem
    {
        $item = $this->findItem($itemId);
        if ($item === null) {
            throw new InvalidLoanException('La línea no pertenece a este préstamo.');
        }
        if ($item->bookId !== $book->id) {
            throw new InvalidBookException('El libro no corresponde a la línea del préstamo.');
        }
        $item->markReturned($now);
        $book->restock();
        return $item;
    }

    public function status(DateTimeImmutable $now): LoanStatus
    {
        $hasOpen = false;
        foreach ($this->items as $item) {
            if ($item->isOpen()) {
                $hasOpen = true;
                if ($item->isOverdue($now)) {
                    return LoanStatus::Overdue;
                }
            }
        }
        return $hasOpen ? LoanStatus::Open : LoanStatus::Closed;
    }

    /** @return list<LoanItem> */
    public function openItems(): array
    {
        return array_values(array_filter($this->items, static fn (LoanItem $i) => $i->isOpen()));
    }

    public function openCount(): int
    {
        return count($this->openItems());
    }

    public function hasOverdue(DateTimeImmutable $now): bool
    {
        foreach ($this->items as $item) {
            if ($item->isOverdue($now)) {
                return true;
            }
        }
        return false;
    }

    private function addExistingItem(LoanItem $item): void
    {
        if (count($this->items) >= self::MAX_ITEMS) {
            throw new InvalidLoanException('Un préstamo lleva de 1 a 3 líneas.');
        }
        foreach ($this->items as $existing) {
            if ($existing->bookId === $item->bookId) {
                throw new DuplicateBookInLoanException();
            }
        }
        $this->items[] = $item;
    }
}
