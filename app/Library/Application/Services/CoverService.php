<?php

declare(strict_types=1);

namespace App\Library\Application\Services;

use App\Library\Application\Exceptions\NotFoundException;
use App\Library\Application\Exceptions\ValidationException;
use App\Library\Application\Ports\BookRepository;
use App\Library\Application\Ports\CoverStorage;

/**
 * HU-03 covers: JPG/PNG by content, max 2 MB, opaque key;
 * without cover the field is null, never ''.
 */
final class CoverService
{
    public const MAX_BYTES = 2 * 1024 * 1024;
    private const ALLOWED_MIME = ['image/jpeg', 'image/png'];

    public function __construct(
        private BookRepository $books,
        private CoverStorage $covers,
    ) {}

    public function attach(string $bookId, string $key, string $bytes, string $mimeType): void
    {
        $book = $this->activeBook($bookId);
        if (!in_array($mimeType, self::ALLOWED_MIME, true)) {
            throw new ValidationException('La portada debe ser JPG o PNG.');
        }
        if (strlen($bytes) > self::MAX_BYTES) {
            throw new ValidationException('La portada no puede superar los 2 MB.');
        }
        if (trim($key) === '') {
            throw new ValidationException('La clave de portada es obligatoria.');
        }
        $this->covers->store($key, $bytes, $mimeType);
        $book->attachCover($key);
        $this->books->save($book);
    }

    public function detach(string $bookId): void
    {
        $book = $this->activeBook($bookId);
        $key = $book->coverKey();
        $book->removeCover();
        $this->books->save($book);
        // Retention order (data-model §7): clear the key and commit first,
        // then delete the binary.
        if ($key !== null) {
            $this->covers->delete($key);
        }
    }

    private function activeBook(string $bookId): \App\Library\Domain\Entities\Book
    {
        $book = $this->books->findById($bookId);
        if ($book === null || !$book->isActive()) {
            throw new NotFoundException('El libro no existe.');
        }
        return $book;
    }
}
