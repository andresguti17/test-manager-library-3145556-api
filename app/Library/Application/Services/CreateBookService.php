<?php

declare(strict_types=1);

namespace App\Library\Application\Services;

use App\Library\Application\Exceptions\IsbnTakenException;
use App\Library\Application\Ports\BookRepository;
use App\Library\Domain\Entities\Book;
use App\Library\Domain\ValueObjects\Isbn;

/** HU-03: create a book; available starts equal to the total. */
final class CreateBookService
{
    public function __construct(private BookRepository $books) {}

    public function create(
        string $id,
        string $title,
        string $author,
        string $isbn,
        string $categoryId,
        string $categoryName,
        int $totalCopies,
        ?string $coverKey = null,
    ): Book {
        $isbnVo = new Isbn($isbn);
        if ($this->books->findByIsbn($isbnVo->value) !== null) {
            throw new IsbnTakenException();
        }
        $book = Book::create($id, $title, $author, $isbnVo, $categoryId, $categoryName, $totalCopies, $coverKey);
        $this->books->save($book);
        return $book;
    }
}
