<?php

declare(strict_types=1);

namespace App\Library\Adapters\Inbound\Http\Controllers;

use App\Library\Adapters\Inbound\Http\Requests\CreateBookRequest;
use App\Library\Adapters\Inbound\Http\Requests\SearchBooksRequest;
use App\Library\Adapters\Inbound\Http\Requests\UpdateBookRequest;
use App\Library\Adapters\Inbound\Http\Requests\UploadCoverRequest;
use App\Library\Adapters\Inbound\Http\Resources\BookResource;
use App\Library\Adapters\Inbound\Http\Support\LibraryExceptionHandler;
use App\Library\Adapters\Outbound\Persistence\CategoryModel;
use App\Library\Adapters\Outbound\Storage\DiskCoverStorage;
use App\Library\Application\Exceptions\NotFoundException;
use App\Library\Application\Exceptions\ValidationException;
use App\Library\Application\Ports\BookRepository;
use App\Library\Application\Services\CoverService;
use App\Library\Application\Services\CreateBookService;
use App\Library\Application\Services\DeleteBookService;
use App\Library\Application\Services\SearchBooksService;
use App\Library\Application\Services\UpdateBookService;
use finfo;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class BookController
{
    public function index(SearchBooksRequest $request, SearchBooksService $search): JsonResponse
    {
        $data = $request->validated();
        $result = $search->search(
            isset($data['q']) ? (string) $data['q'] : null,
            isset($data['category_id']) ? (string) $data['category_id'] : null,
            isset($data['page']) ? (int) $data['page'] : 1,
            isset($data['size']) ? (int) $data['size'] : 20,
        );

        return response()->json([
            'items' => array_map(
                static fn ($book) => BookResource::present($book),
                $result['items'],
            ),
            'page' => $result['page'],
            'size' => $result['size'],
            'total' => $result['total'],
        ]);
    }

    public function show(string $id, BookRepository $books): JsonResponse
    {
        $book = $books->findById($id);
        if ($book === null || !$book->isActive()) {
            return LibraryExceptionHandler::notFound('El libro no existe.');
        }

        return response()->json(BookResource::present($book));
    }

    public function store(CreateBookRequest $request, CreateBookService $create): JsonResponse
    {
        $data = $request->validated();
        $categoryId = (string) $data['category_id'];
        $book = $create->create(
            Str::uuid()->toString(),
            (string) $data['title'],
            (string) $data['author'],
            (string) $data['isbn'],
            $categoryId,
            $this->categoryName($categoryId),
            (int) $data['total_copies'],
        );

        return response()->json(BookResource::present($book), 201);
    }

    public function update(string $id, UpdateBookRequest $request, UpdateBookService $update): JsonResponse
    {
        $data = $request->validated();
        $categoryId = (string) $data['category_id'];
        $book = $update->update(
            $id,
            (string) $data['title'],
            (string) $data['author'],
            $categoryId,
            $this->categoryName($categoryId),
            (int) $data['total_copies'],
        );

        return response()->json(BookResource::present($book));
    }

    public function destroy(string $id, DeleteBookService $delete): Response
    {
        $delete->delete($id);

        return response()->noContent();
    }

    public function updateCover(
        string $id,
        UploadCoverRequest $request,
        CoverService $covers,
        BookRepository $books,
    ): JsonResponse {
        $file = $request->file('file');
        $bytes = (string) file_get_contents((string) $file->getRealPath());
        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes) ?: '';
        if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
            throw new ValidationException('La portada debe ser JPG o PNG.');
        }
        $key = Str::uuid()->toString().($mime === 'image/png' ? '.png' : '.jpg');
        $covers->attach($id, $key, $bytes, $mime);

        $book = $books->findById($id);
        if ($book === null) {
            throw new NotFoundException('El libro no existe.');
        }

        return response()->json(BookResource::present($book));
    }

    public function destroyCover(string $id, CoverService $covers): Response
    {
        $covers->detach($id);

        return response()->noContent();
    }

    public function showCover(string $id, BookRepository $books, DiskCoverStorage $storage): Response
    {
        $book = $books->findById($id);
        if ($book === null || !$book->isActive() || $book->coverKey() === null) {
            return LibraryExceptionHandler::notFound('La portada no existe.');
        }
        $content = $storage->content($book->coverKey());
        if ($content === null) {
            return LibraryExceptionHandler::notFound('La portada no existe.');
        }

        return response($content['bytes'], 200, [
            'Content-Type' => $content['mime'],
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    private function categoryName(string $categoryId): string
    {
        $category = CategoryModel::find($categoryId);
        if ($category === null) {
            throw new NotFoundException('La categoría no existe.');
        }

        return (string) $category->name;
    }
}
