<?php

declare(strict_types=1);

namespace Magna\Admin\Resources\User;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Magna\Admin\Resources\UserResource;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
