<?php

declare(strict_types=1);

namespace Magna\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Magna\Auth\TwoFactorService;
use Magna\Users\User;

class TwoFactorSetupController extends Controller
{
    public function __construct(private TwoFactorService $twoFactor) {}

    /** Begin enrollment: generate and store a new secret (unconfirmed). */
    public function enrol(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $secret = $this->twoFactor->generateSecret();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => null,
        ])->save();

        // Rotate session on privilege change (new 2FA secret = privilege change).
        $request->session()->regenerate();

        return response()->json([
            'secret' => $secret,
            'qr_code' => $this->twoFactor->getQrCodeSvg($user->email, $secret),
        ]);
    }

    /** Confirm enrollment by verifying a TOTP code against the pending secret. */
    public function confirm(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        /** @var User $user */
        $user = Auth::user();

        if ($user->two_factor_secret === null) {
            return response()->json(['message' => 'No pending 2FA enrollment.'], 422);
        }

        if (! $this->twoFactor->verify($user->two_factor_secret, $request->string('code')->toString())) {
            return response()->json(['message' => 'Invalid code.'], 422);
        }

        $codes = $this->twoFactor->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => json_encode($codes),
        ])->save();

        return response()->json(['recovery_codes' => $codes]);
    }

    /** Disable 2FA for the authenticated user (requires password confirmation). */
    public function disable(Request $request): JsonResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        /** @var User $user */
        $user = Auth::user();

        if (! Hash::check($request->string('password')->toString(), (string) $user->getAuthPassword())) {
            return response()->json(['message' => 'Invalid password.'], 422);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $request->session()->regenerate();

        return response()->json(['message' => 'Two-factor authentication disabled.']);
    }

    /** Regenerate recovery codes (requires confirmed 2FA). */
    public function regenerateRecoveryCodes(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->two_factor_confirmed_at === null) {
            return response()->json(['message' => '2FA is not confirmed.'], 422);
        }

        $codes = $this->twoFactor->generateRecoveryCodes();

        $user->forceFill(['two_factor_recovery_codes' => json_encode($codes)])->save();

        return response()->json(['recovery_codes' => $codes]);
    }

    /** Show current recovery codes (requires confirmed 2FA). */
    public function recoveryCodes(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->two_factor_confirmed_at === null) {
            return response()->json(['message' => '2FA is not confirmed.'], 422);
        }

        /** @var list<string> $codes */
        $codes = json_decode((string) $user->two_factor_recovery_codes, true) ?? [];

        return response()->json(['recovery_codes' => $codes]);
    }
}
