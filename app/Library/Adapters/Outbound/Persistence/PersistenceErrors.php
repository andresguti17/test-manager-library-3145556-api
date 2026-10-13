<?php

declare(strict_types=1);

namespace App\Library\Adapters\Outbound\Persistence;

use Illuminate\Database\QueryException;

/** Classifies driver errors so repositories can map them to stable codes. */
final class PersistenceErrors
{
    public static function isUniqueViolation(QueryException $e): bool
    {
        $sqlState = (string) $e->getCode();
        if (in_array($sqlState, ['23000', '23505'], true)) {
            return true;
        }
        $message = $e->getMessage();

        return str_contains($message, 'UNIQUE constraint')
            || str_contains($message, 'duplicate key')
            || str_contains($message, 'Duplicate entry');
    }

    public static function violatesCheck(QueryException $e, string $constraint): bool
    {
        return str_contains($e->getMessage(), $constraint);
    }
}
