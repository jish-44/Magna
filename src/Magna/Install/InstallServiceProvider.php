<?php

declare(strict_types=1);

namespace Magna\Install;

use Illuminate\Support\ServiceProvider;
use Throwable;

class InstallServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EnvWriter::class, function (): EnvWriter {
            return new EnvWriter(config()->string('magna.install.env_path', base_path('.env')));
        });

        // Must run in register(), before any other provider's boot(): the
        // exception handler and various providers read the cache during boot.
        // On a fresh unzip (no .env) the default `database` cache store needs a
        // database that does not exist yet. Force file sessions and an array
        // cache so nothing pre-install depends on a database driver at all.
        // (Providers that read tables are separately guarded by
        // Installer::isInstalled() so they never touch the DB before install.)
        if (! Installer::isInstalled() && ! $this->app->runningInConsole()) {
            config([
                'session.driver' => 'file',
                'cache.default' => 'array',
            ]);
        }
    }

    /**
     * Note: RedirectIfNotInstalled is appended to the web group in
     * bootstrap/app.php — pushing it here would be wiped when the kernel
     * syncs the bootstrap middleware configuration.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'magna-install');
        $this->loadRoutesFrom(__DIR__.'/routes.php');

        if (! Installer::isInstalled() && ! $this->app->runningInConsole()) {
            $this->prepareUninstalledRuntime();
        }
    }

    /**
     * On a fresh unzip there is often no APP_KEY. Self-generate one (and try to
     * persist it to .env) so sessions/CSRF work through the installer. The
     * pre-install cache/database/session overrides are applied earlier, in
     * register(), because they must land before any other provider boots.
     */
    private function prepareUninstalledRuntime(): void
    {
        if (config('app.key') !== null && config('app.key') !== '') {
            return;
        }

        $key = 'base64:'.base64_encode(random_bytes(32));

        try {
            $this->app->make(EnvWriter::class)->set(['APP_KEY' => $key]);
        } catch (Throwable) {
            // .env not writable — the requirements screen will surface this.
        }

        config(['app.key' => $key]);
    }
}
