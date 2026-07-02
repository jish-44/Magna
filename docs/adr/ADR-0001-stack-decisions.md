# ADR-0001 — Foundational stack decisions

**Status:** Accepted · **Date:** 2026-07-02

## Context

Magna CMS is an API-first headless CMS with a small core (kernel + content engine) and a Composer-based plugin ecosystem. The full rationale lives in [docs/Magna-v2.md](../Magna-v2.md); this ADR records the concrete stack choices the specs imply, plus deviations discovered at scaffold time.

## Decisions

1. **Laravel 13 / PHP 8.3+.** The specs were written against Laravel 12; Laravel 13 is current at scaffold time and we start on it rather than beginning life a major version behind. `composer.json` requires PHP `^8.3`; CI tests 8.3 and 8.4. *(Deviation from docs/build-plan.md Stage 0, which says Laravel 12 — recorded here per the build-plan ground rules.)*
2. **PostgreSQL-first, SQLite for local dev/tests.** All migrations must run on Postgres 16 (JSONB, GIN indexes, transactional DDL are design assumptions of the Content Engine). SQLite is supported as the local/test fallback; CI runs the suite on both.
3. **ULID primary keys** on every Magna model (`HasUlids`). Sortable, index-friendly, non-enumerable in URLs, and multi-tenancy-safe later (report Phase 5 note).
4. **Kernel code lives in `src/Magna` (`Magna\` namespace)**, separate from the app skeleton in `app/`. The kernel is written as if it were a package from day one — that is what eventually lets `magna/core` be extracted without a rewrite.
5. **Plugins are Composer packages** with a `magna.json` manifest (never uploaded archives) — see [plugin-development-guide.md](../plugin-development-guide.md) and [store-plan.md](../store-plan.md).
6. **Filament 4 for the admin panel** (report §Admin Panel): Laravel-native, has the plugin/panel extension model we need. Compatibility with Laravel 13 to be verified at Stage 10; if Filament requires it, this is the one dependency we would pin the framework version for.
7. **Quality gates from the first commit:** Pest (tests), PHPStan level 9 via Larastan (`app/`, `src/`, `database/`), Pint (style), bundled as `composer check`. CI runs the gate on PHP 8.3/8.4 × SQLite/Postgres.

## Consequences

- Every future stage inherits a repo where `composer check` green is the definition of done.
- Postgres-only features must degrade or be guarded for SQLite so the local loop stays fast.
- Spec documents still referencing Laravel 12 are to be read as Laravel 13; they will be updated opportunistically.
