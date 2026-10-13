<?php

declare(strict_types=1);

namespace App\Library\Adapters\Outbound\Persistence;

use App\Library\Application\Exceptions\DocumentTakenException;
use App\Library\Application\Ports\MemberRepository;
use App\Library\Domain\Entities\Member;
use Illuminate\Database\QueryException;

/** Eloquent implementation of the member persistence port. */
final class EloquentMemberRepository implements MemberRepository
{
    public function save(Member $member): void
    {
        try {
            MemberModel::updateOrCreate(['id' => $member->id], [
                'full_name' => $member->fullName(),
                'document_number' => $member->document->value,
            ]);
        } catch (QueryException $e) {
            if (PersistenceErrors::isUniqueViolation($e)) {
                throw new DocumentTakenException();
            }
            throw $e;
        }
    }

    public function findById(string $id): ?Member
    {
        $model = MemberModel::find($id);
        if ($model === null) {
            return null;
        }

        return PersistenceMapper::toMember($model);
    }

    public function findByDocument(string $document): ?Member
    {
        $model = MemberModel::where('document_number', $document)->first();
        if ($model === null) {
            return null;
        }

        return PersistenceMapper::toMember($model);
    }

    public function search(?string $query, int $page, int $size): array
    {
        $builder = MemberModel::query();
        $q = $query !== null ? strtolower(trim($query)) : '';
        if ($q !== '') {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%';
            $builder->where(function ($inner) use ($like) {
                $inner->whereRaw('LOWER(full_name) LIKE ? ESCAPE ?', [$like, '\\'])
                    ->orWhereRaw('LOWER(document_number) LIKE ? ESCAPE ?', [$like, '\\']);
            });
        }
        $total = (clone $builder)->count();
        $offset = max(0, ($page - 1) * $size);
        $items = $builder->orderBy('full_name')->orderBy('id')
            ->offset($offset)->limit($size)->get()
            ->map(static fn (MemberModel $m) => PersistenceMapper::toMember($m))
            ->all();

        return ['items' => array_values($items), 'total' => $total];
    }
}
