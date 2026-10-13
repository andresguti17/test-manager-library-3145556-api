<?php

declare(strict_types=1);

namespace App\Library\Adapters\Inbound\Http\Support;

use App\Library\Application\Exceptions\ApplicationException;
use App\Library\Domain\Exceptions\DomainException;
use Illuminate\Http\JsonResponse;

/**
 * Translates domain/application errors into the single contract body
 * {code, message, details} with the contract's HTTP statuses
 * (architecture §5). A framework error never reaches the client as-is.
 */
final class LibraryExceptionHandler
{
    /** @var array<string, int> */
    private const STATUS_BY_CODE = [
        'INVALID_CREDENTIALS' => 401,
        'FORBIDDEN_ROLE' => 403,
        'NOT_FOUND' => 404,
        'USERNAME_TAKEN' => 409,
        'ISBN_TAKEN' => 409,
        'DOCUMENT_TAKEN' => 409,
        'BOOK_HAS_OPEN_LOANS' => 409,
        'BOOK_NOT_AVAILABLE' => 409,
        'MEMBER_HAS_OVERDUE' => 409,
        'ITEM_ALREADY_RETURNED' => 409,
        'MEMBER_LIMIT_EXCEEDED' => 422,
        'DUPLICATE_BOOK_IN_LOAN' => 422,
        'VALIDATION_ERROR' => 422,
        'INVALID_DATE_RANGE' => 422,
    ];

    public static function statusFor(string $code): int
    {
        return self::STATUS_BY_CODE[$code] ?? 500;
    }

    public static function render(ApplicationException|DomainException $e): JsonResponse
    {
        return self::body($e->errorCode(), $e->getMessage(), self::statusFor($e->errorCode()));
    }

    /**
     * @param list<array{field: string, issue: string}> $details
     */
    public static function body(string $code, string $message, int $status, array $details = []): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'details' => $details,
        ], $status);
    }

    public static function notFound(string $message = 'El recurso no existe.'): JsonResponse
    {
        return self::body('NOT_FOUND', $message, 404);
    }

    public static function unauthorized(string $message = 'Token inválido o expirado.'): JsonResponse
    {
        return self::body('INVALID_CREDENTIALS', $message, 401);
    }

    public static function forbidden(): JsonResponse
    {
        return self::body('FORBIDDEN_ROLE', 'No tiene permiso para realizar esta operación.', 403);
    }
}
