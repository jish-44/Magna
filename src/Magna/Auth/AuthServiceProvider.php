<?php

declare(strict_types=1);

namespace Magna\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use Magna\Auth\Console\PermissionsListCommand;
use Magna\Auth\Http\Middleware\AdminCspMiddleware;
use Magna\Auth\Http\Middleware\EnsureTwoFactorAuthenticated;
use Magna\Auth\Http\Middleware\MagnaApiMiddleware;
use Magna\Auth\Http\Middleware\SecurityHeadersMiddleware;
use Magna\Users\User;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PermissionRegistry::class);
        $this->app->singleton(TwoFactorService::class);
        $this->app->singleton(LoginThrottle::class);
    }

    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(MagnaToken::class);

        $this->registerCorePermissions();
        $this->registerGateResolution();
        $this->registerRoutes();
        $this->registerViews();
        $this->registerMiddlewareAliases();

        if ($this->app->runningInConsole()) {
            $this->commands([PermissionsListCommand::class]);
        }
    }

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
            'tokens.manage' => 'Create, list, and revoke API tokens',
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

    private function registerRoutes(): void
    {
        Route::middleware('web')
            ->prefix('auth')
            ->group(__DIR__.'/routes/web.php');

        Route::middleware('api')
            ->prefix('api/v1')
            ->group(__DIR__.'/routes/api.php');
    }

    private function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'magna');
    }

    private function registerMiddlewareAliases(): void
    {
        $router = $this->app->make(Router::class);

        $router->aliasMiddleware('magna.api', MagnaApiMiddleware::class);
        $router->aliasMiddleware('magna.security-headers', SecurityHeadersMiddleware::class);
        $router->aliasMiddleware('magna.admin-csp', AdminCspMiddleware::class);
        $router->aliasMiddleware('magna.two-factor', EnsureTwoFactorAuthenticated::class);
    }
}
