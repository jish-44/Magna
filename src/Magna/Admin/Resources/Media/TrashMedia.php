<?php

declare(strict_types=1);

namespace Magna\Admin\Resources\Media;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Magna\Admin\Resources\MediaResource;
use Magna\Media\Media;

class TrashMedia extends ListRecords
{
    protected static string $resource = MediaResource::class;

    protected static ?string $title = 'Recycle Bin';

    public function getBreadcrumb(): string
    {
        return 'Recycle Bin';
    }

    protected function getTableQuery(): Builder
    {
        return Media::onlyTrashed();
    }

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
