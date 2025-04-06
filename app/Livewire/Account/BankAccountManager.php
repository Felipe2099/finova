<?php

namespace App\Livewire\Account;

use App\Models\Account;
use App\Services\Account\Implementations\AccountService;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use App\DTOs\Account\AccountData;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Services\Currency\CurrencyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Transaction;
use App\Models\Category;
use Carbon\Carbon;
use App\Enums\PaymentMethodEnum;

/**
 * Banka Hesabı Yönetimi Bileşeni
 * 
 * Sadece banka hesaplarının yönetimini sağlayan özelleştirilmiş Livewire bileşeni.
 * Banka hesapları için detaylı işlemler ve özellikler sunar.
 * 
 * Özellikler:
 * - Banka hesabı oluşturma/düzenleme/silme
 * - Hesaplar arası transfer
 * - Bankamatik işlemleri (para yatırma/çekme)
 * - Para birimi dönüşümleri
 * - Detaylı filtreleme ve arama
 * - İşlem geçmişi görüntüleme
 */
class BankAccountManager extends Component implements Forms\Contracts\HasForms, Tables\Contracts\HasTable
{
    use Forms\Concerns\InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;

    /** @var AccountService Hesap işlemleri için servis */
    private AccountService $accountService;

    /** @var CurrencyService Para birimi dönüşümleri için servis */
    private CurrencyService $currencyService;

    /**
     * Bileşen başlatma
     * 
     * @param AccountService $accountService Hesap servisi
     * @param CurrencyService $currencyService Para birimi servisi
     * @return void
     */
    public function boot(AccountService $accountService, CurrencyService $currencyService): void 
    {
        $this->accountService = $accountService;
        $this->currencyService = $currencyService;
    }

