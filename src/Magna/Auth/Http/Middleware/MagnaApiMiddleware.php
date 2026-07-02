<?php

declare(strict_types=1);

namespace Magna\Auth\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Magna\Auth\MagnaToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves a Magna API bearer token, enforces its scope, checks expiry,
 * and applies per-token rate limiting.
 *
 * Usage as an alias: `magna.api` (registered in AuthServiceProvider).
 * Scope enforcement: `magna.api:management` rejects delivery tokens.
 */
class MagnaApiMiddleware
{
    /**
     * @param  Closure(Request): Response  $next
     * @param  string  $requiredScope  'delivery' or 'management'
     */
    public function handle(Request $request, Closure $next, string $requiredScope = 'delivery'): Response
    {
        /** @var MagnaToken|null $token */
        $token = $request->bearerToken()
            ? Sanctum::$personalAccessTokenModel::findToken($request->bearerToken())
            : null;

        if ($token === null) {
            return $this->unauthorized('Missing or invalid API token.');
        }

        if ($token->isExpired()) {
            return $this->unauthorized('API token has expired.');
        }

        if ($requiredScope === 'management' && $token->isDelivery()) {
            return $this->forbidden('A management-scope token is required for this endpoint.');
        }

        $rateLimitKey = 'api_token:'.$token->id;
        $limit = $token->effectiveRateLimit();

        if (RateLimiter::tooManyAttempts($rateLimitKey, $limit)) {
            $retryAfter = RateLimiter::availableIn($rateLimitKey);

            return response()->json(
                ['message' => 'Too many requests.'],
                Response::HTTP_TOO_MANY_REQUESTS,
                ['Retry-After' => $retryAfter],
            );
        }

        RateLimiter::hit($rateLimitKey, 60);

        $tokenable = $token->tokenable;

        if ($tokenable instanceof Authenticatable) {
            $request->setUserResolver(fn () => $tokenable);
            auth()->setUser($tokenable);
        }

        $token->forceFill(['last_used_at' => now()])->save();

        return $next($request);
    }

    private function unauthorized(string $message): JsonResponse
    {
        return response()->json(['message' => $message], Response::HTTP_UNAUTHORIZED);
    }

    private function forbidden(string $message): JsonResponse
    {
        return response()->json(['message' => $message], Response::HTTP_FORBIDDEN);
    }
}
