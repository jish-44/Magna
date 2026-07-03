# Magna Admin — Design Guide

**Status: authoritative.** This is the single source of truth for every screen in the Magna admin panel — Filament resources, widgets, custom pages, and Blade views (including auth screens and the installer). If a value isn't in this guide, don't invent it; add it here first, then use it. Written to be followed exactly by humans and by AI coding sessions.

Applies from Stage 10 onward. The installer and auth views predate this guide and are aligned to it opportunistically.

---

## 1. Brand Overview

Magna's admin is **elegant, modern, data-dense but calm**. It is a professional tool people stare at for hours: deep navy surfaces instead of pure black (less harsh, more depth), generous whitespace between dense clusters of information, one restrained accent, and motion that confirms rather than entertains.

Personality in five words: **quiet, precise, spacious, trustworthy, fast.**

Practical translation:

- Dark by default. Light mode exists but dark is the designed-for experience.
- Color is *information*: violet means "act", emerald means "good", rose means "danger". Decorative color is banned outside the brand gradient.
- Density comes from tight typography and 14px body text — never from cramped spacing.
- Nothing bounces, slides in from off-screen, or animates longer than 400 ms.

---

## 2. Color System

### 2.1 Background layers (back to front)

| Token | Hex / value | Tailwind | Usage |
|---|---|---|---|
| `bg-base` | `#0b0f19` | `bg-[#0b0f19]` | Page/canvas background |
| `bg-shell` | `#0d1220` | `bg-[#0d1220]` | Sidebar + top header bar |
| `bg-sunken` | `#080b13` | `bg-[#080b13]` | Wells inside cards (code blocks, log viewers, toggle-group tracks) |
| `bg-overlay` | `rgba(2,6,23,0.80)` | `bg-slate-950/80` | Modal/slide-over backdrop (with `backdrop-blur-sm`) |

### 2.2 Surfaces (glass panels)

| Token | Value | Usage |
|---|---|---|
| `surface-glass` | `linear-gradient(160deg, rgba(20,27,45,0.70), rgba(13,18,30,0.80))` | All top-level cards, widgets, table containers, modals |
| `surface-solid` | `#141b2d` | Fallback where gradients are impractical (email-safe, tiny elements); equals gray-900 below |
| `surface-raised` | `#1a2338` | Hover state of interactive cards; dropdown menus |
| `surface-border` | `1px solid rgba(255,255,255,0.06)` | Hairline border on every glass panel |
| `surface-shadow` | `0 8px 32px rgba(0,0,0,0.35)` | Shadow on every glass panel |

The canonical glass panel (also available as the `.magna-glass` utility, §9.3):

```html
<div class="rounded-2xl border border-white/[0.06]
            bg-gradient-to-br from-[#141b2d]/70 to-[#0d121e]/80
            shadow-[0_8px_32px_rgba(0,0,0,0.35)]">
```

### 2.3 The Magna gray scale (Filament's `gray`)

Filament paints nearly everything with its `gray` palette. Overriding it with this navy-tinted scale is what re-skins the whole panel — register it exactly as in §9.1.

| Shade | Hex | Role on dark |
|---|---|---|
| 50 | `#f8fafc` | (light mode surfaces) |
| 100 | `#f1f5f9` | (light mode) |
| 200 | `#e2e8f0` | Body text |
| 300 | `#cbd5e1` | Emphasized secondary text |
| 400 | `#94a3b8` | Labels, muted text, inactive icons |
| 500 | `#64748b` | Timestamps, meta, placeholders |
| 600 | `#475569` | Disabled text/icons |
| 700 | `#334155` | Strong borders (input borders on focus-adjacent) |
| 800 | `#1e293b` | Default borders, dividers (at /50 opacity) |
| 900 | `#141b2d` | Solid surface, toggle tracks, input backgrounds (at /90) |
| 950 | `#0b0f19` | Canvas |

### 2.4 Semantic colors

One hue per meaning. The `hover` shade is one step lighter; the `bg tint` is the 500 shade at /10 with a /20 border for badges.

