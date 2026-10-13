<?php

declare(strict_types=1);

namespace App\Library\Adapters\Outbound\Security;

use App\Library\Application\Ports\Clock;
use DateTimeImmutable;
use DateTimeZone;

/** System UTC clock. */
final class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
