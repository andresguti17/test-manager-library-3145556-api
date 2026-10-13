<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Library\Application\Ports\CoverStorage;

final class FakeCoverStorage implements CoverStorage
{
    /** @var array<string, array{bytes: string, mime: string}> */
    private array $objects = [];

    public function store(string $key, string $bytes, string $mimeType): void
    {
        $this->objects[$key] = ['bytes' => $bytes, 'mime' => $mimeType];
    }

    public function delete(string $key): void
    {
        unset($this->objects[$key]);
    }

    public function exists(string $key): bool
    {
        return isset($this->objects[$key]);
    }
}
