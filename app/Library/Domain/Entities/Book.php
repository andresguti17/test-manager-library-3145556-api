<?php

declare(strict_types=1);

namespace App\Library\Domain\Entities;

use App\Library\Domain\Exceptions\BookNotAvailableException;
use App\Library\Domain\Exceptions\InvalidBookException;
use App\Library\Domain\Exceptions\InvalidQuantityException;
use App\Library\Domain\ValueObjects\Isbn;
use DateTimeImmutable;

/**
 * Book aggregate root.
 *
 * Defends RN-1 (0 <= available <= total), total >= 1, non-empty trimmed
 * title, and logical deletion (deletedAt, CA-03.5).
 */
final class Book
{
    private string $title;
    private string $author;
    private string $categoryId;
    private string $categoryName;
    private int $totalCopies;
    private int $availableCopies;
    private ?string $coverKey;
    private ?DateTimeImmutable $deletedAt;

    public function __construct(
        public readonly string $id,
        string $title,
        string $author,
        public readonly Isbn $isbn,
        string $categoryId,
        string $categoryName,
        int $totalCopies,
        int $availableCopies,
        ?string $coverKey = null,
        ?DateTimeImmutable $deletedAt = null,
    ) {
        $this->setTitle($title);
        $this->setAuthor($author);
        $this->setCategory($categoryId, $categoryName);
        if ($totalCopies < 1) {
            throw new InvalidQuantityException('El total de ejemplares debe ser mayor o igual a 1.');
        }
        if ($availableCopies < 0 || $availableCopies > $totalCopies) {
            throw new InvalidBookException('Los ejemplares disponibles deben estar entre 0 y el total.');
        }
        $this->totalCopies = $totalCopies;
        $this->availableCopies = $availableCopies;
        $this->setCoverKey($coverKey);
        $this->deletedAt = $deletedAt;
    }

    /** New books start with available == total (CA-03.1). */
    public static function create(
        string $id,
        string $title,
        string $author,
        Isbn $isbn,
        string $categoryId,
        string $categoryName,
        int $totalCopies,
        ?string $coverKey = null,
    ): self {
        return new self($id, $title, $author, $isbn, $categoryId, $categoryName, $totalCopies, $totalCopies, $coverKey);
    }

    public function title(): string
    {
        return $this->title;
    }

    public function author(): string
    {
        return $this->author;
    }

    public function categoryId(): string
    {
        return $this->categoryId;
    }

    public function categoryName(): string
    {
        return $this->categoryName;
    }

    public function totalCopies(): int
    {
        return $this->totalCopies;
    }

    public function availableCopies(): int
    {
        return $this->availableCopies;
    }

    /** Opaque cover key; null when there is no cover, never ''. */
    public function coverKey(): ?string
    {
        return $this->coverKey;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isActive(): bool
    {
        return $this->deletedAt === null;
    }

    /** Lending one copy: fails when none is available (CA-06.3). */
    public function withdraw(): void
    {
        if ($this->availableCopies < 1) {
            throw new BookNotAvailableException(
                sprintf('No quedan ejemplares disponibles de «%s».', $this->title),
            );
        }
        $this->availableCopies--;
    }

    /** Returning one copy: never exceeds the total (CA-07.3, RN-1). */
    public function restock(): void
    {
        if ($this->availableCopies >= $this->totalCopies) {
            throw new InvalidBookException('Los ejemplares disponibles nunca superan el total.');
        }
        $this->availableCopies++;
    }

    /**
     * Changing the total shifts available by the same difference and never
     * leaves available < 0 (CA-03.3).
     */
    public function changeTotal(int $newTotal): void
    {
        if ($newTotal < 1) {
            throw new InvalidQuantityException('El total de ejemplares debe ser mayor o igual a 1.');
        }
        $newAvailable = $this->availableCopies + ($newTotal - $this->totalCopies);
        if ($newAvailable < 0) {
            throw new InvalidBookException('El cambio dejaría ejemplares disponibles por debajo de 0.');
        }
        $this->totalCopies = $newTotal;
        $this->availableCopies = $newAvailable;
    }

    public function updateDetails(string $title, string $author, string $categoryId, string $categoryName): void
    {
        $this->setTitle($title);
        $this->setAuthor($author);
        $this->setCategory($categoryId, $categoryName);
    }

    public function attachCover(?string $coverKey): void
    {
        $this->setCoverKey($coverKey);
    }

    public function removeCover(): void
    {
        $this->coverKey = null;
    }

    /** Logical deletion (CA-03.5): the book stays in past loans. */
    public function softDelete(DateTimeImmutable $now): void
    {
        $this->deletedAt = $now;
    }

    private function setTitle(string $title): void
    {
        $trimmed = trim($title);
        if ($trimmed === '') {
            throw new InvalidBookException('El título es obligatorio.');
        }
        $this->title = $trimmed;
    }

    private function setAuthor(string $author): void
    {
        $trimmed = trim($author);
        if ($trimmed === '') {
            throw new InvalidBookException('El autor es obligatorio.');
        }
        $this->author = $trimmed;
    }

    private function setCategory(string $categoryId, string $categoryName): void
    {
        if (trim($categoryId) === '' || trim($categoryName) === '') {
            throw new InvalidBookException('La categoría es obligatoria.');
        }
        $this->categoryId = $categoryId;
        $this->categoryName = $categoryName;
    }

    private function setCoverKey(?string $coverKey): void
    {
        if ($coverKey !== null && trim($coverKey) === '') {
            throw new InvalidBookException('La clave de portada nunca es una cadena vacía.');
        }
        $this->coverKey = $coverKey;
    }
}
