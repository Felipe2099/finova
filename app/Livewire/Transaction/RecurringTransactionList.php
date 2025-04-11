<?php

declare(strict_types=1);

namespace App\Livewire\Transaction;

use App\Models\Transaction;
use App\Services\Transaction\Contracts\SubscriptionTransactionServiceInterface;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class RecurringTransactionList extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected SubscriptionTransactionServiceInterface $subscriptionService;

    /**
     * Bileşen başlatılırken servisleri enjekte eder
     *
     * @param SubscriptionTransactionServiceInterface $subscriptionService Abonelik servisi
     * @return void
     */
    public function boot(SubscriptionTransactionServiceInterface $subscriptionService): void
    {
        $this->subscriptionService = $subscriptionService;
    }
    // Filament Action için Transaction nesnesini alır
    public function endSubscriptionAction(Transaction $record): void
    {
        try {
            $this->subscriptionService->endSubscription($record);
            Notification::make()
                ->title('Devamlı işlem başarıyla sonlandırıldı.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Hata')
                ->body('Devamlı işlem sonlandırılamadı: ' . $e->getMessage())
                ->danger()
                ->send();
            Log::error('Devamlı işlem sonlandırma hatası: ' . $e->getMessage(), ['transaction_id' => $record->id]);
        }
    }

    // Filament Table tanımı
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(
                Transaction::query()
                    // ->where('user_id', auth()->id()) 
                    ->where('is_subscription', true) // Sadece devamlı işlemleri al
            )
            ->emptyStateHeading('Devamlı İşlem Bulunamadı')
            ->emptyStateDescription('Başlamak için yeni bir devamlı işlem oluşturun.')
            ->columns([

                // İşlem Türü Sütunu
                Tables\Columns\BadgeColumn::make('type')
                    ->label('İşlem Türü')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'income' => 'Gelir',
                        'expense' => 'Gider',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'income' => 'success',
                        'expense' => 'danger',
                        default => 'secondary',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status_badge')
                    ->label('Kalan Süre')
                    ->getStateUsing(function (Transaction $record): string { 
                        if (!$record->next_payment_date) return '-';
                        $nextPaymentDate = Carbon::parse($record->next_payment_date);
                        $now = Carbon::today();
                        $diff = $now->diffInDays($nextPaymentDate, false);

                        if ($diff < 0) {
                            return $diff . ' gün';
                        } elseif ($diff === 0) {
                            return 'Bugün';
                        } else {
                            return $diff . ' gün';
                        }
                    })
                    ->color(function (Transaction $record): string { // Badge rengini hesapla
                        if (!$record->next_payment_date) return 'secondary';
                        $nextPaymentDate = Carbon::parse($record->next_payment_date);
                        $now = Carbon::today();
                        if ($nextPaymentDate->isPast() && !$nextPaymentDate->isToday()) {
                            return 'danger'; // Geçmiş
                        } elseif ($nextPaymentDate->isToday()) {
                            return 'warning'; // Bugün
                        }
                        return 'primary'; // Gelecek
                    })
                    // Sıralamayı yine next_payment_date'e göre yap
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('next_payment_date', $direction)),

                Tables\Columns\TextColumn::make('subscription_period')
                    ->label('Periyot')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'daily' => 'Günlük',
                        'weekly' => 'Haftalık',
                        'monthly' => 'Aylık',
                        'quarterly' => '3 Aylık',
                        'biannually' => '6 Aylık',
                        'annually' => 'Yıllık',
                        default => '-',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Tutar')
                    ->money(fn (Transaction $record) => $record->currency)
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->placeholder('Yok')
                    ->sortable(),

                Tables\Columns\TextColumn::make('next_payment_date')
                    ->label('Sonraki Ödeme')
                    ->date('d.m.Y')
                    ->sortable(),


            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('subscription_period')
                    ->label('Periyot')
                    ->options([
                        'daily' => 'Günlük',
                        'weekly' => 'Haftalık',
                        'monthly' => 'Aylık',
                        'quarterly' => '3 Aylık',
                        'biannually' => '6 Aylık',
                        'annually' => 'Yıllık',
                    ])
                    ->native(false),
            ])
            ->actions([
                Action::make('quickCreate')
                    ->label('Hızlı İşlem')
                    ->icon('heroicon-o-bolt')
                    ->color('info')
                    ->action(function (Transaction $record): void {
                        $this->redirectRoute('admin.transactions.create', ['copy_from' => $record->id], navigate: true);
                    })
                    ->visible(auth()->user()->can('recurring_transactions.copy')),

                Action::make('endSubscription')
                    ->label('Bitir')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Devamlı İşlemi Sonlandır')
                    ->modalDescription('Bu devamlı işlemi sonlandırmak istediğinizden emin misiniz? Bu işlem geri alınamaz.')
                    ->action(fn (Transaction $record) => $this->endSubscriptionAction($record)) // Action metodunu çağır
                    ->visible(auth()->user()->can('recurring_transactions.complete')),
            ])
            ->bulkActions([
            ])
            ->defaultSort('next_payment_date', 'asc')
            ->striped();
    }

    public function render(): View
    {
        // Filament tablosunu render et
        return view('livewire.transaction.recurring-transaction-list-container');
    }
}