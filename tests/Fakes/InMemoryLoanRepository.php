<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Library\Application\Ports\LoanRepository;
use App\Library\Domain\Entities\Loan;

final class InMemoryLoanRepository implements LoanRepository
{
    /** @var array<string, Loan> */
    private array $loans = [];

    public function save(Loan $loan): void
    {
        $this->loans[$loan->id] = $loan;
    }

    public function findById(string $id): ?Loan
    {
        return $this->loans[$id] ?? null;
    }

    public function findOpenLoansByMember(string $memberId): array
    {
        return array_values(array_filter(
            $this->loans,
            static fn (Loan $l) => $l->memberId === $memberId && $l->openCount() > 0,
        ));
    }

    public function findOpenLoansByBook(string $bookId): array
    {
        return array_values(array_filter($this->loans, static function (Loan $l) use ($bookId) {
            foreach ($l->openItems() as $item) {
                if ($item->bookId === $bookId) {
                    return true;
                }
            }
            return false;
        }));
    }

    public function all(): array
    {
        return array_values($this->loans);
    }
}