| Meaning | Token | Base hex | Tailwind | Hover | Usage |
|---|---|---|---|---|---|
| Primary / brand actions | `primary` | `#7c3aed` | `violet-600` | `#8b5cf6` | Buttons, active nav, links, focus of attention |
| Info | `info` | `#0ea5e9` | `sky-500` | `#38bdf8` | Informational badges, in-progress states |
| Success | `success` | `#10b981` | `emerald-500` | `#34d399` | Published, healthy, positive deltas |
| Warning | `warning` | `#f59e0b` | `amber-500` | `#fbbf24` | Drafts, pending, degraded |
| Danger | `danger` | `#f43f5e` | `rose-500` | `#fb7185` | Destructive actions, errors, negative deltas |

**Links** in prose: `text-violet-400` (`#a78bfa`), hover `text-violet-300`, no underline until hover.

### 2.5 Brand gradient — restricted

`linear-gradient(135deg, #7c3aed 0%, #0ea5e9 55%, #fb7185 100%)` (violet-600 → sky-500 → rose-400).

Allowed on exactly three things: the logo mark, the active-step bar in multi-step flows (installer), and the thin top border of the login card. Never on buttons, text, charts, or badges.

### 2.6 Text hierarchy

| Level | Color | Tailwind |
|---|---|---|
| Headings, key numbers | `#ffffff` | `text-white` |
| Body | `#e2e8f0` | `text-slate-200` |
| Labels, muted | `#94a3b8` | `text-slate-400` |
| Timestamps, meta, placeholders | `#64748b` | `text-slate-500` |
| Disabled | `#475569` | `text-slate-600` |

Never render text darker than slate-500. slate-600 is for disabled states only.

### 2.7 Borders

| Token | Value | Usage |
|---|---|---|
| `border-default` | `#1e293b` (`border-slate-800`) | Inputs, buttons-secondary, panel internals |
| `border-divider` | `rgba(30,41,59,0.5)` (`border-slate-800/50`) | Table row dividers, list separators, section rules |
| `border-hairline` | `rgba(255,255,255,0.06)` (`border-white/[0.06]`) | Glass panel edges only |

---

## 3. Typography

**Family:** Inter for everything; `JetBrains Mono` for code, tokens, IDs, and API paths. Self-hosted (no Google Fonts requests — same rule as the default theme spec).

```css
font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
font-family: 'JetBrains Mono', ui-monospace, 'Cascadia Code', Consolas, monospace; /* code */
```

**Weights:** 400 body · 500 labels, nav items, table headers · 600 headings, buttons, emphasized values · 700 page titles and stat numbers only. Never 300 or 800+.

**Scale** (size / line-height — use these Tailwind pairs, nothing in between):

| Role | Size | Tailwind | Weight |
|---|---|---|---|
| Page title (h1) | 24 / 32 | `text-2xl` | 700 |
| Stat/KPI number | 30 / 36 | `text-3xl` | 700 |
| Section heading (h2) | 18 / 28 | `text-lg` | 600 |
| Card heading (h3) | 16 / 24 | `text-base` | 600 |
| Subheading (h4–h6) | 14 / 20 | `text-sm` | 600 |
| Body (default) | 14 / 20 | `text-sm` | 400 |
| Small (table cells, inputs) | 13 / 18 | `text-[13px] leading-[18px]` | 400 |
| Caption / meta / badges | 12 / 16 | `text-xs` | 400–500 |
| Overline (group labels) | 11 / 16, tracking `0.08em`, uppercase | `text-[11px] uppercase tracking-wider` | 600 |

Rules: body text is 14px, period — density comes from this, not from cramming. Numbers in tables and stats use `tabular-nums`. One h1 per page. Letter-spacing `-0.02em` on h1/h2 only.

---

## 4. Spacing & Layout Grid

**Base unit: 4px.** All spacing is a multiple of 4; prefer the 8-point rhythm (8/16/24/32) for layout.

| Convention | Value | Tailwind |
|---|---|---|
| Card padding | 24px | `p-6` |
| Compact card padding (stat widgets) | 20px | `p-5` |
| Gap between cards / grid gap | 24px | `gap-6` |
| Gap between page sections | 32px | `space-y-8` |
| Inside-card element spacing | 16px | `space-y-4` |
| Form field vertical gap | 20px | `gap-5` |
| Page gutter | 24px (mobile 16px) | `px-6` / `px-4` |
| Content max width | none (fluid); prose/settings pages `max-w-3xl` | — |

**Border-radius scale** (three radii, no exceptions):

