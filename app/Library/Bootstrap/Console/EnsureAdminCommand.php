<?php

declare(strict_types=1);

namespace App\Library\Bootstrap\Console;

use App\Library\Application\Ports\PasswordHasher;
use App\Library\Application\Ports\UserRepository;
use App\Library\Domain\Entities\User;
use App\Library\Domain\Enums\UserRole;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Idempotent admin bootstrap (T-17): creates the ADMIN_USERNAME admin with
 * ADMIN_PASSWORD (bcrypt via the port) when no such user exists.
 */
final class EnsureAdminCommand extends Command
{
    protected $signature = 'library:ensure-admin';

    protected $description = 'Creates the initial admin user from ADMIN_USERNAME/ADMIN_PASSWORD when missing.';

    public function handle(UserRepository $users, PasswordHasher $hasher): int
    {
        $username = strtolower(trim((string) config('library.admin_username')));
        $password = (string) config('library.admin_password');
        if ($username === '' || $password === '') {
            $this->warn('ADMIN_USERNAME/ADMIN_PASSWORD no están configurados; se omite la creación del admin.');

            return self::SUCCESS;
        }
        if ($users->findByUsername($username) !== null) {
            $this->info(sprintf('El usuario admin «%s» ya existe.', $username));

            return self::SUCCESS;
        }
        $users->save(new User(Str::uuid()->toString(), $username, $hasher->hash($password), UserRole::Admin));
        $this->info(sprintf('Usuario admin «%s» creado.', $username));

        return self::SUCCESS;
    }
}
