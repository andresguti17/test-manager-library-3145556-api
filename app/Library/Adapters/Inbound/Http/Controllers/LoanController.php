<?php

declare(strict_types=1);

namespace App\Library\Adapters\Inbound\Http\Controllers;

use App\Library\Adapters\Inbound\Http\Requests\CreateLoanRequest;
use App\Library\Adapters\Inbound\Http\Requests\ListLoansRequest;
use App\Library\Adapters\Inbound\Http\Resources\LoanResource;
use App\Library\Adapters\Inbound\Http\Support\LibraryExceptionHandler;
use App\Library\Application\Exceptions\ValidationException;
use App\Library\Application\Ports\Clock;
use App\Library\Application\Ports\LoanRepository;
use App\Library\Application\Services\LendBooksService;
use App\Library\Application\Services\OverdueService;
use App\Library\Application\Services\ReturnItemService;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class LoanController
{
    public function store(
        CreateLoanRequest $request,
        LendBooksService $lend,
        Clock $clock,
    ): JsonResponse {
        $data = $request->validated();
        /** @var list<array{book_id: string}> $lines */
        $lines = $data['items'];
        $bookIds = array_map(static fn (array $line) => (string) $line['book_id'], $lines);
        $itemIds = array_map(static fn () => Str::uuid()->toString(), $bookIds);
        $loan = $lend->lend(
            Str::uuid()->toString(),
            (string) $data['member_id'],
            $bookIds,
            $this->authSubject($request),
            $itemIds,
        );

        return response()->json(LoanResource::present($loan, $clock->now()), 201);
    }

    public function index(
        ListLoansRequest $request,
        LoanRepository $loans,
        Clock $clock,
    ): JsonResponse {
        $data = $request->validated();
        $now = $clock->now();
        $from = $this->parseBound($data['from'] ?? null, false);
        $to = $this->parseBound($data['to'] ?? null, true);
        $status = isset($data['status']) ? (string) $data['status'] : null;
        $memberId = isset($data['member_id']) ? (string) $data['member_id'] : null;
        $page = isset($data['page']) ? (int) $data['page'] : 1;
        $size = isset($data['size']) ? (int) $data['size'] : 20;

        $filtered = [];
        foreach ($loans->all() as $loan) {
            if ($memberId !== null && $loan->memberId !== $memberId) {
                continue;
            }
            if ($from !== null && $loan->loanedAt < $from) {
                continue;
            }
            if ($to !== null && $loan->loanedAt > $to) {
                continue;
            }
            if ($status !== null && $loan->status($now)->value !== $status) {
                continue;
            }
            $filtered[] = $loan;
        }
        usort($filtered, static fn ($a, $b) => $b->loanedAt <=> $a->loanedAt ?: strcmp($a->id, $b->id));
        $total = count($filtered);
        $items = array_slice($filtered, ($page - 1) * $size, $size);

        return response()->json([
            'items' => array_map(
                static fn ($loan) => LoanResource::present($loan, $now),
                $items,
            ),
            'page' => $page,
            'size' => $size,
            'total' => $total,
        ]);
    }

    public function show(string $id, LoanRepository $loans, Clock $clock): JsonResponse
    {
        $loan = $loans->findById($id);
        if ($loan === null) {
            return LibraryExceptionHandler::notFound('El préstamo no existe.');
        }

        return response()->json(LoanResource::present($loan, $clock->now()));
    }

    public function returnItem(
        string $loanId,
        string $itemId,
        ReturnItemService $returns,
    ): JsonResponse {
        $item = $returns->returnItem($loanId, $itemId);

        return response()->json(LoanResource::presentItem($item));
    }

    public function overdue(OverdueService $overdue): JsonResponse
    {
        $rows = $overdue->list();

        return response()->json([
            'items' => array_map(
                static fn ($row) => LoanResource::presentOverdue($row),
                $rows,
            ),
        ]);
    }

    private function authSubject(Request $request): string
    {
        $auth = $request->attributes->get('auth');

        return is_array($auth) && isset($auth['sub']) ? (string) $auth['sub'] : '';
    }

    private function parseBound(mixed $value, bool $endOfDay): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }
        $utc = new DateTimeZone('UTC');
        try {
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
                return new DateTimeImmutable(
                    $value.($endOfDay ? ' 23:59:59' : ' 00:00:00'),
                    $utc,
                );
            }

            return (new DateTimeImmutable((string) $value))->setTimezone($utc);
        } catch (\Throwable) {
            throw new ValidationException('El rango de fechas no es válido.');
        }
    }
}
