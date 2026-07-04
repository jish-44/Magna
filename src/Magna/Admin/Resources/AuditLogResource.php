<?php

declare(strict_types=1);

namespace Magna\Admin\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Magna\Admin\Resources\AuditLog\ListAuditLogs;
use Magna\Audit\AuditLog;

class AuditLogResource extends \Filament\Resources\Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'action';

    // Audit log is read-only — no create, edit, or delete.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        // No form — read-only resource.
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('action')
                    ->label('Action')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('actor_id')
                    ->label('Actor ID')
                    ->searchable()
                    ->fontFamily('mono')
                    ->placeholder('—')
                    ->limit(20)
                    ->tooltip(fn (?string $state): ?string => $state),

                TextColumn::make('subject_type')
                    ->label('Subject type')
                    ->searchable()
                    ->formatStateUsing(
                        fn (?string $state): string => $state !== null
                            ? class_basename($state)
                            : '—',
                    ),

                TextColumn::make('subject_id')
                    ->label('Subject ID')
                    ->searchable()
                    ->fontFamily('mono')
                    ->placeholder('—')
                    ->limit(20)
                    ->tooltip(fn (?string $state): ?string => $state),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Action')
                    ->options(
                        fn (): array => AuditLog::query()
                            ->select('action')
                            ->distinct()
                            ->orderBy('action')
                            ->pluck('action', 'action')
                            ->all(),
                    ),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordAction(null);
    }

    /** @return array<string, class-string> */
    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
    }
}
