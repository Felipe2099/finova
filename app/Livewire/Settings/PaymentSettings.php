<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\Setting;
use Filament\Forms;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Form;
use Filament\Notifications\Notification;

final class PaymentSettings extends Component implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    // Form state için
    public array $data = [];

    // Toggle durumları için
    public bool $paytr_enabled = false;
    public bool $stripe_enabled = false;
    public bool $iyzico_enabled = false;

    public function mount(): void
    {
        $settings = $this->getSettings();
        
        // Toggle durumlarını ayarla
        $this->paytr_enabled = filter_var($settings['paytr_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->stripe_enabled = filter_var($settings['stripe_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->iyzico_enabled = filter_var($settings['iyzico_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        // Form verilerini doldur
        $this->form->fill([
            'data' => $settings
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Payment Settings')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('PayTR')
                            ->schema([
                                Forms\Components\Toggle::make('data.paytr_enabled')
                                    ->label('Enable PayTR')
                                    ->default(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state) {
                                        $this->paytr_enabled = $state;
                                    }),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('data.paytr_merchant_id')
                                            ->label('Merchant ID')
                                            ->required(fn () => $this->paytr_enabled),
                                        Forms\Components\TextInput::make('data.paytr_merchant_key')
                                            ->label('Merchant Key')
                                            ->required(fn () => $this->paytr_enabled)
                                            ->password(),
                                        Forms\Components\TextInput::make('data.paytr_merchant_salt')
                                            ->label('Merchant Salt')
                                            ->required(fn () => $this->paytr_enabled)
                                            ->password(),
                                        Forms\Components\Toggle::make('data.paytr_test_mode')
                                            ->label('Test Mode')
                                            ->default(false),
                                    ]),
                            ]),
                        Forms\Components\Tabs\Tab::make('Stripe')
                            ->schema([
                                Forms\Components\Toggle::make('data.stripe_enabled')
                                    ->label('Enable Stripe')
                                    ->default(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state) {
                                        $this->stripe_enabled = $state;
                                    }),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('data.stripe_key')
                                            ->label('Public Key')
                                            ->required(fn () => $this->stripe_enabled),
                                        Forms\Components\TextInput::make('data.stripe_secret')
                                            ->label('Secret Key')
                                            ->required(fn () => $this->stripe_enabled)
                                            ->password(),
                                        Forms\Components\Toggle::make('data.stripe_test_mode')
                                            ->label('Test Mode')
                                            ->default(false),
                                    ]),
                            ]),
                        Forms\Components\Tabs\Tab::make('Iyzico')
                            ->schema([
                                Forms\Components\Toggle::make('data.iyzico_enabled')
                                    ->label('Enable Iyzico')
                                    ->default(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state) {
                                        $this->iyzico_enabled = $state;
                                    }),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('data.iyzico_api_key')
                                            ->label('API Key')
                                            ->required(fn () => $this->iyzico_enabled),
                                        Forms\Components\TextInput::make('data.iyzico_secret_key')
                                            ->label('Secret Key')
                                            ->required(fn () => $this->iyzico_enabled)
                                            ->password(),
                                        Forms\Components\Toggle::make('data.iyzico_test_mode')
                                            ->label('Test Mode')
                                            ->default(false),
                                    ]),
                            ]),
                    ])
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState()['data'];

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'group' => 'payment',
                    'value' => $value,
                    'type' => is_bool($value) ? 'boolean' : 'text',
                ]
            );
        }

        Notification::make()
            ->title('Payment settings saved successfully')
            ->success()
            ->send();
    }

    protected function getSettings(): array
    {
        return Setting::where('group', 'payment')
            ->pluck('value', 'key')
            ->toArray();
    }

    public function render(): View
    {
        return view('livewire.settings.payment');
    }
} 