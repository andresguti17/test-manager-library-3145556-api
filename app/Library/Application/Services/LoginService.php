<?php

declare(strict_types=1);

namespace App\Library\Application\Services;

use App\Library\Application\Exceptions\InvalidCredentialsException;
use App\Library\Application\Ports\Clock;
use App\Library\Application\Ports\PasswordHasher;
use App\Library\Application\Ports\TokenIssuer;
use App\Library\Application\Ports\UserRepository;

/** HU-01: authenticate by username + password, issue an 8-hour token. */
final class LoginService
{
    public const TOKEN_TTL_SECONDS = 28800;

    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
        private TokenIssuer $tokens,
        private Clock $clock,
    ) {}

    /**
     * @throws InvalidCredentialsException without revealing whether the
     * username or the password failed (CA-01.2).
     */
    public function login(string $username, string $password): LoginResult
    {
        $user = $this->users->findByUsername(strtolower(trim($username)));
        if ($user === null || !$this->hasher->verify($password, $user->passwordHash)) {
            throw new InvalidCredentialsException();
        }
        $expiresAt = $this->clock->now()->modify(sprintf('+%d seconds', self::TOKEN_TTL_SECONDS));
        return new LoginResult(
            accessToken: $this->tokens->issue($user->id, $user->role->value, $expiresAt),
            tokenType: 'Bearer',
            expiresIn: self::TOKEN_TTL_SECONDS,
            role: $user->role->value,
        );
    }
}
