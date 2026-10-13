<?php

declare(strict_types=1);

namespace App\Library\Bootstrap\Composition;

use App\Library\Adapters\Outbound\Persistence\EloquentBookRepository;
use App\Library\Adapters\Outbound\Persistence\EloquentLoanRepository;
use App\Library\Adapters\Outbound\Persistence\EloquentMemberRepository;
use App\Library\Adapters\Outbound\Persistence\EloquentReportReader;
use App\Library\Adapters\Outbound\Persistence\EloquentUserRepository;
use App\Library\Adapters\Outbound\Security\JwtIssuer;
use App\Library\Adapters\Outbound\Security\LaravelHashHasher;
use App\Library\Adapters\Outbound\Security\SystemClock;
use App\Library\Adapters\Outbound\Storage\DiskCoverStorage;
use App\Library\Application\Ports\BookRepository;
use App\Library\Application\Ports\Clock;
use App\Library\Application\Ports\CoverStorage;
use App\Library\Application\Ports\LoanRepository;
use App\Library\Application\Ports\MemberRepository;
use App\Library\Application\Ports\PasswordHasher;
use App\Library\Application\Ports\ReportReader;
use App\Library\Application\Ports\TokenIssuer;
use App\Library\Application\Ports\UserRepository;
use Illuminate\Contracts\Foundation\Application;

/**
 * Single composition point (Article III): the only place where a port
 * meets its adapter. Secrets come from configuration backed by the
 * environment (Article IX).
 */
final class PortBindings
{
    public static function register(Application $app): void
    {
        $app->singleton(BookRepository::class, EloquentBookRepository::class);
        $app->singleton(LoanRepository::class, EloquentLoanRepository::class);
        $app->singleton(MemberRepository::class, EloquentMemberRepository::class);
        $app->singleton(UserRepository::class, EloquentUserRepository::class);
        $app->singleton(ReportReader::class, EloquentReportReader::class);

        $app->singleton(Clock::class, SystemClock::class);
        $app->singleton(PasswordHasher::class, LaravelHashHasher::class);
        $app->singleton(TokenIssuer::class, static fn () => new JwtIssuer(
            (string) config('library.jwt_secret'),
            $app->make(Clock::class),
        ));
        $app->singleton(JwtIssuer::class, static fn () => new JwtIssuer(
            (string) config('library.jwt_secret'),
            $app->make(Clock::class),
        ));
        $app->singleton(CoverStorage::class, static fn () => new DiskCoverStorage(
            (string) config('library.covers_dir'),
        ));
        $app->singleton(DiskCoverStorage::class, static fn () => new DiskCoverStorage(
            (string) config('library.covers_dir'),
        ));
    }
}
