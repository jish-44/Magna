<?php

declare(strict_types=1);

namespace Magna\Admin\Resources\Media;

use Filament\Resources\Pages\CreateRecord;
use Magna\Admin\Resources\MediaResource;

class CreateMedia extends CreateRecord
{
    protected static string $resource = MediaResource::class;
}
