<?php

declare(strict_types=1);

namespace Magna\Admin;

use Filament\Enums\ThemeMode;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Magna\Admin\Pages\ContentTypeBuilder;
use Magna\Admin\Pages\Dashboard;
use Magna\Admin\Pages\GeneralSettingsPage;
use Magna\Admin\Pages\MailSettingsPage;
use Magna\Admin\Pages\StorageSettingsPage;
use Magna\Admin\Resources\AuditLogResource;
use Magna\Admin\Resources\EntryResource;
use Magna\Admin\Resources\MediaResource;
use Magna\Admin\Resources\RoleResource;
use Magna\Admin\Resources\UserResource;
use Magna\Admin\Widgets\EntryCounts;
use Magna\Admin\Widgets\RecentActivity;
use Magna\Auth\Http\Middleware\EnsureTwoFactorAuthenticated;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('magna')
            ->path('admin')
            // ── Design Guide §9.1: navy-tinted color palette ─────────────────
            ->colors([
                'primary' => Color::hex('#7c3aed'),
                'info' => Color::hex('#0ea5e9'),
                'success' => Color::hex('#10b981'),
                'warning' => Color::hex('#f59e0b'),
                'danger' => Color::hex('#f43f5e'),
                // Re-skin: navy-tinted gray drives every Filament surface.
                'gray' => [
                    50 => '#f8fafc',
                    100 => '#f1f5f9',
                    200 => '#e2e8f0',
                    300 => '#cbd5e1',
                    400 => '#94a3b8',
                    500 => '#64748b',
                    600 => '#475569',
                    700 => '#334155',
                    800 => '#1e293b',
                    900 => '#141b2d',
                    950 => '#0b0f19',
                ],
            ])
            // ── Design Guide §9.1: dark is the designed experience ────────────
            ->defaultThemeMode(ThemeMode::Dark)
            ->darkMode(true)
            // ── Design Guide §3: Inter self-hosted via theme.css @font-face ──
            ->font('Inter')
            // ── Design Guide §9.2: Vite theme with glass panels / scrollbars ─
            ->viteTheme('resources/css/filament/magna/theme.css')
            // ── Auth: web guard + Stage 2 two-factor enforcement ─────────────
            ->authGuard('web')
            ->authMiddleware([EnsureTwoFactorAuthenticated::class])
            // ── Layout ───────────────────────────────────────────────────────
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->brandName('Magna')
            // ── Global search (entries by title) ─────────────────────────────
            ->globalSearch()
            // ── Resources ────────────────────────────────────────────────────
            ->resources([
                EntryResource::class,
                MediaResource::class,
                UserResource::class,
                RoleResource::class,
                AuditLogResource::class,
            ])
            // ── Custom pages ─────────────────────────────────────────────────
            ->pages([
                Dashboard::class,
                ContentTypeBuilder::class,
                GeneralSettingsPage::class,
                MailSettingsPage::class,
                StorageSettingsPage::class,
            ])
            // ── Widgets ──────────────────────────────────────────────────────
            ->widgets([
                EntryCounts::class,
                RecentActivity::class,
            ]);
    }
}
