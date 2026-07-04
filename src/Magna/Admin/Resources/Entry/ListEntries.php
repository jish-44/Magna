<?php

declare(strict_types=1);

namespace Magna\Admin\Resources\Entry;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Url;
use Magna\Admin\Resources\EntryResource;
use Magna\Content\SchemaRegistry;

class ListEntries extends ListRecords
{
    protected static string $resource = EntryResource::class;

    #[Url(as: 'type')]
    public string $type = '';

    public function getTitle(): string|Htmlable
    {
        /** @var SchemaRegistry $registry */
        $registry = app(SchemaRegistry::class);
        $contentType = $registry->get($this->type);

        return $contentType ? $contentType->displayName : 'Content';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->url(EntryResource::getUrl('create', ['type' => $this->type])),
        ];
    }
}
