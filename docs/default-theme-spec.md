# Magna "Launch" — Default Theme Specification

**Package:** `magna/theme-launch` · **Status: Specification v0.1 (pre-implementation)** · Ships with Official Store Stage 1.

The default theme has three jobs, in priority order:

1. **Reference implementation.** It is the living proof of `theme-development-guide.md`. Every rule in that guide must be visibly followed here; theme developers will copy this package before they read the docs. If Launch cheats, the ecosystem cheats.
2. **First impression.** For most evaluators, Launch *is* Magna's frontend. Install Magna → enable Pages → activate Launch → see a genuinely good-looking site in under a minute. That moment competes directly with the WordPress install experience.
3. **Standard block coverage.** Launch styles the entire standard block library (defined below), so any site built with core blocks looks finished with zero custom work.

---

## 1. Design direction

- **Personality:** clean, editorial, confident. Generous whitespace, strong type hierarchy, restrained color. Think "modern SaaS marketing site meets magazine" — deliberately neutral enough for a bakery, a startup, or a law firm, because tokens do the personalization.
- **Not:** trendy gradients, heavy animation, or a look that screams one industry. Distinctive themes are the marketplace's job; the default's job is versatile.
- **Mobile-first**, fluid type scale (clamp-based), max content width from the `maxWidth` token.
- **Dark mode** supported out of the box via tokens (`prefers-color-scheme` + manual override token).

## 2. Standard block library (defined here, shipped by `magna/pages`)

Launch must ship a styled view for every block below. This list is also the v1 scope of the Pages standard blocks — Launch and Pages are developed against each other.

| Block | Purpose | Notes |
|---|---|---|
| `hero` | headline, subhead, media, up to 2 CTAs | 3 layout variants: centered, split, full-bleed |
| `text` | rich text prose | typographic showcase — must be beautiful |
| `image` | single image + caption | responsive `srcset` from media conversions |
| `gallery` | image grid | 2/3/4 column options, lightbox-free (no JS dependency) |
| `cta` | banner with heading + button | |
| `features` | icon/title/text grid | 2–4 columns |
| `testimonials` | quotes with attribution | single + grid variants |
| `logos` | logo strip ("as seen in") | grayscale-until-hover |
| `faq` | accordion | native `<details>` — zero JS |
| `pricing` | tier cards | highlight-one option |
| `team` | people grid | pairs with a `person` content type preset |
| `stats` | big-number row | |
| `video` | embed (YouTube/Vimeo/self-hosted) | lazy, click-to-load facade for embeds |
| `form` | renders a form from `magna/forms` | fallback message if plugin absent |
| `entries` | latest/selected entries of any content type | the bridge to blog/custom types |
| `divider`, `spacer` | rhythm control | |

Fallback rule applies as everywhere: a block Launch doesn't know renders via the block's own default view.

## 3. Templates

| Template | Use |
|---|---|
| `default` | generic page: title + blocks |
| `landing` | no page title, hero-first, full-width sections |
| `article` | prose-optimized reading layout (blog posts) — title, meta, body, author card |
| `listing` | paginated entry listing (blog index, archives) |
| `contact` | narrow page tuned for the form block |

Plus `layout.blade.php` (shell with header/nav/footer), and partials: `header`, `footer`, `nav` (mobile nav = CSS-only or minimal JS).

## 4. Design tokens (`tokens.json` defaults)

- **Colors:** `primary` #2563eb, `text`, `muted`, `surface`, `surface-alt`, plus dark-mode counterparts. All color pairs must meet WCAG AA at their default values (see §6).
- **Typography:** `headingFont` Inter (options: Inter, Fraunces, Lora, system-ui), `bodyFont` Inter, `baseSize` 16px, fluid scale ratio token. Fonts self-hosted (no Google Fonts requests — GDPR + performance).
- **Layout:** `maxWidth` 1152px, `radius` 0.5rem, `sectionSpacing` scale.
- Every token must visibly change the site — no dead options. The Theme Options panel generated from this file is part of the demo script.

## 5. Demo content

`demo/content.json` builds a small fictional company site ("Northwind Coffee" or similar — pick once, keep it): home (landing, uses hero/features/testimonials/logos/cta), about (team/stats/text), blog with 3 posts (article/listing), pricing, contact. Every standard block appears at least once across the demo. Imported as drafts, one-click removable — per the theme guide rules.

## 6. Quality bar (acceptance criteria — release blockers, not aspirations)

- **Performance:** Lighthouse ≥ 95 performance / 100 accessibility / 100 SEO on the demo home page (mobile). **JavaScript budget: ≤ 10 KB gzipped total** — nav toggle and video facade only; everything else is HTML/CSS. CSS ≤ 50 KB gzipped (purged Tailwind).
- **Accessibility:** WCAG 2.2 AA. Semantic landmarks, skip link, visible focus states, keyboard-complete nav, `prefers-reduced-motion` respected. Default token values must not be able to fail contrast (token options are curated, not free-form, for text/surface pairs).
- **SEO markup:** semantic headings from block structure, OpenGraph/Twitter meta slots in layout, JSON-LD for `article` template.
- **Robustness:** every block view renders acceptably with all optional fields empty (`magna:theme:check` fixture pass is CI-gated).
- **Guide compliance:** zero PHP logic, all styling through token CSS variables, passes `magna:theme:check` — audited as if it were a third-party submission.

## 7. Build & repo conventions

- Tailwind CSS 4, config mapped to token CSS variables; compiled CSS committed to `assets/css/` (users never run a build).
- Blade views formatted with Pint/prettier-blade; repo is public from day one and linked from the theme guide as the canonical example.
- Screenshot + store live preview generated from demo content.

## 8. Out of scope for v1

Page-builder-style column/section nesting (needs the visual editor phase), WooCommerce-style shop views (e-commerce plugin's paired theme), multilingual demo content, RTL (v1.1 — but don't hardcode `left/right`; use logical CSS properties from the start so RTL is cheap).
