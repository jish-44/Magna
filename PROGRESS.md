# Magna CMS — Build Progress

Tracks progress against [docs/build-plan.md](docs/build-plan.md). Every session starts by reading this file; every stage ends by updating it.

> **Project location:** `C:\Users\jishn\Herd\magna-cms` — served by Herd at `magna-cms.test`. (Originally scaffolded as `magna`, renamed 2026-07-02; the folder name is hyphenated because Herd derives the dev domain from it. The product name remains "Magna CMS".) All specs are in-repo under `docs/`.

## Stage checklist

- [x] **Stage 0 — Project scaffold & engineering rig**
- [x] **Stage 1 — Kernel: users, roles, permissions (RBAC)**
- [x] **Stage 2 — Kernel: authentication & API tokens**
- [ ] Stage 3 — Kernel: settings system & audit log
- [ ] Stage 4 — Plugin system
- [ ] Stage 5 — Content Engine I: schemas & generated tables
- [ ] Stage 6 — Content Engine II: entries, drafts, revisions, publishing
- [ ] Stage 7 — Media
- [ ] Stage 8 — Delivery REST API
- [ ] Stage 9 — Management API & webhooks
- [ ] Stage 10 — Admin panel (Filament)
- [ ] Stage 11 — Blocks & the structured block editor
- [ ] Stage 12 — Caching & performance contract
- [ ] Stage 13 — Security hardening pass (gates Phase 1 exit)
- [ ] Stage 14 — First-party plugins: blog, SEO, forms (+ afternoon test)
- [ ] Stage 15 — Localization & scheduled publishing polish
- [ ] Stage 16 — Store Stage 1 (official catalog)
- [ ] Stage 17 — Magna Pages
- [ ] Stage 18 — Default theme "Launch"

## Stage 0 notes (2026-07-02)

- Scaffolded **Laravel 13.8** (framework 13.18) — not 12 as the build plan said; rationale in [ADR-0001](docs/adr/ADR-0001-stack-decisions.md). PHP 8.4.20 locally via Herd.
- Rig: Pest 4.7 (+ laravel plugin), Larastan 3.10 / PHPStan 2.2 at **level 9** on `app/`, `src/`, `database/`; Pint. `composer check` runs pint → phpstan → tests.
- `src/Magna` namespace autoloaded; `MagnaServiceProvider` registered in `bootstrap/providers.php` (empty shell — kernel providers attach here in later stages).
- SQLite for dev/tests (`database/database.sqlite`), Postgres-first policy per ADR. CI: PHP 8.3/8.4 matrix, suite runs on SQLite **and** Postgres 16 service.
- Spec docs copied into `docs/`; project README in place.

## Stage 1 notes (2026-07-02)

- RBAC kernel in `src/Magna/Auth` + `src/Magna/Users`. Key pieces: `PermissionRegistry` (in-code string keys, validated format, no wildcards in registered keys), `PermissionMatcher` (trailing `*` = any remainder, mid `*` = exactly one segment), `Role`/`RolePermission` models, `HasRoles` trait (memoized grants), `Magna\Users\User` (ULID, argon2id via config/hashing.php, `UserStatus` enum).
- Gate integration convention: **abilities containing a dot are permission keys** and resolve exclusively through the registry (unregistered → deny + `Log::warning`); dot-free abilities fall through to policies/closures; super-admin roles bypass everything via `Gate::before`.
- Scaffold `app/Models/User.php` deleted; `config/auth.php` points at `Magna\Users\User`. Users migration rewritten for ULID + status before any release exists (allowed only pre-1.0).
- Core kernel permission keys registered in `AuthServiceProvider` (users/roles/settings/plugins/audit). Seeder: super-admin, admin, editor (`content.*`, `media.*`), viewer (`content.*.view`) — wildcards resolve when content permissions register in Stage 6.
- Tests: 45 passing (83 assertions); argon costs lowered in phpunit.xml for speed. `magna:permissions:list` verified live.

## Off-plan: Web installer (2026-07-02)

