<?php

declare(strict_types=1);

namespace App\Library\Application\Ports;

use App\Library\Domain\Entities\Member;

interface MemberRepository
{
    public function save(Member $member): void;

    public function findById(string $id): ?Member;

    public function findByDocument(string $document): ?Member;

    /** @return array{items: list<Member>, total: int} */
    public function search(?string $query, int $page, int $size): array;
}
