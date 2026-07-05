# Magna CMS — Plugin Development Guide

This guide reflects the **live implementation** as of Magna v1.x. Every API shown here exists in the codebase and works today. Where a feature is not yet wired (contracts that are defined but not fully active), it is explicitly marked.

---

## Table of Contents

1. [What a plugin is](#1-what-a-plugin-is)
2. [How discovery works](#2-how-discovery-works)
3. [Plugin structure](#3-plugin-structure)
4. [The manifest — magna.json](#4-the-manifest--magnajson)
5. [composer.json](#5-composerjson)
6. [The entry class and lifecycle](#6-the-entry-class-and-lifecycle)
7. [Contracts — what a plugin can provide](#7-contracts--what-a-plugin-can-provide)
8. [API routes](#8-api-routes)
9. [Database migrations](#9-database-migrations)
10. [Content type schemas](#10-content-type-schemas)
11. [Permissions](#11-permissions)
12. [Step-by-step: build a real plugin](#12-step-by-step-build-a-real-plugin)
13. [Installation workflow](#13-installation-workflow)
14. [Disable and uninstall](#14-disable-and-uninstall)
15. [Contracts roadmap](#15-contracts-roadmap)

---

## 1. What a plugin is

A Magna plugin is an ordinary **Composer package** with a `magna.json` manifest file. There is no ZIP upload, no special archive format, no custom installer. If Composer can require it, Magna can discover and run it.

A plugin can provide any combination of:

- **API routes** — exposed under `/api/v1/{plugin-slug}/`
- **Database migrations** — run automatically when the plugin is enabled
- **Content type schemas** — JSON files that define content types in the CMS
- **Admin sidebar navigation** — groups and items added to the Filament panel
- **Dashboard widgets** — cards on the admin dashboard
- **Settings pages** — pages under the admin Settings section
- **Custom permissions** — gates declared in the manifest and granted by admins

A plugin must **not**: write to Magna's core tables, override or inherit from core classes directly, or depend on another plugin's internal implementation (only on shared contracts in `Magna\Contracts`).

---

## 2. How discovery works

`PluginDiscovery` scans two sources on every request:

| Source | How it works |
|---|---|
| `vendor/composer/installed.json` | Any Composer package with `"type": "magna-plugin"` and a `magna.json` file in its root |
| `plugins-dev/{vendor}/{package}/magna.json` | Local development plugins in the project's `plugins-dev/` folder |

If the same plugin name appears in both, `plugins-dev/` takes precedence (so you can develop a plugin locally against the same name as a published one).

Discovery only tells Magna a plugin *exists*. It does not activate it. Activation requires an admin to click "Install & enable" in the admin panel, which writes a record to the `plugins` table and runs migrations.

---

## 3. Plugin structure

```
your-vendor/your-plugin/
├── magna.json                  ← required: Magna manifest
├── composer.json               ← required: Composer package metadata
├── src/
│   └── YourPlugin.php          ← required: entry class (extends Plugin)
├── routes/
│   └── api.php                 ← optional: API routes
├── database/
│   └── migrations/             ← optional: DB migrations (run on install)
└── schemas/
    └── your-type.json          ← optional: content type definitions
```

None of the optional folders are required. A minimal plugin only needs `magna.json`, `composer.json`, and the entry class.

---

## 4. The manifest — `magna.json`

The manifest is the single source of truth. Magna validates it before enabling a plugin, and the admin displays its contents to the administrator before installation.

```json
{
    "name": "acme/crm",
    "displayName": "CRM",
    "description": "Contact management, pipeline, and activity log.",
    "version": "1.0.0",
    "author": "Acme Inc",
    "license": "MIT",
    "compat": {
        "magna": "^1.0",
        "php": "^8.3"
    },
    "entry": "Acme\\Crm\\CrmPlugin",
    "provides": {
        "apiRoutes": true,
        "adminNavigation": true
    },
    "permissions": [
        "crm.contacts.view",
        "crm.contacts.manage",
        "crm.pipeline.view"
    ],
    "uninstall": {
        "tables": ["crm_contacts", "crm_activities"],
        "contentTypes": []
    }
}
```

### Field reference

| Field | Required | Description |
|---|---|---|
| `name` | Yes | Must match the Composer package name exactly. Format: `vendor/package`. |
| `displayName` | Yes | Human-readable name shown in the admin panel. |
| `description` | Yes | One or two sentence description of what the plugin does. |
| `version` | Yes | Semver string (`1.0.0`). |
| `author` | Yes | Person or organisation name. |
| `license` | Yes | SPDX license identifier, e.g. `MIT`, `GPL-3.0`. |
| `compat.magna` | Yes | Semver constraint for compatible Magna versions (e.g. `^1.0`). |
| `compat.php` | Yes | Semver constraint for compatible PHP versions (e.g. `^8.3`). |
| `entry` | Yes | Fully-qualified class name of your entry class. |
| `provides` | No | Object describing what the plugin contributes. Informational only — shown in admin. |
| `permissions` | No | Array of permission strings this plugin registers. |
| `uninstall.tables` | No | Tables to drop when "Purge data" is chosen. If empty, tables are preserved on uninstall. |
| `uninstall.contentTypes` | No | Content type handles to remove on purge. |

**Compat checking**: Magna reads `compat.magna` and refuses to enable a plugin whose constraint does not satisfy the running core version. Always set this honestly.

---

## 5. `composer.json`

The Composer file handles autoloading. The only Magna-specific requirement is `"type": "magna-plugin"` — this is how `PluginDiscovery` identifies Magna plugins inside `vendor/`.

```json
{
    "name": "acme/crm",
    "description": "CRM plugin for Magna CMS.",
    "type": "magna-plugin",
    "require": {
        "php": "^8.3"
    },
    "autoload": {
        "psr-4": {
            "Acme\\Crm\\": "src/"
        }
    }
}
```

You may add any Composer dependencies in `require`. They are pulled in normally when `composer require acme/crm` is run.

---

## 6. The entry class and lifecycle

Every plugin needs an entry class that extends `Magna\Plugins\Plugin`. All methods are optional — only override the ones you need.

```php
<?php

declare(strict_types=1);

namespace Acme\Crm;

use Magna\Plugins\Plugin;

class CrmPlugin extends Plugin
{
    public function register(): void
    {
        // Bind your services into the container.
        // Called once per request, before boot().
        // Do NOT produce side effects here (no DB queries, no file writes).
        $this->app->singleton(ContactService::class);
    }

    public function boot(): void
    {
        // Called on every request, after all plugins have registered.
        // Load event listeners, publish translations, etc.
    }

    public function enable(): void
    {
        // Called ONCE when an admin enables the plugin.
        // Migrations already ran before this is called.
        // Use this for one-time setup: seed config, warm a cache, etc.
    }

    public function disable(): void
    {
        // Called ONCE when an admin disables the plugin.
        // Routes and nav are unregistered automatically.
        // Use this to clear caches or release held resources.
    }
}
```

### The request lifecycle

On every HTTP request, enabled plugins go through this sequence:

```
PluginManager::bootEnabledPlugins()
  ├── For each enabled plugin: plugin->register()     ← bind services
  ├── For each enabled plugin: plugin->boot()         ← activate behaviour
  ├── For each enabled plugin: loadRoutes()           ← register API routes
  └── For each enabled plugin: registerPermissions()  ← gates available
```

`register()` runs on all plugins first, then `boot()` runs on all — so when your `boot()` runs, other plugins' services are already in the container.

### Available properties

Inside any method, your entry class has access to:

```php
$this->app          // Illuminate\Contracts\Foundation\Application
$this->basePath     // absolute path to your plugin's root directory
$this->manifest     // Magna\Plugins\Manifest — your magna.json as an object
```

Helper methods from the base class:

```php
$this->routesPath()         // "{basePath}/routes/api.php"
$this->routesPath('web.php') // "{basePath}/routes/web.php"
$this->migrationsPath()     // "{basePath}/database/migrations"
```

---

## 7. Contracts — what a plugin can provide

Plugins extend Magna by implementing **interfaces from `Magna\Contracts`**. Each contract is a small, focused interface. You implement only the ones that match what your plugin needs.

### `RegistersAdminNavigation`

Adds a collapsible group to the admin sidebar.

```php
use Magna\Contracts\RegistersAdminNavigation;
use Magna\Admin\Nav\NavGroup;
use Magna\Admin\Nav\NavItem;

class CrmPlugin extends Plugin implements RegistersAdminNavigation
{
    public function adminNavigation(): NavGroup
    {
        return NavGroup::make('CRM', icon: 'heroicon-o-briefcase')
            ->items([
                NavItem::page('Contacts', route: 'crm.contacts.index')
                    ->can('crm.contacts.view'),

                NavItem::page('Pipeline', route: 'crm.pipeline.index')
                    ->can('crm.pipeline.view'),
            ]);
    }
}
```

**`NavGroup::make(label, icon)`** — `icon` is a Heroicons name (e.g. `heroicon-o-briefcase`). Defaults to `puzzle-piece`.

**`NavItem::page(label, route: '...')`** — links to a named Laravel route. The route must exist when the panel renders (your `routes/api.php` or a Filament page's auto-generated route).

**`NavItem::resource(ClassName::class)`** — links to a Filament Resource's index page (for when your plugin ships Filament Resources).

**`->can('permission.string')`** — hides this item from users who do not hold the specified permission.

---

### `RegistersDashboardWidgets`

Returns Filament widget class names to inject into the admin dashboard.

```php
use Magna\Contracts\RegistersDashboardWidgets;

class CrmPlugin extends Plugin implements RegistersDashboardWidgets
{
    public function dashboardWidgets(): array
    {
        return [
            \Acme\Crm\Widgets\ContactsOverviewWidget::class,
            \Acme\Crm\Widgets\RecentActivityWidget::class,
        ];
    }
}
```

Each class must be a valid Filament widget. The core injects them into the dashboard widget list automatically.

---

### `RegistersSettingsPages`

Returns Filament page class names to inject under the Settings section of the admin panel.

```php
use Magna\Contracts\RegistersSettingsPages;

class CrmPlugin extends Plugin implements RegistersSettingsPages
{
    public function settingsPages(): array
    {
        return [
            \Acme\Crm\Pages\CrmSettingsPage::class,
        ];
    }
}
```

Each class must be a valid Filament `Page` class. The core adds them to the panel page list automatically.

---

### Implementing multiple contracts

A plugin can implement any number of contracts at once:

```php
class CrmPlugin extends Plugin
    implements RegistersAdminNavigation,
               RegistersDashboardWidgets,
               RegistersSettingsPages
{
    // ...all three methods...
}
```

---

## 8. API routes

Create `routes/api.php` in your plugin. The core loads this file and prefixes it with `api/v1/{plugin-slug}`, where the slug is the last segment of your plugin name (`acme/crm` → slug is `crm`).

```php
<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

// Accessible at: GET /api/v1/crm/contacts
Route::middleware(['magna.api', 'can:crm.contacts.view'])
    ->get('/contacts', function (): JsonResponse {
        return response()->json(['data' => []]);
    })
    ->name('crm.contacts.index');

// Accessible at: POST /api/v1/crm/contacts
Route::middleware(['magna.api', 'can:crm.contacts.manage'])
    ->post('/contacts', [ContactController::class, 'store'])
    ->name('crm.contacts.store');
```

### Important notes

- **`magna.api` middleware** — handles bearer token authentication via Sanctum. Always include it on any authenticated endpoint.
- **Route names** — use the pattern `{plugin-slug}.{resource}.{action}` to avoid conflicts with core and other plugins. These names are also what you pass to `NavItem::page(label, route: '...')`.
- **Route registration timing** — routes are loaded during `PluginManager::bootEnabledPlugins()`, before Filament renders. If you reference a route name in `adminNavigation()`, it will be available.

---

## 9. Database migrations

Place standard Laravel migration files in `database/migrations/`. They are run automatically by `php artisan migrate` when the admin installs the plugin (before your `enable()` method is called).

```
database/
└── migrations/
    ├── 2024_01_01_000000_create_crm_contacts_table.php
    └── 2024_01_01_000001_create_crm_activities_table.php
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_contacts', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_contacts');
    }
};
```

### Ownership rule

Plugins own their own tables. Never add columns to Magna's core tables (`users`, `entries`, `media`, etc.) via a plugin migration. If you need to associate your data with a core model, use a foreign key in your own table.

### Uninstall and purge

In `magna.json`, list every table your plugin creates under `uninstall.tables`:

```json
"uninstall": {
    "tables": ["crm_contacts", "crm_activities"]
}
```

- **Uninstall** (without purge): disables the plugin and deletes its record from the `plugins` table. Your tables are left intact so data is not lost.
- **Purge**: same as uninstall, but also drops every table listed in `uninstall.tables`. This is irreversible. The admin must confirm in a separate modal.

---

## 10. Content type schemas

Place JSON schema files in `schemas/`. These are loaded into Magna's `SchemaRegistry` when the plugin boots. Content types defined here appear in the Content Type Builder and have entries queryable via the delivery API.

```json
{
    "handle": "contact",
    "displayName": "Contact",
    "localizable": false,
    "draftable": false,
    "fields": [
        { "handle": "name",       "type": "text",   "required": true },
        { "handle": "email",      "type": "email" },
        { "handle": "phone",      "type": "text" },
        { "handle": "company",    "type": "text" },
        { "handle": "notes",      "type": "textarea" },
        { "handle": "tags",       "type": "select", "multiple": true,
          "options": ["lead", "customer", "partner"] }
    ]
}
```

Each file in `schemas/` is treated as one content type. The `handle` must be unique across the CMS.

---

## 11. Permissions

Declare all permissions your plugin uses in `magna.json`:

```json
"permissions": [
    "crm.contacts.view",
    "crm.contacts.manage",
    "crm.pipeline.view"
]
```

These are registered as Laravel gates when the plugin boots. Use them everywhere:

```php
// In a route middleware:
Route::middleware(['magna.api', 'can:crm.contacts.view'])

// In a controller or service:
$this->authorize('crm.contacts.manage');

// In a Blade view:
@can('crm.contacts.view')

// In a NavItem:
NavItem::page('Contacts', route: 'crm.contacts.index')
    ->can('crm.contacts.view')
```

Admins grant permissions to roles through the admin panel under **Settings → Roles & permissions**.

### Naming convention

Use the pattern `{plugin-slug}.{resource}.{action}`. This keeps permissions clearly namespaced and prevents collisions with core permissions and other plugins.

---

## 12. Step-by-step: build a real plugin

This walkthrough builds a minimal "Announcements" plugin that adds an API endpoint and an admin nav link.

### Step 1 — Create the folder

```
plugins-dev/
└── acme/
    └── announcements/
        ├── magna.json
        ├── composer.json
        └── src/
            └── AnnouncementsPlugin.php
```

### Step 2 — Write `magna.json`

```json
{
    "name": "acme/announcements",
    "displayName": "Announcements",
    "description": "Publish site-wide announcements via API.",
    "version": "1.0.0",
    "author": "Acme Inc",
    "license": "MIT",
    "compat": {
        "magna": "^1.0",
        "php": "^8.3"
    },
    "entry": "Acme\\Announcements\\AnnouncementsPlugin",
    "provides": {
        "apiRoutes": true,
        "adminNavigation": true
    },
    "permissions": [
        "announcements.view",
        "announcements.manage"
    ],
    "uninstall": {
        "tables": ["announcements"],
        "contentTypes": []
    }
}
```

### Step 3 — Write `composer.json`

```json
{
    "name": "acme/announcements",
    "description": "Announcements plugin for Magna CMS.",
    "type": "magna-plugin",
    "require": {
        "php": "^8.3"
    },
    "autoload": {
        "psr-4": {
            "Acme\\Announcements\\": "src/"
        }
    }
}
```

### Step 4 — Write the entry class

```php
<?php

declare(strict_types=1);

namespace Acme\Announcements;

use Magna\Admin\Nav\NavGroup;
use Magna\Admin\Nav\NavItem;
use Magna\Contracts\RegistersAdminNavigation;
use Magna\Plugins\Plugin;

class AnnouncementsPlugin extends Plugin implements RegistersAdminNavigation
{
    public function adminNavigation(): NavGroup
    {
        return NavGroup::make('Announcements', icon: 'heroicon-o-megaphone')
            ->items([
                NavItem::page('All Announcements', route: 'announcements.index')
                    ->can('announcements.view'),
            ]);
    }
}
```

### Step 5 — Add a migration

Create `database/migrations/2024_01_01_000000_create_announcements_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
```

### Step 6 — Add an API route

Create `routes/api.php`:

```php
<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// GET /api/v1/announcements
Route::middleware(['magna.api', 'can:announcements.view'])
    ->get('/', function (): JsonResponse {
        $rows = DB::table('announcements')
            ->where('active', true)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $rows]);
    })
    ->name('announcements.index');
```

### Step 7 — Register a path repository in the CMS `composer.json`

Open the root `composer.json` of the Magna installation and add your plugin to the `repositories` array:

```json
"repositories": [
    { "type": "path", "url": "plugins-dev/acme/announcements" }
]
```

### Step 8 — Require the plugin via Composer

```bash
composer require acme/announcements:@dev
```

Composer resolves the path repository, symlinks the package into `vendor/`, and adds it to `vendor/composer/installed.json` with `"type": "magna-plugin"`.

### Step 9 — Install via the admin

1. Open the admin panel → **Plugins** (under System in the sidebar).
2. Your plugin appears in **"Available to Install"**.
3. Click **"Install & enable"** and confirm.
4. Magna runs your migrations, calls `enable()`, and registers routes and nav.

The "Announcements" group appears in the sidebar immediately.

---

## 13. Installation workflow

There are two paths depending on whether the plugin is in development or published.

### Development (path repository)

Use this while building the plugin locally.

```
1. Create the plugin in plugins-dev/your-vendor/your-plugin/
2. Add the path repo to the CMS composer.json
3. composer require your-vendor/your-plugin:@dev
4. Admin → Plugins → Install & enable
```

The `@dev` constraint tells Composer to use the minimum-stability `dev` and link to the local path. Changes you make to the plugin's PHP files are reflected immediately (no re-requiring needed — it's a symlink).

### Published (Packagist or private registry)

Use this to distribute your plugin to other Magna installations.

```
1. Tag a release on GitHub/GitLab
2. Publish to Packagist (public) or a private Composer repository
3. On the target Magna installation: composer require your-vendor/your-plugin
4. Admin → Plugins → Install & enable
```

No path repository entry is needed. `PluginDiscovery` finds the plugin via `vendor/composer/installed.json`.

---

## 14. Disable and uninstall

### Disable

Disabling a plugin makes it inactive for every request: its routes are not registered, its nav group disappears, its services are not bound. The `plugins` table record remains with `enabled = false`. **All data is preserved.**

Re-enabling later calls `enable()` again and re-registers everything.

### Uninstall

Uninstalling deletes the `plugins` table record entirely. Data tables are untouched — your `crm_contacts` table still exists with all its rows.

### Purge

Purge = uninstall + drop every table listed in `uninstall.tables`. This is the nuclear option. The admin must confirm in a separate modal that explicitly warns about data loss.

After any of these operations, Composer still has the package installed in `vendor/`. The plugin will reappear in "Available to Install" until you `composer remove` it.

---

## 15. Contracts roadmap

The following contracts are **defined and have their interfaces in `Magna\Contracts`**, but are not yet wired to their final runtime behaviour. Plan your plugin with these in mind — the interfaces will not change, only the wiring will be added in future core releases.

| Contract | Status | Purpose |
|---|---|---|
| `RegistersAdminNavigation` | **Live** | Admin sidebar nav group |
| `RegistersDashboardWidgets` | **Live** | Admin dashboard widget cards |
| `RegistersSettingsPages` | **Live** | Admin settings section pages |
| `RegistersBlocks` | Defined, not wired | Editor and Magna Pages blocks |
| `ExtendsEntryForm` | Defined, not wired | Add fields/tabs to a content type's edit form |
| `FiltersApiQuery` | Defined, not wired | Scope or transform delivery API queries |
| `RegistersWebhookEvents` | Defined, not wired | Custom webhook event types |

"Defined, not wired" means the interface exists at `src/Magna/Contracts/` and implementing it will not cause errors — the core just won't call the method yet. When the wiring lands in a future minor release, your already-implementing plugin will gain the behaviour automatically.
