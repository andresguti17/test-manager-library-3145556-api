<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Library\Application\Ports\UserRepository;
use App\Library\Domain\Entities\User;

final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, User> */
    private array $users = [];

    public function save(User $user): void
    {
        $this->users[$user->id] = $user;
    }

    public function findById(string $id): ?User
    {
        return $this->users[$id] ?? null;
    }

    public function findByUsername(string $username): ?User
    {
        $normalized = strtolower(trim($username));
        foreach ($this->users as $user) {
            if ($user->username() === $normalized) {
                return $user;
            }
        }
        return null;
    }
}