- WordPress-style browser installer at `/install`, built in `src/Magna/Install` (requested outside the stage plan; kernel-only dependencies so it slots after Stage 1).
- Flow: requirements check (required + recommended, incl. Argon2id/HTTPS/Redis) → site name/URL/production toggle → database (PostgreSQL/MySQL/MariaDB/SQLite, connection probed with friendly errors before anything is written, then migrate + seed) → super-admin account → lock.
- **Stateless steps**: each step writes straight to `.env` (`EnvWriter` — updates in place, preserves comments, quotes safely). No session state to corrupt.
- **Bare-server bootstrap**: when uninstalled, sessions are forced to the `file` driver and a missing `APP_KEY` is self-generated and persisted (`InstallServiceProvider::prepareUninstalledRuntime`).
- **Security**: `EnsureNotInstalled` 404s all installer routes once the lock (`storage/app/magna-installed.json`) exists; `RedirectIfNotInstalled` sends all web traffic to the installer until then. Argon2id fallback to bcrypt is written to `.env` if the platform lacks it.
- Zero build/CDN dependencies: pure Blade + embedded CSS (dark UI), tiny vanilla JS for the driver toggle.
- Gotchas learned: middleware pushed to the `web` group from a provider is wiped by the bootstrap middleware sync — append in `bootstrap/app.php` instead. Installer flow tests are exempt from `RefreshDatabase` (they migrate their own connection); **new Feature test directories must be added to the RefreshDatabase line in tests/Pest.php**.
- Tests: `MAGNA_INSTALLED=true` in phpunit.xml makes the suite run "as installed"; installer tests override `magna.installed_override`/lock/env paths to temp dirs.
- The local dev site is intentionally left uninstalled so the installer can be tried at `http://magna-cms.test`.
- Full spec + **known limitations / future-edit checklist** in [docs/installer.md](docs/installer.md) — revisit at Stage 13 (security pass). Post-review hardening applied: env newline-injection stripped, URL restricted to http/https, installer routes throttled, cached config auto-cleared after .env writes.

## Stage 2 notes (2026-07-02)

- Sanctum installed; `MagnaToken` extends `PersonalAccessToken` — **must** declare `protected $table = 'personal_access_tokens'` explicitly (Eloquent would otherwise derive `magna_tokens` from the class name).
- Custom `personal_access_tokens` migration adds `scope` (`delivery`/`management`) and `rate_limit_per_minute` columns.
- `MagnaApiMiddleware` — stateless bearer-token auth: resolves token, checks expiry, enforces scope, applies per-token rate limiting (`RateLimiter`, 60s window), sets `auth()->setUser()` only when `tokenable instanceof Authenticatable`.
- `TwoFactorService` — `pragmarx/google2fa` + `bacon/bacon-qr-code` v3; `getQrCodeSvg()` returns inline SVG. Recovery codes: `bin2hex(random_bytes(5))-bin2hex(random_bytes(5))` format, count from `Config::integer('magna.two_factor.recovery_codes', 8)`.
- `LoginThrottle` — exponential backoff via `Cache` directly (not `RateLimiter`) to support variable decay per hit: `base * 2^(excess_attempts - 1)`, capped at max. Key fingerprint: `sha256(email|ip)`.
- Session auth routes at `auth/*`; API token routes at `api/v1/tokens`. Views registered as `magna::` namespace (flat — no `auth/` subdirectory); controller references use `magna::login` etc.
- Blade views only (no JS framework) for login, forgot-password, reset-password, verify-email, two-factor-challenge.
- `TwoFactorSetupController::disable()` uses `Hash::check()` directly instead of `current_password` validation rule — the rule generates a redirect (not JSON 422) when called on a DELETE request in the Pest test context.
- PHPStan level 9 clean: all `config()` calls replaced with `Config::string()` / `Config::integer()`; `Cache::get()` results guarded with `is_int()` before arithmetic; `Password` broker status constants passed directly (not `__()`).
- Tests: 100 passing (263 assertions) — 6 new test files covering Login, PasswordReset, EmailVerification, TwoFactor, ApiToken, SecurityHeaders.
- Registration is disabled by default (`magna.registration_enabled = false`); wire to the Settings system in Stage 3.
- CORS: default Laravel config left in place; production hardening deferred to Stage 13 (security pass).

## Notes for next session (Stage 3)

- Follow the Stage 3 prompt in docs/build-plan.md: settings system & audit log.
- Registration flag currently lives in `config/magna.php`; Stage 3 should migrate it to the Settings system.
- Stage 3 may need to move/expose `AppSetting` model, settings seeder, and a settings service accessible from all modules.
