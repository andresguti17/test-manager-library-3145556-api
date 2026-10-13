<?php

declare(strict_types=1);

namespace App\Library\Domain\Entities;

use App\Library\Domain\Exceptions\InvalidMemberException;
use App\Library\Domain\ValueObjects\DocumentNumber;

/** Member: a borrower. Data, not a user — never deleted (CA-05.3). */
final class Member
{
    private string $fullName;

    public function __construct(
        public readonly string $id,
        string $fullName,
        public readonly DocumentNumber $document,
    ) {
        $this->setFullName($fullName);
    }

    public function fullName(): string
    {
        return $this->fullName;
    }

    public function rename(string $fullName): void
    {
        $this->setFullName($fullName);
    }

    private function setFullName(string $fullName): void
    {
        $trimmed = trim($fullName);
        if ($trimmed === '') {
            throw new InvalidMemberException('El nombre del miembro es obligatorio.');
        }
        $this->fullName = $trimmed;
    }
}
