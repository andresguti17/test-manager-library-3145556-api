<?php

declare(strict_types=1);

namespace App\Library\Adapters\Outbound\Persistence;

use App\Library\Application\Exceptions\BookNotAvailableException as AppBookNotAvailableException;
use App\Library\Application\Exceptions\IsbnTakenException;
use App\Library\Application\Ports\BookRepository;
use App\Library\Domain\Entities\Book;
use Illuminate\Database\QueryException;

/** Eloquent implementation of the book persistence port. */
final class EloquentBookRepository implements BookRepository
{
    public function save(Book $book): void
    {
        try {
            BookModel::updateOrCreate(['id' => $book->id], [
                'title' => $book->title(),
                'author' => $book->author(),
                'isbn' => (string) $book->isbn,
                'category_id' => $book->categoryId(),
                'total_copies' => $book->totalCopies(),
                'available_copies' => $book->availableCopies(),
                'cover_key' => $book->coverKey(),
                'deleted_at' => $book->deletedAt(),
            ]);
        } catch (QueryException $e) {
            if (PersistenceErrors::violatesCheck($e, 'ck_book_available_range')) {
                throw new AppBookNotAvailableException('No quedan ejemplares disponibles.');
            }
            if (PersistenceErrors::isUniqueViolation($e)) {
                throw new IsbnTakenException();
            }
            throw $e;
        }
    }

    public function findById(string $id): ?Book
    {
        $model = BookModel::with('category')->find($id);
        if ($model === null) {
            return null;
        }

        return PersistenceMapper::toBook($model);
    }

    public function findByIsbn(string $isbn): ?Book
    {
        $model = BookModel::with('category')
            ->where('isbn', $isbn)
            ->whereNull('deleted_at')
            ->first();
        if ($model === null) {
            return null;
        }

        return PersistenceMapper::toBook($model);
    }

    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $models = BookModel::with('category')->whereIn('id', array_values($ids))->get();
        $found = [];
        foreach ($models as $model) {
            $found[$model->id] = PersistenceMapper::toBook($model);
        }

        return $found;
    }

    public function search(?string $query, ?string $categoryId, int $page, int $size): array
    {
        $builder = BookModel::with('category')->whereNull('deleted_at');
        $q = $query !== null ? strtolower(trim($query)) : '';
        if ($q !== '') {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%';
            $builder->where(function ($inner) use ($like) {
                $inner->whereRaw('LOWER(title) LIKE ? ESCAPE ?', [$like, '\\'])
                    ->orWhereRaw('LOWER(author) LIKE ? ESCAPE ?', [$like, '\\']);
            });
        }
        if ($categoryId !== null) {
            $builder->where('category_id', $categoryId);
        }
        $total = (clone $builder)->count();
        $offset = max(0, ($page - 1) * $size);
        $items = $builder->orderBy('title')->offset($offset)->limit($size)->get()
            ->map(static fn (BookModel $m) => PersistenceMapper::toBook($m))
            ->all();

        return ['items' => array_values($items), 'total' => $total];
    }
}
