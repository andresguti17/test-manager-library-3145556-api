<?php

declare(strict_types=1);

namespace App\Library\Application\Ports;

use App\Library\Domain\Entities\Loan;

interface LoanRepository
{
    public function save(Loan $loan): void;

    public function findById(string $id): ?Loan;

    /** Loans of the member with at least one unreturned line. */
    /** @return list<Loan> */
    public function findOpenLoansByMember(string $memberId): array;

    /** Loans with at least one unreturned line for the book. */
    /** @return list<Loan> */
    public function findOpenLoansByBook(string $bookId): array;

    /** @return list<Loan> */
    public function all(): array;
}
