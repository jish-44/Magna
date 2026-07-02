# Magna Official Store — Plan

**Status: Specification v0.1.**
Decision: the Official Store exists **from the first public release** — but it opens in stages. Stage 1 carries only first-party (Magna-made) plugins and themes, which requires no review infrastructure and no trust model beyond "we wrote it." Third-party open submissions come last, when the review pipeline exists.

The store is a **directory and storefront on top of Composer** (the Statamic model). It never hosts or executes uploaded archives (the WordPress model). Installation always flows through Composer — whether triggered from the CLI or by one click in the admin.

---

## Architecture

```
store.magnacms.com          Laravel app: listings, search, ratings, licensing, checkout
packagist.org               distribution for free packages (public Composer)
repo.magnacms.com           private Composer repository for paid packages
                            (license-key authenticated, per-site)
Admin "Store" screen        browses store API; "Install" runs the Composer flow
                            server-side (composer require + enable + migrate)
```

One-click install in the admin is therefore *safe by construction*: it can only install published, versioned Composer packages that passed the store's checks — never an uploaded file.

## Store data model (minimum)

Listing: package name, type (plugin/theme), display name, description, screenshots, category, `pairsWith`, core-compat matrix (verified by CI, not self-declared), pricing (free / one-time / subscription), install count, ratings (Stage 2+).

---

## Stage 1 — Launch (ships with Magna 1.0): First-party only

Everything in the store is built by the Magna team. This is both the trust model and the ecosystem seed — the store must not look empty on day one.

**Launch catalog (build these alongside the core; they are also how the plugin API gets proven):**

| Package | Type | Why at launch |
|---|---|---|
| `magna/blog` | plugin | the "hello world" every CMS is judged by |
| `magna/seo` | plugin | expected everywhere |
| `magna/forms` | plugin | most-requested feature in every CMS |
| `magna/pages` | plugin | the rendered frontend (Phase 3 of the roadmap) |
| `magna/theme-launch` | theme | clean multi-purpose default theme — full spec in `default-theme-spec.md` |
| `magna/theme-studio` | theme | portfolio/agency theme — proves theme-swapping |

Store features at this stage: browse, search, categories, compat display, install instructions + one-click from admin. No accounts, no ratings, no payments. It can literally be a static site over the package metadata — cheap to ship.

## Stage 2 — Verified partners (post-1.0, with Roadmap Phase 3)

Hand-picked external developers ("Verified Publisher" badge) can list packages. Manual review of each release by the core team — feasible because volume is small. Adds: publisher accounts, ratings/reviews, install stats. Paid packages begin here (Magna takes a revenue share; licensing via `repo.magnacms.com`).

## Stage 3 — Open submissions (Roadmap Phase 4)

Anyone can submit. Only now is the review pipeline built, because only now is it needed:

1. **Automated gate** — manifest validation; CI compat matrix must pass; static analysis (PHPStan + custom rules: no eval, no core-table writes, no undeclared network calls); themes checked for the no-PHP-logic rule; `uninstall` section completeness.
2. **Human review** — first submission per publisher and each *major* release; patch releases ride the automated gate.
3. **Permission transparency** — the manifest's `permissions` and `provides` are displayed at install time, like mobile app permissions.
4. **Kill switch** — the store can flag a package version as malicious/vulnerable; admins see the warning in their dashboard and updates are blocked. (The store never reaches into a site to delete code — sites are self-hosted.)
5. **Security disclosure** — published policy + contact from Stage 1 onward.

---

## Themes in the store

Themes get the same pipeline plus: live preview generated from the theme's demo content, and reciprocal "Works with" linking from `pairsWith` (dating theme ↔ dating plugin), with an "install both" action in the admin.

## What the store never does

- Host or execute uploaded ZIPs.
- Auto-update plugins on sites without the site's opt-in.
- Allow packages with undeclared side effects (everything is in the manifest, or it fails review).
