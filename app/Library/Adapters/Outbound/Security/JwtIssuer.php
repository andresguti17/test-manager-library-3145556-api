<?php

declare(strict_types=1);

namespace App\Library\Adapters\Outbound\Security;

use App\Library\Application\Ports\Clock;
use App\Library\Application\Ports\TokenIssuer;
use DateTimeImmutable;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use RuntimeException;
use Throwable;

/**
 * HS256 JWT issuance. Claims carry only sub + role plus iat/exp;
 * no personal data inside. The secret always comes from the environment
 * (Article IX) via the composition root.
 */
final class JwtIssuer implements TokenIssuer
{
    public function __construct(
        private readonly string $secret,
        private readonly Clock $clock,
    ) {}

    public function issue(string $subject, string $role, DateTimeImmutable $expiresAt): string
    {
        $secret = $this->secret();
        $now = $this->clock->now();

        return JWT::encode(
            [
                'sub' => $subject,
                'role' => $role,
                'iat' => $now->getTimestamp(),
                'exp' => $expiresAt->getTimestamp(),
            ],
            $secret,
            'HS256',
        );
    }

    /**
     * Verifies a bearer token.
     *
     * @return array{sub: string, role: string}|null claims when valid
     */
    public function verify(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret(), 'HS256'));
        } catch (Throwable) {
            return null;
        }
        $subject = $decoded->sub ?? null;
        $role = $decoded->role ?? null;
        if (!is_string($subject) || $subject === '') {
            return null;
        }
        if (!in_array($role, ['admin', 'librarian'], true)) {
            return null;
        }

        return ['sub' => $subject, 'role' => $role];
    }

    private function secret(): string
    {
        if ($this->secret === '') {
            throw new RuntimeException('JWT_SECRET no está configurado.');
        }
        if (strlen($this->secret) < 32) {
            throw new RuntimeException('JWT_SECRET debe tener al menos 32 caracteres.');
        }

        return $this->secret;
    }
}
