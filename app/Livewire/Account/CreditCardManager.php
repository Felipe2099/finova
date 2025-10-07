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
use App\Models\Transaction;
use App\Enums\PaymentMethodEnum;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;

/**
 * Credit Card Management Component
 * 
 * Customized Livewire component for managing credit cards.
 * Provides detailed operations and features for credit cards.
 * 
 * Features:
 * - Create/Edit/Delete credit cards
 * - Card limit and debt tracking
 * - Minimum payment calculation
 * - Card payments
 * - Transaction history view
 */
class CreditCardManager extends Component implements Forms\Contracts\HasForms, Tables\Contracts\HasTable
{
    use Forms\Concerns\InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;

    /** @var AccountService Account service */
    private AccountService $accountService;

    /** @var Account|null Selected credit card */
    public ?Account $selectedCard = null;

    /** @var string Active tab */
    public string $activeTab = 'Kredi Kartları';

    /**
     * Initialize the component
     * 
     * @param AccountService $accountService Account service
     * @return void
     */
    public function boot(AccountService $accountService): void
    {
        $this->accountService = $accountService;
    }

    /**
     * Configure the credit card list table
     * 
     * @param Tables\Table $table Filament table configuration
     * @return Tables\Table
     */
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(
                Account::query()
                    ->where('type', Account::TYPE_CREDIT_CARD)
            )
            ->emptyStateHeading('Kredi Kartı Bulunamadı')
            ->emptyStateDescription('Başlamak için yeni bir kredi kartı oluşturun.')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Kart Adı')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('details.bank_name')
                    ->label('Banka')
                    ->getStateUsing(fn (Account $record) => $record->details['bank_name'] ?? 'Bilinmiyor')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('currency')
                    ->label('Para Birimi')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Borç')
                    ->money(fn (Account $record) => $record->currency)
                    ->getStateUsing(function (Account $record) {
                        // Directly use the balance field for credit cards
                        // This value is the same as the value shown in the edit form
                        return $record->balance;
                    })
                    ->color(fn (Account $record, $state) => $state > 0 ? 'danger' : 'success')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('details.credit_limit')
                    ->label('Limit')
                    ->money(fn (Account $record) => $record->currency)
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('available_limit')
                    ->label('Kullanılabilir Limit')
                    ->getStateUsing(function (Account $record) {
                        $creditLimit = $record->details['credit_limit'] ?? 0;
                        
                        // Directly use the balance field for credit cards
                        // This value is the same as the value shown in the edit form
                        $totalDebt = $record->balance;

                        return $creditLimit - $totalDebt;
                    })
                    ->money(fn (Account $record) => $record->currency)
                    ->color(function (Account $record) {
                        $creditLimit = $record->details['credit_limit'] ?? 0;
                        
                        // Directly use the balance field for credit cards
                        // This value is the same as the value shown in the edit form
                        $totalDebt = $record->balance;
                        
                        $availableLimit = $creditLimit - $totalDebt;
                        
                        return $availableLimit < 0 ? 'danger' : 'success';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ToggleColumn::make('status')
                    ->label('Durum')
                    ->onColor('success')
                    ->offColor('danger')
                    ->afterStateUpdated(function (Account $record, $state) {
                        $statusText = $state ? 'aktif' : 'pasif';
                        Notification::make()
                            ->title("{$record->name} kredi kartı {$statusText} duruma getirildi.")
                            ->success()
                            ->send();
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('name')
                    ->label('Kart Adı')
                    ->multiple()
                    ->native(false)
                    ->options(function () {
                        return Account::query()
                            ->where('user_id', auth()->id())
                            ->where('type', Account::TYPE_CREDIT_CARD)
                            ->pluck('name', 'name')
                            ->unique()
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data): void {
                        if (!empty($data['values'])) {
                            $query->whereIn('name', $data['values']);
                        }
                    }),
                Tables\Filters\SelectFilter::make('currency')
                    ->label('Para Birimi')
                    ->multiple()
                    ->native(false)
                    ->default('TRY')
                    ->options([
                        'TRY' => 'Türk Lirası'
                    ]),
                Tables\Filters\TernaryFilter::make('status')
                    ->label('Durum')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\Action::make('view_transactions')
                    ->label('İşlemler')
                    ->icon('heroicon-o-credit-card')
                    ->action(function (Account $record) {
                        $this->selectedCard = $record;
                        $this->activeTab = 'İşlemler';
                    })
                    ->color('primary')
                    ->visible(fn () => auth()->user()->can('credit_cards.history')),
                
                    Tables\Actions\EditAction::make()
                    ->modalHeading('Kredi Kartı Düzenle')
                    ->modalSubmitActionLabel('Kaydet')
                    ->modalCancelActionLabel('İptal')
                    ->visible(fn () => auth()->user()->can('credit_cards.edit'))
                    ->form($this->getFormSchema())
                    ->mutateRecordDataUsing(function (array $data) {
                        $account = Account::find($data['id']);
                        $details = $account->details ?? [];
                        $data['details'] = [
                            'bank_name' => $details['bank_name'] ?? null,
                            'credit_limit' => $details['credit_limit'] ?? null,
                            'statement_day' => $details['statement_day'] ?? null,
                            'current_debt' => $details['current_debt'] ?? 0,
                        ];
                        $data['minimum_payment'] = $this->calculateMinimumPayment($account);
                        return $data;
                    })
                    ->using(function (Account $record, array $data) {
                        $accountData = AccountData::fromArray([
                            'name' => $data['name'],
                            'type' => Account::TYPE_CREDIT_CARD,
                            'currency' => $data['currency'],
                            'balance' => $data['balance'],
                            'status' => $data['status'],
                            'details' => [
                                'bank_name' => $data['details']['bank_name'],
                                'credit_limit' => $data['details']['credit_limit'],
                                'statement_day' => $data['details']['statement_day'],
                                'current_debt' => $data['balance'],
                            ],
                        ]);
                        $updatedAccount = $this->accountService->updateAccount($record, $accountData);
                        $this->dispatch('creditCardUpdated');
                        return $updatedAccount;
                    }),
                
                $this->makePaymentAction(),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Kredi Kartı Sil')
                    ->modalDescription('Bu kredi kartını silmek istediğinize emin misiniz?')
                    ->modalSubmitActionLabel('Sil')
                    ->modalCancelActionLabel('İptal')
                    ->successNotificationTitle('Kredi kartı silindi')
                    ->visible(fn () => auth()->user()->can('credit_cards.delete'))
                    ->label('Sil')
                    ->using(function (Account $record) {
                        $result = $this->accountService->delete($record);
                        $this->dispatch('creditCardDeleted');
                        return $result;
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Seçili Kartları Sil')
                        ->visible(fn () => auth()->user()->can('credit_cards.delete')),
                ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Kredi Kartı Oluştur')
                    ->modalHeading('Yeni Kredi Kartı')
                    ->modalSubmitActionLabel('Oluştur')
                    ->modalCancelActionLabel('İptal')
                    ->visible(fn () => auth()->user()->can('credit_cards.create'))
                    ->form($this->getFormSchema())
                    ->createAnother(false)
                    ->using(function (array $data) {
                        $accountData = AccountData::fromArray([
                            'name' => $data['name'],
                            'type' => Account::TYPE_CREDIT_CARD,
                            'currency' => $data['currency'],
                            'balance' => $data['balance'],
                            'status' => $data['status'],
                            'details' => [
                                'bank_name' => $data['details']['bank_name'],
                                'credit_limit' => $data['details']['credit_limit'],
                                'statement_day' => $data['details']['statement_day'],
                                'current_debt' => $data['balance'],
                            ],
                        ]);
                        $newAccount = $this->accountService->createAccount($accountData);
                        $this->dispatch('creditCardCreated');
                        return $newAccount;
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
                ->label('Kart Adı')
                ->required(),
            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\TextInput::make('details.bank_name')
                        ->label('Banka Adı')
                        ->required(),
                    Forms\Components\TextInput::make('details.credit_limit')
                        ->label('Kredi Limiti')
                        ->numeric()
                        ->required(),
                    Forms\Components\Select::make('details.statement_day')
                        ->label('Hesap Kesim Günü')
                        ->options(array_combine(range(1, 31), range(1, 31)))
                        ->required()
                        ->rules(['required', 'integer', 'min:1', 'max:31'])
                        ->native(false),
                ])
                ->columns(3),
            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\Select::make('currency')
                        ->label('Para Birimi')
                        ->options([
                            'TRY' => 'Türk Lirası'
                        ])
                        ->default('TRY')
                        ->required()
                        ->native(false),
                    Forms\Components\TextInput::make('balance')
                        ->label('Borç')
                        ->numeric()
                        ->default(0),
                ])
                ->columns(2),
            Forms\Components\TextInput::make('minimum_payment')
                ->label('Asgari Ödeme Tutarı')
                ->numeric()
                ->disabled()
                ->visible(fn ($get) => $get('balance') > 0),
            Forms\Components\Toggle::make('status')
                ->label('Aktif')
                ->default(true),
        ];
    }

    /**
     * Calculate the minimum payment
     * 
     * @param Account $account Credit card account
     * @return float Minimum payment
     */
    protected function calculateMinimumPayment(Account $account): float
    {
        // If the account is not found or the balance is 0, return 0
        if (!$account || $account->balance <= 0) {
            return 0;
        }

        // Minimum payment rate (default %20)
        $minimumPaymentRate = 0.20;

        // Calculate the minimum payment
        $minimumPayment = $account->balance * $minimumPaymentRate;

        // Minimum 100 TL or debt amount (whichever is smaller)
        return max(min($minimumPayment, $account->balance), min(100, $account->balance));
    }

    /**
     * Create the payment action
     * 
     * @return Action Payment action
     */
    public function makePaymentAction(): Action
    {
        return Action::make('makePayment')
            ->label('Ödeme Yap')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->visible(fn () => auth()->user()->can('credit_cards.payments'))
            ->modalHeading('Kredi Kartı Ödemesi')
            ->form(function (Account $record): array {
                return [
                    Forms\Components\Select::make('payment_method')
                        ->label('Ödeme Yöntemi')
                        ->options([
                            PaymentMethodEnum::CASH->value => PaymentMethodEnum::CASH->label(),
                            PaymentMethodEnum::BANK->value => PaymentMethodEnum::BANK->label(),
                        ])
                        ->required()
                        ->live()
                        ->native(false),

                    Forms\Components\Select::make('source_account_id')
                        ->label('Banka Hesabı')
                        ->options(function () use ($record) {
                            return Account::query()
                                ->where('user_id', auth()->id())
                                ->where('type', Account::TYPE_BANK_ACCOUNT)
                                ->where('currency', $record->currency) 
                                ->where('status', true)
                                ->get()
                                ->mapWithKeys(function ($account) {
                                    $formattedBalance = number_format($account->balance, 2, ',', '.') . ' ' . $account->currency;
                                    return [$account->id => "{$account->name} (Bakiye: {$formattedBalance})"];
                                });
                        })
                        ->required(fn (callable $get) => $get('payment_method') === PaymentMethodEnum::BANK->value)
                        ->visible(fn (callable $get) => $get('payment_method') === PaymentMethodEnum::BANK->value)
                        ->native(false),

                    Forms\Components\TextInput::make('amount')
                        ->label('Ödeme Tutarı')
                        ->required()
                        ->numeric()
                        ->minValue(0),

                    Forms\Components\DatePicker::make('date')
                        ->label('Ödeme Tarihi')
                        ->default(now())
                        ->required()
                        ->native(false),
                ];
            })
            ->action(function (array $data, Account $record): void {
                $this->accountService->makeCardPayment(
                    creditCardId: $record->id,
                    amount: $data['amount'],
                    paymentMethod: $data['payment_method'],
                    sourceAccountId: $data['source_account_id'] ?? null,
                    date: $data['date']
                );
                
                $this->dispatch('creditCardUpdated');
            });
    }

    /**
     * Render the component view
     * 
     * @return View
     */
    public function render(): View
    {
        return view('livewire.account.credit-card-manager');
    }
}