<?php

declare(strict_types=1);

namespace App\Library\Adapters\Outbound\Persistence;

use App\Library\Domain\Entities\Book;
use App\Library\Domain\Entities\Loan;
use App\Library\Domain\Entities\LoanItem;
use App\Library\Domain\Entities\Member;
use App\Library\Domain\Entities\User;
use App\Library\Domain\Enums\UserRole;
use App\Library\Domain\ValueObjects\DocumentNumber;
use App\Library\Domain\ValueObjects\Isbn;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/** Maps Eloquent rows to domain entities. Internal to the persistence adapter. */
final class PersistenceMapper
{
    public static function toBook(BookModel $model): Book
    {
        $categoryName = $model->getRelationValue('category') !== null
            ? (string) $model->getRelationValue('category')->name
            : (string) CategoryModel::where('id', $model->category_id)->value('name');

        return new Book(
            $model->id,
            (string) $model->title,
            (string) $model->author,
            new Isbn((string) $model->isbn),
            (string) $model->category_id,
            $categoryName,
            (int) $model->total_copies,
            (int) $model->available_copies,
            $model->cover_key !== null ? (string) $model->cover_key : null,
            self::toImmutable($model->deleted_at),
        );
    }

    public static function toMember(MemberModel $model): Member
    {
        return new Member(
            $model->id,
            (string) $model->full_name,
            new DocumentNumber((string) $model->document_number),
        );
    }

    public static function toUser(UserModel $model): User
    {
        return new User(
            $model->id,
            (string) $model->username,
            (string) $model->password_hash,
            UserRole::from((string) $model->role),
        );
    }

    public static function toLoan(LoanModel $model): Loan
    {
        $items = [];
        foreach ($model->getRelationValue('items') ?? [] as $item) {
            $items[] = self::toLoanItem($item);
        }

        return new Loan(
            $model->id,
            (string) $model->member_id,
            (string) $model->loaned_by_user_id,
            self::toImmutable($model->loaned_at) ?? new DateTimeImmutable('now', new DateTimeZone('UTC')),
            $items,
        );
    }

    public static function toLoanItem(LoanItemModel $model): LoanItem
    {
        return new LoanItem(
            $model->id,
            (string) $model->book_id,
            (string) $model->book_title,
            (string) $model->isbn,
            (string) $model->category_name,
            self::toImmutable($model->due_at) ?? new DateTimeImmutable('now', new DateTimeZone('UTC')),
            self::toImmutable($model->returned_at),
        );
    }

    public static function toImmutable(mixed $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof DateTimeImmutable) {
            return $value;
        }
        if ($value instanceof DateTimeInterface) {
            return new DateTimeImmutable(
                $value->format('Y-m-d H:i:s.u'),
                $value->getTimezone() ?: new DateTimeZone('UTC'),
            );
        }

        return new DateTimeImmutable((string) $value);
    }
}
