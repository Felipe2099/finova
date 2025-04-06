<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\Setting;
use Filament\Forms;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Cache;
use Filament\Support\Enums\IconPosition;
use Livewire\Features\SupportFileUploads\WithFileUploads;

final class SettingsManager extends Component implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;
    use WithFileUploads;

    public string $activeTab = 'site';

    public function mount(): void
    {
        $this->form->fill($this->getSettingsForActiveTab());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->statePath('formData');
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->form->fill($this->getSettingsForActiveTab());
    }

    protected function getFormSchema(): array
    {
        return match($this->activeTab) {
            'site' => [
                Forms\Components\Section::make('Site Information')
                    ->description('Basic site information')
                    ->schema([
                        Forms\Components\TextInput::make('site_name')
                            ->label('Site Name')
                            ->required(),
                        Forms\Components\Textarea::make('site_description')
                            ->label('Site Description'),
                        Forms\Components\TextInput::make('site_keywords')
                            ->label('Site Keywords'),
                        Forms\Components\TextInput::make('site_phone')
                            ->label('Site Phone')
                            ->tel(),
                        Forms\Components\FileUpload::make('site_logo')
                            ->label('Site Logo')
                            ->image()
                            ->directory('site'),
                    ])
                    ->columns(2),
            ],
            'social' => [
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Repeater::make('social_links')
                            ->label('Social Links')
                            ->schema([
                                Forms\Components\Select::make('platform')
                                    ->options([
                                        'facebook' => 'Facebook',
                                        'twitter' => 'Twitter',
                                        'instagram' => 'Instagram',
                                        'linkedin' => 'LinkedIn',
                                        'youtube' => 'YouTube',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('url')
                                    ->label('URL')
                                    ->url()
                                    ->required(),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->reorderable()
                            ->collapsible(),
                    ]),
            ],
            'payment' => [
                Forms\Components\Tabs::make('Payment Settings')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Stripe')
                            ->schema([
                                Forms\Components\Toggle::make('stripe_enabled')
                                    ->label('Enable Stripe'),
                                Forms\Components\TextInput::make('stripe_key')
                                    ->label('Public Key'),
                                Forms\Components\TextInput::make('stripe_secret')
                                    ->label('Secret Key')
                                    ->password(),
                                Forms\Components\Toggle::make('stripe_sandbox')
                                    ->label('Sandbox Mode'),
                            ])->columns(2),
                        Forms\Components\Tabs\Tab::make('PayPal')
                            ->schema([
                                Forms\Components\Toggle::make('paypal_enabled')
                                    ->label('Enable PayPal'),
                                Forms\Components\TextInput::make('paypal_client_id')
                                    ->label('Client ID'),
                                Forms\Components\TextInput::make('paypal_secret')
                                    ->label('Secret')
                                    ->password(),
                                Forms\Components\Toggle::make('paypal_sandbox')
                                    ->label('Sandbox Mode'),
                            ])->columns(2),
                        Forms\Components\Tabs\Tab::make('Iyzico')
                            ->schema([
                                Forms\Components\Toggle::make('iyzico_enabled')
                                    ->label('Enable Iyzico'),
                                Forms\Components\TextInput::make('iyzico_api_key')
                                    ->label('API Key'),
                                Forms\Components\TextInput::make('iyzico_secret')
                                    ->label('Secret Key')
                                    ->password(),
                                Forms\Components\Toggle::make('iyzico_sandbox')
                                    ->label('Sandbox Mode'),
                            ])->columns(2),
                    ]),
            ],
            'smtp' => [
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('smtp_host')
                            ->label('SMTP Host')
                            ->required(),
                        Forms\Components\TextInput::make('smtp_port')
                            ->label('SMTP Port')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('smtp_username')
                            ->label('SMTP Username')
                            ->required(),
                        Forms\Components\TextInput::make('smtp_password')
                            ->label('SMTP Password')
                            ->password()
                            ->required(),
                        Forms\Components\Select::make('smtp_encryption')
                            ->label('Encryption')
                            ->options([
                                'tls' => 'TLS',
                                'ssl' => 'SSL',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('mail_from_address')
                            ->label('From Address')
                            ->email()
                            ->required(),
                        Forms\Components\TextInput::make('mail_from_name')
                            ->label('From Name')
                            ->required(),
                    ])
                    ->columns(2),
            ],
            default => [],
        };
    }

    public function render(): View
    {
        return view('livewire.settings.index');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                [
                    'group' => $this->activeTab,
                    'key' => $key,
                ],
                [
                    'value' => $value,
                    'type' => $this->getSettingType($value),
                ]
            );
        }

        Cache::tags('settings')->flush();

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();

        $this->activeTab = 'site';
    }

    protected function getSettingsForActiveTab(): array
    {
        if (!$this->activeTab) {
            return [];
        }

        return Setting::where('group', $this->activeTab)
            ->get()
            ->pluck('value', 'key')
            ->toArray();
    }

    protected function getSettingType($value): string
    {
        return match(true) {
            is_bool($value) => 'boolean',
            is_array($value) => 'json',
            str_contains((string)$value, "\n") => 'textarea',
            default => 'text',
        };
    }
} 