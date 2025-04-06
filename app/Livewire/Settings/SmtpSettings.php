<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Filament\Forms;
use Livewire\Component;
use Illuminate\Contracts\View\View;

final class SmtpSettings extends Component implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('SMTP Configuration')
                    ->description('Configure email sending settings')
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
            ]);
    }
} 