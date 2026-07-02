<?php

declare(strict_types=1);

namespace Magna\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Magna\Auth\Console\PermissionsListCommand;
use Magna\Users\User;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PermissionRegistry::class);
    }

    public function boot(): void
    {
        $this->registerCorePermissions();
        $this->registerGateResolution();

        if ($this->app->runningInConsole()) {
            $this->commands([PermissionsListCommand::class]);
        }
    }

    /**
     * Permission keys owned by the kernel itself. Plugins register their own
     * keys through the same registry when they are enabled.
     */
    private function registerCorePermissions(): void
    {
        $registry = $this->app->make(PermissionRegistry::class);

        $registry->registerMany([
            'users.view' => 'View users',
            'users.manage' => 'Create, update, suspend, and delete users',
            'roles.view' => 'View roles and their granted permissions',
            'roles.manage' => 'Create, update, and delete roles; grant and revoke permissions',
            'settings.view' => 'View system settings',
            'settings.manage' => 'Change system settings',
            'plugins.view' => 'View installed plugins',
            'plugins.manage' => 'Enable, disable, and uninstall plugins',
            'audit.view' => 'View the audit log',
        ]);
    }

    /**
     * Route every dotted ability through the RBAC engine.
     *
     * Convention: abilities containing a dot are permission keys and are
     * resolved exclusively here — unregistered keys are denied and logged.
     * Dot-free abilities (model policies, closures) fall through untouched.
     * Super admins bypass all checks, including policies.
     */
    private function registerGateResolution(): void
    {
        Gate::before(function (Authenticatable $user, string $ability): ?bool {
            if (! $user instanceof User) {
                return null;
            }

            if ($user->isSuperAdmin()) {
                return true;
            }

            if (! str_contains($ability, '.')) {
                return null;
            }

            $registry = $this->app->make(PermissionRegistry::class);

            if (! $registry->has($ability)) {
                Log::warning('Denied authorization check for unregistered permission key.', [
                    'key' => $ability,
                    'user_id' => $user->getKey(),
                ]);

                return false;
            }

            return $user->hasPermissionGrant($ability);
        });
    }
}
