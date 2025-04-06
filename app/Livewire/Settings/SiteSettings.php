<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\Setting;
use Filament\Forms;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Features\SupportFileUploads\WithFileUploads;

final class SiteSettings extends Component implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;
    use WithFileUploads;

    public array $data = [];
    
    protected array $translatableFields = [
        'site_name',
        'site_description',
        'seo_title',
        'seo_description',
        'seo_keywords'
    ];

    public function mount(): void
    {
        $settings = $this->getSettings();

        // Varsayılan değerleri ekle
        $settings = array_merge([
            'available_locales' => ['en', 'tr'],
            'default_locale' => 'en',
        ], $settings);

        $this->form->fill([
            'data' => $settings
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Dil seçimi için ana tab yapısı
                Forms\Components\Tabs::make('Languages')
                    ->tabs(function () {
                        return collect($this->getAvailableLocales())
                            ->map(fn (string $locale) => 
                                Forms\Components\Tabs\Tab::make($locale)
                                    ->label(mb_strtoupper($locale))
                                    ->schema([
                                        // Her dil için site ayarları
                                        Forms\Components\Section::make('Site Settings')
                                            ->description('Basic site information and SEO settings')
                                            ->schema([
                                                Forms\Components\TextInput::make("data.site_name.{$locale}")
                                                    ->label('Site Name')
                                                    ->required(),
                                                Forms\Components\Textarea::make("data.site_description.{$locale}")
                                                    ->label('Site Description'),
                                                Forms\Components\TextInput::make("data.seo.title.{$locale}")
                                                    ->label('SEO Title'),
                                                Forms\Components\Textarea::make("data.seo.description.{$locale}")
                                                    ->label('SEO Description'),
                                                Forms\Components\TextInput::make("data.seo.keywords.{$locale}")
                                                    ->label('SEO Keywords'),
                                            ])
                                            ->columns(2),
                                    ])
                            )
                            ->toArray();
                    })
                    ->persistTabInQueryString('lang'),

                // Genel ayarlar için ikinci tab yapısı
                Forms\Components\Tabs::make('Settings')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('General')
                            ->schema([
                                Forms\Components\Section::make('URL Settings')
                                    ->schema([
                                        Forms\Components\Select::make('data.url_type')
                                            ->label('URL Type')
                                            ->options([
                                                'https://' => 'https://',
                                                'http://' => 'http://',
                                            ])
                                            ->default('https://')
                                            ->native(false),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Display')
                            ->schema([
                                // Dil Ayarları
                                Forms\Components\Section::make('Language Settings')
                                    ->schema([
                                        Forms\Components\Select::make('data.default_locale')
                                            ->label('Default Language')
                                            ->options(function () {
                                                return collect($this->getAvailableLocales())
                                                    ->mapWithKeys(fn ($locale) => [
                                                        $locale => mb_strtoupper($locale)
                                                    ])
                                                    ->toArray();
                                            })
                                            ->default($this->getDefaultLocale())
                                            ->native(false)
                                            ->required(),
                                        Forms\Components\Select::make('data.available_locales')
                                            ->label('Supported Languages')
                                            ->multiple()
                                            ->options([
                                                'en' => 'English',
                                                'tr' => 'Turkish',
                                                'de' => 'German',
                                                'fr' => 'French',
                                                'es' => 'Spanish',
                                                'it' => 'Italian',
                                                // Diğer diller...
                                            ])
                                            ->default(['en', 'tr'])
                                            ->native(false)
                                            ->required()
                                            ->searchable(),
                                    ])
                                    ->columns(2),

                                // Para Birimi Ayarları
                                Forms\Components\Section::make('Currency Settings')
                                    ->schema([
                                        Forms\Components\Select::make('data.currency')
                                            ->label('Para Birimi')
                                            ->options([
                                                '₺' => 'Türk Lirası (₺)',
                                                '$' => 'Dolar ($)',
                                                '€' => 'Euro (€)',
                                            ])
                                            ->default('₺')
                                            ->native(false),
                                        Forms\Components\Select::make('data.currency_symbol')
                                            ->label('Para Birimi Görünümü')
                                            ->options([
                                                'symbol' => 'Sembol',
                                                'code' => 'Kod',
                                            ])
                                            ->default('symbol')
                                            ->native(false),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Theme Settings')
                                    ->schema([
                                        Forms\Components\Toggle::make('data.dark_mode')
                                            ->label('Gece/Gündüz Modu')
                                            ->inline(false),
                                        Forms\Components\Select::make('data.default_theme')
                                            ->label('Varsayılan Site Rengi')
                                            ->options([
                                                'light' => 'Light',
                                                'dark' => 'Dark',
                                            ])
                                            ->default('light')
                                            ->native(false),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('System')
                            ->schema([
                                Forms\Components\Section::make('System Configuration')
                                    ->schema([
                                        Forms\Components\Select::make('data.sidebar_position')
                                            ->label('Sidebar Özelliği')
                                            ->options([
                                                'passive' => 'Pasif',
                                                'active' => 'Aktif',
                                            ])
                                            ->default('passive')
                                            ->native(false),
                                        Forms\Components\Select::make('data.vat_system')
                                            ->label('KDV Sistemi')
                                            ->options([
                                                'passive' => 'Pasif',
                                                'active' => 'Aktif',
                                            ])
                                            ->default('active')
                                            ->native(false),
                                        Forms\Components\TextInput::make('data.vat_rate')
                                            ->label('KDV Yüzdesi')
                                            ->numeric()
                                            ->default(20),
                                    ])
                                    ->columns(3),

                                Forms\Components\Section::make('Order Settings')
                                    ->schema([
                                        Forms\Components\Select::make('data.address_required')
                                            ->label('Sipariş Formu Adres Bilgileri')
                                            ->options([
                                                'none' => 'Adres bilgileri istenmesin',
                                                'optional' => 'Adres bilgileri isteğe bağlı',
                                                'required' => 'Adres bilgileri zorunlu',
                                            ])
                                            ->native(false),
                                        Forms\Components\TextInput::make('data.refund_hours')
                                            ->label('Telafi Butonunun Başlangıç Saati')
                                            ->numeric()
                                            ->default(24),
                                        Forms\Components\Select::make('data.order_required')
                                            ->label('Üyelikte Sipariş Zorunluluğu')
                                            ->options([
                                                'passive' => 'Pasif',
                                                'active' => 'Aktif',
                                            ])
                                            ->default('active')
                                            ->native(false),
                                    ])
                                    ->columns(3),
                            ]),
                    ])
                    ->persistTabInQueryString('tab'),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState()['data'];

        foreach ($data as $key => $value) {
            // available_locales için özel işlem
            if ($key === 'available_locales') {
                $value = json_encode(array_values($value)); // array_values ile index'leri temizle
            }
            // default_locale için özel işlem
            elseif ($key === 'default_locale') {
                $value = is_array($value) ? array_key_first($value) : $value;
            }
            // Diğer çevrilebilir alanlar için normal işlem
            elseif ($this->isTranslatableField($key)) {
                $value = json_encode($value);
            }

            Setting::updateOrCreate(
                ['key' => $key, 'group' => 'site'],
                [
                    'value' => $value,
                    'type' => $this->getSettingType($value),
                    'is_translatable' => $this->isTranslatableField($key)
                ]
            );
        }

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();

        // Değişiklikleri hemen görmek için sayfayı yenile
        $this->redirect(request()->header('Referer'));
    }

    protected function getSettings(): array
    {
        return Setting::where('group', 'site')
            ->get()
            ->mapWithKeys(function ($setting) {
                $value = $setting->value;
                
                // Çevrilebilir alanlar için
                if ($setting->is_translatable) {
                    $value = json_decode($value, true) ?? [];
                }
                // available_locales için özel işlem
                elseif ($setting->key === 'available_locales') {
                    $value = json_decode($value, true) ?? ['en', 'tr'];
                }
                // default_locale için özel işlem
                elseif ($setting->key === 'default_locale') {
                    $value = $value ?? 'en';
                }

                return [$setting->key => $value];
            })
            ->toArray();
    }

    protected function getSettingType($value): string
    {
        return match(true) {
            is_bool($value) => 'boolean',
            is_string($value) && str_contains($value, "\n") => 'textarea',
            default => 'text',
        };
    }

    public function render(): View
    {
        return view('livewire.settings.site');
    }

    protected function getAvailableLocales(): array
    {
        // Önce ayarlardan kontrol et, yoksa config'den al
        $settings = Setting::where('key', 'available_locales')
            ->where('group', 'site')
            ->first();

        if ($settings) {
            $value = $settings->value;
            // Eğer string ise JSON decode et
            if (is_string($value)) {
                $value = json_decode($value, true);
            }
            // Array değilse veya boşsa default değeri kullan
            if (!is_array($value) || empty($value)) {
                return config('app.available_locales', ['en']);
            }
            return $value;
        }

        return config('app.available_locales', ['en']);
    }

    protected function getDefaultLocale(): string
    {
        // Önce ayarlardan kontrol et, yoksa config'den al
        $settings = Setting::where('key', 'default_locale')
            ->where('group', 'site')
            ->first();

        if ($settings && $settings->value) {
            // Eğer string ise direkt kullan, array ise ilk değeri al
            return is_array($settings->value) 
                ? (array_key_first($settings->value) ?? config('app.locale', 'en'))
                : $settings->value;
        }

        return config('app.locale', 'en');
    }
} 