| Element | Radius | Tailwind |
|---|---|---|
| Top-level cards, widgets, modals, table containers | 16px | `rounded-2xl` |
| Buttons, inputs, selects, dropdowns, toggle groups, badges-large | 12px | `rounded-xl` |
| Small chips, badges, kbd, inline code | 8px | `rounded-lg` |
| Avatars, status dots | full | `rounded-full` |

---

## 5. Navigation Shell

### 5.1 Left sidebar

| Property | Value |
|---|---|
| Width | 280px expanded · 72px collapsed (icons only, labels in tooltips) |
| Background | `#0d1220` (`bg-shell`) |
| Right edge | `border-r border-slate-800/50` |
| Padding | `px-3 py-4` |

**Brand block (top):** logo mark (28px, brand gradient) + "Magna" wordmark, `text-white font-semibold text-[15px]`; 16px padding below, then a `border-slate-800/50` divider.

**Nav items** (40px tall, `rounded-xl`, `px-3 gap-3`, icon 20px + label `text-[13px] font-medium`):

| State | Styling |
|---|---|
| Default | `text-slate-400`, icon `text-slate-500`, transparent background |
| Hover | `text-slate-200`, `bg-white/[0.04]` |
| Active | `text-white`, `bg-violet-600/15`, icon `text-violet-400`, plus a 3px×20px `bg-violet-500 rounded-full` pill on the left edge |
| Badge (counts) | right-aligned, `text-[11px] font-semibold`, `bg-slate-900/90 text-slate-400 rounded-lg px-1.5` |

**Groups:** overline label (`text-[11px] uppercase tracking-wider text-slate-500 font-semibold`, `px-3 pt-6 pb-2`). Group order: *(no label)* Dashboard · **Content** (types, entries, media) · **Site** (pages, themes, menus — appears with Magna Pages) · **Plugins** · **Access** (users, roles, tokens) · **System** (settings, audit log, health). Plugins inject into these groups via their nav registration; new top-level groups require a spec change.

**Footer (pinned bottom):** divider, then user row — avatar 32px `rounded-full ring-1 ring-slate-800`, name `text-[13px] text-slate-200 font-medium`, role `text-[11px] text-slate-500`, kebab menu (profile, theme toggle, sign out). Collapsed mode shows the avatar only.

### 5.2 Top header bar

| Property | Value |
|---|---|
| Height | 64px |
| Background | `#0d1220` with `border-b border-slate-800/50` |
| Contents (left → right) | sidebar collapse toggle · breadcrumbs (`text-[13px] text-slate-500`, current page `text-slate-200`) · spacer · global search · notifications bell · environment badge |

**Global search:** 36px tall, `w-72`, `bg-slate-900/90 border-slate-800 rounded-xl text-[13px]`, placeholder slate-500, magnifier icon slate-500, `⌘K` kbd hint (`rounded-lg bg-slate-800/80 text-slate-500 text-[11px] px-1.5`).

**Environment badge:** `PRODUCTION` in `text-[11px] font-semibold uppercase` — amber tint (`bg-amber-500/10 text-amber-400 border border-amber-500/20 rounded-lg px-2 py-0.5`); local/staging uses sky tint. Always visible; this is a safety feature.

---

## 6. Core Components

### 6.1 Cards / panels

Always the glass panel (§2.2) at `rounded-2xl p-6`. Card header pattern: h3 title + optional caption below (`text-xs text-slate-500`), actions top-right (ghost buttons). No nested glass panels — inside a card use `bg-sunken` wells or dividers instead.

### 6.2 Buttons

All: `rounded-xl`, `text-[13px] font-semibold`, height 36px (`px-4`), small 30px (`px-3 text-xs`), icon-gap 8px, transition 150ms. Disabled: 45% opacity, no pointer events.

| Variant | Default | Hover |
|---|---|---|
| Primary | `bg-violet-600 text-white` | `bg-violet-500` |
| Secondary | `bg-slate-900/90 text-slate-200 border border-slate-800` | `border-slate-700 text-white` |
| Danger | `bg-rose-600 text-white` (confirm-modal context) / secondary style with `text-rose-400 border-rose-500/30` (inline) | `bg-rose-500` / `bg-rose-500/10` |
| Ghost | `text-slate-400`, transparent | `text-slate-200 bg-white/[0.04]` |

One primary button per view region. Destructive actions always confirm via modal.

### 6.3 Form inputs & selects

