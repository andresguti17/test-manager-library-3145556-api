<?php

declare(strict_types=1);

namespace App\Library\Application\Ports;

use App\Library\Domain\Entities\User;

interface UserRepository
{
    public function save(User $user): void;

    public function findById(string $id): ?User;

    /** Lookup by normalized (trimmed, lowercase) username. */
    public function findByUsername(string $username): ?User;
}
