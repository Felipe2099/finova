<?php

namespace App\Livewire\Account\Widgets;

use App\Models\Account;
use App\Models\Transaction;
use Filament\Widgets\Widget as BaseWidget;
use Carbon\Carbon;
use App\Enums\PaymentMethodEnum;

/**
 * Kredi Kartı İstatistikleri Widget'ı
 * 
 * Kredi kartlarıyla ilgili önemli istatistikleri gösteren widget.
 * Toplam limit, borç, gelecek taksitler ve diğer önemli metrikleri listeler.
 */
class CreditCardStatsWidget extends BaseWidget
{
    protected static string $view = 'livewire.account.widgets.credit-card-stats-widget';

    protected $listeners = [
        'creditCardCreated' => '$refresh',
        'creditCardUpdated' => '$refresh',
        'creditCardDeleted' => '$refresh',
        'transactionCreated' => '$refresh',
        'transactionUpdated' => '$refresh',
        'transactionDeleted' => '$refresh'
    ];

    /**
     * İstatistikleri hesaplar ve döndürür
     * 
     * @return array İstatistik dizisi
     */
    public function getStats(): array
    {
        $totalLimit = $this->getTotalLimit();
        $totalBalance = $this->getTotalBalance();
        $minimumPayment = $this->getTotalMinimumPayment();

        return [
            [
                'label' => 'Toplam Limit',
                'value' => '₺' . number_format($totalLimit, 2, ',', '.'),
                'icon' => 'heroicon-o-credit-card',
                'color' => 'info',
            ],
            [
                'label' => 'Toplam Borç',
                'value' => '₺' . number_format($totalBalance, 2, ',', '.'),
                'icon' => 'heroicon-o-banknotes',
                'color' => $totalBalance > 0 ? 'danger' : 'success',
            ],
            [
                'label' => 'Gelecek Taksitler',
                'value' => '₺' . number_format($this->getUpcomingInstallmentsTotal(), 2, ',', '.'),
                'icon' => 'heroicon-o-calendar',
                'color' => 'warning',
            ],
            /**
            [
                'label' => 'Aylık Ekstre',
                'value' => '₺' . number_format($this->getCurrentStatementPayment(), 2, ',', '.'),
                'icon' => 'heroicon-o-receipt-percent',
                'color' => 'primary',
            ],
            [
                'label' => 'Aylık Asgari',
                'value' => '₺' . number_format($minimumPayment, 2, ',', '.'),
                'icon' => 'heroicon-o-arrow-trending-down',
                'color' => $minimumPayment > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Aylık Ödeme',
                'value' => '₺' . number_format($this->getCurrentMonthPayment(), 2, ',', '.'),
                'icon' => 'heroicon-o-banknotes',
                'color' => 'success',
            ],
             */
      
        ];
    }

    /**
     * Toplam kredi kartı limitini hesaplar
     * 
     * @return float Toplam limit
     */
    private function getTotalLimit(): float
    {
        return Account::query()
            ->where('type', Account::TYPE_CREDIT_CARD)
            ->whereNull('deleted_at')
            ->where('status', true) // Sadece aktif kartlar
            ->get()
            ->sum(function ($account) {
                return (float) ($account->details['credit_limit'] ?? 0);
            });
    }

    /**
     * Toplam kredi kartı borcunu hesaplar
     * 
     * @return float Toplam borç
     */
    private function getTotalBalance(): float
    {
        // Tüm kredi kartlarının toplam borcunu hesapla
        // Doğrudan balance alanını kullanarak
        return Account::query()
            ->where('type', Account::TYPE_CREDIT_CARD)
            ->whereNull('deleted_at')
            ->where('status', true)
            ->sum('balance');
    }

    /**
     * Toplam asgari ödeme tutarını hesaplar
     * 
     * @return float Toplam asgari ödeme
     */
    private function getTotalMinimumPayment(): float
    {
        $cards = Account::query()
            ->where('type', Account::TYPE_CREDIT_CARD)
            ->whereNull('deleted_at')
            ->where('status', true) // Sadece aktif kartlar
            ->get();

        $total = 0;
        $currentDate = now();

        foreach ($cards as $card) {
            $statementDay = (int) ($card->details['statement_day'] ?? 1);
            
            // Ekstre başlangıç ve bitiş tarihleri
            $statementStart = $currentDate->copy()->setDay($statementDay);
            if ($currentDate->day < $statementDay) {
                $statementStart->subMonth();
            }
            $statementEnd = $statementStart->copy()->addMonth()->subDay();

            // Bu ay ödenecek taksitler
            $currentInstallments = Transaction::query()
                ->where('type', 'expense')
                ->where('source_account_id', $card->id)
                ->whereNull('deleted_at')
                ->whereNotNull('installments')
                ->where('remaining_installments', '>', 0)
                ->get()
                ->sum(function ($transaction) {
                    return $transaction->try_equivalent / $transaction->installments;
                });

            // Taksitsiz harcamalar
            $regularExpenses = Transaction::query()
                ->where('type', 'expense')
                ->where('source_account_id', $card->id)
                ->whereNull('deleted_at')
                ->whereNull('installments')
                ->whereBetween('date', [$statementStart, $statementEnd])
                ->sum('try_equivalent');

            // Bu ay yapılan ödemeler
            $payments = Transaction::query()
                ->where('type', 'payment')
                ->where('destination_account_id', $card->id)
                ->whereNull('deleted_at')
                ->whereBetween('date', [$statementStart, $statementEnd])
                ->sum('try_equivalent');

            // Ekstre tutarı
            $statementTotal = $currentInstallments + $regularExpenses;

            // Asgari ödeme hesaplama
            if ($statementTotal > 0) {
                $minimumPayment = $statementTotal * ($statementTotal >= 50000 ? 0.40 : 0.20);
                // Ödemeler düşüldükten sonra kalan asgari tutar
                $remainingMinimum = max(0, $minimumPayment - $payments);
                $total += $remainingMinimum;
            }
        }

        return $total;
    }

