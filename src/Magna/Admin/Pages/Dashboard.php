<?php

declare(strict_types=1);

namespace Magna\Admin\Pages;

use Filament\Widgets\Widget;
use Magna\Admin\Widgets\EntryCounts;
use Magna\Admin\Widgets\RecentActivity;
use Magna\Users\User;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = -1;

    protected string $view = 'magna::admin.dashboard';

    /** @return list<class-string<Widget>> */
    public function getWidgets(): array
    {
        $all = [
            EntryCounts::class,
            RecentActivity::class,
        ];

        /** @var User|null $user */
        $user = auth()->user();
        $savedOrder = $user?->widget_order ?? [];

        if (empty($savedOrder)) {
            return $all;
        }

        // Re-order by saved preference; append any newly added widgets at the end.
        $indexed = collect($all)->keyBy(fn (string $class): string => class_basename($class));

        $sorted = collect($savedOrder)
            ->map(fn (string $key): ?string => $indexed->get($key))
            ->filter()
            ->values()
            ->all();

        $missing = array_values(
            array_filter($all, fn (string $class): bool => ! in_array(class_basename($class), $savedOrder, true))
        );

        return array_merge($sorted, $missing);
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

    /**
     * Called by the dashboard blade after the user drags widgets into a new order.
     *
     * @param  list<string>  $order  Array of class_basename keys, e.g. ['RecentActivity', 'EntryCounts']
     */
    public function reorderWidgets(array $order): void
    {
        /** @var User|null $user */
        $user = auth()->user();
        $user?->update(['widget_order' => $order]);
    }
}
