<?php

declare(strict_types=1);

namespace App\Library\Application\Ports;

use DateTimeImmutable;

/** Source of UTC instants (fakeable in tests). */
interface Clock
{
    public function now(): DateTimeImmutable;
}
