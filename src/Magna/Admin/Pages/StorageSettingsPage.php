<?php

declare(strict_types=1);

namespace Magna\Admin\Pages;

use Filament\Actions\Action;
use Filament\Forms\ComponentContainer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Magna\Settings\StorageSettings;

/**
 * @property ComponentContainer $form
 */
class StorageSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    // Hidden from the sidebar: consolidated into the unified SettingsPage.
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Storage Settings';

    protected static ?string $title = 'Storage Settings';

    protected static ?int $navigationSort = 30;

    protected string $view = 'magna::admin.storage-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public function mount(): void
    {
        $settings = StorageSettings::get();

        $this->form->fill([
            'disk' => $settings->disk,
            's3_key' => $settings->s3_key,
            // s3_secret is marked #[Secret] — never pre-fill; show placeholder.
            's3_secret' => null,
            's3_bucket' => $settings->s3_bucket,
            's3_region' => $settings->s3_region,
            's3_url' => $settings->s3_url,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Select::make('disk')
                    ->label('Storage driver')
                    ->options([
                        'local' => 'Local filesystem',
                        'public' => 'Public (local, web-accessible)',
                        's3' => 'Amazon S3',
                        's3-like' => 'S3-compatible (R2, MinIO, etc.)',
                    ])
                    ->required()
                    ->live(),

                TextInput::make('s3_key')
                    ->label('S3 access key')
                    ->maxLength(255)
                    ->nullable(),

                TextInput::make('s3_secret')
                    ->label('S3 secret key')
                    ->password()
                    ->nullable()
                    ->placeholder('[secret — leave blank to keep current]')
                    ->helperText('Leave blank to keep the existing secret unchanged.'),

                TextInput::make('s3_bucket')
                    ->label('S3 bucket')
                    ->maxLength(255)
                    ->nullable(),

                TextInput::make('s3_region')
                    ->label('S3 region')
                    ->maxLength(100)
                    ->nullable()
                    ->placeholder('us-east-1'),

                TextInput::make('s3_url')
                    ->label('S3 endpoint URL')
                    ->url()
                    ->maxLength(500)
                    ->nullable()
                    ->helperText('Leave blank for AWS S3. Set for S3-compatible services (R2, MinIO).'),
            ]);
    }

    public function save(): void
    {
        /** @var array{disk: string, s3_key: ?string, s3_secret: ?string, s3_bucket: ?string, s3_region: ?string, s3_url: ?string} $data */
        $data = $this->form->getState();

        $settings = StorageSettings::get();
        $settings->disk = $data['disk'];
        $settings->s3_key = $data['s3_key'] ?: null;
        $settings->s3_bucket = $data['s3_bucket'] ?: null;
        $settings->s3_region = $data['s3_region'] ?: null;
        $settings->s3_url = $data['s3_url'] ?: null;

        // Only overwrite the secret if the user supplied a new value.
        if (filled($data['s3_secret'])) {
            $settings->s3_secret = $data['s3_secret'];
        }

        $settings->save();

        Notification::make()
            ->title('Storage settings saved.')
            ->success()
            ->send();
    }

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save settings')
                ->action(fn () => $this->save()),
        ];
    }
}
