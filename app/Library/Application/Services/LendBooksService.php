<?php

declare(strict_types=1);

namespace App\Library\Application\Services;

use App\Library\Application\Exceptions\BookNotAvailableException;
use App\Library\Application\Exceptions\DuplicateBookInLoanException;
use App\Library\Application\Exceptions\MemberHasOverdueException;
use App\Library\Application\Exceptions\MemberLimitExceededException;
use App\Library\Application\Exceptions\NotFoundException;
use App\Library\Application\Exceptions\ValidationException;
use App\Library\Application\Ports\BookRepository;
use App\Library\Application\Ports\Clock;
use App\Library\Application\Ports\LoanRepository;
use App\Library\Application\Ports\MemberRepository;
use App\Library\Domain\Entities\Loan;

/**
 * HU-06: record a loan of 1–3 lines for one member.
 *
 * Order of checks: member → shape/duplicates → limit → overdue →
 * availability (all copies verified before mutating, so a 409 records
 * nothing, CA-06.3). The real adapter persists books + loan in one
 * transaction (architecture §4); the fake does it in memory.
 */
final class LendBooksService
{
    public function __construct(
        private MemberRepository $members,
        private BookRepository $books,
        private LoanRepository $loans,
        private Clock $clock,
    ) {}

    /**
     * @param list<string> $bookIds
     * @param list<string> $itemIds optional deterministic line ids
     */
    public function lend(
        string $loanId,
        string $memberId,
        array $bookIds,
        string $loanedByUserId,
        array $itemIds = [],
    ): Loan {
        $member = $this->members->findById($memberId);
        if ($member === null) {
            throw new NotFoundException('El miembro no existe.');
        }
        $count = count($bookIds);
        if ($count < 1 || $count > Loan::MAX_ITEMS) {
            throw new ValidationException('Un préstamo lleva de 1 a 3 libros.');
        }
        if (count(array_unique($bookIds)) !== $count) {
            throw new DuplicateBookInLoanException();
        }

        $now = $this->clock->now();
        $openLoans = $this->loans->findOpenLoansByMember($memberId);
        $openCount = 0;
        foreach ($openLoans as $openLoan) {
            if ($openLoan->hasOverdue($now)) {
                throw new MemberHasOverdueException();
            }
            $openCount += $openLoan->openCount();
        }
        if ($openCount + $count > Loan::MAX_OPEN_BOOKS_PER_MEMBER) {
            throw new MemberLimitExceededException($openCount);
        }

        $byId = $this->books->findByIds($bookIds);
        $ordered = [];
        foreach ($bookIds as $bookId) {
            $book = $byId[$bookId] ?? null;
            if ($book === null || !$book->isActive()) {
                throw new NotFoundException('El libro no existe.');
            }
            if ($book->availableCopies() < 1) {
                // Atomic: nothing is recorded (CA-06.3).
                throw new BookNotAvailableException(
                    sprintf('No quedan ejemplares disponibles de «%s».', $book->title()),
                );
            }
            $ordered[] = $book;
        }

        $loan = new Loan($loanId, $memberId, $loanedByUserId, $now);
        foreach ($ordered as $i => $book) {
            $loan->addItem($book, $itemIds[$i] ?? sprintf('%s#%d', $loanId, $i + 1));
        }
        foreach ($ordered as $book) {
            $this->books->save($book);
        }
        $this->loans->save($loan);
        return $loan;
    }
}
