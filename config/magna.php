<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Installation
    |--------------------------------------------------------------------------
    |
    | The web installer runs until the lock file exists, then its routes
    | return 404 forever. `installed_override` short-circuits the check —
    | used by the test suite (MAGNA_INSTALLED=true) so application tests
    | run as an installed site.
    |
    */

    'installed_override' => env('MAGNA_INSTALLED'),

    'install' => [
        'lock_path' => storage_path('app/magna-installed.json'),
        'env_path' => base_path('.env'),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Registration
    |--------------------------------------------------------------------------
    | Disabled by default per security-spec §1. Enable only when self-service
    | sign-up is intentional. Migrates to the typed Settings system at Stage 3.
    */
    'registration_enabled' => env('MAGNA_REGISTRATION_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | API Token Expiry (days)
    |--------------------------------------------------------------------------
    */
    'token_expiry' => [
        'delivery' => (int) env('MAGNA_TOKEN_EXPIRY_DELIVERY', 365),
        'management' => (int) env('MAGNA_TOKEN_EXPIRY_MANAGEMENT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Per-Token Default Rate Limits (requests per minute)
    |--------------------------------------------------------------------------
    */
    'token_rate_limit' => [
        'delivery' => (int) env('MAGNA_TOKEN_RATE_LIMIT_DELIVERY', 1000),
        'management' => (int) env('MAGNA_TOKEN_RATE_LIMIT_MANAGEMENT', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication
    |--------------------------------------------------------------------------
    */
    'two_factor' => [
        'issuer' => env('APP_NAME', 'Magna CMS'),
        'recovery_codes' => (int) env('MAGNA_2FA_RECOVERY_CODES', 8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Login Brute-Force Protection
    |--------------------------------------------------------------------------
    | max_attempts: consecutive failures before lockout engages.
    | base_lockout_seconds: first lockout duration; doubles per window
    |   (exponential backoff), capped at max_lockout_seconds.
    */
    'login' => [
        'max_attempts' => (int) env('MAGNA_LOGIN_MAX_ATTEMPTS', 5),
        'base_lockout_seconds' => (int) env('MAGNA_LOGIN_BASE_LOCKOUT_SECONDS', 30),
        'max_lockout_seconds' => (int) env('MAGNA_LOGIN_MAX_LOCKOUT_SECONDS', 900),
    ],

];
