<?php

declare(strict_types=1);

namespace Magna\Content\FieldTypes;

use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Component;
use Illuminate\Database\Schema\Blueprint;
use Magna\Content\Field;

class BlocksField extends FieldType
{
    public function typeName(): string
    {
        return 'blocks';
    }

    public function isJsonColumn(): bool
    {
        return true;
    }

    public function isRelationOnly(): bool
    {
        return false;
    }

    public function addColumn(Blueprint $table, string $column): void
    {
        $table->json($column)->nullable();
    }

    /** @return list<string> */
    public function validationRules(): array
    {
        return ['array'];
    }

    public function cast(): ?string
    {
        return 'array';
    }

    public function toFilamentComponent(Field $field): Component
    {
        // Structured block editor is implemented in Stage 11.
        return Placeholder::make($field->handle)
            ->label(ucwords(str_replace('_', ' ', $field->handle)))
            ->content('Block editor — available in Stage 11')
            ->columnSpanFull();
    }
}
