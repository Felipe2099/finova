<?php

namespace App\Livewire\Account;

use App\Models\Account;
use App\Models\Transaction;
use App\Services\Currency\CurrencyService;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Hesap İşlem Geçmişi Bileşeni
 * 
 * Belirli bir hesabın tüm işlem geçmişini görüntüleyen ve yöneten Livewire bileşeni.
 * Gelir, gider, transfer ve diğer finansal işlemleri listeler ve filtreler.
 * 
 * Özellikler:
 * - İşlem geçmişi tablosu
 * - Tarih ve işlem türü filtreleme
 * - Para birimi dönüşümleri
 * - İşlem detayları görüntüleme
 */
class AccountHistory extends Component implements Forms\Contracts\HasForms, Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;
    use Forms\Concerns\InteractsWithForms;


    /** @var Account İşlem geçmişi görüntülenecek hesap */
    public Account $account;

    /** @var CurrencyService Para birimi dönüşümleri için servis */
    private CurrencyService $currencyService;

    /**
     * Bileşen başlatma
     * 
     * @param CurrencyService $currencyService Para birimi servisi
     * @return void
     */
    public function boot(CurrencyService $currencyService): void
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Bileşen yükleme
     * 
     * @param Account $account İşlem geçmişi görüntülenecek hesap
     * @return void
     */
    public function mount(Account $account): void
    {
        $this->account = $account;
    }

    /**
     * İşlem geçmişi tablosunu yapılandırır
     * 
     * @param Tables\Table $table Filament tablo yapılandırması
     * @return Tables\Table
     */
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->where(function ($query) {
                        $query->where('source_account_id', $this->account->id)
                            ->orWhere('destination_account_id', $this->account->id);
                    })
                    ->orderBy('id', 'desc')
            )
            ->emptyStateHeading('İşlem Geçmişi Bulunamadı')
            ->emptyStateDescription('Bu hesap için henüz bir işlem kaydedilmemiş.')
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Tarih')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tür')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'income' => 'Gelir',
                        'expense' => 'Gider',
                        'transfer' => 'Transfer',
                        'loan_payment' => 'Kredi Ödemesi',
                        'payment' => 'Ödeme',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'income' => 'success',
                        'expense' => 'danger',
                        'transfer' => 'warning',
                        'loan_payment' => 'gray',
                        'payment' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('description')
                    ->label('Açıklama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Tutar')
                    ->formatStateUsing(function (Transaction $record) {
                        $amount = abs($record->amount);
                        $prefix = '';
                        
                        // Hesaptan para çıkışı durumları
                        if ($record->source_account_id === $this->account->id) {
                            // Hesaptan para çıkışı (harcama, transfer, kredi ödemesi)
                            $prefix = '-';
                        } 
                        // Hesaba para girişi durumları
                        elseif ($record->destination_account_id === $this->account->id) {
                            // Hesaba para girişi (gelir, transfer, ödeme)
                            $prefix = '+';
                        }
                        
                        return "{$prefix}{$amount} {$record->currency}";
                    }),
                Tables\Columns\TextColumn::make('try_equivalent')
                    ->label('TRY Karşılığı')
                    ->formatStateUsing(function (Transaction $record) {
                        if ($record->currency === 'TRY') {
                            $tryAmount = abs($record->amount);
                            $prefix = '';
                            
                            // Hesaptan para çıkışı durumları
                            if ($record->source_account_id === $this->account->id) {
                                // Hesaptan para çıkışı (harcama, transfer, kredi ödemesi)
                                $prefix = '-';
                            } 
                            // Hesaba para girişi durumları
                            elseif ($record->destination_account_id === $this->account->id) {
                                // Hesaba para girişi (gelir, transfer, ödeme)
                                $prefix = '+';
                            }
                            
                            return "{$prefix}" . number_format($tryAmount, 2) . " TRY";
                        }

                        // Kaydedilmiş TRY karşılığını kullan
                        $tryAmount = abs($record->try_equivalent);
                        $prefix = '';
                        
                        // Hesaptan para çıkışı durumları
                        if ($record->source_account_id === $this->account->id) {
                            // Hesaptan para çıkışı (harcama, transfer, kredi ödemesi)
                            $prefix = '-';
                        } 
                        // Hesaba para girişi durumları
                        elseif ($record->destination_account_id === $this->account->id) {
                            // Hesaba para girişi (gelir, transfer, ödeme)
                            $prefix = '+';
                        }
                        
                        return "{$prefix}" . number_format($tryAmount, 2) . " TRY";
                    })
                    ->visible(fn () => $this->account->currency !== 'TRY'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tür')
                    ->options([
                        'income' => 'Gelir',
                        'expense' => 'Gider',
                        'transfer' => 'Transfer',
                        'loan_payment' => 'Kredi Ödemesi',
                        'payment' => 'Ödeme',
                    ])
                    ->native(false),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Başlangıç Tarihi')
                            ->native(false),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Bitiş Tarihi')
                            ->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['start_date']) {
                            $query->where('date', '>=', $data['start_date']);
                        }
                        if ($data['end_date']) {
                            $query->where('date', '<=', $data['end_date']);
                        }
                    }),
            ]);
    }

    /**
     * Bileşen görünümünü render eder
     * 
     * @return View
     */
    public function render(): View
    {
        return view('livewire.account.account-history');
    }
}