<?php

declare(strict_types=1);

namespace App\Library\Adapters\Inbound\Http\Resources;

use App\Library\Domain\Entities\User;

/** Contract JSON shape of a user (never includes any password hash). */
final class UserResource
{
    /** @return array<string, mixed> */
    public static function present(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username(),
            'role' => $user->role->value,
        ];
    }
}
