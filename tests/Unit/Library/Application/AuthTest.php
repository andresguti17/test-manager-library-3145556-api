<?php

declare(strict_types=1);

namespace Tests\Unit\Library\Application;

use App\Library\Application\Exceptions\ApplicationException;
use App\Library\Application\Services\CreateLibrarianService;
use App\Library\Application\Services\LoginService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FakeClock;
use Tests\Fakes\FakePasswordHasher;
use Tests\Fakes\FakeTokenIssuer;
use Tests\Fakes\Fixtures;
use Tests\Fakes\InMemoryUserRepository;

/** T-07: HU-01 login + HU-02 librarian registration against fakes. */
final class AuthTest extends TestCase
{
    private InMemoryUserRepository $users;
    private FakePasswordHasher $hasher;
    private FakeTokenIssuer $tokens;
    private FakeClock $clock;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->hasher = new FakePasswordHasher();
        $this->tokens = new FakeTokenIssuer();
        $this->clock = new FakeClock(new DateTimeImmutable('2026-10-01T10:00:00Z'));
    }

    public function test_login_with_valid_credentials_issues_8h_token(): void
    {
        $this->users->save(Fixtures::user('u1', 'ana'));
        $this->users->findById('u1');
        // Store a properly hashed password through the hasher.
        $service = new CreateLibrarianService($this->users, $this->hasher);
        $service->create('u2', 'Beto', 's3cret', 'librarian');
        $login = new LoginService($this->users, $this->hasher, $this->tokens, $this->clock);
        $result = $login->login(' Beto ', 's3cret');
        $this->assertSame('Bearer', $result->tokenType);
        $this->assertSame(28800, $result->expiresIn);
        $this->assertSame('librarian', $result->role);
        $this->assertStringStartsWith('fake-token:u2', $result->accessToken);
        $this->assertSame('2026-10-01T18:00:00+00:00', $this->tokens->lastExpiresAt?->format('c'));
    }

    public function test_login_hides_whether_username_or_password_failed(): void
    {
        $login = new LoginService($this->users, $this->hasher, $this->tokens, $this->clock);
        foreach ([
            'unknown user' => fn () => $login->login('nadie', 'x'),
            'wrong password' => function () use ($login) {
                (new CreateLibrarianService($this->users, $this->hasher))->create('u1', 'ana', 'ok', 'librarian');
                return $login->login('ana', 'wrong');
            },
        ] as $case => $call) {
            try {
                $call();
                $this->fail($case.': must throw.');
            } catch (ApplicationException $e) {
                $this->assertSame('INVALID_CREDENTIALS', $e->errorCode(), $case);
            }
        }
    }

    public function test_create_librarian_normalizes_username_and_rejects_duplicates(): void
    {
        $service = new CreateLibrarianService($this->users, $this->hasher);
        $user = $service->create('u1', 'Ana ', 's3cret', 'librarian');
        $this->assertSame('ana', $user->username());
        try {
            $service->create('u2', ' ANA', 'other', 'librarian');
            $this->fail('Duplicate username must throw.');
        } catch (ApplicationException $e) {
            $this->assertSame('USERNAME_TAKEN', $e->errorCode());
        }
    }

    public function test_create_librarian_rejects_admin_role_and_plain_password(): void
    {
        $service = new CreateLibrarianService($this->users, $this->hasher);
        try {
            $service->create('u1', 'root', 's3cret', 'admin');
            $this->fail('Admin role via API must throw.');
        } catch (ApplicationException $e) {
            $this->assertSame('VALIDATION_ERROR', $e->errorCode());
        }
        $user = $service->create('u2', 'ana', 's3cret', 'librarian');
        $this->assertNotSame('s3cret', $user->passwordHash);
        $this->assertTrue($this->hasher->verify('s3cret', $user->passwordHash));
    }
}
