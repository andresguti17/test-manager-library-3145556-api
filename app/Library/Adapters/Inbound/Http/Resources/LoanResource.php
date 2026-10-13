<?php

declare(strict_types=1);

namespace App\Library\Adapters\Inbound\Http\Resources;

use App\Library\Adapters\Inbound\Http\Support\IsoFormat;
use App\Library\Application\Services\OverdueRow;
use App\Library\Domain\Entities\Loan;
use App\Library\Domain\Entities\LoanItem;
use DateTimeImmutable;

/** Contract JSON shapes of loans, lines, and the overdue listing. */
final class LoanResource
{
    /** @return array<string, mixed> */
    public static function present(Loan $loan, DateTimeImmutable $now): array
    {
        return [
            'id' => $loan->id,
            'member_id' => $loan->memberId,
            'loaned_at' => IsoFormat::dateTime($loan->loanedAt),
            'status' => $loan->status($now)->value,
            'items' => array_map(
                static fn (LoanItem $item) => self::presentItem($item),
                $loan->items(),
            ),
        ];
    }

    /** @return array<string, mixed> */
    public static function presentItem(LoanItem $item): array
    {
        return [
            'id' => $item->id,
            'book_id' => $item->bookId,
            'book_title' => $item->bookTitle,
            'isbn' => $item->isbn,
            'category_name' => $item->categoryName,
            'due_at' => IsoFormat::dateTime($item->dueAt),
            'returned_at' => $item->returnedAt() !== null ? IsoFormat::dateTime($item->returnedAt()) : null,
        ];
    }

    /** @return array<string, mixed> */
    public static function presentOverdue(OverdueRow $row): array
    {
        return [
            'loan_id' => $row->loanId,
            'item_id' => $row->itemId,
            'book_title' => $row->bookTitle,
            'member_name' => $row->memberName,
            'due_at' => IsoFormat::dateTime($row->dueAt),
            'days_late' => $row->daysLate,
        ];
    }
}
