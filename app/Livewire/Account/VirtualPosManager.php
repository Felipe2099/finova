<?php

namespace App\Livewire\Account;

use App\Models\Account;
use App\Models\VirtualPosAccount;
use App\Services\Account\Implementations\AccountService;
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
use App\Services\Currency\CurrencyService;
use Closure;

/**
 * Virtual POS Management Component
 * 
 * Customized Livewire component for managing virtual POS accounts.
 * Provides detailed operations and features for virtual POS accounts.
 * 
 * Features:
 * - Create/Edit/Delete virtual POS
 * - Commission rate management
 * - Currency conversions
 * - Advanced filtering and search
 * - View transaction history
 */
class VirtualPosManager extends Component implements Forms\Contracts\HasForms, Tables\Contracts\HasTable
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
     * Configure the virtual POS list table.
     * 
     * @param Tables\Table $table Filament table configuration
     * @return Tables\Table
     */
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(
                Account::query()
                    ->where('type', Account::TYPE_VIRTUAL_POS)
            )
            ->emptyStateHeading('Sanal POS Bulunamadı')
            ->emptyStateDescription('Başlamak için yeni bir sanal POS oluşturun.')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('POS Adı')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('details.provider')
                    ->label('Sağlayıcı'),
                Tables\Columns\TextColumn::make('currency')
                    ->label('Para Birimi')
                    ->badge(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Bakiye')
                    ->formatStateUsing(fn (Account $record) => $record->formatted_balance)
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('status')
                    ->label('Durum')
                    ->onColor('success')
                    ->offColor('danger')
                    ->afterStateUpdated(function (Account $record, $state) {
                        $statusText = $state ? 'aktif' : 'pasif';
                        Notification::make()
                            ->title("{$record->name} sanal POS {$statusText} duruma getirildi.")
                            ->success()
                            ->send();
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider')
                    ->multiple()
                    ->native(false)
                    ->query(function (Builder $query, array $data): void {
                        if (!empty($data['value'])) {
                            $query->where('details->provider', $data['value']);
                        }
                    }),
                Tables\Filters\SelectFilter::make('currency')
                    ->multiple()
                    ->native(false),
                Tables\Filters\TernaryFilter::make('status')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Sanal POS Düzenle')
                    ->modalSubmitActionLabel('Kaydet')
                    ->modalCancelActionLabel('İptal')
                    ->visible(fn () => auth()->user()->can('virtual_pos.edit'))
                    ->form($this->getFormSchema())
                    ->using(function (Account $record, array $data) {
                        $accountData = new AccountData(
                            id: $record->id,
                            user_id: $record->user_id, // Keep original user_id
                            name: $data['name'],
                            type: Account::TYPE_VIRTUAL_POS, // Explicitly set type
                            currency: $data['currency'],
                            balance: $data['balance'],
                            description: $data['description'] ?? null, // Add description
                            status: $data['status'],
                            details: $data['details'] ?? null, // Pass full details array
                        );
                        
                        return $this->accountService->updateAccount($record, $accountData);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Sanal POS Sil')
                    ->modalDescription('Bu sanal POS\'u silmek istediğinize emin misiniz?')
                    ->modalSubmitActionLabel('Sil')
                    ->modalCancelActionLabel('İptal')
                    ->successNotificationTitle('Sanal POS silindi')
                    ->visible(fn () => auth()->user()->can('virtual_pos.delete'))
                    ->label('Sil')
                    ->using(function (Account $record) {
                        return $this->accountService->delete($record);
                    }),
                Tables\Actions\Action::make('transfer')
                    ->label('Transfer')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->modalHeading('POS\'tan Banka Hesabına Transfer')
                    ->modalDescription('Bunu yapmak istediğinizden emin misiniz?')
                    ->visible(function (Account $record): bool { 
                        return auth()->user()->can('virtual_pos.transfer') &&
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

                                                // Calculate the cross rate
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
                                        ->default(function () use ($record) {
                                            return number_format($record->balance, 2, ',', '.') . " {$record->currency}";
                                        })
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
                                            
                                            $targetAccountId = $get('target_account_id');
                                            if (!$targetAccountId) return;
                                            
                                            $targetAccount = Account::find($targetAccountId);
                                            if (!$targetAccount) return;

                                            try {
                                                $date = $get('transaction_date') 
                                                    ? Carbon::parse($get('transaction_date'))
                                                    : now();

                                                $rates = $this->currencyService->getExchangeRates($date);
                                                if (!$rates) throw new \Exception('Kur bilgisi alınamadı');

                                                // Calculate the cross rate
                                                if ($targetAccount->currency === $record->currency) {
                                                    $crossRate = 1;
                                                } elseif ($record->currency === 'TRY') {
                                                    $crossRate = 1 / $rates[$targetAccount->currency]['selling'];
                                                } elseif ($targetAccount->currency === 'TRY') {
                                                    $crossRate = $rates[$record->currency]['buying'];
                                                } else {
                                                    // First convert to TRY, then convert to target currency
                                                    $tryAmount = $state * $rates[$record->currency]['buying'];
                                                    $crossRate = $tryAmount / ($state * $rates[$targetAccount->currency]['selling']);
                                                }

                                                $set('exchange_rate', number_format($crossRate, 4, '.', ''));
                                                
                                                // Calculate the amount to be received
                                                $targetAmount = number_format($state * $crossRate, 4, '.', '');
                                                $set('target_amount', $targetAmount);
                                            } catch (\Exception $e) {
                                                Notification::make()
                                                    ->title('Kur bilgisi alınamadı')
                                                    ->warning()
                                                    ->send();
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
                                        ->afterStateUpdated(function ($state, callable $set, $get) use ($record) {
                                            if (!$state) return;

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
                            
                            // Balance check - validate in the same currency
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
                                // Calculate TRY equivalents
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
                                    'exchange_rate' => $sourceTryRate,
                                    'try_equivalent' => -$sourceTryEquivalent,
                                    'category_id' => $transferCategory->id,
                                ]);
                                
                                // Update source account balance
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
                                    'exchange_rate' => $targetTryRate,
                                    'try_equivalent' => $targetTryEquivalent,
                                    'category_id' => $transferCategory->id,
                                ]);
                                
                                // Update the source transaction
                                $sourceTransaction->reference_id = $targetTransaction->id;
                                $sourceTransaction->save();
                                
                                // Update target account balance
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
                    }),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Seçili POS\'ları Sil')
                        ->visible(fn () => auth()->user()->can('virtual_pos.delete')),
                ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Sanal POS Oluştur')
                    ->modalHeading('Yeni Sanal POS')
                    ->modalSubmitActionLabel('Oluştur')
                    ->modalCancelActionLabel('İptal')
                    ->visible(fn () => auth()->user()->can('virtual_pos.create'))
                    ->form($this->getFormSchema())
                    ->createAnother(false)
                    ->using(function (array $data) {
                        $accountData = AccountData::fromArray([
                            'name' => $data['name'],
                            'type' => Account::TYPE_VIRTUAL_POS,
                            'currency' => $data['currency'],
                            'balance' => $data['balance'] ?? 0,
                            'status' => $data['status'],
                            'details' => [
                                'provider' => $data['details']['provider'],
                            ],
                        ]);
                        
                        return $this->accountService->createAccount($accountData);
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
            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('POS Adı')
                        ->required(),
                    Forms\Components\Select::make('details.provider')
                        ->label('Sağlayıcı')
                        ->options([
                            'stripe' => 'Stripe',
                            'PayTR' => 'PayTR',
                            'vallet' => 'Vallet',
                            'iyzico' => 'iyzico',
                            'Shopier' => 'Shopier',
                            'Other' => 'Diğer',
                        ])
                        ->required()
                        ->native(false),
                ])
                ->columns(2),
            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\Select::make('currency')
                        ->label('Para Birimi')
                        ->options([
                            'TRY' => 'Türk Lirası',
                            'USD' => 'Amerikan Doları',
                            'EUR' => 'Euro',
                            'GBP' => 'İngiliz Sterlini',
                        ])
                        ->required()
                        ->native(false),
                ])
                ->columns(1),
            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\TextInput::make('balance')
                        ->label('Bakiye')
                        ->numeric()
                        ->default(0),
                    Forms\Components\Select::make('status')
                        ->label('Durum')
                        ->options([
                            1 => 'Aktif',
                            0 => 'Pasif'
                        ])
                        ->default(1)
                        ->native(false),
                ])
                ->columns(2),
        ];
    }

    /**
     * Render the component view
     * 
     * @return View
     */
    public function render(): View
    {
        return view('livewire.account.virtual-pos-manager');
    }
} 