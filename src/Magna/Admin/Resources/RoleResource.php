<?php

declare(strict_types=1);

namespace Magna\Admin\Resources;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Magna\Admin\Resources\Role\ManageRoles;
use Magna\Auth\Role;

class RoleResource extends \Filament\Resources\Resource
{
    protected static ?string $model = Role::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|\UnitEnum|null $navigationGroup = 'Access';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('roles.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('roles.manage') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('roles.manage') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('roles.manage') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('handle')
                ->label('Handle')
                ->required()
                ->maxLength(100)
                ->alphaDash()
                ->unique(ignoreRecord: true)
                ->helperText('Lowercase slug used in code, e.g. "content_editor".'),

            TextInput::make('name')
                ->label('Display name')
                ->required()
                ->maxLength(255),

            Textarea::make('description')
                ->label('Description')
                ->rows(2)
                ->maxLength(1000),

            Toggle::make('is_super_admin')
                ->label('Super admin')
                ->helperText('Super admins bypass all permission checks.')
                ->inline(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('handle')
                    ->sortable()
                    ->searchable()
                    ->fontFamily('mono'),

                BadgeColumn::make('is_super_admin')
                    ->label('Super admin')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No')
                    ->colors([
                        'danger' => true,
                        'gray' => false,
                    ]),

                TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->sortable(),

                TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions')
                    ->sortable(),
            ])
            ->defaultSort('name');
    }

    /** @return array<string, class-string> */
    public static function getPages(): array
    {
        return [
            'index' => ManageRoles::route('/'),
        ];
    }
}
