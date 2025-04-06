<?php

namespace App\Livewire\Transaction;

use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Services\Transaction\Implementations\TransactionService;
use App\DTOs\Transaction\TransactionData;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use App\Livewire\Transaction\Widgets\TransactionStatsWidget;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

/**
 * İşlem Yönetimi Bileşeni
 * 
 * Bu bileşen, finansal işlemlerin yönetimini sağlar.
 * Özellikler:
 * - İşlem listesi görüntüleme
 * - Yeni işlem oluşturma
 * - İşlem düzenleme
 * - İşlem silme
 * - İşlem filtreleme (tür, kategori, tarih aralığı)
 * - Toplu işlem desteği
 * - İstatistik widget'ları
 * 
 * @package App\Livewire\Transaction
 */
class TransactionManager extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    /** @var TransactionService İşlem servisi */
    private TransactionService $transactionService;

    /** @var array Dinleyici olayları */
    protected $listeners = ['refreshTransactions' => '$refresh'];

    /**
     * Bileşen başlatılırken işlem servisini enjekte eder
     * 
     * @param TransactionService $transactionService İşlem servisi
     * @return void
     */
    public function boot(TransactionService $transactionService): void
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Bileşen başlatılırken çalışır
     * 
     * @param int|null $transactionId Düzenlenecek işlem ID'si
     * @return void
     */
    public function mount($transactionId = null): void
    {
        if ($transactionId) {
            $this->transaction = Transaction::findOrFail($transactionId);

            if ($this->transaction->is_taxable && $this->transaction->tax_rate) {
                $taxRate = $this->transaction->tax_rate / 100;
                $netAmount = $this->transaction->amount / (1 + $taxRate);
                $this->transaction->tax_amount = round($this->transaction->amount - $netAmount, 2);
            }

            if ($this->transaction->currency === 'TRY') {
                $this->transaction->exchange_rate = 1;
            }

            $this->form->fill($this->transaction->toArray());
        }
    }

    /**
     * Form şemasını oluşturur
     * 
     * @return array Form bileşenleri
     */
    private function getFormSchema(): array
    {
        return [
            // İşlem türü, kategori, müşteri ve ödeme detayları
            ...$this->getTransactionDetailsSchema(),

            // Para birimi, kur ve tutar
            ...$this->getCurrencyAndAmountSchema(),

            // Açıklama
            Forms\Components\Textarea::make('description')
                ->label('Açıklama')
                ->rows(2),

            // Abonelik bilgileri
            $this->getSubscriptionSchema(),

            // Vergilendirme
            $this->getTaxationSchema(),

            // Transfer işlemleri için özel form alanları
            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\Select::make('source_account_id')
                        ->label('Kaynak Hesap')
                        ->options(function () {
                            return Account::where('user_id', auth()->id())
                                ->where('status', true)
                                ->get()
                                ->mapWithKeys(fn ($account) => [
                                    $account->id => "{$account->name} ({$account->formatted_balance})"
                                ]);
                        })
                        ->required()
                        ->searchable()
                        ->native(false)
                        ->live(),
                    
                    Forms\Components\Select::make('destination_account_id')
                        ->label('Hedef Hesap')
                        ->options(function () {
                            return Account::where('user_id', auth()->id())
                                ->where('status', true)
                                ->get()
                                ->mapWithKeys(fn ($account) => [
                                    $account->id => "{$account->name} ({$account->formatted_balance})"
                                ]);
                        })
                        ->required()
                        ->searchable()
                        ->native(false),
                ])
                ->columns(2)
                ->visible(fn (callable $get) => $get('type') === Transaction::TYPE_TRANSFER),
        ];
    }

    /**
     * Önbelleğe alınmış hesap seçeneklerini döndürür
     * 
     * @param string|null $type Hesap tipi
     * @return array Hesap seçenekleri
     */
    private function getCachedAccountOptions(string $type = null): array
    {
        $cacheKey = $type ? "user_{auth()->id()}_accounts_{$type}" : "user_{auth()->id()}_accounts";
        return cache()->remember($cacheKey, now()->addHours(24), function () use ($type) {
            $query = Account::query()
                ->where('user_id', auth()->id())
                ->where('status', true)
                ->whereNull('deleted_at');
            
            if ($type) {
                $query->where('type', $type);
            }
            
            return $query->get()
                ->mapWithKeys(function ($account) {
                    $typeName = match($account->type) {
                        'bank_account' => 'Banka',
                        'credit_card' => 'Kredi Kartı',
                        'crypto_wallet' => 'Kripto',
                        'virtual_pos' => 'Sanal POS',
                        'cash' => 'Nakit',
                        default => $account->type,
                    };
                    return [$account->id => "{$account->name} ({$account->currency}) - {$typeName}"];
                })->toArray();
        });
    }

    /**
     * Önbelleğe alınmış kategori seçeneklerini döndürür
     * 
     * @param string $type Kategori tipi
     * @return array Kategori seçenekleri
     */
    private function getCachedCategoryOptions(string $type): array
    {
        $cacheKey = "user_{auth()->id()}_categories_{$type}";
        return cache()->remember($cacheKey, now()->addHours(24), function () use ($type) {
            return Category::query()
                ->where('type', $type)
                ->where('status', true)
                ->pluck('name', 'id')
                ->toArray();
        });
    }
    
    /**
     * İşlem detayları form şemasını oluşturur
     * 
     * @return array Form bileşenleri
     */
    private function getTransactionDetailsSchema(): array
    {
        return [
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\Select::make('type')
                    ->label('İşlem')
                    ->options([
                        Transaction::TYPE_INCOME => 'Gelir',
                        Transaction::TYPE_EXPENSE => 'Gider',
                        Transaction::TYPE_TRANSFER => 'Transfer',
                    ])
                    ->native(false)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, callable $get) {
                        $set('category_id', null);
                        
                        if ($get('payment_method') !== 'cash' && $get('payment_method') !== null) {
                            $set('payment_method', 'cash');
                        }
                        
                        $set('account_select', null);
                        $set('source_account_id', null);
                        $set('destination_account_id', null);

                        if ($state === Transaction::TYPE_TRANSFER) {
                            $set('show_transfer_fields', true);
                        } else {
                            $set('show_transfer_fields', false);
                        }
                    }),
    
                Forms\Components\Select::make('category_id')
                    ->label('Kategori')
                    ->options(function (callable $get) {
                        if (!$get('type')) return [];
                        return $this->getCachedCategoryOptions($get('type'));
                    })
                    ->searchable()
                    ->required()
                    ->native(false),
    
                Forms\Components\Select::make('customer_id')
                    ->label('Müşteri')
                    ->options(function () {
                        return \App\Models\Customer::query()
                            ->where('status', true)
                            ->get()
                            ->mapWithKeys(function ($customer) {
                                return [$customer->id => $customer->company_name ?? $customer->name];
                            });
                    })
                    ->searchable()
                    ->placeholder('Müşteri seçiniz (opsiyonel)')
                    ->native(false)
                    ->visible(fn (callable $get) => $get('type') === Transaction::TYPE_INCOME)
                    ->columnSpan(2),
    
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Select::make('payment_method')
                            ->label('Ödeme Yöntemi')
                            ->options([
                                'cash' => 'Nakit',
                                'bank' => 'Banka Hesabı',
                                'credit_card' => 'Kredi Kartı',
                                'crypto' => 'Kripto Cüzdan',
                                'virtual_pos' => 'Sanal POS',
                            ])
                            ->placeholder('Ödeme yöntemi seçin')
                            ->default('cash')
                            ->live()
                            ->native(false)
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('account_select', null);
                                $set('source_account_id', null);
                                $set('destination_account_id', null);
                            }),

                        Forms\Components\Select::make('account_select')
                            ->label(fn (callable $get) => match($get('payment_method')) {
                                'bank' => 'Banka Hesabı',
                                'credit_card' => 'Kredi Kartı',
                                'crypto' => 'Kripto Cüzdan',
                                'virtual_pos' => 'Sanal POS',
                                default => 'Hesap'
                            })
                            ->placeholder(fn (callable $get) => match($get('payment_method')) {
                                'bank' => 'Banka Hesabı',
                                'credit_card' => 'Kredi Kartı',
                                'crypto' => 'Kripto Cüzdan',
                                'virtual_pos' => 'Sanal POS',
                                default => 'Hesap'
                            } . ' seçin')
                            ->options(function (callable $get) {
                                if ($get('payment_method') === 'cash' || $get('payment_method') === null) {
                                    return [];
                                }

                                $type = match($get('payment_method')) {
                                    'bank' => Account::TYPE_BANK_ACCOUNT,
                                    'credit_card' => Account::TYPE_CREDIT_CARD,
                                    'crypto' => Account::TYPE_CRYPTO_WALLET,
                                    'virtual_pos' => Account::TYPE_VIRTUAL_POS,
                                    default => null
                                };

                                return $this->getCachedAccountOptions($type);
                            })
                            ->searchable()
                            ->native(false)
                            ->disabled(fn (callable $get) => 
                                $get('payment_method') === 'cash' || 
                                $get('payment_method') === null
                            )
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, callable $get) {
                                if (!$state) {
                                    $set('source_account_id', null);
                                    $set('destination_account_id', null);
                                    return;
                                }

                                $type = $get('type');
                                $paymentMethod = $get('payment_method');

                                if ($type === Transaction::TYPE_INCOME) {
                                    $set('destination_account_id', (int)$state);
                                    $set('source_account_id', null);
                                } else if ($type === Transaction::TYPE_EXPENSE) {
                                    if ($paymentMethod === 'virtual_pos') {
                                        $set('source_account_id', null);
                                        Notification::make()
                                            ->title('Sanal POS ile ödeme yapılamaz')
                                            ->warning()
                                            ->send();
                                        return;
                                    }
                                    $set('source_account_id', (int)$state);
                                    $set('destination_account_id', null);
                                }
                            }),
                    ])
                    ->columns(2),
            ]),
        ];
    }

    /**
     * Para birimi ve tutar form şemasını oluşturur
     * 
     * @return array Form bileşenleri
     */
    private function getCurrencyAndAmountSchema(): array
    {
        return [
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\Select::make('currency')
                    ->label('Para Birimi')
                    ->options([
                        'TRY' => 'Türk Lirası (₺)',
                        'USD' => 'Amerikan Doları ($)',
                        'EUR' => 'Euro (€)',
                        'GBP' => 'İngiliz Sterlini (£)',
                        'USDT' => 'Tether (USDT)',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, callable $get) {
                        $accountId = $get('account_select');
                        if (!$accountId) return;

                        $account = Account::find($accountId);
                        if (!$account) return;

                        if ($account->type === Account::TYPE_CRYPTO_WALLET && $state !== 'USDT') {
                            $set('currency', 'USDT');
                            Notification::make()
                                ->warning()
                                ->title('Kripto cüzdanı için sadece USDT kullanılabilir')
                                ->send();
                            return;
                        }

                        if ($state === 'USDT' && $account->type !== Account::TYPE_CRYPTO_WALLET) {
                            $set('account_select', null);
                            Notification::make()
                                ->warning()
                                ->title('USDT sadece kripto cüzdanlar için kullanılabilir')
                                ->send();
                            return;
                        }

                        if ($state !== 'TRY') {
                            $exchangeRate = $this->getExchangeRate($state);
                            $set('exchange_rate', $exchangeRate);
                            $set('try_equivalent', $get('amount') * $exchangeRate);
                        }
                    }),

                Forms\Components\TextInput::make('exchange_rate')
                    ->label('Kur')
                    ->numeric()
                    ->required(fn (callable $get) => $get('currency') !== 'TRY')
                    ->default(1)
                    ->disabled(fn (callable $get) => $get('currency') === 'TRY')
                    ->dehydrated()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        if ($state && $get('amount')) {
                            $set('try_equivalent', round($get('amount') * $state, 2));
                        }
                    })
                    ->placeholder('Kur değeri')
                    ->helperText('TL karşılığını hesaplamak için kur değeri'),
            ]),

            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('amount')
                    ->label('Tutar (Brüt)')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $amount = $state;
                        $exchangeRate = $get('exchange_rate') ?? 1;
                        $tryAmount = $amount * $exchangeRate;
                        $set('try_equivalent', round($tryAmount, 2));
                        if ($get('is_taxable') && $get('tax_rate')) {
                            $taxRate = $get('tax_rate') / 100;
                            $netAmount = $amount / (1 + $taxRate);
                            $set('tax_amount', round($amount - $netAmount, 2));
                        }
                        if ($get('is_taxable') && $get('has_withholding') && $get('withholding_rate')) {
                            $withholdingRate = $get('withholding_rate') / 100;
                            $set('withholding_amount', round($amount * $withholdingRate, 2));
                        }
                    })
                    ->prefix(fn (callable $get) => match($get('currency')) {
                        'TRY' => '₺',
                        'USD' => '$',
                        'EUR' => '€',
                        'GBP' => '£',
                        default => '₺',
                    }),

                Forms\Components\DatePicker::make('date')
                    ->label('Tarih')
                    ->required()
                    ->default(now())
                    ->displayFormat('d.m.Y')
                    ->native(false),
            ]),

            Forms\Components\Grid::make(1)->schema([
                Forms\Components\TextInput::make('try_equivalent')
                    ->label('TL Karşılığı')
                    ->disabled()
                    ->numeric()
                    ->prefix('₺'),
            ])->visible(fn (callable $get) => $get('currency') !== 'TRY'),
        ];
    }

    /**
     * Abonelik bilgileri form şemasını oluşturur
     * 
     * @return Forms\Components\Section Abonelik bileşenleri
     */
    private function getSubscriptionSchema(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Abonelik Bilgileri')
            ->schema([
                Forms\Components\Toggle::make('is_subscription')
                    ->label('Abonelik mi?')
                    ->helperText('Bu işlem düzenli tekrarlanan bir abonelik ödemesi ise işaretleyin.')
                    ->default(false)
                    ->live(),
                
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('subscription_period')
                            ->label('Abonelik Periyodu')
                            ->options([
                                'daily' => 'Günlük',
                                'weekly' => 'Haftalık',
                                'monthly' => 'Aylık',
                                'quarterly' => '3 Aylık',
                                'biannually' => '6 Aylık',
                                'annually' => 'Yıllık',
                            ])
                            ->required(fn (callable $get) => $get('is_subscription'))
                            ->native(false),
                            
                        Forms\Components\DatePicker::make('next_payment_date')
                            ->label('Sonraki Ödeme Tarihi')
                            ->displayFormat('d.m.Y')
                            ->default(fn () => now()->addMonth())
                            ->required(fn (callable $get) => $get('is_subscription'))
                            ->native(false),
                    ])
                    ->visible(fn (callable $get) => $get('is_subscription')),
            ])
            ->collapsed()
            ->collapsible();
    }

    /**
     * Vergilendirme form şemasını oluşturur
     * 
     * @return Forms\Components\Section Vergilendirme bileşenleri
     */
    private function getTaxationSchema(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Vergilendirme')
            ->schema([
                Forms\Components\Grid::make(1)->schema([
                    Forms\Components\Select::make('is_taxable')
                        ->label('Vergilendirme')
                        ->options([
                            true => 'Var',
                            false => 'Yok',
                        ])
                        ->default(false)
                        ->live()
                        ->native(false)
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (!$state) {
                                $set('tax_rate', null);
                                $set('tax_amount', null);
                                $set('has_withholding', false);
                                $set('withholding_rate', null);
                                $set('withholding_amount', null);
                            }
                        }),
                ]),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('tax_rate')
                            ->label('KDV Oranı')
                            ->options([
                                0 => '%0',
                                1 => '%1',
                                8 => '%8',
                                10 => '%10',
                                18 => '%18',
                                20 => '%20',
                            ])
                            ->required(fn (callable $get) => $get('is_taxable'))
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state === 0 || $state === null) {
                                    $set('tax_amount', null);
                                } else if ($get('amount')) {
                                    $amount = $get('amount');
                                    $taxRate = $state / 100;
                                    $netAmount = $amount / (1 + $taxRate);
                                    $set('tax_amount', round($amount - $netAmount, 2));
                                }
                            }),

                        Forms\Components\TextInput::make('tax_amount')
                            ->label('KDV Tutarı')
                            ->disabled()
                            ->numeric()
                            ->prefix(fn (callable $get) => match($get('currency')) {
                                'TRY' => '₺',
                                'USD' => '$',
                                'EUR' => '€',
                                'GBP' => '£',
                                default => '₺',
                            }),
                    ])->visible(fn (callable $get) => $get('is_taxable')),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('has_withholding')
                            ->label('Stopaj')
                            ->options([
                                true => 'Var',
                                false => 'Yok',
                            ])
                            ->default(false)
                            ->required(fn (callable $get) => $get('is_taxable'))
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!$state) {
                                    $set('withholding_rate', null);
                                    $set('withholding_amount', null);
                                }
                            }),

                        Forms\Components\TextInput::make('withholding_rate')
                            ->label('Stopaj Oranı (%)')
                            ->numeric()
                            ->required(fn (callable $get) => $get('has_withholding'))
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state === 0 || $state === null || !$get('has_withholding')) {
                                    $set('withholding_amount', null);
                                } else if ($get('amount')) {
                                    $amount = $get('amount');
                                    $withholdingRate = $state / 100;
                                    $set('withholding_amount', round($amount * $withholdingRate, 2));
                                }
                            }),
                    ])->visible(fn (callable $get) => $get('is_taxable')),

                Forms\Components\Grid::make(1)
                    ->schema([
                        Forms\Components\TextInput::make('withholding_amount')
                            ->label('Stopaj Tutarı')
                            ->disabled()
                            ->numeric()
                            ->prefix(fn (callable $get) => match($get('currency')) {
                                'TRY' => '₺',
                                'USD' => '$',
                                'EUR' => '€',
                                'GBP' => '£',
                                default => '₺',
                            }),
                    ])->visible(fn (callable $get) => $get('is_taxable') && $get('has_withholding')),
            ])
            ->collapsed()
            ->collapsible();
    }

    /**
     * Tablo yapılandırmasını oluşturur
     * 
     * @param Tables\Table $table Tablo nesnesi
     * @return Tables\Table Yapılandırılmış tablo
     */
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->where('user_id', auth()->id())
                    ->where(function ($query) {
                        $query->where(function ($q) {
                            $q->where('type', 'transfer')
                              ->where('amount', '>', 0);
                        })->orWhere('type', '!=', 'transfer');
                    })
                    ->latest('id')
                    ->with(['category', 'sourceAccount', 'destinationAccount'])
            )
            ->emptyStateHeading('İşlem Bulunamadı')
            ->emptyStateDescription('Başlamak için yeni bir işlem ekleyin.')
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Tarih')
                    ->date('d.m.Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->default(function (Transaction $record) {
                        if (in_array($record->type, ['atm_deposit', 'atm_withdraw'])) {
                            return 'Bankamatik İşlemleri';
                        }
                        if ($record->type === 'loan_payment') {
                            return 'Kredi İşlemleri';
                        }
                        if ($record->type === 'payment') {
                            return 'Kredi Kartı İşlemleri';
                        }
                        return '-';
                    }),

                Tables\Columns\TextColumn::make('description')
                    ->label('Açıklama')
                    ->default(function (Transaction $record) {
                        return $record->description ?? '-';
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Tutar')
                    ->formatStateUsing(function (Transaction $record) {
                        if ($record->currency === 'TRY') {
                            return '₺' . number_format($record->amount, 2, ',', '.');
                        }
                        return match($record->currency) {
                            'USD' => '$',
                            'EUR' => '€',
                            'GBP' => '£',
                            default => $record->currency
                        } . number_format($record->amount, 2, ',', '.') . ' - ' . 
                        '<span class="text-blue-600">₺' . number_format($record->try_equivalent, 2, ',', '.') . '</span>';
                    })
                    ->html()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tür')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'income' => 'success',
                        'expense' => 'danger',
                        'atm_deposit' => 'info',
                        'atm_withdraw' => 'info',
                        'transfer' => 'warning',
                        'payment' => 'info',
                        default => 'info',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'income' => 'Gelir',
                        'expense' => 'Gider',
                        'transfer' => 'Transfer',
                        'atm_deposit' => 'ATM Para Yatırma',
                        'atm_withdraw' => 'ATM Para Çekme',
                        'loan_payment' => 'Kredi Ödemesi',
                        'payment' => 'Kredi Kartı Ödemesi',
                        'debt_payment' => 'Borç Ödemesi',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tür')
                    ->options([
                        'income' => 'Gelir',
                        'expense' => 'Gider',
                        'transfer' => 'Transfer',
                        'loan_payment' => 'Kredi Ödemesi',
                        'payment' => 'Kredi Kartı Ödemesi',
                    ])
                    ->native(false),
                
                Tables\Filters\SelectFilter::make('income_category_id')
                    ->label('Gelir Kategorisi')
                    ->options(function() {
                        return cache()->remember("user_{auth()->id()}_categories_income", now()->addHours(24), function () {
                            return Category::where('user_id', auth()->id())
                                ->where('type', 'income')
                                ->pluck('name', 'id')
                                ->toArray();
                        });
                    })
                    ->query(function ($query, $data) {
                        if (!$data['value']) {
                            return $query;
                        }
                        
                        return $query->where(function ($q) use ($data) {
                            $q->where('category_id', $data['value'])
                              ->where('type', 'income');
                        });
                    })
                    ->native(false),
                
                Tables\Filters\SelectFilter::make('expense_category_id')
                    ->label('Gider Kategorisi')
                    ->options(function() {
                        return cache()->remember("user_{auth()->id()}_categories_expense", now()->addHours(24), function () {
                            return Category::where('user_id', auth()->id())
                                ->where('type', 'expense')
                                ->pluck('name', 'id')
                                ->toArray();
                        });
                    })
                    ->query(function ($query, $data) {
                        if (!$data['value']) {
                            return $query;
                        }
                        
                        return $query->where(function ($q) use ($data) {
                            $q->where('category_id', $data['value'])
                              ->where('type', 'expense');
                        });
                    })
                    ->native(false),
                
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from_date')
                            ->label('Başlangıç Tarihi')
                            ->displayFormat('d.m.Y')
                            ->native(false),
                        Forms\Components\DatePicker::make('to_date')
                            ->label('Bitiş Tarihi')
                            ->displayFormat('d.m.Y')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        
                        if ($data['from_date'] ?? null) {
                            $indicators['from_date'] = 'Başlangıç: ' . \Carbon\Carbon::parse($data['from_date'])->format('d.m.Y');
                        }
                        
                        if ($data['to_date'] ?? null) {
                            $indicators['to_date'] = 'Bitiş: ' . \Carbon\Carbon::parse($data['to_date'])->format('d.m.Y');
                        }
                        
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Düzenle')
                    ->url(fn (Transaction $record): string => route('admin.transactions.edit', $record))
                    ->extraAttributes(['wire:navigate' => true])
                    ->icon('heroicon-m-pencil-square')
                    ->visible(fn (Transaction $record) => !in_array($record->type, ['transfer', 'atm_deposit', 'atm_withdraw', 'loan_payment', 'payment'])),

                Tables\Actions\DeleteAction::make()
                    ->using(function (Transaction $record) {
                        if (in_array($record->type, ['transfer', 'atm_deposit', 'atm_withdraw', 'loan_payment', 'payment'])) {
                            Notification::make()
                                ->title('İşlem Silinemez')
                                ->body('Bu tür işlemler (' . $this->getTransactionTypeName($record->type) . ') silinemez.')
                                ->danger()
                                ->send();
                            return false;
                        }

                        try {
                            $result = $this->transactionService->delete($record);
                            if ($result) {
                                $this->dispatch('transactionDeleted');
                                Notification::make()
                                    ->title('İşlem Silindi')
                                    ->success()
                                    ->send();
                            }
                            return $result;
                        } catch (\Exception $e) {
                            Log::error('Transaction delete error: ' . $e->getMessage());
                            Notification::make()
                                ->title('Hata')
                                ->body('İşlem silinirken bir hata oluştu: ' . $e->getMessage())
                                ->danger()
                                ->send();
                            return false;
                        }
                    })
                    ->visible(fn (Transaction $record) => !in_array($record->type, ['transfer', 'atm_deposit', 'atm_withdraw', 'loan_payment', 'payment'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            $nonDeletableTypes = ['transfer', 'atm_deposit', 'atm_withdraw', 'loan_payment', 'payment'];
                            $nonDeletableRecords = $records->filter(fn ($record) => in_array($record->type, $nonDeletableTypes));
                            
                            if ($nonDeletableRecords->count() > 0) {
                                $types = $nonDeletableRecords->pluck('type')->unique()->map(fn ($type) => $this->getTransactionTypeName($type))->implode(', ');
                                Notification::make()
                                    ->title('Bazı İşlemler Silinemez')
                                    ->body("Seçilen işlemler arasında silinemeyen türler ($types) bulunmaktadır. Lütfen standart işlemleri seçin.")
                                    ->danger()
                                    ->send();
                                return;
                            }
                            
                            $successCount = 0;
                            $errorCount = 0;
                            
                            foreach ($records as $record) {
                                try {
                                    if ($this->transactionService->delete($record)) {
                                        $successCount++;
                                    } else {
                                        $errorCount++;
                                    }
                                } catch (\Exception $e) {
                                    $errorCount++;
                                }
                            }
                            
                            if ($successCount > 0) {
                                $this->dispatch('transactionDeleted');
                                Notification::make()
                                    ->title($successCount . ' İşlem Başarıyla Silindi')
                                    ->success()
                                    ->send();
                            }
                            
                            if ($errorCount > 0) {
                                Notification::make()
                                    ->title($errorCount . ' İşlem Silinemedi')
                                    ->warning()
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create')
                    ->label('İşlem Oluştur')
                    ->url(route('admin.transactions.create'))
                    ->extraAttributes(['wire:navigate' => true]),
            ]);
    }

    /**
     * Bileşenin görünümünü render eder
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        return view('livewire.transaction.transaction-manager', [
            'stats' => new TransactionStatsWidget(),
        ]);
    }

    /**
     * İşlem türü adını döndürür
     * 
     * @param string $type İşlem türü
     * @return string İşlem türü adı
     */
    private function getTransactionTypeName(string $type): string
    {
        return match ($type) {
            'income' => 'Gelir',
            'expense' => 'Gider',
            'transfer' => 'Transfer',
            'atm_deposit' => 'ATM Para Yatırma',
            'atm_withdraw' => 'ATM Para Çekme',
            'loan_payment' => 'Kredi Ödemesi',
            'payment' => 'Kredi Kartı İşlemleri',
            'debt_payment' => 'Borç Ödemesi',
            default => $type,
        };
    }
}