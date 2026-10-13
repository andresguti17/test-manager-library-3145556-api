<?php

declare(strict_types=1);

namespace App\Library\Adapters\Inbound\Http\Controllers;

use App\Library\Adapters\Outbound\Persistence\CategoryModel;
use Illuminate\Http\JsonResponse;

final class CategoryController
{
    public function index(): JsonResponse
    {
        $categories = CategoryModel::orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (CategoryModel $c) => ['id' => $c->id, 'name' => $c->name])
            ->all();

        return response()->json(array_values($categories));
    }
}
