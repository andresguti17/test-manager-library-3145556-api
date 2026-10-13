<?php

declare(strict_types=1);

namespace App\Library\Domain\Entities;

use App\Library\Domain\Enums\UserRole;
use App\Library\Domain\Exceptions\InvalidUserException;

/**
 * Internal operator (admin or librarian).
 *
 * The username is stored lowercased and trimmed (CA-02.2); the password
 * hash is opaque to the domain — hashing is a port (Article II).
 */
final class User
{
    private string $username;

    public function __construct(
        public readonly string $id,
        string $username,
        public readonly string $passwordHash,
        public readonly UserRole $role,
    ) {
        $normalized = strtolower(trim($username));
        if ($normalized === '') {
            throw new InvalidUserException('El nombre de usuario es obligatorio.');
        }
        if ($this->passwordHash === '') {
            throw new InvalidUserException('El hash de contraseña es obligatorio.');
        }
        $this->username = $normalized;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }
}
