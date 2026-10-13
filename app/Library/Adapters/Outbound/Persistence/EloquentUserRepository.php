<?php

declare(strict_types=1);

namespace App\Library\Adapters\Outbound\Persistence;

use App\Library\Application\Exceptions\UsernameTakenException;
use App\Library\Application\Ports\UserRepository;
use App\Library\Domain\Entities\User;
use Illuminate\Database\QueryException;

/** Eloquent implementation of the user persistence port. */
final class EloquentUserRepository implements UserRepository
{
    public function save(User $user): void
    {
        try {
            UserModel::updateOrCreate(['id' => $user->id], [
                'username' => $user->username(),
                'password_hash' => $user->passwordHash,
                'role' => $user->role->value,
            ]);
        } catch (QueryException $e) {
            if (PersistenceErrors::isUniqueViolation($e)) {
                throw new UsernameTakenException();
            }
            throw $e;
        }
    }

    public function findById(string $id): ?User
    {
        $model = UserModel::find($id);
        if ($model === null) {
            return null;
        }

        return PersistenceMapper::toUser($model);
    }

    public function findByUsername(string $username): ?User
    {
        $normalized = strtolower(trim($username));
        $model = UserModel::where('username', $normalized)->first();
        if ($model === null) {
            return null;
        }

        return PersistenceMapper::toUser($model);
    }
}
