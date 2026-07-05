<?php

declare(strict_types=1);

namespace Magna\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Magna\Settings\SecuritySettings;
use Symfony\Component\HttpFoundation\Response;

class ForceHttpsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (SecuritySettings::get()->force_https && ! $request->secure()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
