<?php

declare(strict_types=1);

namespace App\Library\Application\Services;

use App\Library\Application\Exceptions\ItemAlreadyReturnedException;
use App\Library\Application\Exceptions\NotFoundException;
use App\Library\Application\Ports\BookRepository;
use App\Library\Application\Ports\Clock;
use App\Library\Application\Ports\LoanRepository;
use App\Library\Domain\Entities\LoanItem;
use App\Library\Domain\Exceptions\DomainException;

/** HU-07: return one line at a time; never adds twice (CA-07.2). */
final class ReturnItemService
{
    public function __construct(
        private LoanRepository $loans,
        private BookRepository $books,
        private Clock $clock,
    ) {}

    public function returnItem(string $loanId, string $itemId): LoanItem
    {
        $loan = $this->loans->findById($loanId);
        if ($loan === null) {
            throw new NotFoundException('El préstamo no existe.');
        }
        $item = $loan->findItem($itemId);
        if ($item === null) {
            throw new NotFoundException('La línea del préstamo no existe.');
        }
        if (!$item->isOpen()) {
            throw new ItemAlreadyReturnedException();
        }
        $book = $this->books->findById($item->bookId);
        if ($book === null) {
            throw new NotFoundException('El libro no existe.');
        }
        try {
            $returned = $loan->returnItem($itemId, $book, $this->clock->now());
        } catch (DomainException $e) {
            if ($e->errorCode() === 'ITEM_ALREADY_RETURNED') {
                throw new ItemAlreadyReturnedException();
            }
            throw $e;
        }
        $this->books->save($book);
        $this->loans->save($loan);
        return $returned;
    }
}
