<?php

declare(strict_types=1);

namespace Magna\Admin\Pages;

use Filament\Actions\Action;
use Filament\Forms\ComponentContainer;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Magna\Settings\GeneralSettings;

/**
 * @property ComponentContainer $form
 */
class GeneralSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'General Settings';

    protected static ?int $navigationSort = 10;

    protected string $view = 'magna::admin.general-settings';

    public string $site_name = '';

    public string $default_locale = 'en';

    public bool $registration_enabled = false;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public function mount(): void
    {
        $settings = GeneralSettings::get();

        $this->form->fill([
            'site_name' => $settings->site_name,
            'default_locale' => $settings->default_locale,
            'registration_enabled' => $settings->registration_enabled,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('site_name')
                    ->label('Site name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('default_locale')
                    ->label('Default locale')
                    ->required()
                    ->maxLength(10)
                    ->placeholder('en'),

                Toggle::make('registration_enabled')
                    ->label('Allow public registration')
                    ->helperText('When disabled, only admins can create new user accounts.')
                    ->inline(false),
            ]);
    }

    public function save(): void
    {
        /** @var array{site_name: string, default_locale: string, registration_enabled: bool} $data */
        $data = $this->form->getState();

        $settings = GeneralSettings::get();
        $settings->site_name = $data['site_name'];
        $settings->default_locale = $data['default_locale'];
        $settings->registration_enabled = $data['registration_enabled'];
        $settings->save();

        Notification::make()
            ->title('General settings saved.')
            ->success()
            ->send();
    }

    /** @return array<int, Action> */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save settings')
                ->submit('save'),
        ];
    }
}
