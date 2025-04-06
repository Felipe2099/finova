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

/**
 * Hesap Yönetimi Bileşeni
 * 
 * Tüm hesap türlerinin (banka, kredi kartı, kripto, sanal POS, nakit) 
 * genel yönetimini sağlayan Livewire bileşeni.
 * 
 * Özellikler:
 * - Tüm hesapların listesi
 * - Hesap türü ve para birimi filtreleme
 * - Hesap durumu yönetimi
 * - Hesap detayları görüntüleme
 * - Toplu hesap silme
 */
class AccountManager extends Component implements Forms\Contracts\HasForms, Tables\Contracts\HasTable
{
    use Forms\Concerns\InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;

    /** @var AccountService Hesap işlemleri için servis */
    private AccountService $accountService;

    /**
     * Bileşen başlatma
     * 
     * @param AccountService $accountService Hesap servisi
     * @return void
     */
    public function boot(AccountService $accountService): void 
    {
        $this->accountService = $accountService;
    }

    /**
     * Hesap listesi tablosunu yapılandırır
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
                    ->with(['bankAccount', 'creditCard', 'cryptoWallet', 'virtualPos'])
            )
            ->emptyStateHeading('Hesap Bulunamadı')
            ->emptyStateDescription('Başlamak için yeni bir hesap ekleyin.')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Hesap Adı')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Hesap Türü')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Account::TYPE_BANK_ACCOUNT => 'Banka Hesabı',
                        Account::TYPE_CREDIT_CARD => 'Kredi Kartı',
                        Account::TYPE_CRYPTO_WALLET => 'Kripto Cüzdanı',
                        Account::TYPE_VIRTUAL_POS => 'Sanal POS',
                        Account::TYPE_CASH => 'Nakit',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Account::TYPE_BANK_ACCOUNT => 'success',
                        Account::TYPE_CREDIT_CARD => 'danger',
                        Account::TYPE_CRYPTO_WALLET => 'warning',
                        Account::TYPE_VIRTUAL_POS => 'info',
                        Account::TYPE_CASH => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('currency')
                    ->label('Para Birimi')
                    ->badge(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Bakiye')
                    ->money(fn (Account $record) => $record->currency)
                    ->sortable(),
                Tables\Columns\TextColumn::make('try_equivalent')
                    ->label('TRY Karşılığı')
                    ->money('TRY')
                    ->sortable(),
                Tables\Columns\IconColumn::make('status')
                    ->label('Durum')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Hesap Türü')
                    ->options([
                        Account::TYPE_BANK_ACCOUNT => 'Banka Hesabı',
                        Account::TYPE_CREDIT_CARD => 'Kredi Kartı',
                        Account::TYPE_CRYPTO_WALLET => 'Kripto Cüzdanı',
                        Account::TYPE_VIRTUAL_POS => 'Sanal POS',
                        Account::TYPE_CASH => 'Nakit',
                    ])
                    ->native(false),
                Tables\Filters\SelectFilter::make('currency')
                    ->label('Para Birimi')
                    ->options([
                        'TRY' => 'Türk Lirası',
                        'USD' => 'Amerikan Doları',
                        'EUR' => 'Euro',
                        'GBP' => 'İngiliz Sterlini',
                    ])
                    ->native(false),
                Tables\Filters\TernaryFilter::make('status')
                    ->label('Durum')
                    ->placeholder('Hepsi')
                    ->trueLabel('Aktif')
                    ->falseLabel('Pasif'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Görüntüle')
                    ->icon('heroicon-s-eye')
                    ->url(fn (Account $record): string => match ($record->type) {
                        Account::TYPE_BANK_ACCOUNT => route('admin.accounts.bank'),
                        Account::TYPE_CREDIT_CARD => route('admin.accounts.credit-cards'),
                        Account::TYPE_CRYPTO_WALLET => route('admin.accounts.crypto'),
                        Account::TYPE_VIRTUAL_POS => route('admin.accounts.virtual-pos'),
                        default => route('admin.accounts.index'),
                    })
                    ->extraAttributes(['wire:navigate' => true])
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Seçili Hesapları Sil'),
                ]),
            ])
            ->headerActions([
            ]);
    }

    /**
     * Bileşen görünümünü render eder
     * 
     * @return View
     */
    public function render(): View
    {
        return view('livewire.account.account-manager');
    }
} 