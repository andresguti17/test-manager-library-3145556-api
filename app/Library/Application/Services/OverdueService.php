<?php

declare(strict_types=1);

namespace App\Library\Application\Services;

use App\Library\Application\Ports\Clock;
use App\Library\Application\Ports\LoanRepository;
use App\Library\Application\Ports\MemberRepository;

/** HU-09: unreturned lines with due_at before now, most late first. */
final class OverdueService
{
    public function __construct(
        private LoanRepository $loans,
        private MemberRepository $members,
        private Clock $clock,
    ) {}

    /** @return list<OverdueRow> */
    public function list(): array
    {
        $now = $this->clock->now();
        $rows = [];
        foreach ($this->loans->all() as $loan) {
            foreach ($loan->openItems() as $item) {
                if (!$item->isOverdue($now)) {
                    continue;
                }
                $member = $this->members->findById($loan->memberId);
                $rows[] = new OverdueRow(
                    loanId: $loan->id,
                    itemId: $item->id,
                    bookTitle: $item->bookTitle,
                    memberId: $loan->memberId,
                    memberName: $member?->fullName() ?? '',
                    dueAt: $item->dueAt,
                    daysLate: $item->daysLate($now),
                );
            }
        }
        usort($rows, static fn (OverdueRow $a, OverdueRow $b) => $b->daysLate <=> $a->daysLate);
        return $rows;
    }
}
