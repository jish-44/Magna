<?php

declare(strict_types=1);

namespace Magna\Admin\Resources;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Magna\Admin\Resources\Media\CreateMedia;
use Magna\Admin\Resources\Media\EditMedia;
use Magna\Admin\Resources\Media\ListMedia;
use Magna\Media\Media;
use Magna\Media\MediaFolder;

class MediaResource extends \Filament\Resources\Resource
{
    protected static ?string $model = Media::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'original_filename';

    /** @return string[] */
    public static function getGloballySearchableAttributes(): array
    {
        return ['original_filename', 'title', 'alt'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('alt')
                ->label('Alt text')
                ->maxLength(255)
                ->helperText('Describe the image for screen readers and search engines.'),

            TextInput::make('title')
                ->label('Title')
                ->maxLength(255),

            Select::make('folder_id')
                ->label('Folder')
                ->options(
                    fn (): array => MediaFolder::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all(),
                )
                ->searchable()
                ->nullable()
                ->placeholder('No folder'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('original_filename')
                    ->label('Filename')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                TextColumn::make('mime_type')
                    ->label('Type')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('size')
                    ->label('Size')
                    ->sortable()
                    ->formatStateUsing(static function (int $state): string {
                        if ($state >= 1_048_576) {
                            return number_format($state / 1_048_576, 2).' MB';
                        }

                        return number_format($state / 1_024, 1).' KB';
                    }),

                TextColumn::make('folder.name')
                    ->label('Folder')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /** @return array<string, class-string> */
    public static function getPages(): array
    {
        return [
            'index' => ListMedia::route('/'),
            'create' => CreateMedia::route('/create'),
            'edit' => EditMedia::route('/{record}/edit'),
        ];
    }
}
