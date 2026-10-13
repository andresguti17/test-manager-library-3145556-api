<?php

declare(strict_types=1);

namespace App\Library\Application\Services;

use App\Library\Application\Exceptions\UsernameTakenException;
use App\Library\Application\Exceptions\ValidationException;
use App\Library\Application\Ports\PasswordHasher;
use App\Library\Application\Ports\UserRepository;
use App\Library\Domain\Entities\User;
use App\Library\Domain\Enums\UserRole;

/** HU-02: the admin registers librarians. Nobody grants admin at runtime. */
final class CreateLibrarianService
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
    ) {}

    public function create(string $id, string $username, string $password, string $role): User
    {
        if ($role !== UserRole::Librarian->value) {
            throw new ValidationException('El rol debe ser «librarian».');
        }
        if (trim($password) === '') {
            throw new ValidationException('La contraseña es obligatoria.');
        }
        $normalized = strtolower(trim($username));
        if ($normalized === '') {
            throw new ValidationException('El nombre de usuario es obligatorio.');
        }
        if ($this->users->findByUsername($normalized) !== null) {
            throw new UsernameTakenException();
        }
        $user = new User($id, $normalized, $this->hasher->hash($password), UserRole::Librarian);
        $this->users->save($user);
        return $user;
    }
}