Height 40px, `rounded-xl`, `bg-slate-900/90`, `border border-slate-800`, `text-[13px] text-slate-200`, placeholder `text-slate-500`, `px-3.5`.

- Focus: `border-violet-500` + `ring-2 ring-violet-500/25` (plus the global focus-visible rule, §10).
- Error: `border-rose-500/60 ring-2 ring-rose-500/20`, message below `text-xs text-rose-400` with a 14px alert icon.
- Label: `text-[13px] font-medium text-slate-300`, 6px above; required mark `text-rose-400`. Help text `text-xs text-slate-500` below the field.
- Selects/dropdown panels: `bg-[#1a2338] border-slate-800 rounded-xl shadow-[0_8px_32px_rgba(0,0,0,0.45)]`; options 36px, hover `bg-white/[0.05]`, selected `text-violet-400` + check icon.
- Toggles: track `bg-slate-800` → checked `bg-violet-600`; thumb white.

### 6.4 Tables

Container = glass panel with `p-0 overflow-hidden`; toolbar (search/filters) inside at `p-4` with a bottom divider.

- Header row: `text-[11px] uppercase tracking-wider font-semibold text-slate-500`, `bg-white/[0.02]`, `py-3 px-4`.
- Body: `divide-y divide-slate-800/50`; cells `py-3.5 px-4 text-[13px] text-slate-200`; secondary cell text slate-500; row hover `bg-white/[0.03]`; row click targets the whole row.
- Numbers right-aligned `tabular-nums`. Primary column may carry a 32px avatar/thumb.
- Pagination footer: divider, `text-xs text-slate-500` summary left, page buttons right (ghost style, active page `bg-violet-600/15 text-violet-300`).

### 6.5 Badges / pills

`rounded-lg px-2 py-0.5 text-[11px] font-semibold` — tinted, never solid: `bg-{color}-500/10 text-{color}-400 border border-{color}-500/20`.

Canonical mappings: **Published** emerald · **Draft** amber · **Scheduled** sky · **Archived** slate (`bg-slate-500/10 text-slate-400`) · **Failed/Suspended** rose · **Live/processing** adds the pulsing dot (§6.10).

### 6.6 Tabs & toggle groups

- Tabs (page-level): underline style — items `text-[13px] font-medium text-slate-400 pb-3`, active `text-white` with 2px `bg-violet-500` underline; container `border-b border-slate-800/50`.
- Toggle groups (view switchers, e.g. "7d / 30d / 90d"): track `bg-slate-900/90 border border-slate-800 rounded-xl p-1`; segments `rounded-lg px-3 py-1.5 text-xs font-medium text-slate-400`; active segment `bg-slate-800 text-white shadow-sm`.

### 6.7 Modals & slide-overs

Backdrop `bg-slate-950/80 backdrop-blur-sm`. Panel = glass card `rounded-2xl` (slide-overs: `rounded-l-2xl`, full-height right), `max-w-lg` default, `p-6`, title h3, close ghost-icon top-right. Footer: divider + right-aligned buttons (secondary then primary). Danger confirms: rose icon in a `bg-rose-500/10 rounded-full p-3` circle, exact consequence stated ("This deletes 3 entries permanently"), danger button reads the verb ("Delete entries"), never "OK".

### 6.8 Tooltips

`bg-[#1a2338] text-slate-200 text-xs rounded-lg px-2.5 py-1.5 border border-slate-800 shadow-lg`, 150ms delay, no arrow. Icon-only buttons must have one.

### 6.9 Empty states

Centered in the card: icon 40px `text-slate-600`, title `text-sm font-medium text-slate-300`, one line of guidance `text-[13px] text-slate-500`, optional primary button. Copy names the action, not the absence: "Create your first content type", not "No data".

### 6.10 Loading & live states

- Skeletons: `bg-slate-800/60 rounded-lg animate-pulse` shapes matching final layout; never spinners for content areas. Spinners (16px, `text-violet-400`) only inside buttons mid-action.
- **Pulsing live dot:** 8px `rounded-full bg-emerald-400` plus an absolutely-positioned twin with `animate-ping opacity-40`. Reserved for genuinely live things (queue workers, webhook delivery, health).
- Charts and progress bars transition `all 400ms cubic-bezier(0.4,0,0.2,1)`; everything else 150ms.

---

## 7. Data Visualization

**Series palette, in order** (never introduce other hues):

