<?php

declare(strict_types=1);

namespace App\Library\Domain\Enums;

/** Computed loan status (Article VII): never stored. */
enum LoanStatus: string
{
    case Open = 'open';
    case Overdue = 'overdue';
    case Closed = 'closed';
}
