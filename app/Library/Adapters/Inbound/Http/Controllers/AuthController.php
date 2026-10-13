<?php

declare(strict_types=1);

namespace App\Library\Adapters\Inbound\Http\Controllers;

use App\Library\Adapters\Inbound\Http\Requests\LoginRequest;
use App\Library\Application\Services\LoginService;
use Illuminate\Http\JsonResponse;

final class AuthController
{
    public function login(LoginRequest $request, LoginService $login): JsonResponse
    {
        $data = $request->validated();
        $result = $login->login((string) $data['username'], (string) $data['password']);

        return response()->json([
            'access_token' => $result->accessToken,
            'token_type' => $result->tokenType,
            'expires_in' => $result->expiresIn,
            'role' => $result->role,
        ]);
    }
}