1. `#8b5cf6` violet-500 · 2. `#38bdf8` sky-400 · 3. `#34d399` emerald-400 · 4. `#fbbf24` amber-400 · 5. `#fb7185` rose-400 · 6. `#64748b` slate-500

Semantic overrides win over order: success/error series are always emerald/rose regardless of position.

**Chart cards:** standard glass card; header row = title (h3) left, toggle group (time range) right; chart body `h-64` (stat sparklines `h-12`, no axes).

**Chart.js styling (Filament widgets):** grid `rgba(148,163,184,0.08)`, x-grid off; ticks `#64748b` at 11px Inter; no chart legend for single series — multi-series legends are HTML chips above the chart (8px color dot + `text-xs text-slate-400`); line width 2, `tension: 0.4`, points hidden until hover (radius 3); area fills = series color gradient 20% → 0% opacity; bars `borderRadius: 6`, `maxBarThickness: 24`; tooltips match §6.8 styling (`backgroundColor: '#1a2338'`, `borderColor: '#1e293b'`, `titleColor: '#e2e8f0'`, `bodyColor: '#94a3b8'`).

KPI stat widgets: label `text-xs text-slate-500` → number `text-3xl font-bold text-white tabular-nums` → delta chip (`text-xs font-medium`, emerald/rose with ▲▼) + sparkline right.

---

## 8. Iconography

**Standard: Heroicons v2** — outline for navigation and empty states, **mini (20px solid)** inside buttons, table rows, badges, and inputs. This is Filament's native set; every built-in Filament icon then matches ours for free, which no third-party set can promise.

Sizes: nav 20px · buttons/inline 16px (mini scaled) or mini 20px in 36px buttons · empty states 40px. Color follows the text color of the element (`text-slate-500` muted, `text-violet-400` active, semantic colors in badges).

If a glyph doesn't exist in Heroicons, use Lucide for that one icon via `composer require mallardduck/blade-lucide-icons` (`<x-lucide-*>`) — do not swap the whole set: Filament renders its own internal icons from Heroicons enums (`Filament\Support\Icons\Heroicon`), and per-icon overrides via `FilamentIcon::register()` are for exceptions, not wholesale replacement.

---

## 9. Filament-Specific Theming (Filament 4 · Tailwind v4)

### 9.1 Panel provider

```php
use Filament\Enums\ThemeMode;
use Filament\Support\Colors\Color;

$panel
    ->id('magna')->path('admin')
    ->colors([
        'primary' => Color::hex('#7c3aed'),
        'info'    => Color::hex('#0ea5e9'),
        'success' => Color::hex('#10b981'),
        'warning' => Color::hex('#f59e0b'),
        'danger'  => Color::hex('#f43f5e'),
        // The re-skin: navy-tinted gray drives every Filament surface.
        'gray' => [
            50 => '#f8fafc', 100 => '#f1f5f9', 200 => '#e2e8f0', 300 => '#cbd5e1',
            400 => '#94a3b8', 500 => '#64748b', 600 => '#475569', 700 => '#334155',
            800 => '#1e293b', 900 => '#141b2d', 950 => '#0b0f19',
        ],
    ])
    ->defaultThemeMode(ThemeMode::Dark)   // dark is the designed experience
    ->darkMode(true)                      // user may switch; light uses gray 50–300
    ->font('Inter')
    ->viteTheme('resources/css/filament/magna/theme.css')
    ->sidebarCollapsibleOnDesktop()
    ->maxContentWidth('full');
```

### 9.2 Theme setup

`php artisan make:filament-theme magna` scaffolds `resources/css/filament/magna/theme.css` importing Filament's base theme; register it with `->viteTheme()` as above and add it to `vite.config.js` inputs. Fonts are self-hosted: put Inter/JetBrains Mono woff2 files in `resources/fonts` and `@font-face` them in the theme file.

### 9.3 `theme.css` — tokens and the glass treatment

