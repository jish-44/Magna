<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Magna\Auth\Role;
use Magna\Auth\TwoFactorService;
use Magna\Users\User;
use PragmaRX\Google2FA\Google2FA;

it('enrols 2FA and returns a secret + QR code SVG', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('auth.two-factor.enrol'));

    $response->assertOk()
        ->assertJsonStructure(['secret', 'qr_code']);

    expect($user->fresh()?->two_factor_secret)->not->toBeNull();
    expect($user->fresh()?->two_factor_confirmed_at)->toBeNull();
});

it('confirms enrollment with a valid TOTP code', function (): void {
    $twoFactor = app(TwoFactorService::class);
    $user = User::factory()->create(['two_factor_secret' => $twoFactor->generateSecret()]);
    $code = app(Google2FA::class)->getCurrentOtp($user->two_factor_secret);

    $response = $this->actingAs($user)
        ->postJson(route('auth.two-factor.confirm'), ['code' => $code]);

    $response->assertOk()
        ->assertJsonStructure(['recovery_codes']);

    expect($user->fresh()?->two_factor_confirmed_at)->not->toBeNull();
    expect($response->json('recovery_codes'))->toHaveCount(8);
});

it('rejects confirmation with a wrong code', function (): void {
    $twoFactor = app(TwoFactorService::class);
    $user = User::factory()->create(['two_factor_secret' => $twoFactor->generateSecret()]);

    $this->actingAs($user)
        ->postJson(route('auth.two-factor.confirm'), ['code' => '000000'])
        ->assertStatus(422);
});

it('disables 2FA with correct password', function (): void {
    $twoFactor = app(TwoFactorService::class);
    $user = User::factory()->create([
        'password' => Hash::make('secret'),
        'two_factor_secret' => $twoFactor->generateSecret(),
        'two_factor_confirmed_at' => now(),
    ]);

    $this->actingAs($user)
        ->deleteJson(route('auth.two-factor.disable'), ['password' => 'secret'])
        ->assertOk();

    expect($user->fresh()?->two_factor_confirmed_at)->toBeNull()
        ->and($user->fresh()?->two_factor_secret)->toBeNull();
});

it('rejects 2FA disable with wrong password', function (): void {
    $twoFactor = app(TwoFactorService::class);
    $user = User::factory()->create([
        'password' => Hash::make('secret'),
        'two_factor_secret' => $twoFactor->generateSecret(),
        'two_factor_confirmed_at' => now(),
    ]);

    $this->actingAs($user)
        ->deleteJson(route('auth.two-factor.disable'), ['password' => 'wrong'])
        ->assertStatus(422);
});

it('completes login via TOTP challenge', function (): void {
    $twoFactor = app(TwoFactorService::class);
    $secret = $twoFactor->generateSecret();
    $role = Role::factory()->create(['requires_two_factor' => true]);
    $user = User::factory()->create([
        'password' => Hash::make('secret'),
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
    ]);
    $user->assignRole($role);

    // Simulate pending challenge state set by LoginController
    $this->withSession(['auth.two_factor_user_id' => $user->getKey()]);

    $code = app(Google2FA::class)->getCurrentOtp($secret);

    $this->post(route('auth.two-factor.challenge.verify'), ['code' => $code])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('rejects an invalid code at the challenge', function (): void {
    $twoFactor = app(TwoFactorService::class);
    $user = User::factory()->create([
        'two_factor_secret' => $twoFactor->generateSecret(),
        'two_factor_confirmed_at' => now(),
    ]);

    $this->withSession(['auth.two_factor_user_id' => $user->getKey()])
        ->post(route('auth.two-factor.challenge.verify'), ['code' => '000000'])
        ->assertRedirect()
        ->assertSessionHasErrors('code');

    $this->assertGuest();
});

it('completes challenge with a recovery code and removes it', function (): void {
    $twoFactor = app(TwoFactorService::class);
    $codes = $twoFactor->generateRecoveryCodes(8);
    $user = User::factory()->create([
        'two_factor_secret' => $twoFactor->generateSecret(),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => json_encode($codes),
    ]);

    $this->withSession(['auth.two_factor_user_id' => $user->getKey()])
        ->post(route('auth.two-factor.challenge.verify'), ['recovery_code' => $codes[0]])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);

    /** @var list<string> $remaining */
    $remaining = json_decode((string) $user->fresh()?->two_factor_recovery_codes, true);
    expect($remaining)->toHaveCount(7)
        ->and($remaining)->not->toContain($codes[0]);
});

it('role-required 2FA blocks login until challenge is passed', function (): void {
    $role = Role::factory()->create(['requires_two_factor' => true]);
    $user = User::factory()->create([
        'password' => Hash::make('secret'),
        'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
        'two_factor_confirmed_at' => now(),
    ]);
    $user->assignRole($role);

    // Login attempt → redirect to challenge (not to dashboard)
    $this->post(route('auth.login.attempt'), [
        'email' => $user->email,
        'password' => 'secret',
    ])->assertRedirect(route('auth.two-factor.challenge'));

    $this->assertGuest();
});