    private function getCurrentMonthExpense(): float
    {
        return Transaction::query()
            ->where('type', 'expense')
            ->whereNull('deleted_at')
            ->whereHas('sourceAccount', function ($query) {
                $query->where('type', Account::TYPE_CREDIT_CARD)
                    ->whereNull('deleted_at')
                    ->where('status', true); // Sadece aktif kartlar
            })
            ->whereYear('date', Carbon::now()->year)
            ->whereMonth('date', Carbon::now()->month)
            ->sum('try_equivalent');
    }

    private function getCurrentStatementPayment(): float
    {
        $cards = Account::query()
            ->where('type', Account::TYPE_CREDIT_CARD)
            ->whereNull('deleted_at')
            ->where('status', true) // Sadece aktif kartlar
            ->get();

        $total = 0;
        $currentDate = now();

        foreach ($cards as $card) {
            $statementDay = (int) ($card->details['statement_day'] ?? 1);
            
            // Ekstre başlangıç ve bitiş tarihleri
            $statementStart = $currentDate->copy()->setDay($statementDay);
            if ($currentDate->day < $statementDay) {
                $statementStart->subMonth();
            }
            $statementEnd = $statementStart->copy()->addMonth()->subDay();

            // Bu ay ödenecek taksitler
            $currentInstallments = Transaction::query()
                ->where('type', 'expense')
                ->where('source_account_id', $card->id)
                ->whereNull('deleted_at')
                ->whereNotNull('installments')
                ->where('remaining_installments', '>', 0)
                ->get()
                ->sum(function ($transaction) {
                    return $transaction->try_equivalent / $transaction->installments;
                });

            // Taksitsiz harcamalar
            $regularExpenses = Transaction::query()
                ->where('type', 'expense')
                ->where('source_account_id', $card->id)
                ->whereNull('deleted_at')
                ->whereNull('installments')
                ->whereBetween('date', [$statementStart, $statementEnd])
                ->sum('try_equivalent');

            // Bu ay yapılan ödemeler
            $payments = Transaction::query()
                ->where('type', 'payment')
                ->where('destination_account_id', $card->id)
                ->whereNull('deleted_at')
                ->whereBetween('date', [$statementStart, $statementEnd])
                ->sum('try_equivalent');

            // Ekstre tutarı (ödemeler düşülmüş hali)
            $statementTotal = $currentInstallments + $regularExpenses - $payments;
            $total += max(0, $statementTotal);
        }

        return $total;
    }

    private function getCurrentMonthPayment(): float
    {
        return Transaction::query()
            ->where('type', 'payment')
            ->whereNull('deleted_at')
            ->whereHas('destinationAccount', function ($query) {
                $query->where('type', Account::TYPE_CREDIT_CARD)
                    ->whereNull('deleted_at')
                    ->where('status', true); // Sadece aktif kartlar
            })
            ->whereYear('date', Carbon::now()->year)
            ->whereMonth('date', Carbon::now()->month)
            ->sum('try_equivalent');
    }

    /**
     * Gelecek taksitlerin toplam tutarını hesaplar
     * 
     * @return float Gelecek taksitler toplamı
     */
    private function getUpcomingInstallmentsTotal(): float
    {
        // Taksitli işlemleri al
        $installmentTransactions = Transaction::query()
            ->where('type', 'expense')
            ->whereNull('deleted_at')
            ->whereHas('sourceAccount', function ($query) {
                $query->where('type', Account::TYPE_CREDIT_CARD)
                    ->whereNull('deleted_at');
            })
            ->whereNotNull('installments')
            ->where('remaining_installments', '>', 1) // En az 2 taksit kalmış olmalı
            ->get();

        $total = 0;

        foreach ($installmentTransactions as $transaction) {
            // İlk taksit hariç kalan taksitler
            $remainingAmount = ($transaction->remaining_installments - 1) * ($transaction->try_equivalent / $transaction->installments);
            $total += $remainingAmount;
        }

        return $total;
    }
}