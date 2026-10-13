<?php

declare(strict_types=1);

namespace Tests\Unit\Library\Application;

use App\Library\Application\Exceptions\ApplicationException;
use App\Library\Application\Services\LendBooksService;
use App\Library\Application\Services\OverdueService;
use App\Library\Application\Services\ReturnItemService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FakeClock;
use Tests\Fakes\Fixtures;
use Tests\Fakes\InMemoryBookRepository;
use Tests\Fakes\InMemoryLoanRepository;
use Tests\Fakes\InMemoryMemberRepository;

/** T-10: HU-07 returns + HU-09 overdue listing against fakes. */
final class ReturnOverdueTest extends TestCase
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
        $this->clock = new FakeClock(new DateTimeImmutable('2026-10-01T10:00:00Z'));
        $this->members->save(Fixtures::member('m1', 'Juan Pérez', '123'));
        $this->members->save(Fixtures::member('m2', 'Ana Ruiz', '456'));
        $this->books->save(Fixtures::book('b1', 'Libro uno', '9780307474728', total: 2));
        $this->books->save(Fixtures::book('b2', 'Libro dos', '9780307474729', total: 2));
    }

    private function lend(string $loanId, string $member, array $bookIds): \App\Library\Domain\Entities\Loan
    {
        return (new LendBooksService($this->members, $this->books, $this->loans, $this->clock))
            ->lend($loanId, $member, $bookIds, 'u1');
    }

    public function test_return_sets_returned_at_and_restocks(): void
    {
        $loan = $this->lend('l1', 'm1', ['b1']);
        $itemId = $loan->items()[0]->id;
        $returned = (new ReturnItemService($this->loans, $this->books, $this->clock))->returnItem('l1', $itemId);
        $this->assertNotNull($returned->returnedAt());
        $this->assertSame(2, $this->books->findById('b1')->availableCopies());
        $this->assertSame('closed', $this->loans->findById('l1')->status($this->clock->now())->value);
    }

    public function test_double_return_never_adds_twice(): void
    {
        $loan = $this->lend('l1', 'm1', ['b1']);
        $service = new ReturnItemService($this->loans, $this->books, $this->clock);
        $service->returnItem('l1', $loan->items()[0]->id);
        try {
            $service->returnItem('l1', $loan->items()[0]->id);
            $this->fail('Double return must throw.');
        } catch (ApplicationException $e) {
            $this->assertSame('ITEM_ALREADY_RETURNED', $e->errorCode());
        }
        $this->assertSame(2, $this->books->findById('b1')->availableCopies());
    }

    public function test_overdue_lists_most_late_first(): void
    {
        $this->lend('l1', 'm1', ['b1']);
        $this->clock->travelDays(5);
        $this->lend('l2', 'm2', ['b2']);
        $this->clock->travelDays(15);
        $rows = (new OverdueService($this->loans, $this->members, $this->clock))->list();
        $this->assertCount(2, $rows);
        $this->assertSame('l1', $rows[0]->loanId);
        $this->assertSame(6, $rows[0]->daysLate);
        $this->assertSame('Juan Pérez', $rows[0]->memberName);
        $this->assertSame('l2', $rows[1]->loanId);
        // Returned lines disappear from the listing.
        (new ReturnItemService($this->loans, $this->books, $this->clock))->returnItem('l1', $this->loans->findById('l1')->items()[0]->id);
        $rows = (new OverdueService($this->loans, $this->members, $this->clock))->list();
        $this->assertCount(1, $rows);
        $this->assertSame('l2', $rows[0]->loanId);
    }
}
