<?php

declare(strict_types=1);

namespace App\Library\Application\Exceptions;

final class MemberLimitExceededException extends ApplicationException
{
    public function __construct(int $current, string $message = '')
    {
        parent::__construct(
            'MEMBER_LIMIT_EXCEEDED',
            $message !== '' ? $message : sprintf('El miembro ya tiene %d libros sin devolver (máximo 3).', $current),
        );
    }
}
