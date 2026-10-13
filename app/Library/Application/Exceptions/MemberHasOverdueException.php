<?php

declare(strict_types=1);

namespace App\Library\Application\Exceptions;

final class MemberHasOverdueException extends ApplicationException
{
    public function __construct(string $message = 'El miembro tiene libros vencidos y no puede pedir más hasta devolverlos.')
    {
        parent::__construct('MEMBER_HAS_OVERDUE', $message);
    }
}
