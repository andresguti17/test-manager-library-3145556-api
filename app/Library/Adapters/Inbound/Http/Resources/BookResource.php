<?php

declare(strict_types=1);

namespace App\Library\Adapters\Inbound\Http\Resources;

use App\Library\Domain\Entities\Book;

/** Contract JSON shape of a book (snake_case, opaque cover_key). */
final class BookResource
{
    /** @return array<string, mixed> */
    public static function present(Book $book): array
    {
        return [
            'id' => $book->id,
            'title' => $book->title(),
            'author' => $book->author(),
            'isbn' => (string) $book->isbn,
            'category' => [
                'id' => $book->categoryId(),
                'name' => $book->categoryName(),
            ],
            'total_copies' => $book->totalCopies(),
            'available_copies' => $book->availableCopies(),
            'cover_key' => $book->coverKey(),
        ];
    }
}
