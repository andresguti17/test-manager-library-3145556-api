<?php

declare(strict_types=1);

namespace App\Library\Adapters\Inbound\Http\Middleware;

use App\Library\Adapters\Inbound\Http\Support\LibraryExceptionHandler;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role check. Runs after JwtAuth and before any resource lookup, so a 403
 * never reveals whether the resource exists.
 */
final class RequireRoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $auth = $request->attributes->get('auth');
        if (!is_array($auth) || !isset($auth['role'])) {
            return LibraryExceptionHandler::unauthorized('Falta el token de autenticación.');
        }
        if (!in_array($auth['role'], $roles, true)) {
            return LibraryExceptionHandler::forbidden();
        }

        return $next($request);
    }
}
