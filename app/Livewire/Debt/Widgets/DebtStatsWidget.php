<?php

declare(strict_types=1);

namespace App\Livewire\Debt\Widgets;

use App\Models\Debt;
use Filament\Widgets\Widget as BaseWidget;
use Carbon\Carbon;
use App\Services\Debt\Contracts\DebtServiceInterface;
use App\Models\Transaction;

/**
 * Borç/Alacak İstatistikleri Widget'ı
 * 
 * Bu widget, borç ve alacak istatistiklerini görüntüler.
 * Özellikler:
 * - Toplam borç tutarı
 * - Toplam alacak tutarı
 * - Geciken borç tutarı
 * - Geciken alacak tutarı
 * - Aylık tahsilat tutarı
 * - Otomatik durum güncelleme
 * 
 * @package App\Livewire\Debt\Widgets
 */
final class DebtStatsWidget extends BaseWidget
{
    /** @var string Widget görünüm dosyası */
    protected static string $view = 'livewire.debt.widgets.debt-stats-widget';

    /** @var DebtServiceInterface Borç/alacak servisi */
    private DebtServiceInterface $debtService;

    /**
     * Widget başlatılırken borç/alacak servisini enjekte eder
     * 
     * @param DebtServiceInterface $debtService Borç/alacak servisi
     * @return void
     */
    public function boot(DebtServiceInterface $debtService): void
    {
        $this->debtService = $debtService;
    }

    /**
     * Widget dinleyicilerini döndürür
     * 
     * @return array Dinleyici olayları
     */
    protected function getListeners(): array
    {
        return [
            'debt-created' => '$refresh',
            'debt-updated' => '$refresh',
            'debt-deleted' => '$refresh',
        ];
    }

    /**
     * İstatistik verilerini döndürür
     * 
     * @return array İstatistik verileri
     */
    public function getStats(): array
    {
        Transaction::where('next_payment_date', '<', now()->startOfDay())
            ->where('status', 'pending')
            ->whereIn('type', ['loan_payment', 'debt_payment'])
            ->each(function (Transaction $transaction) {
                $transaction->update(['status' => 'overdue']);
            });

        return [
            [
                'label' => 'Borç',
                'value' => '₺' . number_format($this->getTotalDebts(), 2, ',', '.'),
                'icon' => 'heroicon-o-arrow-up-circle',
                'color' => 'danger',
                'columnSpan' => 2,
            ],
            [
                'label' => 'Alacak',
                'value' => '₺' . number_format($this->getTotalReceivables(), 2, ',', '.'),
                'icon' => 'heroicon-o-arrow-down-circle',
                'color' => 'success',
                'columnSpan' => 2,
            ],
            [
                'label' => 'Geciken Borç',
                'value' => '₺' . number_format($this->getOverduePayable(), 2, ',', '.'),
                'icon' => 'heroicon-o-exclamation-circle',
                'color' => 'warning',
                'columnSpan' => 2,
            ],
            [
                'label' => 'Geciken Alacak',
                'value' => '₺' . number_format($this->getOverdueReceivable(), 2, ',', '.'),
                'icon' => 'heroicon-o-exclamation-circle',
                'color' => 'warning',
                'columnSpan' => 2,
            ],
            [
                'label' => 'Alınan Ödeme',
                'value' => '₺' . number_format($this->getCurrentMonthCollections(), 2, ',', '.'),
                'icon' => 'heroicon-o-check-circle',
                'color' => 'primary',
                'columnSpan' => 2,
            ],
        ];
    }

    /**
     * Geciken borç tutarını hesaplar
     * 
     * @return float Geciken borç tutarı
     */
    private function getOverduePayable(): float
    {
        return (float) Transaction::where('next_payment_date', '<', now()->startOfDay())
            ->where('status', '!=', 'completed')
            ->where('type', 'loan_payment')
            ->sum('amount');
    }
    
    /**
     * Geciken alacak tutarını hesaplar
     * 
     * @return float Geciken alacak tutarı
     */
    private function getOverdueReceivable(): float
    {
        return (float) Transaction::where('next_payment_date', '<', now()->startOfDay())
            ->where('status', '!=', 'completed')
            ->where('type', 'debt_payment')
            ->sum('amount');
    }

    /**
     * Aylık tahsilat tutarını hesaplar
     * 
     * @return float Aylık tahsilat tutarı
     */
    private function getCurrentMonthCollections(): float
    {
        return (float) Transaction::where('type', 'debt_payment')
            ->whereYear('date', Carbon::now()->year)
            ->whereMonth('date', Carbon::now()->month)
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Toplam borç tutarını hesaplar
     * 
     * @return float Toplam borç tutarı
     */
    private function getTotalDebts(): float
    {
        return (float) Transaction::where('status', '!=', 'completed')
            ->where('type', 'loan_payment')
            ->sum('amount');
    }

    /**
     * Toplam alacak tutarını hesaplar
     * 
     * @return float Toplam alacak tutarı
     */
    private function getTotalReceivables(): float
    {
        return (float) Transaction::where('status', '!=', 'completed')
            ->where('type', 'debt_payment')
            ->sum('amount');
    }
}