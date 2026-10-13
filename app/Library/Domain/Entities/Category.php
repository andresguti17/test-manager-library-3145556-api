<?php

declare(strict_types=1);

namespace App\Library\Domain\Entities;

/**
 * Fixed classification of five, seeded by migration (data-model §9).
 * Read-only: no maintenance endpoint exists.
 */
final readonly class Category
{
    public function __construct(
        public string $id,
        public string $name,
    ) {}

    /** @return list<self> */
    public static function fixed(): array
    {
        return [
            new self('11111111-1111-4111-8111-111111111111', 'Novela'),
            new self('22222222-2222-4222-8222-222222222222', 'Ciencia'),
            new self('33333333-3333-4333-8333-333333333333', 'Tecnología'),
            new self('44444444-4444-4444-8444-444444444444', 'Historia'),
            new self('55555555-5555-4555-8555-555555555555', 'Infantil'),
        ];
    }
}
