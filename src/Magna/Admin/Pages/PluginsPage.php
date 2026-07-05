<?php

declare(strict_types=1);

namespace Magna\Admin\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Magna\Plugins\Exceptions\PluginCompatibilityException;
use Magna\Plugins\PluginInfo;
use Magna\Plugins\PluginManager;
use Magna\Plugins\PluginRecord;
use Throwable;

class PluginsPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Plugins';

    protected static ?int $navigationSort = 40;

    protected string $view = 'magna::admin.plugins';

    // ── Raw data ──────────────────────────────────────────────────────────────

    /** @var list<array<string, mixed>> */
    public array $installed = [];

    /** @var list<array<string, mixed>> */
    public array $available = [];

    // ── UI state ──────────────────────────────────────────────────────────────

    public string $activeTab = 'installed';

    public string $searchInstalled = '';

    public string $searchAvailable = '';

    public string $statusFilter = 'all';

    /** @var list<string> */
    public array $selectedPlugins = [];

    public string $bulkAction = '';

    // Confirmation target for modal actions
    public ?string $pendingPluginName = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public function mount(): void
    {
        $this->refreshPlugins();
    }

    // Suppress Filament's default h1 — we render our own page header in the blade.
    public function getHeading(): string|Htmlable
    {
        return '';
    }

    // ── View data (called by Filament on every Livewire render) ──────────────

    protected function getViewData(): array
    {
        $search = strtolower(trim($this->searchInstalled));

        $filteredInstalled = array_values(array_filter(
            $this->installed,
            function (array $p) use ($search): bool {
                $statusOk = match ($this->statusFilter) {
                    'active' => (bool) $p['enabled'],
                    'inactive' => ! (bool) $p['enabled'],
                    'update' => $p['update_version'] !== null,
                    default => true,
                };
                if (! $statusOk) {
                    return false;
                }
                if ($search === '') {
                    return true;
                }

                return str_contains(strtolower($p['display_name']), $search)
                    || str_contains(strtolower($p['description']), $search)
                    || str_contains(strtolower($p['author']), $search);
            }
        ));

        $searchAvail = strtolower(trim($this->searchAvailable));
        $filteredAvailable = $searchAvail === '' ? $this->available : array_values(array_filter(
            $this->available,
            fn (array $p): bool => str_contains(strtolower($p['display_name']), $searchAvail)
                || str_contains(strtolower($p['description']), $searchAvail)
                || str_contains(strtolower($p['author']), $searchAvail)
        ));

        $counts = [
            'all' => count($this->installed),
            'active' => count(array_filter($this->installed, fn ($p) => (bool) $p['enabled'])),
            'inactive' => count(array_filter($this->installed, fn ($p) => ! (bool) $p['enabled'])),
            'update' => count(array_filter($this->installed, fn ($p) => $p['update_version'] !== null)),
        ];

        $filteredNames = array_column($filteredInstalled, 'name');
        $allSelected = $filteredNames !== []
            && count(array_intersect($this->selectedPlugins, $filteredNames)) === count($filteredNames);

        return compact('filteredInstalled', 'filteredAvailable', 'counts', 'filteredNames', 'allSelected');
    }

    // ── Tab / filter controls ─────────────────────────────────────────────────

    public function setTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['installed', 'addnew'], true) ? $tab : 'installed';
        $this->selectedPlugins = [];
    }

    public function setStatusFilter(string $filter): void
    {
        $this->statusFilter = in_array($filter, ['all', 'active', 'inactive', 'update'], true) ? $filter : 'all';
        $this->selectedPlugins = [];
    }

    public function toggleSelectAll(): void
    {
        $filteredNames = $this->currentFilteredNames();
        $allSelected = $filteredNames !== []
            && count(array_intersect($this->selectedPlugins, $filteredNames)) === count($filteredNames);

        if ($allSelected) {
            $this->selectedPlugins = array_values(array_diff($this->selectedPlugins, $filteredNames));
        } else {
            $this->selectedPlugins = array_values(
                array_unique([...$this->selectedPlugins, ...$filteredNames])
            );
        }
    }

    public function applyBulkAction(): void
    {
        if ($this->bulkAction === '' || $this->selectedPlugins === []) {
            return;
        }

        $action = $this->bulkAction;
        $names = $this->selectedPlugins;
        $count = count($names);

        foreach ($names as $name) {
            try {
                match ($action) {
                    'activate' => app(PluginManager::class)->enable($name),
                    'deactivate' => app(PluginManager::class)->disable($name),
                    'delete' => app(PluginManager::class)->uninstall($name),
                    default => null,
                };
            } catch (Throwable) {
                // Continue with the rest even if one fails
            }
        }

        $label = match ($action) {
            'activate' => 'enabled',
            'deactivate' => 'disabled',
            'delete' => 'uninstalled',
            default => 'processed',
        };

        Notification::make()->title("{$count} plugin(s) {$label}.")->success()->send();
        $url = static::getUrl();
        $this->js('setTimeout(function(){ window.location.replace('.json_encode($url).'); }, 400)');
    }

    // ── Direct (non-confirmatory) plugin actions ───────────────────────────────

    public function enable(string $name): void
    {
        try {
            app(PluginManager::class)->enable($name);
            Notification::make()->title('Plugin enabled.')->success()->send();
            $url = static::getUrl();
            $this->js('setTimeout(function(){ window.location.replace('.json_encode($url).'); }, 400)');
        } catch (PluginCompatibilityException $e) {
            Notification::make()->title('Incompatible plugin')->body($e->getMessage())->danger()->send();
        } catch (Throwable $e) {
            Notification::make()->title('Failed to enable plugin')->body($e->getMessage())->danger()->send();
        }
    }

    public function disable(string $name): void
    {
        try {
            app(PluginManager::class)->disable($name);
            Notification::make()->title('Plugin disabled.')->success()->send();
            $url = static::getUrl();
            $this->js('setTimeout(function(){ window.location.replace('.json_encode($url).'); }, 400)');
        } catch (Throwable $e) {
            Notification::make()->title('Failed to disable plugin')->body($e->getMessage())->danger()->send();
        }
    }

    public function update(string $name): void
    {
        try {
            // Re-enable syncs the version from the manifest and re-runs any new migrations.
            app(PluginManager::class)->enable($name);
            Notification::make()->title('Plugin updated.')->success()->send();
            $url = static::getUrl();
            $this->js('setTimeout(function(){ window.location.replace('.json_encode($url).'); }, 400)');
        } catch (Throwable $e) {
            Notification::make()->title('Update failed')->body($e->getMessage())->danger()->send();
        }
    }

    // ── Confirmation-gated actions ─────────────────────────────────────────────

    public function requestInstall(string $name): void
    {
        $this->pendingPluginName = $name;
        $this->mountAction('install');
    }

    public function requestUninstall(string $name): void
    {
        $this->pendingPluginName = $name;
        $this->mountAction('uninstall');
    }

    public function requestPurge(string $name): void
    {
        $this->pendingPluginName = $name;
        $this->mountAction('purge');
    }

    // ── Filament action definitions ────────────────────────────────────────────

    public function installAction(): Action
    {
        return Action::make('install')
            ->requiresConfirmation()
            ->modalHeading('Install plugin')
            ->modalDescription("Runs the plugin's database migrations and enables it immediately.")
            ->modalSubmitActionLabel('Install & enable')
            ->color('success')
            ->action(function (): void {
                try {
                    app(PluginManager::class)->enable($this->pendingPluginName ?? '');
                    Notification::make()->title('Plugin installed and enabled.')->success()->send();
                    $url = static::getUrl();
                    $this->js('setTimeout(function(){ window.location.replace('.json_encode($url).'); }, 400)');
                } catch (Throwable $e) {
                    Notification::make()->title('Installation failed')->body($e->getMessage())->danger()->send();
                } finally {
                    $this->pendingPluginName = null;
                }
            });
    }

    public function uninstallAction(): Action
    {
        return Action::make('uninstall')
            ->requiresConfirmation()
            ->modalHeading('Uninstall plugin')
            ->modalDescription('The plugin record will be removed. Database tables are preserved.')
            ->modalSubmitActionLabel('Uninstall')
            ->color('danger')
            ->action(function (): void {
                try {
                    app(PluginManager::class)->uninstall($this->pendingPluginName ?? '');
                    Notification::make()->title('Plugin uninstalled.')->success()->send();
                    $url = static::getUrl();
                    $this->js('setTimeout(function(){ window.location.replace('.json_encode($url).'); }, 400)');
                } catch (Throwable $e) {
                    Notification::make()->title('Uninstall failed')->body($e->getMessage())->danger()->send();
                } finally {
                    $this->pendingPluginName = null;
                }
            });
    }

    public function purgeAction(): Action
    {
        return Action::make('purge')
            ->requiresConfirmation()
            ->modalHeading('Purge plugin data')
            ->modalDescription('Removes the plugin record AND drops its database tables. This cannot be undone.')
            ->modalSubmitActionLabel('Delete everything')
            ->color('danger')
            ->action(function (): void {
                try {
                    app(PluginManager::class)->uninstall($this->pendingPluginName ?? '', purge: true);
                    Notification::make()->title('Plugin purged.')->success()->send();
                    $url = static::getUrl();
                    $this->js('setTimeout(function(){ window.location.replace('.json_encode($url).'); }, 400)');
                } catch (Throwable $e) {
                    Notification::make()->title('Purge failed')->body($e->getMessage())->danger()->send();
                } finally {
                    $this->pendingPluginName = null;
                }
            });
    }

    // ── Data refresh ──────────────────────────────────────────────────────────

    public function refreshPlugins(): void
    {
        $records = PluginRecord::query()->orderBy('display_name')->get();
        $installedNames = $records->pluck('name')->all();

        // Map discovered plugin versions for update detection
        $discoveredVersions = collect(app(PluginManager::class)->discover())
            ->keyBy(fn (PluginInfo $info): string => $info->manifest->name)
            ->map(fn (PluginInfo $info): string => $info->manifest->version)
            ->all();

        $this->installed = $records->map(fn (PluginRecord $r): array => [
            'name' => $r->name,
            'display_name' => $r->display_name,
            'version' => $r->version,
            'enabled' => $r->enabled,
            'description' => is_array($r->manifest) ? (string) ($r->manifest['description'] ?? '') : '',
            'author' => is_array($r->manifest) ? (string) ($r->manifest['author'] ?? '') : '',
            'source' => str_contains(str_replace('\\', '/', (string) $r->base_path), '/plugins-dev/')
                ? 'plugins-dev/'
                : 'Composer',
            'update_version' => isset($discoveredVersions[$r->name]) && $discoveredVersions[$r->name] !== $r->version
                ? $discoveredVersions[$r->name]
                : null,
        ])->values()->all();

        $this->available = collect(app(PluginManager::class)->discover())
            ->reject(fn (PluginInfo $info): bool => in_array($info->manifest->name, $installedNames, true))
            ->map(fn (PluginInfo $info): array => [
                'name' => $info->manifest->name,
                'display_name' => $info->manifest->displayName,
                'version' => $info->manifest->version,
                'description' => $info->manifest->description,
                'author' => $info->manifest->author,
                'source' => str_contains(str_replace('\\', '/', $info->basePath), '/plugins-dev/')
                    ? 'plugins-dev/'
                    : 'Composer',
            ])->values()->all();
    }

    // ── Internal helpers ──────────────────────────────────────────────────────

    /** Returns the names of plugins currently visible in the filtered installed table. */
    private function currentFilteredNames(): array
    {
        $q = strtolower(trim($this->searchInstalled));

        return array_column(
            array_filter($this->installed, function (array $p) use ($q): bool {
                $statusOk = match ($this->statusFilter) {
                    'active' => (bool) $p['enabled'],
                    'inactive' => ! (bool) $p['enabled'],
                    'update' => $p['update_version'] !== null,
                    default => true,
                };

                if (! $statusOk || $q === '') {
                    return $statusOk;
                }

                return str_contains(strtolower($p['display_name']), $q)
                    || str_contains(strtolower($p['description']), $q)
                    || str_contains(strtolower($p['author']), $q);
            }),
            'name'
        );
    }
}
