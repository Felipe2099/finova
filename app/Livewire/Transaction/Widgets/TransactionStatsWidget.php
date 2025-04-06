<?php

namespace App\Livewire\Transaction\Widgets;

use App\Models\Transaction;
use Filament\Widgets\Widget as BaseWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * İşlem İstatistikleri Widget Bileşeni
 * 
 * Bu bileşen, finansal işlemlerin istatistiklerini görüntülemek için kullanılır.
 * Özellikler:
 * - Günlük gelir ve gider istatistikleri
 * - Aylık gelir ve gider istatistikleri
 * - Önceki dönemlere göre artış/azalış oranları
 * - Otomatik yenileme desteği
 * - Hata yönetimi ve loglama
 * 
 * @package App\Livewire\Transaction\Widgets
 */
class TransactionStatsWidget extends BaseWidget
{
    /** @var string Widget görünüm dosyası */
    protected static string $view = 'livewire.transaction.widgets.transaction-stats-widget';

    /** @var array Dinleyici olayları */
    protected $listeners = [
        'transactionCreated' => '$refresh',
        'transactionUpdated' => '$refresh',
        'transactionDeleted' => '$refresh'
    ];

    /**
     * İstatistik verilerini döndürür
     * 
     * @return array İstatistik verileri
     */
    public function getStats(): array
    {
        try {
            return [
                [
                    'label' => 'Gelir (Bugün)',
                    'value' => '₺' . number_format($this->getTodayIncome(), 2, ',', '.'),
                    'icon' => 'heroicon-o-arrow-trending-up',
                    'color' => 'success',
                    'trend' => $this->getYesterdayIncome() == 0 ? 'up' : ($this->getTodayIncome() > $this->getYesterdayIncome() ? 'up' : 'down'),
                    'description' => $this->getTodayIncrement('income'),
                ],
                [
                    'label' => 'Gider (Bugün)',
                    'value' => '₺' . number_format($this->getTodayExpense(), 2, ',', '.'),
                    'icon' => 'heroicon-o-arrow-trending-down',
                    'color' => 'danger',
                    'trend' => $this->getYesterdayExpense() == 0 ? 'up' : ($this->getTodayExpense() > $this->getYesterdayExpense() ? 'up' : 'down'),
                    'description' => $this->getTodayIncrement('expense'),
                ],
                [
                    'label' => 'Gelir (Bu Ay)',
                    'value' => '₺' . number_format($this->getCurrentMonthIncome(), 2, ',', '.'),
                    'icon' => 'heroicon-o-arrow-trending-up',
                    'color' => 'success',
                    'trend' => $this->getLastMonthIncome() == 0 ? 'up' : ($this->getCurrentMonthIncome() > $this->getLastMonthIncome() ? 'up' : 'down'),
                    'description' => $this->getCurrentMonthIncrement('income'),
                ],
                [
                    'label' => 'Gider (Bu Ay)',
                    'value' => '₺' . number_format($this->getCurrentMonthExpense(), 2, ',', '.'),
                    'icon' => 'heroicon-o-arrow-trending-down',
                    'color' => 'danger',
                    'trend' => $this->getLastMonthExpense() == 0 ? 'up' : ($this->getCurrentMonthExpense() > $this->getLastMonthExpense() ? 'up' : 'down'),
                    'description' => $this->getCurrentMonthIncrement('expense'),
                ],
            ];
        } catch (\Exception $e) {
            \Log::error('Error generating transaction stats: ' . $e->getMessage());
            return [
                [
                    'label' => 'Gelir (Bugün)',
                    'value' => '₺0,00',
                    'icon' => 'heroicon-o-arrow-trending-up',
                    'color' => 'success',
                    'trend' => 'up',
                    'description' => '-',
                ],
                [
                    'label' => 'Gider (Bugün)',
                    'value' => '₺0,00',
                    'icon' => 'heroicon-o-arrow-trending-down',
                    'color' => 'danger',
                    'trend' => 'up',
                    'description' => '-',
                ],
                [
                    'label' => 'Gelir (Bu Ay)',
                    'value' => '₺0,00',
                    'icon' => 'heroicon-o-arrow-trending-up',
                    'color' => 'success',
                    'trend' => 'up',
                    'description' => '-',
                ],
                [
                    'label' => 'Gider (Bu Ay)',
                    'value' => '₺0,00',
                    'icon' => 'heroicon-o-arrow-trending-down',
                    'color' => 'danger',
                    'trend' => 'up',
                    'description' => '-',
                ],
            ];
        }
    }

