<?php

declare(strict_types=1);

namespace Tests\Unit\Library\Domain;

use App\Library\Domain\Exceptions\DomainException;
use App\Library\Domain\ValueObjects\DateRange;
use App\Library\Domain\ValueObjects\DocumentNumber;
use App\Library\Domain\ValueObjects\Isbn;
use App\Library\Domain\ValueObjects\Quantity;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/** T-03: Isbn, Quantity/TotalCopies, DocumentNumber, DateRange. */
final class ValueObjectsTest extends TestCase
{
    public function test_isbn_accepts_13_digits(): void
    {
        $isbn = new Isbn('9780307474728');
        $this->assertSame('9780307474728', $isbn->value);
        $this->assertSame('9780307474728', (string) $isbn);
        $this->assertTrue($isbn->equals(new Isbn('9780307474728')));
        $this->assertFalse($isbn->equals(new Isbn('9780307474729')));
    }

    #[TestDox('ISBN rejects non 13-digit values')]
    #[DataProvider('invalidIsbns')]
    public function test_isbn_rejects_non_13_digit_values(string $value): void
    {
        $this->expectException(DomainException::class);
        new Isbn($value);
    }

    /** @return array<string, array{string}> */
    public static function invalidIsbns(): array
    {
        return [
            'too short' => ['978030747472'],
            'too long' => ['97803074747280'],
            'letters' => ['978030747472X'],
            'with dashes' => ['978-0307474728'],
            'empty' => [''],
            'spaces' => [' 9780307474728 '],
        ];
    }

    public function test_quantity_requires_at_least_one(): void
    {
        $this->assertSame(1, (new Quantity(1))->value);
        $this->assertSame(5, (new Quantity(5))->value);
        try {
            new Quantity(0);
            $this->fail('Quantity(0) must throw.');
        } catch (DomainException $e) {
            $this->assertSame('VALIDATION_ERROR', $e->errorCode());
        }
        $this->expectException(DomainException::class);
        new Quantity(-3);
    }

    public function test_document_number_is_required_and_trimmed(): void
    {
        $this->assertSame('ABC123', (new DocumentNumber('  ABC123 '))->value);
        $this->expectException(DomainException::class);
        new DocumentNumber('   ');
    }

    public function test_date_range_rejects_end_before_start(): void
    {
        $start = new DateTimeImmutable('2026-10-01T00:00:00Z');
        $range = new DateRange($start, new DateTimeImmutable('2026-10-31T00:00:00Z'));
        $this->assertTrue($range->contains(new DateTimeImmutable('2026-10-15T00:00:00Z')));
        $this->assertFalse($range->contains(new DateTimeImmutable('2026-11-01T00:00:00Z')));
        // Equal bounds are a valid single-instant range.
        new DateRange($start, $start);
        try {
            new DateRange($start, new DateTimeImmutable('2026-09-30T23:59:59Z'));
            $this->fail('Inverted range must throw.');
        } catch (DomainException $e) {
            $this->assertSame('INVALID_DATE_RANGE', $e->errorCode());
        }
    }
}
