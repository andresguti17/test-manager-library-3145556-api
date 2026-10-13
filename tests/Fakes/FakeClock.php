<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Library\Application\Ports\Clock;
use DateTimeImmutable;

/** Controllable clock for time-dependent tests (T-05). */
final class FakeClock implements Clock
{
    public function __construct(private DateTimeImmutable $now) {}

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function travelTo(DateTimeImmutable $now): void
    {
        $this->now = $now;
    }

    public function travelDays(int $days): void
    {
        $this->now = $this->now->modify(sprintf('%+d days', $days));
    }
}
