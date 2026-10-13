<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Library\Application\Ports\MemberRepository;
use App\Library\Domain\Entities\Member;

final class InMemoryMemberRepository implements MemberRepository
{
    /** @var array<string, Member> */
    private array $members = [];

    public function save(Member $member): void
    {
        $this->members[$member->id] = $member;
    }

    public function findById(string $id): ?Member
    {
        return $this->members[$id] ?? null;
    }

    public function findByDocument(string $document): ?Member
    {
        foreach ($this->members as $member) {
            if ($member->document->value === $document) {
                return $member;
            }
        }
        return null;
    }

    public function search(?string $query, int $page, int $size): array
    {
        $q = $query !== null ? strtolower(trim($query)) : '';
        $filtered = array_filter(
            $this->members,
            static fn (Member $m) => $q === ''
                || str_contains(strtolower($m->fullName()), $q)
                || str_contains(strtolower($m->document->value), $q),
        );
        $total = count($filtered);
        $items = array_slice(array_values($filtered), ($page - 1) * $size, $size);
        return ['items' => $items, 'total' => $total];
    }
}
