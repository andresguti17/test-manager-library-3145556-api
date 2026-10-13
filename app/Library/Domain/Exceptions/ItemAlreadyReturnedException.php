<?php

declare(strict_types=1);

namespace App\Library\Domain\Exceptions;

final class ItemAlreadyReturnedException extends DomainException
{
    public function __construct(string $message = 'La línea ya fue devuelta.')
    {
        parent::__construct('ITEM_ALREADY_RETURNED', $message);
    }
}
