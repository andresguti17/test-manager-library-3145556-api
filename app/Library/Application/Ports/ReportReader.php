<?php

declare(strict_types=1);

namespace App\Library\Application\Ports;

use App\Library\Domain\ValueObjects\DateRange;

/** One aggregated row of the most-borrowed report (frozen values). */
final readonly class MostBorrowedRow
{
    public function __construct(
        public string $bookId,
        public string $bookTitle,
        public string $categoryName,
        public int $timesBorrowed,
    ) {}
}

/**
 * Read port for the most-borrowed report (CA-10).
 *
 * Aggregates frozen loan-line values (book_id + book_title + category_name)
 * within the range; never reads the live catalog (Article VIII).
 */
interface ReportReader
{
    /** @return list<MostBorrowedRow> ordered by times_borrowed desc, then title */
    public function mostBorrowed(DateRange $range): array;
}
