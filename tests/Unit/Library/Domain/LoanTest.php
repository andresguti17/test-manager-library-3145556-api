<?php

declare(strict_types=1);

namespace Tests\Unit\Library\Domain;

use App\Library\Domain\Entities\Loan;
use App\Library\Domain\Enums\LoanStatus;
use App\Library\Domain\Exceptions\DomainException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FakeClock;
use Tests\Fakes\Fixtures;

/** T-05: Loan AddItem/ReturnItem, 14-day due date, max 3, computed status. */
final class LoanTest extends TestCase
{
    private FakeClock $clock;

    protected function setUp(): void
    {
        $this->clock = new FakeClock(new DateTimeImmutable('2026-10-01T10:00:00Z'));
    }

    public function test_add_item_withdraws_and_freezes_values_with_14_day_due(): void
    {
        $book = Fixtures::book('b1', 'Cien años', '9780307474728', total: 2);
        $loan = new Loan('l1', 'm1', 'u1', $this->clock->now());
        $item = $loan->addItem($book, 'i1');
        $this->assertSame(1, $book->availableCopies());
        $this->assertSame('Cien años', $item->bookTitle);
        $this->assertSame('9780307474728', $item->isbn);
        $this->assertSame('Novela', $item->categoryName);
        $this->assertSame('2026-10-15T10:00:00+00:00', $item->dueAt->format('c'));
        $this->assertNull($item->returnedAt());
        $this->assertSame(LoanStatus::Open, $loan->status($this->clock->now()));
    }

    public function test_duplicate_book_and_fourth_item_are_rejected(): void
    {
        $loan = new Loan('l1', 'm1', 'u1', $this->clock->now());
        $loan->addItem(Fixtures::book('b1', isbn: '9780307474728'), 'i1');
        try {
            $loan->addItem(Fixtures::book('b1', isbn: '9780307474728'), 'i2');
            $this->fail('Duplicate book must throw.');
        } catch (DomainException $e) {
            $this->assertSame('DUPLICATE_BOOK_IN_LOAN', $e->errorCode());
        }
        $loan->addItem(Fixtures::book('b2', isbn: '9780307474729'), 'i2');
        $loan->addItem(Fixtures::book('b3', isbn: '9780307474730'), 'i3');
        $this->expectException(DomainException::class);
        $loan->addItem(Fixtures::book('b4', isbn: '9780307474731'), 'i4');
    }

    public function test_status_moves_open_overdue_closed_with_fake_clock(): void
    {
        $book = Fixtures::book('b1', total: 1);
        $loan = new Loan('l1', 'm1', 'u1', $this->clock->now());
        $loan->addItem($book, 'i1');
        $this->assertSame(LoanStatus::Open, $loan->status($this->clock->now()));
        $this->clock->travelDays(15);
        $this->assertSame(LoanStatus::Overdue, $loan->status($this->clock->now()));
        $loan->returnItem('i1', $book, $this->clock->now());
        $this->assertSame(LoanStatus::Closed, $loan->status($this->clock->now()));
        $this->assertSame(1, $book->availableCopies());
    }

    public function test_return_sets_returned_at_once_and_restocks_once(): void
    {
        $book = Fixtures::book('b1', total: 1);
        $loan = new Loan('l1', 'm1', 'u1', $this->clock->now());
        $loan->addItem($book, 'i1');
        $returned = $loan->returnItem('i1', $book, $this->clock->now());
        $this->assertNotNull($returned->returnedAt());
        $this->assertSame(1, $book->availableCopies());
        try {
            $loan->returnItem('i1', $book, $this->clock->now());
            $this->fail('Double return must throw.');
        } catch (DomainException $e) {
            $this->assertSame('ITEM_ALREADY_RETURNED', $e->errorCode());
        }
        $this->assertSame(1, $book->availableCopies());
    }
}
