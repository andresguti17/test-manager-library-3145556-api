<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Library\Application\Ports\PasswordHasher;

final class FakePasswordHasher implements PasswordHasher
{
    public function hash(string $plain): string
    {
        return 'fake-hash:'.$plain;
    }

    public function verify(string $plain, string $hash): bool
    {
        return $hash === $this->hash($plain);
    }
}
