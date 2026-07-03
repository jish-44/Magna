<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Magna\Audit\AuditLog;
use Magna\Auth\Role;
use Magna\Settings\GeneralSettings;
use Magna\Users\User;

beforeEach(function (): void {
    Cache::tags(['magna-settings'])->flush();
});

// ── Immutability ──────────────────────────────────────────────────────────────

it('prevents updating an audit log entry', function (): void {
    $log = AuditLog::record(action: 'test.event', actorType: 'system');

    expect(fn () => $log->save())->toThrow(LogicException::class);
});

it('prevents deleting an audit log entry', function (): void {
    $log = AuditLog::record(action: 'test.event', actorType: 'system');

    expect(fn () => $log->delete())->toThrow(LogicException::class);
});

// ── Auto-audited events ───────────────────────────────────────────────────────

it('records an audit entry on successful login', function (): void {
    $user = User::factory()->create(['password' => Hash::make('secret')]);

    $this->post(route('auth.login.attempt'), [
        'email' => $user->email,
        'password' => 'secret',
    ]);

    expect(AuditLog::query()->where('action', 'auth.login.success')->count())->toBe(1);
});

it('records an audit entry on failed login', function (): void {
    $user = User::factory()->create();

    $this->post(route('auth.login.attempt'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    expect(AuditLog::query()->where('action', 'auth.login.failure')->count())->toBe(1);
});

it('records an audit entry when a role is assigned', function (): void {
    $user = User::factory()->create();
    $role = Role::factory()->create(['handle' => 'editor']);

    $user->assignRole($role);

    $log = AuditLog::query()->where('action', 'roles.assigned')->first();
    expect($log)->not->toBeNull();
    expect($log->after)->toMatchArray(['role' => 'editor']); // @phpstan-ignore-line
});

it('records an audit entry when settings are changed', function (): void {
    $settings = GeneralSettings::get();
    $settings->site_name = 'Audited Site';
    $settings->save();

    $log = AuditLog::query()->where('action', 'settings.changed')->first();
    expect($log)->not->toBeNull();
    expect($log->after)->toMatchArray(['site_name' => 'Audited Site']); // @phpstan-ignore-line
});

it('records an audit entry when an API token is created', function (): void {
    $user = User::factory()->create();
    $expiresAt = now()->addDays(30);
    $mgmt = $user->createToken('mgmt', ['management'], $expiresAt);
    $mgmt->accessToken->forceFill(['scope' => 'management'])->save();

    $this->withToken($mgmt->plainTextToken)
        ->postJson(route('api.tokens.store'), [
            'name' => 'Delivery key',
            'scope' => 'delivery',
        ])
        ->assertCreated();

    expect(AuditLog::query()->where('action', 'tokens.created')->count())->toBe(1);
});

it('records an audit entry when an API token is revoked', function (): void {
    $user = User::factory()->create();
    $expiresAt = now()->addDays(30);
    $mgmt = $user->createToken('mgmt', ['management'], $expiresAt);
    $mgmt->accessToken->forceFill(['scope' => 'management'])->save();
    $tokenId = $mgmt->accessToken->id;

    $this->withToken($mgmt->plainTextToken)
        ->deleteJson(route('api.tokens.destroy', $tokenId))
        ->assertOk();

    expect(AuditLog::query()->where('action', 'tokens.revoked')->count())->toBe(1);
});
