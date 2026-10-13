<?php

declare(strict_types=1);

namespace App\Library\Adapters\Inbound\Http\Resources;

use App\Library\Adapters\Inbound\Http\Support\IsoFormat;
use App\Library\Application\Ports\MostBorrowedRow;
use DateTimeImmutable;

/** Contract JSON shape of the most-borrowed report. */
final class ReportResource
{
    /**
     * @param list<MostBorrowedRow> $rows
     * @return array<string, mixed>
     */
    public static function present(DateTimeImmutable $from, DateTimeImmutable $to, array $rows): array
    {
        return [
            'from' => IsoFormat::date($from),
            'to' => IsoFormat::date($to),
            'rows' => array_map(static fn (MostBorrowedRow $row) => [
                'book_id' => $row->bookId,
                'book_title' => $row->bookTitle,
                'category_name' => $row->categoryName,
                'times_borrowed' => $row->timesBorrowed,
            ], $rows),
        ];
    }
}
