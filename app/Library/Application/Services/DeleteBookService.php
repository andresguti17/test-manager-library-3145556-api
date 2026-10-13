<?php

declare(strict_types=1);

namespace App\Library\Application\Services;

use App\Library\Application\Exceptions\BookHasOpenLoansException;
use App\Library\Application\Exceptions\NotFoundException;
use App\Library\Application\Ports\BookRepository;
use App\Library\Application\Ports\Clock;
use App\Library\Application\Ports\LoanRepository;

/** HU-03 (DELETE): logical deletion; blocked with open loans (CA-03.5). */
final class DeleteBookService
{
    public function __construct(
        private BookRepository $books,
        private LoanRepository $loans,
        private Clock $clock,
    ) {}

    public function delete(string $id): void
    {
        $book = $this->books->findById($id);
        if ($book === null || !$book->isActive()) {
            throw new NotFoundException('El libro no existe.');
        }
        if ($this->loans->findOpenLoansByBook($id) !== []) {
            throw new BookHasOpenLoansException();
        }
        $book->softDelete($this->clock->now());
        $this->books->save($book);
    }
}
