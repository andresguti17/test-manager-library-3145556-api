<?php

declare(strict_types=1);

namespace App\Library\Adapters\Inbound\Http\Controllers;

use App\Library\Adapters\Inbound\Http\Requests\CreateMemberRequest;
use App\Library\Adapters\Inbound\Http\Requests\ListMembersRequest;
use App\Library\Adapters\Inbound\Http\Resources\MemberResource;
use App\Library\Adapters\Inbound\Http\Support\LibraryExceptionHandler;
use App\Library\Application\Ports\LoanRepository;
use App\Library\Application\Ports\MemberRepository;
use App\Library\Application\Services\CreateMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class MemberController
{
    public function index(ListMembersRequest $request, MemberRepository $members): JsonResponse
    {
        $data = $request->validated();
        $page = isset($data['page']) ? (int) $data['page'] : 1;
        $size = isset($data['size']) ? (int) $data['size'] : 20;
        $result = $members->search(
            isset($data['q']) ? (string) $data['q'] : null,
            $page,
            $size,
        );

        return response()->json([
            'items' => array_map(
                static fn ($member) => MemberResource::present($member),
                $result['items'],
            ),
            'page' => $page,
            'size' => $size,
            'total' => $result['total'],
        ]);
    }

    public function show(string $id, MemberRepository $members, LoanRepository $loans): JsonResponse
    {
        $member = $members->findById($id);
        if ($member === null) {
            return LibraryExceptionHandler::notFound('El miembro no existe.');
        }
        $activeLoans = 0;
        foreach ($loans->findOpenLoansByMember($id) as $loan) {
            $activeLoans += $loan->openCount();
        }

        return response()->json(MemberResource::presentDetail($member, $activeLoans));
    }

    public function store(CreateMemberRequest $request, CreateMemberService $create): JsonResponse
    {
        $data = $request->validated();
        $member = $create->create(
            Str::uuid()->toString(),
            (string) $data['full_name'],
            (string) $data['document_number'],
        );

        return response()->json(MemberResource::present($member), 201);
    }
}
