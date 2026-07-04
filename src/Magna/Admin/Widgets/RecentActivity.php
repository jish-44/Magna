<?php

declare(strict_types=1);

namespace Magna\Admin\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Magna\Audit\AuditLog;

class RecentActivity extends TableWidget
{
    protected static ?int $sort = 2;

    public $tableRecordsPerPage = 10;

    protected static ?string $heading = 'Recent Activity';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => AuditLog::query()
                    ->latest('created_at')
                    ->limit(10),
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->color('info'),

                TextColumn::make('actor_id')
                    ->label('Actor')
                    ->fontFamily('mono')
                    ->placeholder('system')
                    ->limit(16)
                    ->tooltip(fn (?string $state): ?string => $state),
            ])
            ->paginated(false)
            ->defaultSort('created_at', 'desc');
    }
}
