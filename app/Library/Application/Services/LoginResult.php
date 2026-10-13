<?php

declare(strict_types=1);

namespace App\Library\Application\Services;

final readonly class LoginResult
{
    public function __construct(
        public string $accessToken,
        public string $tokenType,
        public int $expiresIn,
        public string $role,
    ) {}
}
