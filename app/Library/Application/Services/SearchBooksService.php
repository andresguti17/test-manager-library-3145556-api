<?php

declare(strict_types=1);

namespace App\Library\Application\Services;

use App\Library\Application\Ports\BookRepository;
use App\Library\Domain\Entities\Book;

/** HU-04: search active books by text/category, ordered by title, paginated. */
final class SearchBooksService
{
    public const MAX_SIZE = 50;

    public function __construct(private BookRepository $books) {}

    /** @return array{items: list<Book>, page: int, size: int, total: int} */
    public function search(?string $query, ?string $categoryId, int $page = 1, int $size = 20): array
    {
        $page = max(1, $page);
        $size = min(max(1, $size), self::MAX_SIZE);
        $result = $this->books->search($query, $categoryId, $page, $size);
        return [
            'items' => $result['items'],
            'page' => $page,
            'size' => $size,
            'total' => $result['total'],
        ];
    }
}
