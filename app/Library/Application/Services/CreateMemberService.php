<?php

declare(strict_types=1);

namespace App\Library\Application\Services;

use App\Library\Application\Exceptions\DocumentTakenException;
use App\Library\Application\Ports\MemberRepository;
use App\Library\Domain\Entities\Member;
use App\Library\Domain\ValueObjects\DocumentNumber;

/** HU-05: admins and librarians create members; document is unique. */
final class CreateMemberService
{
    public function __construct(private MemberRepository $members) {}

    public function create(string $id, string $fullName, string $documentNumber): Member
    {
        $document = new DocumentNumber($documentNumber);
        if ($this->members->findByDocument($document->value) !== null) {
            throw new DocumentTakenException();
        }
        $member = new Member($id, $fullName, $document);
        $this->members->save($member);
        return $member;
    }
}
