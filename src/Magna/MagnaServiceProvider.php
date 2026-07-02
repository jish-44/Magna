<?php

declare(strict_types=1);

namespace Magna;

use Illuminate\Support\ServiceProvider;
use Magna\Auth\AuthServiceProvider;

/**
 * Root service provider for the Magna kernel.
 *
 * Kernel subsystems (auth, RBAC, plugins, content engine) register their own
 * providers here as they are built, stage by stage — see docs/build-plan.md.
 */
class MagnaServiceProvider extends ServiceProvider
{
    public const VERSION = '0.1.0-dev';

    public function register(): void
    {
        $this->app->register(AuthServiceProvider::class);
    }

    public function boot(): void
    {
        //
    }
}
