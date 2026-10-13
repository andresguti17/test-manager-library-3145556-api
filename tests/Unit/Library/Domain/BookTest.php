<?php

declare(strict_types=1);

namespace Tests\Unit\Library\Domain;

use App\Library\Domain\Entities\Book;
use App\Library\Domain\Exceptions\DomainException;
use App\Library\Domain\ValueObjects\Isbn;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\Fixtures;

/** T-04: Book withdraw/restock bounds, totals, trim, soft delete, cover. */
final class BookTest extends TestCase
{
    public function test_create_starts_with_available_equal_to_total(): void
    {
        $book = Book::create('b1', '  Cien años  ', 'García Márquez', new Isbn('9780307474728'), Fixtures::CATEGORY_NOVELA, 'Novela', 4);
        $this->assertSame(4, $book->totalCopies());
        $this->assertSame(4, $book->availableCopies());
        $this->assertSame('Cien años', $book->title());
        $this->assertNull($book->coverKey());
        $this->assertTrue($book->isActive());
    }

    public function test_withdraw_and_restock_stay_within_bounds(): void
    {
        $book = Fixtures::book('b1', total: 2);
        $book->withdraw();
        $this->assertSame(1, $book->availableCopies());
        $book->withdraw();
        $this->assertSame(0, $book->availableCopies());
        try {
            $book->withdraw();
            $this->fail('Withdrawing with 0 available must throw.');
        } catch (DomainException $e) {
            $this->assertSame('BOOK_NOT_AVAILABLE', $e->errorCode());
        }
        $book->restock();
        $book->restock();
        $this->assertSame(2, $book->availableCopies());
        $this->expectException(DomainException::class);
        $book->restock();
    }

    public function test_change_total_shifts_available_by_the_difference(): void
    {
        $book = Fixtures::book('b1', total: 4);
        $book->withdraw();
        $book->withdraw();
        $book->changeTotal(6);
        $this->assertSame(6, $book->totalCopies());
        $this->assertSame(4, $book->availableCopies());
        $book->changeTotal(3);
        $this->assertSame(3, $book->totalCopies());
        $this->assertSame(1, $book->availableCopies());
    }

    public function test_change_total_never_leaves_available_below_zero(): void
    {
        $book = Fixtures::book('b1', total: 2);
        $book->withdraw();
        $book->withdraw();
        try {
            $book->changeTotal(1);
            $this->fail('changeTotal leaving available < 0 must throw.');
        } catch (DomainException $e) {
            $this->assertSame('VALIDATION_ERROR', $e->errorCode());
        }
        $this->assertSame(2, $book->totalCopies());
        $this->assertSame(0, $book->availableCopies());
    }

    public function test_total_below_one_is_rejected(): void
    {
        $this->expectException(DomainException::class);
        Fixtures::book('b1', total: 0);
    }

    public function test_blank_title_and_empty_cover_key_are_rejected(): void
    {
        try {
            Fixtures::book('b1', title: '   ');
            $this->fail('Blank title must throw.');
        } catch (DomainException $e) {
            $this->assertSame('VALIDATION_ERROR', $e->errorCode());
        }
        $this->expectException(DomainException::class);
        Book::create('b1', 'T', 'A', new Isbn('9780307474728'), Fixtures::CATEGORY_NOVELA, 'Novela', 1, '');
    }

    public function test_soft_delete_marks_book_inactive(): void
    {
        $book = Fixtures::book('b1');
        $book->softDelete(new DateTimeImmutable('2026-10-14T00:00:00Z'));
        $this->assertFalse($book->isActive());
        $this->assertNotNull($book->deletedAt());
    }
}
