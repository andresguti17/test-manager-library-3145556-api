<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Library\Application\Ports\BookRepository;
use App\Library\Domain\Entities\Book;

final class InMemoryBookRepository implements BookRepository
{
    /** @var array<string, Book> */
    private array $books = [];

    public function save(Book $book): void
    {
        $this->books[$book->id] = $book;
    }

    public function findById(string $id): ?Book
    {
        return $this->books[$id] ?? null;
    }

    public function findByIsbn(string $isbn): ?Book
    {
        foreach ($this->books as $book) {
            if ($book->isActive() && $book->isbn->value === $isbn) {
                return $book;
            }
        }
        return null;
    }

    public function findByIds(array $ids): array
    {
        $found = [];
        foreach ($ids as $id) {
            if (isset($this->books[$id])) {
                $found[$id] = $this->books[$id];
            }
        }
        return $found;
    }

    public function search(?string $query, ?string $categoryId, int $page, int $size): array
    {
        $q = $query !== null ? strtolower(trim($query)) : '';
        $filtered = array_filter(
            $this->books,
            static fn (Book $b) => $b->isActive()
                && ($categoryId === null || $b->categoryId() === $categoryId)
                && ($q === '' || str_contains(strtolower($b->title()), $q) || str_contains(strtolower($b->author()), $q)),
        );
        usort($filtered, static fn (Book $a, Book $b) => strcmp($a->title(), $b->title()));
        $total = count($filtered);
        $items = array_slice(array_values($filtered), ($page - 1) * $size, $size);
        return ['items' => $items, 'total' => $total];
    }
}
