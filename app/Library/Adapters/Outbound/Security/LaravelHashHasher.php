<?php

declare(strict_types=1);

namespace App\Library\Adapters\Outbound\Security;

use App\Library\Application\Ports\PasswordHasher;
use Illuminate\Support\Facades\Hash;

/** Bcrypt password hashing through Laravel's Hash facade. */
final class LaravelHashHasher implements PasswordHasher
{
    public function hash(string $plain): string
    {
        return Hash::make($plain);
    }

    public function verify(string $plain, string $hash): bool
    {
        return Hash::check($plain, $hash);
    }
}
