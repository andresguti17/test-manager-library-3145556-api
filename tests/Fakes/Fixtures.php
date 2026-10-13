<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Library\Domain\Entities\Book;
use App\Library\Domain\Entities\Member;
use App\Library\Domain\Entities\User;
use App\Library\Domain\Enums\UserRole;
use App\Library\Domain\ValueObjects\DocumentNumber;
use App\Library\Domain\ValueObjects\Isbn;

final class Fixtures
{
    public const CATEGORY_NOVELA = '11111111-1111-4111-8111-111111111111';

    public static function book(
        string $id,
        string $title = 'Cien años de soledad',
        string $isbn = '9780307474728',
        int $total = 2,
        ?int $available = null,
        string $categoryId = self::CATEGORY_NOVELA,
        string $categoryName = 'Novela',
        string $author = 'Gabriel García Márquez',
    ): Book {
        return new Book($id, $title, $author, new Isbn($isbn), $categoryId, $categoryName, $total, $available ?? $total);
    }

    public static function member(
        string $id,
        string $name = 'Juan Pérez',
        string $document = '12345678',
    ): Member {
        return new Member($id, $name, new DocumentNumber($document));
    }

    public static function user(
        string $id,
        string $username = 'ana',
        UserRole $role = UserRole::Librarian,
    ): User {
        return new User($id, $username, 'fake-hash:secret', $role);
    }
}
