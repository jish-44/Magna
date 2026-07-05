<?php

declare(strict_types=1);

namespace Magna\Admin;

use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;
use Magna\Admin\Pages\ApiSettingsPage;
use Magna\Admin\Pages\ContentSettingsPage;
use Magna\Admin\Pages\ContentTypeBuilder;
use Magna\Admin\Pages\Dashboard;
use Magna\Admin\Pages\GeneralSettingsPage;
use Magna\Admin\Pages\LocalizationSettingsPage;
use Magna\Admin\Pages\MailSettingsPage;
use Magna\Admin\Pages\MediaSettingsPage;
use Magna\Admin\Pages\PluginsPage;
use Magna\Admin\Pages\ProfilePage;
use Magna\Admin\Pages\SecuritySettingsPage;
use Magna\Admin\Pages\SettingsPage;
use Magna\Admin\Pages\StorageSettingsPage;
use Magna\Admin\Pages\SystemInfoPage;
use Magna\Admin\Pages\UrlSettingsPage;
use Magna\Admin\Resources\ApiKeyResource;
use Magna\Admin\Resources\AuditLogResource;
use Magna\Admin\Resources\EntryResource;
use Magna\Admin\Resources\MediaResource;
use Magna\Admin\Resources\RoleResource;
use Magna\Admin\Resources\UserResource;
use Magna\Admin\Widgets\EntryCounts;
use Magna\Admin\Widgets\RecentActivity;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('magna')
            // Root domain: the admin panel lives at "/" — no "/admin" prefix.
            ->path('')
            // ── Design Guide §9.1: navy-tinted color palette ─────────────────
            ->colors([
                'primary' => Color::hex('#7c3aed'),
                'info' => Color::hex('#0ea5e9'),
                'success' => Color::hex('#10b981'),
                'warning' => Color::hex('#f59e0b'),
                'danger' => Color::hex('#f43f5e'),
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
            ->defaultThemeMode(ThemeMode::Dark)
            ->darkMode(true)
            ->font('Inter')
            ->viteTheme('resources/css/filament/magna/theme.css')
            // ── Auth ──────────────────────────────────────────────────────────
            //   authMiddleware is REQUIRED — without it every panel page is
            //   publicly accessible. Authenticate redirects guests to ->login().
            ->authGuard('web')
            ->middleware(['web'])
            ->authMiddleware([Authenticate::class])
            ->login()
            // ── Layout ───────────────────────────────────────────────────────
            //   SPA mode: navigation uses Livewire wire:navigate, so clicking a
            //   sidebar item swaps content client-side instead of a full page
            //   reload — no re-download of CSS/JS, no Alpine re-boot. Filament
            //   also prefetches pages on link hover. This is the biggest single
            //   win for a native-app feel.
            ->spa()
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            // brandName intentionally omitted: the brand logo view already
            // renders the "Magna" wordmark, so setting brandName too would
            // duplicate it on the login header.
            ->brandLogo(fn (): View => view('filament.magna.brand'))
            ->brandLogoHeight('1.75rem')
            ->favicon(asset('favicon.svg'))
            // ── User menu ────────────────────────────────────────────────────
            //   Make the account row (the user's name + avatar at the top of the
            //   menu) link straight to the profile page, with a matching icon so
            //   it reads as a menu item alongside "Sign out".
            ->userMenuItems([
                'account' => MenuItem::make()
                    ->icon('heroicon-o-user-circle')
                    ->url(fn (): string => ProfilePage::getUrl()),
            ])
            // ── Settings submenu ─────────────────────────────────────────────
            //   The unified settings page registers its own "All Settings" item
            //   (navigationGroup 'Settings'); these children jump to each section
            //   anchor on that page.
            ->navigationItems($this->settingsNavigationItems())
            // ── Global search ─────────────────────────────────────────────────
            ->globalSearch()
            // ── Resources ────────────────────────────────────────────────────
            ->resources([
                EntryResource::class,
                MediaResource::class,
                UserResource::class,
                RoleResource::class,
                AuditLogResource::class,
                ApiKeyResource::class,
            ])
            // ── Custom pages ─────────────────────────────────────────────────
            ->pages([
                Dashboard::class,
                ContentTypeBuilder::class,
                // Unified settings page (one scrollable page with a section
                // sub-nav). The individual *SettingsPage classes below stay
                // registered for their routes but are hidden from the sidebar.
                SettingsPage::class,
                GeneralSettingsPage::class,
                UrlSettingsPage::class,
                LocalizationSettingsPage::class,
                ContentSettingsPage::class,
                MailSettingsPage::class,
                StorageSettingsPage::class,
                MediaSettingsPage::class,
                ApiSettingsPage::class,
                SecuritySettingsPage::class,
                SystemInfoPage::class,
                PluginsPage::class,
                ProfilePage::class,
            ])
            // ── Widgets ──────────────────────────────────────────────────────
            ->widgets([
                EntryCounts::class,
                RecentActivity::class,
            ])
            // ── Fix: Alpine $persist uses global localStorage keys ('isOpen',
            //    'isOpenDesktop') shared across all Filament panels on the same
            //    origin. If lovelink's sidebar is collapsed, those keys are 'false'
            //    and magna-cms opens with an icon-only sidebar.
            //
            //    Solution: on the first page-load of each browser session for this
            //    panel, listen for alpine:initialized and directly call
            //    $store.sidebar.open(). This fires AFTER Alpine has read $persist
            //    values but BEFORE the user has interacted, so the reactive x-show
            //    on every sidebar label immediately updates to visible.
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                    <script>
                    (function () {
                        var SESSION_KEY = 'magna_sb_session_v1';
                        if (sessionStorage.getItem(SESSION_KEY)) {
                            return; // respect user's collapsed preference within session
                        }
                        sessionStorage.setItem(SESSION_KEY, '1');
                        // Pre-set localStorage so Alpine $persist reads 'true' on init
                        try {
                            localStorage.setItem('isOpen', 'true');
                            localStorage.setItem('isOpenDesktop', 'true');
                        } catch (e) {}
                        // Belt-and-suspenders: also set via the store after Alpine boots
                        document.addEventListener('alpine:initialized', function () {
                            try {
                                var store = window.Alpine && window.Alpine.store('sidebar');
                                if (store && typeof store.open === 'function') {
                                    store.open();
                                }
                            } catch (e) {}
                        });
                    })();
                    </script>
                HTML),
            )
            // Settings sub-nav: intercept clicks on the section links so they
            // smooth-scroll (Filament's SPA navigation otherwise swallows the
            // anchor), and drive a scroll-spy that moves the active highlight in
            // the sidebar as the user scrolls through sections. Lives in a
            // persistent render hook and re-runs on every Livewire navigation.
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                    <script>
                    (function () {
                        var observer = null;
                        function hrefId(link) {
                            var m = (link.getAttribute('href') || '').match(/#(settings-[\w-]+)/);
                            return m ? m[1] : null;
                        }
                        function setup() {
                            var links = Array.prototype.slice.call(document.querySelectorAll('a[href*="#settings-"]'));
                            links.forEach(function (link) {
                                var id = hrefId(link);
                                if (! id || link.dataset.magnaBound) return;
                                link.dataset.magnaBound = '1';
                                link.addEventListener('click', function (e) {
                                    var el = document.getElementById(id);
                                    if (! el) return; // not on the settings page — let it navigate
                                    e.preventDefault();
                                    e.stopImmediatePropagation();
                                    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                                    history.replaceState(null, '', '#' + id);
                                }, true);
                            });

                            if (observer) { observer.disconnect(); observer = null; }
                            var sections = Array.prototype.slice.call(document.querySelectorAll('[id^="settings-"]'));
                            if (! sections.length) return;

                            var map = {};
                            links.forEach(function (link) { var id = hrefId(link); if (id) map[id] = link; });

                            observer = new IntersectionObserver(function (entries) {
                                entries.forEach(function (entry) {
                                    if (! entry.isIntersecting) return;
                                    Object.keys(map).forEach(function (k) { map[k].classList.remove('magna-nav-active'); });
                                    if (map[entry.target.id]) map[entry.target.id].classList.add('magna-nav-active');
                                });
                            }, { rootMargin: '-20% 0px -70% 0px', threshold: 0 });
                            sections.forEach(function (s) { observer.observe(s); });

                            if (window.location.hash) {
                                var target = document.querySelector(window.location.hash);
                                if (target) requestAnimationFrame(function () { target.scrollIntoView({ behavior: 'smooth', block: 'start' }); });
                            }
                        }
                        document.addEventListener('DOMContentLoaded', setup);
                        document.addEventListener('livewire:navigated', setup);
                    })();
                    </script>
                HTML),
            );
    }

    /**
     * Child items under the "Settings" sidebar group — one per section on the
     * unified settings page. Each jumps to its anchor. isActiveWhen is false so
     * they don't all highlight (they share the /settings path).
     *
     * @return array<int, NavigationItem>
     */
    private function settingsNavigationItems(): array
    {
        $sections = [
            ['general', 'General', 'heroicon-o-cog-6-tooth'],
            ['localization', 'Localization', 'heroicon-o-language'],
            ['content', 'Content', 'heroicon-o-document-text'],
            ['media', 'Media', 'heroicon-o-photo'],
            ['email', 'Email', 'heroicon-o-envelope'],
            ['storage', 'Storage', 'heroicon-o-circle-stack'],
            ['urls', 'URLs & Frontend', 'heroicon-o-link'],
            ['security', 'Security', 'heroicon-o-shield-check'],
        ];

        $items = [];
        foreach ($sections as $i => [$id, $label, $icon]) {
            $items[] = NavigationItem::make($label)
                ->group('Settings')
                ->icon($icon)
                ->sort($i + 1)
                ->url('/settings#settings-'.$id)
                ->isActiveWhen(fn (): bool => false);
        }

        return $items;
    }
}
