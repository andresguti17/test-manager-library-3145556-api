<?php

declare(strict_types=1);

namespace App\Library\Adapters\Inbound\Http\Resources;

use App\Library\Domain\Entities\Member;

/** Contract JSON shape of a member; detail adds the unreturned count. */
final class MemberResource
{
    /** @return array<string, mixed> */
    public static function present(Member $member): array
    {
        return [
            'id' => $member->id,
            'full_name' => $member->fullName(),
            'document_number' => $member->document->value,
        ];
    }

    /** @return array<string, mixed> */
    public static function presentDetail(Member $member, int $activeLoans): array
    {
        return self::present($member) + ['active_loans' => $activeLoans];
    }
}
