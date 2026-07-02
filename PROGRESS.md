# Magna CMS — Build Progress

Tracks progress against [docs/build-plan.md](docs/build-plan.md). Every session starts by reading this file; every stage ends by updating it.

> **Project location:** `C:\Users\jishn\Herd\magna-cms` — served by Herd at `magna-cms.test`. (Originally scaffolded as `magna`, renamed 2026-07-02; the folder name is hyphenated because Herd derives the dev domain from it. The product name remains "Magna CMS".) All specs are in-repo under `docs/`.

## Stage checklist

- [x] **Stage 0 — Project scaffold & engineering rig**
- [ ] Stage 1 — Kernel: users, roles, permissions (RBAC)
- [ ] Stage 2 — Kernel: authentication & API tokens
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

## Notes for next session (Stage 1)

- Follow the Stage 1 prompt in docs/build-plan.md: RBAC kernel in `src/Magna/Auth` + `src/Magna/Users`.
- Remember: permissions are in-code registry keys (not DB rows), wildcard grants, Gate integration, super-admin bypass via `Gate::before`.
- The scaffold `app/Models/User.php` will need replacing/extending with the Magna user model (ULIDs — scaffold user uses auto-increment; migrate accordingly).
- Watch: Filament 4 ↔ Laravel 13 compatibility only matters at Stage 10, but if adding packages, prefer ones already L13-compatible.
