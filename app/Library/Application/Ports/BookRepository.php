<?php

declare(strict_types=1);

namespace App\Library\Application\Ports;

use App\Library\Domain\Entities\Book;

/**
 * Persistence port for books. No framework or ORM types in signatures
 * (Article II).
 */
interface BookRepository
{
    public function save(Book $book): void;

    public function findById(string $id): ?Book;

    /** Finds an active book by ISBN (soft-deleted books are invisible). */
    public function findByIsbn(string $isbn): ?Book;

    /** @param list<string> $ids @return array<string, Book> keyed by id */
    public function findByIds(array $ids): array;

    /**
     * Active books only, ordered by title.
     *
     * @return array{items: list<Book>, total: int}
     */
    public function search(?string $query, ?string $categoryId, int $page, int $size): array;
}
