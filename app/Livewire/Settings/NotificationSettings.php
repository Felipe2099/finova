<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Filament\Forms;
use Livewire\Component;
use Illuminate\Contracts\View\View;

final class NotificationSettings extends Component implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Telegram Notifications')
                    ->schema([
                        Forms\Components\TextInput::make('telegram_bot_token')
                            ->label('Bot Token')
                            ->required(),
                        Forms\Components\TextInput::make('telegram_chat_id')
                            ->label('Chat ID')
                            ->required(),
                        Forms\Components\Toggle::make('telegram_enabled')
                            ->label('Enable Telegram Notifications')
                            ->default(true),
                    ]),
                Forms\Components\Section::make('SMS Notifications')
                    ->schema([
                        Forms\Components\Select::make('sms_provider')
                            ->label('SMS Provider')
                            ->options([
                                'twilio' => 'Twilio',
                                'nexmo' => 'Nexmo',
                            ]),
                        Forms\Components\TextInput::make('sms_api_key')
                            ->label('API Key'),
                        Forms\Components\TextInput::make('sms_api_secret')
                            ->label('API Secret')
                            ->password(),
                        Forms\Components\Toggle::make('sms_enabled')
                            ->label('Enable SMS Notifications')
                            ->default(false),
                    ]),
            ]);
    }
} 