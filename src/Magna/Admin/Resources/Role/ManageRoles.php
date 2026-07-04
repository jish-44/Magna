<?php

declare(strict_types=1);

namespace Magna\Admin\Resources\Role;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Magna\Admin\Resources\RoleResource;

class ManageRoles extends ManageRecords
{
    protected static string $resource = RoleResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => auth()->user()?->can('roles.manage') ?? false),
        ];
    }
}
