<?php

declare(strict_types=1);

namespace Magna\Content\FieldTypes;

use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Component;
use Illuminate\Database\Schema\Blueprint;
use Magna\Content\Field;

class MediaField extends FieldType
{
    public function typeName(): string
    {
        return 'media';
    }

    public function isJsonColumn(): bool
    {
        return $this->boolOption('multiple');
    }

    public function isRelationOnly(): bool
    {
        return false;
    }

    public function addColumn(Blueprint $table, string $column): void
    {
        if ($this->boolOption('multiple')) {
            $table->json($column)->nullable();
        } else {
            $table->char($column, 26)->nullable();
        }
    }

    /** @return list<string> */
    public function validationRules(): array
    {
        if ($this->boolOption('multiple')) {
            return ['array'];
        }

        return ['string', 'size:26'];
    }

    public function cast(): ?string
    {
        return $this->boolOption('multiple') ? 'array' : null;
    }

    public function toFilamentComponent(Field $field): Component
    {
        // Full media picker component is implemented in Stage 11.
        // For now, surface a placeholder that explains the intent.
        return Placeholder::make($field->handle)
            ->label(ucwords(str_replace('_', ' ', $field->handle)))
            ->content('Media picker — available in Stage 11');
    }
}
