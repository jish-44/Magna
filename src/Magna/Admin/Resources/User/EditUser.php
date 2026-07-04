<?php

declare(strict_types=1);

namespace Magna\Admin\Resources\User;

use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Magna\Admin\Resources\UserResource;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