    /**
     * Banka hesabı listesi tablosunu yapılandırır
     * 
     * @param Tables\Table $table Filament tablo yapılandırması
     * @return Tables\Table
     */
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(
                Account::query()
                    ->where('user_id', auth()->id())
                    ->where('type', Account::TYPE_BANK_ACCOUNT)
            )
            ->emptyStateHeading('Banka Hesabı Bulunamadı')
            ->emptyStateDescription('Başlamak için yeni bir banka hesabı oluşturun.')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Hesap Adı')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('details.bank_name')
                    ->label('Banka')
                    ->getStateUsing(fn (Account $record) => $record->details['bank_name'] ?? 'Bilinmiyor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('currency')
                    ->label('Para Birimi')
                    ->badge(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Bakiye')
                    ->money(fn (Account $record) => $record->currency)
                    ->color(fn (Account $record) => $record->balance < 0 ? 'danger' : 'success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('try_equivalent')
                    ->label('TRY Karşılığı')
                    ->getStateUsing(function (Account $record) {
                        // Eğer hesap zaten TRY ise, bakiyeyi doğrudan döndür
                        if ($record->currency === 'TRY') {
                            return $record->balance;
                        }
                        
                        // Güncel kur bilgisini al
                        $exchangeRate = $this->currencyService->getExchangeRate($record->currency);
                        
                        // Kur bilgisi alınamadıysa null dön
                        if (!$exchangeRate) {
                            return null;
                        }
                        
                        // Alış kuru üzerinden TRY karşılığını hesapla
                        return $record->balance * $exchangeRate['buying'];
                    })
                    ->money('TRY')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('status')
                    ->label('Durum')
                    ->onColor('success')
                    ->offColor('danger')
                    ->extraAttributes(['class' => 'compact-toggle'])
                    ->afterStateUpdated(function (Account $record, $state) {
                        $statusText = $state ? 'aktif' : 'pasif';
                        Notification::make()
                            ->title("{$record->name} hesabı {$statusText} duruma getirildi.")
                            ->success()
                            ->send();
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('currency')
                    ->label('Para Birimi')
                    ->multiple()
                    ->native(false)
                    ->options([
                        'TRY' => 'Türk Lirası',
                        'USD' => 'Amerikan Doları',
                        'EUR' => 'Euro',
                        'GBP' => 'İngiliz Sterlini',
                    ])
                    ->query(function (Builder $query, array $data): void {
                        if (!empty($data['values'])) {
                            $query->whereIn('currency', $data['values']);
                        }
                    }),
                Tables\Filters\TernaryFilter::make('status')
                    ->label('Durum')
                    ->native(false)
                    ->query(function (Builder $query, array $data): void {
                        if (isset($data['value'])) {
                            $query->where('status', $data['value']);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_history')
                    ->label('Bakiye Geçmişi')
                    ->icon('heroicon-o-clock')
                    ->url(fn (Account $record): string => route('admin.accounts.history', $record->id))
                    ->openUrlInNewTab(false)
                    ->extraAttributes(['wire:navigate' => true])
                    ->color('gray'),
                Tables\Actions\EditAction::make()
                    ->modalHeading('Banka Hesabı Düzenle')
                    ->modalSubmitActionLabel('Kaydet')
                    ->modalCancelActionLabel('İptal')
                    ->form($this->getFormSchema())
                    ->mutateRecordDataUsing(function (array $data) {
                        $account = Account::find($data['id']);
                        $details = $account->details ?? [];
                        $data['details'] = [
                            'bank_name' => $details['bank_name'] ?? null,
                            'account_number' => $details['account_number'] ?? null,
                            'iban' => $details['iban'] ?? null,
                            'branch_code' => $details['branch_code'] ?? null,
                            'branch_name' => $details['branch_name'] ?? null,
                        ];
                        return $data;
                    })
                    ->using(function (Account $record, array $data) {
                        $accountData = AccountData::fromArray([
                            'name' => $data['name'],
                            'type' => Account::TYPE_BANK_ACCOUNT,
                            'currency' => $data['currency'],
                            'balance' => $data['balance'],
                            'status' => $data['status'],
                            'details' => [
                                'bank_name' => $data['details']['bank_name'],
                                'account_number' => $data['details']['account_number'] ?? null,
                                'iban' => $data['details']['iban'] ?? null,
                                'branch_code' => $data['details']['branch_code'] ?? null,
                                'branch_name' => $data['details']['branch_name'] ?? null,
                            ],
                        ]);
                        return $this->accountService->updateAccount($record, $accountData);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Banka Hesabı Sil')
                    ->modalDescription('Bu hesabı silmek istediğinize emin misiniz?')
                    ->modalSubmitActionLabel('Sil')
                    ->modalCancelActionLabel('İptal')
                    ->successNotificationTitle('Banka hesabı silindi')
                    ->label('Sil')
                    ->using(function (Account $record) {
                        return $this->accountService->delete($record);
                    }),
                Tables\Actions\Action::make('transfer')
                    ->label('Transfer')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->modalHeading('Hesaplar Arası Transfer')
                    ->modalDescription('Bunu yapmak istediğinizden emin misiniz?')
                    ->form(function (Account $record) {
                        return [
                            Forms\Components\Grid::make()
                                ->schema([
                                    Forms\Components\Select::make('target_account_id')
                                        ->label('Hedef Hesap')
                                        ->options(function () use ($record) {
                                            return Account::where('id', '!=', $record->id)
                                                ->where('status', true)
                                                ->where('type', Account::TYPE_BANK_ACCOUNT)
                                                ->get()
                                                ->mapWithKeys(function ($account) {
                                                    // Bakiyeyi doğrudan account'tan al
                                                    $balance = $account->balance;
                                                    
                                                    // Bakiyeyi formatla
                                                    $formattedBalance = number_format($balance, 2, ',', '.') . " {$account->currency}";
                                                    
                                                    return [
                                                        $account->id => "{$account->name} ({$formattedBalance})"
                                                    ];
                                                });
                                        })
                                        ->required()
                                        ->searchable()
                                        ->native(false)
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set, $get) use ($record) {
                                            // Önce tüm değerleri temizle
                                            $set('source_amount', null);
                                            $set('target_amount', null);
                                            $set('exchange_rate', null);

                                            // Sonra yeni kur hesapla
                                            if (!$state) return;
                                            
                                            $targetAccount = Account::find($state);
                                            if (!$targetAccount) return;

                                            try {
                                                $date = $get('transaction_date') 
                                                    ? Carbon::parse($get('transaction_date'))
                                                    : now();

                                                $rates = $this->currencyService->getExchangeRates($date);
                                                if (!$rates) throw new \Exception('Kur bilgisi alınamadı');

                                                // Kur hesapla
                                                if ($targetAccount->currency === $record->currency) {
                                                    $crossRate = 1;
                                                } elseif ($record->currency === 'TRY') {
                                                    $crossRate = 1 / $rates[$targetAccount->currency]['selling'];
                                                } elseif ($targetAccount->currency === 'TRY') {
                                                    $crossRate = $rates[$record->currency]['buying'];
                                                } else {
                                                    $crossRate = $rates[$record->currency]['buying'] / $rates[$targetAccount->currency]['selling'];
                                                }

                                                $set('exchange_rate', number_format($crossRate, 4, '.', ''));
                                            } catch (\Exception $e) {
                                                Notification::make()
                                                    ->title('Kur bilgisi alınamadı')
                                                    ->warning()
                                                    ->send();
                                            }
                                        })
                                        ->columnSpan(6),

                                    Forms\Components\TextInput::make('source_balance')
                                        ->label('Mevcut Bakiye')
                                        ->default(number_format($record->balance, 2, ',', '.') . " {$record->currency}")
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->columnSpan(3),

                                    Forms\Components\DatePicker::make('transaction_date')
                                        ->label('İşlem Tarihi')
                                        ->default(now())
                                        ->maxDate(now())
                                        ->required()
                                        ->native(false)
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set, $get) use ($record) {
                                            // Hedef hesap seçili değilse çık
                                            $targetAccountId = $get('target_account_id');
                                            if (!$targetAccountId) return;
                                            
                                            $targetAccount = Account::find($targetAccountId);
                                            if (!$targetAccount) return;

                                            try {
                                                $date = Carbon::parse($state);
                                                $rates = $this->currencyService->getExchangeRates($date);
                                                if (!$rates) throw new \Exception('Kur bilgisi alınamadı');

                                                // Yeni tarihe göre kur hesapla
                                                if ($targetAccount->currency === $record->currency) {
                                                    $crossRate = 1;
                                                } elseif ($record->currency === 'TRY') {
                                                    $crossRate = 1 / $rates[$targetAccount->currency]['selling'];
                                                } elseif ($targetAccount->currency === 'TRY') {
                                                    $crossRate = $rates[$record->currency]['buying'];
                                                } else {
                                                    $crossRate = $rates[$record->currency]['buying'] / $rates[$targetAccount->currency]['selling'];
                                                }

                                                $set('exchange_rate', number_format($crossRate, 4, '.', ''));

                                                $sourceAmount = (float) $get('source_amount');
                                                if ($sourceAmount > 0) {
                                                    $targetAmount = number_format($sourceAmount * $crossRate, 4, '.', '');
                                                    $set('target_amount', $targetAmount);
                                                }
                                            } catch (\Exception $e) {
                                                Notification::make()
                                                    ->title('Kur bilgisi alınamadı')
                                                    ->warning()
                                                    ->send();
                                            }
                                        })
                                        ->columnSpan(3),

                                    Forms\Components\TextInput::make('source_amount')
                                        ->label(fn () => "Gönderilecek Miktar ({$record->currency})")
                                        ->required()
                                        ->numeric(4)
                                        ->minValue(0.0001)
                                        ->prefix($record->currency)
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set, $get) {
                                            if (!$state) return;
                                            
                                            $exchangeRate = (float) $get('exchange_rate');
                                            if ($exchangeRate > 0) {
                                                // Alınacak miktarı hesapla ve 4 basamağa yuvarla
                                                $targetAmount = number_format($state * $exchangeRate, 4, '.', '');
                                                $set('target_amount', $targetAmount);
                                            }
                                        })
                                        ->columnSpan(3),

                                    Forms\Components\TextInput::make('target_amount')
                                        ->label(function ($get) {
                                            if (!$get('target_account_id')) return "Alınacak Miktar";
                                            $targetAccount = Account::find($get('target_account_id'));
                                            return "Alınacak Miktar (" . ($targetAccount?->currency ?? '') . ")";
                                        })
                                        ->prefix(fn ($get) => Account::find($get('target_account_id'))?->currency ?? '')
                                        ->numeric(4)
                                        ->columnSpan(3),

                                    Forms\Components\TextInput::make('exchange_rate')
                                        ->label('Dönüşüm Kuru')
                                        ->helperText(function ($get) use ($record) {
                                            if (!$get('target_account_id')) return null;
                                            $targetAccount = Account::find($get('target_account_id'));
                                            if (!$targetAccount || $targetAccount->currency === $record->currency) return null;
                                            return "1 {$record->currency} = ? {$targetAccount->currency}";
                                        })
                                        ->numeric(4)
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set, $get) {
                                            if (!$state) return;

                                            // Gönderilecek miktar varsa, yeni kur ile alınacak miktarı hesapla
                                            $sourceAmount = (float) $get('source_amount');
                                            if ($sourceAmount > 0) {
                                                $targetAmount = number_format($sourceAmount * (float) $state, 4, '.', '');
                                                $set('target_amount', $targetAmount);
                                            }
                                        })
                                        ->columnSpan(6),
                                ])
                                ->columns(6),
                        ];
                    })
                    ->requiresConfirmation()
                    ->action(function (array $data, Account $record): void {
                        try {
                            $targetAccount = Account::findOrFail($data['target_account_id']);
                            
                            // Transfer işlemi için gerekli verileri hazırla
                            $sourceAmount = (float) $data['source_amount'];
                            $targetAmount = (float) $data['target_amount'];
                            // Exchange rate sadece farklı para birimleri için gerekli
                            $exchangeRate = $record->currency !== $targetAccount->currency 
                                ? (float) $data['exchange_rate']
                                : 1;
                            $transactionDate = $data['transaction_date'];
                            
                            // Kaynak hesapta yeterli bakiye var mı kontrol et
                            if ($sourceAmount > $record->balance) {
                                throw new \Exception("Kaynak hesapta yeterli bakiye bulunmamaktadır.");
                            }
                            
                            // Transfer kategorisini bul veya oluştur
                            $transferCategory = Category::firstOrCreate(
                                ['user_id' => auth()->id(), 'name' => 'Transfer', 'type' => 'transfer'],
                                ['description' => 'Hesaplar arası transfer işlemleri']
                            );
                            
                            // Transaction oluştur
                            DB::transaction(function () use ($record, $targetAccount, $sourceAmount, $targetAmount, $exchangeRate, $transactionDate, $transferCategory) {
                                // TRY karşılıklarını hesapla
                                $sourceTryRate = $record->currency === 'TRY' 
                                    ? 1 
                                    : ($this->currencyService->getExchangeRate($record->currency, Carbon::parse($transactionDate))['buying'] ?? 1);
                                
                                $targetTryRate = $targetAccount->currency === 'TRY'
                                    ? 1
                                    : ($this->currencyService->getExchangeRate($targetAccount->currency, Carbon::parse($transactionDate))['buying'] ?? 1);
                                
                                $sourceTryEquivalent = $sourceAmount * $sourceTryRate;
                                $targetTryEquivalent = $targetAmount * $targetTryRate;
                                
                                // Transfer açıklaması
                                $description = "Transfer: {$record->name} -> {$targetAccount->name}";
                                if ($record->currency !== $targetAccount->currency) {
                                    $exchangeRate = round($exchangeRate, 6);
                                    $description .= " (Kur: 1 {$record->currency} = {$exchangeRate} {$targetAccount->currency})";
                                }
                                
                                // Kaynak hesaptan para çıkışı
                                $sourceTransaction = Transaction::create([
                                    'user_id' => auth()->id(),
                                    'account_id' => $record->id,
                                    'amount' => -$sourceAmount,
                                    'currency' => $record->currency,
                                    'description' => $description,
                                    'date' => $transactionDate,
                                    'type' => Transaction::TYPE_TRANSFER,
                                    'status' => 'completed',
                                    'destination_account_id' => $targetAccount->id,
                                    'exchange_rate' => $sourceTryRate, // TRY'ye çevrim kuru
                                    'try_equivalent' => -$sourceTryEquivalent, // TRY karşılığı (negatif)
                                    'category_id' => $transferCategory->id,
                                ]);
                                
                                // Kaynak hesabın bakiyesini güncelle
                                $record->balance -= $sourceAmount;
                                $record->save();
                                
                                // Hedef hesaba para girişi
                                $targetTransaction = Transaction::create([
                                    'user_id' => auth()->id(),
                                    'account_id' => $targetAccount->id,
                                    'amount' => $targetAmount,
                                    'currency' => $targetAccount->currency,
                                    'description' => $description,
                                    'date' => $transactionDate,
                                    'type' => Transaction::TYPE_TRANSFER,
                                    'status' => 'completed',
                                    'reference_id' => $sourceTransaction->id,
                                    'source_account_id' => $record->id,
                                    'exchange_rate' => $targetTryRate, // TRY'ye çevrim kuru
                                    'try_equivalent' => $targetTryEquivalent, // TRY karşılığı (pozitif)
                                    'category_id' => $transferCategory->id,
                                ]);
                                
                                // Kaynak transaction'ı güncelle
                                $sourceTransaction->reference_id = $targetTransaction->id;
                                $sourceTransaction->save();
                                
                                // Hedef hesabın bakiyesini güncelle
                                $targetAccount->balance += $targetAmount;
                                $targetAccount->save();
                            });

                            Notification::make()
                                ->title('Transfer başarılı')
                                ->body(function () use ($record, $targetAccount, $sourceAmount, $targetAmount) {
                                    if ($record->currency === $targetAccount->currency) {
                                        return "{$sourceAmount} {$record->currency} transfer edildi.";
                                    } else {
                                        $exchangeRate = round($targetAmount / $sourceAmount, 6);
                                        return "{$sourceAmount} {$record->currency} gönderildi, {$targetAmount} {$targetAccount->currency} alındı. (Kur: 1 {$record->currency} = {$exchangeRate} {$targetAccount->currency})";
                                    }
                                })
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Transfer başarısız')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (Account $record): bool => 
                        Account::where('id', '!=', $record->id)
                            ->where('status', true)
                            ->where('type', Account::TYPE_BANK_ACCOUNT)
                            ->exists() && 
                        $record->balance > 0
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Seçili Hesapları Sil'),
                ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Banka Hesabı Oluştur')
                    ->modalHeading('Yeni Banka Hesabı')
                    ->modalSubmitActionLabel('Oluştur')
                    ->modalCancelActionLabel('İptal')
                    ->form($this->getFormSchema())
                    ->createAnother(false)
                    ->using(function (array $data) {
                        $accountData = AccountData::fromArray([
                            'name' => $data['name'],
                            'type' => Account::TYPE_BANK_ACCOUNT,
                            'currency' => $data['currency'],
                            'balance' => $data['balance'],
                            'status' => $data['status'],
                            'details' => [
                                'bank_name' => $data['details']['bank_name'],
                                'account_number' => $data['details']['account_number'] ?? null,
                                'iban' => $data['details']['iban'] ?? null,
                                'branch_code' => $data['details']['branch_code'] ?? null,
                                'branch_name' => $data['details']['branch_name'] ?? null,
                            ],
                        ]);
                        return $this->accountService->createAccount($accountData);
                    }),

                Tables\Actions\Action::make('atm_operations')
                    ->label('Bankamatik İşlemleri')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->modalHeading('Bankamatik İşlemleri')
                    ->modalDescription('Para yatırma veya çekme işlemi yapabilirsiniz.')
                    ->form([
                        Forms\Components\Select::make('account_id')
                            ->label('Hesap')
                            ->options(function () {
                                return Account::query()
                                    ->where('user_id', auth()->id())
                                    ->where('type', Account::TYPE_BANK_ACCOUNT)
                                    ->where('status', true)
                                    ->get()
                                    ->mapWithKeys(fn ($account) => [
                                        $account->id => "{$account->name} ({$account->currency} - Bakiye: {$account->formatted_balance})"
                                    ]);
                            })
                            ->required()
                            ->live()
                            ->native(false),

                        Forms\Components\Select::make('operation_type')
                            ->label('İşlem Türü')
                            ->options([
                                'deposit' => 'Para Yatır',
                                'withdraw' => 'Para Çek'
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('amount')
                            ->label('Tutar')
                            ->numeric()
                            ->required()
                            ->minValue(0.01),

                        Forms\Components\DatePicker::make('date')
                            ->label('İşlem Tarihi')
                            ->default(now())
                            ->required()
                            ->native(false),

                        Forms\Components\Textarea::make('description')
                            ->label('Açıklama')
                            ->rows(2),
                    ])
                    ->action(function (array $data): void {
                        try {
                            DB::beginTransaction();

                            $account = Account::findOrFail($data['account_id']);
                            $amount = (float) $data['amount'];
                            
                            // İşlem türüne göre tutarı ayarla
                            $transactionAmount = $data['operation_type'] === 'deposit' ? $amount : -$amount;
                            
                            // Çekme işlemi için bakiye kontrolü
                            if ($data['operation_type'] === 'withdraw' && $account->balance < $amount) {
                                throw new \Exception('Yetersiz bakiye.');
                            }

                            // İşlemi kaydet
                            Transaction::create([
                                'user_id' => auth()->id(),
                                'type' => $data['operation_type'] === 'deposit' ? 'atm_deposit' : 'atm_withdraw',
                                'amount' => $amount,
                                'currency' => $account->currency,
                                'date' => $data['date'],
                                'description' => $data['description'] ?: sprintf(
                                    '%s - %s',
                                    $account->name,
                                    $account->details['bank_name'] ?? 'Banka'
                                ),
                                'payment_method' => PaymentMethodEnum::CASH->value,
                                'status' => 'completed',
                                'source_account_id' => $data['operation_type'] === 'withdraw' ? $account->id : null,
                                'destination_account_id' => $data['operation_type'] === 'deposit' ? $account->id : null,
                                'try_equivalent' => $account->currency === 'TRY' 
                                    ? $amount
                                    : $amount * ($this->currencyService->getExchangeRate($account->currency)['buying'] ?? 1),
                            ]);

                            // Hesap bakiyesini güncelle
                            $account->balance += $transactionAmount;
                            $account->save();

                            DB::commit();

                            Notification::make()
                                ->title('İşlem başarılı')
                                ->body(abs($amount) . " {$account->currency} tutarında " . 
                                    ($data['operation_type'] === 'deposit' ? 'para yatırma' : 'para çekme') . 
                                    ' işlemi gerçekleştirildi.')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            DB::rollBack();

                            Notification::make()
                                ->title('İşlem başarısız')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }

    /**
     * Hesap form şemasını oluşturur
     * 
     * @return array Form bileşenleri dizisi
     */
    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Hesap Adı')
                        ->required()
                        ->columnSpan(6),

                    Forms\Components\TextInput::make('details.bank_name')
                        ->label('Banka Adı')
                        ->required()
                        ->columnSpan(3),

                    Forms\Components\TextInput::make('details.branch_name')
                        ->label('Şube Adı')
                        ->columnSpan(3),

                    Forms\Components\TextInput::make('details.account_number')
                        ->label('Hesap Numarası')
                        ->columnSpan(3),

                    Forms\Components\TextInput::make('details.iban')
                        ->label('IBAN')
                        ->maxLength(34)
                        ->columnSpan(3),

                    Forms\Components\Select::make('currency')
                        ->label('Para Birimi')
                        ->options([
                            'TRY' => 'Türk Lirası',
                            'USD' => 'Amerikan Doları',
                            'EUR' => 'Euro',
                            'GBP' => 'İngiliz Sterlini',
                        ])
                        ->required()
                        ->native(false)
                        ->columnSpan(3),

                    Forms\Components\TextInput::make('balance')
                        ->label('Bakiye')
                        ->numeric()
                        ->default(0)
                        ->columnSpan(3),

                    Forms\Components\Toggle::make('status')
                        ->label('Aktif')
                        ->default(true)
                        ->columnSpan(6),
                ])
                ->columns(6),
        ];
    }

    /**
     * Bileşen görünümünü render eder
     * 
     * @return View
     */
    public function render(): View
    {
        return view('livewire.account.bank-account-manager');
    }
}