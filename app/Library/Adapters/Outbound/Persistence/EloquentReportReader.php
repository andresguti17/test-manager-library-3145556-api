<?php

declare(strict_types=1);

namespace App\Library\Adapters\Outbound\Persistence;

use App\Library\Application\Ports\MostBorrowedRow;
use App\Library\Application\Ports\ReportReader;
use App\Library\Domain\ValueObjects\DateRange;
use Illuminate\Support\Facades\DB;

/**
 * Aggregate read of the most-borrowed report (CA-10, ADR-004).
 *
 * A single query over the frozen loan-line values (book_id + book_title +
 * category_name); it never reads the live catalog (Article VIII).
 */
final class EloquentReportReader implements ReportReader
{
    public function mostBorrowed(DateRange $range): array
    {
        $rows = DB::table('library.loan_item as li')
            ->join('library.loan as l', 'l.id', '=', 'li.loan_id')
            ->whereBetween('l.loaned_at', [$range->start, $range->end])
            ->groupBy('li.book_id', 'li.book_title', 'li.category_name')
            ->orderByDesc('times_borrowed')
            ->orderBy('li.book_title')
            ->select([
                'li.book_id as book_id',
                'li.book_title as book_title',
                'li.category_name as category_name',
                DB::raw('COUNT(*) as times_borrowed'),
            ])
            ->get();

        return $rows
            ->map(static fn (object $row) => new MostBorrowedRow(
                (string) $row->book_id,
                (string) $row->book_title,
                (string) $row->category_name,
                (int) $row->times_borrowed,
            ))
            ->all();
    }
}
