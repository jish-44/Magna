# Magna CMS
## Project Report
### Version 2.1

> Version 2.0 revises the original plan after a critical review. The biggest changes:
> a **Content Engine is now part of the core** (a headless CMS without one is just a plugin framework),
> the **target user is defined**, the **roadmap is cut to a shippable MVP**, the **admin technology is decided**,
> and the **marketplace is deferred in favor of Composer-based plugin distribution** for v1.

---

# Project Information

**Project Name**
Magna CMS

**Project Type**
Open Source Headless Content Management System

**Framework**
Laravel 12+ / PHP 8.3+

**Architecture**
API First • Headless • Plugin Driven • Schema as Code

**License**
MIT for the core. The "Magna" name and logo are trademarked (register early — this is how MIT projects protect their commercial layer). Commercial plugins and the cloud platform may use their own licenses.

---

# Executive Summary

Magna CMS is a modern, API-first, headless Content Management System built on Laravel.

Magna follows a **Small Core Architecture**. The core provides two things:

1. **The Kernel** — authentication, users, RBAC, plugin system, events, settings, API infrastructure.
2. **The Content Engine** — content types, fields, entries, revisions, drafts, publishing workflow, localization, and relationships. This is the product. It cannot be a plugin, because every plugin and every API consumer depends on it behaving one way.

Everything else — blog presets, e-commerce, CRM, SEO tooling, AI — is a plugin.

The philosophy of Magna is:

> Keep the core small — but the core of a CMS is content.
> Everything that is not kernel or content is a plugin.

---

# Target Users (new section)

A product for everyone is a product for no one. Magna targets, in priority order:

1. **Laravel developers and agencies** building client sites, apps, and platforms who currently glue together Nova/Filament admin panels by hand for every project.
2. **Product teams** who want a self-hosted, hackable alternative to Contentful/Strapi that runs on the PHP infrastructure they already have.
3. **(Later)** Content editors and site owners — reached *through* the developers who build for them. The editing experience must be excellent, but Magna is not marketed to non-technical users installing it themselves. A headless CMS with no frontend cannot honestly promise "WordPress simplicity" to end users; it can promise it to developers.

---

# Market Position (new section)

The headless CMS market is crowded: Strapi and Directus (Node), Payload (TypeScript), Contentful and Sanity (SaaS). Magna does not out-feature them on day one. It wins on a specific, underserved wedge:

**There is no dominant Laravel-native, database-backed, headless-first CMS with a real plugin ecosystem.**

- Statamic is Laravel but flat-file-first, theme-oriented, and paid.
- Twill is an admin package, not a headless platform.
- October/Winter are traditional theme-driven CMSs.

Magna's pitch: *"The headless CMS that speaks Laravel."* Millions of PHP developers, existing hosting, Eloquent-native extensibility, and a plugin model the ecosystem already understands from Composer.

**Differentiators to actually build (not just claim):**

- Schema as code — content types defined in versionable files, synced across environments (a top enterprise pain point Strapi handles poorly).
- First-class draft preview for headless frontends (signed preview URLs + preview tokens for Next.js/Nuxt).
- Plugin system with the safety of Composer distribution.
- An editing UI that is genuinely pleasant, powered by Filament.
- **Hybrid mode** — the optional Magna Pages plugin renders a real website from the same install, on plain PHP hosting. No open-source headless competitor offers a credible "website out of the box" path; this is Magna's answer to "install WordPress and see a site."

---

# Vision

To become the default content platform of the Laravel ecosystem, and from that base a serious open-source alternative to Strapi and Directus.

# Mission

Create an open platform where developers extend functionality through plugins while the core remains small, stable, and boring — in the best sense.

---

# Design Principles

## Content First
The Content Engine is the product. Every decision optimizes for modeling, editing, and delivering content.

## Small Core, Not Empty Core
"Can this be a plugin?" is the right question — but content types, revisions, drafts, and localization are infrastructure, not features. Plugins build **on** the Content Engine; they don't provide it.

## API First
Every admin capability is available through the REST API. The admin panel itself is a consumer of the same API where practical.

## Headless First, Frontend Optional
The core never renders websites — it serves content through APIs. But an official **Magna Pages** plugin can turn any install into a rendered website (see Frontend Strategy). Headless is the architecture; a visible site is an option, not a contradiction.

## Developer Friendly
A Laravel developer should be productive in under an hour. Plugins are Composer packages. Content types are Eloquent-friendly. Nothing magical.

