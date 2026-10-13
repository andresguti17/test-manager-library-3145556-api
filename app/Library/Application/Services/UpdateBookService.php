<?php

declare(strict_types=1);

namespace App\Library\Application\Services;

use App\Library\Application\Exceptions\NotFoundException;
use App\Library\Application\Ports\BookRepository;
use App\Library\Domain\Entities\Book;

/**
 * HU-03 (PUT): replaces title, author, category and total.
 * The ISBN is identity and is not editable here.
 */
final class UpdateBookService
{
    public function __construct(private BookRepository $books) {}

    public function update(
        string $id,
        string $title,
        string $author,
        string $categoryId,
        string $categoryName,
        int $totalCopies,
    ): Book {
        $book = $this->books->findById($id);
        if ($book === null || !$book->isActive()) {
            throw new NotFoundException('El libro no existe.');
        }
        $book->updateDetails($title, $author, $categoryId, $categoryName);
        $book->changeTotal($totalCopies);
        $this->books->save($book);
        return $book;
    }
}
