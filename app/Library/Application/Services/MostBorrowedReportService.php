<?php

declare(strict_types=1);

namespace App\Library\Application\Services;

use App\Library\Application\Exceptions\InvalidDateRangeException;
use App\Library\Application\Ports\MostBorrowedRow;
use App\Library\Application\Ports\ReportReader;
use App\Library\Domain\ValueObjects\DateRange;
use DateTimeImmutable;

/** HU-10: most-borrowed books in a range, from frozen line values. */
final class MostBorrowedReportService
{
    public function __construct(private ReportReader $report) {}

    /**
     * @return array{from: DateTimeImmutable, to: DateTimeImmutable, rows: list<MostBorrowedRow>}
     */
    public function report(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        if ($to < $from) {
            throw new InvalidDateRangeException();
        }
        $rows = $this->report->mostBorrowed(new DateRange($from, $to));
        return ['from' => $from, 'to' => $to, 'rows' => $rows];
    }
}
