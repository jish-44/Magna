# Magna CMS — Performance Specification

**Status: Specification v0.1 (pre-implementation).**
Principle: performance claims are only real if they are **numbers, enforced by CI, published publicly**. Anything not measured here is marketing, and Magna doesn't ship marketing.

---

## 1. Published budgets (release blockers)

Measured on the reference environment (4 vCPU / 8 GB, PostgreSQL 16, Redis, FrankenPHP worker mode) against a seeded dataset of **100k entries across 20 content types, 50k media items**. Budgets are p99, app-server time:

| Surface | Budget (p99) |
|---|---|
| Delivery API, cache hit | < 10 ms |
| Delivery API, cache miss (single entry) | < 50 ms |
| Delivery API, filtered list (20 items + relations) | < 80 ms |
| Management API writes | < 120 ms |
| Magna Pages, full-page cache hit | < 15 ms TTFB |
| Magna Pages, cache miss | < 100 ms TTFB |
| Admin panel interactive load | < 1.5 s (cold) |
| Webhook dispatch after publish | < 1 s |

A benchmark harness (k6 scenarios + the seeder) lives **in the public repo**; anyone can reproduce the numbers. CI runs it nightly against `main`; a >10% regression on any budget line fails the build. Every release publishes its benchmark deltas in the changelog.

## 2. Caching architecture (the actual differentiator)

Most CMSs bolt caching on. Magna's Content Engine is designed around **tag-based invalidation**:

- Every delivery response carries **surrogate keys** (`entry:123`, `type:article`, `media:9`) as headers.
- Publishing/updating an entry purges *exactly* the affected keys — in the app cache (Redis, tagged), and at the edge via built-in drivers for Cloudflare, Fastly, and Varnish (surrogate-key purge APIs). No "clear all cache" button as the primary tool.
- ETags + `stale-while-revalidate` on all delivery endpoints; conditional requests cost no DB queries.
- **Stampede protection**: lock-based revalidation (one request rebuilds, others serve stale).
- Magna Pages: full-page cache with the same surrogate keys — editing one entry invalidates only the pages that render it (the block renderer records which entries each page consumed).

## 3. Runtime

- **FrankenPHP worker mode / Octane is the reference deployment** and is tested in CI (plus plain PHP-FPM as the compatibility baseline — cheap hosting stays supported).
- Stateless app servers; horizontal scale is scale-out + shared Redis/DB.
- Queues (Horizon) handle everything non-critical-path: media conversions, webhooks, search indexing, audit writes.

## 4. Database discipline

- Generated real columns + indexes per content type (see report §Database); GIN indexes on JSONB block fields; covering indexes for the default list queries.
- **Cursor pagination is the default** on delivery APIs (offset pagination degrades at depth; it remains available but documented as such).
- `Model::preventLazyLoading()` active in the entire test suite — an N+1 anywhere is a failed build. Each delivery endpoint has a **query-count assertion** (e.g., entry list ≤ 4 queries regardless of relation population).
- Read-replica support in core config; pgbouncer documented in the deploy guide.

## 5. Media & frontend

- Conversions queued, never inline; AVIF/WebP with responsive `srcset` variants generated per media preset; originals offloaded to S3/R2; CDN-first URLs.
- Theme budgets are enforced by the default theme spec (≤10 KB JS, Lighthouse ≥95) and by `magna:theme:check` for store submissions — the performance story extends to what visitors actually download.

## 6. What "industry-first" means here, honestly

Strapi/Directus do not publish CI-enforced latency budgets or ship surrogate-key purge as a core primitive. Doing both, publicly and reproducibly, is a genuine first for open-source CMS. The claim Magna makes is exactly that — never vague superlatives.
