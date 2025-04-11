<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Component;
use Illuminate\Contracts\View\View;

final class TelegramSettings extends Component implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::where('group', 'telegram')
                           ->pluck('value', 'key')
                           ->toArray();

        // Varsayılan değeri ayarla (eğer veritabanında yoksa)
        if (!isset($settings['telegram_enabled'])) {
             $settings['telegram_enabled'] = false; // Varsayılan olarak 'Hayır'
        } else {
             $settings['telegram_enabled'] = filter_var($settings['telegram_enabled'], FILTER_VALIDATE_BOOLEAN);
        }

        $this->form->fill(['data' => $settings]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Telegram Yapılandırması')
                    ->schema([
                        Forms\Components\Select::make('data.telegram_enabled')
                            ->label('Telegram Bildirimlerini Etkinleştir')
                            ->options([
                                true => 'Evet',
                                false => 'Hayır',
                            ])
                            ->native(false)
                            ->required()
                            ->live()
                            ->default(false),
                        Forms\Components\TextInput::make('data.telegram_bot_token')
                            ->label('Bot Token')
                            ->required(fn (Forms\Get $get): bool => (bool) $get('data.telegram_enabled'))
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('data.telegram_enabled')),
                        Forms\Components\TextInput::make('data.telegram_chat_id')
                            ->label('Chat ID') // Etiket zaten Türkçeye yakın
                            ->required(fn (Forms\Get $get): bool => (bool) $get('data.telegram_enabled'))
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('data.telegram_enabled')),
                    ])->columns(1), // Tek sütunlu görünüm
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState()['data'];

        // Select'ten gelen 'telegram_enabled' değerini boolean'a çevir
        if (isset($data['telegram_enabled'])) {
            $data['telegram_enabled'] = filter_var($data['telegram_enabled'], FILTER_VALIDATE_BOOLEAN);
        }

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key, 'group' => 'telegram'],
                [
                    'value' => $value,
                    'type' => is_bool($value) ? 'boolean' : 'text',
                    'is_translatable' => false
                ]
            );
        }

        Notification::make()
            ->title('Telegram ayarları başarıyla kaydedildi') // Mesaj Türkçeleştirildi
            ->success()
            ->send();
    }

    public function render(): View
    {
        return view('livewire.settings.generic-settings-view');
    }
}