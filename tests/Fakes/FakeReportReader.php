<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Library\Application\Ports\LoanRepository;
use App\Library\Application\Ports\MostBorrowedRow;
use App\Library\Application\Ports\ReportReader;
use App\Library\Domain\ValueObjects\DateRange;

/**
 * Aggregates frozen loan-line values (never the live catalog), so renaming
 * a book after lending keeps the old title in the report (ADR-004).
 */
final class FakeReportReader implements ReportReader
{
    public function __construct(private LoanRepository $loans) {}

    public function mostBorrowed(DateRange $range): array
    {
        /** @var array<string, MostBorrowedRow> $rows */
        $rows = [];
        foreach ($this->loans->all() as $loan) {
            if (!$range->contains($loan->loanedAt)) {
                continue;
            }
            foreach ($loan->items() as $item) {
                $key = $item->bookId."\0".$item->bookTitle."\0".$item->categoryName;
                $prev = $rows[$key] ?? null;
                $rows[$key] = new MostBorrowedRow(
                    $item->bookId,
                    $item->bookTitle,
                    $item->categoryName,
                    ($prev?->timesBorrowed ?? 0) + 1,
                );
            }
        }
        $list = array_values($rows);
        usort($list, static fn (MostBorrowedRow $a, MostBorrowedRow $b) => $b->timesBorrowed <=> $a->timesBorrowed ?: strcmp($a->bookTitle, $b->bookTitle));
        return $list;
    }
}
