<?php

declare(strict_types=1);

namespace App\Livewire\Loan;

use App\Models\Loan;
use App\Models\Account;
use App\Enums\PaymentMethodEnum;
use App\Services\Loan\Contracts\LoanServiceInterface;
use App\DTOs\Loan\LoanData;
use App\Services\Payment\Implementations\PaymentService;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Carbon\Carbon;

/**
 * Kredi Yönetimi Bileşeni
 * 
 * Bu bileşen, kredilerin yönetimini sağlar.
 * Özellikler:
 * - Kredi listesi görüntüleme
 * - Yeni kredi ekleme
 * - Kredi düzenleme
 * - Kredi silme
 * - Kredi ödemesi yapma
 * - Kredi durumu takibi
 * - Kredi filtreleme (tür, durum)
 * 
 * @package App\Livewire\Loan
 */
class LoanManager extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    
    /** @var Loan|null Aktif kredi */
    protected ?Loan $loan = null;

    /** @var LoanServiceInterface Kredi servisi */
    private LoanServiceInterface $loanService;

    /** @var PaymentService Ödeme servisi */
    private PaymentService $paymentService;

    /**
     * Bileşen başlatılırken servisleri enjekte eder
     * 
     * @param LoanServiceInterface $loanService Kredi servisi
     * @param PaymentService $paymentService Ödeme servisi
     * @return void
     */
    public function boot(LoanServiceInterface $loanService, PaymentService $paymentService): void
    {
        $this->loanService = $loanService;
        $this->paymentService = $paymentService;
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
            ->query(Loan::query())
            ->emptyStateHeading('Kredi Bulunamadı')
            ->emptyStateDescription('Başlamak için yeni bir kredi oluşturun.')
            ->columns([
                Tables\Columns\TextColumn::make('bank_name')
                    ->label('Banka')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('loan_type')
                    ->label('Tür')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'personal' => 'Bireysel',
                        'business' => 'Ticari',
                    })
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Kredi')
                    ->money('TRY')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('monthly_payment')
                    ->label('Aylık Ödeme')
                    ->money('TRY')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Kalan')
                    ->money('TRY')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Devam Ediyor',
                        'paid' => 'Ödendi',
                        'overdue' => 'Gecikmiş',
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'overdue' => 'danger',
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('next_payment_date')
                    ->label('Sonraki Ödeme')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options(['pending' => 'Beklemede', 'paid' => 'Ödendi', 'overdue' => 'Gecikmiş'])
                    ->native(false),
                Tables\Filters\SelectFilter::make('loan_type')
                    ->label('Kredi Türü')
                    ->options(['personal' => 'Kişisel', 'business' => 'Ticari'])
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Krediyi Düzenle')
                    ->visible(fn () => auth()->user()->can('loans.edit'))
                    ->form($this->getLoanForm())
                    ->action(function (array $data, Loan $loan): void {
                        $loanData = LoanData::fromArray([
                            'bank_name' => $data['bank_name'],
                            'loan_type' => $data['loan_type'],
                            'amount' => floatval($data['amount']),
                            'monthly_payment' => floatval($data['monthly_payment']),
                            'installments' => intval($data['installments']),
                            'remaining_installments' => intval($data['remaining_installments']),
                            'start_date' => $data['start_date'],
                            'next_payment_date' => $data['next_payment_date'],
                            'status' => $loan->status,
                            'notes' => $data['notes'],
                        ]);
                        $this->loanService->update($loan, $loanData);
                        Notification::make()
                            ->title('Başarılı!')
                            ->body('Kredi başarıyla güncellendi.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('payment')
                    ->label('Ödeme Yap')
                    ->color('success')
                    ->icon('heroicon-m-banknotes')
                    ->visible(fn (Loan $record): bool => auth()->user()->can('loans.payments') && $record->status !== 'paid' && $record->remaining_installments > 0)
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Ödeme Tutarı')
                            ->numeric()
                            ->required()
                            ->readonly()
                            ->default(fn (Loan $record) => $record->monthly_payment)
                            ->prefix('TL')
                            ->step(0.01)
                            ->minValue(0),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Son Ödeme Tarihi')
                            ->default(fn (Loan $record) => $record->next_payment_date)
                            ->required()
                            ->format('d.m.Y')
                            ->native(false),
                        Forms\Components\DatePicker::make('transaction_date')
                            ->label('İşlem Tarihi')
                            ->default(now())
                            ->required()
                            ->format('d.m.Y')
                            ->native(false),
                        Forms\Components\Select::make('payment_method')
                            ->label('Ödeme Yöntemi')
                            ->options([
                                PaymentMethodEnum::CASH->value => PaymentMethodEnum::CASH->label(),
                                PaymentMethodEnum::BANK->value => PaymentMethodEnum::BANK->label(),
                                PaymentMethodEnum::CREDIT_CARD->value => PaymentMethodEnum::CREDIT_CARD->label(),
                            ])
                            ->native(false)
                            ->required()
                            ->default('cash')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('account_id', null);
                            }),
                        Forms\Components\Select::make('account_id')
                            ->label(function (callable $get) {
                                $method = $get('payment_method');
                                if ($method === PaymentMethodEnum::BANK->value) {
                                    return 'Banka Hesabı';
                                } elseif ($method === PaymentMethodEnum::CREDIT_CARD->value) {
                                    return 'Kredi Kartı';
                                }
                                return 'Hesap';
                            })
                            ->options(function (callable $get) {
                                $method = $get('payment_method');
                                $type = null;
                                
                                if ($method === PaymentMethodEnum::BANK->value) {
                                    $type = Account::TYPE_BANK_ACCOUNT;
                                } elseif ($method === PaymentMethodEnum::CREDIT_CARD->value) {
                                    $type = Account::TYPE_CREDIT_CARD;
                                }
                                
                                if (!$type) {
                                    return [];
                                }
                                
                                return Account::where('user_id', auth()->id())
                                    ->where('type', $type)
                                    ->where('status', true)
                                    ->where('currency', 'TRY')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->native(false)
                            ->visible(fn (callable $get) => in_array($get('payment_method'), [PaymentMethodEnum::BANK->value, PaymentMethodEnum::CREDIT_CARD->value])),
                    ])
                    ->action(function (Loan $record, array $data): void {
                        try {
                            $this->loanService->addPayment($record, $data);
                            
                            Notification::make()
                                ->title('Başarılı!')
                                ->body('Ödeme başarıyla kaydedildi.')
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Hata!')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Krediyi Sil')
                    ->modalDescription('Bu krediyi silmek istediğinize emin misiniz?')
                    ->modalSubmitActionLabel('Sil')
                    ->modalCancelActionLabel('İptal')
                    ->successNotificationTitle('Kredi silindi')
                    ->visible(fn () => auth()->user()->can('loans.delete'))
                    ->action(function (Loan $loan): void {
                        $result = $this->loanService->delete($loan);
                        
                        if ($result['success']) {
                            Notification::make()
                                ->title('Başarılı!')
                                ->body($result['message'])
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Uyarı')
                                ->body($result['message'])
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Yeni Kredi')
                    ->modalHeading('Yeni Kredi')
                    ->visible(fn () => auth()->user()->can('loans.create'))
                    ->form($this->getLoanForm())
                    ->createAnother(false)
                    ->action(function (array $data): void {
                        $loanData = [
                            'bank_name' => $data['bank_name'],
                            'loan_type' => $data['loan_type'],
                            'amount' => floatval($data['amount']),
                            'monthly_payment' => floatval($data['monthly_payment']),
                            'installments' => intval($data['installments']),
                            'remaining_installments' => intval($data['remaining_installments']),
                            'start_date' => $data['start_date'],
                            'next_payment_date' => $data['next_payment_date'],
                            'status' => 'pending',
                            'notes' => $data['notes'],
                        ];
                        $this->loanService->createLoan($loanData);
                        Notification::make()
                            ->title('Başarılı!')
                            ->body('Kredi başarıyla eklendi.')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('due_date', 'asc');
    }

    /**
     * Kredi formunu oluşturur
     * 
     * @return array Form bileşenleri
     */
    protected function getLoanForm(): array
    {
        return [
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('bank_name')
                    ->label('Banka Adı')
                    ->required(),
                Forms\Components\Select::make('loan_type')
                    ->label('Kredi Türü')
                    ->options(['personal' => 'Bireysel', 'business' => 'Ticari'])
                    ->native(false)
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->label('Kredi Tutarı')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->prefix('TL')
                    ->step(0.01)
                    ->live(),
                Forms\Components\TextInput::make('monthly_payment')
                    ->label('Aylık Ödeme Tutarı')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->prefix('TL')
                    ->step(0.01),
                Forms\Components\TextInput::make('installments')
                    ->label('Toplam Taksit Sayısı')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                Forms\Components\TextInput::make('remaining_installments')
                    ->label('Kalan Taksit Sayısı')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                Forms\Components\DatePicker::make('start_date')
                    ->label('Başlangıç Tarihi')
                    ->default(now())
                    ->required()
                    ->format('d.m.Y'),
                Forms\Components\DatePicker::make('next_payment_date')
                    ->label('Sonraki Ödeme Tarihi')
                    ->default(now()->addMonth())
                    ->required()
                    ->format('d.m.Y'),
                Forms\Components\Textarea::make('notes')
                    ->label('Notlar')
                    ->rows(3)
                    ->columnSpan(2),
            ]),
        ];
    }

    /**
     * Bileşenin görünümünü render eder
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('livewire.loan.loan-manager');
    }
}