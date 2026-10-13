<?php

declare(strict_types=1);

namespace Tests\Unit\Library\Application;

use App\Library\Application\Exceptions\ApplicationException;
use App\Library\Application\Services\CoverService;
use App\Library\Application\Services\CreateBookService;
use App\Library\Application\Services\DeleteBookService;
use App\Library\Application\Services\SearchBooksService;
use App\Library\Application\Services\UpdateBookService;
use App\Library\Domain\Entities\Loan;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FakeClock;
use Tests\Fakes\FakeCoverStorage;
use Tests\Fakes\Fixtures;
use Tests\Fakes\InMemoryBookRepository;
use Tests\Fakes\InMemoryLoanRepository;

/** T-08: HU-03 manage books + HU-04 search against fakes. */
final class BookServicesTest extends TestCase
{
    private InMemoryBookRepository $books;
    private FakeClock $clock;

    protected function setUp(): void
    {
        $this->books = new InMemoryBookRepository();
        $this->clock = new FakeClock(new DateTimeImmutable('2026-10-01T10:00:00Z'));
    }

    public function test_create_and_duplicate_isbn(): void
    {
        $create = new CreateBookService($this->books);
        $book = $create->create('b1', 'Cien años', 'García Márquez', '9780307474728', Fixtures::CATEGORY_NOVELA, 'Novela', 3);
        $this->assertSame(3, $book->availableCopies());
        try {
            $create->create('b2', 'Otro', 'Alguien', '9780307474728', Fixtures::CATEGORY_NOVELA, 'Novela', 1);
            $this->fail('Duplicate ISBN must throw.');
        } catch (ApplicationException $e) {
            $this->assertSame('ISBN_TAKEN', $e->errorCode());
        }
        try {
            $create->create('b3', 'Otro', 'Alguien', '123', Fixtures::CATEGORY_NOVELA, 'Novela', 1);
            $this->fail('Invalid ISBN must throw.');
        } catch (ApplicationException | \App\Library\Domain\Exceptions\DomainException $e) {
            $this->assertSame('VALIDATION_ERROR', $e->errorCode());
        }
    }

    public function test_search_filters_orders_and_counts(): void
    {
        $create = new CreateBookService($this->books);
        $create->create('b1', 'Zorro', 'Autor A', '9780307474728', Fixtures::CATEGORY_NOVELA, 'Novela', 1);
        $create->create('b2', 'Árbol', 'Autor B', '9780307474729', Fixtures::CATEGORY_NOVELA, 'Novela', 1);
        $create->create('b3', 'Zorro viejo', 'García', '9780307474730', Fixtures::CATEGORY_NOVELA, 'Novela', 1);
        $search = new SearchBooksService($this->books);
        $result = $search->search('zorro', null, 1, 50);
        $this->assertSame(2, $result['total']);
        $this->assertSame('Zorro', $result['items'][0]->title());
        $page = $search->search(null, null, 1, 2);
        $this->assertSame(3, $page['total']);
        $this->assertCount(2, $page['items']);
        $capped = $search->search(null, null, 1, 500);
        $this->assertSame(50, $capped['size']);
    }

    public function test_search_excludes_deleted_books(): void
    {
        $create = new CreateBookService($this->books);
        $create->create('b1', 'Visible', 'A', '9780307474728', Fixtures::CATEGORY_NOVELA, 'Novela', 1);
        $create->create('b2', 'Borrado', 'A', '9780307474729', Fixtures::CATEGORY_NOVELA, 'Novela', 1);
        (new DeleteBookService($this->books, new InMemoryLoanRepository(), $this->clock))->delete('b2');
        $result = (new SearchBooksService($this->books))->search(null, null, 1, 50);
        $this->assertSame(1, $result['total']);
    }

    public function test_update_adjusts_total_and_guards_available(): void
    {
        $create = new CreateBookService($this->books);
        $create->create('b1', 'T', 'A', '9780307474728', Fixtures::CATEGORY_NOVELA, 'Novela', 2);
        $book = $this->books->findById('b1');
        $book->withdraw();
        $book->withdraw();
        $update = new UpdateBookService($this->books);
        try {
            $update->update('b1', 'T2', 'A2', Fixtures::CATEGORY_NOVELA, 'Novela', 1);
            $this->fail('Total leaving available < 0 must throw.');
        } catch (ApplicationException | \App\Library\Domain\Exceptions\DomainException $e) {
            $this->assertSame('VALIDATION_ERROR', $e->errorCode());
        }
        $updated = $update->update('b1', ' T2 ', 'A2', Fixtures::CATEGORY_NOVELA, 'Novela', 5);
        $this->assertSame('T2', $updated->title());
        $this->assertSame(5, $updated->totalCopies());
        $this->assertSame(3, $updated->availableCopies());
        try {
            $update->update('missing', 'T', 'A', Fixtures::CATEGORY_NOVELA, 'Novela', 1);
            $this->fail('Missing book must throw.');
        } catch (ApplicationException $e) {
            $this->assertSame('NOT_FOUND', $e->errorCode());
        }
    }

    public function test_delete_is_blocked_with_open_loans(): void
    {
        $create = new CreateBookService($this->books);
        $create->create('b1', 'T', 'A', '9780307474728', Fixtures::CATEGORY_NOVELA, 'Novela', 1);
        $loans = new InMemoryLoanRepository();
        $book = $this->books->findById('b1');
        $loan = new Loan('l1', 'm1', 'u1', $this->clock->now());
        $loan->addItem($book, 'i1');
        $loans->save($loan);
        try {
            (new DeleteBookService($this->books, $loans, $this->clock))->delete('b1');
            $this->fail('Book with open loans must not be deleted.');
        } catch (ApplicationException $e) {
            $this->assertSame('BOOK_HAS_OPEN_LOANS', $e->errorCode());
        }
        $this->assertTrue($this->books->findById('b1')->isActive());
    }

    public function test_cover_attach_validates_and_detach_clears(): void
    {
        $storage = new FakeCoverStorage();
        $service = new CoverService($this->books, $storage);
        (new CreateBookService($this->books))->create('b1', 'T', 'A', '9780307474728', Fixtures::CATEGORY_NOVELA, 'Novela', 1);
        $this->assertNull($this->books->findById('b1')->coverKey());
        $service->attach('b1', 'covers/b1.png', str_repeat('x', 100), 'image/png');
        $this->assertSame('covers/b1.png', $this->books->findById('b1')->coverKey());
        $this->assertTrue($storage->exists('covers/b1.png'));
        foreach ([
            ['k', 'bytes', 'image/gif'],
            ['k2', str_repeat('y', 2 * 1024 * 1024 + 1), 'image/jpeg'],
        ] as [$key, $bytes, $mime]) {
            try {
                $service->attach('b1', $key, $bytes, $mime);
                $this->fail('Invalid cover must throw.');
            } catch (ApplicationException $e) {
                $this->assertSame('VALIDATION_ERROR', $e->errorCode());
            }
        }
        $service->detach('b1');
        $this->assertNull($this->books->findById('b1')->coverKey());
        $this->assertFalse($storage->exists('covers/b1.png'));
    }
}
