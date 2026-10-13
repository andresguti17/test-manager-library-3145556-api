<?php

declare(strict_types=1);

namespace App\Library\Bootstrap;

use App\Library\Bootstrap\Composition\PortBindings;
use App\Library\Bootstrap\Console\EnsureAdminCommand;
use Illuminate\Support\ServiceProvider;

final class LibraryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        PortBindings::register($this->app);
    }

    public function boot(): void
    {
        $this->commands([EnsureAdminCommand::class]);
    }
}
