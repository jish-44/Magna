<?php

declare(strict_types=1);

namespace Magna\Admin\Pages;

use Filament\Widgets\Widget;
use Magna\Admin\Widgets\EntryCounts;
use Magna\Admin\Widgets\RecentActivity;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = -1;

    /** @return list<class-string<Widget>> */
    public function getWidgets(): array
    {
        return [
            EntryCounts::class,
            RecentActivity::class,
        ];
    }

    /** @return list<class-string<Widget>> */
    public function getVisibleWidgets(): array
    {
        return $this->filterVisibleWidgets($this->getWidgets());
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
