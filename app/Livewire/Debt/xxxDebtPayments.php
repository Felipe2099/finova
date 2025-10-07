<?php

// THIS FILE NOT USED NOW
declare(strict_types=1);

namespace App\Livewire\Debt;

use App\Models\{Debt, Account, Transaction};
use App\Services\Debt\Contracts\DebtServiceInterface;
use App\Enums\PaymentMethodEnum;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

/**
 * Debt Payments Component
 * 
 * This component provides functionality to manage debt payments.
 * Features:
 * - Debt payment list view
 * - Debt payment creation
 * - Debt payment editing
 * - Debt payment deletion
 */
final class DebtPayments extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public Debt $debt;
    private DebtServiceInterface $service;

    public function boot(DebtServiceInterface $service): void 
    {
        $this->service = $service;
    }

    public function mount(Debt $debt): void
    {
        $this->debt = $debt;
    }

    public function table(Tables\Table $table): Tables\Table
    {
            /*
        
        return $table
            ->query(
                Transaction::query()
                    ->where('reference_id', $this->debt->id)
                    ->whereIn('type', ['loan_payment', 'debt_payment'])
            )
            ->emptyStateHeading('Ödeme Bulunamadı')
            ->emptyStateDescription('Başlamak için yeni bir ödeme ekleyin.')
            ->columns([
                Tables\Columns\TextColumn::make('amount')
                    ->label('Ödenen Miktar')
                    ->money(function (?Transaction $record): string {
                        return $record?->currency ?? 'TRY';
                    })
                    ->suffix(fn (?Transaction $record): string => $record?->currency === 'XAU' ? ' GR' : '')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_in_currency')
                    ->label('Ödenen Tutar')
                    ->money('TRY')
                    ->prefix('₺')
                    ->sortable(),
                Tables\Columns\TextColumn::make('buy_price')
                    ->label('Alış Fiyatı')
                    ->money('TRY')
                    ->visible(function (?Transaction $record): bool {
                        return $record && $record->currency === 'XAU';
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('sell_price')
                    ->label('Satış Fiyatı')
                    ->money('TRY')
                    ->visible(function (?Transaction $record): bool {
                        return $record && $record->currency === 'XAU';
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('profit_loss')
                    ->label('Kar/Zarar')
                    ->money('TRY')
                    ->visible(function (?Transaction $record): bool {
                        return $record && $record->currency === 'XAU';
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Ödeme Yöntemi')
                    ->formatStateUsing(function (?PaymentMethodEnum $state): string {
                        return $state?->label() ?? '-';
                    })
                    ->badge()
                    ->color(function (?PaymentMethodEnum $state): string {
                        if (!$state) return 'gray';
                        return match ($state) {
                            PaymentMethodEnum::BANK => 'warning',
                            PaymentMethodEnum::CREDIT_CARD => 'info',
                            PaymentMethodEnum::CASH => 'primary',
                            PaymentMethodEnum::CRYPTO => 'success',
                            PaymentMethodEnum::VIRTUAL_POS => 'danger',
                        };
                    }),
                Tables\Columns\TextColumn::make('date')
                    ->label('Ödeme Tarihi')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->formatStateUsing(function (?string $state): string {
                        if (!$state) return '-';
                        return match ($state) {
                            'pending' => 'Bekliyor',
                            'completed' => 'Tamamlandı',
                            'cancelled' => 'İptal Edildi',
                            'failed' => 'Başarısız',
                            default => $state,
                        };
                    })
                    ->badge()
                    ->color(function (?string $state): string {
                        if (!$state) return 'gray';
                        return match ($state) {
                            'pending' => 'warning',
                            'completed' => 'success',
                            'cancelled' => 'danger',
                            'failed' => 'danger',
                            default => 'gray',
                        };
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Ödeme Yöntemi')
                    ->options(PaymentMethodEnum::toArray())
                    ->native(false)
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        'pending' => 'Bekliyor',
                        'completed' => 'Tamamlandı',
                        'cancelled' => 'İptal Edildi',
                        'failed' => 'Başarısız',
                    ])
                    ->native(false)
                    ->preload(),
            ])
            ->filtersFormColumns(1)
            ->filtersFormWidth('md')
            ->filtersTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Filtrele')
            )
            ->defaultSort('date', 'desc')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Ödemeyi Düzenle')
                    ->modalSubmitActionLabel('Güncelle')
                    ->modalCancelActionLabel('İptal')
                    ->successNotificationTitle('Ödeme güncellendi')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Tutar')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue($this->debt->remaining_amount),
                        Forms\Components\Select::make('payment_method')
                            ->label('Ödeme Yöntemi')
                            ->options(PaymentMethodEnum::toArray())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('bank_account_id', null);
                                $set('source_account_id', null);
                            }),
                        Forms\Components\Select::make('source_account_id')
                            ->label('Kaynak Hesap')
                            ->options(function (Forms\Get $get) {
                                $paymentMethod = $get('payment_method');
                                if (!$paymentMethod) return [];

                                $query = Account::query()
                                    ->where('user_id', auth()->id())
                                    ->where('status', true);

                                return $query->when($paymentMethod, function ($query) use ($paymentMethod) {
                                    return match($paymentMethod) {
                                        PaymentMethodEnum::BANK->value => $query->where('type', Account::TYPE_BANK_ACCOUNT),
                                        PaymentMethodEnum::CREDIT_CARD->value => $query->where('type', Account::TYPE_CREDIT_CARD),
                                        PaymentMethodEnum::CRYPTO->value => $query->where('type', Account::TYPE_CRYPTO_WALLET),
                                        PaymentMethodEnum::VIRTUAL_POS->value => $query->where('type', Account::TYPE_VIRTUAL_POS),
                                        default => $query->where('type', Account::TYPE_BANK_ACCOUNT),
                                    };
                                })->get()->mapWithKeys(fn ($account) => [
                                    $account->id => "{$account->name} ({$account->formatted_balance})"
                                ]);
                            })
                            ->searchable()
                            ->required(fn (Forms\Get $get): bool => $get('payment_method') !== PaymentMethodEnum::CASH->value)
                            ->visible(fn (Forms\Get $get): bool => $get('payment_method') !== PaymentMethodEnum::CASH->value),
                        Forms\Components\DatePicker::make('date')
                            ->label('Ödeme Tarihi')
                            ->required()
                            ->format('d.m.Y'),
                    ])
                    ->using(function (Transaction $record, array $data): Transaction {
                        $this->service->addPayment($this->debt, $data);
                        return $record;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Sil')
                    ->modalDescription('Bu ödemeyi silmek istediğinize emin misiniz?')
                    ->modalSubmitActionLabel('Sil')
                    ->modalCancelActionLabel('İptal')
                    ->successNotificationTitle('Ödeme silindi')
                    ->using(function (Transaction $record) {
                        $record->delete();
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Yeni Ödeme')
                    ->modalHeading('Yeni Ödeme')
                    ->modalSubmitActionLabel('Kaydet')
                    ->modalCancelActionLabel('İptal')
                    ->createAnother(false)
                    ->successNotificationTitle('Ödeme eklendi')
                    ->form(function () {
                        $form = [
                            Forms\Components\Grid::make()
                                ->schema([
                                    Forms\Components\Grid::make()
                                        ->schema([
                                            Forms\Components\TextInput::make('amount')
                                                ->label('Ödenecek Miktar')
                                                ->numeric()
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                    if ($this->debt->currency === 'XAU') {
                                                        $currentGoldPrice = $this->getCurrentGoldPrice();
                                                        $amount = $get('amount');
                                                        $set('amount_in_currency', $amount * $currentGoldPrice);
                                                        $set('sell_price', $currentGoldPrice);
                                                    }
                                                })
                                                ->maxValue($this->debt->remaining_amount)
                                                ->suffix($this->debt->currency === 'XAU' ? ' GR' : '')
                                                ->columnSpan(6),
                                            Forms\Components\TextInput::make('amount_in_currency')
                                                ->label('Ödenecek Tutar')
                                                ->numeric()
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                    if ($this->debt->currency === 'XAU') {
                                                        $currentGoldPrice = $this->getCurrentGoldPrice();
                                                        $amountInCurrency = $get('amount_in_currency');
                                                        $set('amount', $amountInCurrency / $currentGoldPrice);
                                                        $set('sell_price', $currentGoldPrice);
                                                    }
                                                })
                                                ->prefix('₺')
                                                ->columnSpan(6),
                                        ])
                                        ->columns(12)
                                        ->columnSpan(12),
                                ])
                                ->columns(12)
                                ->columnSpan(12),
                        ];

                        if ($this->debt->currency === 'XAU') {
                            $form[] = Forms\Components\Grid::make()
                                ->schema([
                                    Forms\Components\TextInput::make('sell_price')
                                        ->label('Satış Fiyatı')
                                        ->numeric()
                                        ->required()
                                        ->default($this->getCurrentGoldPrice())
                                        ->prefix('₺')
                                        ->suffix('/GR')
                                        ->columnSpan(12),
                                ])
                                ->columns(12)
                                ->columnSpan(12);
                        }

                        $form[] = Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Select::make('payment_method')
                                    ->label('Ödeme Yöntemi')
                                    ->options(PaymentMethodEnum::toArray())
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        $set('source_account_id', null);
                                    })
                                    ->columnSpan(6),
                                Forms\Components\Select::make('source_account_id')
                                    ->label('Kaynak Hesap')
                                    ->options(function (Forms\Get $get) {
                                        $paymentMethod = $get('payment_method');
                                        if (!$paymentMethod) return [];

                                        $query = Account::query()
                                            ->where('user_id', auth()->id())
                                            ->where('status', true);

                                        return $query->when($paymentMethod, function ($query) use ($paymentMethod) {
                                            return match($paymentMethod) {
                                                PaymentMethodEnum::BANK->value => $query->where('type', Account::TYPE_BANK_ACCOUNT),
                                                PaymentMethodEnum::CREDIT_CARD->value => $query->where('type', Account::TYPE_CREDIT_CARD),
                                                PaymentMethodEnum::CRYPTO->value => $query->where('type', Account::TYPE_CRYPTO_WALLET),
                                                PaymentMethodEnum::VIRTUAL_POS->value => $query->where('type', Account::TYPE_VIRTUAL_POS),
                                                default => $query->where('type', Account::TYPE_BANK_ACCOUNT),
                                            };
                                        })->get()->mapWithKeys(fn ($account) => [
                                            $account->id => "{$account->name} ({$account->formatted_balance})"
                                        ]);
                                    })
                                    ->searchable()
                                    ->required(fn (Forms\Get $get): bool => $get('payment_method') !== PaymentMethodEnum::CASH->value)
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Tutar')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue($this->debt->remaining_amount),
                        Forms\Components\Select::make('payment_method')
                            ->label('Ödeme Yöntemi')
                            ->options(PaymentMethodEnum::toArray())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('bank_account_id', null);
                                $set('source_account_id', null);
                            }),
                        Forms\Components\Select::make('source_account_id')
                            ->label('Kaynak Hesap')
                            ->options(function (Forms\Get $get) {
                                $paymentMethod = $get('payment_method');
                                if (!$paymentMethod) return [];

                                $query = Account::query()
                                    ->where('user_id', auth()->id())
                                    ->where('status', true);

                                return $query->when($paymentMethod, function ($query) use ($paymentMethod) {
                                    return match($paymentMethod) {
                                        PaymentMethodEnum::BANK->value => $query->where('type', Account::TYPE_BANK_ACCOUNT),
                                        PaymentMethodEnum::CREDIT_CARD->value => $query->where('type', Account::TYPE_CREDIT_CARD),
                                        PaymentMethodEnum::CRYPTO->value => $query->where('type', Account::TYPE_CRYPTO_WALLET),
                                        PaymentMethodEnum::VIRTUAL_POS->value => $query->where('type', Account::TYPE_VIRTUAL_POS),
                                        default => $query->where('type', Account::TYPE_BANK_ACCOUNT),
                                    };
                                })->get()->mapWithKeys(fn ($account) => [
                                    $account->id => "{$account->name} ({$account->formatted_balance})"
                                ]);
                            })
                            ->searchable()
                            ->required(fn (Forms\Get $get): bool => $get('payment_method') !== PaymentMethodEnum::CASH->value)
                            ->visible(fn (Forms\Get $get): bool => $get('payment_method') !== PaymentMethodEnum::CASH->value),
                        Forms\Components\DatePicker::make('date')
                            ->label('Ödeme Tarihi')
                            ->default(now())
                            ->required()
                            ->format('d.m.Y')
                    ])
                    ->using(function (array $data): Transaction {
                        $this->service->addPayment($this->debt, $data);
                        return Transaction::where('reference_id', $this->debt->id)->latest()->first();
                    })
                ]); 
            }
                */
    }
    
    public function render(): View
    {
        return view('livewire.debt.debt-payments');
    }

    protected function getPaymentForm(): array
    {
        return [
            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\Grid::make()
                        ->schema([
                            Forms\Components\TextInput::make('amount')
                                ->label('Ödenecek Miktar')
                                ->numeric()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                    if ($this->debt->currency === 'XAU') {
                                        $currentGoldPrice = $this->getCurrentGoldPrice();
                                        $amount = $get('amount');
                                        $set('amount_in_currency', $amount * $currentGoldPrice);
                                    }
                                })
                                ->maxValue($this->debt->remaining_amount)
                                ->suffix($this->debt->currency === 'XAU' ? ' GR' : '')
                                ->columnSpan(6),
                            Forms\Components\TextInput::make('amount_in_currency')
                                ->label('Ödenecek Tutar')
                                ->numeric()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                    if ($this->debt->currency === 'XAU') {
                                        $currentGoldPrice = $this->getCurrentGoldPrice();
                                        $amountInCurrency = $get('amount_in_currency');
                                        $set('amount', $amountInCurrency / $currentGoldPrice);
                                    }
                                })
                                ->prefix('₺')
                                ->columnSpan(6),
                        ])
                        ->columns(12)
                        ->columnSpan(12),

                    Forms\Components\Grid::make()
                        ->schema([
                            Forms\Components\Select::make('payment_method')
                                ->label('Ödeme Yöntemi')
                                ->options(PaymentMethodEnum::toArray())
                                ->required()
                                ->native(false)
                                ->columnSpan(6),
                            Forms\Components\Select::make('account_id')
                                ->label('Hesap')
                                ->options(Account::pluck('name', 'id'))
                                ->searchable()
                                ->native(false)
                                ->required()
                                ->columnSpan(6),
                        ])
                        ->columns(12)
                        ->columnSpan(12),

                    Forms\Components\Textarea::make('notes')
                        ->label('Notlar')
                        ->rows(3)
                        ->columnSpan(12),
                ])
                ->columns(1),
        ];
    }

    protected function getCurrentGoldPrice(): float
    {
        // TODO: Implement gold price API integration
        return 2500.00; // Örnek fiyat
    }

    protected function getPaymentData(array $data): array
    {
        if ($this->debt->currency === 'XAU') {
            $currentGoldPrice = $this->getCurrentGoldPrice();
            $data['amount_in_currency'] = $data['amount'] * $currentGoldPrice;
        }

        return $data;
    }
}