# Magna CMS — Theme Development Guide

**Status: Specification v0.1 (pre-implementation).**
Themes belong to **Magna Pages** (the official rendered-frontend plugin). A pure headless install has no themes — frontends built in Next.js/Nuxt/etc. own their own presentation.

---

## 1. What a theme is — and is not

A theme is a **presentation-only package**: Blade views, design tokens, page templates, assets, and demo content.

A theme contains **no PHP logic, no migrations, no hooks, no service providers, and no database access**. This is a hard rule enforced by the store and by Magna Pages at load time (themes are rendered in a restricted Blade context — no arbitrary PHP execution from theme files beyond templating).

Why so strict: WordPress themes execute arbitrary code, which made them a malware vector and forced every theme review to be a security review. Magna themes are data + templates, so they are safe to one-click install and cheap to review. Anything requiring logic belongs in a plugin; the theme pairs with it (see §7).

---

## 2. Structure

```
acme/dating-theme/
├── theme.json                  # manifest
├── composer.json               # distribution only (type: "magna-theme")
├── screenshot.png              # 1200×900, shown in the store & admin
├── tokens.json                 # design tokens
├── views/
│   ├── layout.blade.php        # <html> shell: header, footer, meta
│   ├── templates/              # page templates
│   │   ├── default.blade.php
│   │   ├── landing.blade.php
│   │   └── profile-detail.blade.php
│   └── blocks/                 # one view per block this theme styles
│       ├── hero.blade.php
│       ├── profile-grid.blade.php
│       └── match-cta.blade.php
├── assets/
│   ├── css/                    # compiled Tailwind output
│   └── img/
└── demo/
    └── content.json            # optional starter content (pages, entries)
```

---

## 3. The manifest — `theme.json`

```json
{
    "name": "acme/dating-theme",
    "displayName": "Amora",
    "description": "A warm, modern theme for dating platforms.",
    "version": "1.0.0",
    "author": "Acme Inc",
    "license": "MIT",
    "compat": {
        "magna-pages": "^1.0"
    },
    "pairsWith": ["acme/dating"],
    "blocks": ["hero", "profile-grid", "match-cta", "text", "image", "cta"],
    "templates": ["default", "landing", "profile-detail"]
}
```

- `pairsWith` lists plugins this theme is designed for. The store surfaces the pairing both ways ("Works with: Dating Platform"), and the admin offers to install the pair together.
- `blocks` declares which block views the theme provides. Any block *not* listed falls back to the block's own default view (shipped by core or by the plugin that defined it) — so a theme never breaks a page by not knowing a block.

---

## 4. Design tokens — `tokens.json`

Tokens are the theme's customization surface. The admin renders a **Theme Options** panel from this file automatically — no code needed — and editors' changes are stored per site, surviving theme updates.

```json
{
    "colors": {
        "primary":   { "value": "#e11d48", "label": "Primary" },
        "secondary": { "value": "#0f172a", "label": "Secondary" },
        "surface":   { "value": "#ffffff", "label": "Surface" }
    },
    "typography": {
        "headingFont": { "value": "Fraunces", "options": ["Fraunces", "Inter", "Lora"] },
        "bodyFont":    { "value": "Inter" },
        "baseSize":    { "value": "16px" }
    },
    "layout": {
        "maxWidth":  { "value": "1200px" },
        "radius":    { "value": "0.75rem", "label": "Corner roundness" }
    }
}
```

Tokens are exposed to views as CSS custom properties (`var(--color-primary)`, `var(--radius)`), injected by Magna Pages into the layout. Build your Tailwind config on top of these variables so one token change restyles the whole site.

---

## 5. Block views

Each block view receives a typed, read-only view model — the block's fields, already resolved (media URLs generated, relations loaded, rich text pre-rendered):

```blade
{{-- views/blocks/profile-grid.blade.php --}}
<section class="mx-auto grid gap-6" style="max-width: var(--max-width)">
    @if($block->heading)
        <h2 class="font-heading text-3xl">{{ $block->heading }}</h2>
    @endif

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach($block->profiles as $profile)
            <a href="{{ $profile->url }}" class="rounded-[var(--radius)] overflow-hidden">
                <img src="{{ $profile->photos->first()?->url('card') }}"
                     alt="{{ $profile->display_name }}">
                <h3>{{ $profile->display_name }}, {{ $profile->age }}</h3>
            </a>
        @endforeach
    </div>
</section>
```

Rules:

- Views receive data; they never query. `$block->profiles` was resolved by the *plugin's* block definition (its query logic), not by the theme.
- Escape everything by default (`{{ }}`); `{!! !!}` is permitted only for core-rendered rich text.
- Every view must render acceptably with missing optional fields.

---

## 6. Templates and layout

`layout.blade.php` is the HTML shell (head, meta/SEO slots, header, footer, token CSS injection). Templates compose the editable areas:

```blade
{{-- views/templates/landing.blade.php --}}
<x-magna::layout :page="$page">
    <x-magna::blocks :blocks="$page->blocks" />
</x-magna::layout>
```

Editors pick a template per page in the admin; `templates` in the manifest controls what's offered.

---

## 7. Pairing with a plugin

The theme provides *styled views* for blocks a plugin *defines*. The contract between them is only the block's field schema — the theme never touches the plugin's PHP.

Dating example:

| | Dating **plugin** (`acme/dating`) | Dating **theme** (`acme/dating-theme`) |
|---|---|---|
| Content types | Profile, Match | — |
| Blocks (schema + logic + fallback view) | profile-grid, match-cta | — |
| Styled block views | — | profile-grid, match-cta, hero, cta |
| Page templates | — | landing, profile-detail |
| Demo content | — | starter pages wired to the blocks |

Install both → a working, styled dating site. Swap the theme → same data, new look. Remove the theme → fallback views keep every page rendering.

---

## 8. Demo content

`demo/content.json` may ship starter pages and sample entries. On activation the admin offers "Install demo content" — imported as *draft* entries, clearly labeled, delete-in-one-click. Never auto-publish demo content.

---

## 9. Preview, testing, publishing

- `php artisan magna:theme:make acme/dating-theme` scaffolds the structure; `magna:theme:check` validates the manifest, verifies every declared block view exists, renders each view against generated fixture data (catching missing-field crashes), and confirms the no-PHP-logic rule.
- Screenshot required; the store auto-generates live previews from your demo content.
- Distribution is Composer, same as plugins (`type: "magna-theme"`). Paid themes use the private license-authenticated Composer repo.
- Versioning: token *additions* are minor; removing a token or a block view is major.
