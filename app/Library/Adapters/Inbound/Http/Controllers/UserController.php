<?php

declare(strict_types=1);

namespace App\Library\Adapters\Inbound\Http\Controllers;

use App\Library\Adapters\Inbound\Http\Requests\CreateUserRequest;
use App\Library\Adapters\Inbound\Http\Resources\UserResource;
use App\Library\Application\Services\CreateLibrarianService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class UserController
{
    public function store(CreateUserRequest $request, CreateLibrarianService $users): JsonResponse
    {
        $data = $request->validated();
        $user = $users->create(
            Str::uuid()->toString(),
            (string) $data['username'],
            (string) $data['password'],
            (string) $data['role'],
        );

        return response()->json(UserResource::present($user), 201);
    }
}
