<?php

use App\Library\Adapters\Inbound\Http\Middleware\JwtAuthMiddleware;
use App\Library\Adapters\Inbound\Http\Middleware\RequireRoleMiddleware;
use App\Library\Adapters\Inbound\Http\Support\LibraryExceptionHandler;
use App\Library\Adapters\Outbound\Persistence\PersistenceErrors;
use App\Library\Application\Exceptions\ApplicationException;
use App\Library\Domain\Exceptions\DomainException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt' => JwtAuthMiddleware::class,
            'role' => RequireRoleMiddleware::class,
        ]);
        $middleware->append(Illuminate\Http\Middleware\HandleCors::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ApplicationException $e, Request $request) {
            if (!str_starts_with($request->path(), 'api/')) {
                return null;
            }

            return LibraryExceptionHandler::render($e);
        });
        $exceptions->render(function (DomainException $e, Request $request) {
            if (!str_starts_with($request->path(), 'api/')) {
                return null;
            }

            return LibraryExceptionHandler::render($e);
        });
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if (!str_starts_with($request->path(), 'api/')) {
                return null;
            }

            return LibraryExceptionHandler::notFound();
        });
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (!str_starts_with($request->path(), 'api/')) {
                return null;
            }

            return LibraryExceptionHandler::unauthorized('Falta el token de autenticación.');
        });
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (!str_starts_with($request->path(), 'api/')) {
                return null;
            }

            return LibraryExceptionHandler::notFound();
        });
        $exceptions->render(function (QueryException $e, Request $request) {
            if (!str_starts_with($request->path(), 'api/')) {
                return null;
            }
            $message = $e->getMessage();
            if (str_contains($message, 'book_isbn_active_unique')) {
                return LibraryExceptionHandler::body('ISBN_TAKEN', 'El ISBN ya está registrado en otro libro activo.', 409);
            }
            if (PersistenceErrors::isUniqueViolation($e)) {
                if (str_contains($message, 'library.user') || str_contains($message, '"username"')) {
                    return LibraryExceptionHandler::body('USERNAME_TAKEN', 'El nombre de usuario ya está en uso.', 409);
                }
                if (str_contains($message, 'library.member') || str_contains($message, 'document_number')) {
                    return LibraryExceptionHandler::body('DOCUMENT_TAKEN', 'El documento ya está registrado.', 409);
                }
            }
            if (PersistenceErrors::violatesCheck($e, 'ck_book_available_range')) {
                return LibraryExceptionHandler::body('BOOK_NOT_AVAILABLE', 'No quedan ejemplares disponibles.', 409);
            }

            return LibraryExceptionHandler::body('INTERNAL_ERROR', 'Error interno del servidor.', 500);
        });
        $exceptions->render(function (Throwable $e, Request $request) {
            if (!str_starts_with($request->path(), 'api/')) {
                return null;
            }
            if ($e instanceof HttpResponseException || $e instanceof HttpExceptionInterface) {
                return null;
            }

            return LibraryExceptionHandler::body('INTERNAL_ERROR', 'Error interno del servidor.', 500);
        });
    })->create();
