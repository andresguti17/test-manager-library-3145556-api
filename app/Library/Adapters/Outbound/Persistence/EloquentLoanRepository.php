<?php

declare(strict_types=1);

namespace App\Library\Adapters\Outbound\Persistence;

use App\Library\Application\Ports\LoanRepository;
use App\Library\Domain\Entities\Loan;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent implementation of the loan persistence port.
 *
 * Saves run inside one transaction: the involved book rows are locked
 * with SELECT ... FOR UPDATE first so concurrent lends of the last copy
 * serialize instead of overwriting each other. The database CHECK
 * ck_book_available_range stays as the final barrier.
 */
final class EloquentLoanRepository implements LoanRepository
{
    public function save(Loan $loan): void
    {
        DB::transaction(function () use ($loan) {
            $bookIds = [];
            foreach ($loan->items() as $item) {
                $bookIds[] = $item->bookId;
            }
            if ($bookIds !== []) {
                BookModel::whereIn('id', array_values(array_unique($bookIds)))
                    ->lockForUpdate()
                    ->get();
            }

            LoanModel::updateOrCreate(['id' => $loan->id], [
                'member_id' => $loan->memberId,
                'loaned_by_user_id' => $loan->loanedByUserId,
                'loaned_at' => $loan->loanedAt,
            ]);

            foreach ($loan->items() as $item) {
                LoanItemModel::updateOrCreate(['id' => $item->id], [
                    'loan_id' => $loan->id,
                    'book_id' => $item->bookId,
                    'book_title' => $item->bookTitle,
                    'isbn' => $item->isbn,
                    'category_name' => $item->categoryName,
                    'due_at' => $item->dueAt,
                    'returned_at' => $item->returnedAt(),
                ]);
            }
        });
    }

    public function findById(string $id): ?Loan
    {
        $model = LoanModel::with('items')->find($id);
        if ($model === null) {
            return null;
        }

        return PersistenceMapper::toLoan($model);
    }

    public function findOpenLoansByMember(string $memberId): array
    {
        return LoanModel::with('items')
            ->where('member_id', $memberId)
            ->whereHas('items', static function ($inner): void {
                $inner->whereNull('returned_at');
            })
            ->get()
            ->map(static fn (LoanModel $m) => PersistenceMapper::toLoan($m))
            ->all();
    }

    public function findOpenLoansByBook(string $bookId): array
    {
        return LoanModel::with('items')
            ->whereHas('items', static function ($inner) use ($bookId): void {
                $inner->where('book_id', $bookId)->whereNull('returned_at');
            })
            ->get()
            ->map(static fn (LoanModel $m) => PersistenceMapper::toLoan($m))
            ->all();
    }

    public function all(): array
    {
        return LoanModel::with('items')
            ->get()
            ->map(static fn (LoanModel $m) => PersistenceMapper::toLoan($m))
            ->all();
    }
}
