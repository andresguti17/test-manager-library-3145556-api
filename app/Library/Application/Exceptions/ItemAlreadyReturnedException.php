<?php

declare(strict_types=1);

namespace App\Library\Application\Exceptions;

final class ItemAlreadyReturnedException extends ApplicationException
{
    public function __construct(string $message = 'La línea ya fue devuelta.')
    {
        parent::__construct('ITEM_ALREADY_RETURNED', $message);
    }
}
