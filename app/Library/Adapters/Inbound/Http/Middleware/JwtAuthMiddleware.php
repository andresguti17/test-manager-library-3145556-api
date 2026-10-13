<?php

declare(strict_types=1);

namespace App\Library\Adapters\Inbound\Http\Middleware;

use App\Library\Adapters\Inbound\Http\Support\LibraryExceptionHandler;
use App\Library\Adapters\Outbound\Security\JwtIssuer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Verifies the Bearer JWT; 401 when missing, invalid, or expired. */
final class JwtAuthMiddleware
{
    public function __construct(private readonly JwtIssuer $tokens) {}

    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Authorization', '');
        if (!str_starts_with($header, 'Bearer ')) {
            return LibraryExceptionHandler::unauthorized('Falta el token de autenticación.');
        }
        $token = trim(substr($header, 7));
        if ($token === '') {
            return LibraryExceptionHandler::unauthorized('Falta el token de autenticación.');
        }
        $claims = $this->tokens->verify($token);
        if ($claims === null) {
            return LibraryExceptionHandler::unauthorized();
        }
        $request->attributes->set('auth', $claims);

        return $next($request);
    }
}
