<?php

declare(strict_types=1);

namespace Magna\Admin\Resources\Media;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Magna\Admin\Resources\MediaResource;

class ListMedia extends ListRecords
{
    protected static string $resource = MediaResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Upload media')
                ->icon('heroicon-m-arrow-up-tray'),
        ];
    }
}