## Boring Where It Counts
Use proven Laravel ecosystem packages (Sanctum, Filament, Scout, Horizon) instead of reinventing them. Innovation budget is spent on the Content Engine and plugin system only.

---

# Core Architecture

The core is two layers:

## Layer 1 — Kernel

- Authentication (session + API tokens via Sanctum; 2FA)
- User Management
- RBAC (roles, permissions, policies)
- Plugin Manager (discovery, lifecycle, registry)
- Event & Hook System
- Settings System
- Storage Manager (local, S3-compatible)
- API Engine (REST, OpenAPI generation)
- Scheduler, Queues, Cache (thin wrappers over Laravel's own)
- Logging & Health endpoints
- Audit Log

*Removed from core vs. v1.0:* Plugin Marketplace (deferred — see Distribution), Social Login and Passkeys (first-party plugins).

## Layer 2 — Content Engine (new — the most important change in v2.0)

The v1.0 report never defined how content works. This is the product:

- **Content Types** — admin-defined and file-defined models (e.g., Article, Product, Page)
- **Field Types** — text, rich text (portable JSON format, not raw HTML), number, boolean, date, media, select, relation, repeater/blocks, JSON
- **Entries** — the actual content, with per-type database strategy (see Database)
- **Relationships** — one-to-one, one-to-many, many-to-many between entries
- **Drafts & Publishing** — draft → review → published; scheduled publish/unpublish
- **Revisions** — full version history with diff and restore
- **Localization** — per-field and per-entry translation, locale fallbacks
- **Preview** — signed preview tokens so headless frontends can render drafts
- **Schema as Code** — every content type serializes to a JSON/PHP definition file; `php artisan magna:schema:diff` and `magna:schema:sync` move schemas between dev/staging/prod. Schemas live in git.

**Media Management moves into the Content Engine** (upload, folders, metadata, image conversions, S3/R2). A headless CMS without media in core forces every plugin to depend on a media plugin anyway — that's core by another name. Advanced DAM features (AI tagging, video pipelines) remain plugins.

---

# Plugin Philosophy

Every feature beyond Kernel + Content Engine is a plugin:

- Blog preset (content types + admin niceties)
- Forms
- E-commerce
- CRM
- SEO
- Newsletter
- AI
- Comments
- Advanced Search (Meilisearch/Typesense via Scout)
- Social Login / SSO
- GraphQL (official plugin, post-MVP)

## Plugin = Composer Package (revised)

A Magna plugin **is a Composer package** with a `magna.json` manifest. This is the single most important simplification in v2.0:

- Dependency resolution, versioning, signatures, and mirrors come free from Composer/Packagist.
- No custom installer executing arbitrary uploaded ZIPs in v1 — that is WordPress's malware model, and it takes a security team Magna doesn't have yet.
- First-party CLI wraps it: `php artisan magna:plugin:install vendor/magna-seo`.

The **Official Store exists from the first public release** — as a directory and storefront *on top of* this Composer infrastructure (the Statamic model, not WordPress.org). It opens in stages: Stage 1 ships with Magna 1.0 carrying **first-party plugins and themes only** (no review infrastructure needed — a curated catalog with one-click install from the admin, which runs the Composer flow server-side); Stage 2 adds hand-picked verified publishers and paid packages; Stage 3 (Phase 4) opens third-party submissions once the automated + human review pipeline exists. Full spec: `docs/store-plan.md`. Developer-facing specs: `docs/plugin-development-guide.md` and `docs/theme-development-guide.md` — written before the code, as the contract the core must satisfy.

## Plugin Lifecycle

Install (Composer) → Manifest validation → Compatibility check (core version) → Enable → Migrations → Service registration → Permission registration → Admin/menu registration → API route registration → Ready

Disable and uninstall are first-class: a plugin must declare what it leaves behind (tables, files) and the uninstaller must honor a `--purge` flag.

## Plugin Structure

```
vendor/acme/magna-blog/
├── magna.json          # manifest: name, version, core compat, permissions, provides
├── composer.json
├── src/                # ServiceProvider, models, policies
├── routes/api.php
├── database/migrations/
├── resources/          # Filament resources / admin pages
├── schemas/            # content type definitions this plugin ships
└── tests/
```

---

# Admin Panel (decision made — was undefined in v1.0)

**v1 admin is built on Filament 4.**

Rationale:
- Filament is Laravel-native, mature, and already has a plugin/panel extension model — plugins register Filament Resources and Pages, which solves "how does a plugin inject UI into the shell?" This was the hardest unanswered engineering problem in v1.0 (a custom SPA shell requires solving frontend module federation for third-party code — months of work, high risk).
- It ships forms, tables, notifications, dark mode, and accessibility out of the box.
- Trade-off accepted: admin UI is Livewire-based, not a headless SPA. That's fine — the *content delivery* is headless; the admin does not need to be. A custom React shell can be revisited at Phase 5 if the ecosystem demands it, because the admin only talks to the same REST API.

The Admin Shell responsibilities (navigation, auth, notifications, global search, dashboard widgets, settings pages) map directly onto Filament panels; Magna provides conventions and a base plugin class so plugin authors write minimal boilerplate.

---

# Frontend Strategy — Magna Pages (new in v2.1)

Magna's answer to "a WordPress user installs it and sees no website": an **official, optional frontend delivered as a plugin**, not a separate app.

## Why a plugin, not a separately installed frontend

A separate frontend app that must be installed and "connected" to the backend recreates the worst of headless: two deployments, token handshakes, CORS, and cross-app preview. Instead, **Magna Pages is installed like any other plugin** and the same Laravel install starts serving a public website — one server, one deploy, runs on ordinary PHP hosting where Node-based frontends can't. The core remains purely headless; users who don't want a rendered site simply don't install Pages. Separately deployed frontends (Next.js/Nuxt starters) remain the recommended path for headless projects.

## Rendering model

- Pages renders **the same content JSON the delivery API serves** — Blade + Tailwind, server-side, cached. Nothing is theme-only data, so any Pages site can later go headless without content migration.
- Every block is defined once and rendered per channel: a Blade view (Pages), a React/Vue component (headless starters).

## Blocks (the editor)

Magna does **not** clone Gutenberg — a Gutenberg-class canvas editor is a multi-year effort that would consume the project. The block system layers up:

1. **Block definition** = schema (fields) + admin edit form + per-theme views. Stored as the Content Engine's portable-JSON blocks field (already in Phase 1).
2. **v1 editor: structured block list** in the Filament admin — add, remove, drag-to-reorder, edit each block through a real form — with a **live preview iframe** of the rendered page using the same signed preview tokens built for headless preview.
3. **Later, demand-driven:** click-to-edit inside the preview, then visual drag-drop composition.

## Themes

A theme is a **presentation-only package**: Blade views for blocks, design tokens (colors, typography, spacing), page templates, and demo content. Themes contain **no PHP logic, no migrations, no hooks** — that is what makes them safe to one-click install and cheap to review in the marketplace, unlike WordPress themes (a malware vector precisely because they execute arbitrary code).

## Plugin + theme pairing

A domain plugin ships the data and behavior; a matching theme ships the look. Example: a Dating plugin provides Profile/Match content types, API endpoints, and blocks ("profile grid", "match CTA"); the Dating theme provides their views and page templates. Install both → working product. This pairing is a first-class marketplace concept.

## Boundaries

- Pages ships in **Phase 3** — the MVP exit test ("Next.js site in an afternoon") is untouched, and Phase 1's blocks field is the only dependency.
- Pages never becomes required. Every Pages feature must work through the API like everything else.

---

# API

**Ship REST first. Everything else follows.**

- **REST** (v1, versioned as `/api/v1/`): full CRUD for content, media, users, settings; filtering, sorting, pagination, sparse fieldsets, relation population; delivery endpoints are cacheable and support ETags.
- **OpenAPI** spec auto-generated from content schemas — free documentation and typed SDK generation later.
- **Webhooks** (v1): entry published/updated/deleted, media events — required for static-site rebuilds (Next.js/Nuxt ISR), which is the #1 headless integration.
- **GraphQL** — official plugin, Phase 3. Doing REST *and* GraphQL *and* realtime in v1 triples API surface and testing for little launch value.
- **Realtime events** — Phase 3+ (Laravel Reverb).
- API tokens with per-token scopes (read-only delivery tokens vs. management tokens), rate limiting per token.

---

# Database

Primary: **PostgreSQL**. Also supported: MySQL 8+, MariaDB, SQLite (dev/test).

## Content storage strategy (was hand-waved in v1.0)

"Avoid EAV, use relational tables" is the right instinct but incomplete for *admin-defined* content types, which can't have hand-written migrations. Magna's approach:

- Each content type gets **one physical table**, generated and migrated automatically from its schema (`magna_entries_article`, …). Common columns (id/ULID, status, locale, published_at, author, timestamps) are fixed; simple fields become real columns; complex fields (blocks, repeaters) are JSONB with GIN indexes.
- Schema changes generate real migrations (via `doctrine/dbal` column tools), previewed with `magna:schema:diff` before applying.
- Plugins that define their own domain models (e-commerce orders, CRM contacts) own their own conventional tables — unchanged from v1.0.

This keeps queries indexed and Eloquent-native while still allowing runtime-defined types. It is harder to build than a single JSONB blob table, and it is the moat.

---

# Authentication

Core: login, registration (toggleable), password reset, email verification, 2FA (TOTP), API tokens (Sanctum).
Plugins: social login, passkeys/WebAuthn, SAML/OIDC SSO (enterprise, Phase 5).

# Users, Roles, Permissions

Unchanged from v1.0 — core manages users/roles/permissions; each plugin registers namespaced permissions (`blog.posts.create`, `shop.products.manage`); RBAC engine enforces centrally. Add: **per-content-type permissions are generated automatically** (`content.article.publish`), and **audit logging of permission changes is core**, not optional.

# Events & Hooks

Unchanged in spirit. Concrete commitment for v2.0: hooks are **typed PHP interfaces/contracts** (e.g., `RegistersAdminNavigation`, `ExtendsEntryForm`, `FiltersApiQuery`), not string-keyed filters à la WordPress. Every event and hook is documented in the SDK, and **the hook API is semver-guaranteed from 1.0** — plugin authors will not build on a moving target.

# Settings

Unchanged: core settings (general, mail, storage, cache) + plugins register their own settings pages/groups. Settings are exportable (part of schema-as-code) with secrets excluded.

---

# AI (plugin, repositioned)

Still a plugin, still important — but **Phase 3, not a pillar of the launch**. Launch capabilities: content generation/rewriting, translation assistance, SEO suggestions, alt-text generation — all provider-agnostic (Anthropic, OpenAI, local). "AI agents platform" and workflow automation are Phase 4+; naming them earlier is roadmap fiction.

One AI feature *is* worth building early because it's cheap and differentiating: **semantic search over content via pgvector** (Postgres-native, no extra infrastructure).

---

# Performance (full spec: `docs/performance-spec.md`)

Performance is a **published, CI-enforced contract**, not an ingredient list:

- **Public latency budgets as release blockers** — e.g., delivery API < 10 ms p99 cached / < 50 ms uncached, Pages TTFB < 15 ms cached — measured by a reproducible benchmark harness that lives in the public repo; nightly CI fails on >10% regression; every release publishes its benchmark deltas.
- **Tag-based cache invalidation as a core primitive**: every delivery response carries surrogate keys; publishing an entry purges exactly the affected responses — in Redis and at the edge (Cloudflare/Fastly/Varnish drivers). No "clear all cache" as the primary tool. Stampede protection built in.
- FrankenPHP/Octane worker mode is the reference deployment (PHP-FPM remains the compatibility baseline); ETags + stale-while-revalidate; cursor pagination by default; `preventLazyLoading` + per-endpoint query-count assertions in the test suite; media conversions queued with AVIF/WebP variants.

No open-source CMS today ships CI-enforced public latency budgets *and* surrogate-key purge in core — that combination, stated exactly, is the "industry-first" claim.

# Security (full spec: `docs/security-spec.md`)

Security is a **process with proof**, target OWASP ASVS Level 2 verified before 1.0:

- **Secure by default:** Argon2id, per-role enforceable 2FA, scoped expiring API tokens, default-deny CORS on management APIs, strict CSP/HSTS headers, uploads re-encoded and sniffed (not extension-checked), signed expiring preview/media URLs, registration off by default.
- **Field-level encryption as a schema attribute** (`"encrypted": true` on any content field) — a first-class primitive no mainstream open-source CMS offers.
- **Plugin trust model, stated honestly:** PHP cannot sandbox in-process plugins, so the model is transparency + gates + response — declared capabilities in `magna.json` shown at install, store static analysis, logic-free themes (the highest-volume category carries near-zero code risk, unlike WordPress), and a store kill switch with an installed-package advisory feed in the admin.
- **Supply chain:** `composer audit` + PHPStan level 9 + Psalm taint analysis in CI, signed releases with per-release SBOMs, published supported-versions/backport matrix.
- **Proof:** third-party penetration test + ASVS assessment before 1.0 (budgeted), published summary, `SECURITY.md`/`security.txt`/CVE process from first release, append-only audit log with SIEM export, GDPR export/erasure contracts that plugins must implement.

---

# Development Tools

```
php artisan magna:plugin:make {name}      # scaffold a plugin package
php artisan magna:type:make {name}       # scaffold a content type schema
php artisan magna:schema:diff            # preview schema changes vs database
php artisan magna:schema:sync            # apply schema (env-to-env content modeling)
php artisan magna:plugin:install {pkg}   # composer require + enable + migrate
```

Plus: plugin test harness (a `TestCase` that boots a minimal Magna app), and a documented plugin developer guide **before** asking anyone to build plugins.

---

# Business Model (sequenced — v1.0 listed everything at once)

1. **Now → 1.0:** Free, open source. The only goal is adoption and GitHub stars. No revenue.
2. **Post-1.0:** First-party premium plugins (SSO/SAML, advanced workflows, DAM) — this funds development without splitting the community.
3. **Then:** Magna Cloud (managed hosting) — the real business, but only viable after organic demand exists.
4. **Then:** Marketplace revenue share, enterprise support.

Consulting/training are removed as business lines — they consume the founders' build time.

---

# Development Roadmap (revised — v1.0's phases were unscoped)

Assumes a very small team. Cut scope, not quality.

## Phase 0 — Foundation (~1 month)
Write the plugin API RFC and content schema spec *before* code. Name/trademark check on "Magna CMS" (a search shows adjacent uses — verify availability). Repo, CI, coding standards, ADRs (architecture decision records).

## Phase 1 — MVP Core (~4–6 months)
Kernel (auth, users, RBAC, settings, events) • Content Engine (types, fields, entries, drafts, revisions, media) • Filament admin • REST delivery + management API with OpenAPI • Webhooks • Plugin system (Composer-based) with **three first-party plugins built alongside it to prove the API**: Blog preset, SEO, Forms.
**Exit criteria:** a developer can model content, edit it, and ship a Next.js site against the API — start to finish in one afternoon.

## Phase 2 — Developer Ecosystem (~3 months)
Plugin SDK + docs + test harness • CLI polish • Schema-as-code sync • Localization • Scheduled publishing • Example frontend starters (Next.js, Nuxt, Livewire) • **Official Store Stage 1** (first-party catalog: blog, SEO, forms + two themes; one-click install from admin) • **Third-party penetration test + ASVS L2 assessment** (gates the 1.0 tag) • Benchmark harness public with first published budget numbers • Public launch: docs site, demo instance.

## Phase 3 — Depth (~3–6 months)
**Magna Pages v1** (rendered frontend plugin: structured block editor, live preview, first two official themes) • GraphQL plugin • Semantic search (pgvector) • AI plugin v1 • Realtime (Reverb) • Advanced media (conversions pipeline, focal points) • Editorial workflow (roles, review states).

## Phase 4 — Commercial
Magna Cloud beta • **Official Store Stage 3**: open third-party submissions with the full review pipeline (automated static analysis, human review, permission transparency, kill switch) • Premium plugins and themes (license-authenticated private Composer repo).

## Phase 5 — Enterprise
Multi-tenancy (single decision made now: **v1 is single-tenant, but all IDs are ULIDs and all queries go through repositories, so tenancy scoping can be added without a rewrite**) • SSO/SAML • SLAs • Compliance (SOC2 path).

---

# Success Metrics (new section)

- Phase 1: the afternoon test (model → edit → API → deployed frontend) passes with a stranger, not the author.
- Phase 2: 10 plugins not written by the core team; 1,000 GitHub stars; 50 production sites self-reported.
- Phase 3: first external contributor with merge rights; documented migration path from WordPress (importer plugin).
- Phase 4: first paying cloud customer.

# Risks (new section)

1. **Scope death.** The #1 risk. Mitigation: the roadmap above, and the Guiding Principle below applied ruthlessly — including to the founders' own ideas.
2. **Crowded market.** Mitigation: the Laravel wedge; don't market as "better Strapi," market as "the Laravel one."
3. **Plugin API churn.** Breaking plugin authors once forgives; twice kills the ecosystem. Mitigation: semver-guaranteed hook contracts, deprecation cycles.
4. **Solo/small-team burnout.** Mitigation: Phase 1 exit criteria are deliberately small; use Filament and ecosystem packages instead of building UI frameworks.
5. **Security incident via plugins.** Mitigation: Composer-based distribution in v1; marketplace review process later.

---

# Guiding Principle (unchanged — it's the best part of v1.0)

Whenever a new feature is proposed, the first question is:

**"Can this be implemented as a plugin?"**

If yes, it does not enter the core. The core is the Kernel and the Content Engine — nothing else.

One addition:

**"Can this ship after 1.0?"**

If yes, it does. The world's #1 CMS will not be the one with the longest feature list in its plan; it will be the one that shipped, earned developers' trust, and never broke their plugins.
