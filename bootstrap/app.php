<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Magna\Auth\Http\Middleware\SecurityHeadersMiddleware;
use Magna\Install\Http\Middleware\RedirectIfNotInstalled;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Prepend globally so the "not installed" gate runs before anything
        // else — in particular before the Filament panel's Authenticate
        // middleware, which (mounted at "/") would otherwise redirect guests
        // to /login before the installer redirect can fire. It only checks a
        // lock file, so it needs no session.
        $middleware->prepend(RedirectIfNotInstalled::class);

        $middleware->web(append: [
            SecurityHeadersMiddleware::class,
        ]);

        $middleware->api(append: [
            SecurityHeadersMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
