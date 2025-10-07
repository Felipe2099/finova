<?php

namespace App\Livewire\Account;

use App\Models\Account;
use App\Models\CryptoWallet;
use App\Services\Account\Implementations\AccountService;
use App\Services\Currency\CurrencyService;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use App\DTOs\Account\AccountData;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use App\Models\Transaction;
use Closure;

/**
 * Crypto Wallet Management Component
 * 
 * Customized Livewire component for managing cryptocurrency wallets.
 * Provides detailed operations and features for crypto wallets.
 * 
 * Features:
 * - Create/Edit/Delete crypto wallets
 * - Manage platform and wallet address
 * - Currency conversions (USD/TRY)
 * - Advanced filtering and search
 * - View transaction history
 */
class CryptoWalletManager extends Component implements Forms\Contracts\HasForms, Tables\Contracts\HasTable
{
    use Forms\Concerns\InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;

    /** @var AccountService Service for account operations */
    private AccountService $accountService;

    /** @var CurrencyService Service for currency conversions */
    private CurrencyService $currencyService;

    /**
     * Component boot.
     * 
     * @param AccountService $accountService Account service
     * @param CurrencyService $currencyService Currency service
     * @return void
     */
    public function boot(AccountService $accountService, CurrencyService $currencyService): void 
    {
        $this->accountService = $accountService;
        $this->currencyService = $currencyService;
    }

