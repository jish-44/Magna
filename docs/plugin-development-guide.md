# Magna CMS — Plugin Development Guide

**Status: Specification v0.1 (pre-implementation).**
This guide is written *before* the core exists, on purpose: it is the contract the core must be built to satisfy. Every API shown here is a commitment. When core code and this guide disagree, one of them is a bug.

---

## 1. What a plugin is

A Magna plugin is an **ordinary Composer package** with a `magna.json` manifest. There is no custom archive format, no ZIP upload, no special installer. If you can write a Laravel package, you can write a Magna plugin.

A plugin may provide any combination of:

- Content types (schema files)
- Blocks (for the editor and Magna Pages)
- API routes
- Admin pages (Filament resources/pages)
- Permissions
- Settings pages
- Event listeners and scheduled jobs
- Its own database tables

A plugin may **not**: modify core tables, override core classes, or depend on another plugin's internals (only on its published contracts).

---

## 2. Prerequisites

- PHP 8.3+, Composer 2
- A running Magna instance (Laravel 12+)
- Familiarity with Laravel service providers and migrations

---

## 3. Scaffolding

```bash
php artisan magna:plugin:make acme/dating
```

This generates a package skeleton in `plugins-dev/acme/dating` (a path repository wired into the app's `composer.json` for local development):

```
acme/dating/
├── magna.json               # Magna manifest (identity, compat, provides)
├── composer.json            # standard Composer metadata + autoload
├── README.md
├── src/
│   ├── DatingPlugin.php     # entry class — extends Magna\Plugin\Plugin
│   ├── Models/
│   ├── Policies/
│   └── Http/Controllers/
├── routes/
│   └── api.php
├── database/
│   └── migrations/
├── schemas/                 # content type definitions this plugin ships
│   ├── profile.json
│   └── match.json
├── blocks/                  # block definitions (see §9)
│   ├── profile-grid/
│   └── match-cta/
├── resources/
│   └── admin/               # Filament resources & pages
└── tests/
```

---

## 4. The manifest — `magna.json`

The manifest is the single source of truth about what a plugin is and does. The admin displays `permissions` and `provides` to the administrator **before** enabling — like app permissions on a phone.

```json
{
    "name": "acme/dating",
    "displayName": "Dating Platform",
    "description": "Profiles, matching, and messaging content types.",
    "version": "1.2.0",
    "author": "Acme Inc",
    "license": "MIT",
    "compat": {
        "magna": "^1.0",
        "php": "^8.3"
    },
    "entry": "Acme\\Dating\\DatingPlugin",
    "provides": {
        "contentTypes": ["profile", "match"],
        "blocks": ["profile-grid", "match-cta"],
        "settingsPages": ["dating"],
        "adminNavigation": true,
        "apiRoutes": true
    },
    "permissions": [
        "dating.profiles.view",
        "dating.profiles.manage",
        "dating.matches.view"
    ],
    "uninstall": {
        "tables": ["dating_swipes"],
        "contentTypes": ["profile", "match"]
    }
}
```

Rules:

- `name` must equal the Composer package name.
- `compat.magna` uses Composer semver constraints; the core refuses to enable an incompatible plugin.
- `uninstall` declares everything the plugin leaves behind. `magna:plugin:uninstall --purge` removes it; without `--purge`, data is preserved.

---

## 5. The entry class and lifecycle

```php
namespace Acme\Dating;

use Magna\Plugin\Plugin;

class DatingPlugin extends Plugin
{
    public function register(): void
    {
        // bind services into the container — no side effects
    }

    public function boot(): void
    {
        // runs on every request when the plugin is enabled
    }

    public function enable(): void
    {
        // one-time: runs when the admin enables the plugin
        // migrations and schema sync run automatically before this
    }

    public function disable(): void
    {
        // one-time: routes/menus are unregistered automatically; clean up caches here
    }
}
```

Lifecycle: `composer require` → manifest validation → compat check → **Enable** (migrations → schema sync → `enable()` → permission/menu/route registration) → Ready. Disable reverses registration without touching data.

---

## 6. Typed hook contracts

Plugins extend Magna by implementing **interfaces**, never by string-keyed filters. All contracts live in `Magna\Contracts\*` and are **semver-guaranteed from 1.0** — they will not break within a major version.

```php
use Magna\Contracts\RegistersAdminNavigation;
use Magna\Admin\Nav\NavGroup;
use Magna\Admin\Nav\NavItem;

class DatingPlugin extends Plugin implements RegistersAdminNavigation
{
    public function adminNavigation(): NavGroup
    {
        return NavGroup::make('Dating', icon: 'heart')->items([
            NavItem::resource(ProfileResource::class),
            NavItem::resource(MatchResource::class),
            NavItem::page('Settings', route: 'dating.settings')
                ->can('dating.settings.manage'),
        ]);
    }
}
```

Initial contract set (each is a small, documented interface):

| Contract | Purpose |
|---|---|
| `RegistersAdminNavigation` | sidebar groups and items |
| `RegistersDashboardWidgets` | admin dashboard cards |
| `RegistersSettingsPages` | pages under Settings |
| `RegistersBlocks` | editor/Pages blocks |
| `ExtendsEntryForm` | add fields/tabs to a content type's edit form |
| `FiltersApiQuery` | scope or extend delivery API queries |
| `RegistersWebhookEvents` | custom webhook event types |

---

## 7. Content types (schema as code)

Plugins ship content types as JSON schema files in `schemas/`. On enable, the core diffs and syncs them (generating real tables and migrations — see the report's Database section).

```json
{
    "handle": "profile",
    "displayName": "Profile",
    "localizable": false,
    "draftable": true,
    "fields": [
        { "handle": "display_name", "type": "text",   "required": true },
        { "handle": "bio",          "type": "richtext" },
        { "handle": "age",          "type": "number", "min": 18 },
        { "handle": "photos",       "type": "media",  "multiple": true, "max": 6 },
        { "handle": "interests",    "type": "select", "multiple": true,
          "options": ["music", "travel", "food", "sports"] },
        { "handle": "matches",      "type": "relation", "to": "profile", "many": true }
    ]
}
```

Entries are queryable through generated Eloquent models (`Magna\Content\Entry::type('profile')`) and automatically exposed on the delivery API at `/api/v1/content/profile` — with permissions applied.

For domain data that is *not* content (e.g., swipe events), use conventional migrations and your own Eloquent models in `database/migrations` — plugins own their own tables.

---

## 8. Permissions, API routes, settings

**Permissions** are declared in the manifest and referenced everywhere with the standard Laravel gate:
`$user->can('dating.profiles.manage')`. Content-type CRUD permissions (`content.profile.publish`, …) are generated automatically — do not redeclare them.

**API routes** (`routes/api.php`) are auto-prefixed with the plugin's namespace and included in the generated OpenAPI spec:

```php
Route::middleware(['magna.api', 'can:dating.matches.view'])
    ->get('/matches/{profile}', [MatchController::class, 'index']);
// exposed at: /api/v1/dating/matches/{profile}
```

**Settings** use typed settings classes; the core handles storage, caching, and (via `RegistersSettingsPages`) the admin UI:

```php
class DatingSettings extends Magna\Settings\Settings
{
    public int $maxDailySwipes = 50;
    public bool $requirePhotoVerification = true;
}
```

---

## 9. Blocks

A block = schema + edit form + views. Each block lives in `blocks/<handle>/`:

```
blocks/profile-grid/
├── block.json          # fields the editor shows
├── preview.png
└── views/
    └── default.blade.php   # fallback view when the theme provides none
```

```json
{
    "handle": "profile-grid",
    "displayName": "Profile Grid",
    "fields": [
        { "handle": "heading",  "type": "text" },
        { "handle": "limit",    "type": "number", "default": 12 },
        { "handle": "interest", "type": "select", "optionsFrom": "profile.interests" }
    ]
}
```

The block's data is stored as portable JSON in the entry. Rendering is per channel: Magna Pages resolves the view as **theme override → plugin default**; headless frontends receive the block JSON from the API and render their own components. Ship the plugin's `default.blade.php` unstyled-but-decent so the plugin works with any theme.

---

## 10. Events

Subscribe to core and other plugins' events with standard Laravel listeners:

```php
use Magna\Content\Events\EntryPublished;

class NotifyMatchesOnNewProfile
{
    public function handle(EntryPublished $event): void
    {
        if ($event->entry->type === 'profile') { /* ... */ }
    }
}
```

Fire your own events (`Acme\Dating\Events\MatchCreated`) so *other* plugins can build on yours — that's how the ecosystem compounds.

---

## 11. Testing

The SDK ships a test harness that boots a minimal Magna app with your plugin enabled:

```php
use Magna\Testing\PluginTestCase;

class ProfileApiTest extends PluginTestCase
{
    protected string $plugin = 'acme/dating';

    public function test_profiles_are_listed(): void
    {
        Entry::factory()->type('profile')->published()->count(3)->create();

        $this->getJson('/api/v1/content/profile')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }
}
```

CI requirement for the official store: tests pass against the lowest and highest supported core versions.

---

## 12. Versioning rules

- Semver, strictly. Breaking your own plugin's API = major bump.
- Declare core compat honestly (`"magna": "^1.0"`); the store verifies it against CI.
- Never write to another plugin's tables. Never `use` another plugin's non-contract classes.

---

## 13. Publishing to the Official Store

See [store-plan.md](store-plan.md) for the store's phases. Mechanics:

1. Tag a release on your Git host; publish to Packagist (public) or the Magna private Composer repo (paid).
2. Submit the listing (name, description, screenshots, category) to the store.
3. The store validates: manifest well-formed, compat truthful (CI matrix), static analysis clean, `uninstall` section complete.
4. Once listed, installs flow through Composer: `php artisan magna:plugin:install acme/dating`, or one click in the admin (which runs the same flow).

---

## Appendix: field types (initial set)

`text`, `textarea`, `richtext` (portable JSON), `markdown`, `number`, `boolean`, `date`, `datetime`, `select`, `media`, `relation`, `blocks`, `json`, `slug`, `email`, `url`, `color`.

Plugins can register custom field types via `RegistersFieldTypes` (contract lands in core 1.1).
