<?php

declare(strict_types=1);

namespace App\Library\Domain\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Librarian = 'librarian';
}