    /**
     * Configure the crypto wallet list table.
     * 
     * @param Tables\Table $table Filament table configuration
     * @return Tables\Table
     */
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(
                Account::query()
                    ->where('type', Account::TYPE_CRYPTO_WALLET)
            )
            ->emptyStateHeading('Kripto Cüzdanı Bulunamadı')
            ->emptyStateDescription('Başlamak için yeni bir kripto cüzdanı oluşturun.')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Cüzdan Adı')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('details.platform')
                    ->label('Platform'),
                Tables\Columns\TextColumn::make('currency')
                    ->label('Para Birimi')
                    ->badge(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Bakiye')
                    ->formatStateUsing(function (Account $record) {
                        // Format balance with 2 decimals
                        return number_format($record->balance, 2) . ' ' . $record->currency;
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('try_equivalent')
                    ->label('TRY Karşılığı')
                    ->getStateUsing(function (Account $record) {
                        // If account is already TRY, return balance directly
                        if ($record->currency === 'TRY') {
                            return (float) $record->balance;
                        }
                        
                        try {
                            // Calculate TRY equivalent for USDT/USD
                            $exchangeRateData = $this->currencyService->getExchangeRate('USD');
                            
                            if (!$exchangeRateData) {
                                return 0;
                            }
                            
                            $balance = (float) $record->balance;
                            $exchangeRate = (float) $exchangeRateData['buying'];
                            
                            // Calculate and return TRY equivalent
                            return $balance * $exchangeRate;
                        } catch (\Exception $e) {
                            return 0;
                        }
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
                            ->title("{$record->name} kripto cüzdanı {$statusText} duruma getirildi.")
                            ->success()
                            ->send();
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('platform')
                    ->label('Platform')
                    ->options([
                        'Binance' => 'Binance',
                        'Bybit' => 'Bybit',
                        'Kraken' => 'Kraken',
                        'Kucoin' => 'Kucoin',
                        'Gateio' => 'Gate.io',
                        'Coinbase' => 'Coinbase',
                        'MetaMask' => 'MetaMask',
                        'Trust Wallet' => 'Trust Wallet',
                        'Other' => 'Diğer',
                    ])
                    ->multiple()
                    ->native(false)
                    ->query(function (Builder $query, array $data): void {
                        if (!empty($data['values'])) {
                            $query->whereIn('details->platform', $data['values']);
                        }
                    }),
                Tables\Filters\SelectFilter::make('currency')
                    ->label('Para Birimi')
                    ->options([
                        'USDT' => 'USDT (Tether)',
                    ])
                    ->native(false),
                Tables\Filters\TernaryFilter::make('status')
                    ->label('Durum')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Kripto Cüzdan Düzenle')
                    ->modalSubmitActionLabel('Kaydet')
                    ->modalCancelActionLabel('İptal')
                    ->visible(fn () => auth()->user()->can('crypto_wallets.edit'))
                    ->form($this->getFormSchema()),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Kripto Cüzdanı Sil')
                    ->modalDescription('Bu kripto cüzdanını silmek istediğinize emin misiniz?')
                    ->modalSubmitActionLabel('Sil')
                    ->modalCancelActionLabel('İptal')
                    ->successNotificationTitle('Kripto cüzdanı silindi')
                    ->visible(fn () => auth()->user()->can('crypto_wallets.delete'))
                    ->label('Sil')
                    ->using(function (Account $record) {
                        return $this->accountService->delete($record);
                    }),
                Tables\Actions\Action::make('transfer')
                    ->label('Transfer')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->modalHeading('Kripto Cüzdandan Banka Hesabına Transfer')
                    ->modalDescription('Bunu yapmak istediğinizden emin misiniz?')
                    ->visible(function (Account $record): bool { 
                        return auth()->user()->can('crypto_wallets.transfer') &&
                               Account::where('status', true)
                                   ->where('type', Account::TYPE_BANK_ACCOUNT)
                                   ->exists() && 
                               $record->balance > 0 &&
                               $record->status;
                    })
                    ->form(function (Account $record) {
                        return [
                            Forms\Components\Grid::make()
                                ->schema([
                                    Forms\Components\Select::make('target_account_id')
                                        ->label('Hedef Banka Hesabı')
                                        ->options(function () use ($record) {
                                            return Account::where('status', true)
                                                ->where('type', Account::TYPE_BANK_ACCOUNT)
                                                ->get()
                                                ->mapWithKeys(function ($account) {
                                                    // Read balance directly from account
                                                    $balance = $account->balance;
                                                    
                                                    // Format the balance
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
                                            // Reset all values first
                                            $set('source_amount', null);
                                            $set('target_amount', null);
                                            $set('exchange_rate', null);

                                            // Then calculate the new rate
                                            if (!$state) return;
                                            
                                            $targetAccount = Account::find($state);
                                            if (!$targetAccount) return;

                                            try {
                                                $date = $get('transaction_date') 
                                                    ? Carbon::parse($get('transaction_date'))
                                                    : now();

                                                $rates = $this->currencyService->getExchangeRates($date);
                                                if (!$rates) throw new \Exception('Kur bilgisi alınamadı');

                                                // Calculate the cross rate
                                                if ($targetAccount->currency === $record->currency) {
                                                    // Same currency (USD -> USD) should be rate 1
                                                    $crossRate = 1;
                                                } elseif ($targetAccount->currency === 'TRY') {
                                                    // USD -> TRY uses USD buying rate
                                                    $crossRate = $rates['USD']['buying'];
                                                } else {
                                                    // USD -> Other: USD buying / target selling
                                                    $crossRate = $rates['USD']['buying'] / $rates[$targetAccount->currency]['selling'];
                                                }

                                                // Ensure USD -> USD conversion is always 1
                                                if ($record->currency === 'USD' && $targetAccount->currency === 'USD') {
                                                    $crossRate = 1;
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
                                            // Exit if no target account selected
                                            $targetAccountId = $get('target_account_id');
                                            if (!$targetAccountId) return;
                                            
                                            $targetAccount = Account::find($targetAccountId);
                                            if (!$targetAccount) return;

                                            try {
                                                $date = Carbon::parse($state);
                                                $rates = $this->currencyService->getExchangeRates($date);
                                                if (!$rates) throw new \Exception('Kur bilgisi alınamadı');

                                                // Recalculate cross rate for the new date
                                                if ($targetAccount->currency === $record->currency) {
                                                    // Same currency (USD -> USD) should be rate 1
                                                    $crossRate = 1;
                                                } elseif ($targetAccount->currency === 'TRY') {
                                                    // USD -> TRY uses USD buying rate
                                                    $crossRate = $rates['USD']['buying'];
                                                } else {
                                                    // USD -> Other: USD buying / target selling
                                                    $crossRate = $rates['USD']['buying'] / $rates[$targetAccount->currency]['selling'];
                                                }

                                                // Ensure USD -> USD conversion is always 1
                                                if ($record->currency === 'USD' && $targetAccount->currency === 'USD') {
                                                    $crossRate = 1;
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
                                        ->afterStateUpdated(function ($state, callable $set, $get) use ($record) {
                                            if (!$state) return;
                                            
                                            // Balance check
                                            if ($state > $record->balance) {
                                                Notification::make()
                                                    ->title('Yetersiz Bakiye')
                                                    ->body("Gönderilecek miktar ({$state} {$record->currency}) mevcut bakiyeden ({$record->balance} {$record->currency}) fazla olamaz.")
                                                    ->warning()
                                                    ->send();
                                                $set('source_amount', null);
                                                return;
                                            }
                                            
                                            $exchangeRate = (float) $get('exchange_rate');
                                            if ($exchangeRate > 0) {
                                                // Calculate target amount (4 decimals)
                                                $targetAmount = number_format($state * $exchangeRate, 4, '.', '');
                                                $set('target_amount', $targetAmount);
                                            }
                                        })
                                        ->rules([
                                            'required',
                                            'numeric',
                                            'min:0.0001',
                                            'lte:' . $record->balance,
                                        ])
                                        ->validationMessages([
                                            'lte' => 'Gönderilecek miktar (:input ' . $record->currency . ') mevcut bakiyeden (' . $record->balance . ' ' . $record->currency . ') fazla olamaz.',
                                        ])
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

                                            // If there is a source amount, calculate target with new rate
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
                            
                            // Prepare the required data for the transfer
                            $sourceAmount = (float) $data['source_amount'];
                            $targetAmount = (float) $data['target_amount'];
                            $exchangeRate = (float) $data['exchange_rate'];
                            $transactionDate = $data['transaction_date'];
                            
                            // Balance check
                            if ($sourceAmount > $record->balance) {
                                throw new \Exception("Yetersiz bakiye. Transfer edilebilir maksimum tutar: " . number_format($record->balance, 2) . " {$record->currency}");
                            }
                            
                            // Transfer category
                            $transferCategory = Category::firstOrCreate(
                                ['user_id' => auth()->id(), 'name' => 'Transfer', 'type' => 'transfer'],
                                ['description' => 'Hesaplar arası transfer işlemleri']
                            );
                            
                            // Create the transaction
                            DB::transaction(function () use ($record, $targetAccount, $sourceAmount, $targetAmount, $exchangeRate, $transactionDate, $transferCategory) {
                                // Calculate the TRY equivalents
                                $sourceTryRate = $record->currency === 'TRY' 
                                    ? 1 
                                    : ($this->currencyService->getExchangeRate($record->currency, Carbon::parse($transactionDate))['buying'] ?? 1);
                                
                                $targetTryRate = $targetAccount->currency === 'TRY'
                                    ? 1
                                    : ($this->currencyService->getExchangeRate($targetAccount->currency, Carbon::parse($transactionDate))['buying'] ?? 1);
                                
                                $sourceTryEquivalent = $sourceAmount * $sourceTryRate;
                                $targetTryEquivalent = $targetAmount * $targetTryRate;
                                
                                // Transfer description
                                $description = "Transfer: {$record->name} -> {$targetAccount->name}";
                                if ($record->currency !== $targetAccount->currency) {
                                    $exchangeRate = round($exchangeRate, 6);
                                    $description .= " (Kur: 1 {$record->currency} = {$exchangeRate} {$targetAccount->currency})";
                                }
                                
                                // Source account withdrawal
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
                                    'exchange_rate' => $sourceTryRate, // TRY conversion rate
                                    'try_equivalent' => -$sourceTryEquivalent, // TRY equivalent (negative)
                                    'category_id' => $transferCategory->id,
                                ]);
                                
                                // Update the source account balance
                                $record->balance -= $sourceAmount;
                                $record->save();
                                
                                // Destination account deposit
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
                                    'exchange_rate' => $targetTryRate, // TRY conversion rate
                                    'try_equivalent' => $targetTryEquivalent, // TRY equivalent (positive)
                                    'category_id' => $transferCategory->id,
                                ]);
                                
                                // Update the source transaction
                                $sourceTransaction->reference_id = $targetTransaction->id;
                                $sourceTransaction->save();
                                
                                // Update the target account balance
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
                    ->visible(function (Account $record): bool { 
                        return auth()->user()->can('crypto_wallets.transfer') &&
                               Account::where('status', true)
                                   ->where('type', Account::TYPE_BANK_ACCOUNT)
                                   ->exists() && 
                               $record->balance > 0 &&
                               $record->status;
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Seçili Cüzdanları Sil')
                        ->visible(fn () => auth()->user()->can('crypto_wallets.delete')),
                ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Kripto Cüzdan Oluştur')
                    ->modalHeading('Yeni Kripto Cüzdan')
                    ->modalSubmitActionLabel('Oluştur')
                    ->modalCancelActionLabel('İptal')
                    ->visible(fn () => auth()->user()->can('crypto_wallets.create'))
                    ->form($this->getFormSchema())
                    ->createAnother(false)
                    ->mutateFormDataUsing(function (array $data) {
                        return [
                            'user_id' => auth()->id(),
                            'name' => $data['name'],
                            'type' => Account::TYPE_CRYPTO_WALLET,
                            'currency' => $data['currency'],
                            'balance' => $data['balance'] ?? 0,
                            'details' => [
                                'platform' => $data['details']['platform'],
                                'wallet_address' => $data['details']['wallet_address'],
                            ],
                            'status' => $data['status'] ?? true,
                        ];
                    })
                    ->using(function (array $data) {
                        $accountData = AccountData::fromArray($data);
                        return $this->accountService->createCryptoWallet($accountData);
                    }),
            ]);
    }

    /**
     * Create the account form schema
     * 
     * @return array Form components array
     */
    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('Cüzdan Adı')
                ->required(),
            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\Select::make('details.platform')
                        ->label('Platform')
                        ->options([
                            'Binance' => 'Binance',
                            'Gateio' => 'Gate.io',
                            'Coinbase' => 'Coinbase',
                            'Bybit' => 'Bybit',
                            'Kraken' => 'Kraken',
                            'Kucoin' => 'Kucoin',
                            'MetaMask' => 'MetaMask',
                            'Trust Wallet' => 'Trust Wallet',
                            'Other' => 'Diğer',
                        ])
                        ->required()
                        ->native(false),
                    Forms\Components\TextInput::make('details.wallet_address')
                        ->label('Cüzdan Adresi')
                        ->required(),
                ])
                ->columns(2),
            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\Select::make('currency')
                        ->label('Para Birimi')
                        ->options([
                            'USD' => 'USDT (Tether)',
                        ])
                        ->default('USD')
                        ->required()
                        ->native(false),
                    Forms\Components\TextInput::make('balance')
                        ->label('Bakiye')
                        ->numeric()
                        ->default(0),
                ])
                ->columns(2),
            Forms\Components\Toggle::make('status')
                ->label('Aktif')
                ->default(true),
        ];
    }

    /**
     * Render the component view
     * 
     * @return View
     */
    public function render(): View
    {
        return view('livewire.account.crypto-wallet-manager');
    }
} 