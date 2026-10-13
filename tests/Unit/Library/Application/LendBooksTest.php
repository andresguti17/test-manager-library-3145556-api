<?php

declare(strict_types=1);

namespace Tests\Unit\Library\Application;

use App\Library\Application\Exceptions\ApplicationException;
use App\Library\Application\Services\LendBooksService;
use App\Library\Domain\Entities\Loan;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FakeClock;
use Tests\Fakes\Fixtures;
use Tests\Fakes\InMemoryBookRepository;
use Tests\Fakes\InMemoryLoanRepository;
use Tests\Fakes\InMemoryMemberRepository;

/** T-09: full HU-06 lend matrix against fakes. */
final class LendBooksTest extends TestCase
{
    private InMemoryMemberRepository $members;
    private InMemoryBookRepository $books;
    private InMemoryLoanRepository $loans;
    private FakeClock $clock;
    private LendBooksService $lend;

    protected function setUp(): void
    {
        $this->members = new InMemoryMemberRepository();
        $this->books = new InMemoryBookRepository();
        $this->loans = new InMemoryLoanRepository();
        $this->clock = new FakeClock(new DateTimeImmutable('2026-10-01T10:00:00Z'));
        $this->lend = new LendBooksService($this->members, $this->books, $this->loans, $this->clock);
        $this->members->save(Fixtures::member('m1'));
    }

    private function stockBooks(): void
    {
        $this->books->save(Fixtures::book('b1', 'Libro uno', '9780307474728', total: 2));
        $this->books->save(Fixtures::book('b2', 'Libro dos', '9780307474729', total: 1));
        $this->books->save(Fixtures::book('b3', 'Libro tres', '9780307474730', total: 1));
        $this->books->save(Fixtures::book('b4', 'Libro cuatro', '9780307474731', total: 1));
    }

    public function test_happy_path_deducts_and_freezes(): void
    {
        $this->stockBooks();
        $loan = $this->lend->lend('l1', 'm1', ['b1', 'b2'], 'u1', ['i1', 'i2']);
        $this->assertCount(2, $loan->items());
        $this->assertSame(1, $this->books->findById('b1')->availableCopies());
        $this->assertSame(0, $this->books->findById('b2')->availableCopies());
        $this->assertSame('Libro uno', $loan->items()[0]->bookTitle);
        $this->assertSame('2026-10-15T10:00:00+00:00', $loan->items()[0]->dueAt->format('c'));
        $this->assertSame('open', $loan->status($this->clock->now())->value);
        $this->assertNotNull($this->loans->findById('l1'));
    }

    public function test_shape_and_duplicate_guards(): void
    {
        $this->stockBooks();
        foreach ([
            [[], 'VALIDATION_ERROR'],
            [['b1', 'b2', 'b3', 'b4'], 'VALIDATION_ERROR'],
            [['b1', 'b1'], 'DUPLICATE_BOOK_IN_LOAN'],
        ] as [$ids, $code]) {
            try {
                $this->lend->lend('lx', 'm1', $ids, 'u1');
                $this->fail('Must throw '.$code);
            } catch (ApplicationException $e) {
                $this->assertSame($code, $e->errorCode());
            }
        }
        $this->assertSame([], $this->loans->all());
    }

    public function test_no_copies_records_nothing(): void
    {
        $this->stockBooks();
        $this->books->findById('b2')->withdraw();
        try {
            $this->lend->lend('l1', 'm1', ['b1', 'b2'], 'u1');
            $this->fail('Must throw BOOK_NOT_AVAILABLE.');
        } catch (ApplicationException $e) {
            $this->assertSame('BOOK_NOT_AVAILABLE', $e->errorCode());
        }
        $this->assertSame([], $this->loans->all());
        $this->assertSame(2, $this->books->findById('b1')->availableCopies());
    }

    public function test_member_open_loans_cap_is_3(): void
    {
        $this->stockBooks();
        $this->lend->lend('l1', 'm1', ['b1', 'b2', 'b3'], 'u1');
        try {
            $this->lend->lend('l2', 'm1', ['b4'], 'u1');
            $this->fail('Must throw MEMBER_LIMIT_EXCEEDED.');
        } catch (ApplicationException $e) {
            $this->assertSame('MEMBER_LIMIT_EXCEEDED', $e->errorCode());
        }
        $this->assertNull($this->loans->findById('l2'));
    }

    public function test_member_with_overdue_cannot_borrow(): void
    {
        $this->stockBooks();
        $this->lend->lend('l1', 'm1', ['b1'], 'u1');
        $this->clock->travelDays(20);
        try {
            $this->lend->lend('l2', 'm1', ['b4'], 'u1');
            $this->fail('Must throw MEMBER_HAS_OVERDUE.');
        } catch (ApplicationException $e) {
            $this->assertSame('MEMBER_HAS_OVERDUE', $e->errorCode());
        }
        $this->assertNull($this->loans->findById('l2'));
    }

    public function test_missing_member_or_book_is_not_found(): void
    {
        $this->stockBooks();
        try {
            $this->lend->lend('l1', 'ghost', ['b1'], 'u1');
            $this->fail('Missing member must throw.');
        } catch (ApplicationException $e) {
            $this->assertSame('NOT_FOUND', $e->errorCode());
        }
        try {
            $this->lend->lend('l1', 'm1', ['ghost'], 'u1');
            $this->fail('Missing book must throw.');
        } catch (ApplicationException $e) {
            $this->assertSame('NOT_FOUND', $e->errorCode());
        }
    }

    public function test_returned_copies_free_up_the_limit(): void
    {
        $this->stockBooks();
        $loan = $this->lend->lend('l1', 'm1', ['b1', 'b2', 'b3'], 'u1');
        $book = $this->books->findById('b1');
        $loan->returnItem($loan->items()[0]->id, $book, $this->clock->now());
        $this->loans->save($loan);
        $this->books->save($book);
        $second = $this->lend->lend('l2', 'm1', ['b4'], 'u1');
        $this->assertCount(1, $second->items());
    }
}
