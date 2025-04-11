<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Livewire\Features\SupportFileUploads\WithFileUploads; // Dosya yükleme için gerekli

final class SiteSettings extends Component implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;
    use WithFileUploads;

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::where('group', 'site')
                           ->pluck('value', 'key')
                           ->toArray();
        $this->form->fill(['data' => $settings]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Site Ayarları')
                    ->schema([
                        Forms\Components\TextInput::make('data.site_title') 
                            ->label('Site Başlığı')
                            ->required(),
                        Forms\Components\FileUpload::make('data.site_logo')
                            ->label('Logo')
                            ->image()
                            ->directory('site')
                            ->imageEditor() 
                            ->nullable(), 
                        Forms\Components\FileUpload::make('data.site_favicon')
                            ->label('Favicon')
                            ->image()
                            ->directory('site')
                            ->imageResizeTargetWidth('32')
                            ->imageResizeTargetHeight('32')
                            ->nullable(),
                    ])
                    ->columns(1), 
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState()['data'];

        foreach ($data as $key => $value) {

            Setting::updateOrCreate(
                ['key' => $key, 'group' => 'site'],
                [
                    'value' => $value,
                    'type' => is_bool($value) ? 'boolean' : (is_array($value) ? 'json' : 'text'),
                    'is_translatable' => false
                ]
            );
        }

        Notification::make()
            ->title('Site ayarları başarıyla kaydedildi')
            ->success()
            ->send();

        // Ayarlar değiştiği için cache'i temizle
        \Illuminate\Support\Facades\Cache::forget('site_settings');

        // Cache'i yeni kaydedilen ayarlarla hemen tekrar doldur
        \Illuminate\Support\Facades\Cache::forever('site_settings', $data);
    }

    public function render(): View
    {
        // Basit bir view döndür, form ve kaydet butonu içersin
        return view('livewire.settings.generic-settings-view');
    }
}