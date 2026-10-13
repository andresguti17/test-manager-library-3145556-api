<?php

declare(strict_types=1);

namespace App\Library\Application\Ports;

use DateTimeImmutable;

interface TokenIssuer
{
    /** Issues an opaque signed token; no personal data inside. */
    public function issue(string $subject, string $role, DateTimeImmutable $expiresAt): string;
}
