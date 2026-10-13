<?php

declare(strict_types=1);

namespace App\Library\Application\Ports;

/** Cover binary storage behind an opaque key (CA-03.4). */
interface CoverStorage
{
    public function store(string $key, string $bytes, string $mimeType): void;

    public function delete(string $key): void;

    public function exists(string $key): bool;
}
