<?php

declare(strict_types=1);

namespace Tests\Unit\Library\Domain;

use App\Library\Domain\Entities\Member;
use App\Library\Domain\Entities\User;
use App\Library\Domain\Enums\UserRole;
use App\Library\Domain\Exceptions\DomainException;
use App\Library\Domain\ValueObjects\DocumentNumber;
use PHPUnit\Framework\TestCase;

/** T-06: Member document required; User username normalization + role. */
final class MemberUserTest extends TestCase
{
    public function test_username_is_trimmed_and_lowercased(): void
    {
        $user = new User('u1', 'Ana ', 'hash', UserRole::Librarian);
        $this->assertSame('ana', $user->username());
        $this->assertSame(UserRole::Librarian, $user->role);
    }

    public function test_blank_username_is_rejected(): void
    {
        $this->expectException(DomainException::class);
        new User('u1', '   ', 'hash', UserRole::Librarian);
    }

    public function test_admin_role_is_a_valid_domain_role(): void
    {
        // The domain accepts both roles; the API layer refuses to grant
        // admin at runtime (covered in AuthTest).
        $admin = new User('u1', 'root', 'hash', UserRole::Admin);
        $this->assertTrue($admin->isAdmin());
    }

    public function test_member_requires_name_and_document(): void
    {
        $member = new Member('m1', ' Juan ', new DocumentNumber(' 123 '));
        $this->assertSame('Juan', $member->fullName());
        $this->assertSame('123', $member->document->value);
        try {
            new Member('m2', '   ', new DocumentNumber('456'));
            $this->fail('Blank name must throw.');
        } catch (DomainException $e) {
            $this->assertSame('VALIDATION_ERROR', $e->errorCode());
        }
        $this->expectException(DomainException::class);
        new Member('m3', 'Ana', new DocumentNumber(''));
    }
}
