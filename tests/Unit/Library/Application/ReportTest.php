<?php

declare(strict_types=1);

namespace Tests\Unit\Library\Application;

use App\Library\Application\Exceptions\ApplicationException;
use App\Library\Application\Services\LendBooksService;
use App\Library\Application\Services\MostBorrowedReportService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FakeClock;
use Tests\Fakes\FakeReportReader;
use Tests\Fakes\Fixtures;
use Tests\Fakes\InMemoryBookRepository;
use Tests\Fakes\InMemoryLoanRepository;
use Tests\Fakes\InMemoryMemberRepository;

/** T-11: HU-10 most-borrowed report; frozen values survive renames (ADR-004). */
final class ReportTest extends TestCase
{
    private InMemoryMemberRepository $members;
    private InMemoryBookRepository $books;
    private InMemoryLoanRepository $loans;
    private FakeClock $clock;

    protected function setUp(): void
    {
        $this->members = new InMemoryMemberRepository();
        $this->books = new InMemoryBookRepository();
        $this->loans = new InMemoryLoanRepository();
        $this->clock = new FakeClock(new DateTimeImmutable('2026-10-05T10:00:00Z'));
        $this->members->save(Fixtures::member('m1'));
        $this->members->save(Fixtures::member('m2'));
        $this->books->save(Fixtures::book('b1', 'Cien años', '9780307474728', total: 5));
        $this->books->save(Fixtures::book('b2', 'Otro libro', '9780307474729', total: 5));
    }

    private function lend(string $loanId, string $member, array $bookIds): void
    {
        (new LendBooksService($this->members, $this->books, $this->loans, $this->clock))
            ->lend($loanId, $member, $bookIds, 'u1');
    }

    private function report(string $from, string $to): array
    {
        return (new MostBorrowedReportService(new FakeReportReader($this->loans)))->report(
            new DateTimeImmutable($from),
            new DateTimeImmutable($to),
        );
    }

    public function test_rows_count_order_and_empty_range(): void
    {
        $this->lend('l1', 'm1', ['b1']);
        // Return the copy so the same member can borrow it again.
        $loan = $this->loans->findById('l1');
        $book = $this->books->findById('b1');
        $loan->returnItem($loan->items()[0]->id, $book, $this->clock->now());
        $this->lend('l2', 'm1', ['b1']);
        $this->lend('l3', 'm2', ['b2']);
        $result = $this->report('2026-10-01T00:00:00Z', '2026-10-31T23:59:59Z');
        $this->assertCount(2, $result['rows']);
        $this->assertSame('b1', $result['rows'][0]->bookId);
        $this->assertSame(2, $result['rows'][0]->timesBorrowed);
        $this->assertSame(1, $result['rows'][1]->timesBorrowed);
        $empty = $this->report('2026-11-01T00:00:00Z', '2026-11-30T23:59:59Z');
        $this->assertSame([], $empty['rows']);
    }

    public function test_inverted_range_is_rejected(): void
    {
        try {
            $this->report('2026-10-31T00:00:00Z', '2026-10-01T00:00:00Z');
            $this->fail('Inverted range must throw.');
        } catch (ApplicationException $e) {
            $this->assertSame('INVALID_DATE_RANGE', $e->errorCode());
        }
    }

    public function test_frozen_title_and_category_survive_renames(): void
    {
        $this->lend('l1', 'm1', ['b1']);
        $book = $this->books->findById('b1');
        $book->updateDetails('Título nuevo', 'Autor nuevo', '99999999-9999-4999-8999-999999999999', 'Categoría nueva');
        $this->books->save($book);
        $result = $this->report('2026-10-01T00:00:00Z', '2026-10-31T23:59:59Z');
        $this->assertCount(1, $result['rows']);
        $this->assertSame('Cien años', $result['rows'][0]->bookTitle);
        $this->assertSame('Novela', $result['rows'][0]->categoryName);
    }
}
