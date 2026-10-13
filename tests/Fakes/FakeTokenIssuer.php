<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Library\Application\Ports\TokenIssuer;
use DateTimeImmutable;

final class FakeTokenIssuer implements TokenIssuer
{
    public string $lastSubject = '';
    public string $lastRole = '';
    public ?DateTimeImmutable $lastExpiresAt = null;

    public function issue(string $subject, string $role, DateTimeImmutable $expiresAt): string
    {
        $this->lastSubject = $subject;
        $this->lastRole = $role;
        $this->lastExpiresAt = $expiresAt;
        return 'fake-token:'.$subject.':'.$role;
    }
}