    /**
     * Bugünkü gelir toplamını döndürür
     * 
     * @return float Bugünkü gelir toplamı
     */
    private function getTodayIncome(): float
    {
        try {
            return Transaction::query()
                ->where('user_id', auth()->id())
                ->where('type', 'income')
                ->whereDate('date', Carbon::today())
                ->whereNull('reference_id')
                ->sum('try_equivalent');
        } catch (\Exception $e) {
            \Log::error('Error fetching today income: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Bugünkü gider toplamını döndürür
     * 
     * @return float Bugünkü gider toplamı
     */
    private function getTodayExpense(): float
    {
        try {
            return Transaction::query()
                ->where('user_id', auth()->id())
                ->where('type', 'expense')
                ->whereDate('date', Carbon::today())
                ->whereNull('reference_id')
                ->sum('try_equivalent');
        } catch (\Exception $e) {
            \Log::error('Error fetching today expense: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Bu ayki gelir toplamını döndürür
     * 
     * @return float Bu ayki gelir toplamı
     */
    private function getCurrentMonthIncome(): float
    {
        try {
            return Transaction::query()
                ->where('user_id', auth()->id())
                ->where('type', 'income')
                ->whereYear('date', Carbon::now()->year)
                ->whereMonth('date', Carbon::now()->month)
                ->whereNull('reference_id')
                ->sum('try_equivalent');
        } catch (\Exception $e) {
            \Log::error('Error fetching current month income: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Bu ayki gider toplamını döndürür
     * 
     * @return float Bu ayki gider toplamı
     */
    private function getCurrentMonthExpense(): float
    {
        try {
            return Transaction::query()
                ->where('user_id', auth()->id())
                ->where('type', 'expense')
                ->whereYear('date', Carbon::now()->year)
                ->whereMonth('date', Carbon::now()->month)
                ->whereNull('reference_id')
                ->sum('try_equivalent');
        } catch (\Exception $e) {
            \Log::error('Error fetching current month expense: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Bugünkü artış/azalış oranını hesaplar
     * 
     * @param string $type İşlem tipi (income/expense)
     * @return string Artış/azalış oranı ve yönü
     */
    private function getTodayIncrement($type): string
    {
        try {
            if ($type === 'income') {
                $today = $this->getTodayIncome();
                $yesterday = $this->getYesterdayIncome();
            } else {
                $today = $this->getTodayExpense();
                $yesterday = $this->getYesterdayExpense();
            }
            
            if ($yesterday == 0) return 'İlk gün';
            
            $percentage = (($today - $yesterday) / abs($yesterday)) * 100;
            
            return number_format(abs($percentage), 1, ',', '.') . '% ' . 
                ($percentage >= 0 ? 'artış' : 'azalış');
        } catch (\Exception $e) {
            \Log::error('Error calculating today increment: ' . $e->getMessage());
            return 'Hesaplanamadı';
        }
    }

    /**
     * Bu ayki artış/azalış oranını hesaplar
     * 
     * @param string $type İşlem tipi (income/expense)
     * @return string Artış/azalış oranı ve yönü
     */
    private function getCurrentMonthIncrement($type): string
    {
        try {
            if ($type === 'income') {
                $currentMonth = $this->getCurrentMonthIncome();
                $lastMonth = $this->getLastMonthIncome();
            } else {
                $currentMonth = $this->getCurrentMonthExpense();
                $lastMonth = $this->getLastMonthExpense();
            }
            
            if ($lastMonth == 0) return 'İlk ay';
            
            $percentage = (($currentMonth - $lastMonth) / abs($lastMonth)) * 100;
            
            return number_format(abs($percentage), 1, ',', '.') . '% ' . 
                ($percentage >= 0 ? 'artış' : 'azalış');
        } catch (\Exception $e) {
            \Log::error('Error calculating month increment: ' . $e->getMessage());
            return 'Hesaplanamadı';
        }
    }

    /**
     * Dünkü gelir toplamını döndürür
     * 
     * @return float Dünkü gelir toplamı
     */
    private function getYesterdayIncome(): float
    {
        try {
            return Transaction::query()
                ->where('user_id', auth()->id())
                ->where('type', 'income')
                ->whereDate('date', Carbon::yesterday())
                ->whereNull('reference_id')
                ->sum('try_equivalent');
        } catch (\Exception $e) {
            \Log::error('Error fetching yesterday income: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Dünkü gider toplamını döndürür
     * 
     * @return float Dünkü gider toplamı
     */
    private function getYesterdayExpense(): float
    {
        try {
            return Transaction::query()
                ->where('user_id', auth()->id())
                ->where('type', 'expense')
                ->whereDate('date', Carbon::yesterday())
                ->whereNull('reference_id')
                ->sum('try_equivalent');
        } catch (\Exception $e) {
            \Log::error('Error fetching yesterday expense: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Geçen ayki gelir toplamını döndürür
     * 
     * @return float Geçen ayki gelir toplamı
     */
    private function getLastMonthIncome(): float
    {
        try {
            return Transaction::query()
                ->where('user_id', auth()->id())
                ->where('type', 'income')
                ->whereYear('date', Carbon::now()->subMonth()->year)
                ->whereMonth('date', Carbon::now()->subMonth()->month)
                ->whereNull('reference_id')
                ->sum('try_equivalent');
        } catch (\Exception $e) {
            \Log::error('Error fetching last month income: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Geçen ayki gider toplamını döndürür
     * 
     * @return float Geçen ayki gider toplamı
     */
    private function getLastMonthExpense(): float
    {
        try {
            return Transaction::query()
                ->where('user_id', auth()->id())
                ->where('type', 'expense')
                ->whereYear('date', Carbon::now()->subMonth()->year)
                ->whereMonth('date', Carbon::now()->subMonth()->month)
                ->whereNull('reference_id')
                ->sum('try_equivalent');
        } catch (\Exception $e) {
            \Log::error('Error fetching last month expense: ' . $e->getMessage());
            return 0;
        }
    }
} 