<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Component;
use Illuminate\Contracts\View\View;

final class NotificationSettings extends Component implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::where('group', 'notification')->pluck('value', 'key')->toArray();
        $booleanKeys = [
            'notify_credit_card_statement', // Kredi Kartı Ekstre Bildirimi
            'notify_loan_payment', // Kredi Ödeme Bildirimi
            'notify_recurring_payment', // Tekrarlayan Ödeme Bildirimi
            'notify_debt_receivable', // Borç & Alacak Bildirimi
        ];
        foreach ($booleanKeys as $key) {
            if (!isset($settings[$key])) {
                $settings[$key] = false;
            } else {
                $settings[$key] = filter_var($settings[$key], FILTER_VALIDATE_BOOLEAN);
            }
        }
        $this->form->fill(['data' => $settings]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Bildirim Ayarları')
                    ->description('Telegram ile gönderilecek bildirim tiplerini seçin')
                    ->schema([
                        Forms\Components\Select::make('data.notify_credit_card_statement')
                            ->label('Kredi Kartı Ekstre Bildirimi')
                            ->helperText('Kredi kartı ekstre tarihlerinden önce bildirim gönderilir')
                            ->options([
                                true => 'Aktif',
                                false => 'Pasif',
                            ])
                            ->native(false)
                            ->required(),
                            
                        Forms\Components\Select::make('data.notify_loan_payment')
                            ->label('Kredi Ödeme Bildirimi')
                            ->helperText('Yaklaşan kredi ödemeleri için bildirim gönderilir')
                            ->options([
                                true => 'Aktif',
                                false => 'Pasif',
                            ])
                            ->native(false)
                            ->required(),
                            
                        Forms\Components\Select::make('data.notify_recurring_payment')
                            ->label('Tekrarlayan Ödeme Bildirimi')
                            ->helperText('Devamlı işlem/abonelikler için bildirim gönderilir')
                            ->options([
                                true => 'Aktif',
                                false => 'Pasif',
                            ])
                            ->native(false)
                            ->required(),
                            
                        Forms\Components\Select::make('data.notify_debt_receivable')
                            ->label('Borç & Alacak Bildirimi')
                            ->helperText('Yaklaşan borç ve alacak ödemeleri için bildirim gönderilir')
                            ->options([
                                true => 'Aktif',
                                false => 'Pasif',
                            ])
                            ->native(false)
                            ->required(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState()['data'];

        foreach ($data as $key => $value) {
            // Select'ten gelen değer string 'true'/'false' olabilir, boolean'a çevir
            $processedValue = filter_var($value, FILTER_VALIDATE_BOOLEAN);

            Setting::updateOrCreate(
                ['key' => $key, 'group' => 'notification'],
                [
                    'value' => $processedValue,
                    'type' => 'boolean',
                    'is_translatable' => false
                ]
            );
        }

        Notification::make()
            ->title('Bildirim ayarları başarıyla kaydedildi')
            ->success()
            ->send();
    }

    public function render(): View
    {
        return view('livewire.settings.generic-settings-view');
    }
}