```css
@import '../../../../vendor/filament/filament/resources/css/theme.css';

@source '../../../../app/Filament/**/*';
@source '../../../../src/Magna/**/*.blade.php';

@theme {
    --font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif;
    --font-mono: 'JetBrains Mono', ui-monospace, monospace;
    --radius-xl: 0.75rem;   /* buttons & inputs → rounded-xl feel */
    --radius-2xl: 1rem;     /* cards */
}

/* Glass panels: applied to Filament sections & widgets in dark mode */
.dark .fi-section,
.dark .fi-wi-widget,
.dark .fi-ta-ctn,
.magna-glass {
    background: linear-gradient(160deg, rgba(20, 27, 45, .70), rgba(13, 18, 30, .80));
    border: 1px solid rgba(255, 255, 255, .06);
    border-radius: 1rem;
    box-shadow: 0 8px 32px rgba(0, 0, 0, .35);
}

.dark .fi-sidebar, .dark .fi-topbar { background: #0d1220; }
.dark .fi-body { background: #0b0f19; }

/* Thin custom scrollbars */
* { scrollbar-width: thin; scrollbar-color: #334155 transparent; }
*::-webkit-scrollbar { width: 8px; height: 8px; }
*::-webkit-scrollbar-thumb { background: #334155; border-radius: 8px; }
*::-webkit-scrollbar-thumb:hover { background: #475569; }

/* Live-status pulse */
@keyframes magna-ping { 75%, 100% { transform: scale(2); opacity: 0; } }
.magna-live-dot { position: relative; }
.magna-live-dot::after {
    content: ''; position: absolute; inset: 0; border-radius: 9999px;
    background: inherit; animation: magna-ping 1.6s cubic-bezier(0, 0, .2, 1) infinite;
}
```

Verify the `.fi-*` class hooks against the installed Filament version at Stage 10 — they are stable but versioned; adjust selectors, not the design.

### 9.4 Rules of engagement with Filament

- Reach for Filament's components first; drop to custom Blade only when no component fits, and build customs from these tokens.
- Never inline arbitrary hex values in resources — semantic Filament color names (`primary`, `danger`, `gray`) or the tokens above.
- Widgets extend Filament's base widgets so theming cascades; charts get the §7 Chart.js options via `getOptions()`.

---

## 10. Accessibility & Contrast

Measured against `#0b0f19` / `#141b2d` (worst case), targets are WCAG 2.2 AA:

| Combination | Ratio | Verdict |
|---|---|---|
| White on canvas | ~18:1 | ✅ headings |
| slate-200 body on canvas | ~13:1 | ✅ body |
| slate-400 labels on surface | ~6.5:1 | ✅ any size |
| slate-500 meta on surface | ~4.5:1 | ✅ but **meta/timestamps only**, never essential instructions |
| violet-400 links on canvas | ~6.6:1 | ✅ |
| White on violet-600 buttons | ~5.8:1 | ✅ |
| amber-400 / emerald-400 / rose-400 badge text on their /10 tints | ≥5:1 | ✅ (use the 400 shades for badge text, not 500) |

Hard rules:

- **Focus:** global `:focus-visible` = `outline: 2px solid #38bdf8; outline-offset: 2px` (sky — visible against both violet controls and navy surfaces). Never `outline: none` without a replacement.
- Color never carries meaning alone: status = dot/icon + word, deltas = arrow + sign, errors = icon + message.
- All interactive targets ≥ 32px; icon-only buttons get `aria-label` + tooltip.
- Respect `prefers-reduced-motion`: disable ping/pulse animations and chart transitions.
- Keyboard path through every flow; modals trap focus and restore it on close.

---

## 11. Do's and Don'ts

**Do**

- ✅ Use `rounded-2xl` + glass treatment for every top-level card; `rounded-xl` for every control.
- ✅ Pick colors by *meaning* (§2.4) and text shades by *hierarchy* (§2.6) — the decision is already made.
- ✅ Keep body text 14px and table text 13px; density via typography, space via the 8-point rhythm.
- ✅ Show the environment badge, confirm destructive actions with consequences, name empty-state actions.
- ✅ Use `tabular-nums` on every number that can change.
- ✅ Add any genuinely new pattern **to this guide first**, then implement it.

**Don't**

- ❌ Introduce any hue outside §2 — no teal, no orange, no "just this once" pink.
- ❌ Use the brand gradient on anything except the three sanctioned spots (§2.5).
- ❌ Use pure black (`#000`) anywhere, or text darker than slate-500.
- ❌ Nest glass panels, mix radii on the same element class, or exceed 400ms animations.
- ❌ Use spinners where a skeleton fits, or a solid-fill badge where a tint works.
- ❌ Bypass Filament components with bespoke HTML when a themed Filament component exists.
- ❌ Ship an icon from a second icon set when Heroicons has the glyph.
